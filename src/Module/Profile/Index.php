<?php

namespace Friendica\Module\Profile;

use Friendica\BaseModule;

/**
 * Profile index router
 *
 * The default profile path (https://domain.tld/profile/nickname) has to serve the profile data when queried as an
 * ActivityPub endpoint, but it should show statuses to web users.
 *
 * Both these view have dedicated sub-paths,
 * respectively https://domain.tld/profile/nickname/profile and https://domain.tld/profile/nickname/status
 */
class Index extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		Profile::rawContent($parameters);
	}

	public static function content(array $parameters = [])
	{
		return Status::content($parameters);
	}
}
