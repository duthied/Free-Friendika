<?php
require_once("include/enotify.php");
require_once("include/threads.php");
require_once("include/socgraph.php");
require_once("include/items.php");
require_once("include/tags.php");
require_once("include/files.php");

class dfrn2 {

	const DFRN_TOP_LEVEL = 0;
	const DFRN_REPLY = 1;
	const DFRN_REPLY_RC = 2;

	/**
	 * @brief Add new birthday event for this person
	 *
	 * @param array $contact Contact record
	 * @param string $birthday Birthday of the contact
	 *
	 */
	private function birthday_event($contact, $birthday) {

		logger("updating birthday: ".$birthday." for contact ".$contact["id"]);

		$bdtext = sprintf(t("%s\'s birthday"), $contact["name"]);
		$bdtext2 = sprintf(t("Happy Birthday %s"), " [url=".$contact["url"]."]".$contact["name"]."[/url]") ;


		$r = q("INSERT INTO `event` (`uid`,`cid`,`created`,`edited`,`start`,`finish`,`summary`,`desc`,`type`)
			VALUES ( %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s') ",
			intval($contact["uid"]),
			intval($contact["id"]),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			dbesc(datetime_convert("UTC","UTC", $birthday)),
			dbesc(datetime_convert("UTC","UTC", $birthday." + 1 day ")),
			dbesc($bdtext),
			dbesc($bdtext2),
			dbesc("birthday")
		);
	}

	/**
	 * @brief Fetch the author data from head or entry items
	 *
	 * @param object $xpath XPath object
	 * @param object $context In which context should the data be searched
	 * @param array $importer Record of the importer user mixed with contact of the content
	 * @param string $element Element name from which the data is fetched
	 * @param bool $onlyfetch Should the data only be fetched or should it update the contact record as well
	 *
	 * @return Returns an array with relevant data of the author
	 */
	private function fetchauthor($xpath, $context, $importer, $element, $onlyfetch) {

		$author = array();
		$author["name"] = $xpath->evaluate($element."/atom:name/text()", $context)->item(0)->nodeValue;
		$author["link"] = $xpath->evaluate($element."/atom:uri/text()", $context)->item(0)->nodeValue;

		$r = q("SELECT `id`, `uid`, `network`, `avatar-date`, `name-date`, `uri-date`, `addr`,
				`name`, `nick`, `about`, `location`, `keywords`, `bdyear`, `bd`
				FROM `contact` WHERE `uid` = %d AND `nurl` = '%s' AND `network` != '%s'",
			intval($importer["uid"]), dbesc(normalise_link($author["link"])), dbesc(NETWORK_STATUSNET));
		if ($r) {
			$contact = $r[0];
			$author["contact-id"] = $r[0]["id"];
			$author["network"] = $r[0]["network"];
		} else {
			$author["contact-id"] = $importer["id"];
			$author["network"] = $importer["network"];
			$onlyfetch = true;
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
				if ($attributes->name == "updated")
					$contact["avatar-date"] = $attributes->textContent;
			}
			if (($width > 0) AND ($href != ""))
				$avatarlist[$width] = $href;
		}
		if (count($avatarlist) > 0) {
			krsort($avatarlist);
			$author["avatar"] = current($avatarlist);
		}

		//$onlyfetch = true; // Test

		if ($r AND !$onlyfetch) {

			// When was the last change to name or uri?
			$name_element = $xpath->query($element."/atom:name", $context)->item(0);
			foreach($name_element->attributes AS $attributes)
				if ($attributes->name == "updated")
					$contact["name-date"] = $attributes->textContent;

			$link_element = $xpath->query($element."/atom:link", $context)->item(0);
			foreach($link_element->attributes AS $attributes)
				if ($attributes->name == "updated")
					$contact["uri-date"] = $attributes->textContent;

			// Update contact data
			$value = $xpath->evaluate($element."/dfrn:handle/text()", $context)->item(0)->nodeValue;
			if ($value != "")
				$contact["addr"] = $value;

			$value = $xpath->evaluate($element."/poco:displayName/text()", $context)->item(0)->nodeValue;
			if ($value != "")
				$contact["name"] = $value;

			$value = $xpath->evaluate($element."/poco:preferredUsername/text()", $context)->item(0)->nodeValue;
			if ($value != "")
				$contact["nick"] = $value;

			$value = $xpath->evaluate($element."/poco:note/text()", $context)->item(0)->nodeValue;
			if ($value != "")
				$contact["about"] = $value;

			$value = $xpath->evaluate($element."/poco:address/poco:formatted/text()", $context)->item(0)->nodeValue;
			if ($value != "")
				$contact["location"] = $value;

			/// @todo Add support for the following fields that we don't support by now in the contact table:
			/// - poco:utcOffset
			/// - poco:ims
			/// - poco:urls
			/// - poco:locality
			/// - poco:region
			/// - poco:country

			// Save the keywords into the contact table
			$tags = array();
			$tagelements = $xpath->evaluate($element."/poco:tags/text()", $context);
			foreach($tagelements AS $tag)
				$tags[$tag->nodeValue] = $tag->nodeValue;

			if (count($tags))
				$contact["keywords"] = implode(", ", $tags);

			// "dfrn:birthday" contains the birthday converted to UTC
			$old_bdyear = $contact["bdyear"];

			$birthday = $xpath->evaluate($element."/dfrn:birthday/text()", $context)->item(0)->nodeValue;

			if (strtotime($birthday) > time()) {
				$bd_timestamp = strtotime($birthday);

				$contact["bdyear"] = date("Y", $bd_timestamp);
			}

			// "poco:birthday" is the birthday in the format "yyyy-mm-dd"
			$value = $xpath->evaluate($element."/poco:birthday/text()", $context)->item(0)->nodeValue;

			if (!in_array($value, array("", "0000-00-00"))) {
				$bdyear = date("Y");
				$value = str_replace("0000", $bdyear, $value);

				if (strtotime($value) < time()) {
					$value = str_replace($bdyear, $bdyear + 1, $value);
					$bdyear = $bdyear + 1;
				}

				$contact["bd"] = $value;
			}

			if ($old_bdyear != $contact["bdyear"])
				self::birthday_event($contact, $birthday);

			// Get all field names
			$fields = array();
			foreach ($r[0] AS $field => $data)
				$fields[$field] = $data;

			unset($fields["id"]);
			unset($fields["uid"]);
			unset($fields["avatar-date"]);
			unset($fields["name-date"]);
			unset($fields["uri-date"]);

			 // Update check for this field has to be done differently
			$datefields = array("name-date", "uri-date");
			foreach ($datefields AS $field)
				if (strtotime($contact[$field]) > strtotime($r[0][$field]))
					$update = true;

			foreach ($fields AS $field => $data)
				if ($contact[$field] != $r[0][$field]) {
					logger("Difference for contact ".$contact["id"]." in field '".$field."'. Old value: '".$contact[$field]."', new value '".$r[0][$field]."'", LOGGER_DEBUG);
					$update = true;
				}

			if ($update) {
				logger("Update contact data for contact ".$contact["id"], LOGGER_DEBUG);

				q("UPDATE `contact` SET `name` = '%s', `nick` = '%s', `about` = '%s', `location` = '%s',
					`addr` = '%s', `keywords` = '%s', `bdyear` = '%s', `bd` = '%s',
					`name-date`  = '%s', `uri-date` = '%s'
					WHERE `id` = %d AND `network` = '%s'",
					dbesc($contact["name"]), dbesc($contact["nick"]), dbesc($contact["about"]), dbesc($contact["location"]),
					dbesc($contact["addr"]), dbesc($contact["keywords"]), dbesc($contact["bdyear"]),
					dbesc($contact["bd"]), dbesc($contact["name-date"]), dbesc($contact["uri-date"]),
					intval($contact["id"]), dbesc($contact["network"]));
			}

			update_contact_avatar($author["avatar"], $importer["uid"], $contact["id"],
						(strtotime($contact["avatar-date"]) > strtotime($r[0]["avatar-date"])));

			$contact["generation"] = 2;
			$contact["photo"] = $author["avatar"];
			update_gcontact($contact);
		}

		return($author);
	}

	private function transform_activity($xpath, $activity, $element) {
		if (!is_object($activity))
			return "";

		$obj_doc = new DOMDocument("1.0", "utf-8");
		$obj_doc->formatOutput = true;

		$obj_element = $obj_doc->createElementNS(NAMESPACE_ATOM1, $element);

		$activity_type = $xpath->query("activity:object-type/text()", $activity)->item(0)->nodeValue;
		xml_add_element($obj_doc, $obj_element, "type", $activity_type);

		$id = $xpath->query("atom:id", $activity)->item(0);
		if (is_object($id))
			$obj_element->appendChild($obj_doc->importNode($id, true));

		$title = $xpath->query("atom:title", $activity)->item(0);
		if (is_object($title))
			$obj_element->appendChild($obj_doc->importNode($title, true));

		$link = $xpath->query("atom:link", $activity)->item(0);
		if (is_object($link))
			$obj_element->appendChild($obj_doc->importNode($link, true));

		$content = $xpath->query("atom:content", $activity)->item(0);
		if (is_object($content))
			$obj_element->appendChild($obj_doc->importNode($content, true));

		$obj_doc->appendChild($obj_element);

		$objxml = $obj_doc->saveXML($obj_element);

		// @todo This isn't totally clean. We should find a way to transform the namespaces
		$objxml = str_replace("<".$element.' xmlns="http://www.w3.org/2005/Atom">', "<".$element.">", $objxml);
		return($objxml);
	}

	private function process_mail($xpath, $mail, $importer) {

		logger("Processing mails");

		$msg = array();
		$msg["uid"] = $importer["importer_uid"];
		$msg["from-name"] = $xpath->query("dfrn:sender/dfrn:name/text()", $mail)->item(0)->nodeValue;
		$msg["from-url"] = $xpath->query("dfrn:sender/dfrn:uri/text()", $mail)->item(0)->nodeValue;
		$msg["from-photo"] = $xpath->query("dfrn:sender/dfrn:avatar/text()", $mail)->item(0)->nodeValue;
		$msg["contact-id"] = $importer["id"];
		$msg["uri"] = $xpath->query("dfrn:id/text()", $mail)->item(0)->nodeValue;
		$msg["parent-uri"] = $xpath->query("dfrn:in-reply-to/text()", $mail)->item(0)->nodeValue;
		$msg["created"] = $xpath->query("dfrn:sentdate/text()", $mail)->item(0)->nodeValue;
		$msg["title"] = $xpath->query("dfrn:subject/text()", $mail)->item(0)->nodeValue;
		$msg["body"] = $xpath->query("dfrn:content/text()", $mail)->item(0)->nodeValue;
		$msg["seen"] = 0;
		$msg["replied"] = 0;

		dbesc_array($msg);

		$r = dbq("INSERT INTO `mail` (`".implode("`, `", array_keys($msg))."`) VALUES ('".implode("', '", array_values($msg))."')");

		// send notifications.

		$notif_params = array(
			"type" => NOTIFY_MAIL,
			"notify_flags" => $importer["notify-flags"],
			"language" => $importer["language"],
			"to_name" => $importer["username"],
			"to_email" => $importer["email"],
			"uid" => $importer["importer_uid"],
			"item" => $msg,
			"source_name" => $msg["from-name"],
			"source_link" => $importer["url"],
			"source_photo" => $importer["thumb"],
			"verb" => ACTIVITY_POST,
			"otype" => "mail"
		);

		notification($notif_params);

		logger("Mail is processed, notification was sent.");
	}

	private function process_suggestion($xpath, $suggestion, $importer) {

		logger("Processing suggestions");

		$suggest = array();
		$suggest["uid"] = $importer["importer_uid"];
		$suggest["cid"] = $importer["id"];
		$suggest["url"] = $xpath->query("dfrn:url/text()", $suggestion)->item(0)->nodeValue;
		$suggest["name"] = $xpath->query("dfrn:name/text()", $suggestion)->item(0)->nodeValue;
		$suggest["photo"] = $xpath->query("dfrn:photo/text()", $suggestion)->item(0)->nodeValue;
		$suggest["request"] = $xpath->query("dfrn:request/text()", $suggestion)->item(0)->nodeValue;
		$suggest["body"] = $xpath->query("dfrn:note/text()", $suggestion)->item(0)->nodeValue;

		// Does our member already have a friend matching this description?

		$r = q("SELECT `id` FROM `contact` WHERE `name` = '%s' AND `nurl` = '%s' AND `uid` = %d LIMIT 1",
			dbesc($suggest["name"]),
			dbesc(normalise_link($suggest["url"])),
			intval($suggest["uid"])
		);
		if(count($r))
			return false;

		// Do we already have an fcontact record for this person?

		$fid = 0;
		$r = q("SELECT `id` FROM `fcontact` WHERE `url` = '%s' AND `name` = '%s' AND `request` = '%s' LIMIT 1",
			dbesc($suggest["url"]),
			dbesc($suggest["name"]),
			dbesc($suggest["request"])
		);
		if(count($r)) {
			$fid = $r[0]["id"];

			// OK, we do. Do we already have an introduction for this person ?
			$r = q("SELECT `id` FROM `intro` WHERE `uid` = %d AND `fid` = %d LIMIT 1",
				intval($suggest["uid"]),
				intval($fid)
			);
			if(count($r))
				return false;
		}
		if(!$fid)
			$r = q("INSERT INTO `fcontact` (`name`,`url`,`photo`,`request`) VALUES ('%s', '%s', '%s', '%s')",
			dbesc($suggest["name"]),
			dbesc($suggest["url"]),
			dbesc($suggest["photo"]),
			dbesc($suggest["request"])
		);
		$r = q("SELECT `id` FROM `fcontact` WHERE `url` = '%s' AND `name` = '%s' AND `request` = '%s' LIMIT 1",
			dbesc($suggest["url"]),
			dbesc($suggest["name"]),
			dbesc($suggest["request"])
		);
		if(count($r))
			$fid = $r[0]["id"];
		else
			// database record did not get created. Quietly give up.
			return false;


		$hash = random_string();

		$r = q("INSERT INTO `intro` (`uid`, `fid`, `contact-id`, `note`, `hash`, `datetime`, `blocked`)
			VALUES(%d, %d, %d, '%s', '%s', '%s', %d)",
			intval($suggest["uid"]),
			intval($fid),
			intval($suggest["cid"]),
			dbesc($suggest["body"]),
			dbesc($hash),
			dbesc(datetime_convert()),
			intval(0)
		);

		notification(array(
			"type"         => NOTIFY_SUGGEST,
			"notify_flags" => $importer["notify-flags"],
			"language"     => $importer["language"],
			"to_name"      => $importer["username"],
			"to_email"     => $importer["email"],
			"uid"          => $importer["importer_uid"],
			"item"         => $suggest,
			"link"         => App::get_baseurl()."/notifications/intros",
			"source_name"  => $importer["name"],
			"source_link"  => $importer["url"],
			"source_photo" => $importer["photo"],
			"verb"         => ACTIVITY_REQ_FRIEND,
			"otype"        => "intro"
		));

		return true;

	}

	private function process_relocation($xpath, $relocation, $importer) {

		logger("Processing relocations");

		$relocate = array();
		$relocate["uid"] = $importer["importer_uid"];
		$relocate["cid"] = $importer["id"];
		$relocate["url"] = $xpath->query("dfrn:url/text()", $relocation)->item(0)->nodeValue;
		$relocate["name"] = $xpath->query("dfrn:name/text()", $relocation)->item(0)->nodeValue;
		$relocate["photo"] = $xpath->query("dfrn:photo/text()", $relocation)->item(0)->nodeValue;
		$relocate["thumb"] = $xpath->query("dfrn:thumb/text()", $relocation)->item(0)->nodeValue;
		$relocate["micro"] = $xpath->query("dfrn:micro/text()", $relocation)->item(0)->nodeValue;
		$relocate["request"] = $xpath->query("dfrn:request/text()", $relocation)->item(0)->nodeValue;
		$relocate["confirm"] = $xpath->query("dfrn:confirm/text()", $relocation)->item(0)->nodeValue;
		$relocate["notify"] = $xpath->query("dfrn:notify/text()", $relocation)->item(0)->nodeValue;
		$relocate["poll"] = $xpath->query("dfrn:poll/text()", $relocation)->item(0)->nodeValue;
		$relocate["sitepubkey"] = $xpath->query("dfrn:sitepubkey/text()", $relocation)->item(0)->nodeValue;

		// update contact
		$r = q("SELECT `photo`, `url` FROM `contact` WHERE `id` = %d AND `uid` = %d;",
			intval($importer["id"]),
			intval($importer["importer_uid"]));
		if (!$r)
			return false;

		$old = $r[0];

		$x = q("UPDATE `contact` SET
					`name` = '%s',
					`photo` = '%s',
					`thumb` = '%s',
					`micro` = '%s',
					`url` = '%s',
					`nurl` = '%s',
					`request` = '%s',
					`confirm` = '%s',
					`notify` = '%s',
					`poll` = '%s',
					`site-pubkey` = '%s'
			WHERE `id` = %d AND `uid` = %d;",
					dbesc($relocate["name"]),
					dbesc($relocate["photo"]),
					dbesc($relocate["thumb"]),
					dbesc($relocate["micro"]),
					dbesc($relocate["url"]),
					dbesc(normalise_link($relocate["url"])),
					dbesc($relocate["request"]),
					dbesc($relocate["confirm"]),
					dbesc($relocate["notify"]),
					dbesc($relocate["poll"]),
					dbesc($relocate["sitepubkey"]),
					intval($importer["id"]),
					intval($importer["importer_uid"]));

		if ($x === false)
			return false;

		// update items
		$fields = array(
			'owner-link' => array($old["url"], $relocate["url"]),
			'author-link' => array($old["url"], $relocate["url"]),
			'owner-avatar' => array($old["photo"], $relocate["photo"]),
			'author-avatar' => array($old["photo"], $relocate["photo"]),
			);
		foreach ($fields as $n=>$f){
			$x = q("UPDATE `item` SET `%s` = '%s' WHERE `%s` = '%s' AND `uid` = %d",
					$n, dbesc($f[1]),
					$n, dbesc($f[0]),
					intval($importer["importer_uid"]));
				if ($x === false)
					return false;
			}

		/// @TODO
		/// merge with current record, current contents have priority
		/// update record, set url-updated
		/// update profile photos
		/// schedule a scan?
		return true;
	}

	private function update_content($current, $item, $importer, $entrytype) {
		if (edited_timestamp_is_newer($current, $item)) {

			// do not accept (ignore) an earlier edit than one we currently have.
			if(datetime_convert("UTC","UTC",$item["edited"]) < $current["edited"])
				return;

			$r = q("UPDATE `item` SET `title` = '%s', `body` = '%s', `tag` = '%s', `edited` = '%s', `changed` = '%s' WHERE `uri` = '%s' AND `uid` = %d",
				dbesc($item["title"]),
				dbesc($item["body"]),
				dbesc($item["tag"]),
				dbesc(datetime_convert("UTC","UTC",$item["edited"])),
				dbesc(datetime_convert()),
				dbesc($item["uri"]),
				intval($importer["importer_uid"])
			);
			create_tags_from_itemuri($item["uri"], $importer["importer_uid"]);
			update_thread_uri($item["uri"], $importer["importer_uid"]);

			if ($entrytype == DFRN_REPLY_RC)
				proc_run("php", "include/notifier.php","comment-import", $current["id"]);
		}

		// update last-child if it changes
		if($item["last-child"] AND ($item["last-child"] != $current["last-child"])) {
			$r = q("UPDATE `item` SET `last-child` = 0, `changed` = '%s' WHERE `parent-uri` = '%s' AND `uid` = %d",
				dbesc(datetime_convert()),
				dbesc($item["parent-uri"]),
				intval($importer["importer_uid"])
			);
			$r = q("UPDATE `item` SET `last-child` = %d , `changed` = '%s' WHERE `uri` = '%s' AND `uid` = %d",
				intval($item["last-child"]),
				dbesc(datetime_convert()),
				dbesc($item["uri"]),
				intval($importer["importer_uid"])
			);
		}
	}

	private function get_entry_type($importer, $item) {
		if ($item["parent-uri"] != $item["uri"]) {
			$community = false;

			if($importer["page-flags"] == PAGE_COMMUNITY || $importer["page-flags"] == PAGE_PRVGROUP) {
				$sql_extra = "";
				$community = true;
				logger("possible community action");
			} else
				$sql_extra = " AND `contact`.`self` AND `item`.`wall` ";

			// was the top-level post for this action written by somebody on this site?
			// Specifically, the recipient?

			$is_a_remote_action = false;

			$r = q("SELECT `item`.`parent-uri` FROM `item`
				WHERE `item`.`uri` = '%s'
				LIMIT 1",
				dbesc($item["parent-uri"])
			);
			if($r && count($r)) {
				$r = q("SELECT `item`.`forum_mode`, `item`.`wall` FROM `item`
					INNER JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
					WHERE `item`.`uri` = '%s' AND (`item`.`parent-uri` = '%s' OR `item`.`thr-parent` = '%s')
					AND `item`.`uid` = %d
					$sql_extra
					LIMIT 1",
					dbesc($r[0]["parent-uri"]),
					dbesc($r[0]["parent-uri"]),
					dbesc($r[0]["parent-uri"]),
					intval($importer["importer_uid"])
				);
				if($r && count($r))
					$is_a_remote_action = true;
			}

			// Does this have the characteristics of a community or private group action?
			// If it's an action to a wall post on a community/prvgroup page it's a
			// valid community action. Also forum_mode makes it valid for sure.
			// If neither, it's not.

			if($is_a_remote_action && $community) {
				if((!$r[0]["forum_mode"]) && (!$r[0]["wall"])) {
					$is_a_remote_action = false;
					logger("not a community action");
				}
			}

			if ($is_a_remote_action)
				return DFRN_REPLY_RC;
			else
				return DFRN_REPLY;

		} else
			return DFRN_TOP_LEVEL;

	}

	private function do_poke($item, $importer, $posted_id) {
		$verb = urldecode(substr($item["verb"],strpos($item["verb"], "#")+1));
		if(!$verb)
			return;
		$xo = parse_xml_string($item["object"],false);

		if(($xo->type == ACTIVITY_OBJ_PERSON) && ($xo->id)) {

			// somebody was poked/prodded. Was it me?
			$links = parse_xml_string("<links>".unxmlify($xo->link)."</links>",false);

			foreach($links->link as $l) {
				$atts = $l->attributes();
				switch($atts["rel"]) {
					case "alternate":
						$Blink = $atts["href"];
						break;
					default:
						break;
				}
			}
			if($Blink && link_compare($Blink,$a->get_baseurl()."/profile/".$importer["nickname"])) {

				// send a notification
				notification(array(
					"type"         => NOTIFY_POKE,
					"notify_flags" => $importer["notify-flags"],
					"language"     => $importer["language"],
					"to_name"      => $importer["username"],
					"to_email"     => $importer["email"],
					"uid"          => $importer["importer_uid"],
					"item"         => $item,
					"link"         => $a->get_baseurl()."/display/".urlencode(get_item_guid($posted_id)),
					"source_name"  => stripslashes($item["author-name"]),
					"source_link"  => $item["author-link"],
					"source_photo" => ((link_compare($item["author-link"],$importer["url"]))
						? $importer["thumb"] : $item["author-avatar"]),
					"verb"         => $item["verb"],
					"otype"        => "person",
					"activity"     => $verb,
					"parent"       => $item["parent"]
				));
			}
		}
	}

	private function process_entry($header, $xpath, $entry, $importer) {

		logger("Processing entries");

		$item = $header;

		// Get the uri
		$item["uri"] = $xpath->query("atom:id/text()", $entry)->item(0)->nodeValue;

		// Fetch the owner
		$owner = self::fetchauthor($xpath, $entry, $importer, "dfrn:owner", true);

		$item["owner-name"] = $owner["name"];
		$item["owner-link"] = $owner["link"];
		$item["owner-avatar"] = $owner["avatar"];

		// fetch the author
		$author = self::fetchauthor($xpath, $entry, $importer, "atom:author", true);

		$item["author-name"] = $author["name"];
		$item["author-link"] = $author["link"];
		$item["author-avatar"] = $author["avatar"];

		$item["title"] = $xpath->query("atom:title/text()", $entry)->item(0)->nodeValue;

		$item["created"] = $xpath->query("atom:published/text()", $entry)->item(0)->nodeValue;
		$item["edited"] = $xpath->query("atom:updated/text()", $entry)->item(0)->nodeValue;

		$item["body"] = $xpath->query("dfrn:env/text()", $entry)->item(0)->nodeValue;
		$item["body"] = str_replace(array(' ',"\t","\r","\n"), array('','','',''),$item["body"]);
		// make sure nobody is trying to sneak some html tags by us
		$item["body"] = notags(base64url_decode($item["body"]));

		$item["body"] = limit_body_size($item["body"]);

		/// @todo Do we need the old check for HTML elements?

		// We don't need the content element since "dfrn:env" is always present
		//$item["body"] = $xpath->query("atom:content/text()", $entry)->item(0)->nodeValue;

		$item["last-child"] = $xpath->query("dfrn:comment-allow/text()", $entry)->item(0)->nodeValue;
		$item["location"] = $xpath->query("dfrn:location/text()", $entry)->item(0)->nodeValue;

		$georsspoint = $xpath->query("georss:point", $entry);
		if ($georsspoint)
			$item["coord"] = $georsspoint->item(0)->nodeValue;

		$item["private"] = $xpath->query("dfrn:private/text()", $entry)->item(0)->nodeValue;

		$item["extid"] = $xpath->query("dfrn:extid/text()", $entry)->item(0)->nodeValue;

		if ($xpath->query("dfrn:extid/text()", $entry)->item(0)->nodeValue == "true")
			$item["bookmark"] = true;

		$notice_info = $xpath->query("statusnet:notice_info", $entry);
		if ($notice_info AND ($notice_info->length > 0)) {
			foreach($notice_info->item(0)->attributes AS $attributes) {
				if ($attributes->name == "source")
					$item["app"] = strip_tags($attributes->textContent);
			}
		}

		$item["guid"] = $xpath->query("dfrn:diaspora_guid/text()", $entry)->item(0)->nodeValue;

		// We store the data from "dfrn:diaspora_signature" in a different table, this is done in "item_store"
		$item["dsprsig"] = unxmlify($xpath->query("dfrn:diaspora_signature/text()", $entry)->item(0)->nodeValue);

		$item["verb"] = $xpath->query("activity:verb/text()", $entry)->item(0)->nodeValue;

		if ($xpath->query("activity:object-type/text()", $entry)->item(0)->nodeValue != "")
			$item["object-type"] = $xpath->query("activity:object-type/text()", $entry)->item(0)->nodeValue;

		$object = $xpath->query("activity:object", $entry)->item(0);
		$item["object"] = self::transform_activity($xpath, $object, "object");

		$target = $xpath->query("activity:target", $entry)->item(0);
		$item["target"] = self::transform_activity($xpath, $target, "target");

		$categories = $xpath->query("atom:category", $entry);
		if ($categories) {
			foreach ($categories AS $category) {
				foreach($category->attributes AS $attributes)
					if ($attributes->name == "term") {
						$term = $attributes->textContent;
						if(strlen($item["tag"]))
							$item["tag"] .= ",";

						$item["tag"] .= "#[url=".App::get_baseurl()."/search?tag=".$term."]".$term."[/url]";
					}
			}
		}

		$enclosure = "";

		$links = $xpath->query("atom:link", $entry);
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
								$item["attach"] .= ",";

							$item["attach"] .= '[attach]href="'.$href.'" length="'.$length.'" type="'.$type.'" title="'.$title.'"[/attach]';
							break;
					}
			}
		}

		// Is it a reply or a top level posting?
		$item["parent-uri"] = $item["uri"];

		$inreplyto = $xpath->query("thr:in-reply-to", $entry);
		if (is_object($inreplyto->item(0)))
			foreach($inreplyto->item(0)->attributes AS $attributes)
				if ($attributes->name == "ref")
					$item["parent-uri"] = $attributes->textContent;

		// Get the type of the item (Top level post, reply or remote reply)
		$entrytype = self::get_entry_type($importer, $item);

		// Now assign the rest of the values that depend on the type of the message
		if (in_array($entrytype, array(DFRN_REPLY, DFRN_REPLY_RC))) {
			if (!isset($item["object-type"]))
				$item["object-type"] = ACTIVITY_OBJ_COMMENT;

			if ($item["contact-id"] != $owner["contact-id"])
				$item["contact-id"] = $owner["contact-id"];

			if (($item["network"] != $owner["network"]) AND ($owner["network"] != ""))
				$item["network"] = $owner["network"];

			if ($item["contact-id"] != $author["contact-id"])
				$item["contact-id"] = $author["contact-id"];

			if (($item["network"] != $author["network"]) AND ($author["network"] != ""))
				$item["network"] = $author["network"];
		}

		if ($entrytype == DFRN_REPLY_RC) {
			$item["type"] = "remote-comment";
			$item["wall"] = 1;
		} else {
			// The Diaspora signature is only stored in replies
			// Since this isn't a field in the item table this would create a bug when inserting this in the item table
			unset($item["dsprsig"]);

			if (!isset($item["object-type"]))
				$item["object-type"] = ACTIVITY_OBJ_NOTE;

			if ($item["object-type"] === ACTIVITY_OBJ_EVENT) {
				$ev = bbtoevent($item["body"]);
				if((x($ev, "desc") || x($ev, "summary")) && x($ev, "start")) {
					$ev["cid"] = $importer["id"];
					$ev["uid"] = $importer["uid"];
					$ev["uri"] = $item["uri"];
					$ev["edited"] = $item["edited"];
					$ev['private'] = $item['private'];
					$ev["guid"] = $item["guid"];

					$r = q("SELECT `id` FROM `event` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
						dbesc($item["uri"]),
						intval($importer["uid"])
					);
					if(count($r))
						$ev["id"] = $r[0]["id"];
						$xyz = event_store($ev);
							return;
				}
			}
		}

		$r = q("SELECT `id`, `uid`, `last-child`, `edited`, `body` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
			dbesc($item["uri"]),
			intval($importer["importer_uid"])
		);

		// Update content if 'updated' changes
		if(count($r)) {
			self::update_content($r[0], $item, $importer, $entrytype);
			return;
		}

		if (in_array($entrytype, array(DFRN_REPLY, DFRN_REPLY_RC))) {
			if($importer["rel"] == CONTACT_IS_FOLLOWER)
				return;

			if(($item["verb"] === ACTIVITY_LIKE)
				|| ($item["verb"] === ACTIVITY_DISLIKE)
				|| ($item["verb"] === ACTIVITY_ATTEND)
				|| ($item["verb"] === ACTIVITY_ATTENDNO)
				|| ($item["verb"] === ACTIVITY_ATTENDMAYBE)) {
				$is_like = true;
				$item["type"] = "activity";
				$item["gravity"] = GRAVITY_LIKE;
				// only one like or dislike per person
				// splitted into two queries for performance issues
				$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `author-link` = '%s' AND `verb` = '%s' AND `parent-uri` = '%s' AND NOT `deleted` LIMIT 1",
					intval($item["uid"]),
					dbesc($item["author-link"]),
					dbesc($item["verb"]),
					dbesc($item["parent-uri"])
				);
				if($r && count($r))
					return;

				$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `author-link` = '%s' AND `verb` = '%s' AND `thr-parent` = '%s' AND NOT `deleted` LIMIT 1",
					intval($item["uid"]),
					dbesc($item["author-link"]),
					dbesc($item["verb"]),
					dbesc($item["parent-uri"])
				);
				if($r && count($r))
					return;

			} else
				$is_like = false;

			if(($item["verb"] === ACTIVITY_TAG) && ($item["object-type"] === ACTIVITY_OBJ_TAGTERM)) {

				$xo = parse_xml_string($item["object"],false);
				$xt = parse_xml_string($item["target"],false);

				if($xt->type == ACTIVITY_OBJ_NOTE) {
					$r = q("SELECT `id`, `tag` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
						dbesc($xt->id),
						intval($importer["importer_uid"])
					);

					if(!count($r))
						return;

					// extract tag, if not duplicate, add to parent item
					if($xo->content) {
						if(!(stristr($r[0]["tag"],trim($xo->content)))) {
							q("UPDATE `item` SET `tag` = '%s' WHERE `id` = %d",
								dbesc($r[0]["tag"] . (strlen($r[0]["tag"]) ? ',' : '') . '#[url=' . $xo->id . ']'. $xo->content . '[/url]'),
								intval($r[0]["id"])
							);
							create_tags_from_item($r[0]["id"]);
						}
					}
				}
			}

			$posted_id = item_store($item);
			$parent = 0;

			if($posted_id) {

				logger("Reply from contact ".$item["contact-id"]." was stored with id ".$posted_id, LOGGER_DEBUG);

				$item["id"] = $posted_id;

				$r = q("SELECT `parent`, `parent-uri` FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1",
					intval($posted_id),
					intval($importer["importer_uid"])
				);
				if(count($r)) {
					$parent = $r[0]["parent"];
					$parent_uri = $r[0]["parent-uri"];
				}

				if(!$is_like) {
					$r1 = q("UPDATE `item` SET `last-child` = 0, `changed` = '%s' WHERE `uid` = %d AND `parent` = %d",
						dbesc(datetime_convert()),
						intval($importer["importer_uid"]),
						intval($r[0]["parent"])
					);

					$r2 = q("UPDATE `item` SET `last-child` = 1, `changed` = '%s' WHERE `uid` = %d AND `id` = %d",
						dbesc(datetime_convert()),
						intval($importer["importer_uid"]),
						intval($posted_id)
					);
				}

				if($posted_id AND $parent AND ($entrytype == DFRN_REPLY_RC)) {
					logger("Notifying followers about comment ".$posted_id, LOGGER_DEBUG);
					proc_run("php", "include/notifier.php", "comment-import", $posted_id);
				}

				return true;
			}
		} else {
			if(!link_compare($item["owner-link"],$importer["url"])) {
				// The item owner info is not our contact. It's OK and is to be expected if this is a tgroup delivery,
				// but otherwise there's a possible data mixup on the sender's system.
				// the tgroup delivery code called from item_store will correct it if it's a forum,
				// but we're going to unconditionally correct it here so that the post will always be owned by our contact.
				logger('Correcting item owner.', LOGGER_DEBUG);
				$item["owner-name"]   = $importer["senderName"];
				$item["owner-link"]   = $importer["url"];
				$item["owner-avatar"] = $importer["thumb"];
			}

			if(($importer["rel"] == CONTACT_IS_FOLLOWER) && (!tgroup_check($importer["importer_uid"], $item)))
				return;

			// This is my contact on another system, but it's really me.
			// Turn this into a wall post.
			$notify = item_is_remote_self($importer, $item);

			$posted_id = item_store($item, false, $notify);

			logger("Item was stored with id ".$posted_id, LOGGER_DEBUG);

			if(stristr($item["verb"],ACTIVITY_POKE))
				self::do_poke($item, $importer, $posted_id);
		}
	}

	private function process_deletion($header, $xpath, $deletion, $importer) {

		logger("Processing deletions");

		foreach($deletion->attributes AS $attributes) {
			if ($attributes->name == "ref")
				$uri = $attributes->textContent;
			if ($attributes->name == "when")
				$when = $attributes->textContent;
		}
		if ($when)
			$when = datetime_convert("UTC", "UTC", $when, "Y-m-d H:i:s");
		else
			$when = datetime_convert("UTC", "UTC", "now", "Y-m-d H:i:s");

		if (!$uri OR !$importer["id"])
			return false;

		/// @todo Only select the used fields
		$r = q("SELECT `item`.*, `contact`.`self` FROM `item` INNER JOIN `contact` on `item`.`contact-id` = `contact`.`id`
				WHERE `uri` = '%s' AND `item`.`uid` = %d AND `contact-id` = %d AND NOT `item`.`file` LIKE '%%[%%' LIMIT 1",
				dbesc($uri),
				intval($importer["uid"]),
				intval($importer["id"])
			);
		if(!count($r)) {
			logger("Item with uri ".$uri." from contact ".$importer["id"]." for user ".$importer["uid"]." wasn't found.", LOGGER_DEBUG);
			return;
		} else {

			$item = $r[0];

			$entrytype = self::get_entry_type($importer, $item);

			if(!$item["deleted"])
				logger('deleting item '.$item["id"].' uri='.$uri, LOGGER_DEBUG);
			else
				return;

			if($item["object-type"] === ACTIVITY_OBJ_EVENT) {
				logger("Deleting event ".$item["event-id"], LOGGER_DEBUG);
				event_delete($item["event-id"]);
			}

			if(($item["verb"] === ACTIVITY_TAG) && ($item["object-type"] === ACTIVITY_OBJ_TAGTERM)) {
				$xo = parse_xml_string($item["object"],false);
				$xt = parse_xml_string($item["target"],false);
				if($xt->type === ACTIVITY_OBJ_NOTE) {
					$i = q("SELECT `id`, `contact-id`, `tag` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
						dbesc($xt->id),
						intval($importer["importer_uid"])
					);
					if(count($i)) {

						// For tags, the owner cannot remove the tag on the author's copy of the post.

						$owner_remove = (($item["contact-id"] == $i[0]["contact-id"]) ? true: false);
						$author_remove = (($item["origin"] && $item["self"]) ? true : false);
						$author_copy = (($item["origin"]) ? true : false);

						if($owner_remove && $author_copy)
							return;
						if($author_remove || $owner_remove) {
							$tags = explode(',',$i[0]["tag"]);
							$newtags = array();
							if(count($tags)) {
								foreach($tags as $tag)
									if(trim($tag) !== trim($xo->body))
										$newtags[] = trim($tag);
							}
							q("UPDATE `item` SET `tag` = '%s' WHERE `id` = %d",
								dbesc(implode(',',$newtags)),
								intval($i[0]["id"])
							);
							create_tags_from_item($i[0]["id"]);
						}
					}
				}
			}

			if($entrytype == DFRN_TOP_LEVEL) {
				$r = q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s',
						`body` = '', `title` = ''
					WHERE `parent-uri` = '%s' AND `uid` = %d",
						dbesc($when),
						dbesc(datetime_convert()),
						dbesc($uri),
						intval($importer["uid"])
					);
				create_tags_from_itemuri($uri, $importer["uid"]);
				create_files_from_itemuri($uri, $importer["uid"]);
				update_thread_uri($uri, $importer["uid"]);
			} else {
				$r = q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s',
						`body` = '', `title` = ''
					WHERE `uri` = '%s' AND `uid` = %d",
						dbesc($when),
						dbesc(datetime_convert()),
						dbesc($uri),
						intval($importer["uid"])
					);
				create_tags_from_itemuri($uri, $importer["uid"]);
				create_files_from_itemuri($uri, $importer["uid"]);
				update_thread_uri($uri, $importer["importer_uid"]);
				if($item["last-child"]) {
					// ensure that last-child is set in case the comment that had it just got wiped.
					q("UPDATE `item` SET `last-child` = 0, `changed` = '%s' WHERE `parent-uri` = '%s' AND `uid` = %d ",
						dbesc(datetime_convert()),
						dbesc($item["parent-uri"]),
						intval($item["uid"])
					);
					// who is the last child now?
					$r = q("SELECT `id` FROM `item` WHERE `parent-uri` = '%s' AND `type` != 'activity' AND `deleted` = 0 AND `moderated` = 0 AND `uid` = %d
						ORDER BY `created` DESC LIMIT 1",
							dbesc($item["parent-uri"]),
							intval($importer["uid"])
					);
					if(count($r)) {
						q("UPDATE `item` SET `last-child` = 1 WHERE `id` = %d",
							intval($r[0]["id"])
						);
					}
				}
				// if this is a relayed delete, propagate it to other recipients

				if($entrytype == DFRN_REPLY_RC) {
					logger("Notifying followers about deletion of post ".$item["id"], LOGGER_DEBUG);
					proc_run("php", "include/notifier.php","drop", $item["id"]);
				}
			}
		}
	}

	/**
	 * @brief Imports a DFRN message
	 *
	 * @param text $xml The DFRN message
	 * @param array $importer Record of the importer user mixed with contact of the content
	 * @param bool $sort_by_date Is used when feeds are polled
	 */
	function import($xml,$importer, $sort_by_date = false) {

		if ($xml == "")
			return;

		if($importer["readonly"]) {
	                // We aren't receiving stuff from this person. But we will quietly ignore them
	                // rather than a blatant "go away" message.
	                logger('ignoring contact '.$importer["id"]);
	                return;
	        }

		$doc = new DOMDocument();
		@$doc->loadXML($xml);

		$xpath = new DomXPath($doc);
		$xpath->registerNamespace("atom", NAMESPACE_ATOM1);
		$xpath->registerNamespace("thr", NAMESPACE_THREAD);
		$xpath->registerNamespace("at", NAMESPACE_TOMB);
		$xpath->registerNamespace("media", NAMESPACE_MEDIA);
		$xpath->registerNamespace("dfrn", NAMESPACE_DFRN);
		$xpath->registerNamespace("activity", NAMESPACE_ACTIVITY);
		$xpath->registerNamespace("georss", NAMESPACE_GEORSS);
		$xpath->registerNamespace("poco", NAMESPACE_POCO);
		$xpath->registerNamespace("ostatus", NAMESPACE_OSTATUS);
		$xpath->registerNamespace("statusnet", NAMESPACE_STATUSNET);

		$header = array();
		$header["uid"] = $importer["uid"];
		$header["network"] = NETWORK_DFRN;
		$header["type"] = "remote";
		$header["wall"] = 0;
		$header["origin"] = 0;
		$header["contact-id"] = $importer["id"];

		// Update the contact table if the data has changed
		// Only the "dfrn:owner" in the head section contains all data
		self::fetchauthor($xpath, $doc->firstChild, $importer, "dfrn:owner", false);

		logger("Import DFRN message for user ".$importer["uid"]." from contact ".$importer["id"], LOGGER_DEBUG);

		// is it a public forum? Private forums aren't supported by now with this method
		$forum = intval($xpath->evaluate("/atom:feed/dfrn:community/text()", $context)->item(0)->nodeValue);

		if ($forum)
			q("UPDATE `contact` SET `forum` = %d WHERE `forum` != %d AND `id` = %d",
				intval($forum), intval($forum),
				intval($importer["id"])
			);

		$mails = $xpath->query("/atom:feed/dfrn:mail");
		foreach ($mails AS $mail)
			self::process_mail($xpath, $mail, $importer);

		$suggestions = $xpath->query("/atom:feed/dfrn:suggest");
		foreach ($suggestions AS $suggestion)
			self::process_suggestion($xpath, $suggestion, $importer);

		$relocations = $xpath->query("/atom:feed/dfrn:relocate");
		foreach ($relocations AS $relocation)
			self::process_relocation($xpath, $relocation, $importer);

		$deletions = $xpath->query("/atom:feed/at:deleted-entry");
		foreach ($deletions AS $deletion)
			self::process_deletion($header, $xpath, $deletion, $importer);

		$entries = $xpath->query("/atom:feed/atom:entry");
		foreach ($entries AS $entry)
			self::process_entry($header, $xpath, $entry, $importer);
	}
}
?>
