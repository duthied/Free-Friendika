<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Core;

use Friendica\DI;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Activity;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\OStatus;
use Friendica\Protocol\Salmon;

/**
 * Manage compatibility with federated networks
 */
class Protocol
{
	// Native support
	const ACTIVITYPUB = 'apub';    // ActivityPub (Pleroma, Mastodon, Osada, ...)
	const DFRN        = 'dfrn';    // Friendica, Mistpark, other DFRN implementations
	const DIASPORA    = 'dspr';    // Diaspora, Hubzilla, Socialhome, Ganggo
	const FEED        = 'feed';    // RSS/Atom feeds with no known "post/notify" protocol
	const MAIL        = 'mail';    // IMAP/POP
	const OSTATUS     = 'stat';    // GNU Social and other OStatus implementations

	const NATIVE_SUPPORT = [self::DFRN, self::DIASPORA, self::OSTATUS, self::FEED, self::MAIL, self::ACTIVITYPUB];

	const FEDERATED = [self::DFRN, self::DIASPORA, self::OSTATUS, self::ACTIVITYPUB];

	const SUPPORT_PRIVATE = [self::DFRN, self::DIASPORA, self::MAIL, self::ACTIVITYPUB, self::PUMPIO];

	// Supported through a connector
	const DIASPORA2 = 'dspc';    // Diaspora connector
	const LINKEDIN  = 'lnkd';    // LinkedIn
	const PUMPIO    = 'pump';    // pump.io
	const STATUSNET = 'stac';    // Statusnet connector
	const TWITTER   = 'twit';    // Twitter
	const DISCOURSE = 'dscs';    // Discourse

	// Dead protocols
	const APPNET    = 'apdn';    // app.net - Dead protocol
	const FACEBOOK  = 'face';    // Facebook API - Not working anymore, API is closed
	const GPLUS     = 'goog';    // Google+ - Dead in 2019

	// Currently unsupported
	const ICALENDAR = 'ical';    // iCalendar
	const MYSPACE   = 'mysp';    // MySpace
	const NEWS      = 'nntp';    // Network News Transfer Protocol
	const PNUT      = 'pnut';    // pnut.io
	const XMPP      = 'xmpp';    // XMPP
	const ZOT       = 'zot!';    // Zot!

	const PHANTOM   = 'unkn';    // Place holder

	/**
	 * Returns whether the provided protocol supports following
	 *
	 * @param $protocol
	 * @return bool
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function supportsFollow($protocol): bool
	{
		if (in_array($protocol, self::NATIVE_SUPPORT)) {
			return true;
		}

		$hook_data = [
			'protocol' => $protocol,
			'result' => null
		];
		Hook::callAll('support_follow', $hook_data);

		return $hook_data['result'] === true;
	}

	/**
	 * Returns whether the provided protocol supports revoking inbound follows
	 *
	 * @param $protocol
	 * @return bool
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function supportsRevokeFollow($protocol): bool
	{
		if (in_array($protocol, self::NATIVE_SUPPORT)) {
			return true;
		}

		$hook_data = [
			'protocol' => $protocol,
			'result' => null
		];
		Hook::callAll('support_revoke_follow', $hook_data);

		return $hook_data['result'] === true;
	}

	/**
	 * Returns the address string for the provided profile URL
	 *
	 * @param string $profile_url
	 * @return string
	 * @throws \Exception
	 */
	public static function getAddrFromProfileUrl($profile_url)
	{
		$network = self::matchByProfileUrl($profile_url, $matches);

		if ($network === self::PHANTOM) {
			return "";
		}

		$addr = $matches[2] . '@' . $matches[1];

		return $addr;
	}

	/**
	 * Guesses the network from a profile URL
	 *
	 * @param string $profile_url
	 * @param array  $matches preg_match return array: [0] => Full match [1] => hostname [2] => username
	 * @return string
	 */
	public static function matchByProfileUrl($profile_url, &$matches = [])
	{
		if (preg_match('=https?://(twitter\.com)/(.*)=ism', $profile_url, $matches)) {
			return self::TWITTER;
		}

		if (preg_match('=https?://(alpha\.app\.net)/(.*)=ism', $profile_url, $matches)) {
			return self::APPNET;
		}

		if (preg_match('=https?://(plus\.google\.com)/(.*)=ism', $profile_url, $matches)) {
			return self::GPLUS;
		}

		if (preg_match('=https?://(.*)/profile/(.*)=ism', $profile_url, $matches)) {
			return self::DFRN;
		}

		if (preg_match('=https?://(.*)/u/(.*)=ism', $profile_url, $matches)) {
			return self::DIASPORA;
		}

		if (preg_match('=https?://(.*)/channel/(.*)=ism', $profile_url, $matches)) {
			// RedMatrix/Hubzilla is identified as Diaspora - friendica can't connect directly to it
			return self::DIASPORA;
		}

		if (preg_match('=https?://(.*)/user/(.*)=ism', $profile_url, $matches)) {
			$statusnet_host = $matches[1];
			$statusnet_user = $matches[2];
			$UserData = DI::httpClient()->fetch('http://' . $statusnet_host . '/api/users/show.json?user_id=' . $statusnet_user);
			$user = json_decode($UserData);
			if ($user) {
				$matches[2] = $user->screen_name;
				return self::STATUSNET;
			}
		}

		// Mastodon, Pleroma
		if (preg_match('=https?://(.+?)/users/(.+)=ism', $profile_url, $matches)
			|| preg_match('=https?://(.+?)/@(.+)=ism', $profile_url, $matches)
		) {
			return self::ACTIVITYPUB;
		}

		// pumpio (http://host.name/user)
		if (preg_match('=https?://([\.\w]+)/([\.\w]+)$=ism', $profile_url, $matches)) {
			return self::PUMPIO;
		}

		return self::PHANTOM;
	}

	/**
	 * Returns a formatted mention from a profile URL and a display name
	 *
	 * @param string $profile_url
	 * @param string $display_name
	 * @return string
	 * @throws \Exception
	 */
	public static function formatMention($profile_url, $display_name)
	{
		return $display_name . ' (' . self::getAddrFromProfileUrl($profile_url) . ')';
	}

	/**
	 * Sends an unfriend message. Does not remove the contact
	 *
	 * @param array   $user    User unfriending
	 * @param array   $contact Contact unfriended
	 * @return bool|null true if successful, false if not, null if no remote action was performed
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function terminateFriendship(array $user, array $contact): ?bool
	{
		if (empty($contact['network'])) {
			throw new \InvalidArgumentException('Missing network key in contact array');
		}

		$protocol = $contact['network'];
		if (($protocol == Protocol::DFRN) && !empty($contact['protocol'])) {
			$protocol = $contact['protocol'];
		}

		if (in_array($protocol, [Protocol::OSTATUS, Protocol::DFRN])) {
			// create an unfollow slap
			$item = [];
			$item['verb'] = Activity::O_UNFOLLOW;
			$item['gravity'] = GRAVITY_ACTIVITY;
			$item['follow'] = $contact['url'];
			$item['body'] = '';
			$item['title'] = '';
			$item['guid'] = '';
			$item['uri-id'] = 0;
			$slap = OStatus::salmon($item, $user);

			if (empty($contact['notify'])) {
				throw new \InvalidArgumentException('Missing expected "notify" key in OStatus/DFRN contact');
			}

			return Salmon::slapper($user, $contact['notify'], $slap) === 0;
		} elseif ($protocol == Protocol::DIASPORA) {
			return Diaspora::sendUnshare($user, $contact) > 0;
		} elseif ($protocol == Protocol::ACTIVITYPUB) {
			return ActivityPub\Transmitter::sendContactUndo($contact['url'], $contact['id'], $user['uid']);
		}

		// Catch-all hook for connector addons
		$hook_data = [
			'contact' => $contact,
			'result' => null
		];
		Hook::callAll('unfollow', $hook_data);

		return $hook_data['result'];
	}

	/**
	 * Revoke an incoming follow from the provided contact
	 *
	 * @param array $contact Private contact (uid != 0) array
	 * @return bool|null true if successful, false if not, null if no action was performed
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function revokeFollow(array $contact): ?bool
	{
		if (empty($contact['network'])) {
			throw new \InvalidArgumentException('Missing network key in contact array');
		}

		$protocol = $contact['network'];
		if ($protocol == Protocol::DFRN && !empty($contact['protocol'])) {
			$protocol = $contact['protocol'];
		}

		if ($protocol == Protocol::ACTIVITYPUB) {
			return ActivityPub\Transmitter::sendContactReject($contact['url'], $contact['hub-verify'], $contact['uid']);
		}

		// Catch-all hook for connector addons
		$hook_data = [
			'contact' => $contact,
			'result' => null,
		];
		Hook::callAll('revoke_follow', $hook_data);

		return $hook_data['result'];
	}

	/**
	 * Send a block message to a remote server. Only useful for connector addons.
	 *
	 * @param array $contact Public contact record to block
	 * @param int   $uid     User issuing the block
	 * @return bool|null true if successful, false if not, null if no action was performed
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function block(array $contact, int $uid): ?bool
	{
		// Catch-all hook for connector addons
		$hook_data = [
			'contact' => $contact,
			'uid' => $uid,
			'result' => null,
		];
		Hook::callAll('block', $hook_data);

		return $hook_data['result'];
	}

	/**
	 * Send an unblock message to a remote server. Only useful for connector addons.
	 *
	 * @param array $contact Public contact record to unblock
	 * @param int   $uid     User revoking the block
	 * @return bool|null true if successful, false if not, null if no action was performed
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function unblock(array $contact, int $uid): ?bool
	{
		// Catch-all hook for connector addons
		$hook_data = [
			'contact' => $contact,
			'uid' => $uid,
			'result' => null,
		];
		Hook::callAll('unblock', $hook_data);

		return $hook_data['result'];
	}
}
