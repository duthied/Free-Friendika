<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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
use Friendica\Content\Text\BBCode;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\ItemURI;
use Friendica\Model\Photo;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Network\HTTPException;
use Friendica\Object\EMail\ItemCCEMail;
use Friendica\Protocol\Activity;
use Friendica\Util\DateTimeFormat;

function item_post(App $a) {
	$uid = DI::userSession()->getLocalUserId();

	if (!DI::userSession()->isAuthenticated() || !$uid) {
		throw new HTTPException\ForbiddenException();
	}

	if (!empty($_REQUEST['dropitems'])) {
		$arr_drop = explode(',', $_REQUEST['dropitems']);
		foreach ($arr_drop as $item) {
			Item::deleteForUser(['id' => $item], $uid);
		}

		$json = ['success' => 1];
		System::jsonExit($json);
	}

	Hook::callAll('post_local_start', $_REQUEST);

	$return_path = $_REQUEST['return'] ?? '';
	$preview     = intval($_REQUEST['preview'] ?? 0);

	/*
	 * Check for doubly-submitted posts, and reject duplicates
	 * Note that we have to ignore previews, otherwise nothing will post
	 * after it's been previewed
	 */
	if (!$preview && !empty($_REQUEST['post_id_random'])) {
		if (!empty($_SESSION['post-random']) && $_SESSION['post-random'] == $_REQUEST['post_id_random']) {
			Logger::warning('duplicate post');
			item_post_return(DI::baseUrl(), $return_path);
		} else {
			$_SESSION['post-random'] = $_REQUEST['post_id_random'];
		}
	}

	$post_id = intval($_REQUEST['post_id'] ?? 0);

	// is this an edited post?
	if ($post_id > 0) {
		$orig_post = Post::selectFirst(Item::ITEM_FIELDLIST, ['id' => $post_id]);
	} else {
		$orig_post = null;
	}

	$emailcc = trim($_REQUEST['emailcc']  ?? '');

	$post = ['uid' => $uid];

	$post = DI::contentItem()->initializePost($post);

	$post['self']        = true;
	$post['api_source']  = false;
	$post['edit']        = $orig_post;
	$post['file']        = '';
	$post['attach']      = '';
	$post['inform']      = '';
	$post['postopts']    = '';

	$post['wall']        = $_REQUEST['wall'] ?? true;
	$post['post-type']   = $_REQUEST['post_type'] ?? '';
	$post['title']       = trim($_REQUEST['title'] ?? '');
	$post['body']        = $_REQUEST['body'] ?? '';
	$post['location']    = trim($_REQUEST['location'] ?? '');
	$post['coord']       = trim($_REQUEST['coord'] ?? '');
	$post['parent']      = intval($_REQUEST['parent'] ?? 0);
	$post['pubmail']     = $_REQUEST['pubmail_enable'] ?? false;
	$post['created']     = $_REQUEST['created_at'] ?? DateTimeFormat::utcNow();
	$post['edited']      = $post['changed'] = $post['commented'] = $post['created'];
	$post['app']         = '';

	if ($post['parent']) {
		if ($post['parent']) {
			$parent_item = Post::selectFirst(Item::ITEM_FIELDLIST, ['id' => $post['parent']]);
		}

		// if this isn't the top-level parent of the conversation, find it
		if (DBA::isResult($parent_item)) {
			// The URI and the contact is taken from the direct parent which needn't to be the top parent
			$post['thr-parent'] = $parent_item['uri'];
			$toplevel_item = $parent_item;

			if ($parent_item['gravity'] != Item::GRAVITY_PARENT) {
				$toplevel_item = Post::selectFirst(Item::ITEM_FIELDLIST, ['id' => $toplevel_item['parent']]);
			}
		}

		if (!DBA::isResult($toplevel_item)) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Unable to locate original post.'));
			if ($return_path) {
				DI::baseUrl()->redirect($return_path);
			}
			throw new HTTPException\NotFoundException(DI::l10n()->t('Unable to locate original post.'));
		}

		// When commenting on a public post then store the post for the current user
		// This enables interaction like starring and saving into folders
		if ($toplevel_item['uid'] == 0) {
			$stored = Item::storeForUserByUriId($toplevel_item['uri-id'], $post['uid'], ['post-reason' => Item::PR_ACTIVITY]);
			Logger::info('Public item stored for user', ['uri-id' => $toplevel_item['uri-id'], 'uid' => $post['uid'], 'stored' => $stored]);
			if ($stored) {
				$toplevel_item = Post::selectFirst(Item::ITEM_FIELDLIST, ['id' => $stored]);
			}
		}

		$post['parent']      = $toplevel_item['id'];
		$post['gravity']     = Item::GRAVITY_COMMENT;
		$post['wall']        = $toplevel_item['wall'];
	} else {
		$parent_item         = [];
		$post['parent']      = 0;
		$post['gravity']     = Item::GRAVITY_PARENT;
		$post['thr-parent']  = $post['uri'];
	}

	$post = DI::contentItem()->getACL($post, $parent_item, $_REQUEST);

	$post['pubmail'] = $post['pubmail'] && !$post['private'];

	if (!empty($orig_post)) {
		$post['uri']  = $orig_post['uri'];
		$post['file'] = Post\Category::getTextByURIId($orig_post['uri-id'], $orig_post['uid']);
	}

	$post = DI::contentItem()->addCategories($post, $_REQUEST['category'] ?? '');

	if (!$preview) {
		if (Photo::setPermissionFromBody($post['body'], $post['uid'], $post['contact-id'], $post['allow_cid'], $post['allow_gid'], $post['deny_cid'], $post['deny_gid'])) {
			$post['object-type'] = Activity\ObjectType::IMAGE;
		}

		$post = DI::contentItem()->moveAttachmentsFromBodyToAttach($post);
	}

	// Add the attachment to the body.
	if (!empty($_REQUEST['has_attachment'])) {
		$post['body'] .= DI::contentItem()->storeAttachmentFromRequest($_REQUEST);
	}

	$post = DI::contentItem()->finalizePost($post);

	if (!strlen($post['body'])) {
		if ($preview) {
			System::jsonExit(['preview' => '']);
		}

		DI::sysmsg()->addNotice(DI::l10n()->t('Empty post discarded.'));
		if ($return_path) {
			DI::baseUrl()->redirect($return_path);
		}

		throw new HTTPException\BadRequestException(DI::l10n()->t('Empty post discarded.'));
	}

	// Check for hashtags in the body and repair or add hashtag links
	if ($preview || $orig_post) {
		$post['body'] = Item::setHashtags($post['body']);
	}

	// preview mode - prepare the body for display and send it via json
	if ($preview) {
		// We set the datarray ID to -1 because in preview mode the dataray
		// doesn't have an ID.
		$post['id'] = -1;
		$post['uri-id'] = -1;
		$post['author-network'] = Protocol::DFRN;
		$post['author-updated'] = '';
		$post['author-gsid'] = 0;
		$post['author-uri-id'] = ItemURI::getIdByURI($post['author-link']);
		$post['owner-updated'] = '';
		$post['has-media'] = false;
		$post['quote-uri-id'] = Item::getQuoteUriId($post['body'], $post['uid']);
		$post['body'] = BBCode::removeSharedData($post['body']);
		$post['writable'] = true;

		$o = DI::conversation()->create([$post], 'search', false, true);

		System::jsonExit(['preview' => $o]);
	}

	Hook::callAll('post_local',$post);

	if (!empty($_REQUEST['scheduled_at'])) {
		$scheduled_at = DateTimeFormat::convert($_REQUEST['scheduled_at'], 'UTC', $a->getTimeZone());
		if ($scheduled_at > DateTimeFormat::utcNow()) {
			unset($post['created']);
			unset($post['edited']);
			unset($post['commented']);
			unset($post['received']);
			unset($post['changed']);
			unset($post['edit']);
			unset($post['self']);
			unset($post['api_source']);

			Post\Delayed::add($post['uri'], $post, Worker::PRIORITY_HIGH, Post\Delayed::PREPARED_NO_HOOK, $scheduled_at);
			item_post_return(DI::baseUrl(), $return_path);
		}
	}

	if (!empty($post['cancel'])) {
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

	$post['uri-id'] = ItemURI::getIdByURI($post['uri']);

	$quote_uri_id = Item::getQuoteUriId($post['body'], $post['uid']);
	if (!empty($quote_uri_id)) {
		$post['quote-uri-id'] = $quote_uri_id;
		$post['body']         = BBCode::removeSharedData($post['body']);
	}

	if ($orig_post) {
		$fields = [
			'title'   => $post['title'],
			'body'    => $post['body'],
			'attach'  => $post['attach'],
			'file'    => $post['file'],
			'edited'  => DateTimeFormat::utcNow(),
			'changed' => DateTimeFormat::utcNow()
		];

		Item::update($fields, ['id' => $post_id]);
		Item::updateDisplayCache($post['uri-id']);

		if ($return_path) {
			DI::baseUrl()->redirect($return_path);
		}

		throw new HTTPException\OKException(DI::l10n()->t('Post updated.'));
	}

	unset($post['edit']);
	unset($post['self']);
	unset($post['api_source']);

	$post_id = Item::insert($post);
	if (!$post_id) {
		DI::sysmsg()->addNotice(DI::l10n()->t('Item wasn\'t stored.'));
		if ($return_path) {
			DI::baseUrl()->redirect($return_path);
		}

		throw new HTTPException\InternalServerErrorException(DI::l10n()->t('Item wasn\'t stored.'));
	}

	$post = Post::selectFirst(Item::ITEM_FIELDLIST, ['id' => $post_id]);
	if (!DBA::isResult($post)) {
		Logger::error('Item couldn\'t be fetched.', ['post_id' => $post_id]);
		if ($return_path) {
			DI::baseUrl()->redirect($return_path);
		}

		throw new HTTPException\InternalServerErrorException(DI::l10n()->t('Item couldn\'t be fetched.'));
	}

	if (!\Friendica\Content\Feature::isEnabled($post['uid'], 'explicit_mentions') && ($post['gravity'] == Item::GRAVITY_COMMENT)) {
		Tag::createImplicitMentions($post['uri-id'], $post['thr-parent-id']);
	}

	Hook::callAll('post_local_end', $post);

	$recipients = explode(',', $emailcc);
	if (count($recipients)) {
		foreach ($recipients as $recipient) {
			$address = trim($recipient);
			if (!strlen($address)) {
				continue;
			}

			$author = DBA::selectFirst('contact', ['thumb'], ['uid' => $uid, 'self' => true]);

			DI::emailer()->send(new ItemCCEMail(DI::app(), DI::l10n(), DI::baseUrl(),
				$post, $address, $author['thumb'] ?? ''));
		}
	}

	Logger::debug('post_complete');

	item_post_return(DI::baseUrl(), $return_path);
	// NOTREACHED
}

function item_post_return($baseurl, $return_path)
{
	if ($return_path) {
		DI::baseUrl()->redirect($return_path);
	}

	$json = ['success' => 1];
	if (!empty($_REQUEST['jsreload'])) {
		$json['reload'] = $baseurl . '/' . $_REQUEST['jsreload'];
	}

	Logger::debug('post_json', ['json' => $json]);

	System::jsonExit($json);
}

function item_content(App $a)
{
	if (!DI::userSession()->isAuthenticated()) {
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
				Item::deleteForUser(['id' => $args->get(2)], DI::userSession()->getLocalUserId());
				// ajax return: [<item id>, 0 (no perm) | <owner id>]
				System::jsonExit([intval($args->get(2)), DI::userSession()->getLocalUserId()]);
			} else {
				if (!empty($args->get(3))) {
					$o = drop_item($args->get(2), $args->get(3));
				} else {
					$o = drop_item($args->get(2));
				}
			}
			break;

		case 'block':
			$item = Post::selectFirstForUser(DI::userSession()->getLocalUserId(), ['guid', 'author-id', 'parent', 'gravity'], ['id' => $args->get(2)]);
			if (empty($item['author-id'])) {
				throw new HTTPException\NotFoundException('Item not found');
			}

			Contact\User::setBlocked($item['author-id'], DI::userSession()->getLocalUserId(), true);

			if (DI::mode()->isAjax()) {
				// ajax return: [<item id>, 0 (no perm) | <owner id>]
				System::jsonExit([intval($args->get(2)), DI::userSession()->getLocalUserId()]);
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
function drop_item(int $id, string $return = ''): string
{
	// Locate item to be deleted
	$item = Post::selectFirstForUser(DI::userSession()->getLocalUserId(), ['id', 'uid', 'guid', 'contact-id', 'deleted', 'gravity', 'parent'], ['id' => $id]);

	if (!DBA::isResult($item)) {
		DI::sysmsg()->addNotice(DI::l10n()->t('Item not found.'));
		DI::baseUrl()->redirect('network');
		//NOTREACHED
	}

	if ($item['deleted']) {
		return '';
	}

	$contact_id = 0;

	// check if logged in user is either the author or owner of this item
	if (DI::userSession()->getRemoteContactID($item['uid']) == $item['contact-id']) {
		$contact_id = $item['contact-id'];
	}

	if ((DI::userSession()->getLocalUserId() == $item['uid']) || $contact_id) {
		// delete the item
		Item::deleteForUser(['id' => $item['id']], DI::userSession()->getLocalUserId());

		item_redirect_after_action($item, $return);
		//NOTREACHED
	} else {
		Logger::warning('Permission denied.', ['local' => DI::userSession()->getLocalUserId(), 'uid' => $item['uid'], 'cid' => $contact_id]);
		DI::sysmsg()->addNotice(DI::l10n()->t('Permission denied.'));
		DI::baseUrl()->redirect('display/' . $item['guid']);
		//NOTREACHED
	}

	return '';
}

function item_redirect_after_action(array $item, string $returnUrlHex)
{
	$return_url = hex2bin($returnUrlHex);

	// removes update_* from return_url to ignore Ajax refresh
	$return_url = str_replace('update_', '', $return_url);

	// Check if delete a comment
	if ($item['gravity'] == Item::GRAVITY_COMMENT) {
		if (!empty($item['parent'])) {
			$parentitem = Post::selectFirstForUser(DI::userSession()->getLocalUserId(), ['guid'], ['id' => $item['parent']]);
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
