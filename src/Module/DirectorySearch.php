<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Content\ContactSelector;
use Friendica\Content\Pager;
use Friendica\Content\Widget;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Util\Proxy as ProxyUtils;
use Friendica\Util\Strings;
use Friendica\Model;

/**
 * Multi search module, which is needed for further search operations
 */
class DirectorySearch extends BaseModule
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

		if (empty($results) || empty($results->getResults())) {
			info(L10n::t('No matches') . EOL);
			return '';
		}
		$id = 0;

		$entries = [];
		foreach ($results->getResults() as $result) {

			$alt_text    = '';
			$location    = '';
			$about       = '';
			$accountType = '';
			$photo_menu  = [];

			// If We already know this contact then don't show the "connect" button
			if ($result->getCid() > 0 || $result->getPcid() > 0) {
				$connLink = "";
				$connTxt = "";
				$contact = Model\Contact::getById(
					($result->getCid() > 0) ? $result->getCid() : $result->getPcid()
				);

				if (!empty($contact)) {
					$photo_menu  = Model\Contact::photoMenu($contact);
					$details     = Contact::getContactTemplateVars($contact);
					$alt_text    = $details['alt_text'];
					$location    = $contact['location'];
					$about       = $contact['about'];
					$accountType = Model\Contact::getAccountType($contact);
				} else {
					$photo_menu = [];
				}
			} else {
				$connLink = $a->getBaseURL() . '/follow/?url=' . $result->getUrl();
				$connTxt = L10n::t('Connect');

				$photo_menu['profile'] = [L10n::t("View Profile"), Model\Contact::magicLink($result->getUrl())];
				$photo_menu['follow']  = [L10n::t("Connect/Follow"), $connLink];
			}

			$photo = str_replace("http:///photo/", get_server() . "/photo/", $result->getPhoto());

			$entry     = [
				'alt_text'     => $alt_text,
				'url'          => Model\Contact::magicLink($result->getUrl()),
				'itemurl'      => $result->getItem(),
				'name'         => $result->getName(),
				'thumb'        => ProxyUtils::proxifyUrl($photo, false, ProxyUtils::SIZE_THUMB),
				'img_hover'    => $result->getTags(),
				'conntxt'      => $connTxt,
				'connlnk'      => $connLink,
				'photo_menu'   => $photo_menu,
				'details'      => $location,
				'tags'         => $result->getTags(),
				'about'        => $about,
				'account_type' => $accountType,
				'network'      => ContactSelector::networkToName($result->getNetwork(), $result->getUrl()),
				'id'           => ++$id,
			];
			$entries[] = $entry;
		}

		$tpl = Renderer::getMarkupTemplate('viewcontact_template.tpl');
		return Renderer::replaceMacros($tpl, [
			'title'     => $header,
			'$contacts' => $entries,
			'$paginate' => $pager->renderFull($results->getTotal()),
		]);
	}
}
