<?php
/*
require_once("include/Contact.php");
require_once("include/threads.php");
require_once("include/html2bbcode.php");
require_once("include/bbcode.php");
require_once("include/items.php");
require_once("mod/share.php");
require_once("include/enotify.php");
require_once("include/socgraph.php");
require_once("include/Photo.php");
require_once("include/Scrape.php");
require_once("include/follow.php");
require_once("include/api.php");
require_once("mod/proxy.php");
*/

define("NS_ATOM", "http://www.w3.org/2005/Atom");
define("NS_THR", "http://purl.org/syndication/thread/1.0");
define("NS_GEORSS", "http://www.georss.org/georss");
define("NS_ACTIVITY", "http://activitystrea.ms/spec/1.0/");
define("NS_MEDIA", "http://purl.org/syndication/atommedia");
define("NS_POCO", "http://portablecontacts.net/spec/1.0");
define("NS_OSTATUS", "http://ostatus.org/schema/1.0");
define("NS_STATUSNET", "http://status.net/schema/api/1/");

class dfrn2 {
	function fetchauthor($xpath, $context, $importer, $element, &$contact, $onlyfetch) {

		$author = array();
		$author["name"] = $xpath->evaluate($element.'/atom:name/text()', $context)->item(0)->nodeValue;
		$author["link"] = $xpath->evaluate($element.'/atom:uri/text()', $context)->item(0)->nodeValue;

		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `nurl` IN ('%s', '%s') AND `network` != '%s'",
			intval($importer["uid"]), dbesc(normalise_link($author["author-link"])),
			dbesc(normalise_link($author["link"])), dbesc(NETWORK_STATUSNET));
		if ($r) {
			$contact = $r[0];
			$author["contact-id"] = $r[0]["id"];
			$author["network"] = $r[0]["network"];
		} else {
			$author["contact-id"] = $contact["id"];
			$author["network"] = $contact["network"];
		}

		// Until now we aren't serving different sizes - but maybe later
		$avatarlist = array();
		// @todo check if "avatar" or "photo" would be the best field in the specification
		$avatars = $xpath->query($element."/atom:link[@rel='avatar']", $context);
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
			$author["avatar"] = current($avatarlist);
		}

		if ($r AND !$onlyfetch) {
			// Update contact data

			$value = $xpath->evaluate($element.'/poco:displayName/text()', $context)->item(0)->nodeValue;
			if ($value != "")
				$contact["name"] = $value;

			$value = $xpath->evaluate($element.'/poco:preferredUsername/text()', $context)->item(0)->nodeValue;
			if ($value != "")
				$contact["nick"] = $value;

			$value = $xpath->evaluate($element.'/poco:note/text()', $context)->item(0)->nodeValue;
			if ($value != "")
				$contact["about"] = $value;

			$value = $xpath->evaluate($element.'/poco:address/poco:formatted/text()', $context)->item(0)->nodeValue;
			if ($value != "")
				$contact["location"] = $value;

			/// @todo
			/// poco:birthday
			/// poco:utcOffset
			/// poco:updated
			/// poco:ims
			/// poco:tags

/*
			if (($contact["name"] != $r[0]["name"]) OR ($contact["nick"] != $r[0]["nick"]) OR ($contact["about"] != $r[0]["about"]) OR ($contact["location"] != $r[0]["location"])) {

				logger("Update contact data for contact ".$contact["id"], LOGGER_DEBUG);

				q("UPDATE `contact` SET `name` = '%s', `nick` = '%s', `about` = '%s', `location` = '%s', `name-date` = '%s' WHERE `id` = %d AND `network` = '%s'",
					dbesc($contact["name"]), dbesc($contact["nick"]), dbesc($contact["about"]), dbesc($contact["location"]),
					dbesc(datetime_convert()), intval($contact["id"]), dbesc(NETWORK_OSTATUS));

			}

			if (isset($author["author-avatar"]) AND ($author["author-avatar"] != $r[0]['photo'])) {
				logger("Update profile picture for contact ".$contact["id"], LOGGER_DEBUG);

				$photos = import_profile_photo($author["author-avatar"], $importer["uid"], $contact["id"]);

				q("UPDATE `contact` SET `photo` = '%s', `thumb` = '%s', `micro` = '%s', `avatar-date` = '%s' WHERE `id` = %d AND `network` = '%s'",
					dbesc($author["author-avatar"]), dbesc($photos[1]), dbesc($photos[2]),
					dbesc(datetime_convert()), intval($contact["id"]), dbesc(NETWORK_OSTATUS));
			}
*/
			/// @todo Add the "addr" field
//			$contact["generation"] = 2;
//			$contact["photo"] = $author["avatar"];
//print_r($contact);
			//update_gcontact($contact);
		}

		return($author);
	}

	function import($xml,$importer,&$contact, &$hub) {

		$a = get_app();

		logger("Import DFRN message", LOGGER_DEBUG);

		if ($xml == "")
			return;

		$doc = new DOMDocument();
		@$doc->loadXML($xml);

		$xpath = new DomXPath($doc);
		$xpath->registerNamespace('atom', "http://www.w3.org/2005/Atom");
		$xpath->registerNamespace('thr', "http://purl.org/syndication/thread/1.0");
		$xpath->registerNamespace('at', "http://purl.org/atompub/tombstones/1.0");
		$xpath->registerNamespace('media', "http://purl.org/syndication/atommedia");
		$xpath->registerNamespace('dfrn', "http://purl.org/macgirvin/dfrn/1.0");
		$xpath->registerNamespace('activity', "http://activitystrea.ms/spec/1.0/");
		$xpath->registerNamespace('georss', "http://www.georss.org/georss");
		$xpath->registerNamespace('poco', "http://portablecontacts.net/spec/1.0");
		$xpath->registerNamespace('ostatus', "http://ostatus.org/schema/1.0");
		$xpath->registerNamespace('statusnet', "http://status.net/schema/api/1/");

		$header = array();
		$header["uid"] = $importer["uid"];
		$header["network"] = NETWORK_DFRN;
		$header["type"] = "remote";
		$header["wall"] = 0;
		$header["origin"] = 0;
		$header["gravity"] = GRAVITY_PARENT;
		$header["contact-id"] = $importer["id"];

		// Update the contact table if the data has changed
		// Only the "dfrn:owner" in the head section contains all data
		self::fetchauthor($xpath, $doc->firstChild, $importer, "dfrn:owner", $contact, false);

		$entries = $xpath->query('/atom:feed/atom:entry');

		$item_id = 0;

		// Reverse the order of the entries
		$entrylist = array();

		foreach ($entries AS $entry)
			$entrylist[] = $entry;

		foreach (array_reverse($entrylist) AS $entry) {

			$item = $header;

			$mention = false;

			// Fetch the owner
			$owner = self::fetchauthor($xpath, $entry, $importer, "dfrn:owner", $contact, true);

			$item["owner-name"] = $owner["name"];
			$item["owner-link"] = $owner["link"];
			$item["owner-avatar"] = $owner["avatar"];

			if ($header["contact-id"] != $owner["contact-id"])
				$item["contact-id"] = $owner["contact-id"];

			if (($header["network"] != $owner["network"]) AND ($owner["network"] != ""))
				$item["network"] = $owner["network"];

			// fetch the author
			$author = self::fetchauthor($xpath, $entry, $importer, "atom:author", $contact, true);

			$item["author-name"] = $author["name"];
			$item["author-link"] = $author["link"];
			$item["author-avatar"] = $author["avatar"];

			if ($header["contact-id"] != $author["contact-id"])
				$item["contact-id"] = $author["contact-id"];

			if (($header["network"] != $author["network"]) AND ($author["network"] != ""))
				$item["network"] = $author["network"];

			// Now get the item
			$item["uri"] = $xpath->query('atom:id/text()', $entry)->item(0)->nodeValue;

			$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `uri` = '%s'",
				intval($importer["uid"]), dbesc($item["uri"]));
			if ($r) {
				//logger("Item with uri ".$item["uri"]." for user ".$importer["uid"]." already existed under id ".$r[0]["id"], LOGGER_DEBUG);
				//continue;
			}

			// Is it a reply?
			$inreplyto = $xpath->query('thr:in-reply-to', $entry);
			if (is_object($inreplyto->item(0))) {
				$objecttype = ACTIVITY_OBJ_COMMENT;
				$item["type"] = 'remote-comment';
				$item["gravity"] = GRAVITY_COMMENT;

				foreach($inreplyto->item(0)->attributes AS $attributes) {
					if ($attributes->name == "ref")
						$item["parent-uri"] = $attributes->textContent;
				}
			} else {
				$objecttype = ACTIVITY_OBJ_NOTE;
				$item["parent-uri"] = $item["uri"];
			}

			$item["title"] = $xpath->query('atom:title/text()', $entry)->item(0)->nodeValue;

			$item["created"] = $xpath->query('atom:published/text()', $entry)->item(0)->nodeValue;
			$item["edited"] = $xpath->query('atom:updated/text()', $entry)->item(0)->nodeValue;

			$item["body"] = $xpath->query('dfrn:env/text()', $entry)->item(0)->nodeValue;
			$item["body"] = str_replace(array(' ',"\t","\r","\n"), array('','','',''),$item["body"]);
			// make sure nobody is trying to sneak some html tags by us
			$item["body"] = notags(base64url_decode($item["body"]));

			$item["body"] = limit_body_size($item["body"]);

			/// @todo Do we need the old check for HTML elements?

			// We don't need the content element since "dfrn:env" is always present
			//$item["body"] = $xpath->query('atom:content/text()', $entry)->item(0)->nodeValue;

			$item["last-child"] = $xpath->query('dfrn:comment-allow/text()', $entry)->item(0)->nodeValue;
			$item["location"] = $xpath->query('dfrn:location/text()', $entry)->item(0)->nodeValue;

			$georsspoint = $xpath->query('georss:point', $entry);
			if ($georsspoint)
				$item["coord"] = $georsspoint->item(0)->nodeValue;

			$item["private"] = $xpath->query('dfrn:private/text()', $entry)->item(0)->nodeValue;

			$item["extid"] = $xpath->query('dfrn:extid/text()', $entry)->item(0)->nodeValue;

			if ($xpath->query('dfrn:extid/text()', $entry)->item(0)->nodeValue == "true")
				$item["bookmark"] = true;

			$notice_info = $xpath->query('statusnet:notice_info', $entry);
			if ($notice_info AND ($notice_info->length > 0)) {
				foreach($notice_info->item(0)->attributes AS $attributes) {
					if ($attributes->name == "source")
						$item["app"] = strip_tags($attributes->textContent);
				}
			}

			$item["guid"] = $xpath->query('dfrn:diaspora_guid/text()', $entry)->item(0)->nodeValue;

			// dfrn:diaspora_signature

			$item["verb"] = $xpath->query('activity:verb/text()', $entry)->item(0)->nodeValue;

			if ($xpath->query('activity:object-type/text()', $entry)->item(0)->nodeValue != "")
				$objecttype = $xpath->query('activity:object-type/text()', $entry)->item(0)->nodeValue;

			$item["object-type"] = $objecttype;

			// activity:object

			// activity:target

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
							case "enclosure":
								$enclosure = $href;
								if(strlen($item["attach"]))
									$item["attach"] .= ',';

								$item["attach"] .= '[attach]href="'.$href.'" length="'.$length.'" type="'.$type.'" title="'.$title.'"[/attach]';
								break;
							case "mentioned":
								// Notification check
								if ($importer["nurl"] == normalise_link($href))
									$mention = true;
								break;
						}
				}
			}

			print_r($item);
/*
			if (!$item_id) {
				logger("Error storing item", LOGGER_DEBUG);
				continue;
			}

			logger("Item was stored with id ".$item_id, LOGGER_DEBUG);
			$item["id"] = $item_id;
*/

/*
			if ($mention) {
				$u = q("SELECT `notify-flags`, `language`, `username`, `email` FROM user WHERE uid = %d LIMIT 1", intval($item['uid']));
				$r = q("SELECT `parent` FROM `item` WHERE `id` = %d", intval($item_id));

				notification(array(
					'type'         => NOTIFY_TAGSELF,
					'notify_flags' => $u[0]["notify-flags"],
					'language'     => $u[0]["language"],
					'to_name'      => $u[0]["username"],
					'to_email'     => $u[0]["email"],
					'uid'          => $item["uid"],
					'item'         => $item,
					'link'         => $a->get_baseurl().'/display/'.urlencode(get_item_guid($item_id)),
					'source_name'  => $item["author-name"],
					'source_link'  => $item["author-link"],
					'source_photo' => $item["author-avatar"],
					'verb'         => ACTIVITY_TAG,
					'otype'        => 'item',
					'parent'       => $r[0]["parent"]
				));
			}
*/
		}
	}
}
?>
