<?php

namespace Friendica\Core\Session;

use Friendica\Core\Config\Configuration;
use Friendica\App;
use Friendica\Model\User\Cookie;

/**
 * The native Session class which uses the PHP internal Session function
 */
class NativeSession implements ISession
{
	/** @var Cookie */
	protected $cookie;

	public function __construct(Configuration $config, Cookie $cookie)
	{
		ini_set('session.gc_probability', 50);
		ini_set('session.use_only_cookies', 1);
		ini_set('session.cookie_httponly', 1);

		if ($config->get('system', 'ssl_policy') == App\BaseURL::SSL_POLICY_FULL) {
			ini_set('session.cookie_secure', 1);
		}

		$this->cookie = $cookie;
	}

	/**
	 * {@inheritDoc}
	 */
	public function start()
	{
		session_start();
		return $this;
	}

	/**
	 * {@inheritDoc}}
	 */
	public function exists(string $name)
	{
		return isset($_SESSION[$name]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get(string $name, $defaults = null)
	{
		return $_SESSION[$name] ?? $defaults;
	}

	/**
	 * {@inheritDoc}
	 */
	public function set(string $name, $value)
	{
		$_SESSION[$name] = $value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setMultiple(array $values)
	{
		$_SESSION = $values + $_SESSION;
	}

	/**
	 * {@inheritDoc}
	 */
	public function remove(string $name)
	{
		unset($_SESSION[$name]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function clear()
	{
		$_SESSION = [];
	}

	/**
	 * @brief Kills the "Friendica" cookie and all session data
	 */
	public function delete()
	{
		$this->cookie->clear();
		$_SESSION = [];
	}
}
