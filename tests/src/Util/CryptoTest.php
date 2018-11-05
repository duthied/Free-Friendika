<?php

// this is in the same namespace as Crypto for mocking 'rand' and 'random_init'
namespace Friendica\Util;

use PHPUnit\Framework\TestCase;

class CryptoTest extends TestCase
{
	/**
	 * Replaces random_int results with given mocks
	 *
	 */
	private function assertRandomInt($min, $max)
	{
		global $phpMock;
		$phpMock['random_int'] = function($mMin, $mMax) use ($min, $max) {
			$this->assertEquals($min, $mMin);
			$this->assertEquals($max, $mMax);
			return 1;
		};
	}

	public function testRandomDigitsRandomInt()
	{
		$this->assertRandomInt(0, 9);

		$test = Crypto::randomDigits(1);
		$this->assertEquals(1, strlen($test));
		$this->assertEquals(1, $test);

		$test = Crypto::randomDigits(8);
		$this->assertEquals(8, strlen($test));
		$this->assertEquals(11111111, $test);
	}
}

/**
 * A workaround to replace the PHP native random_int() (>= 7.0) with a mocked function
 *
 * @return int
 */
function random_int($min, $max)
{
	global $phpMock;
	if (isset($phpMock['random_int'])) {
		$result = call_user_func_array($phpMock['random_int'], func_get_args());
		return $result;
	}
}
