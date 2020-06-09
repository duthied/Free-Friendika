<?php

namespace Friendica\Test\src\Module\Api\Twitter;

/**
 * Class ContactEndpointMock
 *
 * Exposes protected methods for test in the inherited class
 *
 * @method static int   getUid(int $contact_id = null, string $screen_name = null)
 * @method static array list($rel, int $uid, int $cursor = -1, int $count = self::DEFAULT_COUNT, bool $skip_status = false, bool $include_user_entities = true)
 * @method static array ids($rel, int $uid, int $cursor = -1, int $count = self::DEFAULT_COUNT, bool $stringify_ids = false)
 *
 * @package Friendica\Test\Mock\Module\Api\Twitter
 */
class ContactEndpointMock extends \Friendica\Module\Api\Twitter\ContactEndpoint
{
	public static function __callStatic($name, $arguments)
	{
		return self::$name(...$arguments);
	}
}
