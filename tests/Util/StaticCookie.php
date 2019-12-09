<?php

namespace Friendica\Test\Util;

use Friendica\Model\User\Cookie;

/**
 * Overrides the Cookie class so all cookie information will be saved to a static public variable
 */
class StaticCookie extends Cookie
{
	/** @var array static Cookie array mock */
	public static $_COOKIE = [];
	/** @var int The last expire time set */
	public static $_EXPIRE;

	protected function setCookie(string $name, string $value = null, int $expire = null, bool $secure = null)
	{
		self::$_COOKIE[$name] = $value;
		self::$_EXPIRE = $expire;
	}

	public static function clearStatic()
	{
		self::$_EXPIRE = null;
		self::$_COOKIE = [];
	}
}
