<?php
/**
 * TextTest class.
 */

namespace Friendica\Test;

use Friendica\Model\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests for text functions.
 */
class TextTest extends TestCase
{
	/**
	 * test hex2bin and reverse
	 */
	public function testHex2Bin()
	{
		$this->assertEquals(-3, hex2bin(bin2hex(-3)));
		$this->assertEquals(0, hex2bin(bin2hex(0)));
		$this->assertEquals(12, hex2bin(bin2hex(12)));
		$this->assertEquals(PHP_INT_MAX, hex2bin(bin2hex(PHP_INT_MAX)));
	}
}
