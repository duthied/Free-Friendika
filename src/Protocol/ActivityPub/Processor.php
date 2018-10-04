<?php
/**
 * @file src/Protocol/ActivityPub/Processor.php
 */
namespace Friendica\Protocol\ActivityPub;

use Friendica\Database\DBA;
use Friendica\Core\Protocol;
use Friendica\Model\Conversation;
use Friendica\Model\Contact;
use Friendica\Model\APContact;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Content\Text\HTML;
use Friendica\Util\JsonLD;
use Friendica\Core\Config;
use Friendica\Protocol\ActivityPub;

/**
 * @brief ActivityPub Protocol class
 */
class Processor
{
	/**
	 * @brief Converts mentions from Pleroma into the Friendica format
	 *
	 * @param string $body
	 *
	 * @return converted body
	 */
	private static function convertMentions($body)
	{
		$URLSearchString = "^\[\]";
		$body = preg_replace("/\[url\=([$URLSearchString]*)\]([#@!])(.*?)\[\/url\]/ism", '$2[url=$1]$3[/url]', $body);

		return $body;
	}

	/**
	 * @brief Constructs a string with tags for a given tag array
	 *
	 * @param array $tags
	 * @param boolean $sensitive
	 *
	 * @return string with tags
	 */
	private static function constructTagList($tags, $sensitive)
	{
		if (empty($tags)) {
			return '';
		}

		$tag_text = '';
		foreach ($tags as $tag) {
			if (in_array($tag['type'], ['Mention', 'Hashtag'])) {
				if (!empty($tag_text)) {
					$tag_text .= ',';
				}

				$tag_text .= substr($tag['name'], 0, 1) . '[url=' . $tag['href'] . ']' . substr($tag['name'], 1) . '[/url]';
			}
		}

		/// @todo add nsfw for $sensitive

		return $tag_text;
	}

	/**
	 * @brief 
	 *
	 * @param $attachments
	 * @param array $item
	 *
	 * @return item array
	 */
	private static function constructAttachList($attachments, $item)
	{
		if (empty($attachments)) {
			return $item;
		}

		foreach ($attachments as $attach) {
			$filetype = strtolower(substr($attach['mediaType'], 0, strpos($attach['mediaType'], '/')));
			if ($filetype == 'image') {
				$item['body'] .= "\n[img]" . $attach['url'] . '[/img]';
			} else {
				if (!empty($item["attach"])) {
					$item["attach"] .= ',';
				} else {
					$item["attach"] = '';
				}
				if (!isset($attach['length'])) {
					$attach['length'] = "0";
				}
				$item["attach"] .= '[attach]href="'.$attach['url'].'" length="'.$attach['length'].'" type="'.$attach['mediaType'].'" title="'.defaults($attach, 'name', '').'"[/attach]';
			}
		}

		return $item;
	}

	/**
	 * @brief 
	 *
	 * @param array $activity
	 * @param $body
	 */
	public static function createItem($activity, $body)
	{
		$item = [];
		$item['verb'] = ACTIVITY_POST;
		$item['parent-uri'] = $activity['reply-to-id'];

		if ($activity['reply-to-id'] == $activity['id']) {
			$item['gravity'] = GRAVITY_PARENT;
			$item['object-type'] = ACTIVITY_OBJ_NOTE;
		} else {
			$item['gravity'] = GRAVITY_COMMENT;
			$item['object-type'] = ACTIVITY_OBJ_COMMENT;
		}

		if (($activity['id'] != $activity['reply-to-id']) && !Item::exists(['uri' => $activity['reply-to-id']])) {
			logger('Parent ' . $activity['reply-to-id'] . ' not found. Try to refetch it.');
			self::fetchMissingActivity($activity['reply-to-id'], $activity);
		}

		self::postItem($activity, $item, $body);
	}

	/**
	 * @brief 
	 *
	 * @param array $activity
	 * @param $body
	 */
	public static function likeItem($activity, $body)
	{
		$item = [];
		$item['verb'] = ACTIVITY_LIKE;
		$item['parent-uri'] = $activity['object'];
		$item['gravity'] = GRAVITY_ACTIVITY;
		$item['object-type'] = ACTIVITY_OBJ_NOTE;

		self::postItem($activity, $item, $body);
	}

	/**
	 * @brief Delete items
	 *
	 * @param array $activity
	 * @param $body
	 */
	public static function deleteItem($activity)
	{
		$owner = Contact::getIdForURL($activity['owner']);
		$object = JsonLD::fetchElement($activity, 'object', 'id');
		logger('Deleting item ' . $object . ' from ' . $owner, LOGGER_DEBUG);
		Item::delete(['uri' => $object, 'owner-id' => $owner]);
	}

	/**
	 * @brief 
	 *
	 * @param array $activity
	 * @param $body
	 */
	public static function dislikeItem($activity, $body)
	{
		$item = [];
		$item['verb'] = ACTIVITY_DISLIKE;
		$item['parent-uri'] = $activity['object'];
		$item['gravity'] = GRAVITY_ACTIVITY;
		$item['object-type'] = ACTIVITY_OBJ_NOTE;

		self::postItem($activity, $item, $body);
	}

	/**
	 * @brief 
	 *
	 * @param array $activity
	 * @param array $item
	 * @param $body
	 */
	private static function postItem($activity, $item, $body)
	{
		/// @todo What to do with $activity['context']?

		if (($item['gravity'] != GRAVITY_PARENT) && !Item::exists(['uri' => $item['parent-uri']])) {
			logger('Parent ' . $item['parent-uri'] . ' not found, message will be discarded.', LOGGER_DEBUG);
			return;
		}

		$item['network'] = Protocol::ACTIVITYPUB;
		$item['private'] = !in_array(0, $activity['receiver']);
		$item['author-id'] = Contact::getIdForURL($activity['author'], 0, true);
		$item['owner-id'] = Contact::getIdForURL($activity['owner'], 0, true);
		$item['uri'] = $activity['id'];
		$item['created'] = $activity['published'];
		$item['edited'] = $activity['updated'];
		$item['guid'] = $activity['diaspora:guid'];
		$item['title'] = HTML::toBBCode($activity['name']);
		$item['content-warning'] = HTML::toBBCode($activity['summary']);
		$item['body'] = self::convertMentions(HTML::toBBCode($activity['content']));
		$item['location'] = $activity['location'];
		$item['tag'] = self::constructTagList($activity['tags'], $activity['sensitive']);
		$item['app'] = $activity['service'];
		$item['plink'] = defaults($activity, 'alternate-url', $item['uri']);

		$item = self::constructAttachList($activity['attachments'], $item);

		$source = JsonLD::fetchElement($activity, 'source', 'content', 'mediaType', 'text/bbcode');
		if (!empty($source)) {
			$item['body'] = $source;
		}

		$item['protocol'] = Conversation::PARCEL_ACTIVITYPUB;
		$item['source'] = $body;
		$item['conversation-href'] = $activity['context'];
		$item['conversation-uri'] = $activity['conversation'];

		foreach ($activity['receiver'] as $receiver) {
			$item['uid'] = $receiver;
			$item['contact-id'] = Contact::getIdForURL($activity['author'], $receiver, true);

			if (($receiver != 0) && empty($item['contact-id'])) {
				$item['contact-id'] = Contact::getIdForURL($activity['author'], 0, true);
			}

			$item_id = Item::insert($item);
			logger('Storing for user ' . $item['uid'] . ': ' . $item_id);
		}
	}

	/**
	 * @brief 
	 *
	 * @param $url
	 * @param $child
	 */
	private static function fetchMissingActivity($url, $child)
	{
		if (Config::get('system', 'ostatus_full_threads')) {
			return;
		}

		$object = ActivityPub::fetchContent($url);
		if (empty($object)) {
			logger('Activity ' . $url . ' was not fetchable, aborting.');
			return;
		}

		$activity = [];
		$activity['@context'] = $object['@context'];
		unset($object['@context']);
		$activity['id'] = $object['id'];
		$activity['to'] = defaults($object, 'to', []);
		$activity['cc'] = defaults($object, 'cc', []);
		$activity['actor'] = $child['author'];
		$activity['object'] = $object;
		$activity['published'] = $object['published'];
		$activity['type'] = 'Create';

		ActivityPub\Receiver::processActivity($activity);
		logger('Activity ' . $url . ' had been fetched and processed.');
	}

	/**
	 * @brief perform a "follow" request
	 *
	 * @param array $activity
	 */
	public static function followUser($activity)
	{
		$actor = JsonLD::fetchElement($activity, 'object', 'id');
		$uid = User::getIdForURL($actor);
		if (empty($uid)) {
			return;
		}

		$owner = User::getOwnerDataById($uid);

		$cid = Contact::getIdForURL($activity['owner'], $uid);
		if (!empty($cid)) {
			$contact = DBA::selectFirst('contact', [], ['id' => $cid]);
		} else {
			$contact = false;
		}

		$item = ['author-id' => Contact::getIdForURL($activity['owner']),
			'author-link' => $activity['owner']];

		Contact::addRelationship($owner, $contact, $item);
		$cid = Contact::getIdForURL($activity['owner'], $uid);
		if (empty($cid)) {
			return;
		}

		$contact = DBA::selectFirst('contact', ['network'], ['id' => $cid]);
		if ($contact['network'] != Protocol::ACTIVITYPUB) {
			Contact::updateFromProbe($cid, Protocol::ACTIVITYPUB);
		}

		DBA::update('contact', ['hub-verify' => $activity['id']], ['id' => $cid]);
		logger('Follow user ' . $uid . ' from contact ' . $cid . ' with id ' . $activity['id']);
	}

	/**
	 * @brief Update the given profile
	 *
	 * @param array $activity
	 */
	public static function updatePerson($activity)
	{
		if (empty($activity['object']['id'])) {
			return;
		}

		logger('Updating profile for ' . $activity['object']['id'], LOGGER_DEBUG);
		APContact::getByURL($activity['object']['id'], true);
	}

	/**
	 * @brief Delete the given profile
	 *
	 * @param array $activity
	 */
	public static function deletePerson($activity)
	{
		if (empty($activity['object']['id']) || empty($activity['object']['actor'])) {
			logger('Empty object id or actor.', LOGGER_DEBUG);
			return;
		}

		if ($activity['object']['id'] != $activity['object']['actor']) {
			logger('Object id does not match actor.', LOGGER_DEBUG);
			return;
		}

		$contacts = DBA::select('contact', ['id'], ['nurl' => normalise_link($activity['object']['id'])]);
		while ($contact = DBA::fetch($contacts)) {
			Contact::remove($contact["id"]);
		}
		DBA::close($contacts);

		logger('Deleted contact ' . $activity['object']['id'], LOGGER_DEBUG);
	}

	/**
	 * @brief Accept a follow request
	 *
	 * @param array $activity
	 */
	public static function acceptFollowUser($activity)
	{
		$actor = JsonLD::fetchElement($activity, 'object', 'actor');
		$uid = User::getIdForURL($actor);
		if (empty($uid)) {
			return;
		}

		$owner = User::getOwnerDataById($uid);

		$cid = Contact::getIdForURL($activity['owner'], $uid);
		if (empty($cid)) {
			logger('No contact found for ' . $activity['owner'], LOGGER_DEBUG);
			return;
		}

		$fields = ['pending' => false];

		$contact = DBA::selectFirst('contact', ['rel'], ['id' => $cid]);
		if ($contact['rel'] == Contact::FOLLOWER) {
			$fields['rel'] = Contact::FRIEND;
		}

		$condition = ['id' => $cid];
		DBA::update('contact', $fields, $condition);
		logger('Accept contact request from contact ' . $cid . ' for user ' . $uid, LOGGER_DEBUG);
	}

	/**
	 * @brief Reject a follow request
	 *
	 * @param array $activity
	 */
	public static function rejectFollowUser($activity)
	{
		$actor = JsonLD::fetchElement($activity, 'object', 'actor');
		$uid = User::getIdForURL($actor);
		if (empty($uid)) {
			return;
		}

		$owner = User::getOwnerDataById($uid);

		$cid = Contact::getIdForURL($activity['owner'], $uid);
		if (empty($cid)) {
			logger('No contact found for ' . $activity['owner'], LOGGER_DEBUG);
			return;
		}

		if (DBA::exists('contact', ['id' => $cid, 'rel' => Contact::SHARING, 'pending' => true])) {
			Contact::remove($cid);
			logger('Rejected contact request from contact ' . $cid . ' for user ' . $uid . ' - contact had been removed.', LOGGER_DEBUG);
		} else {
			logger('Rejected contact request from contact ' . $cid . ' for user ' . $uid . '.', LOGGER_DEBUG);
		}
	}

	/**
	 * @brief Undo activity like "like" or "dislike"
	 *
	 * @param array $activity
	 */
	public static function undoActivity($activity)
	{
		$activity_url = JsonLD::fetchElement($activity, 'object', 'id');
		if (empty($activity_url)) {
			return;
		}

		$actor = JsonLD::fetchElement($activity, 'object', 'actor');
		if (empty($actor)) {
			return;
		}

		$author_id = Contact::getIdForURL($actor);
		if (empty($author_id)) {
			return;
		}

		Item::delete(['uri' => $activity_url, 'author-id' => $author_id, 'gravity' => GRAVITY_ACTIVITY]);
	}

	/**
	 * @brief Activity to remove a follower
	 *
	 * @param array $activity
	 */
	public static function undoFollowUser($activity)
	{
		$object = JsonLD::fetchElement($activity, 'object', 'object');
		$uid = User::getIdForURL($object);
		if (empty($uid)) {
			return;
		}

		$owner = User::getOwnerDataById($uid);

		$cid = Contact::getIdForURL($activity['owner'], $uid);
		if (empty($cid)) {
			logger('No contact found for ' . $activity['owner'], LOGGER_DEBUG);
			return;
		}

		$contact = DBA::selectFirst('contact', [], ['id' => $cid]);
		if (!DBA::isResult($contact)) {
			return;
		}

		Contact::removeFollower($owner, $contact);
		logger('Undo following request from contact ' . $cid . ' for user ' . $uid, LOGGER_DEBUG);
	}
}
