<?php

namespace Friendica\Core\Session;

use Friendica\Core\Config\Configuration;
use Friendica\Model\User\Cookie;

/**
 * Usable for backend processes (daemon/worker) and testing
 *
 * @todo after replacing the last direct $_SESSION call, use a internal array instead of the global variable
 */
final class Memory extends Native
{
	public function __construct(Configuration $config, Cookie $cookie)
	{
		$this->cookie = $cookie;
	}

	public function start()
	{
		// Backward compatibility until all Session variables are replaced
		// with the Session class
		$_SESSION = [];
		$this->clear();
		return $this;
	}
}
