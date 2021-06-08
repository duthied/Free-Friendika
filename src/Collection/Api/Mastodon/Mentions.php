<?php

namespace Friendica\Collection\Api\Mastodon;

use Friendica\BaseCollection;
use Friendica\Object\Api\Mastodon\Mention;

class Mentions extends BaseCollection
{
	/**
	 * @return Mention
	 */
	public function current(): Mention
	{
		return parent::current();
	}
}
