<?php

namespace Friendica\Module\Api\Mastodon\Instance;

use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Module\Base\Api;
use Friendica\Network\HTTPException;
use Friendica\Util\Network;

/**
 * Undocumented API endpoint that is implemented by both Mastodon and Pleroma
 */
class Peers extends Api
{
	/**
	 * @param array $parameters
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function rawContent(array $parameters = [])
	{
		$return = [];

		// We only select for Friendica and ActivityPub servers, since it is expected to only deliver AP compatible systems here.
		$instances = DBA::select('gserver', ['url'], ["`network` in (?, ?) AND `last_contact` >= `last_failure`", Protocol::DFRN, Protocol::ACTIVITYPUB]);
		while ($instance = DBA::fetch($instances)) {
			$urldata = parse_url($instance['url']);
			unset($urldata['scheme']);
			$return[] = ltrim(Network::unparseURL($urldata), '/');
		}
		DBA::close($instances);

		System::jsonExit($return);
	}
}
