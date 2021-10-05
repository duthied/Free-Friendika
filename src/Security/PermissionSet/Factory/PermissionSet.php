<?php

namespace Friendica\Security\PermissionSet\Factory;

use Friendica\BaseFactory;
use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Security\PermissionSet\Entity;
use Friendica\Util\ACLFormatter;
use Psr\Log\LoggerInterface;

class PermissionSet extends BaseFactory implements ICanCreateFromTableRow
{
	/** @var ACLFormatter */
	protected $formatter;

	public function __construct(LoggerInterface $logger, ACLFormatter $formatter)
	{
		parent::__construct($logger);

		$this->formatter = $formatter;
	}

	public function createFromTableRow(array $row): Entity\PermissionSet
	{
		return new Entity\PermissionSet(
			$row['uid'],
			$this->formatter->expand($row['allow_cid'] ?? []),
			$this->formatter->expand($row['allow_gid'] ?? []),
			$this->formatter->expand($row['deny_cid'] ?? []),
			$this->formatter->expand($row['deny_gid'] ?? []),
			$row['id'] ?? null
		);
	}

	public function createFromString(
		int $uid,
		string $allow_cid = '',
		string $allow_gid = '',
		string $deny_cid = '',
		string $deny_gid = '')
	{
		return new Entity\PermissionSet(
			$uid,
			$this->formatter->expand($allow_cid),
			$this->formatter->expand($allow_gid),
			$this->formatter->expand($deny_cid),
			$this->formatter->expand($deny_gid)
		);
	}

	public function createPrototypeForUser(int $uid, string $allowCid): Entity\PermissionSet
	{
		return new Entity\PermissionSet(
			$uid,
			$this->formatter->expand($allowCid)
		);
	}
}
