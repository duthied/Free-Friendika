<?php

namespace Friendica\Repository;

use Friendica\BaseRepository;
use Friendica\Collection;
use Friendica\Model;

class Introduction extends BaseRepository
{
	protected static $table_name = 'intro';

	protected static $model_class = Model\Introduction::class;

	protected static $collection_class = Collection\Introductions::class;

	/**
	 * @param array $data
	 * @return Model\Introduction
	 */
	protected function create(array $data)
	{
		return new Model\Introduction($this->dba, $this->logger, $this, $data);
	}

	/**
	 * @param array $condition
	 * @return Model\Introduction
	 * @throws \Friendica\Network\HTTPException\NotFoundException
	 */
	public function selectFirst(array $condition)
	{
		return parent::selectFirst($condition);
	}

	/**
	 * @param array $condition
	 * @param array $order An optional array with order information
	 * @param int|array $limit Optional limit information
	 *
	 * @return Collection\Introductions
	 * @throws \Exception
	 */
	public function select(array $condition = [], array $order = [], $limit = null)
	{
		return parent::select($condition, $order, $limit);
	}

	/**
	 * @param array $condition
	 * @param array $order
	 * @param int|null $max_id
	 * @param int|null $since_id
	 * @param int|array $limit
	 * @return Collection\Introductions
	 * @throws \Exception
	 */
	public function selectByBoundaries(array $condition = [], array $order = [], int $max_id = null, int $since_id = null, int $limit = self::LIMIT)
	{
		return parent::selectByBoundaries($condition, $order, $max_id, $since_id, $limit);
	}
}
