<?php

namespace Friendica\Collection\Api\Mastodon;

use Friendica\Api\Entity\Mastodon\Field;
use Friendica\BaseCollection;

class Fields extends BaseCollection
{
	/**
	 * @param Field[]  $entities
	 * @param int|null $totalCount
	 */
	public function __construct(array $entities = [], int $totalCount = null)
	{
		parent::__construct($entities);

		$this->totalCount = $totalCount ?? count($entities);
	}
}
