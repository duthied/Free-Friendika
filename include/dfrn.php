<?php

require_once('include/items.php');

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

	$birthday = feed_birthday($owner_id,$owner['timezone']);

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

	//$feed_template = get_markup_template(($dfrn_id) ? 'atom_feed_dfrn.tpl' : 'atom_feed.tpl');

	//$atom = '';
	$doc = new DOMDocument('1.0', 'utf-8');
	$doc->formatOutput = true;

        $xpath = new DomXPath($doc);
        $xpath->registerNamespace('atom', "http://www.w3.org/2005/Atom");
        $xpath->registerNamespace('thr', "http://purl.org/syndication/thread/1.0");
        $xpath->registerNamespace('georss', "http://www.georss.org/georss");
        $xpath->registerNamespace('activity', "http://activitystrea.ms/spec/1.0/");
        $xpath->registerNamespace('media', "http://purl.org/syndication/atommedia");
        $xpath->registerNamespace('poco', "http://portablecontacts.net/spec/1.0");
        $xpath->registerNamespace('ostatus', "http://ostatus.org/schema/1.0");
        $xpath->registerNamespace('statusnet', "http://status.net/schema/api/1/");

	$root = ostatus_add_header($doc, $owner);
	dfrn_add_header($root, $doc, $xpath, $owner);

	// Todo $hubxml = feed_hublinks();

	// Todo $salmon = feed_salmonlinks($owner_nick);

	// todo $alternatelink = $owner['url'];

/*
	if(isset($category))
		$alternatelink .= "/category/".$category;

	$atom .= replace_macros($feed_template, array(
		'$version'      => xmlify(FRIENDICA_VERSION),
		'$feed_id'      => xmlify($a->get_baseurl() . '/profile/' . $owner_nick),
		'$feed_title'   => xmlify($owner['name']),
		'$feed_updated' => xmlify(datetime_convert('UTC', 'UTC', 'now' , ATOM_TIME)) ,
		'$hub'          => $hubxml,
		'$salmon'       => $salmon,
		'$alternatelink' => xmlify($alternatelink),
		'$name'         => xmlify($owner['name']),
		'$profile_page' => xmlify($owner['url']),
		'$photo'        => xmlify($owner['photo']),
		'$thumb'        => xmlify($owner['thumb']),
		'$picdate'      => xmlify(datetime_convert('UTC','UTC',$owner['avatar-date'] . '+00:00' , ATOM_TIME)) ,
		'$uridate'      => xmlify(datetime_convert('UTC','UTC',$owner['uri-date']    . '+00:00' , ATOM_TIME)) ,
		'$namdate'      => xmlify(datetime_convert('UTC','UTC',$owner['name-date']   . '+00:00' , ATOM_TIME)) ,
		'$birthday'     => ((strlen($birthday)) ? '<dfrn:birthday>' . xmlify($birthday) . '</dfrn:birthday>' : ''),
		'$community'    => (($owner['page-flags'] == PAGE_COMMUNITY) ? '<dfrn:community>1</dfrn:community>' : '')
	));
*/
	call_hooks('atom_feed', $atom);

	if(! count($items)) {

		call_hooks('atom_feed_end', $atom);

		//$atom .= '</feed>' . "\r\n";
		//return $atom;
		return(trim($doc->saveXML()));
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

		//$atom .= atom_entry($item,$type,null,$owner,true);
		$entry = ostatus_entry($doc, $item, $owner);
		dfrn_entry($entry, $doc, $xpath, $item, $owner);
		$root->appendChild($entry);

	}

	call_hooks('atom_feed_end', $atom);

	//$atom .= '</feed>' . "\r\n";

	//return $atom;
	return(trim($doc->saveXML()));
}

/**
 * @brief Adds the header elements for thr DFRN protocol
 *
 * We use the XML from OStatus as a base and are adding the DFRN parts to it.
 *
 * @root Class XML root element
 * @doc Class XML document
 * @xpath Class XML xpath
 * @owner array Owner record
 *
 */
function dfrn_add_header(&$root, $doc, $xpath, $owner) {

	$root->setAttribute("xmlns:at", "http://purl.org/atompub/tombstones/1.0");
	$root->setAttribute("xmlns:xmlns:dfrn", "http://purl.org/macgirvin/dfrn/1.0");

	$attributes = array("href" => "http://creativecommons.org/licenses/by/3.0/", "rel" => "license");
	xml_add_element($doc, $root, "link", "", $attributes);

	xml_replace_element($doc, $root, $xpath, "title", $owner["name"]);
	xml_remove_element($root, $xpath, "/atom:feed/subtitle");
	xml_remove_element($root, $xpath, "/atom:feed/logo");
	xml_remove_element($root, $xpath, "/atom:feed/author");

	$author = dfrn_add_author($doc, $owner);
	$root->appendChild($author);
}

function dfrn_add_author($doc, $owner) {
	$a = get_app();

	$author = $doc->createElement("author");

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

	return $author;
}

function dfrn_entry($entry, $doc, $xpath, $item, $owner) {
	$a = get_app();

	$author = ostatus_add_author($doc, $owner);
	$entry->appendChild($author);

}

function xml_replace_element($doc, $parent, $xpath, $element, $value = "", $attributes = array()) {
	$old_element = $xpath->query("/atom:feed/".$element)->item(0);

	$element = $doc->createElement($element, xmlify($value));

	foreach ($attributes AS $key => $value) {
                $attribute = $doc->createAttribute($key);
                $attribute->value = xmlify($value);
                $element->appendChild($attribute);
        }

	$parent->replaceChild($element, $old_element);
}

function xml_remove_element($parent, $xpath, $element) {
	$old_element = $xpath->query($element)->item(0);
	$parent->removeChild($old_element);
}
