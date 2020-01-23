<?php

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseEntity;

/**
 * Class Field
 *
 * @see https://docs.joinmastodon.org/entities/field/
 */
class Field extends BaseEntity
{
	/** @var string */
	protected $name;
	/** @var string (HTML) */
	protected $value;
	/** @var string (Datetime)*/
	protected $verified_at;

	public function __construct(string $name, string $value)
	{
		$this->name = $name;
		$this->value = $value;
		// Link verification unsupported
		$this->verified_at = null;
	}
}
