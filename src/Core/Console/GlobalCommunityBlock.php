<?php

namespace Friendica\Core\Console;

use Friendica\Core\L10n;
use Friendica\Model\Contact;

/**
 * Description of GlobalCommunityBlock
 *
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class GlobalCommunityBlock extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	protected function getHelp()
	{
		$help = <<<HELP
console globalcommunityblock - Silence remote profile from global community page
Usage
	bin/console globalcommunityblock <profile_url> [-h|--help|-?] [-v]

Description
	bin/console globalcommunityblock <profile_url>
		Silences the provided remote profile URL from the global community page

Options
    -h|--help|-? Show help information
    -v           Show more debug information.
HELP;
		return $help;
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

		require_once '.htconfig.php';
		$result = \dba::connect($db_host, $db_user, $db_pass, $db_data);
		unset($db_host, $db_user, $db_pass, $db_data);

		if (!$result) {
			throw new \RuntimeException('Unable to connect to database');
		}

		$contact_id = Contact::getIdForURL($argv[1]);
		if (!$contact_id) {
			throw new \RuntimeException(L10n::t('Could not find any contact entry for this URL (%s)', $nurl));
		}
		Contact::block($contact_id);
		$this->out(L10n::t('The contact has been blocked from the node'));

		return 0;
	}

}
