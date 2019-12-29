<?php

namespace Friendica\Core\Session;

use Friendica\App;
use Friendica\Model\User\Cookie;
use SessionHandlerInterface;

/**
 * The native Session class which uses the PHP internal Session functions
 */
final class Native extends AbstractSession implements ISession
{
	public function __construct(App\BaseURL $baseURL, SessionHandlerInterface $handler = null)
	{
		ini_set('session.gc_probability', 50);
		ini_set('session.use_only_cookies', 1);
		ini_set('session.cookie_httponly', (int)Cookie::HTTPONLY);

		if ($baseURL->getSSLPolicy() == App\BaseURL::SSL_POLICY_FULL) {
			ini_set('session.cookie_secure', 1);
		}

		if (isset($handler)) {
			session_set_save_handler($handler);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function start()
	{
		session_start();
		return $this;
	}
}
