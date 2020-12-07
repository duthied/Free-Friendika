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

namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Cache\MemcacheCache;
use Friendica\Core\Config\IConfig;
use Friendica\Core\Lock\CacheLock;

/**
 * @requires extension Memcache
 * @group MEMCACHE
 */
class MemcacheCacheLockTest extends LockTest
{
	protected function getInstance()
	{
		$configMock = \Mockery::mock(IConfig::class);

		$host = $_SERVER['MEMCACHE_HOST'] ?? 'localhost';

		$configMock
			->shouldReceive('get')
			->with('system', 'memcache_host')
			->andReturn($host);
		$configMock
			->shouldReceive('get')
			->with('system', 'memcache_port')
			->andReturn(11211);

		$lock = null;

		try {
			$cache = new MemcacheCache($host, $configMock);
			$lock = new CacheLock($cache);
		} catch (\Exception $e) {
			$this->markTestSkipped('Memcache is not available');
		}

		return $lock;
	}

	/**
	 * @small
	 */
	public function testGetLocks()
	{
		$this->markTestIncomplete('Race condition because of too fast getAllKeys() which uses a workaround');
	}

	/**
	 * @small
	 */
	public function testGetLocksWithPrefix()
	{
		$this->markTestIncomplete('Race condition because of too fast getAllKeys() which uses a workaround');
	}
}
