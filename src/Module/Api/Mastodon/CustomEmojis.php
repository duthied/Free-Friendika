<?php

namespace Friendica\Module\Api\Mastodon;

use Friendica\Content\Smilies;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/follow_requests
 */
class CustomEmojis extends BaseApi
{
	/**
	 * @param array $parameters
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @see https://docs.joinmastodon.org/methods/accounts/follow_requests#pending-follows
	 */
	public static function rawContent(array $parameters = [])
	{
		$emojis = DI::mstdnEmoji()->createCollectionFromSmilies(Smilies::getList());

		System::jsonExit($emojis->getArrayCopy());
	}
}
