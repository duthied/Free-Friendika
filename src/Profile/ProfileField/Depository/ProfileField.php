<?php

namespace Friendica\Profile\ProfileField\Depository;

use Friendica\BaseDepository;
use Friendica\Database\Database;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Profile\ProfileField\Factory;
use Friendica\Profile\ProfileField\Entity;
use Friendica\Profile\ProfileField\Collection;
use Friendica\Security\PermissionSet\Depository\PermissionSet as PermissionSetDepository;
use Psr\Log\LoggerInterface;

class ProfileField extends BaseDepository
{
	/** @var  Factory\ProfileField */
	protected $factory;

	protected static $table_name = 'profile_field';

	/** @var PermissionSetDepository */
	protected $permissionSetDepository;

	public function __construct(Database $database, LoggerInterface $logger, Factory\ProfileField $factory, PermissionSetDepository $permissionSetDepository)
	{
		parent::__construct($database, $logger, $factory);

		$this->permissionSetDepository = $this->permissionSetDepository;
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
			'psid' => PermissionSetDepository::PUBLIC,
		]);
	}

	/**
	 * @param int $uid Field owner user Id
	 *
	 * @throws \Exception
	 */
	public function selectByUserId(int $uid): Collection\ProfileFields
	{
		return $this->select(
			['uid' => $uid],
			['order' => ['order']]
		);
	}

	/**
	 * Retrieve all custom profile field a given contact is able to access to, including public profile fields.
	 *
	 * @param int $cid Private contact id, must be owned by $uid
	 * @param int $uid Field owner user id
	 *
	 * @throws \Exception
	 */
	public function selectByContactId(int $cid, int $uid): Collection\ProfileFields
	{
		$permissionSets = $this->permissionSetDepository->selectByContactId($cid, $uid);

		$permissionSetIds = $permissionSets->column('id');

		// Includes public custom fields
		$permissionSetIds[] = PermissionSetDepository::PUBLIC;

		return $this->select(
			['uid' => $uid, 'psid' => $permissionSetIds],
			['order' => ['order']]
		);
	}
}
