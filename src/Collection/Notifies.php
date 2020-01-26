<?php

namespace Friendica\Collection;

use Friendica\BaseCollection;
use Friendica\Model;

class Notifies extends BaseCollection
{
	/**
	 * @return Model\Notify
	 */
	public function current()
	{
		return parent::current();
	}
}
