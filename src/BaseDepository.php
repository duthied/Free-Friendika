<?php

namespace Friendica;

use Exception;
use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Database\Database;
use Friendica\Network\HTTPException\NotFoundException;
use Psr\Log\LoggerInterface;

/**
 * Depositories are meant to store and retrieve Entities from the database.
 *
 * The reason why there are methods prefixed with an underscore is because PHP doesn't support generic polymorphism
 * which means we can't direcly overload base methods and make parameters more strict (from a parent class to a child
 * class for example)
 *
 * Similarly, we can't make an overloaded method return type more strict until we only support PHP version 7.4 but this
 * is less pressing.
 */
abstract class BaseDepository
{
	const LIMIT = 30;

	/**
	 * @var string This should be set to the main database table name the depository is using
	 */
	protected static $table_name;

	/** @var Database */
	protected $db;

	/** @var LoggerInterface */
	protected $logger;

	/** @var ICanCreateFromTableRow */
	protected $factory;

	public function __construct(Database $database, LoggerInterface $logger, ICanCreateFromTableRow $factory)
	{
		$this->db      = $database;
		$this->logger  = $logger;
		$this->factory = $factory;
	}


	/**
	 * @param array $condition
	 * @param array $params
	 * @return BaseCollection
	 * @throws Exception
	 */
	protected function _select(array $condition, array $params = []): BaseCollection
	{
		$rows = $this->db->selectToArray(static::$table_name, [], $condition, $params);

		$Entities = new BaseCollection();
		foreach ($rows as $fields) {
			$Entities[] = $this->factory->createFromTableRow($fields);
		}

		return $Entities;
	}

	/**
	 * @param array $condition
	 * @param array $params
	 * @return BaseEntity
	 * @throws NotFoundException
	 */
	protected function _selectOne(array $condition, array $params = []): BaseEntity
	{
		$fields = $this->db->selectFirst(static::$table_name, [], $condition, $params);
		if (!$this->db->isResult($fields)) {
			throw new NotFoundException();
		}

		return $this->factory->createFromTableRow($fields);
	}

	/**
	 * @param array $condition
	 * @param array $params
	 * @return int
	 * @throws Exception
	 */
	public function count(array $condition, array $params = []): int
	{
		return $this->db->count(static::$table_name, $condition, $params);
	}

	/**
	 * @param array $condition
	 * @return bool
	 * @throws Exception
	 */
	public function exists(array $condition): bool
	{
		return $this->db->exists(static::$table_name, $condition);
	}
}
