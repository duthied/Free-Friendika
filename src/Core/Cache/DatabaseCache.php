<?php

namespace Friendica\Core\Cache;

use Friendica\Database\Database;
use Friendica\Util\DateTimeFormat;

/**
 * Database Cache
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class DatabaseCache extends Cache implements ICache
{
	/**
	 * @var Database
	 */
	private $dba;

	public function __construct(string $hostname, Database $dba)
	{
		parent::__construct($hostname);

		$this->dba = $dba;
	}

	/**
	 * (@inheritdoc)
	 */
	public function getAllKeys($prefix = null)
	{
		if (empty($prefix)) {
			$where = ['`expires` >= ?', DateTimeFormat::utcNow()];
		} else {
			$where = ['`expires` >= ? AND `k` LIKE CONCAT(?, \'%\')', DateTimeFormat::utcNow(), $prefix];
		}

		$stmt = $this->dba->select('cache', ['k'], $where);

		$keys = [];
		while ($key = $this->dba->fetch($stmt)) {
			array_push($keys, $key['k']);
		}
		$this->dba->close($stmt);

		return $keys;
	}

	/**
	 * (@inheritdoc)
	 */
	public function get($key)
	{
		$cache = $this->dba->selectFirst('cache', ['v'], ['`k` = ? AND (`expires` >= ? OR `expires` = -1)', $key, DateTimeFormat::utcNow()]);

		if ($this->dba->isResult($cache)) {
			$cached = $cache['v'];
			$value = @unserialize($cached);

			// Only return a value if the serialized value is valid.
			// We also check if the db entry is a serialized
			// boolean 'false' value (which we want to return).
			if ($cached === serialize(false) || $value !== false) {
				return $value;
			}
		}

		return null;
	}

	/**
	 * (@inheritdoc)
	 */
	public function set($key, $value, $ttl = Cache::FIVE_MINUTES)
	{
		if ($ttl > 0) {
			$fields = [
				'v' => serialize($value),
				'expires' => DateTimeFormat::utc('now + ' . $ttl . 'seconds'),
				'updated' => DateTimeFormat::utcNow()
			];
		} else {
			$fields = [
				'v' => serialize($value),
				'expires' => -1,
				'updated' => DateTimeFormat::utcNow()
			];
		}

		return $this->dba->update('cache', $fields, ['k' => $key], true);
	}

	/**
	 * (@inheritdoc)
	 */
	public function delete($key)
	{
		return $this->dba->delete('cache', ['k' => $key]);
	}

	/**
	 * (@inheritdoc)
	 */
	public function clear($outdated = true)
	{
		if ($outdated) {
			return $this->dba->delete('cache', ['`expires` < NOW()']);
		} else {
			return $this->dba->delete('cache', ['`k` IS NOT NULL ']);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return self::TYPE_DATABASE;
	}
}
