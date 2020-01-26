<?php

namespace Friendica\Repository;

use Exception;
use Friendica\BaseRepository;
use Friendica\Core\Hook;
use Friendica\Model;
use Friendica\Collection;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Util\DateTimeFormat;

class Notify extends BaseRepository
{
	protected static $table_name = 'notify';

	protected static $model_class = Model\Notify::class;

	protected static $collection_class = Collection\Notifies::class;

	/**
	 * {@inheritDoc}
	 *
	 * @return Model\Notify
	 */
	protected function create(array $data)
	{
		return new Model\Notify($this->dba, $this->logger, $this, $data);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return Collection\Notifies
	 */
	public function select(array $condition = [], array $params = [])
	{
		$params['order'] = $params['order'] ?? ['date' => 'DESC'];

		$condition = array_merge($condition, ['uid' => local_user()]);

		return parent::select($condition, $params);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return Model\Notify
	 * @throws NotFoundException
	 */
	public function getByID(int $id)
	{
		return $this->selectFirst(['id' => $id, 'uid' => local_user()]);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return bool true on success, false on error
	 * @throws Exception
	 */
	public function setAllSeen(bool $seen = true)
	{
		return $this->dba->update('notify', ['seen' => $seen], ['uid' => local_user()]);
	}

	/**
	 * @param array $fields
	 *
	 * @return Model\Notify
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws Exception
	 */
	public function insert(array $fields)
	{
		$fields['date']  = DateTimeFormat::utcNow();
		$fields['abort'] = false;

		Hook::callAll('enotify_store', $fields);

		if ($fields['abort']) {
			$this->logger->debug('Abort adding notification entry', ['fields' => $fields]);
			return null;
		}

		$this->logger->debug('adding notification entry', ['fields' => $fields]);

		return parent::insert($fields);
	}
}
