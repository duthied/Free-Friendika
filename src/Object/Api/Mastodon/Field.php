<?php

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseEntity;

/**
 * Class Field
 *
 * @see https://docs.joinmastodon.org/api/entities/#field
 */
class Field extends BaseEntity
{
	/** @var string */
	protected $name;
	/** @var string (HTML) */
	protected $value;
	/** @var string (Datetime)*/
	protected $verified_at;
}
