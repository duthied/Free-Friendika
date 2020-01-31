<?php

namespace Friendica\Repository;

use Friendica\BaseRepository;
use Friendica\Collection;
use Friendica\Model;

class FSuggest extends BaseRepository
{
	protected static $table_name = 'fsuggest';

	protected static $model_class = Model\FSuggest::class;

	protected static $collection_class = Collection\FSuggests::class;

	/**
	 * @param array $data
	 * @return Model\FSuggest
	 */
	protected function create(array $data)
	{
		return new Model\FSuggest($this->dba, $this->logger, $data);
	}

	/**
	 * Returns the Friend Suggest based on it's ID
	 *
	 * @param int $id The id of the fsuggest
	 *
	 * @return Model\FSuggest
	 *
	 * @throws \Friendica\Network\HTTPException\NotFoundException
	 */
	public function getById(int $id)
	{
		return $this->selectFirst(['id' => $id]);
	}

	/**
	 * @param array $condition
	 * @return Model\FSuggest
	 * @throws \Friendica\Network\HTTPException\NotFoundException
	 */
	public function selectFirst(array $condition)
	{
		return parent::selectFirst($condition);
	}

	/**
	 * @param array $condition
	 * @param array $params
	 * @return Collection\FSuggests
	 * @throws \Exception
	 */
	public function select(array $condition = [], array $params = [])
	{
		return parent::select($condition, $params);
	}

	/**
	 * @param array $condition
	 * @param array $params
	 * @param int|null $max_id
	 * @param int|null $since_id
	 * @param int $limit
	 * @return Collection\FSuggests
	 * @throws \Exception
	 */
	public function selectByBoundaries(array $condition = [], array $params = [], int $max_id = null, int $since_id = null, int $limit = self::LIMIT)
	{
		return parent::selectByBoundaries($condition, $params, $max_id, $since_id, $limit);
	}
}
