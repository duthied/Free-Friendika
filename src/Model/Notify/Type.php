<?php

namespace Friendica\Model\Notify;

/**
 * Enum for different types of the Notify
 */
class Type
{
	/** @var int Notification about a introduction */
	const INTRO = 1;
	/** @var int Notification about a confirmed introduction */
	const CONFIRM = 2;
	/** @var int Notification about a post on your wall */
	const WALL = 4;
	/** @var int Notification about a followup comment */
	const COMMENT = 8;
	/** @var int Notification about a private message */
	const MAIL = 16;
	/** @var int Notification about a friend suggestion */
	const SUGGEST = 32;
	/** @var int Unknown Notification
	 * @deprecated
	 */
	const PROFILE = 64;
	/** @var int Notification about being tagged in a post */
	const TAG_SELF = 128;
	/** @var int Notification about being tagged
	 * @deprecated
	 */
	const TAG_SHARE = 256;
	/** @var int Notification about getting poked/prodded/etc. */
	const POKE = 512;
	/** @var int Notification about either a contact had posted something directly or the contact is a mentioned forum */
	const SHARE = 1024;

	/** @var int Global System notifications */
	const SYSTEM = 32768;
}
