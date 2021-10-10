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

namespace Friendica\Profile\ProfileField\Factory;

use Friendica\BaseFactory;
use Friendica\Security\PermissionSet\Depository\PermissionSet as PermissionSetDepository;
use Friendica\Profile\ProfileField\Entity;
use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Security\PermissionSet\Entity\PermissionSet;
use Psr\Log\LoggerInterface;

class ProfileField extends BaseFactory implements ICanCreateFromTableRow
{
	/** @var PermissionSetDepository */
	private $permissionSetDepository;

	public function __construct(LoggerInterface $logger, PermissionSetDepository $permissionSetDepository)
	{
		parent::__construct($logger);

		$this->permissionSetDepository = $permissionSetDepository;
	}

	/**
	 * @inheritDoc
	 */
	public function createFromTableRow(array $row, PermissionSet $permissionSet = null): Entity\ProfileField
	{
		return new Entity\ProfileField(
			$this->permissionSetDepository,
			$row['uid'],
			$row['order'],
			$row['psid'],
			$row['label'],
			$row['value'],
			new \DateTime($row['created'] ?? 'now', new \DateTimeZone('UTC')),
			new \DateTime($row['edited'] ?? 'now', new \DateTimeZone('UTC')),
			$row['id'],
			$permissionSet
		);
	}

	public function createFromString(
		int $uid,
		int $order,
		string $label,
		string $value,
		PermissionSet $permissionSet
	): Entity\ProfileField {
		return $this->createFromTableRow([
			'uid'   => $uid,
			'order' => $order,
			'psid'  => $permissionSet->id,
			'label' => $label,
			'value' => $value,
		], $permissionSet);
	}
}
