<?php
/**
 * @file tests/src/Util/StringsTest.php
 */
namespace Friendica\Test\src\Util;

use Friendica\Util\Strings;
use PHPUnit\Framework\TestCase;

/**
 * @brief Strings utility test class
 */
class StringsTest extends TestCase
{
	/**
	 * randomnames should be random, even length
	 */
	public function testRandomEven()
	{
		$randomname1 = Strings::getRandomName(10);
		$randomname2 = Strings::getRandomName(10);

		$this->assertNotEquals($randomname1, $randomname2);
	}

	/**
	 * randomnames should be random, odd length
	 */
	public function testRandomOdd()
	{
		$randomname1 = Strings::getRandomName(9);
		$randomname2 = Strings::getRandomName(9);

		$this->assertNotEquals($randomname1, $randomname2);
	}

	/**
	 * try to fail ramdonnames
	 */
	public function testRandomNameNoLength()
	{
		$randomname1 = Strings::getRandomName(0);
		$this->assertEquals(0, strlen($randomname1));
	}

	/**
	 * try to fail it with invalid input
	 *
	 * @todo What's corect behaviour here? An exception?
	 */
	public function testRandomNameNegativeLength()
	{
		$randomname1 = Strings::getRandomName(-23);
		$this->assertEquals(0, strlen($randomname1));
	}

	/**
	 * test with a length, that may be too short
	 */
	public function testRandomNameLength1()
	{
		$randomname1 = Strings::getRandomName(1);
		$this->assertEquals(1, strlen($randomname1));

		$randomname2 = Strings::getRandomName(1);
		$this->assertEquals(1, strlen($randomname2));
	}

	/**
	 * test, that tags are escaped
	 */
	public function testEscapeHtml()
	{
		$invalidstring='<submit type="button" onclick="alert(\'failed!\');" />';

		$validstring = Strings::escapeTags($invalidstring);
		$escapedString = Strings::escapeHtml($invalidstring);

		$this->assertEquals('[submit type="button" onclick="alert(\'failed!\');" /]', $validstring);
		$this->assertEquals(
			"&lt;submit type=&quot;button&quot; onclick=&quot;alert('failed!');&quot; /&gt;",
			$escapedString
		);
	}

	public function dataIsHex()
	{
		return [
			'validHex' => [
				'input' => '90913473615bf00c122ac78338492980',
				'valid' => true,
			],
			'invalidHex' => [
				'input' => '90913473615bf00c122ac7833849293',
				'valid' => false,
			],
			'emptyHex' => [
				'input' => '',
				'valid' => false,
			],
			'nullHex' => [
				'input' => null,
				'valid' => false,
			],
		];
	}

	/**
	 * Tests if the string is a valid hexadecimal value
	 *
	 * @param string $input
	 * @param bool $valid
	 *
	 * @dataProvider dataIsHex
	 */
	public function testIsHex($input, $valid)
	{
		$this->assertEquals($valid, Strings::isHex($input));
	}
}
