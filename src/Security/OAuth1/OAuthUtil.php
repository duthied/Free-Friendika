<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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

namespace Friendica\Security\OAuth1;

class OAuthUtil
{
	public static function urlencode_rfc3986($input)
	{
		if (is_array($input)) {
			return array_map(['Friendica\Security\OAuth1\OAuthUtil', 'urlencode_rfc3986'], $input);
		} else if (is_scalar($input)) {
			return str_replace(
				'+',
				' ',
				str_replace('%7E', '~', rawurlencode($input))
			);
		} else {
			return '';
		}
	}


	// This decode function isn't taking into consideration the above
	// modifications to the encoding process. However, this method doesn't
	// seem to be used anywhere so leaving it as is.
	public static function urldecode_rfc3986($string)
	{
		return urldecode($string);
	}

	// Utility function for turning the Authorization: header into
	// parameters, has to do some unescaping
	// Can filter out any non-oauth parameters if needed (default behaviour)
	public static function split_header($header, $only_allow_oauth_parameters = true)
	{
		$pattern = '/(([-_a-z]*)=("([^"]*)"|([^,]*)),?)/';
		$offset  = 0;
		$params  = [];
		while (preg_match($pattern, $header, $matches, PREG_OFFSET_CAPTURE, $offset) > 0) {
			$match          = $matches[0];
			$header_name    = $matches[2][0];
			$header_content = (isset($matches[5])) ? $matches[5][0] : $matches[4][0];
			if (preg_match('/^oauth_/', $header_name) || !$only_allow_oauth_parameters) {
				$params[$header_name] = OAuthUtil::urldecode_rfc3986($header_content);
			}
			$offset = $match[1] + strlen($match[0]);
		}

		if (isset($params['realm'])) {
			unset($params['realm']);
		}

		return $params;
	}

	// helper to try to sort out headers for people who aren't running apache
	public static function get_headers()
	{
		if (function_exists('apache_request_headers')) {
			// we need this to get the actual Authorization: header
			// because apache tends to tell us it doesn't exist
			$headers = apache_request_headers();

			// sanitize the output of apache_request_headers because
			// we always want the keys to be Cased-Like-This and arh()
			// returns the headers in the same case as they are in the
			// request
			$out = [];
			foreach ($headers as $key => $value) {
				$key       = str_replace(
					" ",
					"-",
					ucwords(strtolower(str_replace("-", " ", $key)))
				);
				$out[$key] = $value;
			}
		} else {
			// otherwise we don't have apache and are just going to have to hope
			// that $_SERVER actually contains what we need
			$out = [];
			if (isset($_SERVER['CONTENT_TYPE']))
				$out['Content-Type'] = $_SERVER['CONTENT_TYPE'];
			if (isset($_ENV['CONTENT_TYPE']))
				$out['Content-Type'] = $_ENV['CONTENT_TYPE'];

			foreach ($_SERVER as $key => $value) {
				if (substr($key, 0, 5) == "HTTP_") {
					// this is chaos, basically it is just there to capitalize the first
					// letter of every word that is not an initial HTTP and strip HTTP
					// code from przemek
					$key       = str_replace(
						" ",
						"-",
						ucwords(strtolower(str_replace("_", " ", substr($key, 5))))
					);
					$out[$key] = $value;
				}
			}
		}
		return $out;
	}

	// This function takes a input like a=b&a=c&d=e and returns the parsed
	// parameters like this
	// array('a' => array('b','c'), 'd' => 'e')
	public static function parse_parameters($input)
	{
		if (!isset($input) || !$input) return [];

		$pairs = explode('&', $input);

		$parsed_parameters = [];
		foreach ($pairs as $pair) {
			$split     = explode('=', $pair, 2);
			$parameter = OAuthUtil::urldecode_rfc3986($split[0]);
			$value     = isset($split[1]) ? OAuthUtil::urldecode_rfc3986($split[1]) : '';

			if (isset($parsed_parameters[$parameter])) {
				// We have already received parameter(s) with this name, so add to the list
				// of parameters with this name

				if (is_scalar($parsed_parameters[$parameter])) {
					// This is the first duplicate, so transform scalar (string) into an array
					// so we can add the duplicates
					$parsed_parameters[$parameter] = [$parsed_parameters[$parameter]];
				}

				$parsed_parameters[$parameter][] = $value;
			} else {
				$parsed_parameters[$parameter] = $value;
			}
		}
		return $parsed_parameters;
	}

	public static function build_http_query($params)
	{
		// Parameters are sorted by name, using lexicographical byte value ordering.
		// Ref: Spec: 9.1.1 (1)
		uksort($params, 'strcmp');
		return http_build_query($params, '', null, PHP_QUERY_RFC3986);
	}
}
