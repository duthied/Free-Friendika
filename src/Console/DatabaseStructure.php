<?php

namespace Friendica\Console;

use Friendica\Core\Config\Cache\ConfigCache;
use Friendica\Core\Update;
use Friendica\Database\Database;
use Friendica\Database\DBStructure;
use RuntimeException;

/**
 * @brief Performs database updates from the command line
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class DatabaseStructure extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	/**
	 * @var Database
	 */
	private $dba;
	/**
	 * @var ConfigCache
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

	public function __construct(Database $dba, ConfigCache $configCache, $argv = null)
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
