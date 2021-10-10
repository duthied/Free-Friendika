<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Profile\ProfileField\Entity;

use Friendica\BaseEntity;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Profile\ProfileField\Exception\ProfileFieldNotFoundException;
use Friendica\Profile\ProfileField\Exception\UnexpectedPermissionSetException;
use Friendica\Security\PermissionSet\Depository\PermissionSet as PermissionSetDepository;
use Friendica\Security\PermissionSet\Entity\PermissionSet;

/**
 * Custom profile field model class.
 *
 * Custom profile fields are user-created arbitrary profile fields that can be assigned a permission set to restrict its
 * display to specific Friendica contacts as it requires magic authentication to work.
 *
 * @property-read int|null  $id
 * @property-read int       $uid
 * @property-read int       $order
 * @property-read int       $permissionSetId
 * @property-read string    $label
 * @property-read string    $value
 * @property-read \DateTime $created
 * @property-read \DateTime $edited
 * @property PermissionSet  $permissionSet
 */
class ProfileField extends BaseEntity
{
	/** @var int|null */
	protected $id;
	/** @var PermissionSet */
	protected $permissionSet;
	/** @var PermissionSetDepository */
	protected $permissionSetDepository;
	/** @var int */
	protected $uid;
	/** @var int */
	protected $order;
	/** @var int */
	protected $psid;
	/** @var string */
	protected $label;
	/** @var string */
	protected $value;
	/** @var \DateTime */
	protected $created;
	/** @var \DateTime */
	protected $edited;

	public function __construct(PermissionSetDepository $permissionSetDepository, int $uid, int $order, int $permissionSetId, string $label, string $value, \DateTime $created, \DateTime $edited, int $id = null, PermissionSet $permissionSet = null)
	{
		$this->permissionSetDepository = $permissionSetDepository;
		$this->permissionSet           = $permissionSet;

		$this->uid     = $uid;
		$this->order   = $order;
		$this->psid    = $permissionSetId;
		$this->label   = $label;
		$this->value   = $value;
		$this->created = $created;
		$this->edited  = $edited;
		$this->id      = $id;
	}

	/**
	 * @throws ProfileFieldNotFoundException
	 * @throws UnexpectedPermissionSetException
	 */
	public function __get($name)
	{
		switch ($name) {
			case 'permissionSet':
				if (empty($this->permissionSet)) {
					try {
						$permissionSet = $this->permissionSetDepository->selectOneById($this->psid, $this->uid);
						if ($permissionSet->uid !== $this->uid) {
							throw new UnexpectedPermissionSetException(sprintf('PermissionSet %d (user-id: %d) for ProfileField %d (user-id: %d) is invalid.', $permissionSet->id, $permissionSet->uid, $this->id, $this->uid));
						}

						$this->permissionSet = $permissionSet;
					} catch (NotFoundException $exception) {
						throw new UnexpectedPermissionSetException(sprintf('No PermissionSet found for ProfileField %d (user-id: %d).', $this->id, $this->uid));
					}
				}

				$return = $this->permissionSet;
				break;
			default:
				try {
					$return = parent::__get($name);
				} catch (InternalServerErrorException $exception) {
					throw new ProfileFieldNotFoundException($exception->getMessage());
				}
				break;
		}

		return $return;
	}

	/**
	 * Updates a ProfileField
	 *
	 * @param string        $value         The current or changed value
	 * @param int           $order         The current or changed order
	 * @param PermissionSet $permissionSet The current or changed PermissionSet
	 */
	public function update(string $value, int $order, PermissionSet $permissionSet)
	{
		$this->value         = $value;
		$this->order         = $order;
		$this->permissionSet = $permissionSet;
		$this->psid          = $permissionSet->id;
		$this->edited        = new \DateTime('now', new \DateTimeZone('UTC'));
	}

	/**
	 * Sets the order of the ProfileField
	 *
	 * @param int $order
	 */
	public function setOrder(int $order)
	{
		$this->order  = $order;
		$this->edited = new \DateTime('now', new \DateTimeZone('UTC'));
	}
}
