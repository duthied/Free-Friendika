<?php

namespace Friendica\Profile\ProfileField\Factory;

use Friendica\BaseFactory;
use Friendica\Security\PermissionSet\Depository\PermissionSet as PermissionSetDepository;
use Friendica\Profile\ProfileField\Entity;
use Friendica\Capabilities\ICanCreateFromTableRow;
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
	public function createFromTableRow(array $row): Entity\ProfileField
	{
		return new Entity\ProfileField(
			$this->permissionSetDepository,
			$row['uid'],
			$row['order'],
			$row['psid'],
			$row['label'],
			$row['value'],
			new \DateTime($row['created'], new \DateTimeZone('UTC')),
			new \DateTime($row['edited'] ?? 'now', new \DateTimeZone('UTC'))
		);
	}
}
