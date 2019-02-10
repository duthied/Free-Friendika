<?php

namespace Friendica\Core\Config\Adapter;

use Friendica\Database\DBA;

abstract class AbstractDbaConfigAdapter
{
	/** @var bool */
	protected $connected = true;

	public function __construct()
	{
		$this->connected = DBA::connected();
	}

	public function isConnected()
	{
		return $this->connected;
	}
}
