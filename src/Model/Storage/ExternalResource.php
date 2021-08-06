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

namespace Friendica\Model\Storage;

use BadMethodCallException;
use Friendica\Util\HTTPSignature;
use Friendica\Network\IHTTPRequest;

/**
 * External resource storage class
 *
 * This class is used to load external resources, like images.
 * Is not intended to be selectable by admins as default storage class.
 */
class ExternalResource implements IStorage
{
	const NAME = 'ExternalResource';

	/** @var IHTTPRequest */
	private $httpRequest;

	public function __construct(IHTTPRequest $httpRequest)
	{
		$this->httpRequest = $httpRequest;
	}

	/**
	 * @inheritDoc
	 */
	public function get(string $reference)
	{
		$data = json_decode($reference);
		if (empty($data->url)) {
			return "";
		}

		$parts = parse_url($data->url);
		if (empty($parts['scheme']) || empty($parts['host'])) {
			return "";
		}

		$fetchResult = HTTPSignature::fetchRaw($data->url, $data->uid, ['accept_content' => '']);
		if ($fetchResult->isSuccess()) {
			return $fetchResult->getBody();
		} else {
			return "";
		}
	}

	/**
	 * @inheritDoc
	 */
	public function put(string $data, string $reference = '')
	{
		throw new BadMethodCallException();
	}

	public function delete(string $reference)
	{
		throw new BadMethodCallException();
	}

	/**
	 * @inheritDoc
	 */
	public function getOptions()
	{
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function saveOptions(array $data)
	{
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function __toString()
	{
		return self::NAME;
	}

	/**
	 * @inheritDoc
	 */
	public static function getName()
	{
		return self::NAME;
	}
}
