<?php

namespace Friendica\Test\src\Security\PermissionSet\Repository;

use Friendica\Database\Database;
use Friendica\Security\PermissionSet\Collection\PermissionSets;
use Friendica\Security\PermissionSet\Exception\PermissionSetNotFoundException;
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

	/**
	 * Asserts that the actual permissionset is equal to the expected permissionset
	 *   --> It skips the "id" fields
	 *
	 * @param PermissionSets $expected
	 * @param PermissionSets $actual
	 */
	public static function assertEqualPermissionSets(PermissionSets $expected, PermissionSets $actual)
	{
		self::assertEquals($expected->count(), $actual->count(), 'PermissionSets not even ' . PHP_EOL . 'expected: ' . print_r($expected, true) . 'actual: ' . print_r($actual, true));

		foreach ($expected as $outputPermissionSet) {
			self::assertCount(1, $actual->filter(function (PermissionSet $actualPermissionSet) use ($outputPermissionSet) {
				return (
					$actualPermissionSet->uid == $outputPermissionSet->uid &&
					$actualPermissionSet->allow_cid == $outputPermissionSet->allow_cid &&
					$actualPermissionSet->allow_gid == $outputPermissionSet->allow_gid &&
					$actualPermissionSet->deny_cid == $outputPermissionSet->deny_cid &&
					$actualPermissionSet->deny_gid == $outputPermissionSet->deny_gid
				);
			}), 'PermissionSet not found: ' . print_r($outputPermissionSet, true));
		}
	}

	public function dataSet()
	{
		return [
			'standard' => [
				'permissionSets' => [
					[
						'uid'       => 42,
						'allow_cid' => '<<43>>',
						'allow_gid' => '',
						'deny_cid'  => '<<44>>',
						'deny_gid'  => '',
					],
					[
						'uid'       => 42,
						'allow_cid' => '',
						'allow_gid' => '<<>>',
						'deny_cid'  => '',
						'deny_gid'  => '',
					],
					[
						'uid'       => 42,
						'allow_cid' => '<<44>>',
						'allow_gid' => '',
						'deny_cid'  => '',
						'deny_gid'  => '',
					],
				],
				'assertions' => [
					[
						'input' => [
							'cid' => 43,
							'uid' => 42,
						],
						'output' => new PermissionSets([
							new PermissionSet(42, [43], [], [44], []),
							new PermissionSet(42, [], [], [], []),
						]),
					],
					[
						'input' => [
							'cid' => 44,
							'uid' => 42,
						],
						'output' => new PermissionSets([
							new PermissionSet(42, [], [], [], []),
							new PermissionSet(42, [44], [], [], []),
						]),
					],
					[
						'input' => [
							'cid' => 47,
							'uid' => 42,
						],
						'output' => new PermissionSets([
							new PermissionSet(42, [], [], [], []),
						]),
					],
				]
			],
			'empty' => [
				'permissionSets' => [
					[
						'uid'       => 42,
						'allow_cid' => '',
						'allow_gid' => '<<>>',
						'deny_cid'  => '',
						'deny_gid'  => '',
					],
				],
				'assertions' => [
					[
						'input' => [
							'cid' => 43,
							'uid' => 42,
						],
						'output' => new PermissionSets([
							new PermissionSet(42, [], [], [], []),
						]),
					],
					[
						'input' => [
							'cid' => 44,
							'uid' => 42,
						],
						'output' => new PermissionSets([
							new PermissionSet(42, [], [], [], []),
						]),
					],
					[
						'input' => [
							'cid' => 47,
							'uid' => 42,
						],
						'output' => new PermissionSets([
							new PermissionSet(42, [], [], [], []),
						]),
					],
				]
			],
		];
	}

	/**
	 * @dataProvider dataSet
	 */
	public function testSelectContactId(array $inputPermissionSets, array $assertions)
	{
		/** @var Database $db */
		$db = $this->dice->create(Database::class);

		foreach ($inputPermissionSets as $inputPermissionSet) {
			$db->insert('permissionset', $inputPermissionSet);
		}

		foreach ($assertions as $assertion) {
			$permissionSets = $this->repository->selectByContactId($assertion['input']['cid'], $assertion['input']['uid']);
			self::assertInstanceOf(PermissionSets::class, $permissionSets);
			self::assertEqualPermissionSets($assertion['output'], $permissionSets);
		}
	}

	public function testSelectOneByIdInvalid()
	{
		self::expectException(PermissionSetNotFoundException::class);
		self::expectExceptionMessage('PermissionSet with id -1 for user 42 doesn\'t exist.');

		$this->repository->selectOneById(-1, 42);
	}

	/**
	 * @dataProvider dataSet
	 */
	public function testSelectOneById(array $inputPermissionSets, array $assertions)
	{
		/** @var Database $db */
		$db = $this->dice->create(Database::class);

		foreach ($inputPermissionSets as $inputPermissionSet) {
			$db->insert('permissionset', $inputPermissionSet);
			$id = $db->lastInsertId();

			self::assertInstanceOf(PermissionSet::class, $this->repository->selectOneById($id, $inputPermissionSet['uid']));
		}
	}
}
