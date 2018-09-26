<?php
/**
 * @file src/Util/JsonLD.php
 */
namespace Friendica\Util;

use Friendica\Core\Cache;
use digitalbazaar\jsonld as DBJsonLD;
use Exception;

/**
 * @brief This class contain methods to work with JsonLD data
 */
class JsonLD
{
	/**
	 * @brief Loader for LD-JSON validation
	 *
	 * @param $url
	 *
	 * @return the loaded data
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
			logger('jsonld bomb detected at: ' . $url);
			exit();
		}

		$result = Cache::get('documentLoader:' . $url);
		if (!is_null($result)) {
			return $result;
		}

		$data = jsonld_default_document_loader($url);
		Cache::set('documentLoader:' . $url, $data, CACHE_DAY);
		return $data;
	}

	/**
	 * @brief Normalises a given JSON array
	 *
	 * @param array $json
	 *
	 * @return normalized JSON string
	 */
	public static function normalize($json)
	{
		jsonld_set_document_loader('Friendica\Util\JsonLD::documentLoader');

		$jsonobj = json_decode(json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

		try {
			$normalized = jsonld_normalize($jsonobj, array('algorithm' => 'URDNA2015', 'format' => 'application/nquads'));
		}
		catch (Exception $e) {
			logger('normalise error:' . print_r($e, true), LOGGER_DEBUG);
		}

		return $normalized;
	}

	/**
	 * @brief Compacts a given JSON array
	 *
	 * @param array $json
	 *
	 * @return comacted JSON array
	 */
	public static function compact($json)
	{
		jsonld_set_document_loader('Friendica\Util\JsonLD::documentLoader');

		$context = (object)['as' => 'https://www.w3.org/ns/activitystreams',
			'w3sec' => 'https://w3id.org/security',
			'ostatus' => (object)['@id' => 'http://ostatus.org#', '@type' => '@id'],
			'vcard' => (object)['@id' => 'http://www.w3.org/2006/vcard/ns#', '@type' => '@id'],
			'uuid' => (object)['@id' => 'http://schema.org/identifier', '@type' => '@id']];

		$jsonobj = json_decode(json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

		$compacted = jsonld_compact($jsonobj, $context);

		return json_decode(json_encode($compacted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), true);
	}

	/**
	 * @brief Fetches an element from a JSON array
	 *
	 * @param $array
	 * @param $element
	 * @param $key
	 * @param $type
	 * @param $type_value
	 *
	 * @return fetched element
	 */
	public static function fetchElement($array, $element, $key, $type = null, $type_value = null)
	{
		if (empty($array)) {
			return false;
		}

		if (empty($array[$element])) {
			return false;
		}

		if (is_string($array[$element])) {
			return $array[$element];
		}

		if (is_null($type_value)) {
			if (!empty($array[$element][$key])) {
				return $array[$element][$key];
			}

			if (!empty($array[$element][0][$key])) {
				return $array[$element][0][$key];
			}

			return false;
		}

		if (!empty($array[$element][$key]) && !empty($array[$element][$type]) && ($array[$element][$type] == $type_value)) {
			return $array[$element][$key];
		}

		/// @todo Add array search

		return false;
	}
}
