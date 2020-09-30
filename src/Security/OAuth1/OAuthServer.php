<?php

namespace Friendica\Security\OAuth1;

use Friendica\Security\FKOAuthDataStore;
use OAuthConsumer;
use OAuthRequest;
use OAuthSignatureMethod;
use OAuthToken;

class OAuthServer
{
	protected $timestamp_threshold = 300; // in seconds, five minutes
	protected $version = '1.0';             // hi blaine
	/** @var \Friendica\Security\OAuth1\OAuthSignatureMethod[] */
	protected $signature_methods = [];

	/** @var FKOAuthDataStore */
	protected $data_store;

	function __construct(FKOAuthDataStore $data_store)
	{
		$this->data_store = $data_store;
	}

	public function add_signature_method(\Friendica\Security\OAuth1\OAuthSignatureMethod $signature_method)
	{
		$this->signature_methods[$signature_method->get_name()] =
			$signature_method;
	}

	// high level functions

	/**
	 * process a request_token request
	 * returns the request token on success
	 *
	 * @param \Friendica\Security\OAuth1\OAuthRequest $request
	 *
	 * @return \Friendica\Security\OAuth1\OAuthToken|null
	 * @throws OAuthException
	 */
	public function fetch_request_token(\Friendica\Security\OAuth1\OAuthRequest $request)
	{
		$this->get_version($request);

		$consumer = $this->get_consumer($request);

		// no token required for the initial token request
		$token = null;

		$this->check_signature($request, $consumer, $token);

		// Rev A change
		$callback  = $request->get_parameter('oauth_callback');
		$new_token = $this->data_store->new_request_token($consumer, $callback);

		return $new_token;
	}

	/**
	 * process an access_token request
	 * returns the access token on success
	 *
	 * @param \Friendica\Security\OAuth1\OAuthRequest $request
	 *
	 * @return object
	 * @throws OAuthException
	 */
	public function fetch_access_token(\Friendica\Security\OAuth1\OAuthRequest $request)
	{
		$this->get_version($request);

		$consumer = $this->get_consumer($request);

		// requires authorized request token
		$token = $this->get_token($request, $consumer, "request");

		$this->check_signature($request, $consumer, $token);

		// Rev A change
		$verifier  = $request->get_parameter('oauth_verifier');
		$new_token = $this->data_store->new_access_token($token, $consumer, $verifier);

		return $new_token;
	}

	/**
	 * verify an api call, checks all the parameters
	 *
	 * @param \Friendica\Security\OAuth1\OAuthRequest $request
	 *
	 * @return array
	 * @throws OAuthException
	 */
	public function verify_request(\Friendica\Security\OAuth1\OAuthRequest $request)
	{
		$this->get_version($request);
		$consumer = $this->get_consumer($request);
		$token    = $this->get_token($request, $consumer, "access");
		$this->check_signature($request, $consumer, $token);
		return [$consumer, $token];
	}

	// Internals from here

	/**
	 * version 1
	 *
	 * @param \Friendica\Security\OAuth1\OAuthRequest $request
	 *
	 * @return string
	 * @throws OAuthException
	 */
	private function get_version(\Friendica\Security\OAuth1\OAuthRequest $request)
	{
		$version = $request->get_parameter("oauth_version");
		if (!$version) {
			// Service Providers MUST assume the protocol version to be 1.0 if this parameter is not present.
			// Chapter 7.0 ("Accessing Protected Ressources")
			$version = '1.0';
		}
		if ($version !== $this->version) {
			throw new OAuthException("OAuth version '$version' not supported");
		}
		return $version;
	}

	/**
	 * figure out the signature with some defaults
	 *
	 * @param \Friendica\Security\OAuth1\OAuthRequest $request
	 *
	 * @return \Friendica\Security\OAuth1\OAuthSignatureMethod
	 * @throws OAuthException
	 */
	private function get_signature_method(\Friendica\Security\OAuth1\OAuthRequest $request)
	{
		$signature_method =
			@$request->get_parameter("oauth_signature_method");

		if (!$signature_method) {
			// According to chapter 7 ("Accessing Protected Ressources") the signature-method
			// parameter is required, and we can't just fallback to PLAINTEXT
			throw new OAuthException('No signature method parameter. This parameter is required');
		}

		if (!in_array(
			$signature_method,
			array_keys($this->signature_methods)
		)) {
			throw new OAuthException(
				"Signature method '$signature_method' not supported " .
				"try one of the following: " .
				implode(", ", array_keys($this->signature_methods))
			);
		}
		return $this->signature_methods[$signature_method];
	}

	/**
	 * try to find the consumer for the provided request's consumer key
	 *
	 * @param \Friendica\Security\OAuth1\OAuthRequest $request
	 *
	 * @return \Friendica\Security\OAuth1\OAuthConsumer
	 * @throws OAuthException
	 */
	private function get_consumer(\Friendica\Security\OAuth1\OAuthRequest $request)
	{
		$consumer_key = @$request->get_parameter("oauth_consumer_key");
		if (!$consumer_key) {
			throw new OAuthException("Invalid consumer key");
		}

		$consumer = $this->data_store->lookup_consumer($consumer_key);
		if (!$consumer) {
			throw new OAuthException("Invalid consumer");
		}

		return $consumer;
	}

	/**
	 * try to find the token for the provided request's token key
	 *
	 * @param \Friendica\Security\OAuth1\OAuthRequest $request
	 * @param                                         $consumer
	 * @param string                                  $token_type
	 *
	 * @return \Friendica\Security\OAuth1\OAuthToken|null
	 * @throws OAuthException
	 */
	private function get_token(\Friendica\Security\OAuth1\OAuthRequest &$request, $consumer, $token_type = "access")
	{
		$token_field = @$request->get_parameter('oauth_token');
		$token       = $this->data_store->lookup_token(
			$consumer,
			$token_type,
			$token_field
		);
		if (!$token) {
			throw new OAuthException("Invalid $token_type token: $token_field");
		}
		return $token;
	}

	/**
	 * all-in-one function to check the signature on a request
	 * should guess the signature method appropriately
	 *
	 * @param \Friendica\Security\OAuth1\OAuthRequest    $request
	 * @param \Friendica\Security\OAuth1\OAuthConsumer   $consumer
	 * @param \Friendica\Security\OAuth1\OAuthToken|null $token
	 *
	 * @throws OAuthException
	 */
	private function check_signature(\Friendica\Security\OAuth1\OAuthRequest $request, \Friendica\Security\OAuth1\OAuthConsumer $consumer, \Friendica\Security\OAuth1\OAuthToken $token = null)
	{
		// this should probably be in a different method
		$timestamp = @$request->get_parameter('oauth_timestamp');
		$nonce     = @$request->get_parameter('oauth_nonce');

		$this->check_timestamp($timestamp);
		$this->check_nonce($consumer, $token, $nonce, $timestamp);

		$signature_method = $this->get_signature_method($request);

		$signature = $request->get_parameter('oauth_signature');
		$valid_sig = $signature_method->check_signature(
			$request,
			$consumer,
			$signature,
			$token
		);

		if (!$valid_sig) {
			throw new OAuthException("Invalid signature");
		}
	}

	/**
	 * check that the timestamp is new enough
	 *
	 * @param int $timestamp
	 *
	 * @throws OAuthException
	 */
	private function check_timestamp($timestamp)
	{
		if (!$timestamp)
			throw new OAuthException(
				'Missing timestamp parameter. The parameter is required'
			);

		// verify that timestamp is recentish
		$now = time();
		if (abs($now - $timestamp) > $this->timestamp_threshold) {
			throw new OAuthException(
				"Expired timestamp, yours $timestamp, ours $now"
			);
		}
	}

	/**
	 * check that the nonce is not repeated
	 *
	 * @param \Friendica\Security\OAuth1\OAuthConsumer $consumer
	 * @param \Friendica\Security\OAuth1\OAuthToken    $token
	 * @param string                                   $nonce
	 * @param int                                      $timestamp
	 *
	 * @throws OAuthException
	 */
	private function check_nonce(\Friendica\Security\OAuth1\OAuthConsumer $consumer, \Friendica\Security\OAuth1\OAuthToken $token, $nonce, int $timestamp)
	{
		if (!$nonce)
			throw new OAuthException(
				'Missing nonce parameter. The parameter is required'
			);

		// verify that the nonce is uniqueish
		$found = $this->data_store->lookup_nonce(
			$consumer,
			$token,
			$nonce,
			$timestamp
		);
		if ($found) {
			throw new OAuthException("Nonce already used: $nonce");
		}
	}
}
