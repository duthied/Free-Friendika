<?php

namespace Friendica\Test\Util;

use Friendica\Module\Api\ApiResponse;

class ApiResponseDouble extends ApiResponse
{
	/**
	 * The header list
	 *
	 * @var string[]
	 */
	protected static $header = [];

	/**
	 * The printed output
	 *
	 * @var string
	 */
	protected static $output = '';

	/**
	 * @return string[]
	 */
	public static function getHeader(): array
	{
		return static::$header;
	}

	/**
	 * @return string
	 */
	public static function getOutput(): string
	{
		return static::$output;
	}

	public static function reset()
	{
		self::$output = '';
		self::$header = [];
	}

	protected function setHeader(string $header)
	{
		static::$header[] = $header;
	}

	protected function printOutput(string $output)
	{
		static::$output .= $output;
	}
}
