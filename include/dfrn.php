<?php
require_once('include/items.php');
require_once('include/ostatus.php');

/**
 * @brief Adds the header elements for the DFRN protocol
 *
 * @param array $items Item elements
 * @param array $owner Owner record
 *
 * @return string DFRN entries
 */
function dfrn_entries($items,$owner) {

	$doc = new DOMDocument('1.0', 'utf-8');
	$doc->formatOutput = true;

	$root = dfrn_add_header($doc, $owner, "dfrn:owner", "", false);

	if(! count($items))
		return trim($doc->saveXML());

	foreach($items as $item) {
		$entry = dfrn_entry($doc, "text", $item, $owner, $item["entry:comment-allow"], $item["entry:cid"]);
		$root->appendChild($entry);
	}

	return(trim($doc->saveXML()));
}

/**
 * @brief Adds the header elements for the DFRN protocol
 *
 * @param App $a
 * @param string $dfrn_id
 * @param string $owner_nick Owner nick name
 * @param string $last_update Date of the last update
 * @param int $direction
 *
 * @return string DFRN feed entries
 */
function dfrn_feed(&$a, $dfrn_id, $owner_nick, $last_update, $direction = 0) {

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
		}
		else
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

	$root = dfrn_add_header($doc, $owner, "author", $alternatelink, true);

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
		}
		else {
			$type = 'text';
		}

		$entry = dfrn_entry($doc, $type, $item, $owner, true);
		$root->appendChild($entry);

	}

	$atom = trim($doc->saveXML());

	call_hooks('atom_feed_end', $atom);

	return $atom;
}

/**
 * @brief Create XML text for DFRN mail
 *
 * @param array $item message elements
 * @param array $owner Owner record
 *
 * @return string DFRN mail
 */
function dfrn_mail($item, $owner) {
	$doc = new DOMDocument('1.0', 'utf-8');
	$doc->formatOutput = true;

	$root = dfrn_add_header($doc, $owner, "dfrn:owner", "", false);

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
 * @brief Create XML text for DFRN suggestions
 *
 * @param array $item suggestion elements
 * @param array $owner Owner record
 *
 * @return string DFRN suggestions
 */
function dfrn_fsuggest($item, $owner) {
	$doc = new DOMDocument('1.0', 'utf-8');
	$doc->formatOutput = true;

	$root = dfrn_add_header($doc, $owner, "dfrn:owner", "", false);

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
function dfrn_relocate($owner, $uid) {

	$a = get_app();

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
	foreach($rp as $p){
		$photos[$p['scale']] = $a->get_baseurl().'/photo/'.$p['resource-id'].'-'.$p['scale'].'.'.$ext[$p['type']];
	}
	unset($rp, $ext);

	$doc = new DOMDocument('1.0', 'utf-8');
	$doc->formatOutput = true;

	$root = dfrn_add_header($doc, $owner, "dfrn:owner", "", false);

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
function dfrn_add_header($doc, $owner, $authorelement, $alternatelink = "", $public = false) {
	$a = get_app();

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

	xml_add_element($doc, $root, "id", $a->get_baseurl()."/profile/".$owner["nick"]);
	xml_add_element($doc, $root, "title", $owner["name"]);

	$attributes = array("uri" => "https://friendi.ca", "version" => FRIENDICA_VERSION."-".DB_UPDATE_VERSION);
	xml_add_element($doc, $root, "generator", FRIENDICA_PLATFORM, $attributes);

	$attributes = array("rel" => "license", "href" => "http://creativecommons.org/licenses/by/3.0/");
	xml_add_element($doc, $root, "link", "", $attributes);

	$attributes = array("rel" => "alternate", "type" => "text/html", "href" => $alternatelink);
	xml_add_element($doc, $root, "link", "", $attributes);

	ostatus_hublinks($doc, $root);

	if ($public) {
		$attributes = array("rel" => "salmon", "href" => $a->get_baseurl()."/salmon/".$owner["nick"]);
		xml_add_element($doc, $root, "link", "", $attributes);

		$attributes = array("rel" => "http://salmon-protocol.org/ns/salmon-replies", "href" => $a->get_baseurl()."/salmon/".$owner["nick"]);
		xml_add_element($doc, $root, "link", "", $attributes);

		$attributes = array("rel" => "http://salmon-protocol.org/ns/salmon-mention", "href" => $a->get_baseurl()."/salmon/".$owner["nick"]);
		xml_add_element($doc, $root, "link", "", $attributes);
	}

	if ($owner['page-flags'] == PAGE_COMMUNITY)
		xml_add_element($doc, $root, "dfrn:community", 1);

	xml_add_element($doc, $root, "updated", datetime_convert("UTC", "UTC", "now", ATOM_TIME));

	$author = dfrn_add_author($doc, $owner, $authorelement);
	$root->appendChild($author);

	return $root;
}

/**
 * @brief Adds the author elements for the DFRN protocol
 *
 * @param object $doc XML document
 * @param array $owner Owner record
 * @param string $authorelement Element name for the author
 *
 * @return object XML author object
 */
function dfrn_add_author($doc, $owner, $authorelement) {
	$a = get_app();

	$author = $doc->createElement($authorelement);

	$namdate = datetime_convert('UTC', 'UTC', $owner['name-date'].'+00:00' , ATOM_TIME);
	$uridate = datetime_convert('UTC', 'UTC', $owner['uri-date'].'+00:00', ATOM_TIME);
	$picdate = datetime_convert('UTC', 'UTC', $owner['avatar-date'].'+00:00', ATOM_TIME);

	$attributes = array("dfrn:updated" => $namdate);
	xml_add_element($doc, $author, "name", $owner["name"], $attributes);

	$attributes = array("dfrn:updated" => $namdate);
	xml_add_element($doc, $author, "uri", $a->get_baseurl().'/profile/'.$owner["nickname"], $attributes);

	$attributes = array("rel" => "photo", "type" => "image/jpeg", "dfrn:updated" => $picdate,
				"media:width" => 175, "media:height" => 175, "href" => $owner['photo']);
	xml_add_element($doc, $author, "link", "", $attributes);

	$attributes = array("rel" => "avatar", "type" => "image/jpeg", "dfrn:updated" => $picdate,
				"media:width" => 175, "media:height" => 175, "href" => $owner['photo']);
	xml_add_element($doc, $author, "link", "", $attributes);

	$birthday = feed_birthday($owner['user_uid'], $owner['timezone']);

	if ($birthday)
		xml_add_element($doc, $author, "dfrn:birthday", $birthday);

	return $author;
}

/**
 * @brief Adds the author elements for the item entries of the DFRN protocol
 *
 * @param object $doc XML document
 * @param string $element Element name for the author
 * @param string $contact_url Link of the contact
 * @param array $items Item elements
 *
 * @return object XML author object
 */
function dfrn_add_entry_author($doc, $element, $contact_url, $item) {
	$a = get_app();

	$contact = get_contact_details_by_url($contact_url, $item["uid"]);

	$r = q("SELECT `profile`.`about`, `profile`.`name`, `profile`.`homepage`, `contact`.`nick`, `contact`.`location` FROM `profile`
			INNER JOIN `contact` ON `contact`.`uid` = `profile`.`uid`
			INNER JOIN `user` ON `user`.`uid` = `profile`.`uid`
			WHERE `contact`.`self` AND `profile`.`is-default` AND NOT `user`.`hidewall` AND `contact`.`nurl`='%s'",
		dbesc(normalise_link($contact_url)));
	if ($r)
		$profile = $r[0];

	$author = $doc->createElement($element);
	xml_add_element($doc, $author, "name", $contact["name"]);
	xml_add_element($doc, $author, "uri", $contact["url"]);

	/// @Todo
	/// - Check real image type and image size
	/// - Check which of these boths elements we really use
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

	// Only show contact details when it is a user from our system and we are allowed to
	if ($profile) {
		xml_add_element($doc, $author, "poco:preferredUsername", $profile["nick"]);
		xml_add_element($doc, $author, "poco:displayName", $profile["name"]);
		xml_add_element($doc, $author, "poco:note", $profile["about"]);

		if (trim($contact["location"]) != "") {
			$element = $doc->createElement("poco:address");
			xml_add_element($doc, $element, "poco:formatted", $profile["location"]);
			$author->appendChild($element);
		}

		if (trim($profile["homepage"]) != "") {
			$urls = $doc->createElement("poco:urls");
			xml_add_element($doc, $urls, "poco:type", "homepage");
			xml_add_element($doc, $urls, "poco:value", $profile["homepage"]);
			xml_add_element($doc, $urls, "poco:primary", "true");
			$author->appendChild($urls);
		}
	}

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
function dfrn_create_activity($doc, $element, $activity) {

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
 * @brief Adds the attachments elements
 *
 * @param object $doc XML document
 * @param object $root XML root
 * @param array $item Item element
 *
 * @return object XML attachment object
 */
function dfrn_get_attachment($doc, $root, $item) {
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
 * @brief Adds the header elements for the DFRN protocol
 *
 * @param object $doc XML document
 * @param string $type "text" or "html"
 * @param array $item Item element
 * @param array $owner Owner record
 * @param bool $comment Trigger the sending of the "comment" element
 * @param int $cid
 *
 * @return object XML entry object
 */
function dfrn_entry($doc, $type, $item, $owner, $comment = false, $cid = 0) {
	$a = get_app();

	$mentioned = array();

	if(!$item['parent'])
		return;

	$entry = $doc->createElement("entry");

	if($item['deleted']) {
		$attributes = array("ref" => $item['uri'], "when" => datetime_convert('UTC','UTC',$item['edited'] . '+00:00',ATOM_TIME));
		xml_add_element($doc, $entry, "at:deleted-entry", "", $attributes);
		return $entry;
	}

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

	$author = dfrn_add_entry_author($doc, "author", $item["author-link"], $item);
	$entry->appendChild($author);

	$dfrnowner = dfrn_add_entry_author($doc, "dfrn:owner", $item["owner-link"], $item);
	$entry->appendChild($dfrnowner);

	if(($item['parent'] != $item['id']) || ($item['parent-uri'] !== $item['uri']) || (($item['thr-parent'] !== '') && ($item['thr-parent'] !== $item['uri']))) {
		$parent = q("SELECT `guid` FROM `item` WHERE `id` = %d", intval($item["parent"]));
		$parent_item = (($item['thr-parent']) ? $item['thr-parent'] : $item['parent-uri']);
		$attributes = array("ref" => $parent_item, "type" => "text/html", "href" => $a->get_baseurl().'/display/'.$parent[0]['guid']);
		xml_add_element($doc, $entry, "thr:in-reply-to", "", $attributes);
	}

	xml_add_element($doc, $entry, "id", $item["uri"]);
	xml_add_element($doc, $entry, "title", $item["title"]);

	xml_add_element($doc, $entry, "published", datetime_convert("UTC","UTC",$item["created"]."+00:00",ATOM_TIME));
	xml_add_element($doc, $entry, "updated", datetime_convert("UTC","UTC",$item["edited"]."+00:00",ATOM_TIME));

	xml_add_element($doc, $entry, "dfrn:env", base64url_encode($body, true));
	xml_add_element($doc, $entry, "content", (($type === 'html') ? $htmlbody : $body), array("type" => $type));

	xml_add_element($doc, $entry, "link", "", array("rel" => "alternate", "type" => "text/html",
							"href" => $a->get_baseurl()."/display/".$item["guid"]));

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

	if($item['signed_text']) {
		$sign = base64_encode(json_encode(array('signed_text' => $item['signed_text'],'signature' => $item['signature'],'signer' => $item['signer'])));
		xml_add_element($doc, $entry, "dfrn:diaspora_signature", $sign);
	}

	xml_add_element($doc, $entry, "activity:verb", construct_verb($item));

	$actobj = dfrn_create_activity($doc, "activity:object", $item['object']);
	if ($actobj)
		$entry->appendChild($actobj);

	$actarg = dfrn_create_activity($doc, "activity:target", $item['target']);
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

	dfrn_get_attachment($doc, $entry, $item);

	return $entry;
}
