<?php

namespace Friendica\Profile\ProfileField\Depository;

use Friendica\BaseDepository;
use Friendica\Database\Database;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Profile\ProfileField\Exception\ProfileFieldNotFoundException;
use Friendica\Profile\ProfileField\Exception\ProfileFieldPersistenceException;
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

		$this->permissionSetDepository = permissionSetDepository;
	}

	/**
	 * @param array $condition
	 * @param array $params
	 * @return Entity\ProfileField
	 * @throws ProfileFieldNotFoundException
	 */
	private function selectOne(array $condition, array $params = []): Entity\ProfileField
	{
		try {
			return parent::_selectOne($condition, $params);
		} catch (NotFoundException $exception) {
			throw new ProfileFieldNotFoundException($exception->getMessage());
		}
	}

	/**
	 * @param array $condition
	 * @param array $params
	 *
	 * @return Collection\ProfileFields
	 *
	 * @throws ProfileFieldPersistenceException In case of underlying persistence exceptions
	 */
	private function select(array $condition, array $params = []): Collection\ProfileFields
	{
		try {
			return new Collection\ProfileFields(parent::_select($condition, $params)->getArrayCopy());
		} catch (\Exception $exception) {
			throw new ProfileFieldPersistenceException('Cannot select ProfileFields', $exception);
		}
	}

	/**
	 * Converts a given ProfileField into a DB compatible row array
	 *
	 * @param Entity\ProfileField $profileField
	 *
	 * @return array
	 */
	protected function convertToTableRow(Entity\ProfileField $profileField): array
	{
		return [
			'label'   => $profileField->label,
			'value'   => $profileField->value,
			'order'   => $profileField->order,
			'created' => $profileField->created,
			'edited'  => $profileField->edited,
		];
	}

	/**
	 * Returns all public available ProfileFields for a specific user
	 *
	 * @param int $uid the user id
	 *
	 * @return Collection\ProfileFields
	 *
	 * @throws ProfileFieldPersistenceException In case of underlying persistence exceptions
	 */
	public function selectPublicFieldsByUserId(int $uid): Collection\ProfileFields
	{
		try {
			return $this->select([
				'uid'  => $uid,
				'psid' => PermissionSetDepository::PUBLIC,
			]);
		} catch (\Exception $exception) {
			throw new ProfileFieldPersistenceException(sprintf('Cannot select public ProfileField for user "%d"', $uid), $exception);
		}
	}

	/**
	 * @param int $uid Field owner user Id
	 *
	 * @throws ProfileFieldPersistenceException In case of underlying persistence exceptions
	 */
	public function selectByUserId(int $uid): Collection\ProfileFields
	{
		try {
			return $this->select(
				['uid' => $uid],
				['order' => ['order']]
			);
		} catch (\Exception $exception) {
			throw new ProfileFieldPersistenceException(sprintf('Cannot select ProfileField for user "%d"', $uid), $exception);
		}
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

	/**
	 * @param int $id
	 *
	 * @return Entity\ProfileField
	 *
	 * @ProfileFieldNotFoundException In case there is no ProfileField found
	 */
	public function selectOnyById(int $id): Entity\ProfileField
	{
		try {
			return $this->selectOne(['id' => $id]);
		} catch (\Exception $exception) {
			throw new ProfileFieldNotFoundException(sprintf('Cannot find Profile "%s"', $id), $exception);
		}
	}

	/**
	 * Delets a whole collection of ProfileFields
	 *
	 * @param Collection\ProfileFields $profileFields
	 *
	 * @return bool
	 * @throws ProfileFieldPersistenceException in case the persistence layer cannot delete the ProfileFields
	 */
	public function deleteCollection(Collection\ProfileFields $profileFields): bool
	{
		try {
			return $this->db->delete(self::$table_name, ['id' => $profileFields->column('id')]);
		} catch (\Exception $exception) {
			throw new ProfileFieldPersistenceException('Cannot delete ProfileFields', $exception);
		}
	}

	/**
	 * @param Entity\ProfileField $profileField
	 *
	 * @return Entity\ProfileField
	 * @throws ProfileFieldPersistenceException in case the persistence layer cannot save the ProfileField
	 */
	public function save(Entity\ProfileField $profileField): Entity\ProfileField
	{
		$fields = $this->convertToTableRow($profileField);

		try {
			if ($profileField->id) {
				$this->db->update(self::$table_name, $fields, ['id' => $profileField]);
			} else {
				$this->db->insert(self::$table_name, $fields);

				$profileField = $this->selectOnyById($this->db->lastInsertId());
			}
		} catch (\Exception $exception) {
			throw new ProfileFieldPersistenceException(sprintf('Cannot save ProfileField with id "%d" and label "%s"', $profileField->id, $profileField->label), $exception);
		}

		return $profileField;
	}

	public function saveCollectionForUser(int $uid, Collection\ProfileFields $profileFields): Collection\ProfileFields
	{
		$savedProfileFields = new Collection\ProfileFields();

		$profileFieldsOld = $this->selectByUserId($uid);

		// Prunes profile field whose label has been emptied
		$labels                 = $profileFields->column('label');
		$prunedProfileFieldsOld = $profileFieldsOld->filter(function (Entity\ProfileField $profileFieldOld) use ($labels) {
			return array_search($profileFieldOld->label, $labels) === false;
		});
		$this->deleteCollection($prunedProfileFieldsOld);

		// Update the order based on the new Profile Field Collection
		$order                 = 0;
		$labelProfileFieldsOld = $profileFieldsOld->column('id', 'label');

		foreach ($profileFields as $profileField) {
			// Update existing field (preserve
			if (array_key_exists($profileField->label, $labelProfileFieldsOld)) {
				$profileFieldOldId = $labelProfileFieldsOld[$profileField->label];
				/** @var Entity\ProfileField $foundProfileFieldOld */
				$foundProfileFieldOld = $profileFieldsOld[$profileFieldOldId];
				$foundProfileFieldOld->update(
					$profileField->value,
					$order,
					$profileField->permissionSet
				);

				$savedProfileFields->append($this->save($foundProfileFieldOld));
			} else {
				$profileField->setOrder($order);
				$savedProfileFields->append($this->save($profileField));
			}

			$order++;
		}

		return $savedProfileFields;
	}
}
