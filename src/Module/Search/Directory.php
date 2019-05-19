<?php

namespace Friendica\Module\Search;

use Friendica\BaseModule;
use Friendica\Content\ContactSelector;
use Friendica\Content\Pager;
use Friendica\Content\Widget;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Module\BaseSearchModule;
use Friendica\Util\Proxy as ProxyUtils;
use Friendica\Util\Strings;
use Friendica\Model;

/**
 * Multi search module, which is needed for further search operations
 */
class Directory extends BaseSearchModule
{
	public static function content()
	{
		if (!local_user()) {
			notice(L10n::t('Permission denied.'));
			return Login::form();
		}

		$a = self::getApp();

		if (empty($a->page['aside'])) {
			$a->page['aside'] = '';
		}

		$a->page['aside'] .= Widget::findPeople();
		$a->page['aside'] .= Widget::follow();

		return self::performSearch();
	}

	public static function performSearch($prefix = '')
	{
		$a      = self::getApp();
		$config = $a->getConfig();

		$community = false;

		$localSearch = $config->get('system', 'poco_local_search');

		$search = $prefix . Strings::escapeTags(trim(defaults($_REQUEST, 'search', '')));

		if (!$search) {
			return '';
		}

		$header = '';

		if (strpos($search, '@') === 0) {
			$search  = substr($search, 1);
			$header  = L10n::t('People Search - %s', $search);
			$results = Model\Search::searchUser($search);
		}

		if (strpos($search, '!') === 0) {
			$search    = substr($search, 1);
			$community = true;
			$header    = L10n::t('Forum Search - %s', $search);
		}

		$pager = new Pager($a->query_string);

		if ($localSearch && empty($results)) {
			$pager->setItemsPerPage(80);
			$results = Model\Search::searchLocal($search, $pager->getStart(), $pager->getItemsPerPage(), $community);

		} elseif (strlen($config->get('system', 'directory')) && empty($results)) {
			$results = Model\Search::searchDirectory($search, $pager->getPage());
			$pager->setItemsPerPage($results->getItemsPage());
		}

		return self::printResult($results, $pager, $header);
	}
}
