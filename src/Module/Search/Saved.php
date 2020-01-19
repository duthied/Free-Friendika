<?php

namespace Friendica\Module\Search;

use Friendica\BaseModule;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\Strings;

class Saved extends BaseModule
{
	public static function init(array $parameters = [])
	{
		$action = DI::args()->get(2, 'none');
		$search = Strings::escapeTags(trim(rawurldecode($_GET['term'] ?? '')));

		$return_url = $_GET['return_url'] ?? 'search?q=' . urlencode($search);

		if (local_user() && $search) {
			switch ($action) {
				case 'add':
					$fields = ['uid' => local_user(), 'term' => $search];
					if (!DBA::exists('search', $fields)) {
						DBA::insert('search', $fields);
						info(DI::l10n()->t('Search term successfully saved.'));
					} else {
						info(DI::l10n()->t('Search term already saved.'));
					}
					break;

				case 'remove':
					DBA::delete('search', ['uid' => local_user(), 'term' => $search]);
					info(DI::l10n()->t('Search term successfully removed.'));
					break;
			}
		}

		DI::baseUrl()->redirect($return_url);
	}
}
