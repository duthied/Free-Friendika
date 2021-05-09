<?php

namespace Friendica\Security\OAuth1;

class OAuthConsumer
{
	public $key;
	public $secret;
	public $callback_url;

	function __construct($key, $secret, $callback_url = null)
	{
		$this->key          = $key;
		$this->secret       = $secret;
		$this->callback_url = $callback_url;
	}

	function __toString()
	{
		return "OAuthConsumer[key=$this->key,secret=$this->secret]";
	}
}
