<?php
require_once("mod/share.php");
require_once('include/html2bbcode.php');
require_once('include/enotify.php');
require_once('include/items.php');
require_once('include/ostatus_conversation.php');

function ostatus_fetchauthor($xpath, $context, $importer, &$contact) {

	$author = array();
	$author["author-link"] = $xpath->evaluate('atom:author/atom:uri/text()', $context)->item(0)->nodeValue;
	$author["author-name"] = $xpath->evaluate('atom:author/atom:name/text()', $context)->item(0)->nodeValue;

	// Preserve the value
	$authorlink = $author["author-link"];

	$alternate = $xpath->query("atom:author/atom:link[@rel='alternate']", $context)->item(0)->attributes;
	if (is_object($alternate))
		foreach($alternate AS $attributes)
			if ($attributes->name == "href")
				$author["author-link"] = $attributes->textContent;

	$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `nurl` IN ('%s', '%s') AND `network` != '%s'",
		intval($importer["uid"]), dbesc(normalise_link($author["author-link"])),
		dbesc(normalise_link($authorlink)), dbesc(NETWORK_STATUSNET));
	if ($r) {
		$contact = $r[0];
		$author["contact-id"] = $r[0]["id"];
	} else
		$author["contact-id"] = $contact["id"];

	$avatarlist = array();
	$avatars = $xpath->query("atom:author/atom:link[@rel='avatar']", $context);
	foreach($avatars AS $avatar) {
		$href = "";
		$width = 0;
		foreach($avatar->attributes AS $attributes) {
			if ($attributes->name == "href")
				$href = $attributes->textContent;
			if ($attributes->name == "width")
				$width = $attributes->textContent;
		}
		if (($width > 0) AND ($href != ""))
			$avatarlist[$width] = $href;
	}
	if (count($avatarlist) > 0) {
		krsort($avatarlist);
		$author["author-avatar"] = current($avatarlist);
	}

	$displayname = $xpath->evaluate('atom:author/poco:displayName/text()', $context)->item(0)->nodeValue;
	if ($displayname != "")
		$author["author-name"] = $displayname;

	$author["owner-name"] = $author["author-name"];
	$author["owner-link"] = $author["author-link"];
	$author["owner-avatar"] = $author["author-avatar"];

	return($author);
}

function ostatus_import($xml,$importer,&$contact, &$hub) {

	// To-Do:
	// Hub

	$a = get_app();

	logger("Import OStatus message", LOGGER_DEBUG);

	if ($xml == "")
		return;

	$doc = new DOMDocument();
	@$doc->loadXML($xml);

	$xpath = new DomXPath($doc);
	$xpath->registerNamespace('atom', "http://www.w3.org/2005/Atom");
	$xpath->registerNamespace('thr', "http://purl.org/syndication/thread/1.0");
	$xpath->registerNamespace('georss', "http://www.georss.org/georss");
	$xpath->registerNamespace('activity', "http://activitystrea.ms/spec/1.0/");
	$xpath->registerNamespace('media', "http://purl.org/syndication/atommedia");
	$xpath->registerNamespace('poco', "http://portablecontacts.net/spec/1.0");
	$xpath->registerNamespace('ostatus', "http://ostatus.org/schema/1.0");
	$xpath->registerNamespace('statusnet', "http://status.net/schema/api/1/");

	$gub = "";
	$hub_attributes = $xpath->query("/atom:feed/atom:link[@rel='hub']")->item(0)->attributes;
	if (is_object($hub_attributes))
		foreach($hub_attributes AS $hub_attribute)
			if ($hub_attribute->name == "href") {
				$hub = $hub_attribute->textContent;
				logger("Found hub ".$hub, LOGGER_DEBUG);
			}

	$header = array();
	$header["uid"] = $importer["uid"];
	$header["network"] = NETWORK_OSTATUS;
	$header["type"] = "remote";
	$header["wall"] = 0;
	$header["origin"] = 0;
	$header["gravity"] = GRAVITY_PARENT;

	// it could either be a received post or a post we fetched by ourselves
	// depending on that, the first node is different
	$first_child = $doc->firstChild->tagName;

	if ($first_child == "feed")
		$entries = $xpath->query('/atom:feed/atom:entry');
	else
		$entries = $xpath->query('/atom:entry');

	$conversation = "";
	$conversationlist = array();
	$item_id = 0;

	// Reverse the order of the entries
	$entrylist = array();

	foreach ($entries AS $entry)
		$entrylist[] = $entry;

	foreach (array_reverse($entrylist) AS $entry) {

		$mention = false;

		// fetch the author
		if ($first_child == "feed")
			$author = ostatus_fetchauthor($xpath, $doc->firstChild, $importer, $contact);
		else
			$author = ostatus_fetchauthor($xpath, $entry, $importer, $contact);

		$item = array_merge($header, $author);

		// Now get the item
		$item["uri"] = $xpath->query('atom:id/text()', $entry)->item(0)->nodeValue;

		$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `uri` = '%s'",
			intval($importer["uid"]), dbesc($item["uri"]));
		if ($r) {
			logger("Item with uri ".$item["uri"]." for user ".$importer["uid"]." already existed under id ".$r[0]["id"], LOGGER_DEBUG);
			continue;
		}

		$item["body"] = add_page_info_to_body(html2bbcode($xpath->query('atom:content/text()', $entry)->item(0)->nodeValue));
		$item["object-type"] = $xpath->query('activity:object-type/text()', $entry)->item(0)->nodeValue;
		$item["verb"] = $xpath->query('activity:verb/text()', $entry)->item(0)->nodeValue;

		if ($item["verb"] == ACTIVITY_FOLLOW) {
			// ignore "Follow" messages
			continue;
		}

		if ($item["verb"] == ACTIVITY_FAVORITE) {
			// ignore "Favorite" messages
			continue;
		}

		$item["created"] = $xpath->query('atom:published/text()', $entry)->item(0)->nodeValue;
		$item["edited"] = $xpath->query('atom:updated/text()', $entry)->item(0)->nodeValue;
		$conversation = $xpath->query('ostatus:conversation/text()', $entry)->item(0)->nodeValue;

		$related = "";

		$inreplyto = $xpath->query('thr:in-reply-to', $entry);
		if (is_object($inreplyto->item(0))) {
			foreach($inreplyto->item(0)->attributes AS $attributes) {
				if ($attributes->name == "ref")
					$item["parent-uri"] = $attributes->textContent;
				if ($attributes->name == "href")
					$related = $attributes->textContent;
			}
		}

		$georsspoint = $xpath->query('georss:point', $entry);
		if ($georsspoint)
			$item["coord"] = $georsspoint->item(0)->nodeValue;

		$categories = $xpath->query('atom:category', $entry);
		if ($categories) {
			foreach ($categories AS $category) {
				foreach($category->attributes AS $attributes)
					if ($attributes->name == "term") {
						$term = $attributes->textContent;
						if(strlen($item["tag"]))
							$item["tag"] .= ',';
						$item["tag"] .= "#[url=".$a->get_baseurl()."/search?tag=".$term."]".$term."[/url]";
					}
			}
		}

		$self = "";
		$enclosure = "";

		$links = $xpath->query('atom:link', $entry);
		if ($links) {
			$rel = "";
			$href = "";
			$type = "";
			$length = "0";
			$title = "";
			foreach ($links AS $link) {
				foreach($link->attributes AS $attributes) {
					if ($attributes->name == "href")
						$href = $attributes->textContent;
					if ($attributes->name == "rel")
						$rel = $attributes->textContent;
					if ($attributes->name == "type")
						$type = $attributes->textContent;
					if ($attributes->name == "length")
						$length = $attributes->textContent;
					if ($attributes->name == "title")
						$title = $attributes->textContent;
				}
				if (($rel != "") AND ($href != ""))
					switch($rel) {
						case "alternate":
							$item["plink"] = $href;
							break;
						case "ostatus:conversation":
							$conversation = $href;
							break;
						case "enclosure":
							$enclosure = $href;
							if(strlen($item["attach"]))
								$item["attach"] .= ',';

							$item["attach"] .= '[attach]href="'.$href.'" length="'.$length.'" type="'.$type.'" title="'.$title.'"[/attach]';
							break;
						case "related":
							if (!isset($item["parent-uri"]))
								$item["parent-uri"] = $href;

							if ($related == "")
								$related = $href;
							break;
						case "self":
							$self = $href;
							break;
						case "mentioned":
							// Notification check
							if ($importer["nurl"] == normalise_link($href))
								$mention = true;
							break;
					}
			}
		}

		$local_id = "";
		$repeat_of = "";

		$notice_info = $xpath->query('statusnet:notice_info', $entry);
		if ($notice_info)
			foreach($notice_info->item(0)->attributes AS $attributes) {
				if ($attributes->name == "source")
					$item["app"] = strip_tags($attributes->textContent);
				if ($attributes->name == "local_id")
					$local_id = $attributes->textContent;
				if ($attributes->name == "repeat_of")
					$repeat_of = $attributes->textContent;
			}

		// Is it a repeated post?
		if ($repeat_of != "") {
			$activityobjects = $xpath->query('activity:object', $entry)->item(0);

			if (is_object($activityobjects)) {

				$orig_uris = $xpath->query("activity:object/atom:link[@rel='alternate']", $activityobjects);
				if ($orig_uris)
					foreach($orig_uris->item(0)->attributes AS $attributes)
						if ($attributes->name == "href")
							$orig_uri = $attributes->textContent;

				if (!isset($orig_uri))
					$orig_uri = $xpath->query("atom:link[@rel='alternate']", $activityobjects)->item(0)->nodeValue;

				if (!isset($orig_uri))
					$orig_uri = $xpath->query("activity:object/atom:id", $activityobjects)->item(0)->nodeValue;

				if (!isset($orig_uri))
					$orig_uri = $xpath->query('atom:id/text()', $activityobjects)->item(0)->nodeValue;

				$orig_body = $xpath->query('atom:content/text()', $activityobjects)->item(0)->nodeValue;
				$orig_created = $xpath->query('atom:published/text()', $activityobjects)->item(0)->nodeValue;

				$orig_contact = $contact;
				$orig_author = ostatus_fetchauthor($xpath, $activityobjects, $importer, $orig_contact);

				$prefix = share_header($orig_author['author-name'], $orig_author['author-link'], $orig_author['author-avatar'], "", $orig_created, $orig_uri);
				$item["body"] = $prefix.html2bbcode($orig_body)."[/share]";

				$item["verb"] = $xpath->query('activity:verb/text()', $activityobjects)->item(0)->nodeValue;
				$item["object-type"] = $xpath->query('activity:object-type/text()', $activityobjects)->item(0)->nodeValue;
			}
		}

		//if ($enclosure != "")
		//	$item["body"] .= add_page_info($enclosure);

		if (isset($item["parent-uri"])) {
			$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `uri` = '%s'",
				intval($importer["uid"]), dbesc($item["parent-uri"]));

			if (!$r AND ($related != "")) {
				$reply_path = str_replace("/notice/", "/api/statuses/show/", $related).".atom";

				if ($reply_path != $related) {
					logger("Fetching related items for user ".$importer["uid"]." from ".$reply_path, LOGGER_DEBUG);
					$reply_xml = fetch_url($reply_path);

					$reply_contact = $contact;
					ostatus_import($reply_xml,$importer,$reply_contact, $reply_hub);

					// After the import try to fetch the parent item again
					$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `uri` = '%s'",
						intval($importer["uid"]), dbesc($item["parent-uri"]));
				}
			}
			if ($r) {
				$item["type"] = 'remote-comment';
				$item["gravity"] = GRAVITY_COMMENT;
			}
		} else
			$item["parent-uri"] = $item["uri"];

		$item_id = item_store($item);
		//echo $xml;
		//print_r($item);
		//echo $item_id." ".$item["parent-uri"]."\n";

		if (!$item_id) {
			logger("Error storing item ".print_r($item, true), LOGGER_DEBUG);
			continue;
		}

		logger("Item was stored with id ".$item_id, LOGGER_DEBUG);
		$item["id"] = $item_id;

		if (!isset($item["parent"]) OR ($item["parent"] == 0))
			$item["parent"] = $item_id;

		if ($mention) {
			$u = q("SELECT `notify-flags`, `language`, `username`, `email` FROM user WHERE uid = %d LIMIT 1", intval($item['uid']));

			notification(array(
				'type'         => NOTIFY_TAGSELF,
				'notify_flags' => $u[0]["notify-flags"],
				'language'     => $u[0]["language"],
				'to_name'      => $u[0]["username"],
				'to_email'     => $u[0]["email"],
				'uid'          => $item["uid"],
				'item'         => $item,
				'link'         => $a->get_baseurl().'/display/'.urlencode(get_item_guid($item["id"])),
				'source_name'  => $item["author-name"],
				'source_link'  => $item["author-link"],
				'source_photo' => $item["author-avatar"],
				'verb'         => ACTIVITY_TAG,
				'otype'        => 'item',
				'parent'       => $item["parent"]
			));
		}

		if ($conversation != "") {
			// Check for duplicates. We really don't need to check the same conversation twice.
			if (!in_array($conversation, $conversationlist)) {
				complete_conversation($item_id, $conversation);
				$conversationlist[] = $conversation;
			}
		}
	}
}
