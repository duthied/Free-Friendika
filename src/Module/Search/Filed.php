<?php

namespace Friendica\Module\Search;

use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Text\HTML;
use Friendica\Content\Widget;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Post\Category;
use Friendica\Module\BaseSearch;
use Friendica\Module\Security\Login;

class Filed extends BaseSearch
{
	public static function content(array $parameters = [])
	{
		if (!local_user()) {
			return Login::form();
		}

		DI::page()['aside'] .= Widget::fileAs(DI::args()->getCommand(), $_GET['file'] ?? '');

		if (DI::pConfig()->get(local_user(), 'system', 'infinite_scroll') && ($_GET['mode'] ?? '') != 'minimal') {
			$tpl = Renderer::getMarkupTemplate('infinite_scroll_head.tpl');
			$o = Renderer::replaceMacros($tpl, ['$reload_uri' => DI::args()->getQueryString()]);
		} else {
			$o = '';
		}

		$file = $_GET['file'] ?? '';

		// Rawmode is used for fetching new content at the end of the page
		if (!(isset($_GET['mode']) && ($_GET['mode'] == 'raw'))) {
			Nav::setSelected(DI::args()->get(0));
		}

		if (DI::mode()->isMobile()) {
			$itemspage_network = DI::pConfig()->get(local_user(), 'system', 'itemspage_mobile_network',
				DI::config()->get('system', 'itemspage_network_mobile'));
		} else {
			$itemspage_network = DI::pConfig()->get(local_user(), 'system', 'itemspage_network',
				DI::config()->get('system', 'itemspage_network'));
		}

		$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), $itemspage_network);

		$term_condition = ['type' => Category::FILE, 'uid' => local_user()];
		if ($file) {
			$term_condition['name'] = $file;
		}
		$term_params = ['order' => ['uri-id' => true], 'limit' => [$pager->getStart(), $pager->getItemsPerPage()]];
		$result = DBA::select('category-view', ['uri-id'], $term_condition, $term_params);

		$total = DBA::count('category-view', $term_condition);

		$posts = [];
		while ($term = DBA::fetch($result)) {
			$posts[] = $term['uri-id'];
		}
		DBA::close($result);

		if (count($posts) == 0) {
			return '';
		}
		$item_condition = ['uid' => local_user(), 'uri-id' => $posts];
		$item_params = ['order' => ['uri-id' => true]];

		$items = Post::toArray(Post::selectForUser(local_user(), Item::DISPLAY_FIELDLIST, $item_condition, $item_params));

		$o .= conversation(DI::app(), $items, 'filed', false, false, '', local_user());

		if (DI::pConfig()->get(local_user(), 'system', 'infinite_scroll')) {
			$o .= HTML::scrollLoader();
		} else {
			$o .= $pager->renderFull($total);
		}

		return $o;
	}
}
