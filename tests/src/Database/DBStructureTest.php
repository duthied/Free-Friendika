<?php

namespace Friendica\Test\src\Database;

use Dice\Dice;
use Friendica\BaseObject;
use Friendica\Database\DBStructure;
use Friendica\Test\DatabaseTest;

class DBStructureTest extends DatabaseTest
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function setUp()
	{
		parent::setUp();

		$dice = new Dice();
		$dice = $dice->addRules(include __DIR__ . '/../../../static/dependencies.config.php');
		BaseObject::setDependencyInjection($dice);
	}

	/**
	 * @small
	 */
	public function testExists() {
		$this->assertTrue(DBStructure::existsTable('config'));

		$this->assertFalse(DBStructure::existsTable('notatable'));

		$this->assertTrue(DBStructure::existsColumn('config', ['k']));
		$this->assertFalse(DBStructure::existsColumn('config', ['nonsense']));
		$this->assertFalse(DBStructure::existsColumn('config', ['k', 'nonsense']));
	}

	/**
	 * @small
	 */
	public function testRename() {
		$fromColumn = 'k';
		$toColumn = 'key';
		$fromType = 'varbinary(255) not null';
		$toType = 'varbinary(255) not null comment \'Test To Type\'';

		$this->assertTrue(DBStructure::rename('config', [ $fromColumn => [ $toColumn, $toType ]]));
		$this->assertTrue(DBStructure::existsColumn('config', [ $toColumn ]));
		$this->assertFalse(DBStructure::existsColumn('config', [ $fromColumn ]));

		$this->assertTrue(DBStructure::rename('config', [ $toColumn => [ $fromColumn, $fromType ]]));
		$this->assertTrue(DBStructure::existsColumn('config', [ $fromColumn ]));
		$this->assertFalse(DBStructure::existsColumn('config', [ $toColumn ]));
	}

	/**
	 * @small
	 */
	public function testChangePrimaryKey() {
		$oldID = 'client_id';
		$newID = 'pw';

		$this->assertTrue(DBStructure::rename('clients', [ $newID ], DBStructure::RENAME_PRIMARY_KEY));
		$this->assertTrue(DBStructure::rename('clients', [ $oldID ], DBStructure::RENAME_PRIMARY_KEY));
	}
}
