<?php

namespace Friendica\Core\Session;

use Friendica\BaseObject;
use Friendica\Core\Session;
use SessionHandlerInterface;
use Memcache;

require_once 'boot.php';
require_once 'include/text.php';

/**
 * SessionHandler using Memcache
 *
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class MemcacheSessionHandler extends BaseObject implements SessionHandlerInterface
{
	/**
	 * @var Memcache
	 */
	private $memcache = null;

	/**
	 *
	 * @param Memcache $memcache
	 */
	public function __construct(Memcache $memcache)
	{
		$this->memcache = $memcache;
	}

	public function open($save_path, $session_name)
	{
		return true;
	}

	public function read($session_id)
	{
		if (!x($session_id)) {
			return '';
		}

		$data = $this->memcache->get(self::getApp()->get_hostname() . ":session:" . $session_id);
		if (!is_bool($data)) {
			Session::$exists = true;
			return $data;
		}
		logger("no data for session $session_id", LOGGER_TRACE);
		return '';
	}

	/**
	 * @brief Standard PHP session write callback
	 *
	 * This callback updates the stored session data and/or the expiration depending
	 * on the case. Uses the Session::expire for existing session, 5 minutes
	 * for newly created session.
	 *
	 * @param  string $session_id   Session ID with format: [a-z0-9]{26}
	 * @param  string $session_data Serialized session data
	 * @return boolean Returns false if parameters are missing, true otherwise
	 */
	public function write($session_id, $session_data)
	{
		if (!$session_id) {
			return false;
		}

		if (!$session_data) {
			return true;
		}

		$expire = time() + Session::$expire;

		$this->memcache->set(
			self::getApp()->get_hostname() . ":session:" . $session_id,
			$session_data,
			MEMCACHE_COMPRESSED,
			$expire
		);

		return true;
	}

	public function close()
	{
		return true;
	}

	public function destroy($id)
	{
		$this->memcache->delete(self::getApp()->get_hostname() . ":session:" . $id);
		return true;
	}

	public function gc($maxlifetime)
	{
		return true;
	}
}
