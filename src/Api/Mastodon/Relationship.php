<?php

namespace Friendica\Api\Mastodon;

use Friendica\Model\Contact;
use Friendica\Util\Network;

/**
 * Class Relationship
 *
 * @see https://docs.joinmastodon.org/api/entities/#relationship
 */
class Relationship
{
	/** @var int */
	var $id;
	/** @var bool */
	var $following = false;
	/** @var bool */
	var $followed_by = false;
	/** @var bool */
	var $blocking = false;
	/** @var bool */
	var $muting = false;
	/** @var bool */
	var $muting_notifications = false;
	/** @var bool */
	var $requested = false;
	/** @var bool */
	var $domain_blocking = false;
	/** @var bool */
	var $showing_reblogs = false;
	/** @var bool */
	var $endorsed = false;

	/**
	 * @param array $contact Full Contact table record
	 * @return Relationship
	 */
	public static function createFromContact(array $contact)
	{
		$relationship = new self();

		$relationship->id                   = $contact['id'];
		$relationship->following            = in_array($contact['rel'], [Contact::SHARING, Contact::FRIEND]);
		$relationship->followed_by          = in_array($contact['rel'], [Contact::FOLLOWER, Contact::FRIEND]);
		$relationship->blocking             = (bool)$contact['blocked'];
		$relationship->muting               = (bool)$contact['readonly'];
		$relationship->muting_notifications = (bool)$contact['readonly'];
		$relationship->requested            = (bool)$contact['pending'];
		$relationship->domain_blocking      = Network::isUrlBlocked($contact['url']);
		// Unsupported
		$relationship->showing_reblogs      = true;
		// Unsupported
		$relationship->endorsed             = false;

		return $relationship;
	}
}
