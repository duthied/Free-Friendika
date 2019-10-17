<?php

namespace Friendica\Console;

use Friendica\App;
use Friendica\Core\L10n\L10n;
use Friendica\Database\Database;
use Friendica\Model\User;
use RuntimeException;

/**
 * @brief tool to set a new password for a user
 *
 * With this tool, you can set a new password for a user
 *
 * License: AGPLv3 or later, same as Friendica
 *
 * @author Michael Vogel <heluecht@pirati.ca>
 */
class NewPassword extends \Asika\SimpleConsole\Console
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
console newpassword - Creates a new password for a given user
Usage
	bin/console newpassword <nickname> [<password>] [-h|--help|-?] [-v]

Description
	Creates a new password for a user without using the "forgot password" functionality.

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

		if (count($this->args) > 2) {
			throw new \Asika\SimpleConsole\CommandArgsException('Too many arguments');
		}

		if ($this->appMode->isInstall()) {
			throw new RuntimeException('Database isn\'t ready or populated yet');
		}

		$nick = $this->getArgument(0);

		$user = $this->dba->selectFirst('user', ['uid'], ['nickname' => $nick]);
		if (!$this->dba->isResult($user)) {
			throw new RuntimeException($this->l10n->t('User not found'));
		}

		$password = $this->getArgument(1);
		if (is_null($password)) {
			$this->out($this->l10n->t('Enter new password: '), false);
			$password = \Seld\CliPrompt\CliPrompt::hiddenPrompt(true);
		}

		try {
			$result = User::updatePassword($user['uid'], $password);

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
