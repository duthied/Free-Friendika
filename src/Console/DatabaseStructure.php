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

use Friendica\Core\Config\Cache;
use Friendica\Core\Update;
use Friendica\Database\Database;
use Friendica\Database\DBStructure;
use RuntimeException;

/**
 * Performs database updates from the command line
 */
class DatabaseStructure extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	/**
	 * @var Database
	 */
	private $dba;
	/**
	 * @var Cache
	 */
	private $configCache;

	protected function getHelp()
	{
		$help = <<<HELP
console dbstructure - Performs database updates
Usage
	bin/console dbstructure <command> [-h|--help|-?] |-f|--force] [-v]

Commands
	dryrun   Show database update schema queries without running them
	update   Update database schema
	dumpsql  Dump database schema
	toinnodb Convert all tables from MyISAM to InnoDB

Options
    -h|--help|-?       Show help information
    -v                 Show more debug information.
    -f|--force         Force the update command (Even if the database structure matches)
    -o|--override      Override running or stalling updates
HELP;
		return $help;
	}

	public function __construct(Database $dba, Cache $configCache, $argv = null)
	{
		parent::__construct($argv);

		$this->dba = $dba;
		$this->configCache = $configCache;
	}

	protected function doExecute()
	{
		if ($this->getOption('v')) {
			$this->out('Class: ' . __CLASS__);
			$this->out('Arguments: ' . var_export($this->args, true));
			$this->out('Options: ' . var_export($this->options, true));
		}

		if (count($this->args) == 0) {
			$this->out($this->getHelp());
			return 0;
		}

		if (count($this->args) > 1) {
			throw new \Asika\SimpleConsole\CommandArgsException('Too many arguments');
		}

		if (!$this->dba->isConnected()) {
			throw new RuntimeException('Unable to connect to database');
		}

		$basePath = $this->configCache->get('system', 'basepath');

		switch ($this->getArgument(0)) {
			case "dryrun":
				$output = DBStructure::update($basePath, true, false);
				break;
			case "update":
				$force    = $this->getOption(['f', 'force'], false);
				$override = $this->getOption(['o', 'override'], false);
				$output = Update::run($basePath, $force, $override,true, false);
				break;
			case "dumpsql":
				ob_start();
				DBStructure::printStructure($basePath);
				$output = ob_get_clean();
				break;
			case "toinnodb":
				ob_start();
				DBStructure::convertToInnoDB();
				$output = ob_get_clean();
				break;
			default:
				$output = 'Unknown command: ' . $this->getArgument(0);
		}

		$this->out($output);

		return 0;
	}
}
