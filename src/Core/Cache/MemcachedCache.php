<?php

namespace Friendica\Core\Cache;

use Exception;
use Friendica\Core\Config\Configuration;
use Memcached;
use Psr\Log\LoggerInterface;

/**
 * Memcached Cache
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class MemcachedCache extends Cache implements IMemoryCache
{
	use TraitCompareSet;
	use TraitCompareDelete;

	/**
	 * @var \Memcached
	 */
	private $memcached;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var string First server address
	 */

	private $firstServer;

	/**
	 * @var int First server port
	 */
	private $firstPort;

	/**
	 * Due to limitations of the INI format, the expected configuration for Memcached servers is the following:
	 * array {
	 *   0 => "hostname, port(, weight)",
	 *   1 => ...
	 * }
	 *
	 * @param array $memcached_hosts
	 *
	 * @throws \Exception
	 */
	public function __construct(string $hostname, Configuration $config, LoggerInterface $logger)
	{
		if (!class_exists('Memcached', false)) {
			throw new Exception('Memcached class isn\'t available');
		}

		parent::__construct($hostname);

		$this->logger = $logger;

		$this->memcached = new Memcached();

		$memcached_hosts = $config->get('system', 'memcached_hosts');

		array_walk($memcached_hosts, function (&$value) {
			if (is_string($value)) {
				$value = array_map('trim', explode(',', $value));
			}
		});

		$this->firstServer = $memcached_hosts[0][0] ?? 'localhost';
		$this->firstPort   = $memcached_hosts[0][1] ?? 11211;

		$this->memcached->addServers($memcached_hosts);

		if (count($this->memcached->getServerList()) == 0) {
			throw new Exception('Expected Memcached servers aren\'t available, config:' . var_export($memcached_hosts, true));
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function getAllKeys($prefix = null)
	{
		$keys = $this->getOriginalKeys($this->getMemcachedKeys());

		return $this->filterArrayKeysByPrefix($keys, $prefix);
	}

	/**
	 * Get all memcached keys.
	 * Special function because getAllKeys() is broken since memcached 1.4.23.
	 *
	 * cleaned up version of code found on Stackoverflow.com by Maduka Jayalath
	 * @see https://stackoverflow.com/a/34724821
	 *
	 * @return array|int - all retrieved keys (or negative number on error)
	 */
	private function getMemcachedKeys()
	{
		$mem = @fsockopen($this->firstServer, $this->firstPort);
		if ($mem === false) {
			return -1;
		}

		// retrieve distinct slab
		$r = @fwrite($mem, 'stats items' . chr(10));
		if ($r === false) {
			return -2;
		}

		$slab = [];
		while (($l = @fgets($mem, 1024)) !== false) {
			// finished?
			$l = trim($l);
			if ($l == 'END') {
				break;
			}

			$m = [];
			// <STAT items:22:evicted_nonzero 0>
			$r = preg_match('/^STAT\sitems\:(\d+)\:/', $l, $m);
			if ($r != 1) {
				return -3;
			}
			$a_slab = $m[1];

			if (!array_key_exists($a_slab, $slab)) {
				$slab[$a_slab] = [];
			}
		}

		reset($slab);
		foreach ($slab as $a_slab_key => &$a_slab) {
			$r = @fwrite($mem, 'stats cachedump ' . $a_slab_key . ' 100' . chr(10));
			if ($r === false) {
				return -4;
			}

			while (($l = @fgets($mem, 1024)) !== false) {
				// finished?
				$l = trim($l);
				if ($l == 'END') {
					break;
				}

				$m = [];
				// ITEM 42 [118 b; 1354717302 s]
				$r = preg_match('/^ITEM\s([^\s]+)\s/', $l, $m);
				if ($r != 1) {
					return -5;
				}
				$a_key = $m[1];

				$a_slab[] = $a_key;
			}
		}

		// close the connection
		@fclose($mem);
		unset($mem);

		$keys = [];
		reset($slab);
		foreach ($slab AS &$a_slab) {
			reset($a_slab);
			foreach ($a_slab AS &$a_key) {
				$keys[] = $a_key;
			}
		}
		unset($slab);

		return $keys;
	}

	/**
	 * (@inheritdoc)
	 */
	public function get($key)
	{
		$return   = null;
		$cachekey = $this->getCacheKey($key);

		// We fetch with the hostname as key to avoid problems with other applications
		$value = $this->memcached->get($cachekey);

		if ($this->memcached->getResultCode() === Memcached::RES_SUCCESS) {
			$return = $value;
		} else {
			$this->logger->debug('Memcached \'get\' failed', ['result' => $this->memcached->getResultMessage()]);
		}

		return $return;
	}

	/**
	 * (@inheritdoc)
	 */
	public function set($key, $value, $ttl = Cache::FIVE_MINUTES)
	{
		$cachekey = $this->getCacheKey($key);

		// We store with the hostname as key to avoid problems with other applications
		if ($ttl > 0) {
			return $this->memcached->set(
				$cachekey,
				$value,
				$ttl
			);
		} else {
			return $this->memcached->set(
				$cachekey,
				$value
			);
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function delete($key)
	{
		$cachekey = $this->getCacheKey($key);
		return $this->memcached->delete($cachekey);
	}

	/**
	 * (@inheritdoc)
	 */
	public function clear($outdated = true)
	{
		if ($outdated) {
			return true;
		} else {
			return $this->memcached->flush();
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function add($key, $value, $ttl = Cache::FIVE_MINUTES)
	{
		$cachekey = $this->getCacheKey($key);
		return $this->memcached->add($cachekey, $value, $ttl);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return self::TYPE_MEMCACHED;
	}
}
