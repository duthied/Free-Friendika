<?php

namespace Friendica\Core\Config\Model;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Config\Capability\ISetConfigValuesTransactionally;
use Friendica\Core\Config\Util\SerializeUtil;
use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Database\Database;

/**
 * Complete system configuration model, bound with the database
 */
class DatabaseConfig implements IManageConfigValues
{
	/** @var Database */
	protected $database;
	/** @var Cache */
	protected $cache;

	public function __construct(Database $database, Cache $cache)
	{
		$this->database = $database;
		$this->cache    = $cache;

		$this->reload();
	}

	/** {@inheritDoc} */
	public function reload()
	{
		$config = $this->database->selectToArray('config');

		foreach ($config as $entry) {
			$this->cache->set($entry['cat'], $entry['k'], SerializeUtil::maybeUnserialize($entry['v']), Cache::SOURCE_DATA);
		}
	}

	public function setAndSave(Cache $setCache, Cache $delCache): bool
	{
		$this->database->transaction();

		foreach ($setCache->getAll() as $category => $data) {
			foreach ($data as $key => $value) {
				$this->cache->set($category, $key, $value, Cache::SOURCE_DATA);
				$this->database->insert('config', ['cat' => $category, 'k' => $key, 'v' => serialize($value)], Database::INSERT_UPDATE);
			}
		}

		foreach ($delCache->getAll() as $category => $keys) {
			foreach ($keys as $key => $value) {
				$this->cache->delete($category, $key);
				$this->database->delete('config', ['cat' => $category, 'k' => $key]);
			}
		}

		return $this->database->commit();
	}

	/** {@inheritDoc} */
	public function get(string $cat, string $key = null, $default_value = null)
	{
		return $this->cache->get($cat, $key) ?? $default_value;
	}

	/** {@inheritDoc} */
	public function set(string $cat, string $key, $value): bool
	{
		$this->cache->set($cat, $key, $value, Cache::SOURCE_DATA);
		return $this->database->insert('config', ['cat' => $cat, 'k' => $key, 'v' => serialize($value)], Database::INSERT_UPDATE);
	}

	/** {@inheritDoc} */
	public function beginTransaction(): ISetConfigValuesTransactionally
	{
		return new ConfigTransaction($this);
	}

	/** {@inheritDoc} */
	public function delete(string $cat, string $key): bool
	{
		$this->cache->delete($cat, $key);
		return $this->database->delete('config', ['cat' => $cat, 'k' => $key]);
	}

	/** {@inheritDoc} */
	public function getCache(): Cache
	{
		return $this->cache;
	}
}
