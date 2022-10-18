<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Util\Strings;

/**
 * High-level Session service class
 */
class Session
{
	public static $exists = false;
	public static $expire = 180000;

	public static function set($name, $value)
	{
		DI::session()->set($name, $value);
	}

	public static function setMultiple(array $values)
	{
		DI::session()->setMultiple($values);
	}

	public static function remove($name)
	{
		DI::session()->remove($name);
	}

	public static function clear()
	{
		DI::session()->clear();
	}

	/**
	 * Returns the user id of locally logged in user or false.
	 *
	 * @return int|bool user id or false
	 */
	public static function getLocalUser()
	{
		$session = DI::session();

		if (!empty($session->get('authenticated')) && !empty($session->get('uid'))) {
			return intval($session->get('uid'));
		}

		return false;
	}

	/**
	 * Returns the public contact id of logged in user or false.
	 *
	 * @return int|bool public contact id or false
	 */
	public static function getPublicContact()
	{
		static $public_contact_id = false;

		$session = DI::session();

		if (!$public_contact_id && !empty($session->get('authenticated'))) {
			if (!empty($session->get('my_address'))) {
				// Local user
				$public_contact_id = intval(Contact::getIdForURL($session->get('my_address'), 0, false));
			} elseif (!empty($session->get('visitor_home'))) {
				// Remote user
				$public_contact_id = intval(Contact::getIdForURL($session->get('visitor_home'), 0, false));
			}
		} elseif (empty($session->get('authenticated'))) {
			$public_contact_id = false;
		}

		return $public_contact_id;
	}

	/**
	 * Returns public contact id of authenticated site visitor or false
	 *
	 * @return int|bool visitor_id or false
	 */
	public static function getRemoteUser()
	{
		$session = DI::session();

		if (empty($session->get('authenticated'))) {
			return false;
		}

		if (!empty($session->get('visitor_id'))) {
			return intval($session->get('visitor_id'));
		}

		return false;
	}

	/**
	 * Return the user contact ID of a visitor for the given user ID they are visiting
	 *
	 * @param integer $uid User ID
	 * @return integer
	 */
	public static function getRemoteContactID($uid)
	{
		$session = DI::session();

		if (!empty($session->get('remote')[$uid])) {
			$remote = $session->get('remote')[$uid];
		} else {
			$remote = 0;
		}

		$local_user = !empty($session->get('authenticated')) ? $session->get('uid') : 0;

		if (empty($remote) && ($local_user != $uid) && !empty($my_address = $session->get('my_address'))) {
			$remote = Contact::getIdForURL($my_address, $uid, false);
		}

		return $remote;
	}

	/**
	 * Returns User ID for given contact ID of the visitor
	 *
	 * @param integer $cid Contact ID
	 * @return integer User ID for given contact ID of the visitor
	 */
	public static function getUserIDForVisitorContactID($cid)
	{
		$session = DI::session();

		if (empty($session->get('remote'))) {
			return false;
		}

		return array_search($cid, $session->get('remote'));
	}

	/**
	 * Set the session variable that contains the contact IDs for the visitor's contact URL
	 *
	 * @param string $url Contact URL
	 */
	public static function setVisitorsContacts()
	{
		$session = DI::session();

		$session->set('remote', []);
		$remote = [];

		$remote_contacts = DBA::select('contact', ['id', 'uid'], ['nurl' => Strings::normaliseLink($session->get('my_url')), 'rel' => [Contact::FOLLOWER, Contact::FRIEND], 'self' => false]);
		while ($contact = DBA::fetch($remote_contacts)) {
			if (($contact['uid'] == 0) || Contact\User::isBlocked($contact['id'], $contact['uid'])) {
				continue;
			}
			$remote[$contact['uid']] = $contact['id'];
		}
		DBA::close($remote_contacts);
		$session->set('remote', $remote);
	}

	/**
	 * Returns if the current visitor is authenticated
	 *
	 * @return boolean "true" when visitor is either a local or remote user
	 */
	public static function isAuthenticated()
	{
		$session = DI::session();

		return $session->get('authenticated', false);
	}
}
