<?php

namespace Friendica\Content\Widget;

use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;

class SavedSearches
{
	/**
	 * @param string $return_url
	 * @param string $search
	 * @return string
	 * @throws \Exception
	 */
	public static function getHTML($return_url, $search = '')
	{
		$o = '';

		$saved_searches = DBA::select('search', ['id', 'term'], ['uid' => local_user()]);
		if (DBA::isResult($saved_searches)) {
			$saved = [];
			foreach ($saved_searches as $saved_search) {
				$saved[] = [
					'id'          => $saved_search['id'],
					'term'        => $saved_search['term'],
					'encodedterm' => urlencode($saved_search['term']),
					'delete'      => L10n::t('Remove term'),
					'selected'    => $search == $saved_search['term'],
				];
			}

			$tpl = Renderer::getMarkupTemplate('widget/saved_searches.tpl');

			$o = Renderer::replaceMacros($tpl, [
				'$title'      => L10n::t('Saved Searches'),
				'$add'        => '',
				'$searchbox'  => '',
				'$saved'      => $saved,
				'$return_url' => urlencode($return_url),
			]);
		}

		return $o;
	}
}
