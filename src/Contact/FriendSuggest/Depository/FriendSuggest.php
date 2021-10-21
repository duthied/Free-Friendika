<?php

namespace Friendica\Contact\FriendSuggest\Depository;

use Friendica\BaseDepository;
use Friendica\Contact\FriendSuggest\Exception\FriendSuggestPersistenceException;
use Friendica\Contact\FriendSuggest\Factory;
use Friendica\Contact\FriendSuggest\Entity;
use Friendica\Database\Database;
use Friendica\Network\HTTPException\NotFoundException;
use Psr\Log\LoggerInterface;

class FriendSuggest extends BaseDepository
{
	/** @var Factory\FriendSuggest */
	protected $factory;

	protected static $table_name = 'fsuggest';

	public function __construct(Database $database, LoggerInterface $logger, Factory\FriendSuggest $factory)
	{
		parent::__construct($database, $logger, $factory);
	}

	private function convertToTableRow(Entity\FriendSuggest $fsuggest): array
	{
		return [
			'uid'     => $fsuggest->uid,
			'cid'     => $fsuggest->cid,
			'name'    => $fsuggest->name,
			'url'     => $fsuggest->url,
			'request' => $fsuggest->request,
			'photo'   => $fsuggest->photo,
			'note'    => $fsuggest->note,
		];
	}

	/**
	 * @param array $condition
	 * @param array $params
	 *
	 * @return Entity\FriendSuggest
	 *
	 * @throws NotFoundException The underlying exception if there's no FriendSuggest with the given conditions
	 */
	private function selectOne(array $condition, array $params = []): Entity\FriendSuggest
	{
		return parent::_selectOne($condition, $params);
	}

	public function selectOneById(int $id): Entity\FriendSuggest
	{
		return $this->selectOne(['id' => $id]);
	}

	public function save(Entity\FriendSuggest $fsuggest): Entity\FriendSuggest
	{
		try {
			$fields = $this->convertToTableRow($fsuggest);

			if ($fsuggest->id) {
				$this->db->update(self::$table_name, $fields, ['id' => $fsuggest->id]);
				return $this->factory->createFromTableRow($fields);
			} else {
				$this->db->insert(self::$table_name, $fields);
				return $this->selectOneById($this->db->lastInsertId());
			}
		} catch (\Exception $exception) {
			throw new FriendSuggestPersistenceException(sprintf('Cannot insert/update the FriendSuggestion %d for user %d', $fsuggest->id, $fsuggest->uid), $exception);
		}
	}
}
