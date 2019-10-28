<?php

namespace Friendica\Test\src\Util;

use Error;
use Friendica\Model\Group;
use Friendica\Util\ACLFormatter;
use PHPUnit\Framework\TestCase;

/**
 * @brief ACLFormater utility testing class
 */
class ACLFormaterTest extends TestCase
{
	/**
	 * test expand_acl, perfect input
	 */
	public function testExpandAclNormal()
	{
		$aclFormatter = new ACLFormatter();

		$text='<1><2><3><' . Group::FOLLOWERS . '><' . Group::MUTUALS . '>';
		$this->assertEquals(array('1', '2', '3', Group::FOLLOWERS, Group::MUTUALS), $aclFormatter->expand($text));
	}

	/**
	 * test with a big number
	 */
	public function testExpandAclBigNumber()
	{
		$aclFormatter = new ACLFormatter();

		$text='<1><' . PHP_INT_MAX . '><15>';
		$this->assertEquals(array('1', (string)PHP_INT_MAX, '15'), $aclFormatter->expand($text));
	}

	/**
	 * test with a string in it.
	 *
	 * @todo is this valid input? Otherwise: should there be an exception?
	 */
	public function testExpandAclString()
	{
		$aclFormatter = new ACLFormatter();

		$text="<1><279012><tt>";
		$this->assertEquals(array('1', '279012'), $aclFormatter->expand($text));
	}

	/**
	 * test with a ' ' in it.
	 *
	 * @todo is this valid input? Otherwise: should there be an exception?
	 */
	public function testExpandAclSpace()
	{
		$aclFormatter = new ACLFormatter();

		$text="<1><279 012><32>";
		$this->assertEquals(array('1', '32'), $aclFormatter->expand($text));
	}

	/**
	 * test empty input
	 */
	public function testExpandAclEmpty()
	{
		$aclFormatter = new ACLFormatter();

		$text="";
		$this->assertEquals(array(), $aclFormatter->expand($text));
	}

	/**
	 * test invalid input, no < at all
	 *
	 * @todo should there be an exception?
	 */
	public function testExpandAclNoBrackets()
	{
		$aclFormatter = new ACLFormatter();

		$text="According to documentation, that's invalid. "; //should be invalid
		$this->assertEquals(array(), $aclFormatter->expand($text));
	}

	/**
	 * test invalid input, just open <
	 *
	 * @todo should there be an exception?
	 */
	public function testExpandAclJustOneBracket1()
	{
		$aclFormatter = new ACLFormatter();

		$text="<Another invalid string"; //should be invalid
		$this->assertEquals(array(), $aclFormatter->expand($text));
	}

	/**
	 * test invalid input, just close >
	 *
	 * @todo should there be an exception?
	 */
	public function testExpandAclJustOneBracket2()
	{
		$aclFormatter = new ACLFormatter();

		$text="Another invalid> string"; //should be invalid
		$this->assertEquals(array(), $aclFormatter->expand($text));
	}

	/**
	 * test invalid input, just close >
	 *
	 * @todo should there be an exception?
	 */
	public function testExpandAclCloseOnly()
	{
		$aclFormatter = new ACLFormatter();

		$text="Another> invalid> string>"; //should be invalid
		$this->assertEquals(array(), $aclFormatter->expand($text));
	}

	/**
	 * test invalid input, just open <
	 *
	 * @todo should there be an exception?
	 */
	public function testExpandAclOpenOnly()
	{
		$aclFormatter = new ACLFormatter();

		$text="<Another< invalid string<"; //should be invalid
		$this->assertEquals(array(), $aclFormatter->expand($text));
	}

	/**
	 * test invalid input, open and close do not match
	 *
	 * @todo should there be an exception?
	 */
	public function testExpandAclNoMatching1()
	{
		$aclFormatter = new ACLFormatter();

		$text="<Another<> invalid <string>"; //should be invalid
		$this->assertEquals(array(), $aclFormatter->expand($text));
	}

	/**
	 * test invalid input, empty <>
	 *
	 * @todo should there be an exception? Or array(1, 3)
	 * (This should be array(1,3) - mike)
	 */
	public function testExpandAclEmptyMatch()
	{
		$aclFormatter = new ACLFormatter();

		$text="<1><><3>";
		$this->assertEquals(array('1', '3'), $aclFormatter->expand($text));
	}

	/**
	 * Test expected exception in case of wrong typehint
	 *
	 * @expectedException Error
	 */
	public function testExpandNull()
	{
		$aclFormatter = new ACLFormatter();

		$aclFormatter->expand(null);
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
