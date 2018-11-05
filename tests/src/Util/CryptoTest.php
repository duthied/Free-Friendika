<?php

// this is in the same namespace as Crypto for mocking 'rand' and 'random_init'
namespace Friendica\Util;

use PHPUnit\Framework\TestCase;

class CryptoTest extends TestCase
{
	/**
	 * Replaces function_exists results with given mocks
	 *
	 * @param array $functions a list from function names and their result
	 */
	private function setFunctions($functions)
	{
		global $phpMock;
		$phpMock['function_exists'] = function($function) use ($functions) {
			foreach ($functions as $name => $value) {
				if ($function == $name) {
					return $value;
				}
			}
			return '__phpunit_continue__';
		};
	}

	/**
	 * Replaces rand results with given mocks
	 *
	 */
	private function assertRand($min, $max)
	{
		global $phpMock;
		$phpMock['rand'] = function($mMin, $mMax) use ($min, $max) {
			$this->assertEquals($min, $mMin);
			$this->assertEquals($max, $mMax);
			return 1;
		};
	}

	/**
	 * Replaces rand results with given mocks
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

	public function testRandomDigitsRand()
	{
		$this->setFunctions(['random_int' => false]);
		$this->assertRand(0, 9);

		$test = Crypto::randomDigits(1);
		$this->assertEquals(1, strlen($test));
		$this->assertEquals(1, $test);

		$test = Crypto::randomDigits(8);
		$this->assertEquals(8, strlen($test));
		$this->assertEquals(11111111, $test);
	}


	public function testRandomDigitsRandomInt()
	{
		$this->setFunctions(['random_int' => true]);
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
 * A workaround to replace the PHP native function_exists() with a mocked function
 *
 * @param string $function_name the Name of the function
 *
 * @return bool true or false
 */
function function_exists($function_name)
{
	global $phpMock;
	if (isset($phpMock['function_exists'])) {
		$result = call_user_func_array($phpMock['function_exists'], func_get_args());
		if ($result !== '__phpunit_continue__') {
			return $result;
		}
	}
	return call_user_func_array('\function_exists', func_get_args());
}

/**
 * A workaround to replace the PHP native rand() (< 7.0) with a mocked function
 *
 * @return int
 */
function rand($min, $max)
{
	global $phpMock;
	if (isset($phpMock['rand'])) {
		$result = call_user_func_array($phpMock['rand'], func_get_args());
		return $result;
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
