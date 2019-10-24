<?php

namespace Friendica\Protocol;

use Friendica\Protocol\Activity\ANamespace;

/**
 * Base class for the Activity constants and particular method
 */
final class Activity
{
	const LIKE         = ANamespace::ACTIVITY_SCHEMA . 'like';

	const DISLIKE      = ANamespace::DFRN            . '/dislike';
	const ATTEND       = ANamespace::ZOT             . '/activity/attendyes';
	const ATTENDNO     = ANamespace::ZOT             . '/activity/attendno';
	const ATTENDMAYBE  = ANamespace::ZOT             . '/activity/attendmaybe';
	const OBJ_HEART    = ANamespace::DFRN            . '/heart';

	const FRIEND       = ANamespace::ACTIVITY_SCHEMA . 'make-friend';
	const REQ_FRIEND   = ANamespace::ACTIVITY_SCHEMA . 'request-friend';
	const UNFRIEND     = ANamespace::ACTIVITY_SCHEMA . 'remove-friend';
	const FOLLOW       = ANamespace::ACTIVITY_SCHEMA . 'follow';
	const UNFOLLOW     = ANamespace::ACTIVITY_SCHEMA . 'stop-following';
	const JOIN         = ANamespace::ACTIVITY_SCHEMA . 'join';
	const POST         = ANamespace::ACTIVITY_SCHEMA . 'post';
	const UPDATE       = ANamespace::ACTIVITY_SCHEMA . 'update';
	const TAG          = ANamespace::ACTIVITY_SCHEMA . 'tag';
	const FAVORITE     = ANamespace::ACTIVITY_SCHEMA . 'favorite';
	const UNFAVORITE   = ANamespace::ACTIVITY_SCHEMA . 'unfavorite';
	const SHARE        = ANamespace::ACTIVITY_SCHEMA . 'share';
	const DELETE       = ANamespace::ACTIVITY_SCHEMA . 'delete';
	const ANNOUNCE     = ANamespace::ACTIVITY2       . 'Announce';

	const POKE         = ANamespace::ZOT             . '/activity/poke';

	const OBJ_BOOKMARK = ANamespace::ACTIVITY_SCHEMA . 'bookmark';
	const OBJ_COMMENT  = ANamespace::ACTIVITY_SCHEMA . 'comment';
	const OBJ_NOTE     = ANamespace::ACTIVITY_SCHEMA . 'note';
	const OBJ_PERSON   = ANamespace::ACTIVITY_SCHEMA . 'person';
	const OBJ_IMAGE    = ANamespace::ACTIVITY_SCHEMA . 'image';
	const OBJ_PHOTO    = ANamespace::ACTIVITY_SCHEMA . 'photo';
	const OBJ_VIDEO    = ANamespace::ACTIVITY_SCHEMA . 'video';
	const OBJ_P_PHOTO  = ANamespace::ACTIVITY_SCHEMA . 'profile-photo';
	const OBJ_ALBUM    = ANamespace::ACTIVITY_SCHEMA . 'photo-album';
	const OBJ_EVENT    = ANamespace::ACTIVITY_SCHEMA . 'event';
	const OBJ_GROUP    = ANamespace::ACTIVITY_SCHEMA . 'group';
	const OBJ_TAGTERM  = ANamespace::DFRN            . '/tagterm';
	const OBJ_PROFILE  = ANamespace::DFRN            . '/profile';

	const OBJ_QUESTION = 'http://activityschema.org/object/question';

	/**
	 * likes (etc.) can apply to other things besides posts. Check if they are post children,
	 * in which case we handle them specially
	 *
	 * Hidden activities, which doesn't need to be shown
	 */
	const HIDDEN_ACTIVITIES = [
		Activity::LIKE, Activity::DISLIKE,
		Activity::ATTEND, Activity::ATTENDNO, Activity::ATTENDMAYBE,
		Activity::FOLLOW,
		Activity::ANNOUNCE,
	];

	/**
	 * Checks if the given activity is a hidden activity
	 *
	 * @param string $activity The current activity
	 *
	 * @return bool True, if the activity is hidden
	 */
	public function isHidden(string $activity)
	{
		foreach (self::HIDDEN_ACTIVITIES as $hiddenActivity) {
			if ($this->match($activity, $hiddenActivity)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Compare activity uri. Knows about activity namespace.
	 *
	 * @param string $haystack
	 * @param string $needle
	 *
	 * @return boolean
	 */
	public function match(string $haystack, string $needle)
	{
		return (($haystack === $needle) ||
		        ((basename($needle) === $haystack) &&
		         strstr($needle, ANamespace::ACTIVITY_SCHEMA)));
	}
}
