<?php

namespace Friendica\Security\OAuth1;

class OAuthDataStore
{
	function lookup_consumer($consumer_key)
	{
		// implement me
	}

	function lookup_token(OAuthConsumer $consumer, $token_type, $token_id)
	{
		// implement me
	}

	function lookup_nonce(OAuthConsumer $consumer, OAuthToken $token, $nonce, int $timestamp)
	{
		// implement me
	}

	function new_request_token(OAuthConsumer $consumer, $callback = null)
	{
		// return a new token attached to this consumer
	}

	function new_access_token(OAuthToken $token, OAuthConsumer $consumer, $verifier = null)
	{
		// return a new access token attached to this consumer
		// for the user associated with this token if the request token
		// is authorized
		// should also invalidate the request token
	}
}
