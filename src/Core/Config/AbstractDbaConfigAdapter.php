<?php

namespace Friendica\Core\Config;

use Friendica\Database\DBA;

abstract class AbstractDbaConfigAdapter
{
	public function isConnected()
	{
		return DBA::connected();
	}
}
