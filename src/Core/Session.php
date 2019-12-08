<?php

/**
 * @file src/Core/Session.php
 */
namespace Friendica\Core;

use Friendica\App;
use Friendica\BaseObject;
use Friendica\Core\Session\CacheSessionHandler;
use Friendica\Core\Session\DatabaseSessionHandler;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\User;
use Friendica\Util\Strings;

/**
 * High-level Session service class
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class Session
{
	public static $exists = false;
	public static $expire = 180000;

	public static function init()
	{
		ini_set('session.gc_probability', 50);
		ini_set('session.use_only_cookies', 1);
		ini_set('session.cookie_httponly', 1);

		if (Config::get('system', 'ssl_policy') == App\BaseURL::SSL_POLICY_FULL) {
			ini_set('session.cookie_secure', 1);
		}

		$session_handler = Config::get('system', 'session_handler', 'database');
		if ($session_handler != 'native') {
			if ($session_handler == 'cache' && Config::get('system', 'cache_driver', 'database') != 'database') {
				$SessionHandler = new CacheSessionHandler();
			} else {
				$SessionHandler = new DatabaseSessionHandler();
			}

			session_set_save_handler($SessionHandler);
		}
	}

	public static function exists($name)
	{
		return isset($_SESSION[$name]);
	}

	/**
	 * Retrieves a key from the session super global or the defaults if the key is missing or the value is falsy.
	 *
	 * Handle the case where session_start() hasn't been called and the super global isn't available.
	 *
	 * @param string $name
	 * @param mixed $defaults
	 * @return mixed
	 */
	public static function get($name, $defaults = null)
	{
		return $_SESSION[$name] ?? $defaults;
	}

	/**
	 * Sets a single session variable.
	 * Overrides value of existing key.
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public static function set($name, $value)
	{
		$_SESSION[$name] = $value;
	}

	/**
	 * Sets multiple session variables.
	 * Overrides values for existing keys.
	 *
	 * @param array $values
	 */
	public static function setMultiple(array $values)
	{
		$_SESSION = $values + $_SESSION;
	}

	/**
	 * Removes a session variable.
	 * Ignores missing keys.
	 *
	 * @param $name
	 */
	public static function remove($name)
	{
		unset($_SESSION[$name]);
	}

	/**
	 * Clears the current session array
	 */
	public static function clear()
	{
		session_unset();
		session_start();
		$_SESSION = [];
	}

	/**
	 * Returns contact ID for given user ID
	 *
	 * @param integer $uid User ID
	 * @return integer Contact ID of visitor for given user ID
	 */
	public static function getRemoteContactID($uid)
	{
		if (empty($_SESSION['remote'][$uid])) {
			return false;
		}

		return $_SESSION['remote'][$uid];
	}

	/**
	 * Returns User ID for given contact ID of the visitor
	 *
	 * @param integer $cid Contact ID
	 * @return integer User ID for given contact ID of the visitor
	 */
	public static function getUserIDForVisitorContactID($cid)
	{
		if (empty($_SESSION['remote'])) {
			return false;
		}

		return array_search($cid, $_SESSION['remote']);
	}

	/**
	 * Set the session variable that contains the contact IDs for the visitor's contact URL
	 *
	 * @param string $url Contact URL
	 */
	public static function setVisitorsContacts()
	{
		$_SESSION['remote'] = [];

		$remote_contacts = DBA::select('contact', ['id', 'uid'], ['nurl' => Strings::normaliseLink($_SESSION['my_url']), 'rel' => [Contact::FOLLOWER, Contact::FRIEND], 'self' => false]);
		while ($contact = DBA::fetch($remote_contacts)) {
			if (($contact['uid'] == 0) || Contact::isBlockedByUser($contact['id'], $contact['uid'])) {
				continue;
			}

			$_SESSION['remote'][$contact['uid']] = $contact['id'];
		}
		DBA::close($remote_contacts);
	}

	/**
	 * Returns if the current visitor is authenticated
	 *
	 * @return boolean "true" when visitor is either a local or remote user
	 */
	public static function isAuthenticated()
	{
		if (empty($_SESSION['authenticated'])) {
			return false;
		}

		return $_SESSION['authenticated'];
	}

	/**
	 * @brief Kills the "Friendica" cookie and all session data
	 */
	public static function delete()
	{
		/** @var User\Cookie $cookie */
		$cookie = BaseObject::getClass(User\Cookie::class);
		$cookie->clear();
		$_SESSION = [];
		session_unset();
		session_destroy();
	}
}
