<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Test\Util;

use Friendica\Database\DBStructure;
use Mockery\MockInterface;

/**
 * Trait to mock the DBStructure connection status
 */
trait DBStructureMockTrait
{
	/**
	 * @var MockInterface The mocking interface of Friendica\Database\DBStructure
	 */
	private $dbStructure;

	/**
	 * Mocking DBStructure::update()
	 * @see DBStructure::update();
	 *
	 * @param array $args The arguments for the update call
	 * @param bool $return True, if the connect was successful, otherwise false
	 * @param null|int $times How often the method will get used
	 */
	public function mockUpdate($args = [], $return = true, $times = null)
	{
		if (!isset($this->dbStructure)) {
			$this->dbStructure = \Mockery::mock('alias:' . DBStructure::class);
		}

		$this->dbStructure
			->shouldReceive('update')
			->withArgs($args)
			->times($times)
			->andReturn($return);
	}

	/**
	 * Mocking DBStructure::existsTable()
	 *
	 * @param string $tableName The name of the table to check
	 * @param bool $return True, if the connect was successful, otherwise false
	 * @param null|int $times How often the method will get used
	 */
	public function mockExistsTable($tableName, $return = true, $times = null)
	{
		if (!isset($this->dbStructure)) {
			$this->dbStructure = \Mockery::mock('alias:' . DBStructure::class);
		}

		$this->dbStructure
			->shouldReceive('existsTable')
			->with($tableName)
			->times($times)
			->andReturn($return);
	}
}
