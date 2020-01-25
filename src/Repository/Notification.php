<?php

namespace Friendica\Repository;

use Exception;
use Friendica\BaseRepository;
use Friendica\Core\Hook;
use Friendica\Model;
use Friendica\Collection;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Util\DateTimeFormat;

class Notification extends BaseRepository
{
	protected static $table_name = 'notify';

	protected static $model_class = Model\Notification::class;

	protected static $collection_class = Collection\Notifications::class;

	/**
	 * {@inheritDoc}
	 *
	 * @return Model\Notification
	 */
	protected function create(array $data)
	{
		return new Model\Notification($this->dba, $this->logger, $this, $data);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return Collection\Notifications
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
	 * @return Model\Notification
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
	 * @return Model\Notification
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
