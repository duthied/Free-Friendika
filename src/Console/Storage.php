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
use Friendica\Core\StorageManager;

/**
 * tool to manage storage backend and stored data from CLI
 */
class Storage extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	/** @var StorageManager */
	private $storageManager;

	/**
	 * @param StorageManager $storageManager
	 */
	public function __construct(StorageManager $storageManager, array $argv = [])
	{
		parent::__construct($argv);

		$this->storageManager = $storageManager;
	}

	protected function getHelp()
	{
		$help = <<<HELP
console storage - manage storage backend and stored data
Synopsis
    bin/console storage [-h|--help|-?] [-v]
        Show this help
    
    bin/console storage list
        List available storage backends
    
    bin/console storage set <name>
        Set current storage backend
            name        storage backend to use. see "list".
    
    bin/console storage move [table] [-n 5000]
        Move stored data to current storage backend.
            table       one of "photo" or "attach". default to both
            -n          limit of processed entry batch size
HELP;
		return $help;
	}

	protected function doExecute()
	{
		if ($this->getOption('v')) {
			$this->out('Executable: ' . $this->executable);
			$this->out('Class: ' . __CLASS__);
			$this->out('Arguments: ' . var_export($this->args, true));
			$this->out('Options: ' . var_export($this->options, true));
		}

		if (count($this->args) == 0) {
			$this->out($this->getHelp());
			return -1;
		}

		switch ($this->args[0]) {
			case 'list':
				return $this->doList();
				break;
			case 'set':
				return $this->doSet();
				break;
			case 'move':
				return $this->doMove();
				break;
		}

		$this->out(sprintf('Invalid action "%s"', $this->args[0]));
		return -1;
	}

	protected function doList()
	{
		$rowfmt = ' %-3s | %-20s';
		$current = $this->storageManager->getBackend();
		$this->out(sprintf($rowfmt, 'Sel', 'Name'));
		$this->out('-----------------------');
		$isregisterd = false;
		foreach ($this->storageManager->listBackends() as $name => $class) {
			$issel = ' ';
			if ($current::getName() == $name) {
				$issel = '*';
				$isregisterd = true;
			};
			$this->out(sprintf($rowfmt, $issel, $name));
		}

		if ($current === '') {
			$this->out();
			$this->out('This system is using legacy storage system');
		}
		if ($current !== '' && !$isregisterd) {
			$this->out();
			$this->out('The current storage class (' . $current . ') is not registered!');
		}
		return 0;
	}

	protected function doSet()
	{
		if (count($this->args) !== 2) {
			throw new CommandArgsException('Invalid arguments');
		}

		$name = $this->args[1];
		$class = $this->storageManager->getByName($name);

		if ($class === '') {
			$this->out($name . ' is not a registered backend.');
			return -1;
		}

		if (!$this->storageManager->setBackend($class)) {
			$this->out($class . ' is not a valid backend storage class.');
			return -1;
		}

		return 0;
	}

	protected function doMove()
	{
		if (count($this->args) < 1 || count($this->args) > 2) {
			throw new CommandArgsException('Invalid arguments');
		}

		if (count($this->args) == 2) {
			$table = strtolower($this->args[1]);
			if (!in_array($table, ['photo', 'attach'])) {
				throw new CommandArgsException('Invalid table');
			}
			$tables = [$table];
		} else {
			$tables = StorageManager::TABLES;
		}

		$current = $this->storageManager->getBackend();
		$total = 0;

		do {
			$moved = $this->storageManager->move($current, $tables, $this->getOption('n', 5000));
			if ($moved) {
				$this->out(date('[Y-m-d H:i:s] ') . sprintf('Moved %d files', $moved));
			}

			$total += $moved;
		} while ($moved);

		$this->out(sprintf(date('[Y-m-d H:i:s] ') . 'Moved %d files total', $total));
	}
}
