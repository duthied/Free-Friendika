<?php

namespace Friendica\Model\Notify;

/**
 * Enum for different types of the Notify
 */
class Type
{
	/** @var int Introduction notifications */
	const INTRO  = 1;
	/** @var int Notification about a confirmed introduction */
	const CONFIRM = 2;
}
