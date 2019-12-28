<?php

namespace Friendica\Module;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Page;
use Friendica\BaseModule;
use Friendica\Core\Config\Configuration;
use Friendica\Core\L10n\L10n;
use Friendica\Core\Session;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Network\HTTPException\NotFoundException;

/**
 * Loads a profile for the HoverCard view
 */
class HoverCard extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		$a = self::getApp();

		if ((local_user()) && ($parameters['action'] ?? '') === 'view') {
			// A logged in user views a profile of a user
			$nickname = $a->user['nickname'];
			$profile  = $parameters['profile'];
		} elseif (empty($parameters['action'])) {
			// Show the profile hovercard
			$nickname = $parameters['profile'];
			$profile  = 0;
		} else {
			/** @var L10n $l10n */
			$l10n = self::getClass(L10n::class);
			throw new NotFoundException($l10n->t('No profile'));
		}

		Profile::load($a, $nickname, $profile);

		/** @var Page $page */
		$page = self::getClass(Page::class);

		if (!empty($a->profile['page-flags']) && ($a->profile['page-flags'] == User::PAGE_FLAGS_COMMUNITY)) {
			$page['htmlhead'] .= '<meta name="friendica.community" content="true" />';
		}
		if (!empty($a->profile['openidserver'])) {
			$page['htmlhead'] .= '<link rel="openid.server" href="' . $a->profile['openidserver'] . '" />' . "\r\n";
		}
		if (!empty($a->profile['openid'])) {
			$delegate         = ((strstr($a->profile['openid'], '://')) ? $a->profile['openid'] : 'http://' . $a->profile['openid']);
			$page['htmlhead'] .= '<link rel="openid.delegate" href="' . $delegate . '" />' . "\r\n";
		}

		/** @var Configuration $config */
		$config = self::getClass(Configuration::class);
		// check if blocked
		if ($config->get('system', 'block_public') && !Session::isAuthenticated()) {
			$keywords = $a->profile['pub_keywords'] ?? '';
			$keywords = str_replace([',', ' ', ',,'], [' ', ',', ','], $keywords);
			if (strlen($keywords)) {
				$page['htmlhead'] .= '<meta name="keywords" content="' . $keywords . '" />' . "\r\n";
			}
		}

		/** @var BaseURL $baseUrl */
		$baseUrl = self::getClass(BaseURL::class);

		$uri = urlencode('acct:' . $a->profile['nickname'] . '@' . $baseUrl->getHostname() . ($baseUrl->getUrlPath() ? '/' . $baseUrl->getUrlPath() : ''));

		$page['htmlhead'] .= '<meta name="dfrn-global-visibility" content="' . (($a->profile['net-publish']) ? 'true' : 'false') . '" />' . "\r\n";
		$page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . $baseUrl->get() . '/dfrn_poll/' . $nickname . '" />' . "\r\n";
		$page['htmlhead'] .= '<link rel="lrdd" type="application/xrd+xml" href="' . $baseUrl->get() . '/xrd/?uri=' . $uri . '" />' . "\r\n";
		header('Link: <' . $baseUrl->get() . '/xrd/?uri=' . $uri . '>; rel="lrdd"; type="application/xrd+xml"', false);

		foreach (['request', 'confirm', 'notify', 'poll'] as $dfrn) {
			$page['htmlhead'] .= "<link rel=\"dfrn-{$dfrn}\" href=\"" . $baseUrl->get() . "/dfrn_{$dfrn}/{$nickname}\" />\r\n";
		}
	}
}
