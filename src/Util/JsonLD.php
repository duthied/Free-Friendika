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

namespace Friendica\Util;

use Friendica\Core\Cache\Duration;
use Friendica\Core\Logger;
use Exception;
use Friendica\DI;

/**
 * This class contain methods to work with JsonLD data
 */
class JsonLD
{
	/**
	 * Loader for LD-JSON validation
	 *
	 * @param $url
	 *
	 * @return mixed the loaded data
	 * @throws \JsonLdException
	 */
	public static function documentLoader($url)
	{
		$recursion = 0;

		$x = debug_backtrace();
		if ($x) {
			foreach ($x as $n) {
				if ($n['function'] === __FUNCTION__)  {
					$recursion ++;
				}
			}
		}

		if ($recursion > 5) {
			Logger::error('jsonld bomb detected at: ' . $url);
			exit();
		}

		$result = DI::cache()->get('documentLoader:' . $url);
		if (!is_null($result)) {
			return $result;
		}

		$data = jsonld_default_document_loader($url);
		DI::cache()->set('documentLoader:' . $url, $data, Duration::DAY);
		return $data;
	}

	/**
	 * Normalises a given JSON array
	 *
	 * @param array $json
	 *
	 * @return mixed|bool normalized JSON string
	 * @throws Exception
	 */
	public static function normalize($json)
	{
		jsonld_set_document_loader('Friendica\Util\JsonLD::documentLoader');

		$jsonobj = json_decode(json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

		try {
			$normalized = jsonld_normalize($jsonobj, array('algorithm' => 'URDNA2015', 'format' => 'application/nquads'));
		}
		catch (Exception $e) {
			$normalized = false;
			$messages = [];
			$currentException = $e;
			do {
				$messages[] = $currentException->getMessage();
			} while($currentException = $currentException->getPrevious());

			Logger::warning('JsonLD normalize error');
			Logger::notice('JsonLD normalize error', ['messages' => $messages]);
			Logger::info('JsonLD normalize error', ['trace' => $e->getTraceAsString()]);
			Logger::debug('JsonLD normalize error', ['jsonobj' => $jsonobj]);
		}

		return $normalized;
	}

	/**
	 * Compacts a given JSON array
	 *
	 * @param array $json
	 *
	 * @return array Compacted JSON array
	 * @throws Exception
	 */
	public static function compact($json)
	{
		jsonld_set_document_loader('Friendica\Util\JsonLD::documentLoader');

		$context = (object)['as' => 'https://www.w3.org/ns/activitystreams#',
			'w3id' => 'https://w3id.org/security#',
			'ldp' => (object)['@id' => 'http://www.w3.org/ns/ldp#', '@type' => '@id'],
			'vcard' => (object)['@id' => 'http://www.w3.org/2006/vcard/ns#', '@type' => '@id'],
			'dfrn' => (object)['@id' => 'http://purl.org/macgirvin/dfrn/1.0/', '@type' => '@id'],
			'diaspora' => (object)['@id' => 'https://diasporafoundation.org/ns/', '@type' => '@id'],
			'ostatus' => (object)['@id' => 'http://ostatus.org#', '@type' => '@id'],
			'dc' => (object)['@id' => 'http://purl.org/dc/terms/', '@type' => '@id'],
			'toot' => (object)['@id' => 'http://joinmastodon.org/ns#', '@type' => '@id'],
			'litepub' => (object)['@id' => 'http://litepub.social/ns#', '@type' => '@id'],
			'sc' => (object)['@id' => 'http://schema.org#', '@type' => '@id'],
			'pt' => (object)['@id' => 'https://joinpeertube.org/ns#', '@type' => '@id']];

		// Preparation for adding possibly missing content to the context
		if (!empty($json['@context']) && is_string($json['@context'])) {
			$json['@context'] = [$json['@context']];
		}

		// Workaround for servers with missing context
		// See issue https://github.com/nextcloud/social/issues/330
		if (!empty($json['@context']) && is_array($json['@context'])) {
			$json['@context'][] = 'https://w3id.org/security/v1';
		}

		// Trying to avoid memory problems with large content fields
		if (!empty($json['object']['source']['content'])) {
			$content = $json['object']['source']['content'];
			$json['object']['source']['content'] = '';
		}

		$jsonobj = json_decode(json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

		try {
			$compacted = jsonld_compact($jsonobj, $context);
		}
		catch (Exception $e) {
			$compacted = false;
			Logger::error('compacting error');
			// Sooner or later we should log some details as well - but currently this leads to memory issues
			// Logger::log('compacting error:' . substr(print_r($e, true), 0, 10000), Logger::DEBUG);
		}

		$json = json_decode(json_encode($compacted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), true);

		if (isset($json['as:object']['as:source']['as:content']) && !empty($content)) {
			$json['as:object']['as:source']['as:content'] = $content;
		}

		return $json;
	}

	/**
	 * Fetches an element array from a JSON array
	 *
	 * @param $array
	 * @param $element
	 * @param $key
	 *
	 * @return array fetched element
	 */
	public static function fetchElementArray($array, $element, $key = '@id')
	{
		if (empty($array)) {
			return null;
		}

		if (!isset($array[$element])) {
			return null;
		}

		// If it isn't an array yet, make it to one
		if (!is_int(key($array[$element]))) {
			$array[$element] = [$array[$element]];
		}

		$elements = [];

		foreach ($array[$element] as $entry) {
			if (!is_array($entry)) {
				$elements[] = $entry;
			} elseif (isset($entry[$key])) {
				$elements[] = $entry[$key];
			} elseif (!empty($entry) || !is_array($entry)) {
				$elements[] = $entry;
			}
		}

		return $elements;
	}

	/**
	 * Fetches an element from a JSON array
	 *
	 * @param $array
	 * @param $element
	 * @param $key
	 * @param $type
	 * @param $type_value
	 *
	 * @return string fetched element
	 */
	public static function fetchElement($array, $element, $key = '@id', $type = null, $type_value = null)
	{
		if (empty($array)) {
			return null;
		}

		if (!isset($array[$element])) {
			return null;
		}

		if (!is_array($array[$element])) {
			return $array[$element];
		}

		if (is_null($type) || is_null($type_value)) {
			$element_array = self::fetchElementArray($array, $element, $key);
			if (is_null($element_array)) {
				return null;
			}

			return array_shift($element_array);
		}

		$element_array = self::fetchElementArray($array, $element);
		if (is_null($element_array)) {
			return null;
		}

		foreach ($element_array as $entry) {
			if (isset($entry[$key]) && isset($entry[$type]) && ($entry[$type] == $type_value)) {
				return $entry[$key];
			}
		}

		return null;
	}
}
