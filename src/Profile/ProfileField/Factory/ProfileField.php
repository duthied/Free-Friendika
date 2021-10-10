<?php

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
