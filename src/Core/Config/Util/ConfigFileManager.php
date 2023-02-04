<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Core\Config\Util;

use Friendica\Core\Addon;
use Friendica\Core\Config\Exception\ConfigFileException;
use Friendica\Core\Config\ValueObject\Cache;

/**
 * The ConfigFileLoader loads and saves config-files and stores them in a ConfigCache ( @see Cache )
 *
 * It is capable of loading the following config files:
 * - *.config.php   (current)
 * - *.ini.php      (deprecated)
 * - *.htconfig.php (deprecated)
 */
class ConfigFileManager
{
	/**
	 * The default name of the user defined legacy config file
	 *
	 * @var string
	 */
	const CONFIG_HTCONFIG = 'htconfig';

	/**
	 * The config file, where overrides per admin page/console are saved at
	 *
	 * @var string
	 */
	const CONFIG_DATA_FILE = 'node.config.php';

	/**
	 * The sample string inside the configs, which shouldn't get loaded
	 *
	 * @var string
	 */
	const SAMPLE_END = '-sample';

	/**
	 * @var string
	 */
	private $baseDir;
	/**
	 * @var string
	 */
	private $configDir;
	/**
	 * @var string
	 */
	private $staticDir;

	/**
	 * @var array
	 */
	private $server;

	/**
	 * @param string $baseDir   The base
	 * @param string $configDir
	 * @param string $staticDir
	 */
	public function __construct(string $baseDir, string $configDir, string $staticDir, array $server = [])
	{
		$this->baseDir   = $baseDir;
		$this->configDir = $configDir;
		$this->staticDir = $staticDir;
		$this->server    = $server;
	}

	/**
	 * Load the configuration files into an configuration cache
	 *
	 * First loads the default value for all the configuration keys, then the legacy configuration files, then the
	 * expected local.config.php
	 *
	 * @param Cache $configCache The config cache to load to
	 * @param bool  $raw         Set up the raw config format
	 *
	 * @throws ConfigFileException
	 */
	public function setupCache(Cache $configCache, bool $raw = false)
	{
		// Load static config files first, the order is important
		$configCache->load($this->loadStaticConfig('defaults'), Cache::SOURCE_STATIC);
		$configCache->load($this->loadStaticConfig('settings'), Cache::SOURCE_STATIC);

		// try to load the legacy config first
		$configCache->load($this->loadLegacyConfig('htpreconfig'), Cache::SOURCE_FILE);
		$configCache->load($this->loadLegacyConfig('htconfig'), Cache::SOURCE_FILE);

		// Now load every other config you find inside the 'config/' directory
		$this->loadCoreConfig($configCache);

		// Now load the node.config.php file with the node specific config values (based on admin gui/console actions)
		$this->loadDataConfig($configCache);

		$configCache->load($this->loadEnvConfig(), Cache::SOURCE_ENV);

		// In case of install mode, add the found basepath (because there isn't a basepath set yet
		if (!$raw && empty($configCache->get('system', 'basepath'))) {
			// Setting at least the basepath we know
			$configCache->set('system', 'basepath', $this->baseDir, Cache::SOURCE_FILE);
		}
	}

	/**
	 * Tries to load the static core-configuration and returns the config array.
	 *
	 * @param string $name The name of the configuration
	 *
	 * @return array The config array (empty if no config found)
	 *
	 * @throws ConfigFileException if the configuration file isn't readable
	 */
	private function loadStaticConfig(string $name): array
	{
		$configName = $this->staticDir . DIRECTORY_SEPARATOR . $name . '.config.php';
		$iniName    = $this->staticDir . DIRECTORY_SEPARATOR . $name . '.ini.php';

		if (file_exists($configName)) {
			return $this->loadConfigFile($configName);
		} else if (file_exists($iniName)) {
			return $this->loadINIConfigFile($iniName);
		} else {
			return [];
		}
	}

	/**
	 * Tries to load the specified core-configuration into the config cache.
	 *
	 * @param Cache $configCache The Config cache
	 *
	 * @throws ConfigFileException if the configuration file isn't readable
	 */
	private function loadCoreConfig(Cache $configCache)
	{
		// try to load legacy ini-files first
		foreach ($this->getConfigFiles(true) as $configFile) {
			$configCache->load($this->loadINIConfigFile($configFile), Cache::SOURCE_FILE);
		}

		// try to load supported config at last to overwrite it
		foreach ($this->getConfigFiles() as $configFile) {
			$configCache->load($this->loadConfigFile($configFile), Cache::SOURCE_FILE);
		}
	}

	/**
	 * Tries to load the data config file with the overridden data
	 *
	 * @param Cache $configCache The Config cache
	 *
	 * @throws ConfigFileException In case the config file isn't loadable
	 */
	private function loadDataConfig(Cache $configCache)
	{
		$filename = $this->configDir . '/' . self::CONFIG_DATA_FILE;

		if (file_exists($filename) && (filesize($filename) > 0)) {

			// The fallback empty return content
			$content = '<?php return [];';

			/**
			 * This code-block creates a readonly node.config.php content stream (fopen() with "r")
			 * The stream is locked shared (LOCK_SH), so not exclusively, but the OS knows that there's a lock
			 *
			 * Any exclusive locking (LOCK_EX) would need to wait until all LOCK_SHs are unlocked
			 */
			if (($configStream = @fopen($filename, 'r')) === false) {
				throw new ConfigFileException(sprintf('Cannot open file "%s" in mode r', $filename));
			}

			try {
				if (flock($configStream, LOCK_SH)) {
					clearstatcache(true, $filename);

					if (($filesize = filesize($filename)) === 0) {
						return;
					}

					$content = fread($configStream, $filesize);
					if (!$content) {
						throw new ConfigFileException(sprintf('Couldn\'t read file %s', $filename));
					}
				} else {
					throw new ConfigFileException(sprintf('Cannot lock file %s', $filename));
				}
			} finally {
				// unlock and close the stream for every circumstances
				flock($configStream, LOCK_UN);
				fclose($configStream);
			}

			/**
			 * Evaluate the content string as PHP code
			 *
			 * @see https://www.php.net/manual/en/function.eval.php
			 *
			 * @note
			 * To leave the PHP mode, we have to use the appropriate PHP tags '?>' as prefix.
			 */
			$dataArray = eval('?>' . $content);

			if (is_array($dataArray)) {
				$configCache->load($dataArray, Cache::SOURCE_DATA);
			}
		}
	}

	/**
	 * Checks, if the node.config.php is writable
	 *
	 * @return bool
	 */
	public function dataIsWritable(): bool
	{
		$filename = $this->configDir . '/' . self::CONFIG_DATA_FILE;

		if (file_exists($filename)) {
			return is_writable($filename);
		} else {
			return is_writable($this->configDir);
		}
	}

	/**
	 * Saves overridden config entries back into the data.config.php
	 *
	 * @param Cache $configCache The config cache
	 *
	 * @throws ConfigFileException In case the config file isn't writeable or the data is invalid
	 */
	public function saveData(Cache $configCache)
	{
		$filename = $this->configDir . '/' . self::CONFIG_DATA_FILE;

		if (file_exists($filename)) {
			$fileExists = true;
		} else {
			$fileExists = false;
		}

		/**
		 * Creates a read-write stream
		 *
		 * @see  https://www.php.net/manual/en/function.fopen.php
		 * @note Open the file for reading and writing. If the file does not exist, it is created.
		 * If it exists, it is neither truncated (as opposed to 'w'), nor the call to this function fails
		 * (as is the case with 'x'). The file pointer is positioned on the beginning of the file.
		 *
		 */
		if (($configStream = @fopen($filename, 'c+')) === false) {
			throw new ConfigFileException(sprintf('Cannot open file "%s" in mode c+', $filename));
		}

		try {
			// We do want an exclusive lock, so we wait until every LOCK_SH (config reading) is unlocked
			if (flock($configStream, LOCK_EX)) {

				/**
				 * If the file exists, we read the whole file again to avoid a race condition with concurrent threads that could have modified the file between the first config read of this thread and now
				 * Since we're currently exclusive locked, no other process can now change the config again
				 */
				if ($fileExists) {
					// When reading the config file too fast, we get a wrong filesize, "clearstatcache" prevents that
					clearstatcache(true, $filename);
					$content = fread($configStream, filesize($filename));
					if (!$content) {
						throw new ConfigFileException(sprintf('Cannot read file %s', $filename));
					}

					// Event truncating the whole content wouldn't automatically rewind the stream,
					// so we need to do it manually
					rewind($configStream);

					$dataArray = eval('?>' . $content);

					// Merge the new content into the existing file based config cache and use it
					// as the new config cache
					if (is_array($dataArray)) {
						$fileConfigCache = new Cache();
						$fileConfigCache->load($dataArray, Cache::SOURCE_DATA);
						$configCache = $fileConfigCache->merge($configCache);
					}
				}

				// Only SOURCE_DATA is wanted, the rest isn't part of the node.config.php file
				$data = $configCache->getDataBySource(Cache::SOURCE_DATA);

				$encodedData = ConfigFileTransformer::encode($data);
				if (!$encodedData) {
					throw new ConfigFileException('config source cannot get encoded');
				}

				// Once again to avoid wrong, implicit "filesize" calls during the fwrite() or ftruncate() call
				clearstatcache(true, $filename);
				if (!ftruncate($configStream, 0) ||
					!fwrite($configStream, $encodedData) ||
					!fflush($configStream)) {
					throw new ConfigFileException(sprintf('Cannot modify locked file %s', $filename));
				}
			} else {
				throw new ConfigFileException(sprintf('Cannot lock file %s', $filename));
			}
		} finally {
			// unlock and close the stream for every circumstances
			flock($configStream, LOCK_UN);
			fclose($configStream);
		}
	}

	/**
	 * Tries to load the specified addon-configuration and returns the config array.
	 *
	 * @param string $name The name of the configuration
	 *
	 * @return array The config array (empty if no config found)
	 *
	 * @throws ConfigFileException if the configuration file isn't readable
	 */
	public function loadAddonConfig(string $name): array
	{
		$filepath = $this->baseDir . DIRECTORY_SEPARATOR .   // /var/www/html/
					Addon::DIRECTORY . DIRECTORY_SEPARATOR . // addon/
					$name . DIRECTORY_SEPARATOR .            // openstreetmap/
					'config' . DIRECTORY_SEPARATOR .         // config/
					$name . ".config.php";                   // openstreetmap.config.php

		if (file_exists($filepath)) {
			return $this->loadConfigFile($filepath);
		} else {
			return [];
		}
	}

	/**
	 * Tries to load environment specific variables, based on the `env.config.php` mapping table
	 *
	 * @return array The config array (empty if no config was found)
	 *
	 * @throws ConfigFileException if the configuration file isn't readable
	 */
	protected function loadEnvConfig(): array
	{
		$filepath = $this->staticDir . DIRECTORY_SEPARATOR .   // /var/www/html/static/
					"env.config.php";                          // env.config.php

		if (!file_exists($filepath)) {
			return [];
		}

		$envConfig = $this->loadConfigFile($filepath);

		$return = [];

		foreach ($envConfig as $envKey => $configStructure) {
			if (isset($this->server[$envKey])) {
				$return[$configStructure[0]][$configStructure[1]] = $this->server[$envKey];
			}
		}

		return $return;
	}

	/**
	 * Get the config files of the config-directory
	 *
	 * @param bool $ini True, if scan for ini-files instead of config files
	 *
	 * @return array
	 */
	private function getConfigFiles(bool $ini = false): array
	{
		$files = scandir($this->configDir);
		$found = [];

		$filePattern = ($ini ? '*.ini.php' : '*.config.php');

		// Don't load sample files
		$sampleEnd = self::SAMPLE_END . ($ini ? '.ini.php' : '.config.php');

		foreach ($files as $filename) {
			if (fnmatch($filePattern, $filename) &&
				substr_compare($filename, $sampleEnd, -strlen($sampleEnd)) &&
				$filename !== self::CONFIG_DATA_FILE) {
				$found[] = $this->configDir . '/' . $filename;
			}
		}

		return $found;
	}

	/**
	 * Tries to load the legacy config files (.htconfig.php, .htpreconfig.php) and returns the config array.
	 *
	 * @param string $name The name of the config file (default is empty, which means .htconfig.php)
	 *
	 * @return array The configuration array (empty if no config found)
	 *
	 * @deprecated since version 2018.09
	 */
	private function loadLegacyConfig(string $name = ''): array
	{
		$name     = !empty($name) ? $name : self::CONFIG_HTCONFIG;
		$fullName = $this->baseDir . DIRECTORY_SEPARATOR . '.' . $name . '.php';

		$config = [];
		if (file_exists($fullName)) {
			$a         = new \stdClass();
			$a->config = [];
			include $fullName;

			$htConfigCategories = array_keys($a->config);

			// map the legacy configuration structure to the current structure
			foreach ($htConfigCategories as $htConfigCategory) {
				if (is_array($a->config[$htConfigCategory])) {
					$keys = array_keys($a->config[$htConfigCategory]);

					foreach ($keys as $key) {
						$config[$htConfigCategory][$key] = $a->config[$htConfigCategory][$key];
					}
				} else {
					$config['config'][$htConfigCategory] = $a->config[$htConfigCategory];
				}
			}

			unset($a);

			if (isset($db_host)) {
				$config['database']['hostname'] = $db_host;
				unset($db_host);
			}
			if (isset($db_user)) {
				$config['database']['username'] = $db_user;
				unset($db_user);
			}
			if (isset($db_pass)) {
				$config['database']['password'] = $db_pass;
				unset($db_pass);
			}
			if (isset($db_data)) {
				$config['database']['database'] = $db_data;
				unset($db_data);
			}
			if (isset($config['system']['db_charset'])) {
				$config['database']['charset'] = $config['system']['db_charset'];
			}
			if (isset($pidfile)) {
				$config['system']['pidfile'] = $pidfile;
				unset($pidfile);
			}
			if (isset($default_timezone)) {
				$config['system']['default_timezone'] = $default_timezone;
				unset($default_timezone);
			}
			if (isset($lang)) {
				$config['system']['language'] = $lang;
				unset($lang);
			}
		}

		return $config;
	}

	/**
	 * Tries to load the specified legacy configuration file and returns the config array.
	 *
	 * @param string $filepath
	 *
	 * @return array The configuration array
	 * @throws ConfigFileException
	 * @deprecated since version 2018.12
	 */
	private function loadINIConfigFile(string $filepath): array
	{
		$contents = include($filepath);

		$config = parse_ini_string($contents, true, INI_SCANNER_TYPED);

		if ($config === false) {
			throw new ConfigFileException('Error parsing INI config file ' . $filepath);
		}

		return $config;
	}

	/**
	 * Tries to load the specified configuration file and returns the config array.
	 *
	 * The config format is PHP array and the template for configuration files is the following:
	 *
	 * <?php return [
	 *      'section' => [
	 *          'key' => 'value',
	 *      ],
	 * ];
	 *
	 * @param string $filepath The filepath of the
	 *
	 * @return array The config array0
	 *
	 * @throws ConfigFileException if the config cannot get loaded.
	 */
	private function loadConfigFile(string $filepath): array
	{
		if (file_exists($filepath)) {
			$config = include $filepath;

			if (!is_array($config)) {
				throw new ConfigFileException('Error loading config file ' . $filepath);
			}

			return $config;
		} else {
			return [];
		}
	}
}
