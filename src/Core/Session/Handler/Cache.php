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

namespace Friendica\Core\Session\Handler;

use Friendica\Core\Cache\ICache;
use Friendica\Core\Session;
use Psr\Log\LoggerInterface;
use SessionHandlerInterface;

/**
 * SessionHandler using Friendica Cache
 */
class Cache implements SessionHandlerInterface
{
	/** @var ICache */
	private $cache;
	/** @var LoggerInterface */
	private $logger;
	/** @var array The $_SERVER array */
	private $server;

	public function __construct(ICache $cache, LoggerInterface $logger, array $server)
	{
		$this->cache  = $cache;
		$this->logger = $logger;
		$this->server = $server;
	}

	public function open($save_path, $session_name)
	{
		return true;
	}

	public function read($session_id)
	{
		if (empty($session_id)) {
			return '';
		}

		$data = $this->cache->get('session:' . $session_id);
		if (!empty($data)) {
			Session::$exists = true;
			return $data;
		}

		$this->logger->notice('no data for session', ['session_id' => $session_id, 'uri' => $this->server['REQUEST_URI'] ?? '']);

		return '';
	}

	/**
	 * Standard PHP session write callback
	 *
	 * This callback updates the stored session data and/or the expiration depending
	 * on the case. Uses the Session::expire for existing session, 5 minutes
	 * for newly created session.
	 *
	 * @param string $session_id   Session ID with format: [a-z0-9]{26}
	 * @param string $session_data Serialized session data
	 *
	 * @return boolean Returns false if parameters are missing, true otherwise
	 * @throws \Exception
	 */
	public function write($session_id, $session_data)
	{
		if (!$session_id) {
			return false;
		}

		if (!$session_data) {
			return true;
		}

		return $this->cache->set('session:' . $session_id, $session_data, Session::$expire);
	}

	public function close()
	{
		return true;
	}

	public function destroy($id)
	{
		return $this->cache->delete('session:' . $id);
	}

	public function gc($maxlifetime)
	{
		return true;
	}
}
