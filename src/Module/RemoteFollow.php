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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Search;
use Friendica\Core\System;
use Friendica\Model\Profile;
use Friendica\Network\Probe;

/**
 * Remotely follow the account on this system by the provided account
 */
class RemoteFollow extends BaseModule
{
	public static function init(array $parameters = [])
	{
		Profile::load(DI::app(), $parameters['profile']);
	}

	public static function post(array $parameters = [])
	{
		$a = DI::app();

		if (!empty($_POST['cancel']) || empty($_POST['dfrn_url'])) {
			DI::baseUrl()->redirect();
		}
	
		if (empty($a->profile['uid'])) {
			notice(DI::l10n()->t('Profile unavailable.'));
			return;
		}
		
		$url = Probe::cleanURI($_POST['dfrn_url']);
		if (!strlen($url)) {
			notice(DI::l10n()->t("Invalid locator"));
			return;
		}

		// Detect the network, make sure the provided URL is valid
		$data = Probe::uri($url);
		if ($data['network'] == Protocol::PHANTOM) {
			notice(DI::l10n()->t("The provided profile link doesn't seem to be valid"));
			return;
		}

		// Fetch link for the "remote follow" functionality of the given profile
		$follow_link_template = Probe::getRemoteFollowLink($url);

		if (empty($follow_link_template)) {
			notice(DI::l10n()->t("Remote subscription can't be done for your network. Please subscribe directly on your system."));
			return;
		}

		Logger::notice('Remote request', ['url' => $url, 'follow' => $a->profile['url'], 'remote' => $follow_link_template]);
		
		// Substitute our user's feed URL into $follow_link_template
		// Send the subscriber home to subscribe
		// Diaspora needs the uri in the format user@domain.tld
		if ($data['network'] == Protocol::DIASPORA) {
			$uri = urlencode($a->profile['addr']);
		} else {
			$uri = urlencode($a->profile['url']);
		}
	
		$follow_link = str_replace('{uri}', $uri, $follow_link_template);
		System::externalRedirect($follow_link);
	}

	public static function content(array $parameters = [])
	{
		$a = DI::app();

		if (empty($a->profile)) {
			return '';
		}
	
		$target_addr = $a->profile['addr'];
		$target_url = $a->profile['url'];

		$tpl = Renderer::getMarkupTemplate('auto_request.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$header'        => DI::l10n()->t('Friend/Connection Request'),
			'$page_desc'     => DI::l10n()->t('Enter your Webfinger address (user@domain.tld) or profile URL here. If this isn\'t supported by your system, you have to subscribe to <strong>%s</strong> or <strong>%s</strong> directly on your system.', $target_addr, $target_url),
			'$invite_desc'   => DI::l10n()->t('If you are not yet a member of the free social web, <a href="%s">follow this link to find a public Friendica node and join us today</a>.', Search::getGlobalDirectory() . '/servers'),
			'$your_address'  => DI::l10n()->t('Your Webfinger address or profile URL:'),
			'$pls_answer'    => DI::l10n()->t('Please answer the following:'),
			'$submit'        => DI::l10n()->t('Submit Request'),
			'$cancel'        => DI::l10n()->t('Cancel'),

			'$request'       => 'remote_follow/' . $parameters['profile'],
			'$name'          => $a->profile['name'],
			'$myaddr'        => Profile::getMyURL(),
		]);
		return $o;
	}
}
