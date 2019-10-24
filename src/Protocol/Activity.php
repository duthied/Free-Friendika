<?php

namespace Friendica\Protocol;

use Friendica\Protocol\Activity\ANamespace;

/**
 * Base class for the Activity Verbs
 */
final class Activity
{
	/**
	 * Indicates that the actor marked the object as an item of special interest.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const LIKE = ANamespace::ACTIVITY_SCHEMA . 'like';
	/**
	 * Dislike a message ("I don't like the post")
	 *
	 * @see http://purl.org/macgirvin/dfrn/1.0/dislike
	 * @var string
	 */
	const DISLIKE = ANamespace::DFRN . '/dislike';

	/**
	 * Attend an event
	 *
	 * @see https://github.com/friendica/friendica/wiki/ActivityStreams#activity_attend
	 * @var string
	 */
	const ATTEND      = ANamespace::ZOT . '/activity/attendyes';
	/**
	 * Don't attend an event
	 *
	 * @see https://github.com/friendica/friendica/wiki/ActivityStreams#activity_attendno
	 * @var string
	 */
	const ATTENDNO    = ANamespace::ZOT . '/activity/attendno';
	/**
	 * Attend maybe an event
	 *
	 * @see https://github.com/friendica/friendica/wiki/ActivityStreams#activity_attendmaybe
	 * @var string
	 */
	const ATTENDMAYBE = ANamespace::ZOT . '/activity/attendmaybe';

	/**
	 * Indicates the creation of a friendship that is reciprocated by the object.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const FRIEND      = ANamespace::ACTIVITY_SCHEMA . 'make-friend';
	/**
	 * Indicates the creation of a friendship that has not yet been reciprocated by the object.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const REQ_FRIEND = ANamespace::ACTIVITY_SCHEMA . 'request-friend';
	/**
	 * Indicates that the actor has removed the object from the collection of friends.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const UNFRIEND   = ANamespace::ACTIVITY_SCHEMA . 'remove-friend';
	/**
	 * Indicates that the actor began following the activity of the object.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const FOLLOW     = ANamespace::ACTIVITY_SCHEMA . 'follow';
	/**
	 * Indicates that the actor has stopped following the object.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const UNFOLLOW   = ANamespace::ACTIVITY_SCHEMA . 'stop-following';
	/**
	 * Indicates that the actor has become a member of the object.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const JOIN       = ANamespace::ACTIVITY_SCHEMA . 'join';
	/**
	 * Implementors SHOULD use verbs such as post where the actor is adding new items to a collection or similar.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const POST       = ANamespace::ACTIVITY_SCHEMA . 'post';
	/**
	 * The "update" verb indicates that the actor has modified the object.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const UPDATE     = ANamespace::ACTIVITY_SCHEMA . 'update';
	/**
	 * Indicates that the actor has identified the presence of a target inside another object.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const TAG        = ANamespace::ACTIVITY_SCHEMA . 'tag';
	/**
	 * Indicates that the actor marked the object as an item of special interest.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const FAVORITE   = ANamespace::ACTIVITY_SCHEMA . 'favorite';
	/**
	 * Indicates that the actor has removed the object from the collection of favorited items.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const UNFAVORITE = ANamespace::ACTIVITY_SCHEMA . 'unfavorite';
	/**
	 * Indicates that the actor has called out the object to readers.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const SHARE      = ANamespace::ACTIVITY_SCHEMA . 'share';
	/**
	 * Indicates that the actor has deleted the object.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const DELETE     = ANamespace::ACTIVITY_SCHEMA . 'delete';
	/**
	 * Indicates that the actor is calling the target's attention the object.
	 *
	 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-announce
	 * @var string
	 */
	const ANNOUNCE   = ANamespace::ACTIVITY2 . 'Announce';

	/**
	 * Pokes an user.
	 *
	 * @see https://github.com/friendica/friendica/wiki/ActivityStreams#activity_poke
	 * @var string
	 */
	const POKE       = ANamespace::ZOT . '/activity/poke';


	const O_UNFOLLOW    = ANamespace::OSTATUS . '/unfollow';
	const O_UNFAVOURITE = ANamespace::OSTATUS . '/unfavorite';

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
