<?php
require_once("include/Contact.php");
require_once("include/threads.php");
require_once("include/html2bbcode.php");
require_once("include/items.php");

define('OSTATUS_DEFAULT_POLL_INTERVAL', 30); // given in minutes
define('OSTATUS_DEFAULT_POLL_TIMEFRAME', 1440); // given in minutes

function ostatus_convert_href($href) {
	$elements = explode(":",$href);

	if ((count($elements) <= 2) OR ($elements[0] != "tag"))
		return $href;

	$server = explode(",", $elements[1]);
	$conversation = explode("=", $elements[2]);

	if ((count($elements) == 4) AND ($elements[2] == "post"))
		return "http://".$server[0]."/notice/".$elements[3];

	if ((count($conversation) != 2) OR ($conversation[1] ==""))
		return $href;

	if ($elements[3] == "objectType=thread")
		return "http://".$server[0]."/conversation/".$conversation[1];
	else
		return "http://".$server[0]."/notice/".$conversation[1];

	return $href;
}

function check_conversations($override = false) {
	$last = get_config('system','ostatus_last_poll');

	$poll_interval = intval(get_config('system','ostatus_poll_interval'));
	if(! $poll_interval)
		$poll_interval = OSTATUS_DEFAULT_POLL_INTERVAL;

	// Don't poll if the interval is set negative
	if (($poll_interval < 0) AND !$override)
		return;

	$poll_timeframe = intval(get_config('system','ostatus_poll_timeframe'));
	if (!$poll_timeframe)
		$poll_timeframe = OSTATUS_DEFAULT_POLL_TIMEFRAME;

	if ($last AND !$override) {
		$next = $last + ($poll_interval * 60);
		if ($next > time()) {
			logger('poll interval not reached');
			return;
		}
	}

	logger('cron_start');

	$start = date("Y-m-d H:i:s", time() - ($poll_timeframe * 60));
	$conversations = q("SELECT `oid`, `url`, `uid` FROM `term` WHERE `type` = 7 AND `term` > '%s' GROUP BY `url`, `uid` ORDER BY `term` DESC",
				dbesc($start));

	foreach ($conversations AS $conversation) {
		ostatus_completion($conversation['url'], $conversation['uid']);
	}

	logger('cron_end');

	set_config('system','ostatus_last_poll', time());
}

function ostatus_completion($conversation_url, $uid, $item = array()) {

	$item_stored = -3;

	$conversation_url = ostatus_convert_href($conversation_url);

	// If the thread shouldn't be completed then store the item and go away
	if ((intval(get_config('system','ostatus_poll_interval')) == -2) AND (count($item) > 0)) {
		$item_stored = item_store($item, true);
		return($item_stored);
	}

	// Get the parent
	$parents = q("SELECT `id`, `parent`, `uri`, `contact-id`, `type`, `verb`, `visible` FROM `item` WHERE `id` IN
			(SELECT `parent` FROM `item` WHERE `id` IN
				(SELECT `oid` FROM `term` WHERE `uid` = %d AND `otype` = %d AND `type` = %d AND `url` = '%s'))",
			intval($uid), intval(TERM_OBJ_POST), intval(TERM_CONVERSATION), dbesc($conversation_url));

	if ($parents)
		$parent = $parents[0];
	elseif (count($item) > 0) {
		$parent = $item;
		$parent["type"] = "remote";
		$parent["verb"] = ACTIVITY_POST;
		$parent["visible"] = 1;
	} else {
		// Preset the parent
		$r = q("SELECT `id` FROM `contact` WHERE `self` AND `uid`=%d", $uid);
		if (!$r)
			return(-1);

		$parent = array();
		$parent["id"] = 0;
		$parent["parent"] = 0;
		$parent["uri"] = "";
		$parent["contact-id"] = $r[0]["id"];
		$parent["type"] = "remote";
		$parent["verb"] = ACTIVITY_POST;
		$parent["visible"] = 1;
	}

	$conv = str_replace("/conversation/", "/api/statusnet/conversation/", $conversation_url).".as";
	$pageno = 1;
	$items = array();

	logger('fetching conversation url '.$conv.' for user '.$uid);

	do {
		$conv_arr = z_fetch_url($conv."?page=".$pageno);

		// If it is a non-ssl site and there is an error, then try ssl or vice versa
		if (!$conv_arr["success"] AND (substr($conv, 0, 7) == "http://")) {
			$conv = str_replace("http://", "https://", $conv);
			$conv_as = fetch_url($conv."?page=".$pageno);
		} elseif (!$conv_arr["success"] AND (substr($conv, 0, 8) == "https://")) {
			$conv = str_replace("https://", "http://", $conv);
			$conv_as = fetch_url($conv."?page=".$pageno);
		} else
			$conv_as = $conv_arr["body"];

		$conv_as = str_replace(',"statusnet:notice_info":', ',"statusnet_notice_info":', $conv_as);
		$conv_as = json_decode($conv_as);

		if (@is_array($conv_as->items))
			$items = array_merge($items, $conv_as->items);
		else
			break;

		$pageno++;

	} while (true);

	logger('fetching conversation done. Found '.count($items).' items');

	if (!sizeof($items)) {
		if (count($item) > 0) {
			$item_stored = item_store($item, true);
			logger("Conversation ".$conversation_url." couldn't be fetched. Item uri ".$item["uri"]." stored: ".$item_stored, LOGGER_DEBUG);

			if ($item_stored)
				complete_conversation($item_id, $conversation_url);

			return($item_stored);
		} else
			return(-2);
	}

	$items = array_reverse($items);

	foreach ($items as $single_conv) {

		// Test - remove before flight
		//$tempfile = tempnam(get_temppath(), "conversation");
		//file_put_contents($tempfile, json_encode($single_conv));


		if (isset($single_conv->object->id))
			$single_conv->id = $single_conv->object->id;

		$plink = ostatus_convert_href($single_conv->id);
		if (isset($single_conv->object->url))
			$plink = ostatus_convert_href($single_conv->object->url);

		if (@!$single_conv->id)
			continue;

		logger("Got id ".$single_conv->id, LOGGER_DEBUG);

		if ($first_id == "") {
			$first_id = $single_conv->id;

			// The first post of the conversation isn't our first post. There are three options:
			// 1. Our conversation hasn't the "real" thread starter
			// 2. This first post is a post inside our thread
			// 3. This first post is a post inside another thread
			if (($first_id != $parent["uri"]) AND ($parent["uri"] != "")) {
				$new_parents = q("SELECT `id`, `parent`, `uri`, `contact-id`, `type`, `verb`, `visible` FROM `item` WHERE `id` IN
							(SELECT `parent` FROM `item`
								WHERE `uid` = %d AND `uri` = '%s' AND `network` IN ('%s','%s')) LIMIT 1",
					intval($uid), dbesc($first_id), dbesc(NETWORK_OSTATUS), dbesc(NETWORK_DFRN));
				if ($new_parents) {
					if ($new_parents[0]["parent"] == $parent["parent"]) {
						// Option 2: This post is already present inside our thread - but not as thread starter
						logger("Option 2: uri present in our thread: ".$first_id, LOGGER_DEBUG);
						$first_id = $parent["uri"];
					} else {
						// Option 3: Not so good. We have mixed parents. We have to see how to clean this up.
						// For now just take the new parent.
						$parent = $new_parents[0];
						$first_id = $parent["uri"];
						logger("Option 3: mixed parents for uri ".$first_id, LOGGER_DEBUG);
					}
				} else {
					// Option 1: We hadn't got the real thread starter
					// We have to clean up our existing messages.
					$parent["id"] = 0;
					$parent["uri"] = $first_id;
					logger("Option 1: we have a new parent: ".$first_id, LOGGER_DEBUG);
				}
			} elseif ($parent["uri"] == "") {
				$parent["id"] = 0;
				$parent["uri"] = $first_id;
			}
		}

		if (isset($single_conv->context->inReplyTo->id)) {
			$parent_uri = $single_conv->context->inReplyTo->id;

			$parent_exists = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `uri` = '%s' AND `network` IN ('%s','%s') LIMIT 1",
						intval($uid), dbesc($parent_uri), dbesc(NETWORK_OSTATUS), dbesc(NETWORK_DFRN));
			if (!$parent_exists) {
				logger("Parent ".$parent_uri." wasn't found here", LOGGER_DEBUG);
				$parent_uri = $parent["uri"];
			}
		} else
			$parent_uri = $parent["uri"];

		$message_exists = q("SELECT `id`, `parent`, `uri` FROM `item` WHERE `uid` = %d AND `uri` = '%s' AND `network` IN ('%s','%s') LIMIT 1",
						intval($uid), dbesc($single_conv->id),
						dbesc(NETWORK_OSTATUS), dbesc(NETWORK_DFRN));
		if ($message_exists) {
			logger("Message ".$single_conv->id." already existed on the system", LOGGER_DEBUG);

			if ($parent["id"] != 0) {
				$existing_message = $message_exists[0];

				// We improved the way we fetch OStatus messages, this shouldn't happen very often now
				// To-Do: we have to change the shadow copies as well. This way here is really ugly.
				if ($existing_message["parent"] != $parent["id"]) {
					logger('updating id '.$existing_message["id"].' with parent '.$existing_message["parent"].' to parent '.$parent["id"].' uri '.$parent["uri"].' thread '.$parent_uri, LOGGER_DEBUG);

					// Update the parent id of the selected item
					$r = q("UPDATE `item` SET `parent` = %d, `parent-uri` = '%s' WHERE `id` = %d",
						intval($parent["id"]), dbesc($parent["uri"]), intval($existing_message["id"]));

					// Update the parent uri in the thread - but only if it points to itself
					$r = q("UPDATE `item` SET `thr-parent` = '%s' WHERE `id` = %d AND `uri` = `thr-parent`",
						dbesc($parent_uri), intval($existing_message["id"]));

					// try to change all items of the same parent
					$r = q("UPDATE `item` SET `parent` = %d, `parent-uri` = '%s' WHERE `parent` = %d",
						intval($parent["id"]), dbesc($parent["uri"]), intval($existing_message["parent"]));

					// Update the parent uri in the thread - but only if it points to itself
					$r = q("UPDATE `item` SET `thr-parent` = '%s' WHERE (`parent` = %d) AND (`uri` = `thr-parent`)",
						dbesc($parent["uri"]), intval($existing_message["parent"]));

					// Now delete the thread
					delete_thread($existing_message["parent"]);
				}
			}

			// The item we are having on the system is the one that we wanted to store via the item array
			if (isset($item["uri"]) AND ($item["uri"] == $existing_message["uri"])) {
				$item = array();
				$item_stored = 0;
			}

			continue;
		}

		$actor = $single_conv->actor->id;
		if (isset($single_conv->actor->url))
			$actor = $single_conv->actor->url;

		$contact = q("SELECT `id` FROM `contact` WHERE `uid` = %d AND `nurl` = '%s' AND `network` != '%s'",
				$uid, normalise_link($actor), NETWORK_STATUSNET);

		if (count($contact)) {
			logger("Found contact for url ".$actor, LOGGER_DEBUG);
			$contact_id = $contact[0]["id"];
		} else {
			logger("No contact found for url ".$actor, LOGGER_DEBUG);

			// Adding a global contact
			// To-Do: Use this data for the post
			$global_contact_id = get_contact($actor, 0);

			logger("Global contact ".$global_contact_id." found for url ".$actor, LOGGER_DEBUG);

			$contact_id = $parent["contact-id"];
		}

		$arr = array();
		$arr["network"] = NETWORK_OSTATUS;
		$arr["uri"] = $single_conv->id;
		$arr["plink"] = $plink;
		$arr["uid"] = $uid;
		$arr["contact-id"] = $contact_id;
		$arr["parent-uri"] = $parent_uri;
		$arr["created"] = $single_conv->published;
		$arr["edited"] = $single_conv->published;
		$arr["owner-name"] = $single_conv->actor->displayName;
		if ($arr["owner-name"] == '')
			$arr["owner-name"] = $single_conv->actor->contact->displayName;
		if ($arr["owner-name"] == '')
			$arr["owner-name"] = $single_conv->actor->portablecontacts_net->displayName;

		$arr["owner-link"] = $actor;
		$arr["owner-avatar"] = $single_conv->actor->image->url;
		$arr["author-name"] = $arr["owner-name"];
		$arr["author-link"] = $actor;
		$arr["author-avatar"] = $single_conv->actor->image->url;
		$arr["body"] = add_page_info_to_body(html2bbcode($single_conv->content));

		if (isset($single_conv->status_net->notice_info->source))
			$arr["app"] = strip_tags($single_conv->status_net->notice_info->source);
		elseif (isset($single_conv->statusnet->notice_info->source))
			$arr["app"] = strip_tags($single_conv->statusnet->notice_info->source);
		elseif (isset($single_conv->statusnet_notice_info->source))
			$arr["app"] = strip_tags($single_conv->statusnet_notice_info->source);
		elseif (isset($single_conv->provider->displayName))
			$arr["app"] = $single_conv->provider->displayName;
		else
			$arr["app"] = "OStatus";

		$arr["app"] .= "*";

		$arr["object"] = json_encode($single_conv);
		$arr["verb"] = $parent["verb"];
		$arr["visible"] = $parent["visible"];
		$arr["location"] = $single_conv->location->displayName;
		$arr["coord"] = trim($single_conv->location->lat." ".$single_conv->location->lon);

		// Is it a reshared item?
		if (isset($item->verb) AND ($item->verb == "share") AND isset($item->object)) {
			if (is_array($item->object))
				$item->object = $item->object[0];

			logger("Found reshared item ".$single_conv->object->id);

			// $single_conv->object->context->conversation;

			$plink = ostatus_convert_href($single_conv->object->url);

			$arr["uri"] = $single_conv->object->id;
			$arr["plink"] = $plink;
			$arr["created"] = $single_conv->object->published;
			$arr["edited"] = $single_conv->object->published;

			$arr["author-name"] = $single_conv->object->actor->displayName;
			if ($arr["owner-name"] == '')
				$arr["author-name"] = $single_conv->object->actor->contact->displayName;

			$arr["author-link"] = $single_conv->object->actor->url;
			$arr["author-avatar"] = $single_conv->object->actor->image->url;

			$arr["body"] = add_page_info_to_body(html2bbcode($single_conv->object->content));
			$arr["app"] = $single_conv->object->provider->displayName."#";
			//$arr["verb"] = $single_conv->object->verb;

			$arr["location"] = $single_conv->object->location->displayName;
			$arr["coord"] = trim($single_conv->object->location->lat." ".$single_conv->object->location->lon);
		}

		if ($arr["location"] == "")
			unset($arr["location"]);

		if ($arr["coord"] == "")
			unset($arr["coord"]);

		// Copy fields from given item array
		if (isset($item["uri"]) AND ($item["uri"] == $arr["uri"])) {
			$copy_fields = array("owner-name", "owner-link", "owner-avatar", "author-name", "author-link", "author-avatar",
						"gravity", "body", "object-type", "verb", "created", "edited", "coord", "tag",
						"attach", "app", "type", "location", "contact-id");
			foreach ($copy_fields AS $field)
				if (isset($item[$field]))
					$arr[$field] = $item[$field];

			$arr["app"] .= "+";
		}

		$newitem = item_store($arr);
		if (!$newitem) {
			logger("Item wasn't stored ".print_r($arr, true), LOGGER_DEBUG);
			continue;
		}

		if (isset($item["uri"]) AND ($item["uri"] == $arr["uri"])) {
			$item = array();
			$item_stored = $newitem;
		}

		logger('Stored new item '.$plink.' for parent '.$arr["parent-uri"].' under id '.$newitem, LOGGER_DEBUG);

		// Add the conversation entry (but don't fetch the whole conversation)
		complete_conversation($newitem, $conversation_url);

		// If the newly created item is the top item then change the parent settings of the thread
		// This shouldn't happen anymore. This is supposed to be absolote.
		if ($arr["uri"] == $first_id) {
			logger('setting new parent to id '.$newitem);
			$new_parents = q("SELECT `id`, `uri`, `contact-id`, `type`, `verb`, `visible` FROM `item` WHERE `uid` = %d AND `id` = %d LIMIT 1",
				intval($uid), intval($newitem));
			if ($new_parents)
				$parent = $new_parents[0];
		}
	}

	return($item_stored);
}

function complete_conversation($itemid, $conversation_url) {
	global $a;

	$conversation_url = ostatus_convert_href($conversation_url);

	$messages = q("SELECT `uid`, `parent`, `created`, `received`, `guid` FROM `item` WHERE `id` = %d LIMIT 1", intval($itemid));
	if (!$messages)
		return;
	$message = $messages[0];

	// Store conversation url if not done before
	$conversation = q("SELECT `url` FROM `term` WHERE `uid` = %d AND `oid` = %d AND `otype` = %d AND `type` = %d",
		intval($message["uid"]), intval($itemid), intval(TERM_OBJ_POST), intval(TERM_CONVERSATION));

	if (!$conversation) {
		$r = q("INSERT INTO `term` (`uid`, `oid`, `otype`, `type`, `term`, `url`, `created`, `received`, `guid`) VALUES (%d, %d, %d, %d, '%s', '%s', '%s', '%s', '%s')",
			intval($message["uid"]), intval($itemid), intval(TERM_OBJ_POST), intval(TERM_CONVERSATION),
			dbesc($message["created"]), dbesc($conversation_url), dbesc($message["created"]), dbesc($message["received"]), dbesc($message["guid"]));
		logger('Storing conversation url '.$conversation_url.' for id '.$itemid);
	}
}
?>
