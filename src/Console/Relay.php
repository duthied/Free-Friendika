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
use Friendica\Database\DBA;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Protocol\ActivityPub\Transmitter;

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
class Relay extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	/**
	 * @var App\Mode
	 */
	private $appMode;

	protected function getHelp()
	{
		$help = <<<HELP
console relay - Manage ActivityPub relay configuration
Synopsis
	bin/console relay [-h|--help|-?] [-v]
	bin/console relay add <actor> [-h|--help|-?] [-v]
	bin/console relay remove <actoor> [-h|--help|-?] [-v]

Description
	bin/console relay
		Lists all active relais

	bin/console relay add <actor>
		Add a relay actor

	bin/console relay remove <actoor>
		Remove a relay actor

Options
    -h|--help|-? Show help information
    -v           Show more debug information.
HELP;
		return $help;
	}

	public function __construct(App\Mode $appMode, array $argv = null)
	{
		parent::__construct($argv);

		$this->appMode = $appMode;
	}

	protected function doExecute()
	{
		if ($this->getOption('v')) {
			$this->out('Executable: ' . $this->executable);
			$this->out('Class: ' . __CLASS__);
			$this->out('Arguments: ' . var_export($this->args, true));
			$this->out('Options: ' . var_export($this->options, true));
		}

		if (count($this->args) > 2) {
			throw new CommandArgsException('Too many arguments');
		}

		if (count($this->args) == 1) {
			throw new CommandArgsException('Too few arguments');
		}

		if (count($this->args) == 0) {
			$contacts = DBA::select('apcontact', ['url'],
			["`type` = ? AND `url` IN (SELECT `url` FROM `contact` WHERE `uid` = ? AND `rel` IN (?, ?))",
				'Application', 0, Contact::FOLLOWER, Contact::FRIEND]);
			while ($contact = DBA::fetch($contacts)) {
				$this->out($contact['url']);
			}
			DBA::close($contacts);
		}

		if (count($this->args) == 2) {
			$mode = $this->getArgument(0);
			$actor = $this->getArgument(1);

			$apcontact = APContact::getByURL($actor);
			if (empty($apcontact) || ($apcontact['type'] != 'Application')) {
				$this->out($actor . ' is no relay actor');
				return 1;
			}

			if ($mode == 'add') {
				if (Transmitter::sendRelayFollow($actor)) {
					$this->out('Successfully added ' . $actor);
				} else {
					$this->out($actor . " couldn't be added");
				}
			} elseif ($mode == 'remove') {
				if (Transmitter::sendRelayUndoFollow($actor)) {
					$this->out('Successfully removed ' . $actor);
				} else {
					$this->out($actor . " couldn't be removed");
				}
			} else {
				throw new CommandArgsException($mode . ' is no valid command');
			}
		}

		return 0;
	}
}
