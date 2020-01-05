<?php

namespace Friendica\Api\Entity\Mastodon;

use Friendica\Api\BaseEntity;

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
