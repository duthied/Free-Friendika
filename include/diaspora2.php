<?php
/**
 * @file include/diaspora.php
 * @brief The implementation of the diaspora protocol
 */

require_once("include/diaspora.php");
require_once("include/Scrape.php");

function array_to_xml($array, &$xml) {

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

/**
 * @brief This class contain functions to create and send DFRN XML files
 *
 */
class diaspora {

	public static function dispatch_public($msg) {

		$enabled = intval(get_config("system", "diaspora_enabled"));
		if (!$enabled) {
			logger('diaspora is disabled');
			return false;
		}

		// Use a dummy importer to import the data for the public copy
		$importer = array("uid" => 0, "page-flags" => PAGE_FREELOVE);
		self::dispatch($importer,$msg);

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
	}

	public static function dispatch($importer, $msg) {

		// The sender is the handle of the contact that sent the message.
		// This will often be different with relayed messages (for example "like" and "comment")
		$sender = $msg->author;

		if (!diaspora::valid_posting($msg, $fields)) {
			logger("Invalid posting");
			return false;
		}

		$type = $fields->getName();

		switch ($type) {
			case "account_deletion":
				return self::import_account_deletion($importer, $fields);

			case "comment":
				return self::import_comment($importer, $sender, $fields);

			case "conversation":
				return self::import_conversation($importer, $fields);

			case "like":
				return self::import_like($importer, $sender, $fields);

			case "message":
				return self::import_message($importer, $fields);

			case "participation":
				return self::import_participation($importer, $fields);

			case "photo":
				return self::import_photo($importer, $fields);

			case "poll_participation":
				return self::import_poll_participation($importer, $fields);

			case "profile":
				return self::import_profile($importer, $fields);

			case "request":
				return self::import_request($importer, $fields);

			case "reshare":
				return self::import_reshare($importer, $fields);

			case "retraction":
				return self::import_retraction($importer, $fields);

			case "status_message":
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
	 * @param object $fields SimpleXML object that contains the posting
	 *
	 * @return bool Is the posting valid?
	 */
	private function valid_posting($msg, &$fields) {

		$data = parse_xml_string($msg->message, false);

		$first_child = $data->getName();

		if ($data->getName() == "XML") {
			$oldXML = true;
			foreach ($data->post->children() as $child)
				$element = $child;
		} else {
			$oldXML = false;
			$element = $data;
		}

		$type = $element->getName();

		if (in_array($type, array("signed_retraction", "relayable_retraction")))
			$type = "retraction";

		$fields = new SimpleXMLElement("<".$type."/>");

		$signed_data = "";

		foreach ($element->children() AS $fieldname => $data) {

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
				$author_signature = base64_decode($data);
			elseif ($fieldname == "parent_author_signature")
				$parent_author_signature = base64_decode($data);
			elseif ($fieldname != "target_author_signature") {
				if ($signed_data != "") {
					$signed_data .= ";";
					$signed_data_parent .= ";";
				}

				$signed_data .= $data;
				$fields->$fieldname = $data;
			}
		}

		if (in_array($type, array("status_message", "reshare")))
			if ($msg->author != $fields->author) {
				logger("Message handle is not the same as envelope sender. Quitting this message.");
				return false;
			}

		if (!in_array($type, array("comment", "conversation", "message", "like")))
			return true;

		if (!isset($author_signature))
			return false;

		if (isset($parent_author_signature)) {
			$key = self::get_key($msg->author);

			if (!rsa_verify($signed_data, $parent_author_signature, $key, "sha256"))
				return false;
		}

		$key = self::get_key($fields->author);

		return rsa_verify($signed_data, $author_signature, $key, "sha256");
	}

	private function get_key($handle) {
		logger("Fetching diaspora key for: ".$handle);

		$r = self::get_person_by_handle($handle);
		if($r)
			return $r["pubkey"];

		return "";
	}

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

/*
function DiasporaFetchGuid($item) {
        preg_replace_callback("&\[url=/posts/([^\[\]]*)\](.*)\[\/url\]&Usi",
                function ($match) use ($item){
                        return(DiasporaFetchGuidSub($match, $item));
                },$item["body"]);
}

function DiasporaFetchGuidSub($match, $item) {
        $a = get_app();

        if (!diaspora_store_by_guid($match[1], $item["author-link"]))
                diaspora_store_by_guid($match[1], $item["owner-link"]);
}

function diaspora_store_by_guid($guid, $server, $uid = 0) {
        require_once("include/Contact.php");

        $serverparts = parse_url($server);
        $server = $serverparts["scheme"]."://".$serverparts["host"];

        logger("Trying to fetch item ".$guid." from ".$server, LOGGER_DEBUG);

        $item = diaspora_fetch_message($guid, $server);

        if (!$item)
                return false;

        logger("Successfully fetched item ".$guid." from ".$server, LOGGER_DEBUG);

        $body = $item["body"];
        $str_tags = $item["tag"];
        $app = $item["app"];
        $created = $item["created"];
        $author = $item["author"];
        $guid = $item["guid"];
        $private = $item["private"];
        $object = $item["object"];
        $objecttype = $item["object-type"];

        $message_id = $author.':'.$guid;
        $r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `guid` = '%s' LIMIT 1",
                intval($uid),
                dbesc($guid)
        );
        if(count($r))
                return $r[0]["id"];

        $person = find_diaspora_person_by_handle($author);

        $contact_id = get_contact($person['url'], $uid);

        $contacts = q("SELECT * FROM `contact` WHERE `id` = %d", intval($contact_id));
        $importers = q("SELECT * FROM `user` WHERE `uid` = %d", intval($uid));

        if ($contacts AND $importers)
                if(!diaspora_post_allow($importers[0],$contacts[0], false)) {
                        logger('Ignoring author '.$person['url'].' for uid '.$uid);
                        return false;
                } else
                        logger('Author '.$person['url'].' is allowed for uid '.$uid);

        $datarray = array();
        $datarray['uid'] = $uid;
        $datarray['contact-id'] = $contact_id;
        $datarray['wall'] = 0;
        $datarray['network'] = NETWORK_DIASPORA;
        $datarray['guid'] = $guid;
        $datarray['uri'] = $datarray['parent-uri'] = $message_id;
        $datarray['changed'] = $datarray['created'] = $datarray['edited'] = datetime_convert('UTC','UTC',$created);
        $datarray['private'] = $private;
        $datarray['parent'] = 0;
        $datarray['plink'] = diaspora_plink($author, $guid);
        $datarray['author-name'] = $person['name'];
        $datarray['author-link'] = $person['url'];
        $datarray['author-avatar'] = ((x($person,'thumb')) ? $person['thumb'] : $person['photo']);
        $datarray['owner-name'] = $datarray['author-name'];
        $datarray['owner-link'] = $datarray['author-link'];
        $datarray['owner-avatar'] = $datarray['author-avatar'];
        $datarray['body'] = $body;
        $datarray['tag'] = $str_tags;
        $datarray['app']  = $app;
        $datarray['visible'] = ((strlen($body)) ? 1 : 0);
        $datarray['object'] = $object;
        $datarray['object-type'] = $objecttype;

        if ($datarray['contact-id'] == 0)
                return false;

        DiasporaFetchGuid($datarray);
        $message_id = item_store($datarray);

        /// @TODO
        /// Looking if there is some subscribe mechanism in Diaspora to get all comments for this post

        return $message_id;
}
*/

	private function import_account_deletion($importer, $data) {
		return true;
	}

	private function import_comment($importer, $sender, $data) {
		$guid = notags(unxmlify($data->guid));
		$parent_guid = notags(unxmlify($data->parent_guid));
		$text = unxmlify($data->text);
		$author = notags(unxmlify($data->author));

		$contact = self::get_contact_by_handle($importer["uid"], $sender);
		if (!$contact) {
			logger("cannot find contact for sender: ".$sender);
			return false;
		}
/*
        if(! diaspora_post_allow($importer,$contact, true)) {
                logger('diaspora_comment: Ignoring this author.');
                return 202;
        }

        $r = q("SELECT * FROM `item` WHERE `uid` = %d AND `guid` = '%s' LIMIT 1",
                intval($importer['uid']),
                dbesc($guid)
        );
        if(count($r)) {
                logger('diaspora_comment: our comment just got relayed back to us (or there was a guid collision) : ' . $guid);
                return;
        }

        $r = q("SELECT * FROM `item` WHERE `uid` = %d AND `guid` = '%s' LIMIT 1",
                intval($importer['uid']),
                dbesc($parent_guid)
        );

        if(!count($r)) {
                $result = diaspora_store_by_guid($parent_guid, $contact['url'], $importer['uid']);

                if (!$result) {
                        $person = find_diaspora_person_by_handle($diaspora_handle);
                        $result = diaspora_store_by_guid($parent_guid, $person['url'], $importer['uid']);
                }

                if ($result) {
                        logger("Fetched missing item ".$parent_guid." - result: ".$result, LOGGER_DEBUG);

                        $r = q("SELECT * FROM `item` WHERE `uid` = %d AND `guid` = '%s' LIMIT 1",
                                intval($importer['uid']),
                                dbesc($parent_guid)
                        );
                }
        }

        if(! count($r)) {
                logger('diaspora_comment: parent item not found: parent: ' . $parent_guid . ' item: ' . $guid);
                return;
        }
        $parent_item = $r[0];

        // Find the original comment author information.
        // We need this to make sure we display the comment author
        // information (name and avatar) correctly.
        if(strcasecmp($diaspora_handle,$msg['author']) == 0)
                $person = $contact;
        else {
                $person = find_diaspora_person_by_handle($diaspora_handle);

                if(! is_array($person)) {
                        logger('diaspora_comment: unable to find author details');
                        return;
                }
        }

        // Fetch the contact id - if we know this contact
        $r = q("SELECT `id`, `network` FROM `contact` WHERE `nurl` = '%s' AND `uid` = %d LIMIT 1",
                dbesc(normalise_link($person['url'])), intval($importer['uid']));
        if ($r) {
                $cid = $r[0]['id'];
                $network = $r[0]['network'];
        } else {
                $cid = $contact['id'];
                $network = NETWORK_DIASPORA;
        }

        $body = diaspora2bb($text);
        $message_id = $diaspora_handle . ':' . $guid;

        $datarray = array();

        $datarray['uid'] = $importer['uid'];
        $datarray['contact-id'] = $cid;
        $datarray['type'] = 'remote-comment';
        $datarray['wall'] = $parent_item['wall'];
        $datarray['network']  = $network;
        $datarray['verb'] = ACTIVITY_POST;
        $datarray['gravity'] = GRAVITY_COMMENT;
        $datarray['guid'] = $guid;
        $datarray['uri'] = $message_id;
        $datarray['parent-uri'] = $parent_item['uri'];

        // No timestamps for comments? OK, we'll the use current time.
        $datarray['changed'] = $datarray['created'] = $datarray['edited'] = datetime_convert();
        $datarray['private'] = $parent_item['private'];

        $datarray['owner-name'] = $parent_item['owner-name'];
        $datarray['owner-link'] = $parent_item['owner-link'];
        $datarray['owner-avatar'] = $parent_item['owner-avatar'];

        $datarray['author-name'] = $person['name'];
        $datarray['author-link'] = $person['url'];
        $datarray['author-avatar'] = ((x($person,'thumb')) ? $person['thumb'] : $person['photo']);
        $datarray['body'] = $body;
        $datarray["object"] = json_encode($xml);
        $datarray["object-type"] = ACTIVITY_OBJ_COMMENT;

        // We can't be certain what the original app is if the message is relayed.
        if(($parent_item['origin']) && (! $parent_author_signature))
                $datarray['app']  = 'Diaspora';

        DiasporaFetchGuid($datarray);
        $message_id = item_store($datarray);

        $datarray['id'] = $message_id;

        // If we are the origin of the parent we store the original signature and notify our followers
        if($parent_item['origin']) {
                $author_signature_base64 = base64_encode($author_signature);
                $author_signature_base64 = diaspora_repair_signature($author_signature_base64, $diaspora_handle);

                q("insert into sign (`iid`,`signed_text`,`signature`,`signer`) values (%d,'%s','%s','%s') ",
                        intval($message_id),
                        dbesc($signed_data),
                        dbesc($author_signature_base64),
                        dbesc($diaspora_handle)
                );

                // notify others
                proc_run('php','include/notifier.php','comment-import',$message_id);
        }
*/
		return true;
	}

	private function import_conversation($importer, $data) {
		return true;
	}

	private function import_like($importer, $sender, $data) {
		return true;
	}

	private function import_message($importer, $data) {
		return true;
	}

	private function import_participation($importer, $data) {
		return true;
	}

	private function import_photo($importer, $data) {
		return true;
	}

	private function import_poll_participation($importer, $data) {
		return true;
	}

	private function import_profile($importer, $data) {
		return true;
	}

	private function import_request($importer, $data) {
		return true;
	}

	private function import_reshare($importer, $data) {
		return true;
	}

	private function import_retraction($importer, $data) {
		return true;
	}

	private function import_status_message($importer, $data) {
		return true;
	}
}
?>
