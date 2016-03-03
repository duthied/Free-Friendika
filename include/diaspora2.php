<?php
/**
 * @file include/diaspora.php
 * @brief The implementation of the diaspora protocol
 */

require_once("include/items.php");
require_once("include/bb2diaspora.php");
require_once("include/Scrape.php");
require_once("include/Contact.php");
require_once("include/Photo.php");
require_once("include/socgraph.php");
require_once("include/group.php");
require_once("include/api.php");

/**
 * @brief This class contain functions to work with XML data
 *
 */
class xml {
	function from_array($array, &$xml) {

		if (!is_object($xml)) {
			foreach($array as $key => $value) {
				$root = new SimpleXMLElement('<'.$key.'/>');
				array_to_xml($value, $root);

				$dom = dom_import_simplexml($root)->ownerDocument;
				$dom->formatOutput = true;
				return $dom->saveXML();
			}
		}

		foreach($array as $key => $value) {
			if (!is_array($value) AND !is_numeric($key))
				$xml->addChild($key, $value);
			elseif (is_array($value))
				array_to_xml($value, $xml->addChild($key));
		}
	}

	function copy(&$source, &$target, $elementname) {
		if (count($source->children()) == 0)
			$target->addChild($elementname, $source);
		else {
			$child = $target->addChild($elementname);
			foreach ($source->children() AS $childfield => $childentry)
				self::copy($childentry, $child, $childfield);
		}
	}
}

/**
 * @brief This class contain functions to create and send Diaspora XML files
 *
 */
class diaspora {

	/**
	 * @brief Dispatches public messages and find the fitting receivers
	 *
	 * @param array $msg The post that will be dispatched
	 *
	 * @return bool Was the message accepted?
	 */
	public static function dispatch_public($msg) {

		$enabled = intval(get_config("system", "diaspora_enabled"));
		if (!$enabled) {
			logger("diaspora is disabled");
			return false;
		}

		// Use a dummy importer to import the data for the public copy
		$importer = array("uid" => 0, "page-flags" => PAGE_FREELOVE);
		$item_id = self::dispatch($importer,$msg);

		// Now distribute it to the followers
		$r = q("SELECT `user`.* FROM `user` WHERE `user`.`uid` IN
			(SELECT `contact`.`uid` FROM `contact` WHERE `contact`.`network` = '%s' AND `contact`.`addr` = '%s')
			AND NOT `account_expired` AND NOT `account_removed`",
			dbesc(NETWORK_DIASPORA),
			dbesc($msg["author"])
		);
		if(count($r)) {
			foreach($r as $rr) {
				logger("delivering to: ".$rr["username"]);
				self::dispatch($rr,$msg);
			}
		} else
			logger("No subscribers for ".$msg["author"]." ".print_r($msg, true));

		return $item_id;
	}

	/**
	 * @brief Dispatches the different message types to the different functions
	 *
	 * @param array $importer Array of the importer user
	 * @param array $msg The post that will be dispatched
	 *
	 * @return bool Was the message accepted?
	 */
	public static function dispatch($importer, $msg) {

		// The sender is the handle of the contact that sent the message.
		// This will often be different with relayed messages (for example "like" and "comment")
		$sender = $msg["author"];

		if (!diaspora::valid_posting($msg, $fields)) {
			logger("Invalid posting");
			return false;
		}

		$type = $fields->getName();

		switch ($type) {
			case "account_deletion": // Done
				return true;
				//return self::import_account_deletion($importer, $fields);

			case "comment": // Done
				return true;
				//return self::import_comment($importer, $sender, $fields);

			case "conversation":
				//return true;
				return self::import_conversation($importer, $fields);

			case "like": // Done
				return true;
				//return self::import_like($importer, $sender, $fields);

			case "message": // Done
				return true;
				//return self::import_message($importer, $fields);

			case "participation": // Not implemented
				return self::import_participation($importer, $fields);

			case "photo": // Not needed
				return self::import_photo($importer, $fields);

			case "poll_participation": // Not implemented
				return self::import_poll_participation($importer, $fields);

			case "profile": // Done
				return true;
				//return self::import_profile($importer, $fields);

			case "request":
				//return true;
				return self::import_request($importer, $fields);

			case "reshare": // Done
				return true;
				//return self::import_reshare($importer, $fields);

			case "retraction": // Done
				return true;
				//return self::import_retraction($importer, $sender, $fields);

			case "status_message":
				//return true;
				return self::import_status_message($importer, $fields);

			default:
				logger("Unknown message type ".$type);
				return false;
		}

		return true;
	}

	/**
	 * @brief Checks if a posting is valid and fetches the data fields.
	 *
	 * This function does not only check the signature.
	 * It also does the conversion between the old and the new diaspora format.
	 *
	 * @param array $msg Array with the XML, the sender handle and the sender signature
	 * @param object $fields SimpleXML object that contains the posting when it is valid
	 *
	 * @return bool Is the posting valid?
	 */
	private function valid_posting($msg, &$fields) {

		$data = parse_xml_string($msg["message"], false);

		if (!is_object($data))
			return false;

		$first_child = $data->getName();

		// Is this the new or the old version?
		if ($data->getName() == "XML") {
			$oldXML = true;
			foreach ($data->post->children() as $child)
				$element = $child;
		} else {
			$oldXML = false;
			$element = $data;
		}

		$type = $element->getName();
		$orig_type = $type;

		// All retractions are handled identically from now on.
		// In the new version there will only be "retraction".
		if (in_array($type, array("signed_retraction", "relayable_retraction")))
			$type = "retraction";

		$fields = new SimpleXMLElement("<".$type."/>");

		$signed_data = "";

		foreach ($element->children() AS $fieldname => $entry) {
			if ($oldXML) {
				// Translation for the old XML structure
				if ($fieldname == "diaspora_handle")
					$fieldname = "author";

				if ($fieldname == "participant_handles")
					$fieldname = "participants";

				if (in_array($type, array("like", "participation"))) {
					if ($fieldname == "target_type")
						$fieldname = "parent_type";
				}

				if ($fieldname == "sender_handle")
					$fieldname = "author";

				if ($fieldname == "recipient_handle")
					$fieldname = "recipient";

				if ($fieldname == "root_diaspora_id")
					$fieldname = "root_author";

				if ($type == "retraction") {
					if ($fieldname == "post_guid")
						$fieldname = "target_guid";

					if ($fieldname == "type")
						$fieldname = "target_type";
				}
			}

			if ($fieldname == "author_signature")
				$author_signature = base64_decode($entry);
			elseif ($fieldname == "parent_author_signature")
				$parent_author_signature = base64_decode($entry);
			elseif ($fieldname != "target_author_signature") {
				if ($signed_data != "") {
					$signed_data .= ";";
					$signed_data_parent .= ";";
				}

				$signed_data .= $entry;
			}
			if (!in_array($fieldname, array("parent_author_signature", "target_author_signature")) OR
				($orig_type == "relayable_retraction"))
				xml::copy($entry, $fields, $fieldname);
		}

		// This is something that shouldn't happen at all.
		if (in_array($type, array("status_message", "reshare", "profile")))
			if ($msg["author"] != $fields->author) {
				logger("Message handle is not the same as envelope sender. Quitting this message.");
				return false;
			}

		// Only some message types have signatures. So we quit here for the other types.
		if (!in_array($type, array("comment", "message", "like")))
			return true;

		// No author_signature? This is a must, so we quit.
		if (!isset($author_signature))
			return false;

		if (isset($parent_author_signature)) {
			$key = self::get_key($msg["author"]);

			if (!rsa_verify($signed_data, $parent_author_signature, $key, "sha256"))
				return false;
		}

		$key = self::get_key($fields->author);

		return rsa_verify($signed_data, $author_signature, $key, "sha256");
	}

	/**
	 * @brief Fetches the public key for a given handle
	 *
	 * @param string $handle The handle
	 *
	 * @return string The public key
	 */
	private function get_key($handle) {
		logger("Fetching diaspora key for: ".$handle);

		$r = self::get_person_by_handle($handle);
		if($r)
			return $r["pubkey"];

		return "";
	}

	/**
	 * @brief Fetches data for a given handle
	 *
	 * @param string $handle The handle
	 *
	 * @return array the queried data
	 */
	private function get_person_by_handle($handle) {

		$r = q("SELECT * FROM `fcontact` WHERE `network` = '%s' AND `addr` = '%s' LIMIT 1",
			dbesc(NETWORK_DIASPORA),
			dbesc($handle)
		);
		if (count($r)) {
			$person = $r[0];
			logger("In cache ".print_r($r,true), LOGGER_DEBUG);

			// update record occasionally so it doesn't get stale
			$d = strtotime($person["updated"]." +00:00");
			if ($d < strtotime("now - 14 days"))
				$update = true;
		}

		if (!$person OR $update) {
			logger("create or refresh", LOGGER_DEBUG);
			$r = probe_url($handle, PROBE_DIASPORA);

			// Note that Friendica contacts will return a "Diaspora person"
			// if Diaspora connectivity is enabled on their server
			if (count($r) AND ($r["network"] === NETWORK_DIASPORA)) {
				self::add_fcontact($r, $update);
				$person = $r;
			}
		}
		return $person;
	}

	/**
	 * @brief Updates the fcontact table
	 *
	 * @param array $arr The fcontact data
	 * @param bool $update Update or insert?
	 *
	 * @return string The id of the fcontact entry
	 */
	private function add_fcontact($arr, $update = false) {
		/// @todo Remove this function from include/network.php

		if($update) {
			$r = q("UPDATE `fcontact` SET
					`name` = '%s',
					`photo` = '%s',
					`request` = '%s',
					`nick` = '%s',
					`addr` = '%s',
					`batch` = '%s',
					`notify` = '%s',
					`poll` = '%s',
					`confirm` = '%s',
					`alias` = '%s',
					`pubkey` = '%s',
					`updated` = '%s'
				WHERE `url` = '%s' AND `network` = '%s'",
					dbesc($arr["name"]),
					dbesc($arr["photo"]),
					dbesc($arr["request"]),
					dbesc($arr["nick"]),
					dbesc($arr["addr"]),
					dbesc($arr["batch"]),
					dbesc($arr["notify"]),
					dbesc($arr["poll"]),
					dbesc($arr["confirm"]),
					dbesc($arr["alias"]),
					dbesc($arr["pubkey"]),
					dbesc(datetime_convert()),
					dbesc($arr["url"]),
					dbesc($arr["network"])
				);
		} else {
			$r = q("INSERT INTO `fcontact` (`url`,`name`,`photo`,`request`,`nick`,`addr`,
					`batch`, `notify`,`poll`,`confirm`,`network`,`alias`,`pubkey`,`updated`)
				VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
					dbesc($arr["url"]),
					dbesc($arr["name"]),
					dbesc($arr["photo"]),
					dbesc($arr["request"]),
					dbesc($arr["nick"]),
					dbesc($arr["addr"]),
					dbesc($arr["batch"]),
					dbesc($arr["notify"]),
					dbesc($arr["poll"]),
					dbesc($arr["confirm"]),
					dbesc($arr["network"]),
					dbesc($arr["alias"]),
					dbesc($arr["pubkey"]),
					dbesc(datetime_convert())
				);
		}

		return $r;
	}

	private function get_contact_by_handle($uid, $handle) {
		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `addr` = '%s' LIMIT 1",
			intval($uid),
			dbesc($handle)
		);

		if ($r AND count($r))
			return $r[0];

		$handle_parts = explode("@", $handle);
		$nurl_sql = '%%://' . $handle_parts[1] . '%%/profile/' . $handle_parts[0];
		$r = q("SELECT * FROM `contact` WHERE `network` = '%s' AND `uid` = %d AND `nurl` LIKE '%s' LIMIT 1",
			dbesc(NETWORK_DFRN),
			intval($uid),
			dbesc($nurl_sql)
		);
		if($r AND count($r))
			return $r[0];

		return false;
	}

	private function post_allow($importer, $contact, $is_comment = false) {

		// perhaps we were already sharing with this person. Now they're sharing with us.
		// That makes us friends.
		// Normally this should have handled by getting a request - but this could get lost
		if($contact["rel"] == CONTACT_IS_FOLLOWER && in_array($importer["page-flags"], array(PAGE_FREELOVE))) {
			q("UPDATE `contact` SET `rel` = %d, `writable` = 1 WHERE `id` = %d AND `uid` = %d",
				intval(CONTACT_IS_FRIEND),
				intval($contact["id"]),
				intval($importer["uid"])
			);
			$contact["rel"] = CONTACT_IS_FRIEND;
			logger("defining user ".$contact["nick"]." as friend");
		}

		if(($contact["blocked"]) || ($contact["readonly"]) || ($contact["archive"]))
			return false;
		if($contact["rel"] == CONTACT_IS_SHARING || $contact["rel"] == CONTACT_IS_FRIEND)
			return true;
		if($contact["rel"] == CONTACT_IS_FOLLOWER)
			if(($importer["page-flags"] == PAGE_COMMUNITY) OR $is_comment)
				return true;

		// Messages for the global users are always accepted
		if ($importer["uid"] == 0)
			return true;

		return false;
	}

	private function get_allowed_contact_by_handle($importer, $handle, $is_comment = false) {
		$contact = self::get_contact_by_handle($importer["uid"], $handle);
		if (!$contact) {
			logger("A Contact for handle ".$handle." and user ".$importer["uid"]." was not found");
			return false;
		}

		if (!self::post_allow($importer, $contact, false)) {
			logger("The handle: ".$handle." is not allowed to post to user ".$importer["uid"]);
			return false;
		}
		return $contact;
	}

	private function message_exists($uid, $guid) {
		$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `guid` = '%s' LIMIT 1",
			intval($uid),
			dbesc($guid)
		);

		if(count($r)) {
			logger("message ".$guid." already exists for user ".$uid);
			return false;
		}

		return true;
	}

	private function fetch_guid($item) {
		preg_replace_callback("&\[url=/posts/([^\[\]]*)\](.*)\[\/url\]&Usi",
			function ($match) use ($item){
				return(self::fetch_guid_sub($match, $item));
			},$item["body"]);
	}

	private function fetch_guid_sub($match, $item) {
		if (!self::store_by_guid($match[1], $item["author-link"]))
			self::store_by_guid($match[1], $item["owner-link"]);
	}

	private function store_by_guid($guid, $server, $uid = 0) {
		$serverparts = parse_url($server);
		$server = $serverparts["scheme"]."://".$serverparts["host"];

		logger("Trying to fetch item ".$guid." from ".$server, LOGGER_DEBUG);

		$msg = self::fetch_message($guid, $server);

		if (!$msg)
			return false;

		logger("Successfully fetched item ".$guid." from ".$server, LOGGER_DEBUG);

		// Now call the dispatcher
		return self::dispatch_public($msg);
	}

	private function fetch_message($guid, $server, $level = 0) {

		if ($level > 5)
			return false;

		// This will work for Diaspora and newer Friendica servers
		$source_url = $server."/p/".$guid.".xml";
		$x = fetch_url($source_url);
		if(!$x)
			return false;

		$source_xml = parse_xml_string($x, false);

		if (!is_object($source_xml))
			return false;

		if ($source_xml->post->reshare) {
			// Reshare of a reshare - old Diaspora version
			return self::fetch_message($source_xml->post->reshare->root_guid, $server, ++$level);
		} elseif ($source_xml->getName() == "reshare") {
			// Reshare of a reshare - new Diaspora version
			return self::fetch_message($source_xml->root_guid, $server, ++$level);
		}

		// Fetch the author - for the old and the new Diaspora version
		if ($source_xml->post->status_message->diaspora_handle)
			$author = (string)$source_xml->post->status_message->diaspora_handle;
		elseif ($source_xml->author)
			$author = (string)$source_xml->author;

		if (!$author)
			return false;

		$msg = array("message" => $x, "author" => $author);

		// We don't really need this, but until the work is unfinished we better will keep this
		$msg["key"] = self::get_key($msg["author"]);

		return $msg;
	}

	private function fetch_parent_item($uid, $guid, $author, $contact) {
		$r = q("SELECT `id`, `body`, `wall`, `uri`, `private`, `origin`,
				`author-name`, `author-link`, `author-avatar`,
				`owner-name`, `owner-link`, `owner-avatar`
			FROM `item` WHERE `uid` = %d AND `guid` = '%s' LIMIT 1",
			intval($uid), dbesc($guid));

		if(!count($r)) {
			$result = self::store_by_guid($guid, $contact["url"], $uid);

			if (!$result) {
				$person = self::get_person_by_handle($author);
				$result = self::store_by_guid($guid, $person["url"], $uid);
			}

			if ($result) {
				logger("Fetched missing item ".$guid." - result: ".$result, LOGGER_DEBUG);

				$r = q("SELECT `id`, `body`, `wall`, `uri`, `private`, `origin`,
						`author-name`, `author-link`, `author-avatar`,
						`owner-name`, `owner-link`, `owner-avatar`
					FROM `item` WHERE `uid` = %d AND `guid` = '%s' LIMIT 1",
					intval($uid), dbesc($guid));
			}
		}

		if (!count($r)) {
			logger("parent item not found: parent: ".$guid." item: ".$guid);
			return false;
		} else
			return $r[0];
	}

	private function get_author_contact_by_url($contact, $person, $uid) {

		$r = q("SELECT `id`, `network` FROM `contact` WHERE `nurl` = '%s' AND `uid` = %d LIMIT 1",
			dbesc(normalise_link($person["url"])), intval($uid));
		if ($r) {
			$cid = $r[0]["id"];
			$network = $r[0]["network"];
		} else {
			$cid = $contact["id"];
			$network = NETWORK_DIASPORA;
		}

		return (array("cid" => $cid, "network" => $network));
	}

	public static function is_redmatrix($url) {
		return(strstr($url, "/channel/"));
	}

	private function plink($addr, $guid) {
		$r = q("SELECT `url`, `nick`, `network` FROM `fcontact` WHERE `addr`='%s' LIMIT 1", dbesc($addr));

		// Fallback
		if (!$r)
			return "https://".substr($addr,strpos($addr,"@")+1)."/posts/".$guid;

		// Friendica contacts are often detected as Diaspora contacts in the "fcontact" table
		// So we try another way as well.
		$s = q("SELECT `network` FROM `gcontact` WHERE `nurl`='%s' LIMIT 1", dbesc(normalise_link($r[0]["url"])));
		if ($s)
			$r[0]["network"] = $s[0]["network"];

		if ($r[0]["network"] == NETWORK_DFRN)
			return(str_replace("/profile/".$r[0]["nick"]."/", "/display/".$guid, $r[0]["url"]."/"));

		if (self::is_redmatrix($r[0]["url"]))
			return $r[0]["url"]."/?f=&mid=".$guid;

		return "https://".substr($addr,strpos($addr,"@")+1)."/posts/".$guid;
	}

	private function import_account_deletion($importer, $data) {
		$author = notags(unxmlify($data->author));

		$contact = self::get_contact_by_handle($importer["uid"], $author);
		if (!$contact) {
			logger("cannot find contact for author: ".$author);
			return false;
		}

		// We now remove the contact
		contact_remove($contact["id"]);
		return true;
	}

	private function import_comment($importer, $sender, $data) {
		$guid = notags(unxmlify($data->guid));
		$parent_guid = notags(unxmlify($data->parent_guid));
		$text = unxmlify($data->text);
		$author = notags(unxmlify($data->author));

		$contact = self::get_allowed_contact_by_handle($importer, $sender, true);
		if (!$contact)
			return false;

		if (self::message_exists($importer["uid"], $guid))
			return false;

		$parent_item = self::fetch_parent_item($importer["uid"], $parent_guid, $author, $contact);
		if (!$parent_item)
			return false;

		$person = self::get_person_by_handle($author);
		if (!is_array($person)) {
			logger("unable to find author details");
			return false;
		}

		// Fetch the contact id - if we know this contact
		$author_contact = self::get_author_contact_by_url($contact, $person, $importer["uid"]);

		$datarray = array();

		$datarray["uid"] = $importer["uid"];
		$datarray["contact-id"] = $author_contact["cid"];
		$datarray["network"]  = $author_contact["network"];

		$datarray["author-name"] = $person["name"];
		$datarray["author-link"] = $person["url"];
		$datarray["author-avatar"] = ((x($person,"thumb")) ? $person["thumb"] : $person["photo"]);

		$datarray["owner-name"] = $contact["name"];
		$datarray["owner-link"] = $contact["url"];
		$datarray["owner-avatar"] = ((x($contact,"thumb")) ? $contact["thumb"] : $contact["photo"]);

		$datarray["guid"] = $guid;
		$datarray["uri"] = $author.":".$guid;

		$datarray["type"] = "remote-comment";
		$datarray["verb"] = ACTIVITY_POST;
		$datarray["gravity"] = GRAVITY_COMMENT;
		$datarray["parent-uri"] = $parent_item["uri"];

		$datarray["object-type"] = ACTIVITY_OBJ_COMMENT;
		$datarray["object"] = json_encode($data);

		$datarray["body"] = diaspora2bb($text);

		self::fetch_guid($datarray);

		$message_id = item_store($datarray);
		// print_r($datarray);

		// If we are the origin of the parent we store the original data and notify our followers
		if($message_id AND $parent_item["origin"]) {

			// Formerly we stored the signed text, the signature and the author in different fields.
			// We now store the raw data so that we are more flexible.
			q("INSERT INTO `sign` (`iid`,`signed_text`) VALUES (%d,'%s')",
				intval($message_id),
				dbesc(json_encode($data))
			);

			// notify others
			proc_run("php", "include/notifier.php", "comment-import", $message_id);
		}

		return $message_id;
	}

	private function import_conversation($importer, $data) {
		// @todo
		print_r($data);
		die();
/*
	$guid = notags(unxmlify($xml->guid));
	$subject = notags(unxmlify($xml->subject));
	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));
	$participant_handles = notags(unxmlify($xml->participant_handles));
	$created_at = datetime_convert('UTC','UTC',notags(unxmlify($xml->created_at)));

	$parent_uri = $diaspora_handle . ':' . $guid;

	$messages = $xml->message;

	if(! count($messages)) {
		logger('empty conversation');
		return;
	}

		$contact = self::get_allowed_contact_by_handle($importer, $sender, true)
		if (!$contact)
			return false;

	$conversation = null;

	$c = q("select * from conv where uid = %d and guid = '%s' limit 1",
		intval($importer["uid"]),
		dbesc($guid)
	);
	if(count($c))
		$conversation = $c[0];
	else {
		$r = q("insert into conv (uid,guid,creator,created,updated,subject,recips) values(%d, '%s', '%s', '%s', '%s', '%s', '%s') ",
			intval($importer["uid"]),
			dbesc($guid),
			dbesc($diaspora_handle),
			dbesc(datetime_convert('UTC','UTC',$created_at)),
			dbesc(datetime_convert()),
			dbesc($subject),
			dbesc($participant_handles)
		);
		if($r)
			$c = q("select * from conv where uid = %d and guid = '%s' limit 1",
		intval($importer["uid"]),
	    dbesc($guid)
	);
	    if(count($c))
	    $conversation = $c[0];
	}
	if(! $conversation) {
		logger('diaspora_conversation: unable to create conversation.');
		return;
	}

	foreach($messages as $mesg) {

		$reply = 0;

		$msg_guid = notags(unxmlify($mesg->guid));
		$msg_parent_guid = notags(unxmlify($mesg->parent_guid));
		$msg_parent_author_signature = notags(unxmlify($mesg->parent_author_signature));
		$msg_author_signature = notags(unxmlify($mesg->author_signature));
		$msg_text = unxmlify($mesg->text);
		$msg_created_at = datetime_convert('UTC','UTC',notags(unxmlify($mesg->created_at)));
		$msg_diaspora_handle = notags(unxmlify($mesg->diaspora_handle));
		$msg_conversation_guid = notags(unxmlify($mesg->conversation_guid));
		if($msg_conversation_guid != $guid) {
			logger('diaspora_conversation: message conversation guid does not belong to the current conversation. ' . $xml);
			continue;
		}

		$body = diaspora2bb($msg_text);
		$message_id = $msg_diaspora_handle . ':' . $msg_guid;

		$author_signed_data = $msg_guid . ';' . $msg_parent_guid . ';' . $msg_text . ';' . unxmlify($mesg->created_at) . ';' . $msg_diaspora_handle . ';' . $msg_conversation_guid;

		$author_signature = base64_decode($msg_author_signature);

		if(strcasecmp($msg_diaspora_handle,$msg["author"]) == 0) {
			$person = $contact;
			$key = $msg["key"];
		}
		else {
			$person = find_diaspora_person_by_handle($msg_diaspora_handle); 

			if(is_array($person) && x($person,'pubkey'))
				$key = $person["pubkey"];
			else {
				logger('diaspora_conversation: unable to find author details');
				continue;
			}
		}

		if(! rsa_verify($author_signed_data,$author_signature,$key,'sha256')) {
			logger('diaspora_conversation: verification failed.');
			continue;
		}

		if($msg_parent_author_signature) {
			$owner_signed_data = $msg_guid . ';' . $msg_parent_guid . ';' . $msg_text . ';' . unxmlify($mesg->created_at) . ';' . $msg_diaspora_handle . ';' . $msg_conversation_guid;

			$parent_author_signature = base64_decode($msg_parent_author_signature);

			$key = $msg["key"];

			if(! rsa_verify($owner_signed_data,$parent_author_signature,$key,'sha256')) {
				logger('diaspora_conversation: owner verification failed.');
				continue;
			}
		}

		$r = q("select id from mail where `uri` = '%s' limit 1",
			dbesc($message_id)
		);
		if(count($r)) {
			logger('diaspora_conversation: duplicate message already delivered.', LOGGER_DEBUG);
			continue;
		}

		q("insert into mail ( `uid`, `guid`, `convid`, `from-name`,`from-photo`,`from-url`,`contact-id`,`title`,`body`,`seen`,`reply`,`uri`,`parent-uri`,`created`) values ( %d, '%s', %d, '%s', '%s', '%s', %d, '%s', '%s', %d, %d, '%s','%s','%s')",
			intval($importer["uid"]),
			dbesc($msg_guid),
			intval($conversation["id"]),
			dbesc($person["name"]),
			dbesc($person["photo"]),
			dbesc($person["url"]),
			intval($contact["id"]),
			dbesc($subject),
			dbesc($body),
			0,
			0,
			dbesc($message_id),
			dbesc($parent_uri),
			dbesc($msg_created_at)
		);

		q("update conv set updated = '%s' where id = %d",
			dbesc(datetime_convert()),
			intval($conversation["id"])
		);

		notification(array(
			'type' => NOTIFY_MAIL,
			'notify_flags' => $importer["notify-flags"],
			'language' => $importer["language"],
			'to_name' => $importer["username"],
			'to_email' => $importer["email"],
			'uid' =>$importer["uid"],
			'item' => array('subject' => $subject, 'body' => $body),
			'source_name' => $person["name"],
			'source_link' => $person["url"],
			'source_photo' => $person["thumb"],
			'verb' => ACTIVITY_POST,
			'otype' => 'mail'
		));
	}
*/
		return true;
	}

	private function construct_like_body($contact, $parent_item, $guid) {
		$bodyverb = t('%1$s likes %2$s\'s %3$s');

		$ulink = "[url=".$contact["url"]."]".$contact["name"]."[/url]";
		$alink = "[url=".$parent_item["author-link"]."]".$parent_item["author-name"]."[/url]";
		$plink = "[url=".App::get_baseurl()."/display/".urlencode($guid)."]".t("status")."[/url]";

		return sprintf($bodyverb, $ulink, $alink, $plink);
	}

	private function construct_like_object($importer, $parent_item) {
		$objtype = ACTIVITY_OBJ_NOTE;
		$link = xmlify('<link rel="alternate" type="text/html" href="'.App::get_baseurl()."/display/".$importer["nickname"]."/".$parent_item["id"].'" />'."\n") ;
		$parent_body = $parent_item["body"];

		$obj = <<< EOT

		<object>
			<type>$objtype</type>
			<local>1</local>
			<id>{$parent_item["uri"]}</id>
			<link>$link</link>
			<title></title>
			<content>$parent_body</content>
		</object>
EOT;

		return $obj;
	}

	private function import_like($importer, $sender, $data) {
		$positive = notags(unxmlify($data->positive));
		$guid = notags(unxmlify($data->guid));
		$parent_type = notags(unxmlify($data->parent_type));
		$parent_guid = notags(unxmlify($data->parent_guid));
		$author = notags(unxmlify($data->author));

		// likes on comments aren't supported by Diaspora - only on posts
		// But maybe this will be supported in the future, so we will accept it.
		if (!in_array($parent_type, array("Post", "Comment")))
			return false;

		$contact = self::get_allowed_contact_by_handle($importer, $sender, true);
		if (!$contact)
			return false;

		if (self::message_exists($importer["uid"], $guid))
			return false;

		$parent_item = self::fetch_parent_item($importer["uid"], $parent_guid, $author, $contact);
		if (!$parent_item)
			return false;

		$person = self::get_person_by_handle($author);
		if (!is_array($person)) {
			logger("unable to find author details");
			return false;
		}

		// Fetch the contact id - if we know this contact
		$author_contact = self::get_author_contact_by_url($contact, $person, $importer["uid"]);

		// "positive" = "false" would be a Dislike - wich isn't currently supported by Diaspora
		// We would accept this anyhow.
		if ($positive === "true")
			$verb = ACTIVITY_LIKE;
		else
			$verb = ACTIVITY_DISLIKE;

		$datarray = array();

		$datarray["uid"] = $importer["uid"];
		$datarray["contact-id"] = $author_contact["cid"];
		$datarray["network"]  = $author_contact["network"];

		$datarray["author-name"] = $person["name"];
		$datarray["author-link"] = $person["url"];
		$datarray["author-avatar"] = ((x($person,"thumb")) ? $person["thumb"] : $person["photo"]);

		$datarray["owner-name"] = $contact["name"];
		$datarray["owner-link"] = $contact["url"];
		$datarray["owner-avatar"] = ((x($contact,"thumb")) ? $contact["thumb"] : $contact["photo"]);

		$datarray["guid"] = $guid;
		$datarray["uri"] = $author.":".$guid;

		$datarray["type"] = "activity";
		$datarray["verb"] = $verb;
		$datarray["gravity"] = GRAVITY_LIKE;
		$datarray["parent-uri"] = $parent_item["uri"];

		$datarray["object-type"] = ACTIVITY_OBJ_NOTE;
		$datarray["object"] = self::construct_like_object($importer, $parent_item);

		$datarray["body"] = self::construct_like_body($contact, $parent_item, $guid);

		$message_id = item_store($datarray);
		// print_r($datarray);

		// If we are the origin of the parent we store the original data and notify our followers
		if($message_id AND $parent_item["origin"]) {

			// Formerly we stored the signed text, the signature and the author in different fields.
			// We now store the raw data so that we are more flexible.
			q("INSERT INTO `sign` (`iid`,`signed_text`) VALUES (%d,'%s')",
				intval($message_id),
				dbesc(json_encode($data))
			);

			// notify others
			proc_run("php", "include/notifier.php", "comment-import", $message_id);
		}

		return $message_id;
	}

	private function import_message($importer, $data) {
		$guid = notags(unxmlify($data->guid));
		$parent_guid = notags(unxmlify($data->parent_guid));
		$text = unxmlify($data->text);
		$created_at = datetime_convert("UTC", "UTC", notags(unxmlify($data->created_at)));
		$author = notags(unxmlify($data->author));
		$conversation_guid = notags(unxmlify($data->conversation_guid));

		$parent_uri = $author.":".$parent_guid;

		$contact = self::get_allowed_contact_by_handle($importer, $author, true);
		if (!$contact)
			return false;

		$conversation = null;

		$c = q("SELECT * FROM `conv` WHERE `uid` = %d AND `guid` = '%s' LIMIT 1",
			intval($importer["uid"]),
			dbesc($conversation_guid)
		);
		if(count($c))
			$conversation = $c[0];
		else {
			logger("conversation not available.");
			return false;
		}

		$reply = 0;

		$body = diaspora2bb($text);
		$message_id = $author.":".$guid;

		$person = self::get_person_by_handle($author);
		if (!$person) {
			logger("unable to find author details");
			return false;
		}

		$r = q("SELECT `id` FROM `mail` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
			dbesc($message_id),
			intval($importer["uid"])
		);
		if(count($r)) {
			logger("duplicate message already delivered.", LOGGER_DEBUG);
			return false;
		}

		q("INSERT INTO `mail` (`uid`, `guid`, `convid`, `from-name`,`from-photo`,`from-url`,`contact-id`,`title`,`body`,`seen`,`reply`,`uri`,`parent-uri`,`created`)
				VALUES ( %d, '%s', %d, '%s', '%s', '%s', %d, '%s', '%s', %d, %d, '%s','%s','%s')",
			intval($importer["uid"]),
			dbesc($guid),
			intval($conversation["id"]),
			dbesc($person["name"]),
			dbesc($person["photo"]),
			dbesc($person["url"]),
			intval($contact["id"]),
			dbesc($conversation["subject"]),
			dbesc($body),
			0,
			1,
			dbesc($message_id),
			dbesc($parent_uri),
			dbesc($created_at)
		);

		q("UPDATE `conv` SET `updated` = '%s' WHERE `id` = %d",
			dbesc(datetime_convert()),
			intval($conversation["id"])
		);

		return true;
	}

	private function import_participation($importer, $data) {
		// I'm not sure if we can fully support this message type
		return true;
	}

	private function import_photo($importer, $data) {
		// There doesn't seem to be a reason for this function, since the photo data is transmitted in the status message as well
		return true;
	}

	private function import_poll_participation($importer, $data) {
		// We don't support polls by now
		return true;
	}

	private function import_profile($importer, $data) {
		$author = notags(unxmlify($data->author));

		$contact = self::get_contact_by_handle($importer["uid"], $author);
		if (!$contact)
			return;

		$name = unxmlify($data->first_name).((strlen($data->last_name)) ? " ".unxmlify($data->last_name) : "");
		$image_url = unxmlify($data->image_url);
		$birthday = unxmlify($data->birthday);
		$location = diaspora2bb(unxmlify($data->location));
		$about = diaspora2bb(unxmlify($data->bio));
		$gender = unxmlify($data->gender);
		$searchable = (unxmlify($data->searchable) == "true");
		$nsfw = (unxmlify($data->nsfw) == "true");
		$tags = unxmlify($data->tag_string);

		$tags = explode("#", $tags);

		$keywords = array();
		foreach ($tags as $tag) {
			$tag = trim(strtolower($tag));
			if ($tag != "")
				$keywords[] = $tag;
		}

		$keywords = implode(", ", $keywords);

		$handle_parts = explode("@", $author);
		$nick = $handle_parts[0];

		if($name === "")
			$name = $handle_parts[0];

		if( preg_match("|^https?://|", $image_url) === 0)
			$image_url = "http://".$handle_parts[1].$image_url;

		update_contact_avatar($image_url, $importer["uid"], $contact["id"]);

		// Generic birthday. We don't know the timezone. The year is irrelevant.

		$birthday = str_replace("1000", "1901", $birthday);

		if ($birthday != "")
			$birthday = datetime_convert("UTC", "UTC", $birthday, "Y-m-d");

		// this is to prevent multiple birthday notifications in a single year
		// if we already have a stored birthday and the 'm-d' part hasn't changed, preserve the entry, which will preserve the notify year

		if(substr($birthday,5) === substr($contact["bd"],5))
			$birthday = $contact["bd"];

		$r = q("UPDATE `contact` SET `name` = '%s', `nick` = '%s', `addr` = '%s', `name-date` = '%s', `bd` = '%s',
				`location` = '%s', `about` = '%s', `keywords` = '%s', `gender` = '%s' WHERE `id` = %d AND `uid` = %d",
			dbesc($name),
			dbesc($nick),
			dbesc($author),
			dbesc(datetime_convert()),
			dbesc($birthday),
			dbesc($location),
			dbesc($about),
			dbesc($keywords),
			dbesc($gender),
			intval($contact["id"]),
			intval($importer["uid"])
		);

		if ($searchable) {
			poco_check($contact["url"], $name, NETWORK_DIASPORA, $image_url, $about, $location, $gender, $keywords, "",
				datetime_convert(), 2, $contact["id"], $importer["uid"]);
		}

		$gcontact = array("url" => $contact["url"], "network" => NETWORK_DIASPORA, "generation" => 2,
					"photo" => $image_url, "name" => $name, "location" => $location,
					"about" => $about, "birthday" => $birthday, "gender" => $gender,
					"addr" => $author, "nick" => $nick, "keywords" => $keywords,
					"hide" => !$searchable, "nsfw" => $nsfw);

		update_gcontact($gcontact);

		return true;
	}

	private function import_request($importer, $data) {
		// @todo
		print_r($data);
/*
	$author = unxmlify($xml->author);
	$recipient = unxmlify($xml->recipient);

	if (!$author || !$recipient)
		return;

	$contact = self::get_contact_by_handle($importer["uid"],$author);

	if($contact) {

		// perhaps we were already sharing with this person. Now they're sharing with us.
		// That makes us friends.

		if($contact["rel"] == CONTACT_IS_FOLLOWER && in_array($importer["page-flags"], array(PAGE_FREELOVE))) {
			q("UPDATE `contact` SET `rel` = %d, `writable` = 1 WHERE `id` = %d AND `uid` = %d",
				intval(CONTACT_IS_FRIEND),
				intval($contact["id"]),
				intval($importer["uid"])
			);
		}
		// send notification

		$r = q("SELECT `hide-friends` FROM `profile` WHERE `uid` = %d AND `is-default` = 1 LIMIT 1",
			intval($importer["uid"])
		);

		if((count($r)) && (!$r[0]["hide-friends"]) && (!$contact["hidden"]) && intval(get_pconfig($importer["uid"],'system','post_newfriend'))) {

			$self = q("SELECT * FROM `contact` WHERE `self` = 1 AND `uid` = %d LIMIT 1",
				intval($importer["uid"])
			);

			// they are not CONTACT_IS_FOLLOWER anymore but that's what we have in the array

			if(count($self) && $contact["rel"] == CONTACT_IS_FOLLOWER) {

				$arr = array();
				$arr["uri"] = $arr["parent-uri"] = item_new_uri(App::get_hostname(), $importer["uid"]);
				$arr["uid"] = $importer["uid"];
				$arr["contact-id"] = $self[0]["id"];
				$arr["wall"] = 1;
				$arr["type"] = 'wall';
				$arr["gravity"] = 0;
				$arr["origin"] = 1;
				$arr["author-name"] = $arr["owner-name"] = $self[0]["name"];
				$arr["author-link"] = $arr["owner-link"] = $self[0]["url"];
				$arr["author-avatar"] = $arr["owner-avatar"] = $self[0]["thumb"];
				$arr["verb"] = ACTIVITY_FRIEND;
				$arr["object-type"] = ACTIVITY_OBJ_PERSON;

				$A = '[url=' . $self[0]["url"] . "]' . $self[0]["name"] . '[/url]';
				$B = '[url=' . $contact["url"] . "]' . $contact["name"] . '[/url]';
				$BPhoto = '[url=' . $contact["url"] . "]' . '[img]' . $contact["thumb"] . '[/img][/url]';
				$arr["body"] =  sprintf( t('%1$s is now friends with %2$s'), $A, $B)."\n\n\n".$Bphoto;

				$arr["object"] = '<object><type>' . ACTIVITY_OBJ_PERSON . '</type><title>' . $contact["name"] . '</title>'
					. '<id>' . $contact["url"] . '/' . $contact["name"] . '</id>';
				$arr["object"] .= '<link>' . xmlify('<link rel="alternate" type="text/html" href="' . $contact["url"] . '" />' . "\n")
;
				$arr["object"] .= xmlify('<link rel="photo" type="image/jpeg" href="' . $contact["thumb"] . '" />' . "\n");
				$arr["object"] .= '</link></object>' . "\n";
				$arr["last-child"] = 1;

				$arr["allow_cid"] = $user[0]["allow_cid"];
				$arr["allow_gid"] = $user[0]["allow_gid"];
				$arr["deny_cid"]  = $user[0]["deny_cid"];
				$arr["deny_gid"]  = $user[0]["deny_gid"];

				$i = item_store($arr);
				if($i)
				proc_run('php',"include/notifier.php","activity","$i");

			}

		}

		return;
	}

	$ret = self::get_person_by_handle($author);


	if((! count($ret)) || ($ret["network"] != NETWORK_DIASPORA)) {
		logger('diaspora_request: Cannot resolve diaspora handle ' . $author . ' for ' . $recipient);
		return;
	}

	$batch = (($ret["batch"]) ? $ret["batch"] : implode('/', array_slice(explode('/',$ret["url"]),0,3)) . '/receive/public');



	$r = q("INSERT INTO `contact` (`uid`, `network`,`addr`,`created`,`url`,`nurl`,`batch`,`name`,`nick`,`photo`,`pubkey`,`notify`,`poll`,`blocked`,`priority`)
		VALUES ( %d, '%s', '%s', '%s', '%s','%s','%s','%s','%s','%s','%s','%s','%s',%d,%d) ",
		intval($importer["uid"]),
		dbesc($ret["network"]),
		dbesc($ret["addr"]),
		datetime_convert(),
		dbesc($ret["url"]),
		dbesc(normalise_link($ret["url"])),
		dbesc($batch),
		dbesc($ret["name"]),
		dbesc($ret["nick"]),
		dbesc($ret["photo"]),
		dbesc($ret["pubkey"]),
		dbesc($ret["notify"]),
		dbesc($ret["poll"]),
		1,
		2
	);

	// find the contact record we just created

	$contact_record = diaspora_get_contact_by_handle($importer["uid"],$author);

	if(! $contact_record) {
		logger('diaspora_request: unable to locate newly created contact record.');
		return;
	}

	$g = q("select def_gid from user where uid = %d limit 1",
		intval($importer["uid"])
	);
	if($g && intval($g[0]["def_gid"])) {
		group_add_member($importer["uid"],'',$contact_record["id"],$g[0]["def_gid"]);
	}

	if($importer["page-flags"] == PAGE_NORMAL) {

		$hash = random_string() . (string) time();   // Generate a confirm_key

		$ret = q("INSERT INTO `intro` ( `uid`, `contact-id`, `blocked`, `knowyou`, `note`, `hash`, `datetime` )
			VALUES ( %d, %d, %d, %d, '%s', '%s', '%s' )",
			intval($importer["uid"]),
			intval($contact_record["id"]),
			0,
			0,
			dbesc( t('Sharing notification from Diaspora network')),
			dbesc($hash),
			dbesc(datetime_convert())
		);
	}
	else {

		// automatic friend approval

		update_contact_avatar($contact_record["photo"],$importer["uid"],$contact_record["id"]);

		// technically they are sharing with us (CONTACT_IS_SHARING),
		// but if our page-type is PAGE_COMMUNITY or PAGE_SOAPBOX
		// we are going to change the relationship and make them a follower.

		if($importer["page-flags"] == PAGE_FREELOVE)
			$new_relation = CONTACT_IS_FRIEND;
		else
			$new_relation = CONTACT_IS_FOLLOWER;

		$r = q("UPDATE `contact` SET `rel` = %d,
			`name-date` = '%s',
			`uri-date` = '%s',
			`blocked` = 0,
			`pending` = 0,
			`writable` = 1
			WHERE `id` = %d
			",
			intval($new_relation),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			intval($contact_record["id"])
		);

		$u = q("select * from user where uid = %d limit 1",intval($importer["uid"]));
		if($u)
			$ret = diaspora_share($u[0],$contact_record);
	}
*/
		return true;
	}

	private function get_original_item($guid, $orig_author, $author) {

		// Do we already have this item?
		$r = q("SELECT `body`, `tag`, `app`, `created`, `object-type`, `uri`, `guid`,
				`author-name`, `author-link`, `author-avatar`
				FROM `item` WHERE `guid` = '%s' AND `visible` AND NOT `deleted` AND `body` != '' LIMIT 1",
			dbesc($guid));

		if(count($r)) {
			logger("reshared message ".$guid." already exists on system.");

			// Maybe it is already a reshared item?
			// Then refetch the content, since there can be many side effects with reshared posts from other networks or reshares from reshares
			if (api_share_as_retweet($r[0]))
				$r = array();
			else
				return $r[0];
		}

		if (!count($r)) {
			$server = 'https://'.substr($orig_author,strpos($orig_author,'@')+1);
			logger("1st try: reshared message ".$guid." will be fetched from original server: ".$server);
			$item_id = self::store_by_guid($guid, $server);

			if (!$item_id) {
				$server = 'https://'.substr($author,strpos($author,'@')+1);
				logger("2nd try: reshared message ".$guid." will be fetched from sharer's server: ".$server);
				$item = self::store_by_guid($guid, $server);
			}
			if (!$item_id) {
				$server = 'http://'.substr($orig_author,strpos($orig_author,'@')+1);
				logger("3rd try: reshared message ".$guid." will be fetched from original server: ".$server);
				$item = self::store_by_guid($guid, $server);
			}
			if (!$item_id) {
				$server = 'http://'.substr($author,strpos($author,'@')+1);
				logger("4th try: reshared message ".$guid." will be fetched from sharer's server: ".$server);
				$item = self::store_by_guid($guid, $server);
			}

			if ($item_id) {
				$r = q("SELECT `body`, `tag`, `app`, `created`, `object-type`, `uri`, `guid`,
						`author-name`, `author-link`, `author-avatar`
					FROM `item` WHERE `id` = %d AND `visible` AND NOT `deleted` AND `body` != '' LIMIT 1",
					intval($item_id));

				if ($r)
					return $r[0];

			}
		}
		return false;
	}

	private function import_reshare($importer, $data) {
		$root_author = notags(unxmlify($data->root_author));
		$root_guid = notags(unxmlify($data->root_guid));
		$guid = notags(unxmlify($data->guid));
		$author = notags(unxmlify($data->author));
		$public = notags(unxmlify($data->public));
		$created_at = notags(unxmlify($data->created_at));

		$contact = self::get_allowed_contact_by_handle($importer, $author, false);
		if (!$contact)
			return false;

		if (self::message_exists($importer["uid"], $guid))
			return false;

		$original_item = self::get_original_item($root_guid, $root_author, $author);
		if (!$original_item)
			return false;

		$datarray = array();

		$datarray["uid"] = $importer["uid"];
		$datarray["contact-id"] = $contact["id"];
		$datarray["network"]  = NETWORK_DIASPORA;

		$datarray["author-name"] = $contact["name"];
		$datarray["author-link"] = $contact["url"];
		$datarray["author-avatar"] = ((x($contact,"thumb")) ? $contact["thumb"] : $contact["photo"]);

		$datarray["owner-name"] = $datarray["author-name"];
		$datarray["owner-link"] = $datarray["author-link"];
		$datarray["owner-avatar"] = $datarray["author-avatar"];

		$datarray["guid"] = $guid;
		$datarray["uri"] = $datarray["parent-uri"] = $author.":".$guid;

		$datarray["verb"] = ACTIVITY_POST;
		$datarray["gravity"] = GRAVITY_PARENT;

		$datarray["object"] = json_encode($data);

		$prefix = share_header($original_item["author-name"], $original_item["author-link"], $original_item["author-avatar"],
					$original_item["guid"], $original_item["created"], $original_item["uri"]);
		$datarray["body"] = $prefix.$original_item["body"]."[/share]";

		$datarray["tag"] = $original_item["tag"];
		$datarray["app"]  = $original_item["app"];

		$datarray["plink"] = self::plink($author, $guid);
		$datarray["private"] = (($public == "false") ? 1 : 0);
		$datarray["changed"] = $datarray["created"] = $datarray["edited"] = datetime_convert("UTC", "UTC", $created_at);

		$datarray["object-type"] = $original_item["object-type"];

		self::fetch_guid($datarray);
		$message_id = item_store($datarray);
		// print_r($datarray);

		return $message_id;
	}

	private function item_retraction($importer, $contact, $data) {
		$target_type = notags(unxmlify($data->target_type));
		$target_guid = notags(unxmlify($data->target_guid));
		$author = notags(unxmlify($data->author));

		$person = self::get_person_by_handle($author);
		if (!is_array($person)) {
			logger("unable to find author detail for ".$author);
			return false;
		}

		$r = q("SELECT `id`, `parent`, `parent-uri`, `author-link` FROM `item` WHERE `guid` = '%s' AND `uid` = %d AND NOT `file` LIKE '%%[%%' LIMIT 1",
			dbesc($target_guid),
			intval($importer["uid"])
		);
		if (!$r)
			return false;

		// Only delete it if the author really fits
		if (!link_compare($r[0]["author-link"],$person["url"]))
			return false;

		// Check if the sender is the thread owner
		$p = q("SELECT `author-link`, `origin` FROM `item` WHERE `id` = %d",
			intval($r[0]["parent"]));

		// Only delete it if the parent author really fits
		if (!link_compare($p[0]["author-link"], $contact["url"]))
			return false;

		// Currently we don't have a central deletion function that we could use in this case. The function "item_drop" doesn't work for that case
		q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s', `body` = '' , `title` = '' WHERE `id` = %d",
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			intval($r[0]["id"])
		);
		delete_thread($r[0]["id"], $r[0]["parent-uri"]);

		// Now check if the retraction needs to be relayed by us
		if($p[0]["origin"]) {

			// Formerly we stored the signed text, the signature and the author in different fields.
			// We now store the raw data so that we are more flexible.
			q("INSERT INTO `sign` (`iid`,`signed_text`) VALUES (%d,'%s')",
				intval($r[0]["id"]),
				dbesc(json_encode($data))
			);

			// notify others
			proc_run("php", "include/notifier.php", "drop", $r[0]["id"]);
		}
	}

	private function import_retraction($importer, $sender, $data) {
		$target_type = notags(unxmlify($data->target_type));

		$contact = self::get_contact_by_handle($importer["uid"], $sender);
		if (!$contact) {
			logger("cannot find contact for sender: ".$sender." and user ".$importer["uid"]);
			return false;
		}

		switch ($target_type) {
			case "Comment":
			case "Like":
			case "Post": // "Post" will be supported in a future version
			case "Reshare":
			case "StatusMessage":
				return self::item_retraction($importer, $contact, $data);;

			case "Person":
				contact_remove($contact["id"]);
				return true;

			default:
				logger("Unknown target type ".$target_type);
				return false;
		}
		return true;
	}

	private function import_status_message($importer, $data) {

		$raw_message = unxmlify($data->raw_message);
		$guid = notags(unxmlify($data->guid));
		$author = notags(unxmlify($data->author));
		$public = notags(unxmlify($data->public));
		$created_at = notags(unxmlify($data->created_at));
		$provider_display_name = notags(unxmlify($data->provider_display_name));

		/// @todo enable support for polls
		//if ($data->poll) {
		//	foreach ($data->poll AS $poll)
		//		print_r($poll);
		//	die("poll!\n");
		//}
		$contact = self::get_allowed_contact_by_handle($importer, $author, false);
		if (!$contact)
			return false;

		if (self::message_exists($importer["uid"], $guid))
			return false;

		$address = array();
		if ($data->location)
			foreach ($data->location->children() AS $fieldname => $data)
				$address[$fieldname] = notags(unxmlify($data));

		$body = diaspora2bb($raw_message);

		$datarray = array();

		if ($data->photo) {
			foreach ($data->photo AS $photo)
				$body = "[img]".$photo->remote_photo_path.$photo->remote_photo_name."[/img]\n".$body;

			$datarray["object-type"] = ACTIVITY_OBJ_PHOTO;
		} else {
			$datarray["object-type"] = ACTIVITY_OBJ_NOTE;

			// Add OEmbed and other information to the body
			if (!self::is_redmatrix($contact["url"]))
				$body = add_page_info_to_body($body, false, true);
		}

		$datarray["uid"] = $importer["uid"];
		$datarray["contact-id"] = $contact["id"];
		$datarray["network"] = NETWORK_DIASPORA;

		$datarray["author-name"] = $contact["name"];
		$datarray["author-link"] = $contact["url"];
		$datarray["author-avatar"] = ((x($contact,"thumb")) ? $contact["thumb"] : $contact["photo"]);

		$datarray["owner-name"] = $datarray["author-name"];
		$datarray["owner-link"] = $datarray["author-link"];
		$datarray["owner-avatar"] = $datarray["author-avatar"];

		$datarray["guid"] = $guid;
		$datarray["uri"] = $datarray["parent-uri"] = $author.":".$guid;

		$datarray["verb"] = ACTIVITY_POST;
		$datarray["gravity"] = GRAVITY_PARENT;

		$datarray["object"] = json_encode($data);

		$datarray["body"] = $body;

		if ($provider_display_name != "")
			$datarray["app"] = $provider_display_name;

		$datarray["plink"] = self::plink($author, $guid);
		$datarray["private"] = (($public == "false") ? 1 : 0);
		$datarray["changed"] = $datarray["created"] = $datarray["edited"] = datetime_convert("UTC", "UTC", $created_at);

		if (isset($address["address"]))
			$datarray["location"] = $address["address"];

		if (isset($address["lat"]) AND isset($address["lng"]))
			$datarray["coord"] = $address["lat"]." ".$address["lng"];

		self::fetch_guid($datarray);
		$message_id = item_store($datarray);
		// print_r($datarray);

		logger("Stored item with message id ".$message_id, LOGGER_DEBUG);

		return $message_id;
	}
}
?>
