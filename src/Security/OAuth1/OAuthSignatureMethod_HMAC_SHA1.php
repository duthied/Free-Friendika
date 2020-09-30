<?php

namespace Friendica\Security\OAuth1;

use Friendica\Security\OAuth1\OAuthRequest;
use Friendica\Security\OAuth1\OAuthUtil;

/**
 * The HMAC-SHA1 signature method uses the HMAC-SHA1 signature algorithm as defined in [RFC2104]
 * where the Signature Base String is the text and the key is the concatenated values (each first
 * encoded per Parameter Encoding) of the Consumer Secret and Token Secret, separated by an '&'
 * character (ASCII code 38) even if empty.
 *   - Chapter 9.2 ("HMAC-SHA1")
 */
class OAuthSignatureMethod_HMAC_SHA1 extends \Friendica\Security\OAuth1\OAuthSignatureMethod
{
	function get_name()
	{
		return "HMAC-SHA1";
	}

	/**
	 * @param OAuthRequest                             $request
	 * @param \Friendica\Security\OAuth1\OAuthConsumer $consumer
	 * @param \Friendica\Security\OAuth1\OAuthToken    $token
	 *
	 * @return string
	 */
	public function build_signature(OAuthRequest $request, \Friendica\Security\OAuth1\OAuthConsumer $consumer, \Friendica\Security\OAuth1\OAuthToken $token = null)
	{
		$base_string          = $request->get_signature_base_string();
		$request->base_string = $base_string;

		$key_parts = [
			$consumer->secret,
			($token) ? $token->secret : "",
		];

		$key_parts = OAuthUtil::urlencode_rfc3986($key_parts);
		$key       = implode('&', $key_parts);


		$r = base64_encode(hash_hmac('sha1', $base_string, $key, true));
		return $r;
	}
}
