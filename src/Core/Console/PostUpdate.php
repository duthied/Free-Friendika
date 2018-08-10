<?php

namespace Friendica\Core\Console;

use Friendica\Core\L10n;

/**
 * @brief tool to block an account from the node
 *
 * With this tool, you can block an account in such a way, that no postings
 * or comments this account writes are accepted to the node.
 *
 * License: AGPLv3 or later, same as Friendica
 *
 * @author Tobias Diekershoff <mrpetovan@gmail.com>
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class PostUpdate extends \Asika\SimpleConsole\Console
{
	protected function doExecute()
	{
		$a = get_app();

		if (count($this->args) > 0) {
			throw new \Asika\SimpleConsole\CommandArgsException('Too many arguments');
		}

		if ($a->isInstallMode()) {
			throw new \RuntimeException('Database isn\'t ready or populated yet');
		}

		echo L10n::t('Execute pending post updates.') . "\n";

		while (!\Friendica\Database\PostUpdate::update()) {
			echo '.';
		}

		echo "\n" . L10n::t('All pending post updates are done.') . "\n";

		return 0;
	}
}
