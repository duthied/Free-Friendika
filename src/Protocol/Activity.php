<?php

namespace Friendica\Protocol;

use Friendica\Protocol\Activity\Namespaces;

/**
 * Base class for the Activity constants and match method
 */
final class Activity
{
	const LIKE         = Namespaces::ACTIVITY_SCHEMA . 'like';

	const DISLIKE      = Namespaces::DFRN            . '/dislike';
	const ATTEND       = Namespaces::ZOT             . '/activity/attendyes';
	const ATTENDNO     = Namespaces::ZOT             . '/activity/attendno';
	const ATTENDMAYBE  = Namespaces::ZOT             . '/activity/attendmaybe';
	const OBJ_HEART    = Namespaces::DFRN            . '/heart';

	const FRIEND       = Namespaces::ACTIVITY_SCHEMA . 'make-friend';
	const REQ_FRIEND   = Namespaces::ACTIVITY_SCHEMA . 'request-friend';
	const UNFRIEND     = Namespaces::ACTIVITY_SCHEMA . 'remove-friend';
	const FOLLOW       = Namespaces::ACTIVITY_SCHEMA . 'follow';
	const UNFOLLOW     = Namespaces::ACTIVITY_SCHEMA . 'stop-following';
	const JOIN         = Namespaces::ACTIVITY_SCHEMA . 'join';
	const POST         = Namespaces::ACTIVITY_SCHEMA . 'post';
	const UPDATE       = Namespaces::ACTIVITY_SCHEMA . 'update';
	const TAG          = Namespaces::ACTIVITY_SCHEMA . 'tag';
	const FAVORITE     = Namespaces::ACTIVITY_SCHEMA . 'favorite';
	const UNFAVORITE   = Namespaces::ACTIVITY_SCHEMA . 'unfavorite';
	const SHARE        = Namespaces::ACTIVITY_SCHEMA . 'share';
	const DELETE       = Namespaces::ACTIVITY_SCHEMA . 'delete';
	const ANNOUNCE     = Namespaces::ACTIVITY2       . 'Announce';

	const POKE         = Namespaces::ZOT             . '/activity/poke';

	const OBJ_BOOKMARK = Namespaces::ACTIVITY_SCHEMA . 'bookmark';
	const OBJ_COMMENT  = Namespaces::ACTIVITY_SCHEMA . 'comment';
	const OBJ_NOTE     = Namespaces::ACTIVITY_SCHEMA . 'note';
	const OBJ_PERSON   = Namespaces::ACTIVITY_SCHEMA . 'person';
	const OBJ_IMAGE    = Namespaces::ACTIVITY_SCHEMA . 'image';
	const OBJ_PHOTO    = Namespaces::ACTIVITY_SCHEMA . 'photo';
	const OBJ_VIDEO    = Namespaces::ACTIVITY_SCHEMA . 'video';
	const OBJ_P_PHOTO  = Namespaces::ACTIVITY_SCHEMA . 'profile-photo';
	const OBJ_ALBUM    = Namespaces::ACTIVITY_SCHEMA . 'photo-album';
	const OBJ_EVENT    = Namespaces::ACTIVITY_SCHEMA . 'event';
	const OBJ_GROUP    = Namespaces::ACTIVITY_SCHEMA . 'group';
	const OBJ_TAGTERM  = Namespaces::DFRN            . '/tagterm';
	const OBJ_PROFILE  = Namespaces::DFRN            . '/profile';

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
		         strstr($needle, Namespaces::ACTIVITY_SCHEMA)));
	}
}
