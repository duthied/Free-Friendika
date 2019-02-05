<?php

namespace Friendica\Core\Config;

use Friendica\Core\Addon;

/**
 * The ConfigCacheLoader loads config-files and stores them in a ConfigCache ( @see ConfigCache )
 *
 * It is capable of loading the following config files:
 * - *.config.php   (current)
 * - *.ini.php      (deprecated)
 * - *.htconfig.php (deprecated)
 */
class ConfigCacheLoader
{
	/**
	 * The Sub directory of the config-files
	 * @var string
	 */
	const SUBDIRECTORY   = '/config/';

	private $baseDir;
	private $configDir;

	public function __construct($baseDir)
	{
		$this->baseDir = $baseDir;
		$this->configDir = $baseDir . self::SUBDIRECTORY;
	}

	/**
	 * Load the configuration files
	 *
	 * First loads the default value for all the configuration keys, then the legacy configuration files, then the
	 * expected local.config.php
	 */
	public function loadConfigFiles(ConfigCache $config)
	{
		// Setting at least the basepath we know
		$config->set('system', 'basepath', $this->baseDir);

		$config->loadConfigArray($this->loadCoreConfig('defaults'));
		$config->loadConfigArray($this->loadCoreConfig('settings'));

		// Legacy .htconfig.php support
		if (file_exists($this->baseDir  . '/.htpreconfig.php')) {
			$a = $config;
			include $this->baseDir . '/.htpreconfig.php';
		}

		// Legacy .htconfig.php support
		if (file_exists($this->baseDir . '/.htconfig.php')) {
			$a = $config;

			include $this->baseDir . '/.htconfig.php';

			$config->set('database', 'hostname', $db_host);
			$config->set('database', 'username', $db_user);
			$config->set('database', 'password', $db_pass);
			$config->set('database', 'database', $db_data);
			$charset = $config->get('system', 'db_charset');
			if (isset($charset)) {
				$config->set('database', 'charset', $charset);
			}

			unset($db_host, $db_user, $db_pass, $db_data);

			if (isset($default_timezone)) {
				$config->set('system', 'default_timezone', $default_timezone);
				unset($default_timezone);
			}

			if (isset($pidfile)) {
				$config->set('system', 'pidfile', $pidfile);
				unset($pidfile);
			}

			if (isset($lang)) {
				$config->set('system', 'language', $lang);
				unset($lang);
			}
		}

		$config->loadConfigArray($this->loadCoreConfig('local'), true);
	}

	/**
	 * Tries to load the specified legacy configuration file into the ConfigCache (@see ConfigCache ).
	 *
	 * @deprecated since version 2018.12
	 * @param string $filename
	 *
	 * @return array The configuration
	 * @throws \Exception
	 */
	public function loadINIConfigFile($filename)
	{
		$filepath = $this->configDir . $filename . ".ini.php";

		if (!file_exists($filepath)) {
			throw new \Exception('Error parsing non-existent INI config file ' . $filepath);
		}

		$contents = include($filepath);

		$config = parse_ini_string($contents, true, INI_SCANNER_TYPED);

		if ($config === false) {
			throw new \Exception('Error parsing INI config file ' . $filepath);
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
	 * @param  string $filepath The filepath of the
	 * @return array The config array0
	 *
	 * @throws \Exception if the config cannot get loaded.
	 */
	private function loadConfigFile($filepath)
	{
		if (!file_exists($filepath)) {
			throw new \Exception('Error loading non-existent config file ' . $filepath);
		}

		$config = include($filepath);

		if (!is_array($config)) {
			throw new \Exception('Error loading config file ' . $filepath);
		}

		return $config;
	}

	/**
	 * Tries to load the specified core-configuration and returns the config array.
	 *
	 * @param string $name The name of the configuration
	 *
	 * @return array The config array (empty if no config found)
	 *
	 * @throws \Exception if the configuration file isn't readable
	 */
	public function loadCoreConfig($name)
	{
		if (file_exists($this->configDir . $name . '.config.php')) {
			return $this->loadConfigFile($this->configDir . $name . '.config.php');
		} elseif (file_exists($this->configDir . $name . '.ini.php')) {
			return $this->loadINIConfigFile($this->configDir . $name . '.ini.php');
		} else {
			return [];
		}
	}

	/**
	 * Tries to load the specified addon-configuration and returns the config array.
	 *
	 * First loads all activated addons default configuration through the load_config hook, then load the local.config.php
	 * again to overwrite potential local addon configuration.
	 *
	 * @param string $name The name of the configuration
	 *
	 * @return array The config array (empty if no config found)
	 *
	 * @throws \Exception if the configuration file isn't readable
	 */
	public function loadAddonConfig($name)
	{
		$filepath = $this->baseDir . Addon::DIRECTORY . $name . self::SUBDIRECTORY . $name . ".config.php";

		if (file_exists($filepath)) {
			return $this->loadConfigFile($filepath);
		} else {
			return [];
		}
	}
}
