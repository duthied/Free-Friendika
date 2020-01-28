<?php

namespace Friendica\Module\Api\Mastodon;

use Friendica\Core\System;
use Friendica\Module\BaseApi;
use Friendica\Object\Api\Mastodon\Instance as InstanceEntity;

/**
 * @see https://docs.joinmastodon.org/api/rest/instances/
 */
class Instance extends BaseApi
{
	/**
	 * @param array $parameters
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function rawContent(array $parameters = [])
	{
		System::jsonExit(InstanceEntity::get());
	}
}
