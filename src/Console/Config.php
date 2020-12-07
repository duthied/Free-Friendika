<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Console;

use Asika\SimpleConsole\CommandArgsException;
use Friendica\App;
use Friendica\Core\Config\IConfig;
use RuntimeException;

/**
 * tool to access the system config from the CLI
 *
 * With this script you can access the system configuration of your node from
 * the CLI. You can do both, reading current values stored in the database and
 * set new values to config variables.
 *
 * Usage:
 *   If you specify no parameters at the CLI, the script will list all config
 *   variables defined.
 *
 *   If you specify one parameter, the script will list all config variables
 *   defined in this section of the configuration (e.g. "system").
 *
 *   If you specify two parameters, the script will show you the current value
 *   of the named configuration setting. (e.g. "system loglevel")
 *
 *   If you specify three parameters, the named configuration setting will be
 *   set to the value of the last parameter. (e.g. "system loglevel 0" will
 *   disable logging)
 */
class Config extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	/**
	 * @var App\Mode
	 */
	private $appMode;
	/**
	 * @var IConfig
	 */
	private $config;

	protected function getHelp()
	{
		$help = <<<HELP
console config - Manage site configuration
Synopsis
	bin/console config [-h|--help|-?] [-v]
	bin/console config <category> [-h|--help|-?] [-v]
	bin/console config <category> <key> [-h|--help|-?] [-v]
	bin/console config <category> <key> <value> [-h|--help|-?] [-v]

Description
	bin/console config
		Lists all config values

	bin/console config <category>
		Lists all config values in the provided category

	bin/console config <category> <key>
		Shows the value of the provided key in the category

	bin/console config <category> <key> <value>
		Sets the value of the provided key in the category

Notes:
	Setting config entries which are manually set in config/local.config.php may result in
	conflict between database settings and the manual startup settings.

Options
    -h|--help|-? Show help information
    -v           Show more debug information.
HELP;
		return $help;
	}

	public function __construct(App\Mode $appMode, IConfig $config, array $argv = null)
	{
		parent::__construct($argv);

		$this->appMode = $appMode;
		$this->config = $config;
	}

	protected function doExecute()
	{
		if ($this->getOption('v')) {
			$this->out('Executable: ' . $this->executable);
			$this->out('Class: ' . __CLASS__);
			$this->out('Arguments: ' . var_export($this->args, true));
			$this->out('Options: ' . var_export($this->options, true));
		}

		if (count($this->args) > 3) {
			throw new CommandArgsException('Too many arguments');
		}

		if (!$this->appMode->has(App\Mode::DBCONFIGAVAILABLE)) {
			$this->out('Database isn\'t ready or populated yet, showing file config only');
		}

		if (count($this->args) == 3) {
			$cat = $this->getArgument(0);
			$key = $this->getArgument(1);
			$value = $this->getArgument(2);

			if (is_array($this->config->get($cat, $key))) {
				throw new RuntimeException("$cat.$key is an array and can't be set using this command.");
			}

			$result = $this->config->set($cat, $key, $value);
			if ($result) {
				$this->out("{$cat}.{$key} <= " .
				           $this->config->get($cat, $key));
			} else {
				$this->out("Unable to set {$cat}.{$key}");
			}
		}

		if (count($this->args) == 2) {
			$cat = $this->getArgument(0);
			$key = $this->getArgument(1);
			$value = $this->config->get($this->getArgument(0), $this->getArgument(1));

			if (is_array($value)) {
				foreach ($value as $k => $v) {
					$this->out("{$cat}.{$key}[{$k}] => " . (is_array($v) ? implode(', ', $v) : $v));
				}
			} else {
				$this->out("{$cat}.{$key} => " . $value);
			}
		}

		if (count($this->args) == 1) {
			$cat = $this->getArgument(0);
			$this->config->load($cat);
			$configCache = $this->config->getCache();

			if ($configCache->get($cat) !== null) {
				$this->out("[{$cat}]");
				$catVal = $configCache->get($cat);
				foreach ($catVal as $key => $value) {
					if (is_array($value)) {
						foreach ($value as $k => $v) {
							$this->out("{$key}[{$k}] => " . (is_array($v) ? implode(', ', $v) : $v));
						}
					} else {
						$this->out("{$key} => " . $value);
					}
				}
			} else {
				$this->out('Config section ' . $this->getArgument(0) . ' returned nothing');
			}
		}

		if (count($this->args) == 0) {
			$this->config->load();

			if ($this->config->get('system', 'config_adapter') == 'jit' && $this->appMode->has(App\Mode::DBCONFIGAVAILABLE)) {
				$this->out('Warning: The JIT (Just In Time) Config adapter doesn\'t support loading the entire configuration, showing file config only');
			}

			$config = $this->config->getCache()->getAll();
			foreach ($config as $cat => $section) {
				if (is_array($section)) {
					foreach ($section as $key => $value) {
						if (is_array($value)) {
							foreach ($value as $k => $v) {
								$this->out("{$cat}.{$key}[{$k}] => " . (is_array($v) ? implode(', ', $v) : $v));
							}
						} else {
							$this->out("{$cat}.{$key} => " . $value);
						}
					}
				} else {
					$this->out("config.{$cat} => " . $section);
				}
			}
		}

		return 0;
	}
}
