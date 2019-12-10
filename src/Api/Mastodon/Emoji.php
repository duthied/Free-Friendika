<?php

namespace Friendica\Api\Mastodon;

/**
 * Class Emoji
 *
 * @see https://docs.joinmastodon.org/api/entities/#emoji
 */
class Emoji
{
	/** @var string */
	var $shortcode;
	/** @var string (URL)*/
	var $static_url;
	/** @var string (URL)*/
	var $url;
	/** @var bool */
	var $visible_in_picker;
}
