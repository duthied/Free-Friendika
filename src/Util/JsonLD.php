<?php
/**
 * @file src/Util/JsonLD.php
 */
namespace Friendica\Util;

use Friendica\Core\Cache;
use digitalbazaar\jsonld as DBJsonLD;

/**
 * @brief This class contain methods to work with JsonLD data
 */
class JsonLD
{
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

	private static function objectify($element)
	{
	        if (is_array($element)) {
	                $keys = array_keys($element);
	                if (is_int(array_pop($keys))) {
	                        return array_map('objectify', $element);
	                } else {
	                        return (object)array_map('objectify', $element);
	                }
	        } else {
	                return $element;
	        }
	}

	public static function normalize($json)
	{
		jsonld_set_document_loader('Friendica\Util\JsonLD::documentLoader');

//		$jsonobj = array_map('Friendica\Util\JsonLD::objectify', $json);
		$jsonobj = json_decode(json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

		return jsonld_normalize($jsonobj, array('algorithm' => 'URDNA2015', 'format' => 'application/nquads'));
	}

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
