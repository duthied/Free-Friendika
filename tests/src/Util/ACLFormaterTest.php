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

namespace Friendica\Test\src\Util;

use Error;
use Friendica\Model\Group;
use Friendica\Util\ACLFormatter;
use PHPUnit\Framework\TestCase;

/**
 * ACLFormater utility testing class
 */
class ACLFormaterTest extends TestCase
{
	public function assertAcl($text, array $assert = [])
	{
		$aclFormatter = new ACLFormatter();

		$acl = $aclFormatter->expand($text);

		$this->assertEquals($assert, $acl);

		$this->assertMergable($acl);
	}

	public function assertMergable(array $aclOne, array $aclTwo = [])
	{
		$this->assertTrue(is_array($aclOne));
		$this->assertTrue(is_array($aclTwo));

		$aclMerged = array_unique(array_merge($aclOne, $aclTwo));
		$this->assertTrue(is_array($aclMerged));

		return $aclMerged;
	}

	public function dataExpand()
	{
		return [
			'normal' => [
				'input' => '<1><2><3><' . Group::FOLLOWERS . '><' . Group::MUTUALS . '>',
				'assert' => ['1', '2', '3', Group::FOLLOWERS, Group::MUTUALS],
			],
			'nigNumber' => [
				'input' => '<1><' . PHP_INT_MAX . '><15>',
				'assert' => ['1', (string)PHP_INT_MAX, '15'],
			],
			'string' => [
				'input' => '<1><279012><tt>',
				'assert' => ['1', '279012'],
			],
			'space' => [
				'input' => '<1><279 012><32>',
				'assert' => ['1', '32'],
			],
			'empty' => [
				'input' => '',
				'assert' => [],
			],
			/// @todo should there be an exception?
			'noBrackets' => [
				'input' => 'According to documentation, that\'s invalid. ', //should be invalid
				'assert' => [],
			],
			/// @todo should there be an exception?
			'justOneBracket' => [
				'input' => '<Another invalid string', //should be invalid
				'assert' => [],
			],
			/// @todo should there be an exception?
			'justOneBracket2' => [
				'input' => 'Another invalid> string', //should be invalid
				'assert' => [],
			],
			/// @todo should there be an exception?
			'closeOnly' => [
				'input' => 'Another> invalid> string>', //should be invalid
				'assert' => [],
			],
			/// @todo should there be an exception?
			'openOnly' => [
				'input' => '<Another< invalid string<', //should be invalid
				'assert' => [],
			],
			/// @todo should there be an exception?
			'noMatching1' => [
				'input' => '<Another<> invalid <string>', //should be invalid
				'assert' => [],
			],
			'emptyMatch' => [
				'input' => '<1><><3>',
				'assert' => ['1', '3'],
			],
		];
	}

	/**
	 * @dataProvider dataExpand
	 */
	public function testExpand($input, array $assert)
	{
		$this->assertAcl($input, $assert);
	}

	/**
	 * Test nullable expand (=> no ACL set)
	 */
	public function testExpandNull()
	{
		$aclFormatter = new ACLFormatter();

		$allow_people = $aclFormatter->expand();
		$allow_groups = $aclFormatter->expand();

		$this->assertEmpty($aclFormatter->expand(null));
		$this->assertEmpty($aclFormatter->expand());

		$recipients   = array_unique(array_merge($allow_people, $allow_groups));
		$this->assertEmpty($recipients);
	}

	public function dataAclToString()
	{
		return [
			'empty'   => [
				'input'  => '',
				'assert' => '',
			],
			'string'  => [
				'input'  => '1,2,3,4',
				'assert' => '<1><2><3><4>',
			],
			'array'   => [
				'input'  => [1, 2, 3, 4],
				'assert' => '<1><2><3><4>',
			],
			'invalid' => [
				'input'  => [1, 'a', 3, 4],
				'assert' => '<1><3><4>',
			],
			'invalidString' => [
				'input'  => 'a,bsd23,4',
				'assert' => '<4>',
			],
			/** @see https://github.com/friendica/friendica/pull/7787 */
			'bug-7778-angle-brackets' => [
				'input' => ["<40195>"],
				'assert' => "<40195>",
			],
			Group::FOLLOWERS => [
				'input' => [Group::FOLLOWERS, 1],
				'assert' => '<' . Group::FOLLOWERS . '><1>',
			],
			Group::MUTUALS => [
				'input' => [Group::MUTUALS, 1],
				'assert' => '<' . Group::MUTUALS . '><1>',
			],
			'wrong-angle-brackets' => [
				'input' => ["<asd>","<123>"],
				'assert' => "<123>",
			],
		];
	}

	/**
	 * @dataProvider dataAclToString
	 */
	public function testAclToString($input, string $assert)
	{
		$aclFormatter = new ACLFormatter();

		$this->assertEquals($assert, $aclFormatter->toString($input));
	}
}
