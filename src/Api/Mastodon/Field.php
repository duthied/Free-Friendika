<?php

namespace Friendica\Api\Mastodon;

/**
 * Class Field
 *
 * @see https://docs.joinmastodon.org/api/entities/#field
 */
class Field
{
	/** @var string */
	var $name;
	/** @var string (HTML) */
	var $value;
	/** @var string (Datetime)*/
	var $verified_at;
}
