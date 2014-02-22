<?php

require_once("include/bbcode.php");
require_once("include/acl_selectors.php");


// Note: the code in 'item_extract_images' and 'item_redir_and_replace_images'
// is identical to the code in mod/message.php for 'item_extract_images' and
// 'item_redir_and_replace_images'
if(! function_exists('item_extract_images')) {
function item_extract_images($body) {

	$saved_image = array();
	$orig_body = $body;
	$new_body = '';

	$cnt = 0;
	$img_start = strpos($orig_body, '[img');
	$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
	$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
	while(($img_st_close !== false) && ($img_end !== false)) {

		$img_st_close++; // make it point to AFTER the closing bracket
		$img_end += $img_start;

		if(! strcmp(substr($orig_body, $img_start + $img_st_close, 5), 'data:')) {
			// This is an embedded image

			$saved_image[$cnt] = substr($orig_body, $img_start + $img_st_close, $img_end - ($img_start + $img_st_close));
			$new_body = $new_body . substr($orig_body, 0, $img_start) . '[!#saved_image' . $cnt . '#!]';

			$cnt++;
		}
		else
			$new_body = $new_body . substr($orig_body, 0, $img_end + strlen('[/img]'));

		$orig_body = substr($orig_body, $img_end + strlen('[/img]'));

		if($orig_body === false) // in case the body ends on a closing image tag
			$orig_body = '';

		$img_start = strpos($orig_body, '[img');
		$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
		$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
	}

	$new_body = $new_body . $orig_body;

	return array('body' => $new_body, 'images' => $saved_image);
}}

if(! function_exists('item_redir_and_replace_images')) {
function item_redir_and_replace_images($body, $images, $cid) {

	$origbody = $body;
	$newbody = '';

	$cnt = 1;
	$pos = get_bb_tag_pos($origbody, 'url', 1);
	while($pos !== false && $cnt < 1000) {

		$search = '/\[url\=(.*?)\]\[!#saved_image([0-9]*)#!\]\[\/url\]' . '/is';
		$replace = '[url=' . z_path() . '/redir/' . $cid
				   . '?f=1&url=' . '$1' . '][!#saved_image' . '$2' .'#!][/url]';

		$newbody .= substr($origbody, 0, $pos['start']['open']);
		$subject = substr($origbody, $pos['start']['open'], $pos['end']['close'] - $pos['start']['open']);
		$origbody = substr($origbody, $pos['end']['close']);
		if($origbody === false)
			$origbody = '';

		$subject = preg_replace($search, $replace, $subject);
		$newbody .= $subject;

		$cnt++;
		$pos = get_bb_tag_pos($origbody, 'url', 1);
	}
	$newbody .= $origbody;

	$cnt = 0;
	foreach($images as $image) {
		// We're depending on the property of 'foreach' (specified on the PHP website) that
		// it loops over the array starting from the first element and going sequentially
		// to the last element
		$newbody = str_replace('[!#saved_image' . $cnt . '#!]', '[img]' . $image . '[/img]', $newbody);
		$cnt++;
	}
	return $newbody;
}}



/**
 * Render actions localized
 */
function localize_item(&$item){

	$extracted = item_extract_images($item['body']);
	if($extracted['images'])
		$item['body'] = item_redir_and_replace_images($extracted['body'], $extracted['images'], $item['contact-id']);

	$xmlhead="<"."?xml version='1.0' encoding='UTF-8' ?".">";
	if (activity_match($item['verb'],ACTIVITY_LIKE) || activity_match($item['verb'],ACTIVITY_DISLIKE)){

		$r = q("SELECT * from `item`,`contact` WHERE
				`item`.`contact-id`=`contact`.`id` AND `item`.`uri`='%s';",
				 dbesc($item['parent-uri']));
		if(count($r)==0) return;
		$obj=$r[0];

		$author  = '[url=' . $item['author-link'] . ']' . $item['author-name'] . '[/url]';
		$objauthor =  '[url=' . $obj['author-link'] . ']' . $obj['author-name'] . '[/url]';

		switch($obj['verb']){
			case ACTIVITY_POST:
				switch ($obj['object-type']){
					case ACTIVITY_OBJ_EVENT:
						$post_type = t('event');
						break;
					default:
						$post_type = t('status');
				}
				break;
			default:
				if($obj['resource-id']){
					$post_type = t('photo');
					$m=array(); preg_match("/\[url=([^]]*)\]/", $obj['body'], $m);
					$rr['plink'] = $m[1];
				} else {
					$post_type = t('status');
				}
		}

		$plink = '[url=' . $obj['plink'] . ']' . $post_type . '[/url]';

		if(activity_match($item['verb'],ACTIVITY_LIKE)) {
			$bodyverb = t('%1$s likes %2$s\'s %3$s');
		}
		elseif(activity_match($item['verb'],ACTIVITY_DISLIKE)) {
			$bodyverb = t('%1$s doesn\'t like %2$s\'s %3$s');
		}
		$item['body'] = sprintf($bodyverb, $author, $objauthor, $plink);

	}
	if (activity_match($item['verb'],ACTIVITY_FRIEND)) {

		if ($item['object-type']=="" || $item['object-type']!== ACTIVITY_OBJ_PERSON) return;

		$Aname = $item['author-name'];
		$Alink = $item['author-link'];

		$xmlhead="<"."?xml version='1.0' encoding='UTF-8' ?".">";

		$obj = parse_xml_string($xmlhead.$item['object']);
		$links = parse_xml_string($xmlhead."<links>".unxmlify($obj->link)."</links>");

		$Bname = $obj->title;
		$Blink = ""; $Bphoto = "";
		foreach ($links->link as $l){
			$atts = $l->attributes();
			switch($atts['rel']){
				case "alternate": $Blink = $atts['href'];
				case "photo": $Bphoto = $atts['href'];
			}

		}

		$A = '[url=' . zrl($Alink) . ']' . $Aname . '[/url]';
		$B = '[url=' . zrl($Blink) . ']' . $Bname . '[/url]';
		if ($Bphoto!="") $Bphoto = '[url=' . zrl($Blink) . '][img]' . $Bphoto . '[/img][/url]';

		$item['body'] = sprintf( t('%1$s is now friends with %2$s'), $A, $B)."\n\n\n".$Bphoto;

	}
	if (stristr($item['verb'],ACTIVITY_POKE)) {
		$verb = urldecode(substr($item['verb'],strpos($item['verb'],'#')+1));
		if(! $verb)
			return;
		if ($item['object-type']=="" || $item['object-type']!== ACTIVITY_OBJ_PERSON) return;

		$Aname = $item['author-name'];
		$Alink = $item['author-link'];

		$xmlhead="<"."?xml version='1.0' encoding='UTF-8' ?".">";

		$obj = parse_xml_string($xmlhead.$item['object']);
		$links = parse_xml_string($xmlhead."<links>".unxmlify($obj->link)."</links>");

		$Bname = $obj->title;
		$Blink = ""; $Bphoto = "";
		foreach ($links->link as $l){
			$atts = $l->attributes();
			switch($atts['rel']){
				case "alternate": $Blink = $atts['href'];
				case "photo": $Bphoto = $atts['href'];
			}

		}

		$A = '[url=' . zrl($Alink) . ']' . $Aname . '[/url]';
		$B = '[url=' . zrl($Blink) . ']' . $Bname . '[/url]';
		if ($Bphoto!="") $Bphoto = '[url=' . zrl($Blink) . '][img=80x80]' . $Bphoto . '[/img][/url]';

		// we can't have a translation string with three positions but no distinguishable text
		// So here is the translate string.

		$txt = t('%1$s poked %2$s');

		// now translate the verb

		$txt = str_replace( t('poked'), t($verb), $txt);

		// then do the sprintf on the translation string

		$item['body'] = sprintf($txt, $A, $B). "\n\n\n" . $Bphoto;

	}
	if (stristr($item['verb'],ACTIVITY_MOOD)) {
		$verb = urldecode(substr($item['verb'],strpos($item['verb'],'#')+1));
		if(! $verb)
			return;

		$Aname = $item['author-name'];
		$Alink = $item['author-link'];
		$A = '[url=' . zrl($Alink) . ']' . $Aname . '[/url]';

		$txt = t('%1$s is currently %2$s');

		$item['body'] = sprintf($txt, $A, t($verb));
	}

	if (activity_match($item['verb'],ACTIVITY_TAG)) {
		$r = q("SELECT * from `item`,`contact` WHERE
		`item`.`contact-id`=`contact`.`id` AND `item`.`uri`='%s';",
		 dbesc($item['parent-uri']));
		if(count($r)==0) return;
		$obj=$r[0];

		$author  = '[url=' . zrl($item['author-link']) . ']' . $item['author-name'] . '[/url]';
		$objauthor =  '[url=' . zrl($obj['author-link']) . ']' . $obj['author-name'] . '[/url]';

		switch($obj['verb']){
			case ACTIVITY_POST:
				switch ($obj['object-type']){
					case ACTIVITY_OBJ_EVENT:
						$post_type = t('event');
						break;
					default:
						$post_type = t('status');
				}
				break;
			default:
				if($obj['resource-id']){
					$post_type = t('photo');
					$m=array(); preg_match("/\[url=([^]]*)\]/", $obj['body'], $m);
					$rr['plink'] = $m[1];
				} else {
					$post_type = t('status');
				}
		}
		$plink = '[url=' . $obj['plink'] . ']' . $post_type . '[/url]';

		$parsedobj = parse_xml_string($xmlhead.$item['object']);

		$tag = sprintf('#[url=%s]%s[/url]', $parsedobj->id, $parsedobj->content);
		$item['body'] = sprintf( t('%1$s tagged %2$s\'s %3$s with %4$s'), $author, $objauthor, $plink, $tag );

	}
	if (activity_match($item['verb'],ACTIVITY_FAVORITE)){

		if ($item['object-type']== "")
			return;

		$Aname = $item['author-name'];
		$Alink = $item['author-link'];

		$xmlhead="<"."?xml version='1.0' encoding='UTF-8' ?".">";

		$obj = parse_xml_string($xmlhead.$item['object']);
		if(strlen($obj->id)) {
			$r = q("select * from item where uri = '%s' and uid = %d limit 1",
					dbesc($obj->id),
					intval($item['uid'])
			);
			if(count($r) && $r[0]['plink']) {
				$target = $r[0];
				$Bname = $target['author-name'];
				$Blink = $target['author-link'];
				$A = '[url=' . zrl($Alink) . ']' . $Aname . '[/url]';
				$B = '[url=' . zrl($Blink) . ']' . $Bname . '[/url]';
				$P = '[url=' . $target['plink'] . ']' . t('post/item') . '[/url]';
				$item['body'] = sprintf( t('%1$s marked %2$s\'s %3$s as favorite'), $A, $B, $P)."\n";

			}
		}
	}
	$matches = null;
	if(preg_match_all('/@\[url=(.*?)\]/is',$item['body'],$matches,PREG_SET_ORDER)) {
		foreach($matches as $mtch) {
			if(! strpos($mtch[1],'zrl='))
				$item['body'] = str_replace($mtch[0],'@[url=' . zrl($mtch[1]). ']',$item['body']);
		}
	}

	// add zrl's to public images
	$photo_pattern = "/\[url=(.*?)\/photos\/(.*?)\/image\/(.*?)\]\[img(.*?)\]h(.*?)\[\/img\]\[\/url\]/is";
	if(preg_match($photo_pattern,$item['body'])) {
		$photo_replace = '[url=' . zrl('$1' . '/photos/' . '$2' . '/image/' . '$3' ,true) . '][img' . '$4' . ']h' . '$5'  . '[/img][/url]';
		$item['body'] = bb_tag_preg_replace($photo_pattern, $photo_replace, 'url', $item['body']);
	}

	// add sparkle links to appropriate permalinks

	$x = stristr($item['plink'],'/display/');
	if($x) {
		$sparkle = false;
		$y = best_link_url($item,$sparkle,true);
		if(strstr($y,'/redir/'))
			$item['plink'] = $y . '?f=&url=' . $item['plink'];
	}



}

/**
 * Count the total of comments on this item and its desendants
 */
function count_descendants($item) {
	$total = count($item['children']);

	if($total > 0) {
		foreach($item['children'] as $child) {
			if(! visible_activity($child))
				$total --;
			$total += count_descendants($child);
		}
	}

	return $total;
}

function visible_activity($item) {

	if(activity_match($item['verb'],ACTIVITY_LIKE) || activity_match($item['verb'],ACTIVITY_DISLIKE))
		return false;

	if(activity_match($item['verb'],ACTIVITY_FOLLOW) && $item['object-type'] === ACTIVITY_OBJ_NOTE) {
		if(! (($item['self']) && ($item['uid'] == local_user()))) {
			return false;
		}
	}

	return true;
}


/**
 * "Render" a conversation or list of items for HTML display.
 * There are two major forms of display:
 *      - Sequential or unthreaded ("New Item View" or search results)
 *      - conversation view
 * The $mode parameter decides between the various renderings and also
 * figures out how to determine page owner and other contextual items
 * that are based on unique features of the calling module.
 *
 */

if(!function_exists('conversation')) {
function conversation(&$a, $items, $mode, $update, $preview = false) {

	require_once('include/bbcode.php');

	$ssl_state = ((local_user()) ? true : false);

	$profile_owner = 0;
	$page_writeable = false;
	$live_update_div = '';

	$arr_blocked = null;

	if(local_user()) {
		$str_blocked = get_pconfig(local_user(),'system','blocked');
		if($str_blocked) {
			$arr_blocked = explode(',',$str_blocked);
			for($x = 0; $x < count($arr_blocked); $x ++)
				$arr_blocked[$x] = trim($arr_blocked[$x]);
		}

	}

	$previewing = (($preview) ? ' preview ' : '');

	if($mode === 'network') {
		$profile_owner = local_user();
		$page_writeable = true;
		if(!$update) {
			// The special div is needed for liveUpdate to kick in for this page.
			// We only launch liveUpdate if you aren't filtering in some incompatible 
			// way and also you aren't writing a comment (discovered in javascript).

			$live_update_div = '<div id="live-network"></div>' . "\r\n"
				. "<script> var profile_uid = " . $_SESSION['uid'] 
				. "; var netargs = '" . substr($a->cmd,8)
				. '?f='
				. ((x($_GET,'cid'))    ? '&cid='    . $_GET['cid']    : '')
				. ((x($_GET,'search')) ? '&search=' . $_GET['search'] : '') 
				. ((x($_GET,'star'))   ? '&star='   . $_GET['star']   : '') 
				. ((x($_GET,'order'))  ? '&order='  . $_GET['order']  : '') 
				. ((x($_GET,'bmark'))  ? '&bmark='  . $_GET['bmark']  : '') 
				. ((x($_GET,'liked'))  ? '&liked='  . $_GET['liked']  : '') 
				. ((x($_GET,'conv'))   ? '&conv='   . $_GET['conv']   : '') 
				. ((x($_GET,'spam'))   ? '&spam='   . $_GET['spam']   : '') 
				. ((x($_GET,'nets'))   ? '&nets='   . $_GET['nets']   : '') 
				. ((x($_GET,'cmin'))   ? '&cmin='   . $_GET['cmin']   : '') 
				. ((x($_GET,'cmax'))   ? '&cmax='   . $_GET['cmax']   : '') 
				. ((x($_GET,'file'))   ? '&file='   . $_GET['file']   : '') 

				. "'; var profile_page = " . $a->pager['page'] . "; </script>\r\n";
		}
	}
	else if($mode === 'profile') {
		$profile_owner = $a->profile['profile_uid'];
		$page_writeable = can_write_wall($a,$profile_owner);

		if(!$update) {
			$tab = notags(trim($_GET['tab']));
			$tab = ( $tab ? $tab : 'posts' );
			if($tab === 'posts') {
				// This is ugly, but we can't pass the profile_uid through the session to the ajax updater,
				// because browser prefetching might change it on us. We have to deliver it with the page.

				$live_update_div = '<div id="live-profile"></div>' . "\r\n"
					. "<script> var profile_uid = " . $a->profile['profile_uid'] 
					. "; var netargs = '?f='; var profile_page = " . $a->pager['page'] . "; </script>\r\n";
			}
		}
	}
	else if($mode === 'notes') {
		$profile_owner = local_user();
		$page_writeable = true;
		if(!$update) {
			$live_update_div = '<div id="live-notes"></div>' . "\r\n"
				. "<script> var profile_uid = " . local_user() 
				. "; var netargs = '/?f='; var profile_page = " . $a->pager['page'] . "; </script>\r\n";
		}
	}
	else if($mode === 'display') {
		$profile_owner = $a->profile['uid'];
		$page_writeable = can_write_wall($a,$profile_owner);
		if(!$update) {
			$live_update_div = '<div id="live-display"></div>' . "\r\n"
				. "<script> var profile_uid = " . $_SESSION['uid'] . ";"
				. " var profile_page = 1; </script>";
		}
	}
	else if($mode === 'community') {
		$profile_owner = 0;
		$page_writeable = false;
		if(!$update) {
			$live_update_div = '<div id="live-community"></div>' . "\r\n"
				. "<script> var profile_uid = -1; var netargs = '/?f='; var profile_page = " . $a->pager['page'] . "; </script>\r\n";
		}
	}
	else if($mode === 'search') {
		$live_update_div = '<div id="live-search"></div>' . "\r\n";
	}

	$page_dropping = ((local_user() && local_user() == $profile_owner) ? true : false);


	if($update)
		$return_url = $_SESSION['return_url'];
	else
		$return_url = $_SESSION['return_url'] = $a->query_string;

	load_contact_links(local_user());

	$cb = array('items' => $items, 'mode' => $mode, 'update' => $update, 'preview' => $preview);
	call_hooks('conversation_start',$cb);

	$items = $cb['items'];

	$cmnt_tpl    = get_markup_template('comment_item.tpl');
	$hide_comments_tpl = get_markup_template('hide_comments.tpl');

	$alike = array();
	$dlike = array();

	// array with html for each thread (parent+comments)
	$threads = array();
	$threadsid = -1;

	$page_template = get_markup_template("conversation.tpl");

	if($items && count($items)) {

		if($mode === 'network-new' || $mode === 'search' || $mode === 'community') {

			// "New Item View" on network page or search page results
			// - just loop through the items and format them minimally for display

//			$tpl = get_markup_template('search_item.tpl');
			$tpl = 'search_item.tpl';

			foreach($items as $item) {

				if($arr_blocked) {
					$blocked = false;
					foreach($arr_blocked as $b) {
						if($b && link_compare($item['author-link'],$b)) {
							$blocked = true;
							break;
						}
					}
					if($blocked)
						continue;
				}


				$threadsid++;

				$comment     = '';
				$owner_url   = '';
				$owner_photo = '';
				$owner_name  = '';
				$sparkle     = '';

				if($mode === 'search' || $mode === 'community') {
					if(((activity_match($item['verb'],ACTIVITY_LIKE)) || (activity_match($item['verb'],ACTIVITY_DISLIKE)))
						&& ($item['id'] != $item['parent']))
						continue;
					$nickname = $item['nickname'];
				}
				else
					$nickname = $a->user['nickname'];

				// prevent private email from leaking.
				if($item['network'] === NETWORK_MAIL && local_user() != $item['uid'])
						continue;

				$profile_name   = ((strlen($item['author-name']))   ? $item['author-name']   : $item['name']);
				if($item['author-link'] && (! $item['author-name']))
					$profile_name = $item['author-link'];



				$tags=array();
				$hashtags = array();
				$mentions = array();

				$taglist = q("SELECT `type`, `term`, `url` FROM `term` WHERE `otype` = %d AND `oid` = %d AND `type` IN (%d, %d) ORDER BY `tid`",
						intval(TERM_OBJ_POST), intval($item['id']), intval(TERM_HASHTAG), intval(TERM_MENTION));

				foreach($taglist as $tag) {

					if ($tag["url"] == "")
						$tag["url"] = $searchpath.strtolower($tag["term"]);

					if ($tag["type"] == TERM_HASHTAG) {
						$hashtags[] = "#<a href=\"".$tag["url"]."\" target=\"external-link\">".$tag["term"]."</a>";
						$prefix = "#";
					} elseif ($tag["type"] == TERM_MENTION) {
						$mentions[] = "@<a href=\"".$tag["url"]."\" target=\"external-link\">".$tag["term"]."</a>";
						$prefix = "@";
					}
					$tags[] = $prefix."<a href=\"".$tag["url"]."\" target=\"external-link\">".$tag["term"]."</a>";
				}

				/*foreach(explode(',',$item['tag']) as $tag){
					$tag = trim($tag);
					if ($tag!="") {
						$t = bbcode($tag);
						$tags[] = $t;
						if($t[0] == '#')
							$hashtags[] = $t;
						elseif($t[0] == '@')
							$mentions[] = $t;
					}
				}*/

				$sp = false;
				$profile_link = best_link_url($item,$sp);
				if($profile_link === 'mailbox')
					$profile_link = '';
				if($sp)
					$sparkle = ' sparkle';
				else
					$profile_link = zrl($profile_link);

				$normalised = normalise_link((strlen($item['author-link'])) ? $item['author-link'] : $item['url']);
				if(($normalised != 'mailbox') && (x($a->contacts[$normalised])))
					$profile_avatar = $a->contacts[$normalised]['thumb'];
				else
					$profile_avatar = ((strlen($item['author-avatar'])) ? $a->get_cached_avatar_image($item['author-avatar']) : $item['thumb']);

				$locate = array('location' => $item['location'], 'coord' => $item['coord'], 'html' => '');
				call_hooks('render_location',$locate);

				$location = ((strlen($locate['html'])) ? $locate['html'] : render_location_google($locate));

				localize_item($item);
				if($mode === 'network-new')
					$dropping = true;
				else
					$dropping = false;


				$drop = array(
					'dropping' => $dropping,
					'pagedrop' => $page_dropping,
					'select' => t('Select'),
					'delete' => t('Delete'),
				);

				$star = false;
				$isstarred = "unstarred";

				$lock = false;
				$likebuttons = false;
				$shareable = false;

				$body = prepare_body($item,true);


				list($categories, $folders) = get_cats_and_terms($item);

				if($a->theme['template_engine'] === 'internal') {
					$profile_name_e = template_escape($profile_name);
					$item['title_e'] = template_escape($item['title']);
					$body_e = template_escape($body);
					$tags_e = template_escape($tags);
					$hashtags_e = template_escape($hashtags);
					$mentions_e = template_escape($mentions);
					$location_e = template_escape($location);
					$owner_name_e = template_escape($owner_name);
				}
				else {
					$profile_name_e = $profile_name;
					$item['title_e'] = $item['title'];
					$body_e = $body;
					$tags_e = $tags;
					$hashtags_e = $hashtags;
					$mentions_e = $mentions;
					$location_e = $location;
					$owner_name_e = $owner_name;
				}

				$tmp_item = array(
					'template' => $tpl,
					'id' => (($preview) ? 'P0' : $item['item_id']),
					'network' => $item['item_network'],
					'linktitle' => sprintf( t('View %s\'s profile @ %s'), $profile_name, ((strlen($item['author-link'])) ? $item['author-link'] : $item['url'])),
					'profile_url' => $profile_link,
					'item_photo_menu' => item_photo_menu($item),
					'name' => $profile_name_e,
					'sparkle' => $sparkle,
					'lock' => $lock,
					'thumb' => $profile_avatar,
					'title' => $item['title_e'],
					'body' => $body_e,
					'tags' => $tags_e,
					'hashtags' => $hashtags_e,
					'mentions' => $mentions_e,
					'txt_cats' => t('Categories:'),
					'txt_folders' => t('Filed under:'),
					'has_cats' => ((count($categories)) ? 'true' : ''),
					'has_folders' => ((count($folders)) ? 'true' : ''),
					'categories' => $categories,
					'folders' => $folders,
					'text' => strip_tags($body_e),
					'localtime' => datetime_convert('UTC', date_default_timezone_get(), $item['created'], 'r'),
					'ago' => (($item['app']) ? sprintf( t('%s from %s'),relative_date($item['created']),$item['app']) : relative_date($item['created'])),
					'location' => $location_e,
					'indent' => '',
					'owner_name' => $owner_name_e,
					'owner_url' => $owner_url,
					'owner_photo' => $owner_photo,
					'plink' => get_plink($item),
					'edpost' => false,
					'isstarred' => $isstarred,
					'star' => $star,
					'drop' => $drop,
					'vote' => $likebuttons,
					'like' => '',
					'dislike' => '',
					'comment' => '',
					'conv' => (($preview) ? '' : array('href'=> $a->get_baseurl($ssl_state) . '/display/' . $nickname . '/' . $item['id'], 'title'=> t('View in context'))),
					'previewing' => $previewing,
					'wait' => t('Please wait'),
					'thread_level' => 1,
				);

				$arr = array('item' => $item, 'output' => $tmp_item);
				call_hooks('display_item', $arr);

				$threads[$threadsid]['id'] = $item['item_id'];
				$threads[$threadsid]['network'] = $item['item_network'];
				$threads[$threadsid]['items'] = array($arr['output']);

			}
		}
		else
		{
			// Normal View
			$page_template = get_markup_template("threaded_conversation.tpl");

			require_once('object/Conversation.php');
			require_once('object/Item.php');

			$conv = new Conversation($mode, $preview);

			// get all the topmost parents
			// this shouldn't be needed, as we should have only them in our array
			// But for now, this array respects the old style, just in case

			$threads = array();
			foreach($items as $item) {

				if($arr_blocked) {
					$blocked = false;
					foreach($arr_blocked as $b) {

						if($b && link_compare($item['author-link'],$b)) {
							$blocked = true;
							break;
						}
					}
					if($blocked)
						continue;
				}



				// Can we put this after the visibility check?
				like_puller($a,$item,$alike,'like');
				like_puller($a,$item,$dlike,'dislike');

				// Only add what is visible
				if($item['network'] === NETWORK_MAIL && local_user() != $item['uid']) {
					continue;
				}
				if(! visible_activity($item)) {
					continue;
				}

				call_hooks('display_item', $arr);

				$item['pagedrop'] = $page_dropping;

				if($item['id'] == $item['parent']) {
					$item_object = new Item($item);
					$conv->add_thread($item_object);
				}
			}

			$threads = $conv->get_template_data($alike, $dlike);

			if(!$threads) {
				logger('[ERROR] conversation : Failed to get template data.', LOGGER_DEBUG);
				$threads = array();
			}
		}
	}

	$o = replace_macros($page_template, array(
		'$baseurl' => $a->get_baseurl($ssl_state),
		'$return_path' => $a->query_string,
		'$live_update' => $live_update_div,
		'$remove' => t('remove'),
		'$mode' => $mode,
		'$user' => $a->user,
		'$threads' => $threads,
		'$dropping' => ($page_dropping && feature_enabled(local_user(),'multi_delete') ? t('Delete Selected Items') : False),
	));

	return $o;
}}

function best_link_url($item,&$sparkle,$ssl_state = false) {

	$a = get_app();

	$best_url = '';
	$sparkle  = false;

	$clean_url = normalise_link($item['author-link']);

	if((local_user()) && (local_user() == $item['uid'])) {
		if(isset($a->contacts) && x($a->contacts,$clean_url)) {
			if($a->contacts[$clean_url]['network'] === NETWORK_DFRN) {
				$best_url = $a->get_baseurl($ssl_state) . '/redir/' . $a->contacts[$clean_url]['id'];
				$sparkle = true;
			}
			else
				$best_url = $a->contacts[$clean_url]['url'];
		}
	}
	if(! $best_url) {
		if(strlen($item['author-link']))
			$best_url = $item['author-link'];
		else
			$best_url = $item['url'];
	}

	return $best_url;
}


if(! function_exists('item_photo_menu')){
function item_photo_menu($item){
	$a = get_app();

	$ssl_state = false;

	if(local_user()) {
		$ssl_state = true;
		 if(! count($a->contacts))
			load_contact_links(local_user());
	}
	$sub_link="";
	$poke_link="";
	$contact_url="";
	$pm_url="";
	$status_link="";
	$photos_link="";
	$posts_link="";

	if((local_user()) && local_user() == $item['uid'] && $item['parent'] == $item['id'] && (! $item['self'])) {
		$sub_link = 'javascript:dosubthread(' . $item['id'] . '); return false;';
	}

	$sparkle = false;
	$profile_link = best_link_url($item,$sparkle,$ssl_state);
	if($profile_link === 'mailbox')
		$profile_link = '';

	if($sparkle) {
		$cid = intval(basename($profile_link));
		$status_link = $profile_link . "?url=status";
		$photos_link = $profile_link . "?url=photos";
		$profile_link = $profile_link . "?url=profile";
		$pm_url = $a->get_baseurl($ssl_state) . '/message/new/' . $cid;
		$zurl = '';
	}
	else {
		$profile_link = zrl($profile_link);
		if(local_user() && local_user() == $item['uid'] && link_compare($item['url'],$item['author-link'])) {
			$cid = $item['contact-id'];
		}
		else {
			$cid = 0;
		}
	}
	if(($cid) && (! $item['self'])) {
		$poke_link = $a->get_baseurl($ssl_state) . '/poke/?f=&c=' . $cid;
		$contact_url = $a->get_baseurl($ssl_state) . '/contacts/' . $cid;
		$posts_link = $a->get_baseurl($ssl_state) . '/network/0?nets=all&cid=' . $cid;

		$clean_url = normalise_link($item['author-link']);

		if((local_user()) && (local_user() == $item['uid'])) {
			if(isset($a->contacts) && x($a->contacts,$clean_url)) {
				if($a->contacts[$clean_url]['network'] === NETWORK_DIASPORA) {
					$pm_url = $a->get_baseurl($ssl_state) . '/message/new/' . $cid;
				}
			}
		}

	}

	$menu = Array(
		t("Follow Thread") => $sub_link,
		t("View Status") => $status_link,
		t("View Profile") => $profile_link,
		t("View Photos") => $photos_link,
		t("Network Posts") => $posts_link,
		t("Edit Contact") => $contact_url,
		t("Send PM") => $pm_url,
		t("Poke") => $poke_link
	);


	$args = array('item' => $item, 'menu' => $menu);

	call_hooks('item_photo_menu', $args);

	$menu = $args['menu'];

	$o = "";
	foreach($menu as $k=>$v){
		if(strpos($v,'javascript:') === 0) {
			$v = substr($v,11);
			$o .= "<li><a href=\"#\" onclick=\"$v\">$k</a></li>\n";
		}
		elseif ($v!="") $o .= "<li><a href=\"$v\">$k</a></li>\n";
	}
	return $o;
}}

if(! function_exists('like_puller')) {
function like_puller($a,$item,&$arr,$mode) {

	$url = '';
	$sparkle = '';
	$verb = (($mode === 'like') ? ACTIVITY_LIKE : ACTIVITY_DISLIKE);

	if((activity_match($item['verb'],$verb)) && ($item['id'] != $item['parent'])) {
		$url = $item['author-link'];
		if((local_user()) && (local_user() == $item['uid']) && ($item['network'] === 'dfrn') && (! $item['self']) && (link_compare($item['author-link'],$item['url']))) {
			$url = $a->get_baseurl(true) . '/redir/' . $item['contact-id'];
			$sparkle = ' class="sparkle" ';
		}
		else
			$url = zrl($url);

		if(! $item['thr-parent'])
			$item['thr-parent'] = $item['parent-uri'];

		if(! ((isset($arr[$item['thr-parent'] . '-l'])) && (is_array($arr[$item['thr-parent'] . '-l']))))
			$arr[$item['thr-parent'] . '-l'] = array();
		if(! isset($arr[$item['thr-parent']]))
			$arr[$item['thr-parent']] = 1;
		else
			$arr[$item['thr-parent']] ++;
		$arr[$item['thr-parent'] . '-l'][] = '<a href="'. $url . '"'. $sparkle .'>' . $item['author-name'] . '</a>';
	}
	return;
}}

// Format the like/dislike text for a profile item
// $cnt = number of people who like/dislike the item
// $arr = array of pre-linked names of likers/dislikers
// $type = one of 'like, 'dislike'
// $id  = item id
// returns formatted text

if(! function_exists('format_like')) {
function format_like($cnt,$arr,$type,$id) {
	$o = '';
	if($cnt == 1)
		$o .= (($type === 'like') ? sprintf( t('%s likes this.'), $arr[0]) : sprintf( t('%s doesn\'t like this.'), $arr[0])) . EOL ;
	else {
		$spanatts = "class=\"fakelink\" onclick=\"openClose('{$type}list-$id');\"";
		switch($type) {
			case 'like':
				$phrase = sprintf( t('<span  %1$s>%2$d people</span> like this'), $spanatts, $cnt);
				break;
			case 'dislike':
				$phrase = sprintf( t('<span  %1$s>%2$d people</span> don\'t like this'), $spanatts, $cnt);
				break;
		}
		$phrase .= EOL ;
		$o .= replace_macros(get_markup_template('voting_fakelink.tpl'), array(
			'$phrase' => $phrase,
			'$type' => $type,
			'$id' => $id
		));

		$total = count($arr);
		if($total >= MAX_LIKERS)
			$arr = array_slice($arr, 0, MAX_LIKERS - 1);
		if($total < MAX_LIKERS) {
			$last = t('and') . ' ' . $arr[count($arr)-1];
			$arr2 = array_slice($arr, 0, -1);
			$str = implode(', ', $arr2) . ' ' . $last;
		}
		if($total >= MAX_LIKERS) {
			$str = implode(', ', $arr);
			$str .= sprintf( t(', and %d other people'), $total - MAX_LIKERS );
		}
		$str = (($type === 'like') ? sprintf( t('%s like this.'), $str) : sprintf( t('%s don\'t like this.'), $str));
		$o .= "\t" . '<div class="wall-item-' . $type . '-expanded" id="' . $type . 'list-' . $id . '" style="display: none;" >' . $str . '</div>';
	}
	return $o;
}}


function status_editor($a,$x, $notes_cid = 0, $popup=false) {

	$o = '';

	$geotag = (($x['allow_location']) ? replace_macros(get_markup_template('jot_geotag.tpl'), array()) : '');

/*	$plaintext = false;
	if( local_user() && (intval(get_pconfig(local_user(),'system','plaintext')) || !feature_enabled(local_user(),'richtext')) )
		$plaintext = true;*/
	$plaintext = true;
	if( local_user() && feature_enabled(local_user(),'richtext') )
		$plaintext = false;

	$tpl = get_markup_template('jot-header.tpl');
	$a->page['htmlhead'] .= replace_macros($tpl, array(
		'$newpost' => 'true',
		'$baseurl' => $a->get_baseurl(true),
		'$editselect' => (($plaintext) ? 'none' : '/(profile-jot-text|prvmail-text)/'),
		'$geotag' => $geotag,
		'$nickname' => $x['nickname'],
		'$ispublic' => t('Visible to <strong>everybody</strong>'),
		'$linkurl' => t('Please enter a link URL:'),
		'$vidurl' => t("Please enter a video link/URL:"),
		'$audurl' => t("Please enter an audio link/URL:"),
		'$term' => t('Tag term:'),
		'$fileas' => t('Save to Folder:'),
		'$whereareu' => t('Where are you right now?'),
		'$delitems' => t('Delete item(s)?')
	));


	$tpl = get_markup_template('jot-end.tpl');
	$a->page['end'] .= replace_macros($tpl, array(
		'$newpost' => 'true',
		'$baseurl' => $a->get_baseurl(true),
		'$editselect' => (($plaintext) ? 'none' : '/(profile-jot-text|prvmail-text)/'),
		'$geotag' => $geotag,
		'$nickname' => $x['nickname'],
		'$ispublic' => t('Visible to <strong>everybody</strong>'),
		'$linkurl' => t('Please enter a link URL:'),
		'$vidurl' => t("Please enter a video link/URL:"),
		'$audurl' => t("Please enter an audio link/URL:"),
		'$term' => t('Tag term:'),
		'$fileas' => t('Save to Folder:'),
		'$whereareu' => t('Where are you right now?')
	));


	$jotplugins = '';
	$jotnets = '';

	$mail_disabled = ((function_exists('imap_open') && (! get_config('system','imap_disabled'))) ? 0 : 1);

	$mail_enabled = false;
	$pubmail_enabled = false;

	if(($x['is_owner']) && (! $mail_disabled)) {
		$r = q("SELECT * FROM `mailacct` WHERE `uid` = %d AND `server` != '' LIMIT 1",
			intval(local_user())
		);
		if(count($r)) {
			$mail_enabled = true;
			if(intval($r[0]['pubmail']))
				$pubmail_enabled = true;
		}
	}

	if($mail_enabled) {
		$selected = (($pubmail_enabled) ? ' checked="checked" ' : '');
		$jotnets .= '<div class="profile-jot-net"><input type="checkbox" name="pubmail_enable"' . $selected . ' value="1" /> ' . t("Post to Email") . '</div>';
	}

	call_hooks('jot_tool', $jotplugins);
	call_hooks('jot_networks', $jotnets);

	if($notes_cid)
		$jotnets .= '<input type="hidden" name="contact_allow[]" value="' . $notes_cid .'" />';


	// Private/public post links for the non-JS ACL form
	$private_post = 1;
	if($_REQUEST['public'])
		$private_post = 0;

	$query_str = $a->query_string;
	if(strpos($query_str, 'public=1') !== false)
		$query_str = str_replace(array('?public=1', '&public=1'), array('', ''), $query_str);

	// I think $a->query_string may never have ? in it, but I could be wrong
	// It looks like it's from the index.php?q=[etc] rewrite that the web
	// server does, which converts any ? to &, e.g. suggest&ignore=61 for suggest?ignore=61
	if(strpos($query_str, '?') === false)
		$public_post_link = '?public=1';
	else
		$public_post_link = '&public=1';



//	$tpl = replace_macros($tpl,array('$jotplugins' => $jotplugins));
	$tpl = get_markup_template("jot.tpl");

	$o .= replace_macros($tpl,array(
		'$return_path' => $query_str,
		'$action' =>  $a->get_baseurl(true) . '/item',
		'$share' => (x($x,'button') ? $x['button'] : t('Share')),
		'$upload' => t('Upload photo'),
		'$shortupload' => t('upload photo'),
		'$attach' => t('Attach file'),
		'$shortattach' => t('attach file'),
		'$weblink' => t('Insert web link'),
		'$shortweblink' => t('web link'),
		'$video' => t('Insert video link'),
		'$shortvideo' => t('video link'),
		'$audio' => t('Insert audio link'),
		'$shortaudio' => t('audio link'),
		'$setloc' => t('Set your location'),
		'$shortsetloc' => t('set location'),
		'$noloc' => t('Clear browser location'),
		'$shortnoloc' => t('clear location'),
		'$title' => "",
		'$placeholdertitle' => t('Set title'),
		'$category' => "",
		'$placeholdercategory' => (feature_enabled(local_user(),'categories') ? t('Categories (comma-separated list)') : ''),
		'$wait' => t('Please wait'),
		'$permset' => t('Permission settings'),
		'$shortpermset' => t('permissions'),
		'$ptyp' => (($notes_cid) ? 'note' : 'wall'),
		'$content' => '',
		'$post_id' => '',
		'$baseurl' => $a->get_baseurl(true),
		'$defloc' => $x['default_location'],
		'$visitor' => $x['visitor'],
		'$pvisit' => (($notes_cid) ? 'none' : $x['visitor']),
		'$emailcc' => t('CC: email addresses'),
		'$public' => t('Public post'),
		'$jotnets' => $jotnets,
		'$emtitle' => t('Example: bob@example.com, mary@example.com'),
		'$lockstate' => $x['lockstate'],
		'$bang' => $x['bang'],
		'$profile_uid' => $x['profile_uid'],
		'$preview' => ((feature_enabled($x['profile_uid'],'preview')) ? t('Preview') : ''),
		'$jotplugins' => $jotplugins,
		'$sourceapp' => t($a->sourcename),
		'$cancel' => t('Cancel'),
		'$rand_num' => random_digits(12),

		// ACL permissions box
		'$acl' => $x['acl'],
		'$acl_data' => $x['acl_data'],
		'$group_perms' => t('Post to Groups'),
		'$contact_perms' => t('Post to Contacts'),
		'$private' => t('Private post'),
		'$is_private' => $private_post,
		'$public_link' => $public_post_link,
	));


	if ($popup==true){
		$o = '<div id="jot-popup" style="display: none;">'.$o.'</div>';

	}

	return $o;
}


function get_item_children($arr, $parent) {
	$children = array();
	$a = get_app();
	foreach($arr as $item) {
		if($item['id'] != $item['parent']) {
			if(get_config('system','thread_allow') && $a->theme_thread_allow) {
				// Fallback to parent-uri if thr-parent is not set
				$thr_parent = $item['thr-parent'];
				if($thr_parent == '')
					$thr_parent = $item['parent-uri'];

				if($thr_parent == $parent['uri']) {
					$item['children'] = get_item_children($arr, $item);
					$children[] = $item;
				}
			}
			else if($item['parent'] == $parent['id']) {
				$children[] = $item;
			}
		}
	}
	return $children;
}

function sort_item_children($items) {
	$result = $items;
	usort($result,'sort_thr_created_rev');
	foreach($result as $k => $i) {
		if(count($result[$k]['children'])) {
			$result[$k]['children'] = sort_item_children($result[$k]['children']);
		}
	}
	return $result;
}

function add_children_to_list($children, &$arr) {
	foreach($children as $y) {
		$arr[] = $y;
		if(count($y['children']))
			add_children_to_list($y['children'], $arr);
	}
}

function conv_sort($arr,$order) {

	if((!(is_array($arr) && count($arr))))
		return array();

	$parents = array();
	$children = array();

	foreach($arr as $x)
		if($x['id'] == $x['parent'])
				$parents[] = $x;

	if(stristr($order,'created'))
		usort($parents,'sort_thr_created');
	elseif(stristr($order,'commented'))
		usort($parents,'sort_thr_commented');

	if(count($parents))
		foreach($parents as $i=>$_x)
			$parents[$i]['children'] = get_item_children($arr, $_x);

	/*foreach($arr as $x) {
		if($x['id'] != $x['parent']) {
			$p = find_thread_parent_index($parents,$x);
			if($p !== false)
				$parents[$p]['children'][] = $x;
		}
	}*/
	if(count($parents)) {
		foreach($parents as $k => $v) {
			if(count($parents[$k]['children'])) {
				$parents[$k]['children'] = sort_item_children($parents[$k]['children']);
				/*$y = $parents[$k]['children'];
				usort($y,'sort_thr_created_rev');
				$parents[$k]['children'] = $y;*/
			}
		}
	}

	$ret = array();
	if(count($parents)) {
		foreach($parents as $x) {
			$ret[] = $x;
			if(count($x['children']))
				add_children_to_list($x['children'], $ret);
				/*foreach($x['children'] as $y)
					$ret[] = $y;*/
		}
	}

	return $ret;
}


function sort_thr_created($a,$b) {
	return strcmp($b['created'],$a['created']);
}

function sort_thr_created_rev($a,$b) {
	return strcmp($a['created'],$b['created']);
}

function sort_thr_commented($a,$b) {
	return strcmp($b['commented'],$a['commented']);
}

function find_thread_parent_index($arr,$x) {
	foreach($arr as $k => $v)
		if($v['id'] == $x['parent'])
			return $k;
	return false;
}

function render_location_google($item) {
	$location = (($item['location']) ? '<a target="map" title="' . $item['location'] . '" href="http://maps.google.com/?q=' . urlencode($item['location']) . '">' . $item['location'] . '</a>' : '');
	$coord = (($item['coord']) ? '<a target="map" title="' . $item['coord'] . '" href="http://maps.google.com/?q=' . urlencode($item['coord']) . '">' . $item['coord'] . '</a>' : '');
	if($coord) {
		if($location)
			$location .= '<br /><span class="smalltext">(' . $coord . ')</span>';
		else
			$location = '<span class="smalltext">' . $coord . '</span>';
	}
	return $location;
}
