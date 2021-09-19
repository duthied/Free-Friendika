<?php

namespace Friendica\Navigation\Notifications\Depository;

use Friendica\BaseDepository;
use Friendica\Core\Hook;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Navigation\Notifications\Collection;
use Friendica\Navigation\Notifications\Entity;
use Friendica\Navigation\Notifications\Exception;
use Friendica\Navigation\Notifications\Factory;
use Friendica\Network\HTTPException;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;

class Notify extends BaseDepository
{
	/** @var Factory\Notify  */
	protected $factory;

	protected static $table_name = 'notify';

	public function __construct(Database $database, LoggerInterface $logger, Factory\Notify $factory = null)
	{
		parent::__construct($database, $logger, $factory ?? new Factory\Notify($logger));
	}

	/**
	 * @param array $condition
	 * @param array $params
	 * @return Entity\Notify
	 * @throws HTTPException\NotFoundException
	 */
	private function selectOne(array $condition, array $params = []): Entity\Notify
	{
		return parent::_selectOne($condition, $params);
	}

	private function select(array $condition, array $params = []): Collection\Notifies
	{
		return new Collection\Notifies(parent::_select($condition, $params)->getArrayCopy());
	}

	public function countForUser($uid, array $condition, array $params = []): int
	{
		$condition = DBA::mergeConditions($condition, ['uid' => $uid]);

		return $this->count($condition, $params);
	}

	public function existsForUser($uid, array $condition): bool
	{
		$condition = DBA::mergeConditions($condition, ['uid' => $uid]);

		return $this->exists($condition);
	}

	/**
	 * @param int $id
	 * @return Entity\Notify
	 * @throws HTTPException\NotFoundException
	 */
	public function selectOneById(int $id): Entity\Notify
	{
		return $this->selectOne(['id' => $id]);
	}

	public function selectForUser(int $uid, array $condition, array $params): Collection\Notifies
	{
		$condition = DBA::mergeConditions($condition, ['uid' => $uid]);

		return $this->select($condition, $params);
	}

	/**
	 * Returns notifications for the user, unread first, ordered in descending chronological order.
	 *
	 * @param int $uid
	 * @param int $limit
	 * @return Collection\Notifies
	 */
	public function selectAllForUser(int $uid, int $limit): Collection\Notifies
	{
		return $this->selectForUser($uid, [], ['order' => ['seen' => 'ASC', 'date' => 'DESC'], 'limit' => $limit]);
	}

	public function setAllSeenForUser(int $uid, array $condition = []): bool
	{
		$condition = DBA::mergeConditions($condition, ['uid' => $uid]);

		return $this->db->update(self::$table_name, ['seen' => true], $condition);
	}

	/**
	 * @param Entity\Notify $Notify
	 * @return Entity\Notify
	 * @throws HTTPException\NotFoundException
	 * @throws HTTPException\InternalServerErrorException
	 * @throws Exception\NotificationCreationInterceptedException
	 */
	public function save(Entity\Notify $Notify): Entity\Notify
	{
		$fields = [
			'type'          => $Notify->type,
			'name'          => $Notify->name,
			'url'           => $Notify->url,
			'photo'         => $Notify->photo,
			'msg'           => $Notify->msg,
			'uid'           => $Notify->uid,
			'link'          => $Notify->link,
			'iid'           => $Notify->itemId,
			'parent'        => $Notify->parent,
			'seen'          => $Notify->seen,
			'verb'          => $Notify->verb,
			'otype'         => $Notify->otype,
			'name_cache'    => $Notify->name_cache,
			'msg_cache'     => $Notify->msg_cache,
			'uri-id'        => $Notify->uriId,
			'parent-uri-id' => $Notify->parentUriId,
		];

		if ($Notify->id) {
			$this->db->update(self::$table_name, $fields, ['id' => $Notify->id]);
		} else {
			$fields['date'] = DateTimeFormat::utcNow();
			Hook::callAll('enotify_store', $fields);

			$this->db->insert(self::$table_name, $fields);

			$Notify = $this->selectOneById($this->db->lastInsertId());
		}

		return $Notify;
	}

	public function setAllSeenForRelatedNotify(Entity\Notify $Notify): bool
	{
		$condition = [
			'(`link` = ? OR (`parent` != 0 AND `parent` = ? AND `otype` = ?)) AND `uid` = ?',
			$Notify->link,
			$Notify->parent,
			$Notify->otype,
			$Notify->uid
		];
		return $this->db->update(self::$table_name, ['seen' => true], $condition);
	}
}
