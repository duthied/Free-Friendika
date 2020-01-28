<?php

namespace Friendica\Collection\Api;

use Friendica\BaseCollection;
use Friendica\Object\Api\Friendica\Notification;

class Notifications extends BaseCollection
{
	/**
	 * @return Notification
	 */
	public function current()
	{
		return parent::current();
	}
}
