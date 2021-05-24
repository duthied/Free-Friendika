<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

use Friendica\Util\HTTPInputData;

/**
 * This class is used to enable testability for HTTPInputData
 * It overrides the two PHP input functionality with custom content
 */
class HTTPInputDataDouble extends HTTPInputData
{
	/** @var false|resource */
	protected static $injectedStream = false;
	/** @var false|string */
	protected static $injectedContent = false;
	/** @var false|string */
	protected static $injectedContentType = false;

	/**
	 * injects the PHP input stream for a test
	 *
	 * @param false|resource $stream
	 */
	public static function setPhpInputStream($stream)
	{
		self::$injectedStream = $stream;
	}

	/**
	 * injects the PHP input content for a test
	 *
	 * @param false|string $content
	 */
	public static function setPhpInputContent($content)
	{
		self::$injectedContent = $content;
	}

	/**
	 * injects the PHP input content type for a test
	 *
	 * @param false|string $contentType
	 */
	public static function setPhpInputContentType($contentType)
	{
		self::$injectedContentType = $contentType;
	}

	/** {@inheritDoc} */
	protected static function getPhpInputStream()
	{
		return static::$injectedStream;
	}

	/** {@inheritDoc} */
	protected static function getPhpInputContent()
	{
		return static::$injectedContent;
	}

	/** {@inheritDoc} */
	protected static function getContentType()
	{
		return static::$injectedContentType;
	}

	protected static function fetchFileData($stream, string $boundary, array $headers, string $filename)
	{
		$data = parent::fetchFileData($stream, $boundary, $headers, $filename);
		if (!empty($data['tmp_name'])) {
			unlink($data['tmp_name']);
			$data['tmp_name'] = $data['name'];
		}

		return $data;
	}

}
