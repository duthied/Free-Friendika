<?php

namespace Friendica\Api\Entity\Mastodon;

use Friendica\Api\BaseEntity;

/**
 * Class Emoji
 *
 * @see https://docs.joinmastodon.org/api/entities/#emoji
 */
class Emoji extends BaseEntity
{
	/** @var string */
	protected $shortcode;
	/** @var string (URL)*/
	protected $static_url;
	/** @var string (URL)*/
	protected $url;
	/** @var bool */
	protected $visible_in_picker;
}
