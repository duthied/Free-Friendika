<?php


namespace Friendica\Test\src\Core\Cache;


use Friendica\Core\Cache\CacheDriverFactory;

class MemcachedCacheDriverTest extends MemoryCacheTest
{
	/**
	 * @var \Friendica\Core\Cache\IMemoryCacheDriver
	 */
	private $cache;

	protected function getInstance()
	{
		if (class_exists('Memcached')) {
			try {
				$this->cache = CacheDriverFactory::create('memcached');
			} catch (\Exception $exception) {
				throw new \Exception("Memcached - TestCase failed: " . $exception->getMessage(), $exception->getCode(), $exception);
			}
			return $this->cache;
		} else {
			$this->markTestSkipped('Memcached driver isn\'t available');
			return null;
		}
	}

	public function tearDown()
	{
		if (class_exists('Memcached')) {
			$this->cache->clear(false);
		}
		parent::tearDown();
	}
}
