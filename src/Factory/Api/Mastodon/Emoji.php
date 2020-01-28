<?php

namespace Friendica\Factory\Api\Mastodon;

use Friendica\App\BaseURL;
use Friendica\BaseFactory;
use Friendica\Collection\Api\Mastodon\Emojis;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Network\HTTPException;
use Psr\Log\LoggerInterface;

class Emoji extends BaseFactory
{
	public function create(string $shortcode, string $url)
	{
		return new \Friendica\Object\Api\Mastodon\Emoji($shortcode, $url);
	}

	/**
	 * @param array $smilies
	 * @return Emojis
	 */
	public function createCollectionFromSmilies(array $smilies)
	{
		$prototype = null;

		$emojis = [];

		foreach ($smilies['texts'] as $key => $shortcode) {
			if (preg_match('/src="(.+?)"/', $smilies['icons'][$key], $matches)) {
				$url = $matches[1];

				if ($prototype === null) {
					$prototype = $this->create($shortcode, $url);
					$emojis[] = $prototype;
				} else {
					$emojis[] = \Friendica\Object\Api\Mastodon\Emoji::createFromPrototype($prototype, $shortcode, $url);
				}
			};
		}

		return new Emojis($emojis);
	}
}
