<?php

namespace Friendica\Profile\ProfileField\Depository;

use Friendica\BaseDepository;
use Friendica\Database\Database;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Profile\ProfileField\Factory;
use Friendica\Profile\ProfileField\Entity;
use Friendica\Profile\ProfileField\Collection;
use Friendica\Security\PermissionSet\Depository\PermissionSet;
use Psr\Log\LoggerInterface;

class ProfileField extends BaseDepository
{
	/** @var  Factory\ProfileField */
	protected $factory;

	protected static $table_name = 'profile_field';

	public function __construct(Database $database, LoggerInterface $logger, Factory\ProfileField $factory)
	{
		parent::__construct($database, $logger, $factory);
	}

	/**
	 * @param array $condition
	 * @param array $params
	 * @return Entity\ProfileField
	 * @throws NotFoundException
	 */
	private function selectOne(array $condition, array $params = []): Entity\ProfileField
	{
		return parent::_selectOne($condition, $params);
	}

	private function select(array $condition, array $params = []): Collection\ProfileFields
	{
		return new Collection\ProfileFields(parent::_select($condition, $params)->getArrayCopy());
	}

	/**
	 * Returns all public available ProfileFields for a specific user
	 *
	 * @param int $uid the user id
	 *
	 * @return Collection\ProfileFields
	 */
	public function selectPublicFieldsByUserId(int $uid): Collection\ProfileFields
	{
		return $this->select([
			'uid'  => $uid,
			'psid' => PermissionSet::PUBLIC,
		]);
	}
}
