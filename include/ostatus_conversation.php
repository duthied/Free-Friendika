<?php
require_once("include/Contact.php");
require_once("include/threads.php");

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
        $conversations = q("SELECT `oid`, `url` FROM `term` WHERE `type` = 7 AND `term` > '%s' GROUP BY `url` ORDER BY `term` DESC",
                                dbesc($start));

        foreach ($conversations AS $conversation) {
                $id = $conversation['oid'];
                $url = $conversation['url'];
                complete_conversation($id, $url);
        }

        logger('cron_end');

        set_config('system','ostatus_last_poll', time());
}

function complete_conversation($itemid, $conversation_url, $only_add_conversation = false) {
	global $a;

	$conversation_url = ostatus_convert_href($conversation_url);

	if (intval(get_config('system','ostatus_poll_interval')) == -2)
		return;

	if ($a->last_ostatus_conversation_url == $conversation_url)
		return;

	$a->last_ostatus_conversation_url = $conversation_url;

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

	if ($only_add_conversation)
		return;

	// Get the parent
	$parents = q("SELECT `id`, `uri`, `contact-id`, `type`, `verb`, `visible` FROM `item` WHERE `uid` = %d AND `id` = %d LIMIT 1",
			intval($message["uid"]), intval($message["parent"]));
	if (!$parents)
		return;
	$parent = $parents[0];

	require_once('include/html2bbcode.php');
	require_once('include/items.php');

	$conv = str_replace("/conversation/", "/api/statusnet/conversation/", $conversation_url).".as";
	$pageno = 1;
	$items = array();

	logger('fetching conversation url '.$conv.' for id '.$itemid.' and parent '.$parent["id"]);

	do {
		$conv_as = fetch_url($conv."?page=".$pageno);
		$conv_as = str_replace(',"statusnet:notice_info":', ',"statusnet_notice_info":', $conv_as);
		$conv_as = json_decode($conv_as);

		if (@is_array($conv_as->items))
			$items = array_merge($items, $conv_as->items);
		else
			break;

		$pageno++;

	} while (true);

	if (!sizeof($items))
		return;

	$items = array_reverse($items);

	foreach ($items as $single_conv) {
		if (isset($single_conv->object->id))
			$single_conv->id = $single_conv->object->id;

		//logger("Got id ".$single_conv->id, LOGGER_DEBUG);

		$plink = ostatus_convert_href($single_conv->id);
		if (isset($single_conv->object->url))
			$plink = ostatus_convert_href($single_conv->object->url);

		//logger("Got url ".$plink, LOGGER_DEBUG);

		if (@!$single_conv->id)
			continue;

		if ($first_id == "") {
			$first_id = $single_conv->id;

			$new_parents = q("SELECT `id`, `parent`, `uri`, `contact-id`, `type`, `verb`, `visible` FROM `item` WHERE `uid` = %d AND `uri` = '%s' AND `network` IN ('%s','%s') LIMIT 1",
				intval($message["uid"]), dbesc($first_id),
				dbesc(NETWORK_OSTATUS), dbesc(NETWORK_DFRN));
			if ($new_parents) {
				// It can happen that OStatus servers have incomplete threads.
				// Then keep the current parent
				if ($parent["id"] == $parent["parent"]) {
					$parent = $new_parents[0];

					if ($parent["id"] != $message["parent"])
						logger('Fetch new parent id '.$parent["id"].' - Old parent: '.$message["parent"]);
				} else {
					$first_id = $parent["uri"];
					//logger('Keep parent for '.$itemid.' - Old parent: '.$message["parent"]);
				}
			} else {
				$parent["id"] = 0;
				$parent["uri"] = $first_id;
			}
		}

		if (isset($single_conv->context->inReplyTo->id))
			$parent_uri = $single_conv->context->inReplyTo->id;
		else
			$parent_uri = $parent["uri"];

		$message_exists = q("SELECT `id`, `parent` FROM `item` WHERE `uid` = %d AND `plink` = '%s' AND `network` IN ('%s','%s') LIMIT 1",
							intval($message["uid"]), dbesc($plink),
							dbesc(NETWORK_OSTATUS), dbesc(NETWORK_DFRN));

		if (!$message_exists)
			$message_exists = q("SELECT `id`, `parent` FROM `item` WHERE `uid` = %d AND `uri` = '%s' AND `network` IN ('%s','%s') LIMIT 1",
							intval($message["uid"]), dbesc($single_conv->id),
							dbesc(NETWORK_OSTATUS), dbesc(NETWORK_DFRN));

		if ($message_exists) {
			if ($parent["id"] != 0) {
				$existing_message = $message_exists[0];

				// We improved the way we fetch OStatus messages, this shouldn't happen very often now
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
			continue;
		}

		$actor = $single_conv->actor->id;
		if (isset($single_conv->actor->url))
			$actor = $single_conv->actor->url;

		$contact = q("SELECT `id` FROM `contact` WHERE `uid` = %d AND `nurl` = '%s' AND `network` != '%s'",
				$message["uid"], normalise_link($actor), NETWORK_STATUSNET);

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
		$arr["uid"] = $message["uid"];
		$arr["contact-id"] = $contact_id;
		if ($parent["id"] != 0)
			$arr["parent"] = $parent["id"];
		$arr["parent-uri"] = $parent["uri"];
		$arr["thr-parent"] = $parent_uri;
		$arr["created"] = $single_conv->published;
		$arr["edited"] = $single_conv->published;
		$arr["owner-name"] = $single_conv->actor->contact->displayName;
		//$arr["owner-name"] = $single_conv->actor->contact->preferredUsername;
		if ($arr["owner-name"] == '')
			$arr["owner-name"] = $single_conv->actor->portablecontacts_net->displayName;
		if ($arr["owner-name"] == '')
			$arr["owner-name"] = $single_conv->actor->displayName;
		if ($arr["owner-name"] == '')
			$arr["owner-name"] = $single_conv->actor->portablecontacts_net->preferredUsername;
		if ($arr["owner-name"] == '')
			$arr["owner-name"] = $single_conv->actor->preferredUsername;

		$arr["owner-link"] = $actor;
		$arr["owner-avatar"] = $single_conv->actor->image->url;
		//$arr["author-name"] = $single_conv->actor->contact->displayName;
		//$arr["author-name"] = $single_conv->actor->contact->preferredUsername;
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

		$arr["verb"] = $parent["verb"];
		$arr["visible"] = $parent["visible"];
		$arr["location"] = $single_conv->location->displayName;
		$arr["coord"] = trim($single_conv->location->lat." ".$single_conv->location->lon);

		if ($arr["location"] == "")
			unset($arr["location"]);

		if ($arr["coord"] == "")
			unset($arr["coord"]);

		$newitem = item_store($arr);

		logger('Stored new item '.$plink.' for parent '.$arr["parent"].' under id '.$newitem, LOGGER_DEBUG);

		// Add the conversation entry (but don't fetch the whole conversation)
		complete_conversation($newitem, $conversation_url, true);

		// If the newly created item is the top item then change the parent settings of the thread
		// This shouldn't happen anymore. This is supposed to be absolote.
		if ($newitem AND ($arr["uri"] == $first_id)) {
			logger('setting new parent to id '.$newitem);
			$new_parents = q("SELECT `id`, `uri`, `contact-id`, `type`, `verb`, `visible` FROM `item` WHERE `uid` = %d AND `id` = %d LIMIT 1",
				intval($message["uid"]), intval($newitem));
			if ($new_parents)
				$parent = $new_parents[0];
		}
	}
}
?>
