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

namespace Friendica\Test\src\Model;

use Friendica\Model\User;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\DBAMockTrait;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class UserTest extends MockedTest
{
	use DBAMockTrait;

	private $parent;
	private $child;
	private $manage;

	protected function setUp()
	{
		parent::setUp();

		$this->parent = [
			'uid'        => 1,
			'username'   => 'maxmuster',
			'nickname'   => 'Max Muster'
		];

		$this->child = [
			'uid'        => 2,
			'username'   => 'johndoe',
			'nickname'   => 'John Doe'
		];

		$this->manage = [
			'uid'        => 3,
			'username'   => 'janesmith',
			'nickname'   => 'Jane Smith'
		];
	}

	public function testIdentitiesEmpty()
	{
		$this->mockSelectFirst('user',
			['uid', 'nickname', 'username', 'parent-uid'],
			['uid' => $this->parent['uid']],
			$this->parent,
			1
		);
		$this->mockIsResult($this->parent, false, 1);

		$record = User::identities($this->parent['uid']);

		$this->assertEquals([], $record);
	}

	public function testIdentitiesAsParent()
	{
		$parentSelect = $this->parent;
		$parentSelect['parent-uid'] = 0;

		// Select the user itself (=parent)
		$this->mockSelectFirst('user',
			['uid', 'nickname', 'username', 'parent-uid'],
			['uid' => $this->parent['uid']],
			$parentSelect,
			1
		);
		$this->mockIsResult($parentSelect, true, 1);

		// Select one child
		$this->mockSelect('user',
			['uid', 'username', 'nickname'],
			[
				'parent-uid' => $this->parent['uid'],
				'account_removed' => false
			],
			'objectReturn',
			1
		);
		$this->mockIsResult('objectReturn', true, 1);
		$this->mockToArray('objectReturn', [ $this->child ], 1);

		// Select the manage
		$this->mockP(null, 'objectTwo', 1);
		$this->mockIsResult('objectTwo', true, 1);
		$this->mockToArray('objectTwo', [ $this->manage ], 1);

		$record = User::identities($this->parent['uid']);

		$this->assertEquals([
			$this->parent,
			$this->child,
			$this->manage
		], $record);
	}

	public function testIdentitiesAsChild()
	{
		$childSelect = $this->child;
		$childSelect['parent-uid'] = $this->parent['uid'];

		// Select the user itself (=child)
		$this->mockSelectFirst('user',
			['uid', 'nickname', 'username', 'parent-uid'],
			['uid' => $this->child['uid']],
			$childSelect,
			1
		);
		$this->mockIsResult($childSelect, true, 1);

		// Select the parent
		$this->mockSelect('user',
			['uid', 'username', 'nickname'],
			[
				'uid' => $this->parent['uid'],
				'account_removed' => false
			],
			'objectReturn',
			1
		);
		$this->mockIsResult('objectReturn', true, 1);
		$this->mockToArray('objectReturn', [ $this->parent ], 1);

		// Select the childs (user & manage)
		$this->mockSelect('user',
			['uid', 'username', 'nickname'],
			[
				'parent-uid' => $this->parent['uid'],
				'account_removed' => false
			],
			'objectReturn',
			1
		);
		$this->mockIsResult('objectReturn', true, 1);
		$this->mockToArray('objectReturn', [ $this->child, $this->manage ], 1);

		// Select the manage
		$this->mockP(null, 'objectTwo', 1);
		$this->mockIsResult('objectTwo', false, 1);

		$record = User::identities($this->child['uid']);

		$this->assertEquals([
			$this->parent,
			$this->child,
			$this->manage
		], $record);
	}
}
