<?php

namespace Friendica\Core\Cache;

use Friendica\Core\Cache;
use Friendica\Database\DBA;
use Friendica\Util\DateTimeFormat;

/**
 * Database Cache Driver
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class DatabaseCacheDriver extends AbstractCacheDriver implements ICacheDriver
{
	/**
	 * (@inheritdoc)
	 */
	public function getAllKeys($prefix = null)
	{
		if (empty($prefix)) {
			$where = ['`expires` >= ?', DateTimeFormat::utcNow()];
		} else {
			$where = ['`expires` >= ? AND k LIKE CONCAT(?, \'%\')', DateTimeFormat::utcNow(), $prefix];
		}

		$stmt = DBA::select('cache', ['k'], $where);

		$list = [];
		while ($key = DBA::fetch($stmt)) {
			array_push($list, $key['k']);
		}
		DBA::close($stmt);

		return $list;
	}

	/**
	 * (@inheritdoc)
	 */
	public function get($key)
	{
		$cache = DBA::selectFirst('cache', ['v'], ['`k` = ? AND `expires` >= ?', $key, DateTimeFormat::utcNow()]);

		if (DBA::isResult($cache)) {
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
		$fields = [
			'v'       => serialize($value),
			'expires' => DateTimeFormat::utc('now + ' . $ttl . 'seconds'),
			'updated' => DateTimeFormat::utcNow()
		];

		return DBA::update('cache', $fields, ['k' => $key], true);
	}

	/**
	 * (@inheritdoc)
	 */
	public function delete($key)
	{
		return DBA::delete('cache', ['k' => $key]);
	}

	/**
	 * (@inheritdoc)
	 */
	public function clear($outdated = true)
	{
		if ($outdated) {
			return DBA::delete('cache', ['`expires` < NOW()']);
		} else {
			return DBA::delete('cache', ['`k` IS NOT NULL ']);
		}
	}
}
