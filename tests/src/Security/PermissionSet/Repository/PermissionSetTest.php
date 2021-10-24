<?php

namespace Friendica\Test\src\Security\PermissionSet\Repository;

use Friendica\Security\PermissionSet\Repository\PermissionSet as PermissionSetRepository;
use Friendica\Security\PermissionSet\Entity\PermissionSet;
use Friendica\Security\PermissionSet\Factory\PermissionSet as PermissionSetFactory;
use Friendica\Test\FixtureTest;
use Friendica\DI;

class PermissionSetTest extends FixtureTest
{
	/** @var PermissionSetRepository */
	private $repository;
	/** @var PermissionSetFactory */
	private $factory;

	public function setUp(): void
	{
		parent::setUp();

		$this->repository = DI::permissionSet();
		$this->factory    = DI::permissionSetFactory();
	}

	public function testSelectOneByIdPublic()
	{
		$permissionSet = $this->repository->selectPublicForUser(1);

		$this->assertInstanceOf(PermissionSet::class, $permissionSet);
		self::assertEmpty($permissionSet->allow_cid);
		self::assertEmpty($permissionSet->allow_gid);
		self::assertEmpty($permissionSet->deny_cid);
		self::assertEmpty($permissionSet->deny_gid);
		self::assertEmpty(PermissionSetRepository::PUBLIC, $permissionSet->id);
		self::assertEquals(1, $permissionSet->uid);
	}

	/**
	 * Test create/update PermissionSets
	 */
	public function testSaving()
	{
		$permissionSet = $this->factory->createFromString(42, '', '<~>');

		$permissionSet = $this->repository->selectOrCreate($permissionSet);

		self::assertNotNull($permissionSet->id);

		$permissionSetSelected = $this->repository->selectOneById($permissionSet->id, 42);

		self::assertEquals($permissionSet, $permissionSetSelected);

		$newPermissionSet   = $permissionSet->withAllowedContacts(['1', '2']);
		$savedPermissionSet = $this->repository->save($newPermissionSet);

		self::assertNotNull($savedPermissionSet->id);
		self::assertNull($newPermissionSet->id);

		$permissionSetSavedSelected = $this->repository->selectOneById($savedPermissionSet->id, 42);

		self::assertEquals($savedPermissionSet, $permissionSetSavedSelected);
	}
}
