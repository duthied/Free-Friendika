<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * This is the POST destination for most all locally posted
 * text stuff. This function handles status, wall-to-wall status,
 * local comments, and remote coments that are posted on this site
 * (as opposed to being delivered in a feed).
 * Also processed here are posts and comments coming through the
 * statusnet/twitter API.
 *
 * All of these become an "item" which is our basic unit of
 * information.
 */

use Friendica\App;
use Friendica\Content\Item as ItemHelper;
use Friendica\Content\PageInfo;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Attach;
use Friendica\Model\Contact;
use Friendica\Model\Conversation;
use Friendica\Model\FileTag;
use Friendica\Model\Item;
use Friendica\Model\Notification;
use Friendica\Model\Photo;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Model\User;
use Friendica\Network\HTTPException;
use Friendica\Object\EMail\ItemCCEMail;
use Friendica\Protocol\Activity;
use Friendica\Protocol\Diaspora;
use Friendica\Security\Security;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\ParseUrl;
use Friendica\Worker\Delivery;

function item_post(App $a) {
	if (!Session::isAuthenticated()) {
		throw new HTTPException\ForbiddenException();
	}

	$uid = local_user();

	if (!empty($_REQUEST['dropitems'])) {
		$arr_drop = explode(',', $_REQUEST['dropitems']);
		foreach ($arr_drop as $item) {
			Item::deleteForUser(['id' => $item], $uid);
		}

		$json = ['success' => 1];
		System::jsonExit($json);
	}

	Hook::callAll('post_local_start', $_REQUEST);

	Logger::debug('postvars', ['_REQUEST' => $_REQUEST]);

	$api_source = $_REQUEST['api_source'] ?? false;

	$return_path = $_REQUEST['return'] ?? '';
	$preview = intval($_REQUEST['preview'] ?? 0);

	/*
	 * Check for doubly-submitted posts, and reject duplicates
	 * Note that we have to ignore previews, otherwise nothing will post
	 * after it's been previewed
	 */
	if (!$preview && !empty($_REQUEST['post_id_random'])) {
		if (!empty($_SESSION['post-random']) && $_SESSION['post-random'] == $_REQUEST['post_id_random']) {
			Logger::info('item post: duplicate post');
			item_post_return(DI::baseUrl(), $api_source, $return_path);
		} else {
			$_SESSION['post-random'] = $_REQUEST['post_id_random'];
		}
	}

	// Is this a reply to something?
	$parent_item_id = intval($_REQUEST['parent'] ?? 0);
	$thr_parent_uri = trim($_REQUEST['parent_uri'] ?? '');

	$parent_item = null;
	$toplevel_item = null;
	$toplevel_item_id = 0;
	$toplevel_user_id = null;

	$objecttype = null;
	$profile_uid = ($_REQUEST['profile_uid'] ?? 0) ?: local_user();
	$posttype = ($_REQUEST['post_type'] ?? '') ?: Item::PT_ARTICLE;

	if ($parent_item_id || $thr_parent_uri) {
		if ($parent_item_id) {
			$parent_item = Post::selectFirst(Item::ITEM_FIELDLIST, ['id' => $parent_item_id]);
		} elseif ($thr_parent_uri) {
			$parent_item = Post::selectFirst(Item::ITEM_FIELDLIST, ['uri' => $thr_parent_uri, 'uid' => $profile_uid]);
		}

		// if this isn't the top-level parent of the conversation, find it
		if (DBA::isResult($parent_item)) {
			// The URI and the contact is taken from the direct parent which needn't to be the top parent
			$thr_parent_uri = $parent_item['uri'];
			$toplevel_item = $parent_item;

			if ($parent_item['gravity'] != GRAVITY_PARENT) {
				$toplevel_item = Post::selectFirst(Item::ITEM_FIELDLIST, ['id' => $toplevel_item['parent']]);
			}
		}

		if (!DBA::isResult($toplevel_item)) {
			notice(DI::l10n()->t('Unable to locate original post.'));
			if ($return_path) {
				DI::baseUrl()->redirect($return_path);
			}
			throw new HTTPException\NotFoundException(DI::l10n()->t('Unable to locate original post.'));
		}

		// When commenting on a public post then store the post for the current user
		// This enables interaction like starring and saving into folders
		if ($toplevel_item['uid'] == 0) {
			$stored = Item::storeForUserByUriId($toplevel_item['uri-id'], local_user());
			Logger::info('Public item stored for user', ['uri-id' => $toplevel_item['uri-id'], 'uid' => $uid, 'stored' => $stored]);
			if ($stored) {
				$toplevel_item = Post::selectFirst(Item::ITEM_FIELDLIST, ['id' => $stored]);
			}
		}

		$toplevel_item_id = $toplevel_item['id'];
		$toplevel_user_id = $toplevel_item['uid'];

		$objecttype = Activity\ObjectType::COMMENT;
	}

	if ($toplevel_item_id) {
		Logger::info('mod_item: item_post', ['parent' => $toplevel_item_id]);
	}

	$post_id     = intval($_REQUEST['post_id'] ?? 0);
	$app         = strip_tags($_REQUEST['source'] ?? '');
	$extid       = strip_tags($_REQUEST['extid'] ?? '');
	$object      = $_REQUEST['object'] ?? '';

	// Don't use "defaults" here. It would turn 0 to 1
	if (!isset($_REQUEST['wall'])) {
		$wall = 1;
	} else {
		$wall = $_REQUEST['wall'];
	}

	// Ensure that the user id in a thread always stay the same
	if (!is_null($toplevel_user_id) && in_array($toplevel_user_id, [local_user(), 0])) {
		$profile_uid = $toplevel_user_id;
	}

	// Allow commenting if it is an answer to a public post
	$allow_comment = local_user() && ($profile_uid == 0) && $toplevel_item_id && in_array($toplevel_item['network'], Protocol::FEDERATED);

	// Now check that valid personal details have been provided
	if (!Security::canWriteToUserWall($profile_uid) && !$allow_comment) {
		notice(DI::l10n()->t('Permission denied.'));
		if ($return_path) {
			DI::baseUrl()->redirect($return_path);
		}

		throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
	}

	// Init post instance
	$orig_post = null;

	// is this an edited post?
	if ($post_id > 0) {
		$orig_post = Post::selectFirst(Item::ITEM_FIELDLIST, ['id' => $post_id]);
	}

	$user = User::getById($profile_uid, ['allow_cid', 'allow_gid', 'deny_cid', 'deny_gid']);
	if (!DBA::isResult($user) && !$toplevel_item_id) {
		return 0;
	}

	$categories = '';
	$postopts = '';
	$emailcc = '';
	$body = $_REQUEST['body'] ?? '';
	$has_attachment = $_REQUEST['has_attachment'] ?? 0;

	// If we have a speparate attachment, we need to add it to the body.
	if (!empty($has_attachment)) {
		$attachment_type  = $_REQUEST['attachment_type'] ??  '';
		$attachment_title = $_REQUEST['attachment_title'] ?? '';
		$attachment_text  = $_REQUEST['attachment_text'] ??  '';

		$attachment_url     = hex2bin($_REQUEST['attachment_url'] ??     '');
		$attachment_img_src = hex2bin($_REQUEST['attachment_img_src'] ?? '');

		$attachment_img_width  = $_REQUEST['attachment_img_width'] ??  0;
		$attachment_img_height = $_REQUEST['attachment_img_height'] ?? 0;

		// Fetch the basic attachment data
		$attachment = ParseUrl::getSiteinfoCached($attachment_url);
		unset($attachment['keywords']);

		// Overwrite the basic data with possible changes from the frontend
		$attachment['type'] = $attachment_type;
		$attachment['title'] = $attachment_title;
		$attachment['text'] = $attachment_text;
		$attachment['url'] = $attachment_url;

		if (!empty($attachment_img_src)) {
			$attachment['images'] = [
				0 => [
					'src'    => $attachment_img_src,
					'width'  => $attachment_img_width,
					'height' => $attachment_img_height
				]
			];
		} else {
			unset($attachment['images']);
		}

		$att_bbcode = "\n" . PageInfo::getFooterFromData($attachment);
		$body .= $att_bbcode;
	}

	// Convert links with empty descriptions to links without an explicit description
	$body = preg_replace('#\[url=([^\]]*?)\]\[/url\]#ism', '[url]$1[/url]', $body);

	if (!empty($orig_post)) {
		$str_group_allow   = $orig_post['allow_gid'];
		$str_contact_allow = $orig_post['allow_cid'];
		$str_group_deny    = $orig_post['deny_gid'];
		$str_contact_deny  = $orig_post['deny_cid'];
		$location          = $orig_post['location'];
		$coord             = $orig_post['coord'];
		$verb              = $orig_post['verb'];
		$objecttype        = $orig_post['object-type'];
		$app               = $orig_post['app'];
		$categories        = Post\Category::getTextByURIId($orig_post['uri-id'], $orig_post['uid']);
		$title             = trim($_REQUEST['title'] ?? '');
		$body              = trim($body);
		$private           = $orig_post['private'];
		$pubmail_enabled   = $orig_post['pubmail'];
		$network           = $orig_post['network'];
		$guid              = $orig_post['guid'];
		$extid             = $orig_post['extid'];
	} else {
		$aclFormatter = DI::aclFormatter();
		$str_contact_allow = isset($_REQUEST['contact_allow']) ? $aclFormatter->toString($_REQUEST['contact_allow']) : $user['allow_cid'] ?? '';
		$str_group_allow   = isset($_REQUEST['group_allow'])   ? $aclFormatter->toString($_REQUEST['group_allow'])   : $user['allow_gid'] ?? '';
		$str_contact_deny  = isset($_REQUEST['contact_deny'])  ? $aclFormatter->toString($_REQUEST['contact_deny'])  : $user['deny_cid']  ?? '';
		$str_group_deny    = isset($_REQUEST['group_deny'])    ? $aclFormatter->toString($_REQUEST['group_deny'])    : $user['deny_gid']  ?? '';

		$visibility = $_REQUEST['visibility'] ?? '';
		if ($visibility === 'public') {
			// The ACL selector introduced in version 2019.12 sends ACL input data even when the Public visibility is selected
			$str_contact_allow = $str_group_allow = $str_contact_deny = $str_group_deny = '';
		} else if ($visibility === 'custom') {
			// Since we know from the visibility parameter the item should be private, we have to prevent the empty ACL
			// case that would make it public. So we always append the author's contact id to the allowed contacts.
			// See https://github.com/friendica/friendica/issues/9672
			$str_contact_allow .= $aclFormatter->toString(Contact::getPublicIdByUserId($uid));
		}

		$title             = trim($_REQUEST['title']    ?? '');
		$location          = trim($_REQUEST['location'] ?? '');
		$coord             = trim($_REQUEST['coord']    ?? '');
		$verb              = trim($_REQUEST['verb']     ?? '');
		$emailcc           = trim($_REQUEST['emailcc']  ?? '');
		$body              = trim($body);
		$network           = trim(($_REQUEST['network']  ?? '') ?: Protocol::DFRN);
		$guid              = System::createUUID();

		$postopts = $_REQUEST['postopts'] ?? '';

		if (strlen($str_group_allow) || strlen($str_contact_allow) || strlen($str_group_deny) || strlen($str_contact_deny)) {
			$private = Item::PRIVATE;
		} elseif (DI::pConfig()->get($profile_uid, 'system', 'unlisted')) {
			$private = Item::UNLISTED;
		} else {
			$private = Item::PUBLIC;
		}

		// If this is a comment, set the permissions from the parent.

		if ($toplevel_item) {
			// for non native networks use the network of the original post as network of the item
			if (($toplevel_item['network'] != Protocol::DIASPORA)
				&& ($toplevel_item['network'] != Protocol::OSTATUS)
				&& ($network == "")) {
				$network = $toplevel_item['network'];
			}

			$str_contact_allow = $toplevel_item['allow_cid'] ?? '';
			$str_group_allow   = $toplevel_item['allow_gid'] ?? '';
			$str_contact_deny  = $toplevel_item['deny_cid'] ?? '';
			$str_group_deny    = $toplevel_item['deny_gid'] ?? '';
			$private           = $toplevel_item['private'];

			$wall              = $toplevel_item['wall'];
		}

		$pubmail_enabled = ($_REQUEST['pubmail_enable'] ?? false) && !$private;

		// if using the API, we won't see pubmail_enable - figure out if it should be set
		if ($api_source && $profile_uid && $profile_uid == local_user() && !$private) {
			if (function_exists('imap_open') && !DI::config()->get('system', 'imap_disabled')) {
				$pubmail_enabled = DBA::exists('mailacct', ["`uid` = ? AND `server` != ? AND `pubmail`", local_user(), '']);
			}
		}

		if (!strlen($body)) {
			if ($preview) {
				System::jsonExit(['preview' => '']);
			}

			notice(DI::l10n()->t('Empty post discarded.'));
			if ($return_path) {
				DI::baseUrl()->redirect($return_path);
			}

			throw new HTTPException\BadRequestException(DI::l10n()->t('Empty post discarded.'));
		}
	}

	if (!empty($categories)) {
		// get the "fileas" tags for this post
		$filedas = FileTag::fileToArray($categories);
	}

	$categories = FileTag::listToFile(trim($_REQUEST['category'] ?? ''), 'category');

	if (!empty($filedas) && is_array($filedas)) {
		// append the fileas stuff to the new categories list
		$categories .= FileTag::arrayToFile($filedas);
	}

	// get contact info for poster

	$author = null;
	$self   = false;
	$contact_id = 0;

	if (local_user() && ((local_user() == $profile_uid) || $allow_comment)) {
		$self = true;
		$author = DBA::selectFirst('contact', [], ['uid' => local_user(), 'self' => true]);
	} elseif (!empty(Session::getRemoteContactID($profile_uid))) {
		$author = DBA::selectFirst('contact', [], ['id' => Session::getRemoteContactID($profile_uid)]);
	}

	if (DBA::isResult($author)) {
		$contact_id = $author['id'];
	}

	// get contact info for owner
	if ($profile_uid == local_user() || $allow_comment) {
		$contact_record = $author ?: [];
	} else {
		$contact_record = DBA::selectFirst('contact', [], ['uid' => $profile_uid, 'self' => true]) ?: [];
	}

	// Look for any tags and linkify them
	$inform   = '';
	$private_forum = false;
	$private_id = null;
	$only_to_forum = false;
	$forum_contact = [];

	$body = BBCode::performWithEscapedTags($body, ['noparse', 'pre', 'code', 'img'], function ($body) use ($profile_uid, $network, $str_contact_allow, &$inform, &$private_forum, &$private_id, &$only_to_forum, &$forum_contact) {
		$tags = BBCode::getTags($body);

		$tagged = [];

		foreach ($tags as $tag) {
			$tag_type = substr($tag, 0, 1);

			if ($tag_type == Tag::TAG_CHARACTER[Tag::HASHTAG]) {
				continue;
			}

			/* If we already tagged 'Robert Johnson', don't try and tag 'Robert'.
			 * Robert Johnson should be first in the $tags array
			 */
			foreach ($tagged as $nextTag) {
				if (stristr($nextTag, $tag . ' ')) {
					continue 2;
				}
			}

			$success = ItemHelper::replaceTag($body, $inform, local_user() ? local_user() : $profile_uid, $tag, $network);
			if ($success['replaced']) {
				$tagged[] = $tag;
			}
			// When the forum is private or the forum is addressed with a "!" make the post private
			if (!empty($success['contact']['prv']) || ($tag_type == Tag::TAG_CHARACTER[Tag::EXCLUSIVE_MENTION])) {
				$private_forum = $success['contact']['prv'];
				$only_to_forum = ($tag_type == Tag::TAG_CHARACTER[Tag::EXCLUSIVE_MENTION]);
				$private_id = $success['contact']['id'];
				$forum_contact = $success['contact'];
			} elseif (!empty($success['contact']['forum']) && ($str_contact_allow == '<' . $success['contact']['id'] . '>')) {
				$private_forum = false;
				$only_to_forum = true;
				$private_id = $success['contact']['id'];
				$forum_contact = $success['contact'];
			}
		}

		return $body;
	});

	$original_contact_id = $contact_id;

	if (!$toplevel_item_id && !empty($forum_contact) && ($private_forum || $only_to_forum)) {
		// we tagged a forum in a top level post. Now we change the post
		$private = $private_forum;

		$str_group_allow = '';
		$str_contact_deny = '';
		$str_group_deny = '';
		if ($private_forum) {
			$str_contact_allow = '<' . $private_id . '>';
		} else {
			$str_contact_allow = '';
		}
		$contact_id = $private_id;
		$contact_record = $forum_contact;
		$_REQUEST['origin'] = false;
		$wall = 0;
	}

	/*
	 * When a photo was uploaded into the message using the (profile wall) ajax
	 * uploader, The permissions are initially set to disallow anybody but the
	 * owner from seeing it. This is because the permissions may not yet have been
	 * set for the post. If it's private, the photo permissions should be set
	 * appropriately. But we didn't know the final permissions on the post until
	 * now. So now we'll look for links of uploaded messages that are in the
	 * post and set them to the same permissions as the post itself.
	 */

	$match = null;

	if (!$preview && Photo::setPermissionFromBody($body, $uid, $original_contact_id, $str_contact_allow, $str_group_allow, $str_contact_deny, $str_group_deny)) {
		$objecttype = Activity\ObjectType::IMAGE;
	}

	/*
	 * Next link in any attachment references we find in the post.
	 */
	$match = [];

	/// @todo these lines should be moved to Model/Attach (Once it exists)
	if (!$preview && preg_match_all("/\[attachment\](.*?)\[\/attachment\]/", $body, $match)) {
		$attaches = $match[1];
		if (count($attaches)) {
			foreach ($attaches as $attach) {
				// Ensure to only modify attachments that you own
				$srch = '<' . intval($original_contact_id) . '>';

				$condition = ['allow_cid' => $srch, 'allow_gid' => '', 'deny_cid' => '', 'deny_gid' => '',
						'id' => $attach];
				if (!Attach::exists($condition)) {
					continue;
				}

				$fields = ['allow_cid' => $str_contact_allow, 'allow_gid' => $str_group_allow,
						'deny_cid' => $str_contact_deny, 'deny_gid' => $str_group_deny];
				$condition = ['id' => $attach];
				Attach::update($fields, $condition);
			}
		}
	}

	// embedded bookmark or attachment in post? set bookmark flag

	$data = BBCode::getAttachmentData($body);
	$match = [];
	if ((preg_match_all("/\[bookmark\=([^\]]*)\](.*?)\[\/bookmark\]/ism", $body, $match, PREG_SET_ORDER) || isset($data["type"]))
		&& ($posttype != Item::PT_PERSONAL_NOTE)) {
		$posttype = Item::PT_PAGE;
		$objecttype =  Activity\ObjectType::BOOKMARK;
	}

	$body = DI::bbCodeVideo()->transform($body);

	$body = BBCode::scaleExternalImages($body);

	// Setting the object type if not defined before
	if (!$objecttype) {
		$objecttype = Activity\ObjectType::NOTE; // Default value
		$objectdata = BBCode::getAttachedData($body);

		if ($objectdata["type"] == "link") {
			$objecttype = Activity\ObjectType::BOOKMARK;
		} elseif ($objectdata["type"] == "video") {
			$objecttype = Activity\ObjectType::VIDEO;
		} elseif ($objectdata["type"] == "photo") {
			$objecttype = Activity\ObjectType::IMAGE;
		}

	}

	$attachments = '';
	$match = [];

	if (preg_match_all('/(\[attachment\]([0-9]+)\[\/attachment\])/',$body,$match)) {
		foreach ($match[2] as $mtch) {
			$fields = ['id', 'filename', 'filesize', 'filetype'];
			$attachment = Attach::selectFirst($fields, ['id' => $mtch]);
			if ($attachment !== false) {
				if (strlen($attachments)) {
					$attachments .= ',';
				}
				$attachments .= Post\Media::getAttachElement(DI::baseUrl() . '/attach/' . $attachment['id'],
					$attachment['filesize'], $attachment['filetype'], $attachment['filename'] ?? '');
			}
			$body = str_replace($match[1],'',$body);
		}
	}

	if (!strlen($verb)) {
		$verb = Activity::POST;
	}

	if ($network == "") {
		$network = Protocol::DFRN;
	}

	$gravity = ($toplevel_item_id ? GRAVITY_COMMENT : GRAVITY_PARENT);

	// even if the post arrived via API we are considering that it
	// originated on this site by default for determining relayability.

	// Don't use "defaults" here. It would turn 0 to 1
	if (!isset($_REQUEST['origin'])) {
		$origin = 1;
	} else {
		$origin = $_REQUEST['origin'];
	}

	$uri = Item::newURI($api_source ? $profile_uid : $uid, $guid);

	// Fallback so that we alway have a parent uri
	if (!$thr_parent_uri || !$toplevel_item_id) {
		$thr_parent_uri = $uri;
	}

	$datarray = [];
	$datarray['uid']           = $profile_uid;
	$datarray['wall']          = $wall;
	$datarray['gravity']       = $gravity;
	$datarray['network']       = $network;
	$datarray['contact-id']    = $contact_id;
	$datarray['owner-name']    = $contact_record['name'] ?? '';
	$datarray['owner-link']    = $contact_record['url'] ?? '';
	$datarray['owner-avatar']  = $contact_record['thumb'] ?? '';
	$datarray['owner-id']      = Contact::getIdForURL($datarray['owner-link']);
	$datarray['author-name']   = $author['name'];
	$datarray['author-link']   = $author['url'];
	$datarray['author-avatar'] = $author['thumb'];
	$datarray['author-id']     = Contact::getIdForURL($datarray['author-link']);
	$datarray['created']       = DateTimeFormat::utcNow();
	$datarray['edited']        = DateTimeFormat::utcNow();
	$datarray['commented']     = DateTimeFormat::utcNow();
	$datarray['received']      = DateTimeFormat::utcNow();
	$datarray['changed']       = DateTimeFormat::utcNow();
	$datarray['extid']         = $extid;
	$datarray['guid']          = $guid;
	$datarray['uri']           = $uri;
	$datarray['title']         = $title;
	$datarray['body']          = $body;
	$datarray['app']           = $app;
	$datarray['location']      = $location;
	$datarray['coord']         = $coord;
	$datarray['file']          = $categories;
	$datarray['inform']        = $inform;
	$datarray['verb']          = $verb;
	$datarray['post-type']     = $posttype;
	$datarray['object-type']   = $objecttype;
	$datarray['allow_cid']     = $str_contact_allow;
	$datarray['allow_gid']     = $str_group_allow;
	$datarray['deny_cid']      = $str_contact_deny;
	$datarray['deny_gid']      = $str_group_deny;
	$datarray['private']       = $private;
	$datarray['pubmail']       = $pubmail_enabled;
	$datarray['attach']        = $attachments;

	$datarray['thr-parent']    = $thr_parent_uri;

	$datarray['postopts']      = $postopts;
	$datarray['origin']        = $origin;
	$datarray['object']        = $object;

	/*
	 * These fields are for the convenience of addons...
	 * 'self' if true indicates the owner is posting on their own wall
	 * If parent is 0 it is a top-level post.
	 */
	$datarray['parent']        = $toplevel_item_id;
	$datarray['self']          = $self;

	// This triggers posts via API and the mirror functions
	$datarray['api_source'] = $api_source;

	// This field is for storing the raw conversation data
	$datarray['protocol'] = Conversation::PARCEL_DIRECT;
	$datarray['direction'] = Conversation::PUSH;

	$conversation = DBA::selectFirst('conversation', ['conversation-uri', 'conversation-href'], ['item-uri' => $datarray['thr-parent']]);
	if (DBA::isResult($conversation)) {
		if ($conversation['conversation-uri'] != '') {
			$datarray['conversation-uri'] = $conversation['conversation-uri'];
		}
		if ($conversation['conversation-href'] != '') {
			$datarray['conversation-href'] = $conversation['conversation-href'];
		}
	}

	if ($orig_post) {
		$datarray['edit'] = true;
	} else {
		// If this was a share, add missing data here
		$datarray = Item::addShareDataFromOriginal($datarray);

		$datarray['edit'] = false;
	}

	// Check for hashtags in the body and repair or add hashtag links
	if ($preview || $orig_post) {
		$datarray['body'] = Item::setHashtags($datarray['body']);
	}

	// preview mode - prepare the body for display and send it via json
	if ($preview) {
		// We set the datarray ID to -1 because in preview mode the dataray
		// doesn't have an ID.
		$datarray["id"] = -1;
		$datarray["uri-id"] = -1;
		$datarray["author-network"] = Protocol::DFRN;

		$o = conversation($a, [array_merge($contact_record, $datarray)], 'search', false, true);

		System::jsonExit(['preview' => $o]);
	}

	Hook::callAll('post_local',$datarray);

	if (!empty($datarray['cancel'])) {
		Logger::info('mod_item: post cancelled by addon.');
		if ($return_path) {
			DI::baseUrl()->redirect($return_path);
		}

		$json = ['cancel' => 1];
		if (!empty($_REQUEST['jsreload'])) {
			$json['reload'] = DI::baseUrl() . '/' . $_REQUEST['jsreload'];
		}

		System::jsonExit($json);
	}

	if ($orig_post)	{
		// Fill the cache field
		// This could be done in Item::update as well - but we have to check for the existance of some fields.
		Item::putInCache($datarray);

		$fields = [
			'title' => $datarray['title'],
			'body' => $datarray['body'],
			'attach' => $datarray['attach'],
			'file' => $datarray['file'],
			'rendered-html' => $datarray['rendered-html'],
			'rendered-hash' => $datarray['rendered-hash'],
			'edited' => DateTimeFormat::utcNow(),
			'changed' => DateTimeFormat::utcNow()];

		Item::update($fields, ['id' => $post_id]);

		if ($return_path) {
			DI::baseUrl()->redirect($return_path);
		}

		throw new HTTPException\OKException(DI::l10n()->t('Post updated.'));
	}

	unset($datarray['edit']);
	unset($datarray['self']);
	unset($datarray['api_source']);

	if ($origin) {
		$signed = Diaspora::createCommentSignature($uid, $datarray);
		if (!empty($signed)) {
			$datarray['diaspora_signed_text'] = json_encode($signed);
		}
	}

	$post_id = Item::insert($datarray);

	if (!$post_id) {
		notice(DI::l10n()->t('Item wasn\'t stored.'));
		if ($return_path) {
			DI::baseUrl()->redirect($return_path);
		}

		throw new HTTPException\InternalServerErrorException(DI::l10n()->t('Item wasn\'t stored.'));
	}

	$datarray = Post::selectFirst(Item::ITEM_FIELDLIST, ['id' => $post_id]);

	if (!DBA::isResult($datarray)) {
		Logger::error('Item couldn\'t be fetched.', ['post_id' => $post_id]);
		if ($return_path) {
			DI::baseUrl()->redirect($return_path);
		}

		throw new HTTPException\InternalServerErrorException(DI::l10n()->t('Item couldn\'t be fetched.'));
	}

	Tag::storeFromBody($datarray['uri-id'], $datarray['body']);

	if (!\Friendica\Content\Feature::isEnabled($uid, 'explicit_mentions') && ($datarray['gravity'] == GRAVITY_COMMENT)) {
		Tag::createImplicitMentions($datarray['uri-id'], $datarray['thr-parent-id']);
	}

	// These notifications are sent if someone else is commenting other your wall
	if ($contact_record != $author) {
		if ($toplevel_item_id) {
			notification([
				'type'  => Notification\Type::COMMENT,
				'otype' => Notification\ObjectType::ITEM,
				'verb'  => Activity::POST,
				'uid'   => $profile_uid,
				'cid'   => $datarray['author-id'],
				'item'  => $datarray,
				'link'  => DI::baseUrl() . '/display/' . urlencode($datarray['guid']),
			]);
		} elseif (empty($forum_contact)) {
			notification([
				'type'  => Notification\Type::WALL,
				'otype' => Notification\ObjectType::ITEM,
				'verb'  => Activity::POST,
				'uid'   => $profile_uid,
				'cid'   => $datarray['author-id'],
				'item'  => $datarray,
				'link'  => DI::baseUrl() . '/display/' . urlencode($datarray['guid']),
			]);
		}
	}

	Hook::callAll('post_local_end', $datarray);

	if (strlen($emailcc) && $profile_uid == local_user()) {
		$recipients = explode(',', $emailcc);
		if (count($recipients)) {
			foreach ($recipients as $recipient) {
				$address = trim($recipient);
				if (!strlen($address)) {
					continue;
				}
				DI::emailer()->send(new ItemCCEMail(DI::app(), DI::l10n(), DI::baseUrl(),
					$datarray, $address, $author['thumb'] ?? ''));
			}
		}
	}

	// When we are doing some forum posting via ! we have to start the notifier manually.
	// These kind of posts don't initiate the notifier call in the item class.
	if ($only_to_forum) {
		Worker::add(['priority' => PRIORITY_HIGH, 'dont_fork' => false], "Notifier", Delivery::POST, (int)$datarray['uri-id'], (int)$datarray['uid']);
	}

	Logger::info('post_complete');

	if ($api_source) {
		return $post_id;
	}

	item_post_return(DI::baseUrl(), $api_source, $return_path);
	// NOTREACHED
}

function item_post_return($baseurl, $api_source, $return_path)
{
	if ($api_source) {
		return;
	}

	if ($return_path) {
		DI::baseUrl()->redirect($return_path);
	}

	$json = ['success' => 1];
	if (!empty($_REQUEST['jsreload'])) {
		$json['reload'] = $baseurl . '/' . $_REQUEST['jsreload'];
	}

	Logger::info('post_json', ['json' => $json]);

	System::jsonExit($json);
}

function item_content(App $a)
{
	if (!Session::isAuthenticated()) {
		throw new HTTPException\UnauthorizedException();
	}

	$args = DI::args();

	if (!$args->has(3)) {
		throw new HTTPException\BadRequestException();
	}

	$o = '';
	switch ($args->get(1)) {
		case 'drop':
			if (DI::mode()->isAjax()) {
				Item::deleteForUser(['id' => $args->get(2)], local_user());
				// ajax return: [<item id>, 0 (no perm) | <owner id>]
				System::jsonExit([intval($args->get(2)), local_user()]);
			} else {
				if (!empty($args->get(3))) {
					$o = drop_item($args->get(2), $args->get(3));
				} else {
					$o = drop_item($args->get(2));
				}
			}
			break;
		case 'block':
			$item = Post::selectFirstForUser(local_user(), ['guid', 'author-id', 'parent', 'gravity'], ['id' => $args->get(2)]);
			if (empty($item['author-id'])) {
				throw new HTTPException\NotFoundException('Item not found');
			}

			Contact\User::setBlocked($item['author-id'], local_user(), true);

			if (DI::mode()->isAjax()) {
				// ajax return: [<item id>, 0 (no perm) | <owner id>]
				System::jsonExit([intval($args->get(2)), local_user()]);
			} else {
				item_redirect_after_action($item, $args->get(3));
			}
			break;
	}

	return $o;
}

/**
 * @param int    $id
 * @param string $return
 * @return string
 * @throws HTTPException\InternalServerErrorException
 */
function drop_item(int $id, string $return = '')
{
	// locate item to be deleted
	$fields = ['id', 'uid', 'guid', 'contact-id', 'deleted', 'gravity', 'parent'];
	$item = Post::selectFirstForUser(local_user(), $fields, ['id' => $id]);

	if (!DBA::isResult($item)) {
		notice(DI::l10n()->t('Item not found.'));
		DI::baseUrl()->redirect('network');
	}

	if ($item['deleted']) {
		return '';
	}

	$contact_id = 0;

	// check if logged in user is either the author or owner of this item
	if (Session::getRemoteContactID($item['uid']) == $item['contact-id']) {
		$contact_id = $item['contact-id'];
	}

	if ((local_user() == $item['uid']) || $contact_id) {
		// delete the item
		Item::deleteForUser(['id' => $item['id']], local_user());

		item_redirect_after_action($item, $return);
	} else {
		notice(DI::l10n()->t('Permission denied.'));
		DI::baseUrl()->redirect('display/' . $item['guid']);
		//NOTREACHED
	}

	return '';
}

function item_redirect_after_action($item, $returnUrlHex)
{
	$return_url = hex2bin($returnUrlHex);

	// removes update_* from return_url to ignore Ajax refresh
	$return_url = str_replace("update_", "", $return_url);

	// Check if delete a comment
	if ($item['gravity'] == GRAVITY_COMMENT) {
		if (!empty($item['parent'])) {
			$parentitem = Post::selectFirstForUser(local_user(), ['guid'], ['id' => $item['parent']]);
		}

		// Return to parent guid
		if (!empty($parentitem)) {
			DI::baseUrl()->redirect('display/' . $parentitem['guid']);
			//NOTREACHED
		} // In case something goes wrong
		else {
			DI::baseUrl()->redirect('network');
			//NOTREACHED
		}
	} else {
		// if unknown location or deleting top level post called from display
		if (empty($return_url) || strpos($return_url, 'display') !== false) {
			DI::baseUrl()->redirect('network');
			//NOTREACHED
		} else {
			DI::baseUrl()->redirect($return_url);
			//NOTREACHED
		}
	}
}
