<?php

namespace Friendica\Core\Config;

use Friendica\Database\DBA;

abstract class AbstractDbaConfigAdapter
{
	/** @var bool */
	protected $connected = true;

	public function isConnected()
	{
		return $this->connected;
	}
}
