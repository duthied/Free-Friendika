<?php

namespace Friendica\Core\Config;

abstract class AbstractDbaConfigAdapter
{
	/** @var bool */
	protected $connected = true;

	public function isConnected()
	{
		return $this->connected;
	}
}
