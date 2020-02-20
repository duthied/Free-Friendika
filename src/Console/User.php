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

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Database\Database;
use Friendica\Model\User as UserModel;
use RuntimeException;

/**
 * tool to set a new password for a user
 *
 * With this tool, you can set a new password for a user
 */
class User extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	/**
	 * @var App\Mode
	 */
	private $appMode;
	/**
	 * @var L10n
	 */
	private $l10n;
	/**
	 * @var Database
	 */
	private $dba;

	protected function getHelp()
	{
		$help = <<<HELP
console user - Modify user settings per console commands.
Usage
	bin/console user <nickname> password [<password>] [-h|--help|-?] [-v]

Description
	Modify user settings per console commands.

Options
    -h|--help|-? Show help information
    -v           Show more debug information.
HELP;
		return $help;
	}

	public function __construct(App\Mode $appMode, L10n $l10n, Database $dba, array $argv = null)
	{
		parent::__construct($argv);

		$this->appMode = $appMode;
		$this->l10n = $l10n;
		$this->dba = $dba;
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

		if (count($this->args) < 2) {
			throw new \Asika\SimpleConsole\CommandArgsException('Not enough arguments.');
		}

		if ($this->appMode->isInstall()) {
			throw new RuntimeException('Database isn\'t ready or populated yet');
		}

		$nick = $this->getArgument(0);

		$user = $this->dba->selectFirst('user', ['uid'], ['nickname' => $nick]);
		if (!$this->dba->isResult($user)) {
			throw new RuntimeException($this->l10n->t('User not found'));
		}

		$command = $this->getArgument(1);

		switch ($command) {
			case 'password':
				return $this->setPassword($user);
			default:
				throw new \Asika\SimpleConsole\CommandArgsException('Wrong command.');
		}
	}

	/**
	 * Sets a new password
	 *
	 * @param array $user The user
	 *
	 * @return int Return code of this command
	 */
	private function setPassword(array $user)
	{
		$password = $this->getArgument(2);

		if (is_null($password)) {
			$this->out($this->l10n->t('Enter new password: '), false);
			$password = \Seld\CliPrompt\CliPrompt::hiddenPrompt(true);
		}

		try {
			$result = UserModel::updatePassword($user['uid'], $password);

			if (!$this->dba->isResult($result)) {
				throw new \Exception($this->l10n->t('Password update failed. Please try again.'));
			}

			$this->out($this->l10n->t('Password changed.'));
		} catch (\Exception $e) {
			throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
		}

		return 0;
	}
}
