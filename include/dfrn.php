<?php
/**
 * @file include/dfrn.php
 * @brief The implementation of the dfrn protocol
 *
 * https://github.com/friendica/friendica/wiki/Protocol
 */

require_once('include/items.php');
require_once('include/Contact.php');
require_once('include/ostatus.php');

/**
 * @brief This class contain functions to create and send DFRN XML files
 *
 */
class dfrn {

	/**
	 * @brief Generates the atom entries for delivery.php
	 *
	 * This function is used whenever content is transmitted via DFRN.
	 *
	 * @param array $items Item elements
	 * @param array $owner Owner record
	 *
	 * @return string DFRN entries
	 */
	function entries($items,$owner) {

		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$root = self::add_header($doc, $owner, "dfrn:owner", "", false);

		if(! count($items))
			return trim($doc->saveXML());

		foreach($items as $item) {
			$entry = self::entry($doc, "text", $item, $owner, $item["entry:comment-allow"], $item["entry:cid"]);
			$root->appendChild($entry);
		}

		return(trim($doc->saveXML()));
	}

	/**
	 * @brief Generate an atom feed for the given user
	 *
	 * This function is called when another server is pulling data from the user feed.
	 *
	 * @param string $dfrn_id DFRN ID from the requesting party
	 * @param string $owner_nick Owner nick name
	 * @param string $last_update Date of the last update
	 * @param int $direction Can be -1, 0 or 1.
	 *
	 * @return string DFRN feed entries
	 */
	function feed($dfrn_id, $owner_nick, $last_update, $direction = 0) {

		$a = get_app();

		$sitefeed    = ((strlen($owner_nick)) ? false : true); // not yet implemented, need to rewrite huge chunks of following logic
		$public_feed = (($dfrn_id) ? false : true);
		$starred     = false;   // not yet implemented, possible security issues
		$converse    = false;

		if($public_feed && $a->argc > 2) {
			for($x = 2; $x < $a->argc; $x++) {
				if($a->argv[$x] == 'converse')
					$converse = true;
				if($a->argv[$x] == 'starred')
					$starred = true;
				if($a->argv[$x] === 'category' && $a->argc > ($x + 1) && strlen($a->argv[$x+1]))
					$category = $a->argv[$x+1];
			}
		}



		// default permissions - anonymous user

		$sql_extra = " AND `item`.`allow_cid` = '' AND `item`.`allow_gid` = '' AND `item`.`deny_cid`  = '' AND `item`.`deny_gid`  = '' ";

		$r = q("SELECT `contact`.*, `user`.`uid` AS `user_uid`, `user`.`nickname`, `user`.`timezone`, `user`.`page-flags`
			FROM `contact` INNER JOIN `user` ON `user`.`uid` = `contact`.`uid`
			WHERE `contact`.`self` = 1 AND `user`.`nickname` = '%s' LIMIT 1",
			dbesc($owner_nick)
		);

		if(! count($r))
			killme();

		$owner = $r[0];
		$owner_id = $owner['user_uid'];
		$owner_nick = $owner['nickname'];

		$sql_post_table = "";
		$visibility = "";

		if(! $public_feed) {

			$sql_extra = '';
			switch($direction) {
				case (-1):
					$sql_extra = sprintf(" AND `issued-id` = '%s' ", dbesc($dfrn_id));
					$my_id = $dfrn_id;
					break;
				case 0:
					$sql_extra = sprintf(" AND `issued-id` = '%s' AND `duplex` = 1 ", dbesc($dfrn_id));
					$my_id = '1:' . $dfrn_id;
					break;
				case 1:
					$sql_extra = sprintf(" AND `dfrn-id` = '%s' AND `duplex` = 1 ", dbesc($dfrn_id));
					$my_id = '0:' . $dfrn_id;
					break;
				default:
					return false;
					break; // NOTREACHED
			}

			$r = q("SELECT * FROM `contact` WHERE `blocked` = 0 AND `pending` = 0 AND `contact`.`uid` = %d $sql_extra LIMIT 1",
				intval($owner_id)
			);

			if(! count($r))
				killme();

			$contact = $r[0];
			require_once('include/security.php');
			$groups = init_groups_visitor($contact['id']);

			if(count($groups)) {
				for($x = 0; $x < count($groups); $x ++)
					$groups[$x] = '<' . intval($groups[$x]) . '>' ;
				$gs = implode('|', $groups);
			} else
				$gs = '<<>>' ; // Impossible to match

			$sql_extra = sprintf("
				AND ( `allow_cid` = '' OR     `allow_cid` REGEXP '<%d>' )
				AND ( `deny_cid`  = '' OR NOT `deny_cid`  REGEXP '<%d>' )
				AND ( `allow_gid` = '' OR     `allow_gid` REGEXP '%s' )
				AND ( `deny_gid`  = '' OR NOT `deny_gid`  REGEXP '%s')
			",
				intval($contact['id']),
				intval($contact['id']),
				dbesc($gs),
				dbesc($gs)
			);
		}

		if($public_feed)
			$sort = 'DESC';
		else
			$sort = 'ASC';

		$date_field = "`changed`";
		$sql_order = "`item`.`parent` ".$sort.", `item`.`created` ASC";

		if(! strlen($last_update))
			$last_update = 'now -30 days';

		if(isset($category)) {
			$sql_post_table = sprintf("INNER JOIN (SELECT `oid` FROM `term` WHERE `term` = '%s' AND `otype` = %d AND `type` = %d AND `uid` = %d ORDER BY `tid` DESC) AS `term` ON `item`.`id` = `term`.`oid` ",
					dbesc(protect_sprintf($category)), intval(TERM_OBJ_POST), intval(TERM_CATEGORY), intval($owner_id));
			//$sql_extra .= file_tag_file_query('item',$category,'category');
		}

		if($public_feed) {
			if(! $converse)
				$sql_extra .= " AND `contact`.`self` = 1 ";
		}

		$check_date = datetime_convert('UTC','UTC',$last_update,'Y-m-d H:i:s');

		//	AND ( `item`.`edited` > '%s' OR `item`.`changed` > '%s' )
		//	dbesc($check_date),

		$r = q("SELECT STRAIGHT_JOIN `item`.*, `item`.`id` AS `item_id`,
			`contact`.`name`, `contact`.`network`, `contact`.`photo`, `contact`.`url`,
			`contact`.`name-date`, `contact`.`uri-date`, `contact`.`avatar-date`,
			`contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`,
			`contact`.`id` AS `contact-id`, `contact`.`uid` AS `contact-uid`,
			`sign`.`signed_text`, `sign`.`signature`, `sign`.`signer`
			FROM `item` $sql_post_table
			INNER JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			LEFT JOIN `sign` ON `sign`.`iid` = `item`.`id`
			WHERE `item`.`uid` = %d AND `item`.`visible` = 1 and `item`.`moderated` = 0 AND `item`.`parent` != 0
			AND ((`item`.`wall` = 1) $visibility) AND `item`.$date_field > '%s'
			$sql_extra
			ORDER BY $sql_order LIMIT 0, 300",
			intval($owner_id),
			dbesc($check_date),
			dbesc($sort)
		);

		// Will check further below if this actually returned results.
		// We will provide an empty feed if that is the case.

		$items = $r;

		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$alternatelink = $owner['url'];

		if(isset($category))
			$alternatelink .= "/category/".$category;

		if ($public_feed)
			$author = "dfrn:owner";
		else
			$author = "author";

		$root = self::add_header($doc, $owner, $author, $alternatelink, true);

		// This hook can't work anymore
		//	call_hooks('atom_feed', $atom);

		if(! count($items)) {
			$atom = trim($doc->saveXML());

			call_hooks('atom_feed_end', $atom);

			return $atom;
		}

		foreach($items as $item) {

			// prevent private email from leaking.
			if($item['network'] === NETWORK_MAIL)
				continue;

			// public feeds get html, our own nodes use bbcode

			if($public_feed) {
				$type = 'html';
				// catch any email that's in a public conversation and make sure it doesn't leak
				if($item['private'])
					continue;
			} else
				$type = 'text';

			$entry = self::entry($doc, $type, $item, $owner, true);
			$root->appendChild($entry);

		}

		$atom = trim($doc->saveXML());

		call_hooks('atom_feed_end', $atom);

		return $atom;
	}

	/**
	 * @brief Create XML text for DFRN mails
	 *
	 * @param array $item message elements
	 * @param array $owner Owner record
	 *
	 * @return string DFRN mail
	 */
	function mail($item, $owner) {
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$root = self::add_header($doc, $owner, "dfrn:owner", "", false);

		$mail = $doc->createElement("dfrn:mail");
		$sender = $doc->createElement("dfrn:sender");

		xml_add_element($doc, $sender, "dfrn:name", $owner['name']);
		xml_add_element($doc, $sender, "dfrn:uri", $owner['url']);
		xml_add_element($doc, $sender, "dfrn:avatar", $owner['thumb']);

		$mail->appendChild($sender);

		xml_add_element($doc, $mail, "dfrn:id", $item['uri']);
		xml_add_element($doc, $mail, "dfrn:in-reply-to", $item['parent-uri']);
		xml_add_element($doc, $mail, "dfrn:sentdate", datetime_convert('UTC', 'UTC', $item['created'] . '+00:00' , ATOM_TIME));
		xml_add_element($doc, $mail, "dfrn:subject", $item['title']);
		xml_add_element($doc, $mail, "dfrn:content", $item['body']);

		$root->appendChild($mail);

		return(trim($doc->saveXML()));
	}

	/**
	 * @brief Create XML text for DFRN friend suggestions
	 *
	 * @param array $item suggestion elements
	 * @param array $owner Owner record
	 *
	 * @return string DFRN suggestions
	 */
	function fsuggest($item, $owner) {
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$root = self::add_header($doc, $owner, "dfrn:owner", "", false);

		$suggest = $doc->createElement("dfrn:suggest");

		xml_add_element($doc, $suggest, "dfrn:url", $item['url']);
		xml_add_element($doc, $suggest, "dfrn:name", $item['name']);
		xml_add_element($doc, $suggest, "dfrn:photo", $item['photo']);
		xml_add_element($doc, $suggest, "dfrn:request", $item['request']);
		xml_add_element($doc, $suggest, "dfrn:note", $item['note']);

		$root->appendChild($suggest);

		return(trim($doc->saveXML()));
	}

	/**
	 * @brief Create XML text for DFRN relocations
	 *
	 * @param array $owner Owner record
	 * @param int $uid User ID
	 *
	 * @return string DFRN relocations
	 */
	function relocate($owner, $uid) {

		/* get site pubkey. this could be a new installation with no site keys*/
		$pubkey = get_config('system','site_pubkey');
		if(! $pubkey) {
			$res = new_keypair(1024);
			set_config('system','site_prvkey', $res['prvkey']);
			set_config('system','site_pubkey', $res['pubkey']);
		}

		$rp = q("SELECT `resource-id` , `scale`, type FROM `photo`
				WHERE `profile` = 1 AND `uid` = %d ORDER BY scale;", $uid);
		$photos = array();
		$ext = Photo::supportedTypes();

		foreach($rp as $p)
			$photos[$p['scale']] = app::get_baseurl().'/photo/'.$p['resource-id'].'-'.$p['scale'].'.'.$ext[$p['type']];

		unset($rp, $ext);

		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$root = self::add_header($doc, $owner, "dfrn:owner", "", false);

		$relocate = $doc->createElement("dfrn:relocate");

		xml_add_element($doc, $relocate, "dfrn:url", $owner['url']);
		xml_add_element($doc, $relocate, "dfrn:name", $owner['name']);
		xml_add_element($doc, $relocate, "dfrn:photo", $photos[4]);
		xml_add_element($doc, $relocate, "dfrn:thumb", $photos[5]);
		xml_add_element($doc, $relocate, "dfrn:micro", $photos[6]);
		xml_add_element($doc, $relocate, "dfrn:request", $owner['request']);
		xml_add_element($doc, $relocate, "dfrn:confirm", $owner['confirm']);
		xml_add_element($doc, $relocate, "dfrn:notify", $owner['notify']);
		xml_add_element($doc, $relocate, "dfrn:poll", $owner['poll']);
		xml_add_element($doc, $relocate, "dfrn:sitepubkey", get_config('system','site_pubkey'));

		$root->appendChild($relocate);

		return(trim($doc->saveXML()));
	}

	/**
	 * @brief Adds the header elements for the DFRN protocol
	 *
	 * @param object $doc XML document
	 * @param array $owner Owner record
	 * @param string $authorelement Element name for the author
	 * @param string $alternatelink link to profile or category
	 * @param bool $public Is it a header for public posts?
	 *
	 * @return object XML root object
	 */
	private function add_header($doc, $owner, $authorelement, $alternatelink = "", $public = false) {

		if ($alternatelink == "")
			$alternatelink = $owner['url'];

		$root = $doc->createElementNS(NS_ATOM, 'feed');
		$doc->appendChild($root);

		$root->setAttribute("xmlns:thr", NS_THR);
		$root->setAttribute("xmlns:at", "http://purl.org/atompub/tombstones/1.0");
		$root->setAttribute("xmlns:media", NS_MEDIA);
		$root->setAttribute("xmlns:dfrn", "http://purl.org/macgirvin/dfrn/1.0");
		$root->setAttribute("xmlns:activity", NS_ACTIVITY);
		$root->setAttribute("xmlns:georss", NS_GEORSS);
		$root->setAttribute("xmlns:poco", NS_POCO);
		$root->setAttribute("xmlns:ostatus", NS_OSTATUS);
		$root->setAttribute("xmlns:statusnet", NS_STATUSNET);

		//xml_add_element($doc, $root, "id", app::get_baseurl()."/profile/".$owner["nick"]);
		xml_add_element($doc, $root, "id", app::get_baseurl()."/profile/".$owner["nick"]);
		xml_add_element($doc, $root, "title", $owner["name"]);

		$attributes = array("uri" => "https://friendi.ca", "version" => FRIENDICA_VERSION."-".DB_UPDATE_VERSION);
		xml_add_element($doc, $root, "generator", FRIENDICA_PLATFORM, $attributes);

		$attributes = array("rel" => "license", "href" => "http://creativecommons.org/licenses/by/3.0/");
		xml_add_element($doc, $root, "link", "", $attributes);

		$attributes = array("rel" => "alternate", "type" => "text/html", "href" => $alternatelink);
		xml_add_element($doc, $root, "link", "", $attributes);

		ostatus_hublinks($doc, $root);

		if ($public) {
			$attributes = array("rel" => "salmon", "href" => app::get_baseurl()."/salmon/".$owner["nick"]);
			xml_add_element($doc, $root, "link", "", $attributes);

			$attributes = array("rel" => "http://salmon-protocol.org/ns/salmon-replies", "href" => app::get_baseurl()."/salmon/".$owner["nick"]);
			xml_add_element($doc, $root, "link", "", $attributes);

			$attributes = array("rel" => "http://salmon-protocol.org/ns/salmon-mention", "href" => app::get_baseurl()."/salmon/".$owner["nick"]);
			xml_add_element($doc, $root, "link", "", $attributes);
		}

		if ($owner['page-flags'] == PAGE_COMMUNITY)
			xml_add_element($doc, $root, "dfrn:community", 1);

		xml_add_element($doc, $root, "updated", datetime_convert("UTC", "UTC", "now", ATOM_TIME));

		$author = self::add_author($doc, $owner, $authorelement, $public);
		$root->appendChild($author);

		return $root;
	}

	/**
	 * @brief Adds the author element in the header for the DFRN protocol
	 *
	 * @param object $doc XML document
	 * @param array $owner Owner record
	 * @param string $authorelement Element name for the author
	 *
	 * @return object XML author object
	 */
	private function add_author($doc, $owner, $authorelement, $public) {

		$author = $doc->createElement($authorelement);

		$namdate = datetime_convert('UTC', 'UTC', $owner['name-date'].'+00:00' , ATOM_TIME);
		$uridate = datetime_convert('UTC', 'UTC', $owner['uri-date'].'+00:00', ATOM_TIME);
		$picdate = datetime_convert('UTC', 'UTC', $owner['avatar-date'].'+00:00', ATOM_TIME);

		$attributes = array("dfrn:updated" => $namdate);
		xml_add_element($doc, $author, "name", $owner["name"], $attributes);

		$attributes = array("dfrn:updated" => $namdate);
		xml_add_element($doc, $author, "uri", app::get_baseurl().'/profile/'.$owner["nickname"], $attributes);

		$attributes = array("dfrn:updated" => $namdate);
		xml_add_element($doc, $author, "dfrn:handle", $owner["addr"], $attributes);

		$attributes = array("rel" => "photo", "type" => "image/jpeg", "dfrn:updated" => $picdate,
					"media:width" => 175, "media:height" => 175, "href" => $owner['photo']);
		xml_add_element($doc, $author, "link", "", $attributes);

		$attributes = array("rel" => "avatar", "type" => "image/jpeg", "dfrn:updated" => $picdate,
					"media:width" => 175, "media:height" => 175, "href" => $owner['photo']);
		xml_add_element($doc, $author, "link", "", $attributes);

		$birthday = feed_birthday($owner['user_uid'], $owner['timezone']);

		if ($birthday)
			xml_add_element($doc, $author, "dfrn:birthday", $birthday);

		// The following fields will only be generated if this isn't for a public feed
		if ($public)
			return $author;

		// Only show contact details when we are allowed to
		$r = q("SELECT `profile`.`about`, `profile`.`name`, `profile`.`homepage`, `user`.`nickname`, `user`.`timezone`,
				`profile`.`locality`, `profile`.`region`, `profile`.`country-name`, `profile`.`pub_keywords`, `profile`.`dob`
			FROM `profile`
				INNER JOIN `user` ON `user`.`uid` = `profile`.`uid`
				WHERE `profile`.`is-default` AND NOT `user`.`hidewall` AND `user`.`uid` = %d",
			intval($owner['user_uid']));
		if ($r) {
			$profile = $r[0];
			xml_add_element($doc, $author, "poco:displayName", $profile["name"]);
			xml_add_element($doc, $author, "poco:updated", $namdate);

			if (trim($profile["dob"]) != "0000-00-00")
				xml_add_element($doc, $author, "poco:birthday", "0000-".date("m-d", strtotime($profile["dob"])));

			xml_add_element($doc, $author, "poco:note", $profile["about"]);
			xml_add_element($doc, $author, "poco:preferredUsername", $profile["nickname"]);

			$savetz = date_default_timezone_get();
			date_default_timezone_set($profile["timezone"]);
			xml_add_element($doc, $author, "poco:utcOffset", date("P"));
			date_default_timezone_set($savetz);

			if (trim($profile["homepage"]) != "") {
				$urls = $doc->createElement("poco:urls");
				xml_add_element($doc, $urls, "poco:type", "homepage");
				xml_add_element($doc, $urls, "poco:value", $profile["homepage"]);
				xml_add_element($doc, $urls, "poco:primary", "true");
				$author->appendChild($urls);
			}

			if (trim($profile["pub_keywords"]) != "") {
				$keywords = explode(",", $profile["pub_keywords"]);

				foreach ($keywords AS $keyword)
					xml_add_element($doc, $author, "poco:tags", trim($keyword));

			}

			/// @todo When we are having the XMPP address in the profile we should propagate it here
			$xmpp = "";
			if (trim($xmpp) != "") {
				$ims = $doc->createElement("poco:ims");
				xml_add_element($doc, $ims, "poco:type", "xmpp");
				xml_add_element($doc, $ims, "poco:value", $xmpp);
				xml_add_element($doc, $ims, "poco:primary", "true");
				$author->appendChild($ims);
			}

			if (trim($profile["locality"].$profile["region"].$profile["country-name"]) != "") {
				$element = $doc->createElement("poco:address");

				xml_add_element($doc, $element, "poco:formatted", formatted_location($profile));

				if (trim($profile["locality"]) != "")
					xml_add_element($doc, $element, "poco:locality", $profile["locality"]);

				if (trim($profile["region"]) != "")
					xml_add_element($doc, $element, "poco:region", $profile["region"]);

				if (trim($profile["country-name"]) != "")
					xml_add_element($doc, $element, "poco:country", $profile["country-name"]);

				$author->appendChild($element);
			}
		}

		return $author;
	}

	/**
	 * @brief Adds the author elements in the "entry" elements of the DFRN protocol
	 *
	 * @param object $doc XML document
	 * @param string $element Element name for the author
	 * @param string $contact_url Link of the contact
	 * @param array $items Item elements
	 *
	 * @return object XML author object
	 */
	private function add_entry_author($doc, $element, $contact_url, $item) {

		$contact = get_contact_details_by_url($contact_url, $item["uid"]);

		$author = $doc->createElement($element);
		xml_add_element($doc, $author, "name", $contact["name"]);
		xml_add_element($doc, $author, "uri", $contact["url"]);
		xml_add_element($doc, $author, "dfrn:handle", $contact["addr"]);

		/// @Todo
		/// - Check real image type and image size
		/// - Check which of these boths elements we should use
		$attributes = array(
				"rel" => "photo",
				"type" => "image/jpeg",
				"media:width" => 80,
				"media:height" => 80,
				"href" => $contact["photo"]);
		xml_add_element($doc, $author, "link", "", $attributes);

		$attributes = array(
				"rel" => "avatar",
				"type" => "image/jpeg",
				"media:width" => 80,
				"media:height" => 80,
				"href" => $contact["photo"]);
		xml_add_element($doc, $author, "link", "", $attributes);

		return $author;
	}

	/**
	 * @brief Adds the activity elements
	 *
	 * @param object $doc XML document
	 * @param string $element Element name for the activity
	 * @param string $activity activity value
	 *
	 * @return object XML activity object
	 */
	private function create_activity($doc, $element, $activity) {

		if($activity) {
			$entry = $doc->createElement($element);

			$r = parse_xml_string($activity, false);
			if(!$r)
				return false;
			if($r->type)
				xml_add_element($doc, $entry, "activity:object-type", $r->type);
			if($r->id)
				xml_add_element($doc, $entry, "id", $r->id);
			if($r->title)
				xml_add_element($doc, $entry, "title", $r->title);
			if($r->link) {
				if(substr($r->link,0,1) === '<') {
					if(strstr($r->link,'&') && (! strstr($r->link,'&amp;')))
						$r->link = str_replace('&','&amp;', $r->link);

					$r->link = preg_replace('/\<link(.*?)\"\>/','<link$1"/>',$r->link);

					$data = parse_xml_string($r->link, false);
					foreach ($data->attributes() AS $parameter => $value)
						$attributes[$parameter] = $value;
				} else
					$attributes = array("rel" => "alternate", "type" => "text/html", "href" => $r->link);

				xml_add_element($doc, $entry, "link", "", $attributes);
			}
			if($r->content)
				xml_add_element($doc, $entry, "content", bbcode($r->content), array("type" => "html"));

			return $entry;
		}

		return false;
	}

	/**
	 * @brief Adds the elements for attachments
	 *
	 * @param object $doc XML document
	 * @param object $root XML root
	 * @param array $item Item element
	 *
	 * @return object XML attachment object
	 */
	private function get_attachment($doc, $root, $item) {
		$arr = explode('[/attach],',$item['attach']);
		if(count($arr)) {
			foreach($arr as $r) {
				$matches = false;
				$cnt = preg_match('|\[attach\]href=\"(.*?)\" length=\"(.*?)\" type=\"(.*?)\" title=\"(.*?)\"|',$r,$matches);
				if($cnt) {
					$attributes = array("rel" => "enclosure",
							"href" => $matches[1],
							"type" => $matches[3]);

					if(intval($matches[2]))
						$attributes["length"] = intval($matches[2]);

					if(trim($matches[4]) != "")
						$attributes["title"] = trim($matches[4]);

					xml_add_element($doc, $root, "link", "", $attributes);
				}
			}
		}
	}

	/**
	 * @brief Adds the "entry" elements for the DFRN protocol
	 *
	 * @param object $doc XML document
	 * @param string $type "text" or "html"
	 * @param array $item Item element
	 * @param array $owner Owner record
	 * @param bool $comment Trigger the sending of the "comment" element
	 * @param int $cid Contact ID of the recipient
	 *
	 * @return object XML entry object
	 */
	private function entry($doc, $type, $item, $owner, $comment = false, $cid = 0) {

		$mentioned = array();

		if(!$item['parent'])
			return;

		if($item['deleted']) {
			$attributes = array("ref" => $item['uri'], "when" => datetime_convert('UTC','UTC',$item['edited'] . '+00:00',ATOM_TIME));
			return xml_create_element($doc, "at:deleted-entry", "", $attributes);
		}

		$entry = $doc->createElement("entry");

		if($item['allow_cid'] || $item['allow_gid'] || $item['deny_cid'] || $item['deny_gid'])
			$body = fix_private_photos($item['body'],$owner['uid'],$item,$cid);
		else
			$body = $item['body'];

		if ($type == 'html') {
			$htmlbody = $body;

			if ($item['title'] != "")
				$htmlbody = "[b]".$item['title']."[/b]\n\n".$htmlbody;

			$htmlbody = bbcode($htmlbody, false, false, 7);
		}

		$author = self::add_entry_author($doc, "author", $item["author-link"], $item);
		$entry->appendChild($author);

		$dfrnowner = self::add_entry_author($doc, "dfrn:owner", $item["owner-link"], $item);
		$entry->appendChild($dfrnowner);

		if(($item['parent'] != $item['id']) || ($item['parent-uri'] !== $item['uri']) || (($item['thr-parent'] !== '') && ($item['thr-parent'] !== $item['uri']))) {
			$parent = q("SELECT `guid` FROM `item` WHERE `id` = %d", intval($item["parent"]));
			$parent_item = (($item['thr-parent']) ? $item['thr-parent'] : $item['parent-uri']);
			$attributes = array("ref" => $parent_item, "type" => "text/html",
						"href" => app::get_baseurl().'/display/'.$parent[0]['guid'],
						"dfrn:diaspora_guid" => $parent[0]['guid']);
			xml_add_element($doc, $entry, "thr:in-reply-to", "", $attributes);
		}

		xml_add_element($doc, $entry, "id", $item["uri"]);
		xml_add_element($doc, $entry, "title", $item["title"]);

		xml_add_element($doc, $entry, "published", datetime_convert("UTC","UTC",$item["created"]."+00:00",ATOM_TIME));
		xml_add_element($doc, $entry, "updated", datetime_convert("UTC","UTC",$item["edited"]."+00:00",ATOM_TIME));

		xml_add_element($doc, $entry, "dfrn:env", base64url_encode($body, true));
		xml_add_element($doc, $entry, "content", (($type === 'html') ? $htmlbody : $body), array("type" => $type));

		xml_add_element($doc, $entry, "link", "", array("rel" => "alternate", "type" => "text/html",
								"href" => app::get_baseurl()."/display/".$item["guid"]));

		// "comment-allow" is some old fashioned stuff for old Friendica versions.
		// It is included in the rewritten code for completeness
		if ($comment)
			xml_add_element($doc, $entry, "dfrn:comment-allow", intval($item['last-child']));

		if($item['location'])
			xml_add_element($doc, $entry, "dfrn:location", $item['location']);

		if($item['coord'])
			xml_add_element($doc, $entry, "georss:point", $item['coord']);

		if(($item['private']) || strlen($item['allow_cid']) || strlen($item['allow_gid']) || strlen($item['deny_cid']) || strlen($item['deny_gid']))
			xml_add_element($doc, $entry, "dfrn:private", (($item['private']) ? $item['private'] : 1));

		if($item['extid'])
			xml_add_element($doc, $entry, "dfrn:extid", $item['extid']);

		if($item['bookmark'])
			xml_add_element($doc, $entry, "dfrn:bookmark", "true");

		if($item['app'])
			xml_add_element($doc, $entry, "statusnet:notice_info", "", array("local_id" => $item['id'], "source" => $item['app']));

		xml_add_element($doc, $entry, "dfrn:diaspora_guid", $item["guid"]);

		// The signed text contains the content in Markdown, the sender handle and the signatur for the content
		// It is needed for relayed comments to Diaspora.
		if($item['signed_text']) {
			$sign = base64_encode(json_encode(array('signed_text' => $item['signed_text'],'signature' => $item['signature'],'signer' => $item['signer'])));
			xml_add_element($doc, $entry, "dfrn:diaspora_signature", $sign);
		}

		xml_add_element($doc, $entry, "activity:verb", construct_verb($item));

		if ($item['object-type'] != "")
			xml_add_element($doc, $entry, "activity:object-type", $item['object-type']);
		elseif ($item['id'] == $item['parent'])
			xml_add_element($doc, $entry, "activity:object-type", ACTIVITY_OBJ_NOTE);
		else
			xml_add_element($doc, $entry, "activity:object-type", ACTIVITY_OBJ_COMMENT);

		$actobj = self::create_activity($doc, "activity:object", $item['object']);
		if ($actobj)
			$entry->appendChild($actobj);

		$actarg = self::create_activity($doc, "activity:target", $item['target']);
		if ($actarg)
			$entry->appendChild($actarg);

		$tags = item_getfeedtags($item);

		if(count($tags)) {
			foreach($tags as $t)
				if (($type != 'html') OR ($t[0] != "@"))
					xml_add_element($doc, $entry, "category", "", array("scheme" => "X-DFRN:".$t[0].":".$t[1], "term" => $t[2]));
		}

		if(count($tags))
			foreach($tags as $t)
				if ($t[0] == "@")
					$mentioned[$t[1]] = $t[1];

		foreach ($mentioned AS $mention) {
			$r = q("SELECT `forum`, `prv` FROM `contact` WHERE `uid` = %d AND `nurl` = '%s'",
				intval($owner["uid"]),
				dbesc(normalise_link($mention)));
			if ($r[0]["forum"] OR $r[0]["prv"])
				xml_add_element($doc, $entry, "link", "", array("rel" => "mentioned",
											"ostatus:object-type" => ACTIVITY_OBJ_GROUP,
											"href" => $mention));
			else
				xml_add_element($doc, $entry, "link", "", array("rel" => "mentioned",
											"ostatus:object-type" => ACTIVITY_OBJ_PERSON,
											"href" => $mention));
		}

		self::get_attachment($doc, $entry, $item);

		return $entry;
	}

	/**
	 * @brief Delivers the atom content to the contacts
	 *
	 * @param array $owner Owner record
	 * @param array $contactr Contact record of the receiver
	 * @param string $atom Content that will be transmitted
	 * @param bool $dissolve (to be documented)
	 *
	 * @return int Deliver status. -1 means an error.
	 */
	function deliver($owner,$contact,$atom, $dissolve = false) {

		$a = get_app();

		$idtosend = $orig_id = (($contact['dfrn-id']) ? $contact['dfrn-id'] : $contact['issued-id']);

		if($contact['duplex'] && $contact['dfrn-id'])
			$idtosend = '0:' . $orig_id;
		if($contact['duplex'] && $contact['issued-id'])
			$idtosend = '1:' . $orig_id;


		$rino = get_config('system','rino_encrypt');
		$rino = intval($rino);
		// use RINO1 if mcrypt isn't installed and RINO2 was selected
		if ($rino==2 and !function_exists('mcrypt_create_iv')) $rino=1;

		logger("Local rino version: ". $rino, LOGGER_DEBUG);

		$ssl_val = intval(get_config('system','ssl_policy'));
		$ssl_policy = '';

		switch($ssl_val){
			case SSL_POLICY_FULL:
				$ssl_policy = 'full';
				break;
			case SSL_POLICY_SELFSIGN:
				$ssl_policy = 'self';
				break;
			case SSL_POLICY_NONE:
			default:
				$ssl_policy = 'none';
				break;
		}

		$url = $contact['notify'] . '&dfrn_id=' . $idtosend . '&dfrn_version=' . DFRN_PROTOCOL_VERSION . (($rino) ? '&rino='.$rino : '');

		logger('dfrn_deliver: ' . $url);

		$xml = fetch_url($url);

		$curl_stat = $a->get_curl_code();
		if(! $curl_stat)
			return(-1); // timed out

		logger('dfrn_deliver: ' . $xml, LOGGER_DATA);

		if(! $xml)
			return 3;

		if(strpos($xml,'<?xml') === false) {
			logger('dfrn_deliver: no valid XML returned');
			logger('dfrn_deliver: returned XML: ' . $xml, LOGGER_DATA);
			return 3;
		}

		$res = parse_xml_string($xml);

		if((intval($res->status) != 0) || (! strlen($res->challenge)) || (! strlen($res->dfrn_id)))
			return (($res->status) ? $res->status : 3);

		$postvars     = array();
		$sent_dfrn_id = hex2bin((string) $res->dfrn_id);
		$challenge    = hex2bin((string) $res->challenge);
		$perm         = (($res->perm) ? $res->perm : null);
		$dfrn_version = (float) (($res->dfrn_version) ? $res->dfrn_version : 2.0);
		$rino_remote_version = intval($res->rino);
		$page         = (($owner['page-flags'] == PAGE_COMMUNITY) ? 1 : 0);

		logger("Remote rino version: ".$rino_remote_version." for ".$contact["url"], LOGGER_DEBUG);

		if($owner['page-flags'] == PAGE_PRVGROUP)
			$page = 2;

		$final_dfrn_id = '';

		if($perm) {
			if((($perm == 'rw') && (! intval($contact['writable'])))
				|| (($perm == 'r') && (intval($contact['writable'])))) {
				q("update contact set writable = %d where id = %d",
					intval(($perm == 'rw') ? 1 : 0),
					intval($contact['id'])
				);
				$contact['writable'] = (string) 1 - intval($contact['writable']);
			}
		}

		if(($contact['duplex'] && strlen($contact['pubkey']))
			|| ($owner['page-flags'] == PAGE_COMMUNITY && strlen($contact['pubkey']))
			|| ($contact['rel'] == CONTACT_IS_SHARING && strlen($contact['pubkey']))) {
			openssl_public_decrypt($sent_dfrn_id,$final_dfrn_id,$contact['pubkey']);
			openssl_public_decrypt($challenge,$postvars['challenge'],$contact['pubkey']);
		} else {
			openssl_private_decrypt($sent_dfrn_id,$final_dfrn_id,$contact['prvkey']);
			openssl_private_decrypt($challenge,$postvars['challenge'],$contact['prvkey']);
		}

		$final_dfrn_id = substr($final_dfrn_id, 0, strpos($final_dfrn_id, '.'));

		if(strpos($final_dfrn_id,':') == 1)
			$final_dfrn_id = substr($final_dfrn_id,2);

		if($final_dfrn_id != $orig_id) {
			logger('dfrn_deliver: wrong dfrn_id.');
			// did not decode properly - cannot trust this site
			return 3;
		}

		$postvars['dfrn_id']      = $idtosend;
		$postvars['dfrn_version'] = DFRN_PROTOCOL_VERSION;
		if($dissolve)
			$postvars['dissolve'] = '1';


		if((($contact['rel']) && ($contact['rel'] != CONTACT_IS_SHARING) && (! $contact['blocked'])) || ($owner['page-flags'] == PAGE_COMMUNITY)) {
			$postvars['data'] = $atom;
			$postvars['perm'] = 'rw';
		} else {
			$postvars['data'] = str_replace('<dfrn:comment-allow>1','<dfrn:comment-allow>0',$atom);
			$postvars['perm'] = 'r';
		}

		$postvars['ssl_policy'] = $ssl_policy;

		if($page)
			$postvars['page'] = $page;


		if($rino>0 && $rino_remote_version>0 && (! $dissolve)) {
			logger('rino version: '. $rino_remote_version);

			switch($rino_remote_version) {
				case 1:
					// Deprecated rino version!
					$key = substr(random_string(),0,16);
					$data = aes_encrypt($postvars['data'],$key);
					break;
				case 2:
					// RINO 2 based on php-encryption
					try {
						$key = Crypto::createNewRandomKey();
					} catch (CryptoTestFailed $ex) {
						logger('Cannot safely create a key');
						return -1;
					} catch (CannotPerformOperation $ex) {
						logger('Cannot safely create a key');
						return -1;
					}
					try {
						$data = Crypto::encrypt($postvars['data'], $key);
					} catch (CryptoTestFailed $ex) {
						logger('Cannot safely perform encryption');
						return -1;
					} catch (CannotPerformOperation $ex) {
						logger('Cannot safely perform encryption');
						return -1;
					}
					break;
				default:
					logger("rino: invalid requested verision '$rino_remote_version'");
					return -1;
			}

			$postvars['rino'] = $rino_remote_version;
			$postvars['data'] = bin2hex($data);

			#logger('rino: sent key = ' . $key, LOGGER_DEBUG);


			if($dfrn_version >= 2.1) {
				if(($contact['duplex'] && strlen($contact['pubkey']))
					|| ($owner['page-flags'] == PAGE_COMMUNITY && strlen($contact['pubkey']))
					|| ($contact['rel'] == CONTACT_IS_SHARING && strlen($contact['pubkey'])))

					openssl_public_encrypt($key,$postvars['key'],$contact['pubkey']);
				else
					openssl_private_encrypt($key,$postvars['key'],$contact['prvkey']);

			} else {
				if(($contact['duplex'] && strlen($contact['prvkey'])) || ($owner['page-flags'] == PAGE_COMMUNITY))
					openssl_private_encrypt($key,$postvars['key'],$contact['prvkey']);
				else
					openssl_public_encrypt($key,$postvars['key'],$contact['pubkey']);

			}

			logger('md5 rawkey ' . md5($postvars['key']));

			$postvars['key'] = bin2hex($postvars['key']);
		}


		logger('dfrn_deliver: ' . "SENDING: " . print_r($postvars,true), LOGGER_DATA);

		$xml = post_url($contact['notify'],$postvars);

		logger('dfrn_deliver: ' . "RECEIVED: " . $xml, LOGGER_DATA);

		$curl_stat = $a->get_curl_code();
		if((! $curl_stat) || (! strlen($xml)))
			return(-1); // timed out

		if(($curl_stat == 503) && (stristr($a->get_curl_headers(),'retry-after')))
			return(-1);

		if(strpos($xml,'<?xml') === false) {
			logger('dfrn_deliver: phase 2: no valid XML returned');
			logger('dfrn_deliver: phase 2: returned XML: ' . $xml, LOGGER_DATA);
			return 3;
		}

		if($contact['term-date'] != '0000-00-00 00:00:00') {
			logger("dfrn_deliver: $url back from the dead - removing mark for death");
			require_once('include/Contact.php');
			unmark_for_death($contact);
		}

		$res = parse_xml_string($xml);

		return $res->status;
	}
}
