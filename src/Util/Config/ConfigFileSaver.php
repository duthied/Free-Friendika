<?php

namespace Friendica\Util\Config;

/**
 * The ConfigFileSaver saves specific variables into the config-files
 *
 * It is capable of loading the following config files:
 * - *.config.php   (current)
 * - *.ini.php      (deprecated)
 * - *.htconfig.php (deprecated)
 */
class ConfigFileSaver extends ConfigFileManager
{
	/**
	 * The standard indentation for config files
	 * @var string
	 */
	const INDENT = "\t";

	/**
	 * The settings array to save to
	 * @var array
	 */
	private $settings = [];

	/**
	 * Adds a given value to the config file
	 * Either it replaces the current value or it will get added
	 *
	 * @param string $cat   The configuration category
	 * @param string $key   The configuration key
	 * @param string $value The new value
	 */
	public function addConfigValue($cat, $key, $value)
	{
		$this->settings[] = ['cat' => $cat, 'key' => $key, 'value' => $value];
	}

	/**
	 * Resetting all added configuration entries so far
	 */
	public function reset()
	{
		$this->settings = [];
	}

	/**
	 * Save all added configuration entries to the given config files
	 * After updating the config entries, all configuration entries will be reseted
	 *
	 * @param string $name The name of the configuration file (default is empty, which means the default name each type)
	 *
	 * @return bool true, if at least one configuration file was successfully updated
	 */
	public function saveToConfigFile($name = '')
	{
		$saved = false;

		// Check for the *.config.php file inside the /config/ path
		list($reading, $writing) = $this->openFile($this->getConfigFullName($name));
		if (isset($reading) && isset($writing)) {
			$this->saveConfigFile($reading, $writing);
			// Close the current file handler and rename them
			if ($this->closeFile($this->getConfigFullName($name), $reading, $writing)) {
				// just return true, if everything went fine
				$saved = true;
			}
		}

		// Check for the *.ini.php file inside the /config/ path
		list($reading, $writing) = $this->openFile($this->getIniFullName($name));
		if (isset($reading) && isset($writing)) {
			$this->saveINIConfigFile($reading, $writing);
			// Close the current file handler and rename them
			if ($this->closeFile($this->getIniFullName($name), $reading, $writing)) {
				// just return true, if everything went fine
				$saved = true;
			}
		}

		// Check for the *.php file (normally .htconfig.php) inside the / path
		list($reading, $writing) = $this->openFile($this->getHtConfigFullName($name));
		if (isset($reading) && isset($writing)) {
			$this->saveToLegacyConfig($reading, $writing);
			// Close the current file handler and rename them
			if ($this->closeFile($this->getHtConfigFullName($name), $reading, $writing)) {
				// just return true, if everything went fine
				$saved = true;
			}
		}

		$this->reset();

		return $saved;
	}

	/**
	 * Opens a config file and returns two handler for reading and writing
	 *
	 * @param string $fullName The full name of the current config
	 *
	 * @return array An array containing the two reading and writing handler
	 */
	private function openFile($fullName)
	{
		if (empty($fullName)) {
			return [null, null];
		}

		$reading = fopen($fullName, 'r');

		if (!$reading) {
			return [null, null];
		}

		$writing = fopen($fullName . '.tmp', 'w');

		if (!$writing) {
			fclose($reading);
			return [null, null];
		}

		return [$reading, $writing];
	}

	/**
	 * Close and rename the config file
	 *
	 * @param string   $fullName The full name of the current config
	 * @param resource $reading  The reading resource handler
	 * @param resource $writing  The writing resource handler
	 *
	 * @return bool True, if the close was successful
	 */
	private function closeFile($fullName, $reading, $writing)
	{
		fclose($reading);
		fclose($writing);

		if (!rename($fullName, $fullName . '.old')) {
			return false;
		}

		if (!rename($fullName . '.tmp', $fullName)) {
			// revert the move of the current config file to have at least the old config
			rename($fullName . '.old', $fullName);
			return false;
		}

		return true;
	}

	/**
	 * Saves all configuration values to a config file
	 *
	 * @param resource $reading The reading handler
	 * @param resource $writing The writing handler
	 */
	private function saveConfigFile($reading, $writing)
	{
		$settingsCount = count(array_keys($this->settings));
		$categoryFound = array_fill(0, $settingsCount, false);
		$categoryBracketFound = array_fill(0, $settingsCount, false);;
		$lineFound = array_fill(0, $settingsCount, false);;
		$lineArrowFound = array_fill(0, $settingsCount, false);;

		while (!feof($reading)) {

			$line = fgets($reading);

			// check for each added setting if we have to replace a config line
			for ($i = 0; $i < $settingsCount; $i++) {

				// find the first line like "'system' =>"
				if (!$categoryFound[$i] && stristr($line, sprintf('\'%s\'', $this->settings[$i]['cat']))) {
					$categoryFound[$i] = true;
				}

				// find the first line with a starting bracket ( "[" )
				if ($categoryFound[$i] && !$categoryBracketFound[$i] && stristr($line, '[')) {
					$categoryBracketFound[$i] = true;
				}

				// find the first line with the key like "'value'"
				if ($categoryBracketFound[$i] && !$lineFound[$i] && stristr($line, sprintf('\'%s\'', $this->settings[$i]['key']))) {
					$lineFound[$i] = true;
				}

				// find the first line with an arrow ("=>") after finding the key
				if ($lineFound[$i] && !$lineArrowFound[$i] && stristr($line, '=>')) {
					$lineArrowFound[$i] = true;
				}

				// find the current value and replace it
				if ($lineArrowFound[$i] && preg_match_all('/\'(.*?)\'/', $line, $matches, PREG_SET_ORDER)) {
					$lineVal = end($matches)[0];
					$line = str_replace($lineVal, '\'' . $this->settings[$i]['value'] . '\'', $line);
					$categoryFound[$i] = false;
					$categoryBracketFound[$i] = false;
					$lineFound[$i] = false;
					$lineArrowFound[$i] = false;
					// if a line contains a closing bracket for the category ( "]" ) and we didn't find the key/value pair,
					// add it as a new line before the closing bracket
				} elseif ($categoryBracketFound[$i] && !$lineArrowFound[$i] && stristr($line, ']')) {
					$categoryFound[$i] = false;
					$categoryBracketFound[$i] = false;
					$lineFound[$i] = false;
					$lineArrowFound[$i] = false;
					$newLine = sprintf(self::INDENT . self::INDENT . '\'%s\' => \'%s\',' . PHP_EOL, $this->settings[$i]['key'], $this->settings[$i]['value']);
					$line = $newLine . $line;
				}
			}

			fputs($writing, $line);
		}
	}

	/**
	 * Saves a value to a ini file
	 *
	 * @param resource $reading The reading handler
	 * @param resource $writing The writing handler
	 */
	private function saveINIConfigFile($reading, $writing)
	{
		$settingsCount = count(array_keys($this->settings));
		$categoryFound = array_fill(0, $settingsCount, false);

		while (!feof($reading)) {

			$line = fgets($reading);

			// check for each added setting if we have to replace a config line
			for ($i = 0; $i < $settingsCount; $i++) {

				if (!$categoryFound[$i] && stristr($line, sprintf('[%s]', $this->settings[$i]['cat']))) {
					$categoryFound[$i] = true;
				} elseif ($categoryFound[$i] && preg_match_all('/^' . $this->settings[$i]['key'] . '\s*=\s*(.*?)$/', $line, $matches, PREG_SET_ORDER)) {
					$line = $this->settings[$i]['key'] . ' = ' . $this->settings[$i]['value'] . PHP_EOL;
					$categoryFound[$i] = false;
				} elseif ($categoryFound[$i] && (preg_match_all('/^\[.*?\]$/', $line) || preg_match_all('/^INI;.*$/', $line))) {
					$categoryFound[$i] = false;
					$newLine = $this->settings[$i]['key'] . ' = ' . $this->settings[$i]['value'] . PHP_EOL;
					$line = $newLine . $line;
				}
			}

			fputs($writing, $line);
		}
	}

	/**
	 * Saves a value to a .php file (normally .htconfig.php)
	 *
	 * @param resource $reading The reading handler
	 * @param resource $writing The writing handler
	 */
	private function saveToLegacyConfig($reading, $writing)
	{
		$settingsCount = count(array_keys($this->settings));
		$found  = array_fill(0, $settingsCount, false);
		while (!feof($reading)) {

			$line = fgets($reading);

			// check for each added setting if we have to replace a config line
			for ($i = 0; $i < $settingsCount; $i++) {

				if ($this->settings[$i]['cat'] !== 'config' && preg_match_all('/^\$a\-\>config\[\'' . $this->settings[$i]['cat'] . '\'\]\[\'' . $this->settings[$i]['key'] . '\'\]\s*=\s\'*(.*?)\';$/', $line, $matches, PREG_SET_ORDER)) {
					$line = '$a->config[\'' . $this->settings[$i]['cat'] . '\'][\'' . $this->settings[$i]['key'] . '\'] = \'' . $this->settings[$i]['value'] . '\';' . PHP_EOL;
					$found[$i] = true;
				} elseif ($this->settings[$i]['cat'] === 'config' && preg_match_all('/^\$a\-\>config\[\'' . $this->settings[$i]['key'] . '\'\]\s*=\s\'*(.*?)\';$/', $line, $matches, PREG_SET_ORDER)) {
					$line = '$a->config[\'' . $this->settings[$i]['key'] . '\'] = \'' . $this->settings[$i]['value'] . '\';' . PHP_EOL;
					$found[$i] = true;
				}
			}

			fputs($writing, $line);
		}

		for ($i = 0; $i < $settingsCount; $i++) {
			if (!$found[$i]) {
				if ($this->settings[$i]['cat'] !== 'config') {
					$line = '$a->config[\'' . $this->settings[$i]['cat'] . '\'][\'' . $this->settings[$i]['key'] . '\'] = \'' . $this->settings[$i]['value'] . '\';' . PHP_EOL;
				} else {
					$line = '$a->config[\'' . $this->settings[$i]['key'] . '\'] = \'' . $this->settings[$i]['value'] . '\';' . PHP_EOL;
				}

				fputs($writing, $line);
			}
		}
	}
}
