<?php

namespace Friendica\Core\Session;

use Friendica\Model\User\Cookie;

/**
 * Usable for backend processes (daemon/worker) and testing
 *
 * @todo after replacing the last direct $_SESSION call, use a internal array instead of the global variable
 */
final class Memory extends AbstractSession implements ISession
{
	public function __construct(Cookie $cookie)
	{
		parent::__construct($cookie);

		// Backward compatibility until all Session variables are replaced
		// with the Session class
		$_SESSION = [];
	}
}
