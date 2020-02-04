<?php

namespace Friendica\Model\Notify;

/**
 * Enum for different types of the Notify
 */
class Type
{
	/** @var int Notification about a introduction */
	const INTRO  = 1;
	/** @var int Notification about a confirmed introduction */
	const CONFIRM = 2;
	/** @var int Notification about a post on your wall */
	const WALL = 4;
}
