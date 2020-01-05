<?php

namespace Friendica\Repository;

use Friendica\BaseRepository;
use Friendica\Collection;
use Friendica\Model;

/**
 * @method Model\Introduction       selectFirst(array $condition)
 * @method Collection\Introductions select(array $condition = [], array $params = [])
 * @method Collection\Introductions selectByBoundaries(array $condition = [], array $params = [], int $max_id = null, int $since_id = null, int $limit = self::LIMIT)
 */
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
}
