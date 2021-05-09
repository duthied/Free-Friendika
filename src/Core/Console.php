<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Core;

use Dice\Dice;
use Friendica;

/**
 * Description of Console
 */
class Console extends \Asika\SimpleConsole\Console
{
	// Disables the default help handling
	protected $helpOptions = [];
	protected $customHelpOptions = ['h', 'help', '?'];

	/**
	 * @var Dice The DI library
	 */
	protected $dice;

	protected function getHelp()
	{
		$help = <<<HELP
Usage: bin/console [--version] [-h|--help|-?] <command> [<args>] [-v]

Commands:
	cache                  Manage node cache
	config                 Edit site config
	createdoxygen          Generate Doxygen headers
	dbstructure            Do database updates
	docbloxerrorchecker    Check the file tree for DocBlox errors
	extract                Generate translation string file for the Friendica project (deprecated)
	globalcommunityblock   Block remote profile from interacting with this node
	globalcommunitysilence Silence a profile from the global community page
	archivecontact         Archive a contact when you know that it isn't existing anymore
	help                   Show help about a command, e.g (bin/console help config)
	autoinstall            Starts automatic installation of friendica based on values from htconfig.php
	lock                   Edit site locks
	maintenance            Set maintenance mode for this node
	user                   User management
	php2po                 Generate a messages.po file from a strings.php file
	po2php                 Generate a strings.php file from a messages.po file
	typo                   Checks for parse errors in Friendica files
	postupdate             Execute pending post update scripts (can last days)
	serverblock            Manage blocked servers
	storage                Manage storage backend
	relay                  Manage ActivityPub relay servers

Options:
	-h|--help|-? Show help information
	-v           Show more debug information.
HELP;
		return $help;
	}

	protected $subConsoles = [
		'cache'                  => Friendica\Console\Cache::class,
		'config'                 => Friendica\Console\Config::class,
		'createdoxygen'          => Friendica\Console\CreateDoxygen::class,
		'docbloxerrorchecker'    => Friendica\Console\DocBloxErrorChecker::class,
		'dbstructure'            => Friendica\Console\DatabaseStructure::class,
		'extract'                => Friendica\Console\Extract::class,
		'globalcommunityblock'   => Friendica\Console\GlobalCommunityBlock::class,
		'globalcommunitysilence' => Friendica\Console\GlobalCommunitySilence::class,
		'archivecontact'         => Friendica\Console\ArchiveContact::class,
		'autoinstall'            => Friendica\Console\AutomaticInstallation::class,
		'lock'                   => Friendica\Console\Lock::class,
		'maintenance'            => Friendica\Console\Maintenance::class,
		'user'                   => Friendica\Console\User::class,
		'php2po'                 => Friendica\Console\PhpToPo::class,
		'po2php'                 => Friendica\Console\PoToPhp::class,
		'typo'                   => Friendica\Console\Typo::class,
		'postupdate'             => Friendica\Console\PostUpdate::class,
		'serverblock'            => Friendica\Console\ServerBlock::class,
		'storage'                => Friendica\Console\Storage::class,
		'relay'                  => Friendica\Console\Relay::class,
		'fixapdeliveryworkertaskparameters' => Friendica\Console\FixAPDeliveryWorkerTaskParameters::class,
	];

	/**
	 * CliInput Friendica constructor.
	 *
	 * @param Dice $dice The DI library
	 * @param array $argv
	 */
	public function __construct(Dice $dice, array $argv = null)
	{
		parent::__construct($argv);

		$this->dice = $dice;
	}

	protected function doExecute()
	{
		if ($this->getOption('v')) {
			$this->out('Executable: ' . $this->executable);
			$this->out('Arguments: ' . var_export($this->args, true));
			$this->out('Options: ' . var_export($this->options, true));
		}

		$subHelp = false;
		$command = null;

		if ($this->getOption('version')) {
			$this->out('Friendica Console version ' . FRIENDICA_VERSION);

			return 0;
		} elseif ((count($this->options) === 0 || $this->getOption($this->customHelpOptions) === true || $this->getOption($this->customHelpOptions) === 1) && count($this->args) === 0
		) {
		} elseif (count($this->args) >= 2 && $this->getArgument(0) == 'help') {
			$command = $this->getArgument(1);
			$subHelp = true;
			array_shift($this->args);
			array_shift($this->args);
		} elseif (count($this->args) >= 1) {
			$command = $this->getArgument(0);
			array_shift($this->args);
		}

		if (is_null($command)) {
			$this->out($this->getHelp());
			return 0;
		}

		$console = $this->getSubConsole($command);

		if ($subHelp) {
			$console->setOption($this->customHelpOptions, true);
		}

		return $console->execute();
	}

	private function getSubConsole($command)
	{
		if ($this->getOption('v')) {
			$this->out('Command: ' . $command);
		}

		if (!isset($this->subConsoles[$command])) {
			throw new \Asika\SimpleConsole\CommandArgsException('Command ' . $command . ' doesn\'t exist');
		}

		$subargs = $this->args;
		array_unshift($subargs, $this->executable);

		$className = $this->subConsoles[$command];

		Friendica\DI::init($this->dice);

		/** @var Console $subconsole */
		$subconsole = $this->dice->create($className, [$subargs]);

		foreach ($this->options as $name => $value) {
			$subconsole->setOption($name, $value);
		}

		return $subconsole;
	}

}
