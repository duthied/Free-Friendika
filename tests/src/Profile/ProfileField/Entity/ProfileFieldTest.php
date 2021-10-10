<?php

namespace Friendica\Test\src\Profile\ProfileField\Entity;

use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Profile\ProfileField\Entity\ProfileField;
use Friendica\Profile\ProfileField\Exception\ProfileFieldNotFoundException;
use Friendica\Profile\ProfileField\Exception\UnexpectedPermissionSetException;
use Friendica\Profile\ProfileField\Factory\ProfileField as ProfileFieldFactory;
use Friendica\Security\PermissionSet\Depository\PermissionSet as PermissionSetDepository;
use Friendica\Security\PermissionSet\Factory\PermissionSet as PermissionSetFactory;
use Friendica\Test\MockedTest;
use Friendica\Util\ACLFormatter;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Logger\VoidLogger;
use Mockery\MockInterface;

class ProfileFieldTest extends MockedTest
{
	/** @var MockInterface|PermissionSetDepository */
	protected $permissionSetDepository;
	/** @var ProfileFieldFactory */
	protected $profileFieldFactory;
	/** @var PermissionSetFactory */
	protected $permissionSetFactory;

	protected function setUp(): void
	{
		parent::setUp();

		$this->permissionSetDepository = \Mockery::mock(PermissionSetDepository::class);
		$this->profileFieldFactory     = new ProfileFieldFactory(new VoidLogger(), $this->permissionSetDepository);
		$this->permissionSetFactory    = new PermissionSetFactory(new VoidLogger(), new ACLFormatter());
	}

	public function dataEntity()
	{
		return [
			'default' => [
				'uid'     => 23,
				'order'   => 1,
				'psid'    => 2,
				'label'   => 'test',
				'value'   => 'more',
				'created' => new \DateTime('2021-10-10T21:12:00.000000+0000', new \DateTimeZone('UTC')),
				'edited'  => new \DateTime('2021-10-10T21:12:00.000000+0000', new \DateTimeZone('UTC')),
			],
			'withId' => [
				'uid'     => 23,
				'order'   => 1,
				'psid'    => 2,
				'label'   => 'test',
				'value'   => 'more',
				'created' => new \DateTime('2021-10-10T21:12:00.000000+0000', new \DateTimeZone('UTC')),
				'edited'  => new \DateTime('2021-10-10T21:12:00.000000+0000', new \DateTimeZone('UTC')),
				'id'      => 54,
			],
		];
	}

	/**
	 * @dataProvider dataEntity
	 */
	public function testEntity(int $uid, int $order, int $psid, string $label, string $value, \DateTime $created, \DateTime $edited, $id = null)
	{
		$entity = new ProfileField($this->permissionSetDepository, $uid, $order, $psid, $label, $value, $created, $edited, $id);

		self::assertEquals($uid, $entity->uid);
		self::assertEquals($order, $entity->order);
		self::assertEquals($psid, $entity->permissionSetId);
		self::assertEquals($label, $entity->label);
		self::assertEquals($value, $entity->value);
		self::assertEquals($created, $entity->created);
		self::assertEquals($edited, $entity->edited);
		self::assertEquals($id, $entity->id);
	}

	/**
	 * @dataProvider dataEntity
	 */
	public function testUpdate(int $uid, int $order, int $psid, string $label, string $value, \DateTime $created, \DateTime $edited, $id = null)
	{
		$permissionSet = $this->permissionSetFactory->createFromTableRow(['uid' => 2, 'id' => $psid]);

		$entity = $this->profileFieldFactory->createFromTableRow([
			'uid'     => $uid,
			'order'   => $order,
			'psid'    => $psid,
			'label'   => $label,
			'value'   => $value,
			'created' => $created->format(DateTimeFormat::MYSQL),
			'edited'  => $edited->format(DateTimeFormat::MYSQL),
			'id'      => $id,
		], $permissionSet);

		$permissionSetNew = $this->permissionSetFactory->createFromTableRow([
			'uid'       => 2,
			'allow_cid' => '<1>',
			'id'        => 23
		]);

		$entity->update('updatedValue', 2345, $permissionSetNew);

		self::assertEquals($uid, $entity->uid);
		self::assertEquals(2345, $entity->order);
		self::assertEquals(23, $entity->permissionSetId);
		self::assertEquals($label, $entity->label);
		self::assertEquals('updatedValue', $entity->value);
		self::assertEquals($created, $entity->created);
		self::assertGreaterThan($edited, $entity->edited);
		self::assertEquals($id, $entity->id);
	}

	/**
	 * @dataProvider dataEntity
	 */
	public function testSetOrder(int $uid, int $order, int $psid, string $label, string $value, \DateTime $created, \DateTime $edited, $id = null)
	{
		$permissionSet = $this->permissionSetFactory->createFromTableRow(['uid' => 2, 'id' => $psid]);

		$entity = $this->profileFieldFactory->createFromTableRow([
			'uid'     => $uid,
			'order'   => $order,
			'psid'    => $psid,
			'label'   => $label,
			'value'   => $value,
			'created' => $created->format(DateTimeFormat::MYSQL),
			'edited'  => $edited->format(DateTimeFormat::MYSQL),
			'id'      => $id,
		], $permissionSet);

		$entity->setOrder(2345);

		self::assertEquals($uid, $entity->uid);
		self::assertEquals(2345, $entity->order);
		self::assertEquals($psid, $entity->permissionSetId);
		self::assertEquals($label, $entity->label);
		self::assertEquals($value, $entity->value);
		self::assertEquals($created, $entity->created);
		self::assertGreaterThan($edited, $entity->edited);
		self::assertEquals($id, $entity->id);
	}

	/**
	 * Test the exception because of a wrong property
	 *
	 * @dataProvider dataEntity
	 */
	public function testWrongGet(int $uid, int $order, int $psid, string $label, string $value, \DateTime $created, \DateTime $edited, $id = null)
	{
		$entity = new ProfileField($this->permissionSetDepository, $uid, $order, $psid, $label, $value, $created, $edited, $id);

		self::expectException(ProfileFieldNotFoundException::class);
		$entity->wrong;
	}

	/**
	 * Test gathering the permissionset
	 *
	 * @dataProvider dataEntity
	 */
	public function testPermissionSet(int $uid, int $order, int $psid, string $label, string $value, \DateTime $created, \DateTime $edited, $id = null)
	{
		$entity        = new ProfileField($this->permissionSetDepository, $uid, $order, $psid, $label, $value, $created, $edited, $id);
		$permissionSet = $this->permissionSetFactory->createFromTableRow(['uid' => $uid, 'id' => $psid]);

		$this->permissionSetDepository->shouldReceive('selectOneById')->with($psid)->andReturns($permissionSet);

		self::assertEquals($psid, $entity->permissionSet->id);
	}

	/**
	 * Test the exception because of incompatible user id
	 *
	 * @dataProvider dataEntity
	 */
	public function testWrongPermissionSet(int $uid, int $order, int $psid, string $label, string $value, \DateTime $created, \DateTime $edited, $id = null)
	{
		$entity        = new ProfileField($this->permissionSetDepository, $uid, $order, $psid, $label, $value, $created, $edited, $id);
		$permissionSet = $this->permissionSetFactory->createFromTableRow(['uid' => 12345, 'id' => $psid]);

		$this->permissionSetDepository->shouldReceive('selectOneById')->with($psid)->andReturns($permissionSet);

		self::expectException(UnexpectedPermissionSetException::class);
		self::expectExceptionMessage(sprintf('PermissionSet %d (user-id: %d) for ProfileField %d (user-id: %d) is invalid.', $psid, 12345, $id, $uid));
		$entity->permissionSet;
	}

	/**
	 * Test the exception because of missing permission set
	 *
	 * @dataProvider dataEntity
	 */
	public function testMissingPermissionSet(int $uid, int $order, int $psid, string $label, string $value, \DateTime $created, \DateTime $edited, $id = null)
	{
		$entity = new ProfileField($this->permissionSetDepository, $uid, $order, $psid, $label, $value, $created, $edited, $id);

		$this->permissionSetDepository->shouldReceive('selectOneById')->with($psid)
									  ->andThrow(new NotFoundException('test'));

		self::expectException(UnexpectedPermissionSetException::class);
		self::expectExceptionMessage(sprintf('No PermissionSet found for ProfileField %d (user-id: %d).', $id, $uid));
		$entity->permissionSet;
	}

	/**
	 * Test the exception because the factory cannot find a permissionSet ID, nor the permissionSet itself
	 *
	 * @dataProvider dataEntity
	 */
	public function testMissingPermissionFactory(int $uid, int $order, int $psid, string $label, string $value, \DateTime $created, \DateTime $edited, $id = null)
	{
		self::expectException(UnexpectedPermissionSetException::class);
		self::expectExceptionMessage('Either set the permission set ID or the permission set itself');

		$entity = $this->profileFieldFactory->createFromTableRow([
			'uid'     => $uid,
			'order'   => $order,
			'label'   => $label,
			'value'   => $value,
			'created' => $created->format(DateTimeFormat::MYSQL),
			'edited'  => $edited->format(DateTimeFormat::MYSQL),
			'id'      => $id,
		]);
	}
}
