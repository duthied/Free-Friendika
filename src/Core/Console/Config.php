<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Friendica\Core\Console;

use Asika\SimpleConsole\CommandArgsException;
use dba;
use Friendica\Core;

require_once 'include/dba.php';
require_once 'include/text.php';

/**
 * @brief tool to access the system config from the CLI
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
 *
 * @author Tobias Diekershoff
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class Config extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

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
	Setting config entries which are manually set in config/local.ini.php may result in
	conflict between database settings and the manual startup settings.

Options
    -h|--help|-? Show help information
    -v           Show more debug information.
HELP;
		return $help;
	}

	protected function doExecute()
	{
		$a = get_app();

		if ($this->getOption('v')) {
			$this->out('Executable: ' . $this->executable);
			$this->out('Class: ' . __CLASS__);
			$this->out('Arguments: ' . var_export($this->args, true));
			$this->out('Options: ' . var_export($this->options, true));
		}

		if (count($this->args) > 3) {
			throw new CommandArgsException('Too many arguments');
		}

		if (!($a->mode & \Friendica\App::MODE_DBCONFIGAVAILABLE)) {
			$this->out('Database isn\'t ready or populated yet, showing file config only');
		}

		if (count($this->args) == 3) {
			$result = Core\Config::set($this->getArgument(0), $this->getArgument(1), $this->getArgument(2));
			if ($result) {
				$this->out("{$this->getArgument(0)}.{$this->getArgument(1)} = " .
					Core\Config::get($this->getArgument(0), $this->getArgument(1)));
			} else {
				$this->out("Unable to set {$this->getArgument(0)}.{$this->getArgument(1)}");
			}
		}

		if (count($this->args) == 2) {
			$this->out("{$this->getArgument(0)}.{$this->getArgument(1)} = " .
				Core\Config::get($this->getArgument(0), $this->getArgument(1)));
		}

		if (count($this->args) == 1) {
			Core\Config::load($this->getArgument(0));

			if (!is_null($a->config[$this->getArgument(0)])) {
				foreach ($a->config[$this->getArgument(0)] as $k => $x) {
					$this->out("{$this->getArgument(0)}.{$k} = " . $x);
				}
			} else {
				$this->out('Config section ' . $this->getArgument(0) . ' returned nothing');
			}
		}

		if (count($this->args) == 0) {
			Core\Config::load();

			if (Core\Config::get('system', 'config_adapter') != 'preload' && $a->mode & \Friendica\App::MODE_DBCONFIGAVAILABLE) {
				$this->out('Warning: The JIT (Just In Time) Config adapter doesn\'t support loading the entire configuration, showing file config only');
			}

			foreach ($a->config as $cat => $section) {
				if (is_array($section)) {
					foreach ($section as $key => $value) {
						if (is_array($value)) {
							foreach ($value as $k => $v) {
								$this->out("{$cat}.{$key}[{$k}] = " . $v);
							}
						} else {
							$this->out("{$cat}.{$key} = " . $value);
						}
					}
				} else {
					$this->out("config.{$cat} = " . $section);
				}
			}
		}

		return 0;
	}
}
