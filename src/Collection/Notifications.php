<?php

namespace Friendica\Collection;

use Friendica\BaseCollection;
use Friendica\Model;

class Notifications extends BaseCollection
{
	/**
	 * @return Model\Notification
	 */
	public function current()
	{
		return parent::current();
	}
}
