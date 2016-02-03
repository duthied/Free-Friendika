<?php

require_once('include/bbcode.php');
require_once('include/oembed.php');
require_once('include/salmon.php');
require_once('include/crypto.php');
require_once('include/Photo.php');
require_once('include/tags.php');
require_once('include/files.php');
require_once('include/text.php');
require_once('include/email.php');
require_once('include/threads.php');
require_once('include/socgraph.php');
require_once('include/plaintext.php');
require_once('include/ostatus.php');
require_once('include/feed.php');
require_once('include/Contact.php');
require_once('mod/share.php');
require_once('include/enotify.php');
//require_once('include/import-dfrn.php');

require_once('library/defuse/php-encryption-1.2.1/Crypto.php');

function construct_verb($item) {
	if($item['verb'])
		return $item['verb'];
	return ACTIVITY_POST;
}

/* limit_body_size()
 *
 *		The purpose of this function is to apply system message length limits to
 *		imported messages without including any embedded photos in the length
 */
if(! function_exists('limit_body_size')) {
function limit_body_size($body) {

//	logger('limit_body_size: start', LOGGER_DEBUG);

	$maxlen = get_max_import_size();

	// If the length of the body, including the embedded images, is smaller
	// than the maximum, then don't waste time looking for the images
	if($maxlen && (strlen($body) > $maxlen)) {

		logger('limit_body_size: the total body length exceeds the limit', LOGGER_DEBUG);

		$orig_body = $body;
		$new_body = '';
		$textlen = 0;
		$max_found = false;

		$img_start = strpos($orig_body, '[img');
		$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
		$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
		while(($img_st_close !== false) && ($img_end !== false)) {

			$img_st_close++; // make it point to AFTER the closing bracket
			$img_end += $img_start;
			$img_end += strlen('[/img]');

			if(! strcmp(substr($orig_body, $img_start + $img_st_close, 5), 'data:')) {
				// This is an embedded image

				if( ($textlen + $img_start) > $maxlen ) {
					if($textlen < $maxlen) {
						logger('limit_body_size: the limit happens before an embedded image', LOGGER_DEBUG);
						$new_body = $new_body . substr($orig_body, 0, $maxlen - $textlen);
						$textlen = $maxlen;
					}
				}
				else {
					$new_body = $new_body . substr($orig_body, 0, $img_start);
					$textlen += $img_start;
				}

				$new_body = $new_body . substr($orig_body, $img_start, $img_end - $img_start);
			}
			else {

				if( ($textlen + $img_end) > $maxlen ) {
					if($textlen < $maxlen) {
						logger('limit_body_size: the limit happens before the end of a non-embedded image', LOGGER_DEBUG);
						$new_body = $new_body . substr($orig_body, 0, $maxlen - $textlen);
						$textlen = $maxlen;
					}
				}
				else {
					$new_body = $new_body . substr($orig_body, 0, $img_end);
					$textlen += $img_end;
				}
			}
			$orig_body = substr($orig_body, $img_end);

			if($orig_body === false) // in case the body ends on a closing image tag
				$orig_body = '';

			$img_start = strpos($orig_body, '[img');
			$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
			$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
		}

		if( ($textlen + strlen($orig_body)) > $maxlen) {
			if($textlen < $maxlen) {
				logger('limit_body_size: the limit happens after the end of the last image', LOGGER_DEBUG);
				$new_body = $new_body . substr($orig_body, 0, $maxlen - $textlen);
				$textlen = $maxlen;
			}
		}
		else {
			logger('limit_body_size: the text size with embedded images extracted did not violate the limit', LOGGER_DEBUG);
			$new_body = $new_body . $orig_body;
			$textlen += strlen($orig_body);
		}

		return $new_body;
	}
	else
		return $body;
}}

function title_is_body($title, $body) {

	$title = strip_tags($title);
	$title = trim($title);
	$title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
	$title = str_replace(array("\n", "\r", "\t", " "), array("","","",""), $title);

	$body = strip_tags($body);
	$body = trim($body);
	$body = html_entity_decode($body, ENT_QUOTES, 'UTF-8');
	$body = str_replace(array("\n", "\r", "\t", " "), array("","","",""), $body);

	if (strlen($title) < strlen($body))
		$body = substr($body, 0, strlen($title));

	if (($title != $body) and (substr($title, -3) == "...")) {
		$pos = strrpos($title, "...");
		if ($pos > 0) {
			$title = substr($title, 0, $pos);
			$body = substr($body, 0, $pos);
		}
	}

	return($title == $body);
}

function get_atom_elements($feed, $item, $contact = array()) {

	require_once('library/HTMLPurifier.auto.php');
	require_once('include/html2bbcode.php');

	$best_photo = array();

	$res = array();

	$author = $item->get_author();
	if($author) {
		$res['author-name'] = unxmlify($author->get_name());
		$res['author-link'] = unxmlify($author->get_link());
	}
	else {
		$res['author-name'] = unxmlify($feed->get_title());
		$res['author-link'] = unxmlify($feed->get_permalink());
	}
	$res['uri'] = unxmlify($item->get_id());
	$res['title'] = unxmlify($item->get_title());
	$res['body'] = unxmlify($item->get_content());
	$res['plink'] = unxmlify($item->get_link(0));

	if($res['plink'])
		$base_url = implode('/', array_slice(explode('/',$res['plink']),0,3));
	else
		$base_url = '';

	// look for a photo. We should check media size and find the best one,
	// but for now let's just find any author photo
	// Additionally we look for an alternate author link. On OStatus this one is the one we want.

	$authorlinks = $item->feed->data["child"][SIMPLEPIE_NAMESPACE_ATOM_10]["feed"][0]["child"][SIMPLEPIE_NAMESPACE_ATOM_10]["author"][0]["child"]["http://www.w3.org/2005/Atom"]["link"];
	if (is_array($authorlinks)) {
		foreach ($authorlinks as $link) {
			$linkdata = array_shift($link["attribs"]);

			if ($linkdata["rel"] == "alternate")
				$res["author-link"] = $linkdata["href"];
		};
	}

	$rawauthor = $item->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10,'author');

	if($rawauthor && $rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link']) {
		$base = $rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'];
		foreach($base as $link) {
			if($link['attribs']['']['rel'] === 'alternate')
				$res['author-link'] = unxmlify($link['attribs']['']['href']);

			if(!x($res, 'author-avatar') || !$res['author-avatar']) {
				if($link['attribs']['']['rel'] === 'photo' || $link['attribs']['']['rel'] === 'avatar')
					$res['author-avatar'] = unxmlify($link['attribs']['']['href']);
			}
		}
	}

	$rawactor = $item->get_item_tags(NAMESPACE_ACTIVITY, 'actor');

	if($rawactor && activity_match($rawactor[0]['child'][NAMESPACE_ACTIVITY]['object-type'][0]['data'],ACTIVITY_OBJ_PERSON)) {
		$base = $rawactor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'];
		if($base && count($base)) {
			foreach($base as $link) {
				if($link['attribs']['']['rel'] === 'alternate' && (! $res['author-link']))
					$res['author-link'] = unxmlify($link['attribs']['']['href']);
				if(!x($res, 'author-avatar') || !$res['author-avatar']) {
					if($link['attribs']['']['rel'] === 'avatar' || $link['attribs']['']['rel'] === 'photo')
						$res['author-avatar'] = unxmlify($link['attribs']['']['href']);
				}
			}
		}
	}

	// No photo/profile-link on the item - look at the feed level

	if((! (x($res,'author-link'))) || (! (x($res,'author-avatar')))) {
		$rawauthor = $feed->get_feed_tags(SIMPLEPIE_NAMESPACE_ATOM_10,'author');
		if($rawauthor && $rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link']) {
			$base = $rawauthor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'];
			foreach($base as $link) {
				if($link['attribs']['']['rel'] === 'alternate' && (! $res['author-link']))
					$res['author-link'] = unxmlify($link['attribs']['']['href']);
				if(! $res['author-avatar']) {
					if($link['attribs']['']['rel'] === 'photo' || $link['attribs']['']['rel'] === 'avatar')
						$res['author-avatar'] = unxmlify($link['attribs']['']['href']);
				}
			}
		}

		$rawactor = $feed->get_feed_tags(NAMESPACE_ACTIVITY, 'subject');

		if($rawactor && activity_match($rawactor[0]['child'][NAMESPACE_ACTIVITY]['object-type'][0]['data'],ACTIVITY_OBJ_PERSON)) {
			$base = $rawactor[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'];

			if($base && count($base)) {
				foreach($base as $link) {
					if($link['attribs']['']['rel'] === 'alternate' && (! $res['author-link']))
						$res['author-link'] = unxmlify($link['attribs']['']['href']);
					if(! (x($res,'author-avatar'))) {
						if($link['attribs']['']['rel'] === 'avatar' || $link['attribs']['']['rel'] === 'photo')
							$res['author-avatar'] = unxmlify($link['attribs']['']['href']);
					}
				}
			}
		}
	}

	$apps = $item->get_item_tags(NAMESPACE_STATUSNET,'notice_info');
	if($apps && $apps[0]['attribs']['']['source']) {
		$res['app'] = strip_tags(unxmlify($apps[0]['attribs']['']['source']));
		if($res['app'] === 'web')
			$res['app'] = 'OStatus';
	}

	// base64 encoded json structure representing Diaspora signature

	$dsig = $item->get_item_tags(NAMESPACE_DFRN,'diaspora_signature');
	if($dsig) {
		$res['dsprsig'] = unxmlify($dsig[0]['data']);
	}

	$dguid = $item->get_item_tags(NAMESPACE_DFRN,'diaspora_guid');
	if($dguid)
		$res['guid'] = unxmlify($dguid[0]['data']);

	$bm = $item->get_item_tags(NAMESPACE_DFRN,'bookmark');
	if($bm)
		$res['bookmark'] = ((unxmlify($bm[0]['data']) === 'true') ? 1 : 0);


	/**
	 * If there's a copy of the body content which is guaranteed to have survived mangling in transit, use it.
	 */

	$have_real_body = false;

	$rawenv = $item->get_item_tags(NAMESPACE_DFRN, 'env');
	if($rawenv) {
		$have_real_body = true;
		$res['body'] = $rawenv[0]['data'];
		$res['body'] = str_replace(array(' ',"\t","\r","\n"), array('','','',''),$res['body']);
		// make sure nobody is trying to sneak some html tags by us
		$res['body'] = notags(base64url_decode($res['body']));
	}


	$res['body'] = limit_body_size($res['body']);

	// It isn't certain at this point whether our content is plaintext or html and we'd be foolish to trust
	// the content type. Our own network only emits text normally, though it might have been converted to
	// html if we used a pubsubhubbub transport. But if we see even one html tag in our text, we will
	// have to assume it is all html and needs to be purified.

	// It doesn't matter all that much security wise - because before this content is used anywhere, we are
	// going to escape any tags we find regardless, but this lets us import a limited subset of html from
	// the wild, by sanitising it and converting supported tags to bbcode before we rip out any remaining
	// html.

	if((strpos($res['body'],'<') !== false) && (strpos($res['body'],'>') !== false)) {

		$res['body'] = reltoabs($res['body'],$base_url);

		$res['body'] = html2bb_video($res['body']);

		$res['body'] = oembed_html2bbcode($res['body']);

		$config = HTMLPurifier_Config::createDefault();
		$config->set('Cache.DefinitionImpl', null);

		// we shouldn't need a whitelist, because the bbcode converter
		// will strip out any unsupported tags.

		$purifier = new HTMLPurifier($config);
		$res['body'] = $purifier->purify($res['body']);

		$res['body'] = @html2bbcode($res['body']);


	}
	elseif(! $have_real_body) {

		// it's not one of our messages and it has no tags
		// so it's probably just text. We'll escape it just to be safe.

		$res['body'] = escape_tags($res['body']);
	}


	// this tag is obsolete but we keep it for really old sites

	$allow = $item->get_item_tags(NAMESPACE_DFRN,'comment-allow');
	if($allow && $allow[0]['data'] == 1)
		$res['last-child'] = 1;
	else
		$res['last-child'] = 0;

	$private = $item->get_item_tags(NAMESPACE_DFRN,'private');
	if($private && intval($private[0]['data']) > 0)
		$res['private'] = intval($private[0]['data']);
	else
		$res['private'] = 0;

	$extid = $item->get_item_tags(NAMESPACE_DFRN,'extid');
	if($extid && $extid[0]['data'])
		$res['extid'] = $extid[0]['data'];

	$rawlocation = $item->get_item_tags(NAMESPACE_DFRN, 'location');
	if($rawlocation)
		$res['location'] = unxmlify($rawlocation[0]['data']);


	$rawcreated = $item->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10,'published');
	if($rawcreated)
		$res['created'] = unxmlify($rawcreated[0]['data']);


	$rawedited = $item->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10,'updated');
	if($rawedited)
		$res['edited'] = unxmlify($rawedited[0]['data']);

	if((x($res,'edited')) && (! (x($res,'created'))))
		$res['created'] = $res['edited'];

	if(! $res['created'])
		$res['created'] = $item->get_date('c');

	if(! $res['edited'])
		$res['edited'] = $item->get_date('c');


	// Disallow time travelling posts

	$d1 = strtotime($res['created']);
	$d2 = strtotime($res['edited']);
	$d3 = strtotime('now');

	if($d1 > $d3)
		$res['created'] = datetime_convert();
	if($d2 > $d3)
		$res['edited'] = datetime_convert();

	$rawowner = $item->get_item_tags(NAMESPACE_DFRN, 'owner');
	if($rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['name'][0]['data'])
		$res['owner-name'] = unxmlify($rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['name'][0]['data']);
	elseif($rawowner[0]['child'][NAMESPACE_DFRN]['name'][0]['data'])
		$res['owner-name'] = unxmlify($rawowner[0]['child'][NAMESPACE_DFRN]['name'][0]['data']);
	if($rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['uri'][0]['data'])
		$res['owner-link'] = unxmlify($rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['uri'][0]['data']);
	elseif($rawowner[0]['child'][NAMESPACE_DFRN]['uri'][0]['data'])
		$res['owner-link'] = unxmlify($rawowner[0]['child'][NAMESPACE_DFRN]['uri'][0]['data']);

	if($rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link']) {
		$base = $rawowner[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['link'];

		foreach($base as $link) {
			if(!x($res, 'owner-avatar') || !$res['owner-avatar']) {
				if($link['attribs']['']['rel'] === 'photo' || $link['attribs']['']['rel'] === 'avatar')
					$res['owner-avatar'] = unxmlify($link['attribs']['']['href']);
			}
		}
	}

	$rawgeo = $item->get_item_tags(NAMESPACE_GEORSS,'point');
	if($rawgeo)
		$res['coord'] = unxmlify($rawgeo[0]['data']);

	if ($contact["network"] == NETWORK_FEED) {
		$res['verb'] = ACTIVITY_POST;
		$res['object-type'] = ACTIVITY_OBJ_NOTE;
	}

	$rawverb = $item->get_item_tags(NAMESPACE_ACTIVITY, 'verb');

	// select between supported verbs

	if($rawverb) {
		$res['verb'] = unxmlify($rawverb[0]['data']);
	}

	// translate OStatus unfollow to activity streams if it happened to get selected

	if((x($res,'verb')) && ($res['verb'] === 'http://ostatus.org/schema/1.0/unfollow'))
		$res['verb'] = ACTIVITY_UNFOLLOW;

	$cats = $item->get_categories();
	if($cats) {
		$tag_arr = array();
		foreach($cats as $cat) {
			$term = $cat->get_term();
			if(! $term)
				$term = $cat->get_label();
			$scheme = $cat->get_scheme();
			if($scheme && $term && stristr($scheme,'X-DFRN:'))
				$tag_arr[] = substr($scheme,7,1) . '[url=' . unxmlify(substr($scheme,9)) . ']' . unxmlify($term) . '[/url]';
			elseif($term)
				$tag_arr[] = notags(trim($term));
		}
		$res['tag'] =  implode(',', $tag_arr);
	}

	$attach = $item->get_enclosures();
	if($attach) {
		$att_arr = array();
		foreach($attach as $att) {
			$len   = intval($att->get_length());
			$link  = str_replace(array(',','"'),array('%2D','%22'),notags(trim(unxmlify($att->get_link()))));
			$title = str_replace(array(',','"'),array('%2D','%22'),notags(trim(unxmlify($att->get_title()))));
			$type  = str_replace(array(',','"'),array('%2D','%22'),notags(trim(unxmlify($att->get_type()))));
			if(strpos($type,';'))
				$type = substr($type,0,strpos($type,';'));
			if((! $link) || (strpos($link,'http') !== 0))
				continue;

			if(! $title)
				$title = ' ';
			if(! $type)
				$type = 'application/octet-stream';

			$att_arr[] = '[attach]href="' . $link . '" length="' . $len . '" type="' . $type . '" title="' . $title . '"[/attach]';
		}
		$res['attach'] = implode(',', $att_arr);
	}

	$rawobj = $item->get_item_tags(NAMESPACE_ACTIVITY, 'object');

	if($rawobj) {
		$res['object'] = '<object>' . "\n";
		$child = $rawobj[0]['child'];
		if($child[NAMESPACE_ACTIVITY]['object-type'][0]['data']) {
			$res['object-type'] = $child[NAMESPACE_ACTIVITY]['object-type'][0]['data'];
			$res['object'] .= '<type>' . $child[NAMESPACE_ACTIVITY]['object-type'][0]['data'] . '</type>' . "\n";
		}
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'id') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['id'][0]['data'])
			$res['object'] .= '<id>' . $child[SIMPLEPIE_NAMESPACE_ATOM_10]['id'][0]['data'] . '</id>' . "\n";
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'link') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['link'])
			$res['object'] .= '<link>' . encode_rel_links($child[SIMPLEPIE_NAMESPACE_ATOM_10]['link']) . '</link>' . "\n";
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'title') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['title'][0]['data'])
			$res['object'] .= '<title>' . $child[SIMPLEPIE_NAMESPACE_ATOM_10]['title'][0]['data'] . '</title>' . "\n";
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'content') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['content'][0]['data']) {
			$body = $child[SIMPLEPIE_NAMESPACE_ATOM_10]['content'][0]['data'];
			if(! $body)
				$body = $child[SIMPLEPIE_NAMESPACE_ATOM_10]['summary'][0]['data'];
			// preserve a copy of the original body content in case we later need to parse out any microformat information, e.g. events
			$res['object'] .= '<orig>' . xmlify($body) . '</orig>' . "\n";
			if((strpos($body,'<') !== false) || (strpos($body,'>') !== false)) {

				$body = html2bb_video($body);

				$config = HTMLPurifier_Config::createDefault();
				$config->set('Cache.DefinitionImpl', null);

				$purifier = new HTMLPurifier($config);
				$body = $purifier->purify($body);
				$body = html2bbcode($body);
			}

			$res['object'] .= '<content>' . $body . '</content>' . "\n";
		}

		$res['object'] .= '</object>' . "\n";
	}

	$rawobj = $item->get_item_tags(NAMESPACE_ACTIVITY, 'target');

	if($rawobj) {
		$res['target'] = '<target>' . "\n";
		$child = $rawobj[0]['child'];
		if($child[NAMESPACE_ACTIVITY]['object-type'][0]['data']) {
			$res['target'] .= '<type>' . $child[NAMESPACE_ACTIVITY]['object-type'][0]['data'] . '</type>' . "\n";
		}
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'id') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['id'][0]['data'])
			$res['target'] .= '<id>' . $child[SIMPLEPIE_NAMESPACE_ATOM_10]['id'][0]['data'] . '</id>' . "\n";
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'link') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['link'])
			$res['target'] .= '<link>' . encode_rel_links($child[SIMPLEPIE_NAMESPACE_ATOM_10]['link']) . '</link>' . "\n";
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'data') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['title'][0]['data'])
			$res['target'] .= '<title>' . $child[SIMPLEPIE_NAMESPACE_ATOM_10]['title'][0]['data'] . '</title>' . "\n";
		if(x($child[SIMPLEPIE_NAMESPACE_ATOM_10], 'data') && $child[SIMPLEPIE_NAMESPACE_ATOM_10]['content'][0]['data']) {
			$body = $child[SIMPLEPIE_NAMESPACE_ATOM_10]['content'][0]['data'];
			if(! $body)
				$body = $child[SIMPLEPIE_NAMESPACE_ATOM_10]['summary'][0]['data'];
			// preserve a copy of the original body content in case we later need to parse out any microformat information, e.g. events
			$res['target'] .= '<orig>' . xmlify($body) . '</orig>' . "\n";
			if((strpos($body,'<') !== false) || (strpos($body,'>') !== false)) {

				$body = html2bb_video($body);

				$config = HTMLPurifier_Config::createDefault();
				$config->set('Cache.DefinitionImpl', null);

				$purifier = new HTMLPurifier($config);
				$body = $purifier->purify($body);
				$body = html2bbcode($body);
			}

			$res['target'] .= '<content>' . $body . '</content>' . "\n";
		}

		$res['target'] .= '</target>' . "\n";
	}

	$arr = array('feed' => $feed, 'item' => $item, 'result' => $res);

	call_hooks('parse_atom', $arr);

	return $res;
}

function add_page_info_data($data) {
	call_hooks('page_info_data', $data);

	// It maybe is a rich content, but if it does have everything that a link has,
	// then treat it that way
	if (($data["type"] == "rich") AND is_string($data["title"]) AND
		is_string($data["text"]) AND (sizeof($data["images"]) > 0))
		$data["type"] = "link";

	if ((($data["type"] != "link") AND ($data["type"] != "video") AND ($data["type"] != "photo")) OR ($data["title"] == $url))
		return("");

	if ($no_photos AND ($data["type"] == "photo"))
		return("");

	// If the link contains BBCode stuff, make a short link out of this to avoid parsing problems
	if (strpos($data["url"], '[') OR strpos($data["url"], ']')) {
		require_once("include/network.php");
		$data["url"] = short_link($data["url"]);
	}

	if (($data["type"] != "photo") AND is_string($data["title"]))
		$text .= "[bookmark=".$data["url"]."]".trim($data["title"])."[/bookmark]";

	if (($data["type"] != "video") AND ($photo != ""))
		$text .= '[img]'.$photo.'[/img]';
	elseif (($data["type"] != "video") AND (sizeof($data["images"]) > 0)) {
		$imagedata = $data["images"][0];
		$text .= '[img]'.$imagedata["src"].'[/img]';
	}

	if (($data["type"] != "photo") AND is_string($data["text"]))
		$text .= "[quote]".$data["text"]."[/quote]";

	$hashtags = "";
	if (isset($data["keywords"]) AND count($data["keywords"])) {
		$a = get_app();
		$hashtags = "\n";
		foreach ($data["keywords"] AS $keyword) {
			/// @todo make a positive list of allowed characters
			$hashtag = str_replace(array(" ", "+", "/", ".", "#", "'", "’", "`", "(", ")", "„", "“"),
						array("","", "", "", "", "", "", "", "", "", "", ""), $keyword);
			$hashtags .= "#[url=".$a->get_baseurl()."/search?tag=".rawurlencode($hashtag)."]".$hashtag."[/url] ";
		}
	}

	return("\n[class=type-".$data["type"]."]".$text."[/class]".$hashtags);
}

function query_page_info($url, $no_photos = false, $photo = "", $keywords = false, $keyword_blacklist = "") {
	require_once("mod/parse_url.php");

	$data = parseurl_getsiteinfo_cached($url, true);

	if ($photo != "")
		$data["images"][0]["src"] = $photo;

	logger('fetch page info for '.$url.' '.print_r($data, true), LOGGER_DEBUG);

	if (!$keywords AND isset($data["keywords"]))
		unset($data["keywords"]);

	if (($keyword_blacklist != "") AND isset($data["keywords"])) {
		$list = explode(",", $keyword_blacklist);
		foreach ($list AS $keyword) {
			$keyword = trim($keyword);
			$index = array_search($keyword, $data["keywords"]);
			if ($index !== false)
				unset($data["keywords"][$index]);
		}
	}

	return($data);
}

function add_page_keywords($url, $no_photos = false, $photo = "", $keywords = false, $keyword_blacklist = "") {
	$data = query_page_info($url, $no_photos, $photo, $keywords, $keyword_blacklist);

	$tags = "";
	if (isset($data["keywords"]) AND count($data["keywords"])) {
		$a = get_app();
		foreach ($data["keywords"] AS $keyword) {
			$hashtag = str_replace(array(" ", "+", "/", ".", "#", "'"),
						array("","", "", "", "", ""), $keyword);

			if ($tags != "")
				$tags .= ",";

			$tags .= "#[url=".$a->get_baseurl()."/search?tag=".rawurlencode($hashtag)."]".$hashtag."[/url]";
		}
	}

	return($tags);
}

function add_page_info($url, $no_photos = false, $photo = "", $keywords = false, $keyword_blacklist = "") {
	$data = query_page_info($url, $no_photos, $photo, $keywords, $keyword_blacklist);

	$text = add_page_info_data($data);

	return($text);
}

function add_page_info_to_body($body, $texturl = false, $no_photos = false) {

	logger('add_page_info_to_body: fetch page info for body '.$body, LOGGER_DEBUG);

	$URLSearchString = "^\[\]";

	// Adding these spaces is a quick hack due to my problems with regular expressions :)
	preg_match("/[^!#@]\[url\]([$URLSearchString]*)\[\/url\]/ism", " ".$body, $matches);

	if (!$matches)
		preg_match("/[^!#@]\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", " ".$body, $matches);

	// Convert urls without bbcode elements
	if (!$matches AND $texturl) {
		preg_match("/([^\]\='".'"'."]|^)(https?\:\/\/[a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\%\$\!\+\,]+)/ism", " ".$body, $matches);

		// Yeah, a hack. I really hate regular expressions :)
		if ($matches)
			$matches[1] = $matches[2];
	}

	if ($matches)
		$footer = add_page_info($matches[1], $no_photos);

	// Remove the link from the body if the link is attached at the end of the post
	if (isset($footer) AND (trim($footer) != "") AND (strpos($footer, $matches[1]))) {
		$removedlink = trim(str_replace($matches[1], "", $body));
		if (($removedlink == "") OR strstr($body, $removedlink))
			$body = $removedlink;

		$url = str_replace(array('/', '.'), array('\/', '\.'), $matches[1]);
		$removedlink = preg_replace("/\[url\=".$url."\](.*?)\[\/url\]/ism", '', $body);
		if (($removedlink == "") OR strstr($body, $removedlink))
			$body = $removedlink;
	}

	// Add the page information to the bottom
	if (isset($footer) AND (trim($footer) != ""))
		$body .= $footer;

	return $body;
}

function encode_rel_links($links) {
	$o = '';
	if(! ((is_array($links)) && (count($links))))
		return $o;
	foreach($links as $link) {
		$o .= '<link ';
		if($link['attribs']['']['rel'])
			$o .= 'rel="' . $link['attribs']['']['rel'] . '" ';
		if($link['attribs']['']['type'])
			$o .= 'type="' . $link['attribs']['']['type'] . '" ';
		if($link['attribs']['']['href'])
			$o .= 'href="' . $link['attribs']['']['href'] . '" ';
		if( (x($link['attribs'],NAMESPACE_MEDIA)) && $link['attribs'][NAMESPACE_MEDIA]['width'])
			$o .= 'media:width="' . $link['attribs'][NAMESPACE_MEDIA]['width'] . '" ';
		if( (x($link['attribs'],NAMESPACE_MEDIA)) && $link['attribs'][NAMESPACE_MEDIA]['height'])
			$o .= 'media:height="' . $link['attribs'][NAMESPACE_MEDIA]['height'] . '" ';
		$o .= ' />' . "\n" ;
	}
	return xmlify($o);
}

function add_guid($item) {
	$r = q("SELECT `guid` FROM `guid` WHERE `guid` = '%s' LIMIT 1", dbesc($item["guid"]));
	if ($r)
		return;

	q("INSERT INTO `guid` (`guid`,`plink`,`uri`,`network`) VALUES ('%s','%s','%s','%s')",
		dbesc($item["guid"]), dbesc($item["plink"]),
		dbesc($item["uri"]), dbesc($item["network"]));
}

/**
 * Adds a "lang" specification in a "postopts" element of given $arr,
 * if possible and not already present.
 * Expects "body" element to exist in $arr.
 * 
 * @todo Add a parameter to request forcing override
 */
function item_add_language_opt(&$arr) {

	if (version_compare(PHP_VERSION, '5.3.0', '<')) return; // LanguageDetect.php not available ?

	if ( x($arr, 'postopts') )
	{
		if ( strstr($arr['postopts'], 'lang=') )
		{
			// do not override
			/// @TODO Add parameter to request overriding
			return;
		}
		$postopts = $arr['postopts'];
	}
	else
	{
		$postopts = "";
	}

	require_once('library/langdet/Text/LanguageDetect.php');
	$naked_body = preg_replace('/\[(.+?)\]/','',$arr['body']);
	$l = new Text_LanguageDetect;
	//$lng = $l->detectConfidence($naked_body);
	//$arr['postopts'] = (($lng['language']) ? 'lang=' . $lng['language'] . ';' . $lng['confidence'] : '');
	$lng = $l->detect($naked_body, 3);

	if (sizeof($lng) > 0) {
		if ($postopts != "") $postopts .= '&'; // arbitrary separator, to be reviewed
		$postopts .= 'lang=';
		$sep = "";
		foreach ($lng as $language => $score) {
			$postopts .= $sep . $language.";".$score;
			$sep = ':';
		}
		$arr['postopts'] = $postopts;
	}
}

/**
 * @brief Creates an unique guid out of a given uri
 *
 * @param string $uri uri of an item entry
 * @return string unique guid
 */
function uri_to_guid($uri) {

	// Our regular guid routine is using this kind of prefix as well
	// We have to avoid that different routines could accidentally create the same value
	$parsed = parse_url($uri);
	$guid_prefix = hash("crc32", $parsed["host"]);

	// Remove the scheme to make sure that "https" and "http" doesn't make a difference
	unset($parsed["scheme"]);

	$host_id = implode("/", $parsed);

	// We could use any hash algorithm since it isn't a security issue
	$host_hash = hash("ripemd128", $host_id);

	return $guid_prefix.$host_hash;
}

function item_store($arr,$force_parent = false, $notify = false, $dontcache = false) {

	// If it is a posting where users should get notifications, then define it as wall posting
	if ($notify) {
		$arr['wall'] = 1;
		$arr['type'] = 'wall';
		$arr['origin'] = 1;
		$arr['last-child'] = 1;
		$arr['network'] = NETWORK_DFRN;
	}

	// If a Diaspora signature structure was passed in, pull it out of the
	// item array and set it aside for later storage.

	$dsprsig = null;
	if(x($arr,'dsprsig')) {
		$dsprsig = json_decode(base64_decode($arr['dsprsig']));
		unset($arr['dsprsig']);
	}

	// Converting the plink
	if ($arr['network'] == NETWORK_OSTATUS) {
		if (isset($arr['plink']))
			$arr['plink'] = ostatus_convert_href($arr['plink']);
		elseif (isset($arr['uri']))
			$arr['plink'] = ostatus_convert_href($arr['uri']);
	}

	if(x($arr, 'gravity'))
		$arr['gravity'] = intval($arr['gravity']);
	elseif($arr['parent-uri'] === $arr['uri'])
		$arr['gravity'] = 0;
	elseif(activity_match($arr['verb'],ACTIVITY_POST))
		$arr['gravity'] = 6;
	else
		$arr['gravity'] = 6;   // extensible catchall

	if(! x($arr,'type'))
		$arr['type']      = 'remote';



	/* check for create  date and expire time */
	$uid = intval($arr['uid']);
	$r = q("SELECT expire FROM user WHERE uid = %d", intval($uid));
	if(count($r)) {
		$expire_interval = $r[0]['expire'];
		if ($expire_interval>0) {
			$expire_date =  new DateTime( '- '.$expire_interval.' days', new DateTimeZone('UTC'));
			$created_date = new DateTime($arr['created'], new DateTimeZone('UTC'));
			if ($created_date < $expire_date) {
				logger('item-store: item created ('.$arr['created'].') before expiration time ('.$expire_date->format(DateTime::W3C).'). ignored. ' . print_r($arr,true), LOGGER_DEBUG);
				return 0;
			}
		}
	}

	// Do we already have this item?
	// We have to check several networks since Friendica posts could be repeated via OStatus (maybe Diasporsa as well)
	if (in_array(trim($arr['network']), array(NETWORK_DIASPORA, NETWORK_DFRN, NETWORK_OSTATUS, ""))) {
		$r = q("SELECT `id`, `network` FROM `item` WHERE `uri` = '%s' AND `uid` = %d AND `network` IN ('%s', '%s', '%s')  LIMIT 1",
				dbesc(trim($arr['uri'])),
				intval($uid),
				dbesc(NETWORK_DIASPORA),
				dbesc(NETWORK_DFRN),
				dbesc(NETWORK_OSTATUS)
			);
		if ($r) {
			// We only log the entries with a different user id than 0. Otherwise we would have too many false positives
			if ($uid != 0)
				logger("Item with uri ".$arr['uri']." already existed for user ".$uid." with id ".$r[0]["id"]." target network ".$r[0]["network"]." - new network: ".$arr['network']);
			return($r[0]["id"]);
		}
	}

	// Shouldn't happen but we want to make absolutely sure it doesn't leak from a plugin.
	// Deactivated, since the bbcode parser can handle with it - and it destroys posts with some smileys that contain "<"
	//if((strpos($arr['body'],'<') !== false) || (strpos($arr['body'],'>') !== false))
	//	$arr['body'] = strip_tags($arr['body']);

	item_add_language_opt($arr);

	if ($notify)
		$guid_prefix = "";
	elseif ((trim($arr['guid']) == "") AND (trim($arr['plink']) != ""))
		$arr['guid'] = uri_to_guid($arr['plink']);
	elseif ((trim($arr['guid']) == "") AND (trim($arr['uri']) != ""))
		$arr['guid'] = uri_to_guid($arr['uri']);
	else {
		$parsed = parse_url($arr["author-link"]);
		$guid_prefix = hash("crc32", $parsed["host"]);
	}

	$arr['wall']          = ((x($arr,'wall'))          ? intval($arr['wall'])                : 0);
	$arr['guid']          = ((x($arr,'guid'))          ? notags(trim($arr['guid']))          : get_guid(32, $guid_prefix));
	$arr['uri']           = ((x($arr,'uri'))           ? notags(trim($arr['uri']))           : $arr['guid']);
	$arr['extid']         = ((x($arr,'extid'))         ? notags(trim($arr['extid']))         : '');
	$arr['author-name']   = ((x($arr,'author-name'))   ? trim($arr['author-name'])   : '');
	$arr['author-link']   = ((x($arr,'author-link'))   ? notags(trim($arr['author-link']))   : '');
	$arr['author-avatar'] = ((x($arr,'author-avatar')) ? notags(trim($arr['author-avatar'])) : '');
	$arr['owner-name']    = ((x($arr,'owner-name'))    ? trim($arr['owner-name'])    : '');
	$arr['owner-link']    = ((x($arr,'owner-link'))    ? notags(trim($arr['owner-link']))    : '');
	$arr['owner-avatar']  = ((x($arr,'owner-avatar'))  ? notags(trim($arr['owner-avatar']))  : '');
	$arr['created']       = ((x($arr,'created') !== false) ? datetime_convert('UTC','UTC',$arr['created']) : datetime_convert());
	$arr['edited']        = ((x($arr,'edited')  !== false) ? datetime_convert('UTC','UTC',$arr['edited'])  : datetime_convert());
	$arr['commented']     = ((x($arr,'commented')  !== false) ? datetime_convert('UTC','UTC',$arr['commented'])  : datetime_convert());
	$arr['received']      = ((x($arr,'received')  !== false) ? datetime_convert('UTC','UTC',$arr['received'])  : datetime_convert());
	$arr['changed']       = ((x($arr,'changed')  !== false) ? datetime_convert('UTC','UTC',$arr['changed'])  : datetime_convert());
	$arr['title']         = ((x($arr,'title'))         ? trim($arr['title'])         : '');
	$arr['location']      = ((x($arr,'location'))      ? trim($arr['location'])      : '');
	$arr['coord']         = ((x($arr,'coord'))         ? notags(trim($arr['coord']))         : '');
	$arr['last-child']    = ((x($arr,'last-child'))    ? intval($arr['last-child'])          : 0 );
	$arr['visible']       = ((x($arr,'visible') !== false) ? intval($arr['visible'])         : 1 );
	$arr['deleted']       = 0;
	$arr['parent-uri']    = ((x($arr,'parent-uri'))    ? notags(trim($arr['parent-uri']))    : '');
	$arr['verb']          = ((x($arr,'verb'))          ? notags(trim($arr['verb']))          : '');
	$arr['object-type']   = ((x($arr,'object-type'))   ? notags(trim($arr['object-type']))   : '');
	$arr['object']        = ((x($arr,'object'))        ? trim($arr['object'])                : '');
	$arr['target-type']   = ((x($arr,'target-type'))   ? notags(trim($arr['target-type']))   : '');
	$arr['target']        = ((x($arr,'target'))        ? trim($arr['target'])                : '');
	$arr['plink']         = ((x($arr,'plink'))         ? notags(trim($arr['plink']))         : '');
	$arr['allow_cid']     = ((x($arr,'allow_cid'))     ? trim($arr['allow_cid'])             : '');
	$arr['allow_gid']     = ((x($arr,'allow_gid'))     ? trim($arr['allow_gid'])             : '');
	$arr['deny_cid']      = ((x($arr,'deny_cid'))      ? trim($arr['deny_cid'])              : '');
	$arr['deny_gid']      = ((x($arr,'deny_gid'))      ? trim($arr['deny_gid'])              : '');
	$arr['private']       = ((x($arr,'private'))       ? intval($arr['private'])             : 0 );
	$arr['bookmark']      = ((x($arr,'bookmark'))      ? intval($arr['bookmark'])            : 0 );
	$arr['body']          = ((x($arr,'body'))          ? trim($arr['body'])                  : '');
	$arr['tag']           = ((x($arr,'tag'))           ? notags(trim($arr['tag']))           : '');
	$arr['attach']        = ((x($arr,'attach'))        ? notags(trim($arr['attach']))        : '');
	$arr['app']           = ((x($arr,'app'))           ? notags(trim($arr['app']))           : '');
	$arr['origin']        = ((x($arr,'origin'))        ? intval($arr['origin'])              : 0 );
	$arr['network']       = ((x($arr,'network'))       ? trim($arr['network'])               : '');
	$arr['postopts']      = ((x($arr,'postopts'))      ? trim($arr['postopts'])              : '');
	$arr['resource-id']   = ((x($arr,'resource-id'))   ? trim($arr['resource-id'])           : '');
	$arr['event-id']      = ((x($arr,'event-id'))      ? intval($arr['event-id'])            : 0 );
	$arr['inform']        = ((x($arr,'inform'))        ? trim($arr['inform'])                : '');
	$arr['file']          = ((x($arr,'file'))          ? trim($arr['file'])                  : '');

	if ($arr['plink'] == "") {
		$a = get_app();
		$arr['plink'] = $a->get_baseurl().'/display/'.urlencode($arr['guid']);
	}

	if ($arr['network'] == "") {
		$r = q("SELECT `network` FROM `contact` WHERE `network` IN ('%s', '%s', '%s') AND `nurl` = '%s' AND `uid` = %d LIMIT 1",
			dbesc(NETWORK_DFRN), dbesc(NETWORK_DIASPORA), dbesc(NETWORK_OSTATUS),
			dbesc(normalise_link($arr['author-link'])),
			intval($arr['uid'])
		);

		if(!count($r))
			$r = q("SELECT `network` FROM `gcontact` WHERE `network` IN ('%s', '%s', '%s') AND `nurl` = '%s' LIMIT 1",
				dbesc(NETWORK_DFRN), dbesc(NETWORK_DIASPORA), dbesc(NETWORK_OSTATUS),
				dbesc(normalise_link($arr['author-link']))
			);

		if(!count($r))
			$r = q("SELECT `network` FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($arr['contact-id']),
				intval($arr['uid'])
			);

		if(count($r))
			$arr['network'] = $r[0]["network"];

		// Fallback to friendica (why is it empty in some cases?)
		if ($arr['network'] == "")
			$arr['network'] = NETWORK_DFRN;

		logger("item_store: Set network to ".$arr["network"]." for ".$arr["uri"], LOGGER_DEBUG);
	}

	// The contact-id should be set before "item_store" was called - but there seems to be some issues
	if ($arr["contact-id"] == 0) {
		// First we are looking for a suitable contact that matches with the author of the post
		// This is done only for comments (See below explanation at "gcontact-id")
		if($arr['parent-uri'] != $arr['uri'])
			$arr["contact-id"] = get_contact($arr['author-link'], $uid);

		// If not present then maybe the owner was found
		if ($arr["contact-id"] == 0)
			$arr["contact-id"] = get_contact($arr['owner-link'], $uid);

		// Still missing? Then use the "self" contact of the current user
		if ($arr["contact-id"] == 0) {
			$r = q("SELECT `id` FROM `contact` WHERE `self` AND `uid` = %d", intval($uid));
			if ($r)
				$arr["contact-id"] = $r[0]["id"];
		}
		logger("Contact-id was missing for post ".$arr["guid"]." from user id ".$uid." - now set to ".$arr["contact-id"], LOGGER_DEBUG);
	}

	if ($arr["gcontact-id"] == 0) {
		// The gcontact should mostly behave like the contact. But is is supposed to be global for the system.
		// This means that wall posts, repeated posts, etc. should have the gcontact id of the owner.
		// On comments the author is the better choice.
		if($arr['parent-uri'] === $arr['uri'])
			$arr["gcontact-id"] = get_gcontact_id(array("url" => $arr['owner-link'], "network" => $arr['network'],
								 "photo" => $arr['owner-avatar'], "name" => $arr['owner-name']));
		else
			$arr["gcontact-id"] = get_gcontact_id(array("url" => $arr['author-link'], "network" => $arr['network'],
								 "photo" => $arr['author-avatar'], "name" => $arr['author-name']));
	}

	if ($arr['guid'] != "") {
		// Checking if there is already an item with the same guid
		logger('checking for an item for user '.$arr['uid'].' on network '.$arr['network'].' with the guid '.$arr['guid'], LOGGER_DEBUG);
		$r = q("SELECT `guid` FROM `item` WHERE `guid` = '%s' AND `network` = '%s' AND `uid` = '%d' LIMIT 1",
			dbesc($arr['guid']), dbesc($arr['network']), intval($arr['uid']));

		if(count($r)) {
			logger('found item with guid '.$arr['guid'].' for user '.$arr['uid'].' on network '.$arr['network'], LOGGER_DEBUG);
			return 0;
		}
	}

	// Check for hashtags in the body and repair or add hashtag links
	item_body_set_hashtags($arr);

	$arr['thr-parent'] = $arr['parent-uri'];
	if($arr['parent-uri'] === $arr['uri']) {
		$parent_id = 0;
		$parent_deleted = 0;
		$allow_cid = $arr['allow_cid'];
		$allow_gid = $arr['allow_gid'];
		$deny_cid  = $arr['deny_cid'];
		$deny_gid  = $arr['deny_gid'];
		$notify_type = 'wall-new';
	}
	else {

		// find the parent and snarf the item id and ACLs
		// and anything else we need to inherit

		$r = q("SELECT * FROM `item` WHERE `uri` = '%s' AND `uid` = %d ORDER BY `id` ASC LIMIT 1",
			dbesc($arr['parent-uri']),
			intval($arr['uid'])
		);

		if(count($r)) {

			// is the new message multi-level threaded?
			// even though we don't support it now, preserve the info
			// and re-attach to the conversation parent.

			if($r[0]['uri'] != $r[0]['parent-uri']) {
				$arr['parent-uri'] = $r[0]['parent-uri'];
				$z = q("SELECT * FROM `item` WHERE `uri` = '%s' AND `parent-uri` = '%s' AND `uid` = %d
					ORDER BY `id` ASC LIMIT 1",
					dbesc($r[0]['parent-uri']),
					dbesc($r[0]['parent-uri']),
					intval($arr['uid'])
				);
				if($z && count($z))
					$r = $z;
			}

			$parent_id      = $r[0]['id'];
			$parent_deleted = $r[0]['deleted'];
			$allow_cid      = $r[0]['allow_cid'];
			$allow_gid      = $r[0]['allow_gid'];
			$deny_cid       = $r[0]['deny_cid'];
			$deny_gid       = $r[0]['deny_gid'];
			$arr['wall']    = $r[0]['wall'];
			$notify_type    = 'comment-new';

			// if the parent is private, force privacy for the entire conversation
			// This differs from the above settings as it subtly allows comments from
			// email correspondents to be private even if the overall thread is not.

			if($r[0]['private'])
				$arr['private'] = $r[0]['private'];

			// Edge case. We host a public forum that was originally posted to privately.
			// The original author commented, but as this is a comment, the permissions
			// weren't fixed up so it will still show the comment as private unless we fix it here.

			if((intval($r[0]['forum_mode']) == 1) && (! $r[0]['private']))
				$arr['private'] = 0;


			// If its a post from myself then tag the thread as "mention"
			logger("item_store: Checking if parent ".$parent_id." has to be tagged as mention for user ".$arr['uid'], LOGGER_DEBUG);
			$u = q("select * from user where uid = %d limit 1", intval($arr['uid']));
			if(count($u)) {
				$a = get_app();
				$self = normalise_link($a->get_baseurl() . '/profile/' . $u[0]['nickname']);
				logger("item_store: 'myself' is ".$self." for parent ".$parent_id." checking against ".$arr['author-link']." and ".$arr['owner-link'], LOGGER_DEBUG);
				if ((normalise_link($arr['author-link']) == $self) OR (normalise_link($arr['owner-link']) == $self)) {
					q("UPDATE `thread` SET `mention` = 1 WHERE `iid` = %d", intval($parent_id));
					logger("item_store: tagged thread ".$parent_id." as mention for user ".$self, LOGGER_DEBUG);
				}
			}
		}
		else {

			// Allow one to see reply tweets from status.net even when
			// we don't have or can't see the original post.

			if($force_parent) {
				logger('item_store: $force_parent=true, reply converted to top-level post.');
				$parent_id = 0;
				$arr['parent-uri'] = $arr['uri'];
				$arr['gravity'] = 0;
			}
			else {
				logger('item_store: item parent '.$arr['parent-uri'].' for '.$arr['uid'].' was not found - ignoring item');
				return 0;
			}

			$parent_deleted = 0;
		}
	}

	$r = q("SELECT `id` FROM `item` WHERE `uri` = '%s' AND `network` IN ('%s', '%s') AND `uid` = %d LIMIT 1",
		dbesc($arr['uri']),
		dbesc($arr['network']),
		dbesc(NETWORK_DFRN),
		intval($arr['uid'])
	);
	if($r && count($r)) {
		logger('duplicated item with the same uri found. ' . print_r($arr,true));
		return 0;
	}

	// Check for an existing post with the same content. There seems to be a problem with OStatus.
	$r = q("SELECT `id` FROM `item` WHERE `body` = '%s' AND `network` = '%s' AND `created` = '%s' AND `contact-id` = %d AND `uid` = %d LIMIT 1",
		dbesc($arr['body']),
		dbesc($arr['network']),
		dbesc($arr['created']),
		intval($arr['contact-id']),
		intval($arr['uid'])
	);
	if($r && count($r)) {
		logger('duplicated item with the same body found. ' . print_r($arr,true));
		return 0;
	}

	// Is this item available in the global items (with uid=0)?
	if ($arr["uid"] == 0) {
		$arr["global"] = true;

		q("UPDATE `item` SET `global` = 1 WHERE `guid` = '%s'", dbesc($arr["guid"]));
	}  else {
		$isglobal = q("SELECT `global` FROM `item` WHERE `uid` = 0 AND `guid` = '%s'", dbesc($arr["guid"]));

		$arr["global"] = (count($isglobal) > 0);
	}

	// Fill the cache field
	put_item_in_cache($arr);

	if ($notify)
		call_hooks('post_local',$arr);
	else
		call_hooks('post_remote',$arr);

	if(x($arr,'cancel')) {
		logger('item_store: post cancelled by plugin.');
		return 0;
	}

	// Store the unescaped version
	$unescaped = $arr;

	dbesc_array($arr);

	logger('item_store: ' . print_r($arr,true), LOGGER_DATA);

	$r = dbq("INSERT INTO `item` (`"
			. implode("`, `", array_keys($arr))
			. "`) VALUES ('"
			. implode("', '", array_values($arr))
			. "')" );

	// And restore it
	$arr = $unescaped;

	// find the item that we just created
	$r = q("SELECT `id` FROM `item` WHERE `uri` = '%s' AND `uid` = %d AND `network` = '%s' ORDER BY `id` ASC",
		dbesc($arr['uri']),
		intval($arr['uid']),
		dbesc($arr['network'])
	);

	if(count($r) > 1) {
		// There are duplicates. Keep the oldest one, delete the others
		logger('item_store: duplicated post occurred. Removing newer duplicates. uri = '.$arr['uri'].' uid = '.$arr['uid']);
		q("DELETE FROM `item` WHERE `uri` = '%s' AND `uid` = %d AND `network` = '%s' AND `id` > %d",
			dbesc($arr['uri']),
			intval($arr['uid']),
			dbesc($arr['network']),
			intval($r[0]["id"])
		);
		return 0;
	} elseif(count($r)) {

		// Store the guid and other relevant data
		add_guid($arr);

		$current_post = $r[0]['id'];
		logger('item_store: created item ' . $current_post);

		// Set "success_update" and "last-item" to the date of the last time we heard from this contact
		// This can be used to filter for inactive contacts.
		// Only do this for public postings to avoid privacy problems, since poco data is public.
		// Don't set this value if it isn't from the owner (could be an author that we don't know)

		$update = (!$arr['private'] AND (($arr["author-link"] === $arr["owner-link"]) OR ($arr["parent-uri"] === $arr["uri"])));

		// Is it a forum? Then we don't care about the rules from above
		if (!$update AND ($arr["network"] == NETWORK_DFRN) AND ($arr["parent-uri"] === $arr["uri"])) {
			$isforum = q("SELECT `forum` FROM `contact` WHERE `id` = %d AND `forum`",
					intval($arr['contact-id']));
			if ($isforum)
				$update = true;
		}

		if ($update)
			q("UPDATE `contact` SET `success_update` = '%s', `last-item` = '%s' WHERE `id` = %d",
				dbesc($arr['received']),
				dbesc($arr['received']),
				intval($arr['contact-id'])
			);
	} else {
		logger('item_store: could not locate created item');
		return 0;
	}

	if((! $parent_id) || ($arr['parent-uri'] === $arr['uri']))
		$parent_id = $current_post;

	if(strlen($allow_cid) || strlen($allow_gid) || strlen($deny_cid) || strlen($deny_gid))
		$private = 1;
	else
		$private = $arr['private'];

	// Set parent id - and also make sure to inherit the parent's ACLs.

	$r = q("UPDATE `item` SET `parent` = %d, `allow_cid` = '%s', `allow_gid` = '%s',
		`deny_cid` = '%s', `deny_gid` = '%s', `private` = %d, `deleted` = %d WHERE `id` = %d",
		intval($parent_id),
		dbesc($allow_cid),
		dbesc($allow_gid),
		dbesc($deny_cid),
		dbesc($deny_gid),
		intval($private),
		intval($parent_deleted),
		intval($current_post)
	);

	$arr['id'] = $current_post;
	$arr['parent'] = $parent_id;
	$arr['allow_cid'] = $allow_cid;
	$arr['allow_gid'] = $allow_gid;
	$arr['deny_cid'] = $deny_cid;
	$arr['deny_gid'] = $deny_gid;
	$arr['private'] = $private;
	$arr['deleted'] = $parent_deleted;

	// update the commented timestamp on the parent
	// Only update "commented" if it is really a comment
	if (($arr['verb'] == ACTIVITY_POST) OR !get_config("system", "like_no_comment"))
		q("UPDATE `item` SET `commented` = '%s', `changed` = '%s' WHERE `id` = %d",
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			intval($parent_id)
		);
	else
		q("UPDATE `item` SET `changed` = '%s' WHERE `id` = %d",
			dbesc(datetime_convert()),
			intval($parent_id)
		);

	if($dsprsig) {

		// Friendica servers lower than 3.4.3-2 had double encoded the signature ...
		// We can check for this condition when we decode and encode the stuff again.
		if (base64_encode(base64_decode(base64_decode($dsprsig->signature))) == base64_decode($dsprsig->signature)) {
			$dsprsig->signature = base64_decode($dsprsig->signature);
			logger("Repaired double encoded signature from handle ".$dsprsig->signer, LOGGER_DEBUG);
		}

		q("insert into sign (`iid`,`signed_text`,`signature`,`signer`) values (%d,'%s','%s','%s') ",
			intval($current_post),
			dbesc($dsprsig->signed_text),
			dbesc($dsprsig->signature),
			dbesc($dsprsig->signer)
		);
	}


	/**
	 * If this is now the last-child, force all _other_ children of this parent to *not* be last-child
	 */

	if($arr['last-child']) {
		$r = q("UPDATE `item` SET `last-child` = 0 WHERE `parent-uri` = '%s' AND `uid` = %d AND `id` != %d",
			dbesc($arr['uri']),
			intval($arr['uid']),
			intval($current_post)
		);
	}

	$deleted = tag_deliver($arr['uid'],$current_post);

	// current post can be deleted if is for a community page and no mention are
	// in it.
	if (!$deleted AND !$dontcache) {

		$r = q('SELECT * FROM `item` WHERE id = %d', intval($current_post));
		if (count($r) == 1) {
			if ($notify)
				call_hooks('post_local_end', $r[0]);
			else
				call_hooks('post_remote_end', $r[0]);
		} else
			logger('item_store: new item not found in DB, id ' . $current_post);
	}

	// Add every contact of the post to the global contact table
	poco_store($arr);

	create_tags_from_item($current_post);
	create_files_from_item($current_post);

	// Only check for notifications on start posts
	if ($arr['parent-uri'] === $arr['uri'])
		add_thread($current_post);
	else {
		update_thread($parent_id);
		add_shadow_entry($arr);
	}

	check_item_notification($current_post, $uid);

	if ($notify)
		proc_run('php', "include/notifier.php", $notify_type, $current_post);

	return $current_post;
}

function item_body_set_hashtags(&$item) {

	$tags = get_tags($item["body"]);

	// No hashtags?
	if(!count($tags))
		return(false);

	// This sorting is important when there are hashtags that are part of other hashtags
	// Otherwise there could be problems with hashtags like #test and #test2
	rsort($tags);

	$a = get_app();

	$URLSearchString = "^\[\]";

	// All hashtags should point to the home server
	//$item["body"] = preg_replace("/#\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism",
	//		"#[url=".$a->get_baseurl()."/search?tag=$2]$2[/url]", $item["body"]);

	//$item["tag"] = preg_replace("/#\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism",
	//		"#[url=".$a->get_baseurl()."/search?tag=$2]$2[/url]", $item["tag"]);

	// mask hashtags inside of url, bookmarks and attachments to avoid urls in urls
	$item["body"] = preg_replace_callback("/\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism",
		function ($match){
			return("[url=".str_replace("#", "&num;", $match[1])."]".str_replace("#", "&num;", $match[2])."[/url]");
		},$item["body"]);

	$item["body"] = preg_replace_callback("/\[bookmark\=([$URLSearchString]*)\](.*?)\[\/bookmark\]/ism",
		function ($match){
			return("[bookmark=".str_replace("#", "&num;", $match[1])."]".str_replace("#", "&num;", $match[2])."[/bookmark]");
		},$item["body"]);

	$item["body"] = preg_replace_callback("/\[attachment (.*)\](.*?)\[\/attachment\]/ism",
		function ($match){
			return("[attachment ".str_replace("#", "&num;", $match[1])."]".$match[2]."[/attachment]");
		},$item["body"]);

	// Repair recursive urls
	$item["body"] = preg_replace("/&num;\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism",
			"&num;$2", $item["body"]);


	foreach($tags as $tag) {
		if(strpos($tag,'#') !== 0)
			continue;

		if(strpos($tag,'[url='))
			continue;

		$basetag = str_replace('_',' ',substr($tag,1));

		$newtag = '#[url='.$a->get_baseurl().'/search?tag='.rawurlencode($basetag).']'.$basetag.'[/url]';

		$item["body"] = str_replace($tag, $newtag, $item["body"]);

		if(!stristr($item["tag"],"/search?tag=".$basetag."]".$basetag."[/url]")) {
			if(strlen($item["tag"]))
				$item["tag"] = ','.$item["tag"];
			$item["tag"] = $newtag.$item["tag"];
		}
	}

	// Convert back the masked hashtags
	$item["body"] = str_replace("&num;", "#", $item["body"]);
}

function get_item_guid($id) {
	$r = q("SELECT `guid` FROM `item` WHERE `id` = %d LIMIT 1", intval($id));
	if (count($r))
		return($r[0]["guid"]);
	else
		return("");
}

function get_item_id($guid, $uid = 0) {

	$nick = "";
	$id = 0;

	if ($uid == 0)
		$uid == local_user();

	// Does the given user have this item?
	if ($uid) {
		$r = q("SELECT `item`.`id`, `user`.`nickname` FROM `item` INNER JOIN `user` ON `user`.`uid` = `item`.`uid`
			WHERE `item`.`visible` = 1 AND `item`.`deleted` = 0 and `item`.`moderated` = 0
				AND `item`.`guid` = '%s' AND `item`.`uid` = %d", dbesc($guid), intval($uid));
		if (count($r)) {
			$id = $r[0]["id"];
			$nick = $r[0]["nickname"];
		}
	}

	// Or is it anywhere on the server?
	if ($nick == "") {
		$r = q("SELECT `item`.`id`, `user`.`nickname` FROM `item` INNER JOIN `user` ON `user`.`uid` = `item`.`uid`
			WHERE `item`.`visible` = 1 AND `item`.`deleted` = 0 and `item`.`moderated` = 0
				AND `item`.`allow_cid` = ''  AND `item`.`allow_gid` = ''
				AND `item`.`deny_cid`  = '' AND `item`.`deny_gid`  = ''
				AND `item`.`private` = 0 AND `item`.`wall` = 1
				AND `item`.`guid` = '%s'", dbesc($guid));
		if (count($r)) {
			$id = $r[0]["id"];
			$nick = $r[0]["nickname"];
		}
	}
	return(array("nick" => $nick, "id" => $id));
}

// return - test
function get_item_contact($item,$contacts) {
	if(! count($contacts) || (! is_array($item)))
		return false;
	foreach($contacts as $contact) {
		if($contact['id'] == $item['contact-id']) {
			return $contact;
			break; // NOTREACHED
		}
	}
	return false;
}

/**
 * look for mention tags and setup a second delivery chain for forum/community posts if appropriate
 * @param int $uid
 * @param int $item_id
 * @return bool true if item was deleted, else false
 */
function tag_deliver($uid,$item_id) {

	//

	$a = get_app();

	$mention = false;

	$u = q("select * from user where uid = %d limit 1",
		intval($uid)
	);
	if(! count($u))
		return;

	$community_page = (($u[0]['page-flags'] == PAGE_COMMUNITY) ? true : false);
	$prvgroup = (($u[0]['page-flags'] == PAGE_PRVGROUP) ? true : false);


	$i = q("select * from item where id = %d and uid = %d limit 1",
		intval($item_id),
		intval($uid)
	);
	if(! count($i))
		return;

	$item = $i[0];

	$link = normalise_link($a->get_baseurl() . '/profile/' . $u[0]['nickname']);

	// Diaspora uses their own hardwired link URL in @-tags
	// instead of the one we supply with webfinger

	$dlink = normalise_link($a->get_baseurl() . '/u/' . $u[0]['nickname']);

	$cnt = preg_match_all('/[\@\!]\[url\=(.*?)\](.*?)\[\/url\]/ism',$item['body'],$matches,PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			if(link_compare($link,$mtch[1]) || link_compare($dlink,$mtch[1])) {
				$mention = true;
				logger('tag_deliver: mention found: ' . $mtch[2]);
			}
		}
	}

	if(! $mention){
		if ( ($community_page || $prvgroup) &&
			  (!$item['wall']) && (!$item['origin']) && ($item['id'] == $item['parent'])){
			// mmh.. no mention.. community page or private group... no wall.. no origin.. top-post (not a comment)
			// delete it!
			logger("tag_deliver: no-mention top-level post to communuty or private group. delete.");
			q("DELETE FROM item WHERE id = %d and uid = %d",
				intval($item_id),
				intval($uid)
			);
			return true;
		}
		return;
	}

	$arr = array('item' => $item, 'user' => $u[0], 'contact' => $r[0]);

	call_hooks('tagged', $arr);

	if((! $community_page) && (! $prvgroup))
		return;


	// tgroup delivery - setup a second delivery chain
	// prevent delivery looping - only proceed
	// if the message originated elsewhere and is a top-level post

	if(($item['wall']) || ($item['origin']) || ($item['id'] != $item['parent']))
		return;

	// now change this copy of the post to a forum head message and deliver to all the tgroup members


	$c = q("select name, url, thumb from contact where self = 1 and uid = %d limit 1",
		intval($u[0]['uid'])
	);
	if(! count($c))
		return;

	// also reset all the privacy bits to the forum default permissions

	$private = ($u[0]['allow_cid'] || $u[0]['allow_gid'] || $u[0]['deny_cid'] || $u[0]['deny_gid']) ? 1 : 0;

	$forum_mode = (($prvgroup) ? 2 : 1);

	q("update item set wall = 1, origin = 1, forum_mode = %d, `owner-name` = '%s', `owner-link` = '%s', `owner-avatar` = '%s',
		`private` = %d, `allow_cid` = '%s', `allow_gid` = '%s', `deny_cid` = '%s', `deny_gid` = '%s'  where id = %d",
		intval($forum_mode),
		dbesc($c[0]['name']),
		dbesc($c[0]['url']),
		dbesc($c[0]['thumb']),
		intval($private),
		dbesc($u[0]['allow_cid']),
		dbesc($u[0]['allow_gid']),
		dbesc($u[0]['deny_cid']),
		dbesc($u[0]['deny_gid']),
		intval($item_id)
	);
	update_thread($item_id);

	proc_run('php','include/notifier.php','tgroup',$item_id);

}



function tgroup_check($uid,$item) {

	$a = get_app();

	$mention = false;

	// check that the message originated elsewhere and is a top-level post

	if(($item['wall']) || ($item['origin']) || ($item['uri'] != $item['parent-uri']))
		return false;


	$u = q("select * from user where uid = %d limit 1",
		intval($uid)
	);
	if(! count($u))
		return false;

	$community_page = (($u[0]['page-flags'] == PAGE_COMMUNITY) ? true : false);
	$prvgroup = (($u[0]['page-flags'] == PAGE_PRVGROUP) ? true : false);


	$link = normalise_link($a->get_baseurl() . '/profile/' . $u[0]['nickname']);

	// Diaspora uses their own hardwired link URL in @-tags
	// instead of the one we supply with webfinger

	$dlink = normalise_link($a->get_baseurl() . '/u/' . $u[0]['nickname']);

	$cnt = preg_match_all('/[\@\!]\[url\=(.*?)\](.*?)\[\/url\]/ism',$item['body'],$matches,PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			if(link_compare($link,$mtch[1]) || link_compare($dlink,$mtch[1])) {
				$mention = true;
				logger('tgroup_check: mention found: ' . $mtch[2]);
			}
		}
	}

	if(! $mention)
		return false;

	if((! $community_page) && (! $prvgroup))
		return false;

	return true;
}

/*
  This function returns true if $update has an edited timestamp newer
  than $existing, i.e. $update contains new data which should override
  what's already there.  If there is no timestamp yet, the update is
  assumed to be newer.  If the update has no timestamp, the existing
  item is assumed to be up-to-date.  If the timestamps are equal it
  assumes the update has been seen before and should be ignored.
  */
function edited_timestamp_is_newer($existing, $update) {
    if (!x($existing,'edited') || !$existing['edited']) {
	return true;
    }
    if (!x($update,'edited') || !$update['edited']) {
	return false;
    }
    $existing_edited = datetime_convert('UTC', 'UTC', $existing['edited']);
    $update_edited = datetime_convert('UTC', 'UTC', $update['edited']);
    return (strcmp($existing_edited, $update_edited) < 0);
}

/**
 *
 * consume_feed - process atom feed and update anything/everything we might need to update
 *
 * $xml = the (atom) feed to consume - RSS isn't as fully supported but may work for simple feeds.
 *
 * $importer = the contact_record (joined to user_record) of the local user who owns this relationship.
 *             It is this person's stuff that is going to be updated.
 * $contact =  the person who is sending us stuff. If not set, we MAY be processing a "follow" activity
 *             from an external network and MAY create an appropriate contact record. Otherwise, we MUST
 *             have a contact record.
 * $hub = should we find a hub declation in the feed, pass it back to our calling process, who might (or
 *        might not) try and subscribe to it.
 * $datedir sorts in reverse order
 * $pass - by default ($pass = 0) we cannot guarantee that a parent item has been
 *      imported prior to its children being seen in the stream unless we are certain
 *      of how the feed is arranged/ordered.
 * With $pass = 1, we only pull parent items out of the stream.
 * With $pass = 2, we only pull children (comments/likes).
 *
 * So running this twice, first with pass 1 and then with pass 2 will do the right
 * thing regardless of feed ordering. This won't be adequate in a fully-threaded
 * model where comments can have sub-threads. That would require some massive sorting
 * to get all the feed items into a mostly linear ordering, and might still require
 * recursion.
 */

function consume_feed($xml,$importer,&$contact, &$hub, $datedir = 0, $pass = 0) {
	if ($contact['network'] === NETWORK_OSTATUS) {
		if ($pass < 2) {
			// Test - remove before flight
			//$tempfile = tempnam(get_temppath(), "ostatus2");
			//file_put_contents($tempfile, $xml);
			logger("Consume OStatus messages ", LOGGER_DEBUG);
			ostatus_import($xml,$importer,$contact, $hub);
		}
		return;
	}

	if ($contact['network'] === NETWORK_FEED) {
		if ($pass < 2) {
			logger("Consume feeds", LOGGER_DEBUG);
			feed_import($xml,$importer,$contact, $hub);
		}
		return;
	}
	// dfrn-test
/*
	if ($contact['network'] === NETWORK_DFRN) {
		logger("Consume DFRN messages", LOGGER_DEBUG);
		logger("dfrn-test");

		$r = q("SELECT  `contact`.*, `contact`.`uid` AS `importer_uid`,
                                        `contact`.`pubkey` AS `cpubkey`,
                                        `contact`.`prvkey` AS `cprvkey`,
                                        `contact`.`thumb` AS `thumb`,
                                        `contact`.`url` as `url`,
                                        `contact`.`name` as `senderName`,
                                        `user`.*
                        FROM `contact`
                        LEFT JOIN `user` ON `contact`.`uid` = `user`.`uid`
                        WHERE `contact`.`id` = %d AND `user`.`uid` = %d",
	                dbesc($contact["id"], $importer["uid"]);
	        );
		if ($r) {
			dfrn2::import($xml,$r[0], true);
			return;
		}
	}
*/
	// Test - remove before flight
	//if ($pass < 2) {
	//	$tempfile = tempnam(get_temppath(), "dfrn-consume-");
	//	file_put_contents($tempfile, $xml);
	//}

	require_once('library/simplepie/simplepie.inc');
	require_once('include/contact_selectors.php');

	if(! strlen($xml)) {
		logger('consume_feed: empty input');
		return;
	}

	$feed = new SimplePie();
	$feed->set_raw_data($xml);
	if($datedir)
		$feed->enable_order_by_date(true);
	else
		$feed->enable_order_by_date(false);
	$feed->init();

	if($feed->error())
		logger('consume_feed: Error parsing XML: ' . $feed->error());

	$permalink = $feed->get_permalink();

	// Check at the feed level for updated contact name and/or photo

	$name_updated  = '';
	$new_name = '';
	$photo_timestamp = '';
	$photo_url = '';
	$birthday = '';
	$contact_updated = '';

	$hubs = $feed->get_links('hub');
	logger('consume_feed: hubs: ' . print_r($hubs,true), LOGGER_DATA);

	if(count($hubs))
		$hub = implode(',', $hubs);

	$rawtags = $feed->get_feed_tags( NAMESPACE_DFRN, 'owner');
	if(! $rawtags)
		$rawtags = $feed->get_feed_tags( SIMPLEPIE_NAMESPACE_ATOM_10, 'author');
	if($rawtags) {
		$elems = $rawtags[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10];
		if($elems['name'][0]['attribs'][NAMESPACE_DFRN]['updated']) {
			$name_updated = $elems['name'][0]['attribs'][NAMESPACE_DFRN]['updated'];
			$new_name = $elems['name'][0]['data'];

			// Manually checking for changed contact names
			if (($new_name != $contact['name']) AND ($new_name != "") AND ($name_updated <= $contact['name-date'])) {
				$name_updated = date("c");
				$photo_timestamp = date("c");
			}
		}
		if((x($elems,'link')) && ($elems['link'][0]['attribs']['']['rel'] === 'photo') && ($elems['link'][0]['attribs'][NAMESPACE_DFRN]['updated'])) {
			if ($photo_timestamp == "")
				$photo_timestamp = datetime_convert('UTC','UTC',$elems['link'][0]['attribs'][NAMESPACE_DFRN]['updated']);
			$photo_url = $elems['link'][0]['attribs']['']['href'];
		}

		if((x($rawtags[0]['child'], NAMESPACE_DFRN)) && (x($rawtags[0]['child'][NAMESPACE_DFRN],'birthday'))) {
			$birthday = datetime_convert('UTC','UTC', $rawtags[0]['child'][NAMESPACE_DFRN]['birthday'][0]['data']);
		}
	}

	if((is_array($contact)) && ($photo_timestamp) && (strlen($photo_url)) && ($photo_timestamp > $contact['avatar-date'])) {
		logger('consume_feed: Updating photo for '.$contact['name'].' from '.$photo_url.' uid: '.$contact['uid']);

		$contact_updated = $photo_timestamp;

		require_once("include/Photo.php");
		$photos = import_profile_photo($photo_url,$contact['uid'],$contact['id']);

		q("UPDATE `contact` SET `avatar-date` = '%s', `photo` = '%s', `thumb` = '%s', `micro` = '%s'
			WHERE `uid` = %d AND `id` = %d AND NOT `self`",
			dbesc(datetime_convert()),
			dbesc($photos[0]),
			dbesc($photos[1]),
			dbesc($photos[2]),
			intval($contact['uid']),
			intval($contact['id'])
		);
	}

	if((is_array($contact)) && ($name_updated) && (strlen($new_name)) && ($name_updated > $contact['name-date'])) {
		if ($name_updated > $contact_updated)
			$contact_updated = $name_updated;

		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `id` = %d LIMIT 1",
			intval($contact['uid']),
			intval($contact['id'])
		);

		$x = q("UPDATE `contact` SET `name` = '%s', `name-date` = '%s' WHERE `uid` = %d AND `id` = %d AND `name` != '%s' AND NOT `self`",
			dbesc(notags(trim($new_name))),
			dbesc(datetime_convert()),
			intval($contact['uid']),
			intval($contact['id']),
			dbesc(notags(trim($new_name)))
		);

		// do our best to update the name on content items

		if(count($r) AND (notags(trim($new_name)) != $r[0]['name'])) {
			q("UPDATE `item` SET `author-name` = '%s' WHERE `author-name` = '%s' AND `author-link` = '%s' AND `uid` = %d AND `author-name` != '%s'",
				dbesc(notags(trim($new_name))),
				dbesc($r[0]['name']),
				dbesc($r[0]['url']),
				intval($contact['uid']),
				dbesc(notags(trim($new_name)))
			);
		}
	}

	if ($contact_updated AND $new_name AND $photo_url)
		poco_check($contact['url'], $new_name, NETWORK_DFRN, $photo_url, "", "", "", "", "", $contact_updated, 2, $contact['id'], $contact['uid']);

	if(strlen($birthday)) {
		if(substr($birthday,0,4) != $contact['bdyear']) {
			logger('consume_feed: updating birthday: ' . $birthday);

			/**
			 *
			 * Add new birthday event for this person
			 *
			 * $bdtext is just a readable placeholder in case the event is shared
			 * with others. We will replace it during presentation to our $importer
			 * to contain a sparkle link and perhaps a photo.
			 *
			 */

			$bdtext = sprintf( t('%s\'s birthday'), $contact['name']);
			$bdtext2 = sprintf( t('Happy Birthday %s'), ' [url=' . $contact['url'] . ']' . $contact['name'] . '[/url]' ) ;


			$r = q("INSERT INTO `event` (`uid`,`cid`,`created`,`edited`,`start`,`finish`,`summary`,`desc`,`type`)
				VALUES ( %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) ",
				intval($contact['uid']),
				intval($contact['id']),
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				dbesc(datetime_convert('UTC','UTC', $birthday)),
				dbesc(datetime_convert('UTC','UTC', $birthday . ' + 1 day ')),
				dbesc($bdtext),
				dbesc($bdtext2),
				dbesc('birthday')
			);


			// update bdyear

			q("UPDATE `contact` SET `bdyear` = '%s' WHERE `uid` = %d AND `id` = %d",
				dbesc(substr($birthday,0,4)),
				intval($contact['uid']),
				intval($contact['id'])
			);

			// This function is called twice without reloading the contact
			// Make sure we only create one event. This is why &$contact
			// is a reference var in this function

			$contact['bdyear'] = substr($birthday,0,4);
		}
	}

	$community_page = 0;
	$rawtags = $feed->get_feed_tags( NAMESPACE_DFRN, 'community');
	if($rawtags) {
		$community_page = intval($rawtags[0]['data']);
	}
	if(is_array($contact) && intval($contact['forum']) != $community_page) {
		q("update contact set forum = %d where id = %d",
			intval($community_page),
			intval($contact['id'])
		);
		$contact['forum'] = (string) $community_page;
	}


	// process any deleted entries

	$del_entries = $feed->get_feed_tags(NAMESPACE_TOMB, 'deleted-entry');
	if(is_array($del_entries) && count($del_entries) && $pass != 2) {
		foreach($del_entries as $dentry) {
			$deleted = false;
			if(isset($dentry['attribs']['']['ref'])) {
				$uri = $dentry['attribs']['']['ref'];
				$deleted = true;
				if(isset($dentry['attribs']['']['when'])) {
					$when = $dentry['attribs']['']['when'];
					$when = datetime_convert('UTC','UTC', $when, 'Y-m-d H:i:s');
				}
				else
					$when = datetime_convert('UTC','UTC','now','Y-m-d H:i:s');
			}
			if($deleted && is_array($contact)) {
				$r = q("SELECT `item`.*, `contact`.`self` FROM `item` INNER JOIN `contact` on `item`.`contact-id` = `contact`.`id`
					WHERE `uri` = '%s' AND `item`.`uid` = %d AND `contact-id` = %d AND NOT `item`.`file` LIKE '%%[%%' LIMIT 1",
					dbesc($uri),
					intval($importer['uid']),
					intval($contact['id'])
				);
				if(count($r)) {
					$item = $r[0];

					if(! $item['deleted'])
						logger('consume_feed: deleting item ' . $item['id'] . ' uri=' . $item['uri'], LOGGER_DEBUG);

					if($item['object-type'] === ACTIVITY_OBJ_EVENT) {
						logger("Deleting event ".$item['event-id'], LOGGER_DEBUG);
						event_delete($item['event-id']);
					}

					if(($item['verb'] === ACTIVITY_TAG) && ($item['object-type'] === ACTIVITY_OBJ_TAGTERM)) {
						$xo = parse_xml_string($item['object'],false);
						$xt = parse_xml_string($item['target'],false);
						if($xt->type === ACTIVITY_OBJ_NOTE) {
							$i = q("select * from `item` where uri = '%s' and uid = %d limit 1",
								dbesc($xt->id),
								intval($importer['importer_uid'])
							);
							if(count($i)) {

								// For tags, the owner cannot remove the tag on the author's copy of the post.

								$owner_remove = (($item['contact-id'] == $i[0]['contact-id']) ? true: false);
								$author_remove = (($item['origin'] && $item['self']) ? true : false);
								$author_copy = (($item['origin']) ? true : false);

								if($owner_remove && $author_copy)
									continue;
								if($author_remove || $owner_remove) {
									$tags = explode(',',$i[0]['tag']);
									$newtags = array();
									if(count($tags)) {
										foreach($tags as $tag)
											if(trim($tag) !== trim($xo->body))
												$newtags[] = trim($tag);
									}
									q("update item set tag = '%s' where id = %d",
										dbesc(implode(',',$newtags)),
										intval($i[0]['id'])
									);
									create_tags_from_item($i[0]['id']);
								}
							}
						}
					}

					if($item['uri'] == $item['parent-uri']) {
						$r = q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s',
							`body` = '', `title` = ''
							WHERE `parent-uri` = '%s' AND `uid` = %d",
							dbesc($when),
							dbesc(datetime_convert()),
							dbesc($item['uri']),
							intval($importer['uid'])
						);
						create_tags_from_itemuri($item['uri'], $importer['uid']);
						create_files_from_itemuri($item['uri'], $importer['uid']);
						update_thread_uri($item['uri'], $importer['uid']);
					}
					else {
						$r = q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s',
							`body` = '', `title` = ''
							WHERE `uri` = '%s' AND `uid` = %d",
							dbesc($when),
							dbesc(datetime_convert()),
							dbesc($uri),
							intval($importer['uid'])
						);
						create_tags_from_itemuri($uri, $importer['uid']);
						create_files_from_itemuri($uri, $importer['uid']);
						if($item['last-child']) {
							// ensure that last-child is set in case the comment that had it just got wiped.
							q("UPDATE `item` SET `last-child` = 0, `changed` = '%s' WHERE `parent-uri` = '%s' AND `uid` = %d ",
								dbesc(datetime_convert()),
								dbesc($item['parent-uri']),
								intval($item['uid'])
							);
							// who is the last child now?
							$r = q("SELECT `id` FROM `item` WHERE `parent-uri` = '%s' AND `type` != 'activity' AND `deleted` = 0 AND `moderated` = 0 AND `uid` = %d
								ORDER BY `created` DESC LIMIT 1",
									dbesc($item['parent-uri']),
									intval($importer['uid'])
							);
							if(count($r)) {
								q("UPDATE `item` SET `last-child` = 1 WHERE `id` = %d",
									intval($r[0]['id'])
								);
							}
						}
					}
				}
			}
		}
	}

	// Now process the feed

	if($feed->get_item_quantity()) {

		logger('consume_feed: feed item count = ' . $feed->get_item_quantity());

		// in inverse date order
		if ($datedir)
			$items = array_reverse($feed->get_items());
		else
			$items = $feed->get_items();


		foreach($items as $item) {

			$is_reply = false;
			$item_id = $item->get_id();
			$rawthread = $item->get_item_tags( NAMESPACE_THREAD,'in-reply-to');
			if(isset($rawthread[0]['attribs']['']['ref'])) {
				$is_reply = true;
				$parent_uri = $rawthread[0]['attribs']['']['ref'];
			}

			if(($is_reply) && is_array($contact)) {

				if($pass == 1)
					continue;

				// not allowed to post

				if($contact['rel'] == CONTACT_IS_FOLLOWER)
					continue;


				// Have we seen it? If not, import it.

				$item_id  = $item->get_id();
				$datarray = get_atom_elements($feed, $item, $contact);

				if((! x($datarray,'author-name')) && ($contact['network'] != NETWORK_DFRN))
					$datarray['author-name'] = $contact['name'];
				if((! x($datarray,'author-link')) && ($contact['network'] != NETWORK_DFRN))
					$datarray['author-link'] = $contact['url'];
				if((! x($datarray,'author-avatar')) && ($contact['network'] != NETWORK_DFRN))
					$datarray['author-avatar'] = $contact['thumb'];

				if((! x($datarray,'author-name')) || (! x($datarray,'author-link'))) {
					logger('consume_feed: no author information! ' . print_r($datarray,true));
					continue;
				}

				$force_parent = false;
				if($contact['network'] === NETWORK_OSTATUS || stristr($contact['url'],'twitter.com')) {
					if($contact['network'] === NETWORK_OSTATUS)
						$force_parent = true;
					if(strlen($datarray['title']))
						unset($datarray['title']);
					$r = q("UPDATE `item` SET `last-child` = 0, `changed` = '%s' WHERE `parent-uri` = '%s' AND `uid` = %d",
						dbesc(datetime_convert()),
						dbesc($parent_uri),
						intval($importer['uid'])
					);
					$datarray['last-child'] = 1;
					update_thread_uri($parent_uri, $importer['uid']);
				}


				$r = q("SELECT `uid`, `last-child`, `edited`, `body` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
					dbesc($item_id),
					intval($importer['uid'])
				);

				// Update content if 'updated' changes

				if(count($r)) {
					if (edited_timestamp_is_newer($r[0], $datarray)) {

						// do not accept (ignore) an earlier edit than one we currently have.
						if(datetime_convert('UTC','UTC',$datarray['edited']) < $r[0]['edited'])
							continue;

						$r = q("UPDATE `item` SET `title` = '%s', `body` = '%s', `tag` = '%s', `edited` = '%s', `changed` = '%s' WHERE `uri` = '%s' AND `uid` = %d",
							dbesc($datarray['title']),
							dbesc($datarray['body']),
							dbesc($datarray['tag']),
							dbesc(datetime_convert('UTC','UTC',$datarray['edited'])),
							dbesc(datetime_convert()),
							dbesc($item_id),
							intval($importer['uid'])
						);
						create_tags_from_itemuri($item_id, $importer['uid']);
						update_thread_uri($item_id, $importer['uid']);
					}

					// update last-child if it changes

					$allow = $item->get_item_tags( NAMESPACE_DFRN, 'comment-allow');
					if(($allow) && ($allow[0]['data'] != $r[0]['last-child'])) {
						$r = q("UPDATE `item` SET `last-child` = 0, `changed` = '%s' WHERE `parent-uri` = '%s' AND `uid` = %d",
							dbesc(datetime_convert()),
							dbesc($parent_uri),
							intval($importer['uid'])
						);
						$r = q("UPDATE `item` SET `last-child` = %d , `changed` = '%s'  WHERE `uri` = '%s' AND `uid` = %d",
							intval($allow[0]['data']),
							dbesc(datetime_convert()),
							dbesc($item_id),
							intval($importer['uid'])
						);
						update_thread_uri($item_id, $importer['uid']);
					}
					continue;
				}


				if(($contact['network'] === NETWORK_FEED) || (! strlen($contact['notify']))) {
					// one way feed - no remote comment ability
					$datarray['last-child'] = 0;
				}
				$datarray['parent-uri'] = $parent_uri;
				$datarray['uid'] = $importer['uid'];
				$datarray['contact-id'] = $contact['id'];
				if(($datarray['verb'] === ACTIVITY_LIKE)
					|| ($datarray['verb'] === ACTIVITY_DISLIKE)
					|| ($datarray['verb'] === ACTIVITY_ATTEND)
					|| ($datarray['verb'] === ACTIVITY_ATTENDNO)
					|| ($datarray['verb'] === ACTIVITY_ATTENDMAYBE)) {
					$datarray['type'] = 'activity';
					$datarray['gravity'] = GRAVITY_LIKE;
					// only one like or dislike per person
					// splitted into two queries for performance issues
					$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `author-link` = '%s' AND `verb` = '%s' AND `parent-uri` = '%s' AND NOT `deleted` LIMIT 1",
						intval($datarray['uid']),
						dbesc($datarray['author-link']),
						dbesc($datarray['verb']),
						dbesc($datarray['parent-uri'])
					);
					if($r && count($r))
						continue;

					$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `author-link` = '%s' AND `verb` = '%s' AND `thr-parent` = '%s' AND NOT `deleted` LIMIT 1",
						intval($datarray['uid']),
						dbesc($datarray['author-link']),
						dbesc($datarray['verb']),
						dbesc($datarray['parent-uri'])
					);
					if($r && count($r))
						continue;
				}

				if(($datarray['verb'] === ACTIVITY_TAG) && ($datarray['object-type'] === ACTIVITY_OBJ_TAGTERM)) {
					$xo = parse_xml_string($datarray['object'],false);
					$xt = parse_xml_string($datarray['target'],false);

					if($xt->type == ACTIVITY_OBJ_NOTE) {
						$r = q("select * from item where `uri` = '%s' AND `uid` = %d limit 1",
							dbesc($xt->id),
							intval($importer['importer_uid'])
						);
						if(! count($r))
							continue;

						// extract tag, if not duplicate, add to parent item
						if($xo->id && $xo->content) {
							$newtag = '#[url=' . $xo->id . ']'. $xo->content . '[/url]';
							if(! (stristr($r[0]['tag'],$newtag))) {
								q("UPDATE item SET tag = '%s' WHERE id = %d",
									dbesc($r[0]['tag'] . (strlen($r[0]['tag']) ? ',' : '') . $newtag),
									intval($r[0]['id'])
								);
								create_tags_from_item($r[0]['id']);
							}
						}
					}
				}

				$r = item_store($datarray,$force_parent);
				continue;
			}

			else {

				// Head post of a conversation. Have we seen it? If not, import it.

				$item_id  = $item->get_id();

				$datarray = get_atom_elements($feed, $item, $contact);

				if(is_array($contact)) {
					if((! x($datarray,'author-name')) && ($contact['network'] != NETWORK_DFRN))
						$datarray['author-name'] = $contact['name'];
					if((! x($datarray,'author-link')) && ($contact['network'] != NETWORK_DFRN))
						$datarray['author-link'] = $contact['url'];
					if((! x($datarray,'author-avatar')) && ($contact['network'] != NETWORK_DFRN))
						$datarray['author-avatar'] = $contact['thumb'];
				}

				if((! x($datarray,'author-name')) || (! x($datarray,'author-link'))) {
					logger('consume_feed: no author information! ' . print_r($datarray,true));
					continue;
				}

				// special handling for events

				if((x($datarray,'object-type')) && ($datarray['object-type'] === ACTIVITY_OBJ_EVENT)) {
					$ev = bbtoevent($datarray['body']);
					if((x($ev,'desc') || x($ev,'summary')) && x($ev,'start')) {
						$ev['uid'] = $importer['uid'];
						$ev['uri'] = $item_id;
						$ev['edited'] = $datarray['edited'];
						$ev['private'] = $datarray['private'];
						$ev['guid'] = $datarray['guid'];

						if(is_array($contact))
							$ev['cid'] = $contact['id'];
						$r = q("SELECT * FROM `event` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
							dbesc($item_id),
							intval($importer['uid'])
						);
						if(count($r))
							$ev['id'] = $r[0]['id'];
						$xyz = event_store($ev);
						continue;
					}
				}

				if($contact['network'] === NETWORK_OSTATUS || stristr($contact['url'],'twitter.com')) {
					if(strlen($datarray['title']))
						unset($datarray['title']);
					$datarray['last-child'] = 1;
				}


				$r = q("SELECT `uid`, `last-child`, `edited`, `body` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
					dbesc($item_id),
					intval($importer['uid'])
				);

				// Update content if 'updated' changes

				if(count($r)) {
					if (edited_timestamp_is_newer($r[0], $datarray)) {

						// do not accept (ignore) an earlier edit than one we currently have.
						if(datetime_convert('UTC','UTC',$datarray['edited']) < $r[0]['edited'])
							continue;

						$r = q("UPDATE `item` SET `title` = '%s', `body` = '%s', `tag` = '%s', `edited` = '%s', `changed` = '%s' WHERE `uri` = '%s' AND `uid` = %d",
							dbesc($datarray['title']),
							dbesc($datarray['body']),
							dbesc($datarray['tag']),
							dbesc(datetime_convert('UTC','UTC',$datarray['edited'])),
							dbesc(datetime_convert()),
							dbesc($item_id),
							intval($importer['uid'])
						);
						create_tags_from_itemuri($item_id, $importer['uid']);
						update_thread_uri($item_id, $importer['uid']);
					}

					// update last-child if it changes

					$allow = $item->get_item_tags( NAMESPACE_DFRN, 'comment-allow');
					if($allow && $allow[0]['data'] != $r[0]['last-child']) {
						$r = q("UPDATE `item` SET `last-child` = %d , `changed` = '%s' WHERE `uri` = '%s' AND `uid` = %d",
							intval($allow[0]['data']),
							dbesc(datetime_convert()),
							dbesc($item_id),
							intval($importer['uid'])
						);
						update_thread_uri($item_id, $importer['uid']);
					}
					continue;
				}

				if(activity_match($datarray['verb'],ACTIVITY_FOLLOW)) {
					logger('consume-feed: New follower');
					new_follower($importer,$contact,$datarray,$item);
					return;
				}
				if(activity_match($datarray['verb'],ACTIVITY_UNFOLLOW))  {
					lose_follower($importer,$contact,$datarray,$item);
					return;
				}

				if(activity_match($datarray['verb'],ACTIVITY_REQ_FRIEND)) {
					logger('consume-feed: New friend request');
					new_follower($importer,$contact,$datarray,$item,true);
					return;
				}
				if(activity_match($datarray['verb'],ACTIVITY_UNFRIEND))  {
					lose_sharer($importer,$contact,$datarray,$item);
					return;
				}


				if(! is_array($contact))
					return;


				if(($contact['network'] === NETWORK_FEED) || (! strlen($contact['notify']))) {
						// one way feed - no remote comment ability
						$datarray['last-child'] = 0;
				}
				if($contact['network'] === NETWORK_FEED)
					$datarray['private'] = 2;

				$datarray['parent-uri'] = $item_id;
				$datarray['uid'] = $importer['uid'];
				$datarray['contact-id'] = $contact['id'];

				if(! link_compare($datarray['owner-link'],$contact['url'])) {
					// The item owner info is not our contact. It's OK and is to be expected if this is a tgroup delivery,
					// but otherwise there's a possible data mixup on the sender's system.
					// the tgroup delivery code called from item_store will correct it if it's a forum,
					// but we're going to unconditionally correct it here so that the post will always be owned by our contact.
					logger('consume_feed: Correcting item owner.', LOGGER_DEBUG);
					$datarray['owner-name']   = $contact['name'];
					$datarray['owner-link']   = $contact['url'];
					$datarray['owner-avatar'] = $contact['thumb'];
				}

				// We've allowed "followers" to reach this point so we can decide if they are
				// posting an @-tag delivery, which followers are allowed to do for certain
				// page types. Now that we've parsed the post, let's check if it is legit. Otherwise ignore it.

				if(($contact['rel'] == CONTACT_IS_FOLLOWER) && (! tgroup_check($importer['uid'],$datarray)))
					continue;

				// This is my contact on another system, but it's really me.
				// Turn this into a wall post.
				$notify = item_is_remote_self($contact, $datarray);

				$r = item_store($datarray, false, $notify);
				logger('Stored - Contact '.$contact['url'].' Notify '.$notify.' return '.$r.' Item '.print_r($datarray, true), LOGGER_DEBUG);
				continue;

			}
		}
	}
}

function item_is_remote_self($contact, &$datarray) {
	$a = get_app();

	if (!$contact['remote_self'])
		return false;

	// Prevent the forwarding of posts that are forwarded
	if ($datarray["extid"] == NETWORK_DFRN)
		return false;

	// Prevent to forward already forwarded posts
	if ($datarray["app"] == $a->get_hostname())
		return false;

	// Only forward posts
	if ($datarray["verb"] != ACTIVITY_POST)
		return false;

	if (($contact['network'] != NETWORK_FEED) AND $datarray['private'])
		return false;

	$datarray2 = $datarray;
	logger('remote-self start - Contact '.$contact['url'].' - '.$contact['remote_self'].' Item '.print_r($datarray, true), LOGGER_DEBUG);
	if ($contact['remote_self'] == 2) {
		$r = q("SELECT `id`,`url`,`name`,`thumb` FROM `contact` WHERE `uid` = %d AND `self`",
			intval($contact['uid']));
		if (count($r)) {
			$datarray['contact-id'] = $r[0]["id"];

			$datarray['owner-name'] = $r[0]["name"];
			$datarray['owner-link'] = $r[0]["url"];
			$datarray['owner-avatar'] = $r[0]["thumb"];

			$datarray['author-name']   = $datarray['owner-name'];
			$datarray['author-link']   = $datarray['owner-link'];
			$datarray['author-avatar'] = $datarray['owner-avatar'];
		}

		if ($contact['network'] != NETWORK_FEED) {
			$datarray["guid"] = get_guid(32);
			unset($datarray["plink"]);
			$datarray["uri"] = item_new_uri($a->get_hostname(),$contact['uid'], $datarray["guid"]);
			$datarray["parent-uri"] = $datarray["uri"];
			$datarray["extid"] = $contact['network'];
			$urlpart = parse_url($datarray2['author-link']);
			$datarray["app"] = $urlpart["host"];
		} else
			$datarray['private'] = 0;
	}

	if ($contact['network'] != NETWORK_FEED) {
		// Store the original post
		$r = item_store($datarray2, false, false);
		logger('remote-self post original item - Contact '.$contact['url'].' return '.$r.' Item '.print_r($datarray2, true), LOGGER_DEBUG);
	} else
		$datarray["app"] = "Feed";

	return true;
}

function local_delivery($importer,$data) {
	// dfrn-Test
	//return dfrn2::import($data, $importer);

	require_once('library/simplepie/simplepie.inc');

	$a = get_app();

	logger(__function__, LOGGER_TRACE);

	//$tempfile = tempnam(get_temppath(), "dfrn-local-");
	//file_put_contents($tempfile, $data);

	if($importer['readonly']) {
		// We aren't receiving stuff from this person. But we will quietly ignore them
		// rather than a blatant "go away" message.
		logger('local_delivery: ignoring');
		return 0;
		//NOTREACHED
	}

	// Consume notification feed. This may differ from consuming a public feed in several ways
	// - might contain email or friend suggestions
	// - might contain remote followup to our message
	//		- in which case we need to accept it and then notify other conversants
	// - we may need to send various email notifications

	$feed = new SimplePie();
	$feed->set_raw_data($data);
	$feed->enable_order_by_date(false);
	$feed->init();


	if($feed->error())
		logger('local_delivery: Error parsing XML: ' . $feed->error());


	// Check at the feed level for updated contact name and/or photo

	$name_updated  = '';
	$new_name = '';
	$photo_timestamp = '';
	$photo_url = '';
	$contact_updated = '';


	$rawtags = $feed->get_feed_tags( NAMESPACE_DFRN, 'owner');

// Fallback should not be needed here. If it isn't DFRN it won't have DFRN updated tags
//	if(! $rawtags)
//		$rawtags = $feed->get_feed_tags( SIMPLEPIE_NAMESPACE_ATOM_10, 'author');

	if($rawtags) {
		$elems = $rawtags[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10];
		if($elems['name'][0]['attribs'][NAMESPACE_DFRN]['updated']) {
			$name_updated = $elems['name'][0]['attribs'][NAMESPACE_DFRN]['updated'];
			$new_name = $elems['name'][0]['data'];

			// Manually checking for changed contact names
			if (($new_name != $importer['name']) AND ($new_name != "") AND ($name_updated <= $importer['name-date'])) {
				$name_updated = date("c");
				$photo_timestamp = date("c");
			}
		}
		if((x($elems,'link')) && ($elems['link'][0]['attribs']['']['rel'] === 'photo') && ($elems['link'][0]['attribs'][NAMESPACE_DFRN]['updated'])) {
			if ($photo_timestamp == "")
				$photo_timestamp = datetime_convert('UTC','UTC',$elems['link'][0]['attribs'][NAMESPACE_DFRN]['updated']);
			$photo_url = $elems['link'][0]['attribs']['']['href'];
		}
	}

	if(($photo_timestamp) && (strlen($photo_url)) && ($photo_timestamp > $importer['avatar-date'])) {

		$contact_updated = $photo_timestamp;

		logger('local_delivery: Updating photo for ' . $importer['name']);
		require_once("include/Photo.php");

		$photos = import_profile_photo($photo_url,$importer['importer_uid'],$importer['id']);

		q("UPDATE `contact` SET `avatar-date` = '%s', `photo` = '%s', `thumb` = '%s', `micro` = '%s'
			WHERE `uid` = %d AND `id` = %d AND NOT `self`",
			dbesc(datetime_convert()),
			dbesc($photos[0]),
			dbesc($photos[1]),
			dbesc($photos[2]),
			intval($importer['importer_uid']),
			intval($importer['id'])
		);
	}

	if(($name_updated) && (strlen($new_name)) && ($name_updated > $importer['name-date'])) {
		if ($name_updated > $contact_updated)
			$contact_updated = $name_updated;

		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `id` = %d LIMIT 1",
			intval($importer['importer_uid']),
			intval($importer['id'])
		);

		$x = q("UPDATE `contact` SET `name` = '%s', `name-date` = '%s' WHERE `uid` = %d AND `id` = %d AND `name` != '%s' AND NOT `self`",
			dbesc(notags(trim($new_name))),
			dbesc(datetime_convert()),
			intval($importer['importer_uid']),
			intval($importer['id']),
			dbesc(notags(trim($new_name)))
		);

		// do our best to update the name on content items

		if(count($r) AND (notags(trim($new_name)) != $r[0]['name'])) {
			q("UPDATE `item` SET `author-name` = '%s' WHERE `author-name` = '%s' AND `author-link` = '%s' AND `uid` = %d AND `author-name` != '%s'",
				dbesc(notags(trim($new_name))),
				dbesc($r[0]['name']),
				dbesc($r[0]['url']),
				intval($importer['importer_uid']),
				dbesc(notags(trim($new_name)))
			);
		}
	}

	if ($contact_updated AND $new_name AND $photo_url)
		poco_check($importer['url'], $new_name, NETWORK_DFRN, $photo_url, "", "", "", "", "", $contact_updated, 2, $importer['id'], $importer['importer_uid']);

	// Currently unsupported - needs a lot of work
	$reloc = $feed->get_feed_tags( NAMESPACE_DFRN, 'relocate' );
	if(isset($reloc[0]['child'][NAMESPACE_DFRN])) {
		$base = $reloc[0]['child'][NAMESPACE_DFRN];
		$newloc = array();
		$newloc['uid'] = $importer['importer_uid'];
		$newloc['cid'] = $importer['id'];
		$newloc['name'] = notags(unxmlify($base['name'][0]['data']));
		$newloc['photo'] = notags(unxmlify($base['photo'][0]['data']));
		$newloc['thumb'] = notags(unxmlify($base['thumb'][0]['data']));
		$newloc['micro'] = notags(unxmlify($base['micro'][0]['data']));
		$newloc['url'] = notags(unxmlify($base['url'][0]['data']));
		$newloc['request'] = notags(unxmlify($base['request'][0]['data']));
		$newloc['confirm'] = notags(unxmlify($base['confirm'][0]['data']));
		$newloc['notify'] = notags(unxmlify($base['notify'][0]['data']));
		$newloc['poll'] = notags(unxmlify($base['poll'][0]['data']));
		$newloc['sitepubkey'] = notags(unxmlify($base['sitepubkey'][0]['data']));
		/** relocated user must have original key pair */
		/*$newloc['pubkey'] = notags(unxmlify($base['pubkey'][0]['data']));
		$newloc['prvkey'] = notags(unxmlify($base['prvkey'][0]['data']));*/

		logger("items:relocate contact ".print_r($newloc, true).print_r($importer, true), LOGGER_DEBUG);

		// update contact
		$r = q("SELECT photo, url FROM contact WHERE id=%d AND uid=%d;",
			intval($importer['id']),
			intval($importer['importer_uid']));
		if ($r === false)
			return 1;
		$old = $r[0];

		$x = q("UPDATE contact SET
					name = '%s',
					photo = '%s',
					thumb = '%s',
					micro = '%s',
					url = '%s',
					nurl = '%s',
					request = '%s',
					confirm = '%s',
					notify = '%s',
					poll = '%s',
					`site-pubkey` = '%s'
			WHERE id=%d AND uid=%d;",
					dbesc($newloc['name']),
					dbesc($newloc['photo']),
					dbesc($newloc['thumb']),
					dbesc($newloc['micro']),
					dbesc($newloc['url']),
					dbesc(normalise_link($newloc['url'])),
					dbesc($newloc['request']),
					dbesc($newloc['confirm']),
					dbesc($newloc['notify']),
					dbesc($newloc['poll']),
					dbesc($newloc['sitepubkey']),
					intval($importer['id']),
					intval($importer['importer_uid']));

		if ($x === false)
			return 1;
		// update items
		$fields = array(
			'owner-link' => array($old['url'], $newloc['url']),
			'author-link' => array($old['url'], $newloc['url']),
			'owner-avatar' => array($old['photo'], $newloc['photo']),
			'author-avatar' => array($old['photo'], $newloc['photo']),
			);
		foreach ($fields as $n=>$f){
			$x = q("UPDATE `item` SET `%s`='%s' WHERE `%s`='%s' AND uid=%d",
					$n, dbesc($f[1]),
					$n, dbesc($f[0]),
					intval($importer['importer_uid']));
				if ($x === false)
					return 1;
			}

		/// @TODO
		/// merge with current record, current contents have priority
		/// update record, set url-updated
		/// update profile photos
		/// schedule a scan?
		return 0;
	}


	// handle friend suggestion notification

	$sugg = $feed->get_feed_tags( NAMESPACE_DFRN, 'suggest' );
	if(isset($sugg[0]['child'][NAMESPACE_DFRN])) {
		$base = $sugg[0]['child'][NAMESPACE_DFRN];
		$fsugg = array();
		$fsugg['uid'] = $importer['importer_uid'];
		$fsugg['cid'] = $importer['id'];
		$fsugg['name'] = notags(unxmlify($base['name'][0]['data']));
		$fsugg['photo'] = notags(unxmlify($base['photo'][0]['data']));
		$fsugg['url'] = notags(unxmlify($base['url'][0]['data']));
		$fsugg['request'] = notags(unxmlify($base['request'][0]['data']));
		$fsugg['body'] = escape_tags(unxmlify($base['note'][0]['data']));

		// Does our member already have a friend matching this description?

		$r = q("SELECT * FROM `contact` WHERE `name` = '%s' AND `nurl` = '%s' AND `uid` = %d LIMIT 1",
			dbesc($fsugg['name']),
			dbesc(normalise_link($fsugg['url'])),
			intval($fsugg['uid'])
		);
		if(count($r))
			return 0;

		// Do we already have an fcontact record for this person?

		$fid = 0;
		$r = q("SELECT * FROM `fcontact` WHERE `url` = '%s' AND `name` = '%s' AND `request` = '%s' LIMIT 1",
			dbesc($fsugg['url']),
			dbesc($fsugg['name']),
			dbesc($fsugg['request'])
		);
		if(count($r)) {
			$fid = $r[0]['id'];

			// OK, we do. Do we already have an introduction for this person ?
			$r = q("select id from intro where uid = %d and fid = %d limit 1",
				intval($fsugg['uid']),
				intval($fid)
			);
			if(count($r))
				return 0;
		}
		if(! $fid)
			$r = q("INSERT INTO `fcontact` ( `name`,`url`,`photo`,`request` ) VALUES ( '%s', '%s', '%s', '%s' ) ",
			dbesc($fsugg['name']),
			dbesc($fsugg['url']),
			dbesc($fsugg['photo']),
			dbesc($fsugg['request'])
		);
		$r = q("SELECT * FROM `fcontact` WHERE `url` = '%s' AND `name` = '%s' AND `request` = '%s' LIMIT 1",
			dbesc($fsugg['url']),
			dbesc($fsugg['name']),
			dbesc($fsugg['request'])
		);
		if(count($r)) {
			$fid = $r[0]['id'];
		}
		// database record did not get created. Quietly give up.
		else
			return 0;


		$hash = random_string();

		$r = q("INSERT INTO `intro` ( `uid`, `fid`, `contact-id`, `note`, `hash`, `datetime`, `blocked` )
			VALUES( %d, %d, %d, '%s', '%s', '%s', %d )",
			intval($fsugg['uid']),
			intval($fid),
			intval($fsugg['cid']),
			dbesc($fsugg['body']),
			dbesc($hash),
			dbesc(datetime_convert()),
			intval(0)
		);

		notification(array(
			'type'         => NOTIFY_SUGGEST,
			'notify_flags' => $importer['notify-flags'],
			'language'     => $importer['language'],
			'to_name'      => $importer['username'],
			'to_email'     => $importer['email'],
			'uid'          => $importer['importer_uid'],
			'item'         => $fsugg,
			'link'         => $a->get_baseurl() . '/notifications/intros',
			'source_name'  => $importer['name'],
			'source_link'  => $importer['url'],
			'source_photo' => $importer['photo'],
			'verb'         => ACTIVITY_REQ_FRIEND,
			'otype'        => 'intro'
		));

		return 0;
	}

	$ismail = false;

	$rawmail = $feed->get_feed_tags( NAMESPACE_DFRN, 'mail' );
	if(isset($rawmail[0]['child'][NAMESPACE_DFRN])) {

		logger('local_delivery: private message received');

		$ismail = true;
		$base = $rawmail[0]['child'][NAMESPACE_DFRN];

		$msg = array();
		$msg['uid'] = $importer['importer_uid'];
		$msg['from-name'] = notags(unxmlify($base['sender'][0]['child'][NAMESPACE_DFRN]['name'][0]['data']));
		$msg['from-photo'] = notags(unxmlify($base['sender'][0]['child'][NAMESPACE_DFRN]['avatar'][0]['data']));
		$msg['from-url'] = notags(unxmlify($base['sender'][0]['child'][NAMESPACE_DFRN]['uri'][0]['data']));
		$msg['contact-id'] = $importer['id'];
		$msg['title'] = notags(unxmlify($base['subject'][0]['data']));
		$msg['body'] = escape_tags(unxmlify($base['content'][0]['data']));
		$msg['seen'] = 0;
		$msg['replied'] = 0;
		$msg['uri'] = notags(unxmlify($base['id'][0]['data']));
		$msg['parent-uri'] = notags(unxmlify($base['in-reply-to'][0]['data']));
		$msg['created'] = datetime_convert(notags(unxmlify('UTC','UTC',$base['sentdate'][0]['data'])));

		dbesc_array($msg);

		$r = dbq("INSERT INTO `mail` (`" . implode("`, `", array_keys($msg))
			. "`) VALUES ('" . implode("', '", array_values($msg)) . "')" );

		// send notifications.

		require_once('include/enotify.php');

		$notif_params = array(
			'type' => NOTIFY_MAIL,
			'notify_flags' => $importer['notify-flags'],
			'language' => $importer['language'],
			'to_name' => $importer['username'],
			'to_email' => $importer['email'],
			'uid' => $importer['importer_uid'],
			'item' => $msg,
			'source_name' => $msg['from-name'],
			'source_link' => $importer['url'],
			'source_photo' => $importer['thumb'],
			'verb' => ACTIVITY_POST,
			'otype' => 'mail'
		);

		notification($notif_params);
		return 0;

		// NOTREACHED
	}

	$community_page = 0;
	$rawtags = $feed->get_feed_tags( NAMESPACE_DFRN, 'community');
	if($rawtags) {
		$community_page = intval($rawtags[0]['data']);
	}
	if(intval($importer['forum']) != $community_page) {
		q("update contact set forum = %d where id = %d",
			intval($community_page),
			intval($importer['id'])
		);
		$importer['forum'] = (string) $community_page;
	}

	logger('local_delivery: feed item count = ' . $feed->get_item_quantity());

	// process any deleted entries

	$del_entries = $feed->get_feed_tags(NAMESPACE_TOMB, 'deleted-entry');
	if(is_array($del_entries) && count($del_entries)) {
		foreach($del_entries as $dentry) {
			$deleted = false;
			if(isset($dentry['attribs']['']['ref'])) {
				$uri = $dentry['attribs']['']['ref'];
				$deleted = true;
				if(isset($dentry['attribs']['']['when'])) {
					$when = $dentry['attribs']['']['when'];
					$when = datetime_convert('UTC','UTC', $when, 'Y-m-d H:i:s');
				}
				else
					$when = datetime_convert('UTC','UTC','now','Y-m-d H:i:s');
			}
			if($deleted) {

				// check for relayed deletes to our conversation

				$is_reply = false;
				$r = q("select * from item where uri = '%s' and uid = %d limit 1",
					dbesc($uri),
					intval($importer['importer_uid'])
				);
				if(count($r)) {
					$parent_uri = $r[0]['parent-uri'];
					if($r[0]['id'] != $r[0]['parent'])
						$is_reply = true;
				}

				if($is_reply) {
					$community = false;

					if($importer['page-flags'] == PAGE_COMMUNITY || $importer['page-flags'] == PAGE_PRVGROUP ) {
						$sql_extra = '';
						$community = true;
						logger('local_delivery: possible community delete');
					}
					else
						$sql_extra = " and contact.self = 1 and item.wall = 1 ";

					// was the top-level post for this reply written by somebody on this site?
					// Specifically, the recipient?

					$is_a_remote_delete = false;

					// POSSIBLE CLEANUP --> Why select so many fields when only forum_mode and wall are used?
					$r = q("select `item`.`id`, `item`.`uri`, `item`.`tag`, `item`.`forum_mode`,`item`.`origin`,`item`.`wall`,
						`contact`.`name`, `contact`.`url`, `contact`.`thumb` from `item`
						INNER JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
						WHERE `item`.`uri` = '%s' AND (`item`.`parent-uri` = '%s' or `item`.`thr-parent` = '%s')
						AND `item`.`uid` = %d
						$sql_extra
						LIMIT 1",
						dbesc($parent_uri),
						dbesc($parent_uri),
						dbesc($parent_uri),
						intval($importer['importer_uid'])
					);
					if($r && count($r))
						$is_a_remote_delete = true;

					// Does this have the characteristics of a community or private group comment?
					// If it's a reply to a wall post on a community/prvgroup page it's a
					// valid community comment. Also forum_mode makes it valid for sure.
					// If neither, it's not.

					if($is_a_remote_delete && $community) {
						if((! $r[0]['forum_mode']) && (! $r[0]['wall'])) {
							$is_a_remote_delete = false;
							logger('local_delivery: not a community delete');
						}
					}

					if($is_a_remote_delete) {
						logger('local_delivery: received remote delete');
					}
				}

				$r = q("SELECT `item`.*, `contact`.`self` FROM `item` INNER JOIN contact on `item`.`contact-id` = `contact`.`id`
					WHERE `uri` = '%s' AND `item`.`uid` = %d AND `contact-id` = %d AND NOT `item`.`file` LIKE '%%[%%' LIMIT 1",
					dbesc($uri),
					intval($importer['importer_uid']),
					intval($importer['id'])
				);

				if(count($r)) {
					$item = $r[0];

					if($item['deleted'])
						continue;

					logger('local_delivery: deleting item ' . $item['id'] . ' uri=' . $item['uri'], LOGGER_DEBUG);

					if($item['object-type'] === ACTIVITY_OBJ_EVENT) {
						logger("Deleting event ".$item['event-id'], LOGGER_DEBUG);
						event_delete($item['event-id']);
					}

					if(($item['verb'] === ACTIVITY_TAG) && ($item['object-type'] === ACTIVITY_OBJ_TAGTERM)) {
						$xo = parse_xml_string($item['object'],false);
						$xt = parse_xml_string($item['target'],false);

						if($xt->type === ACTIVITY_OBJ_NOTE) {
							$i = q("select * from `item` where uri = '%s' and uid = %d limit 1",
								dbesc($xt->id),
								intval($importer['importer_uid'])
							);
							if(count($i)) {

								// For tags, the owner cannot remove the tag on the author's copy of the post.

								$owner_remove = (($item['contact-id'] == $i[0]['contact-id']) ? true: false);
								$author_remove = (($item['origin'] && $item['self']) ? true : false);
								$author_copy = (($item['origin']) ? true : false);

								if($owner_remove && $author_copy)
									continue;
								if($author_remove || $owner_remove) {
									$tags = explode(',',$i[0]['tag']);
									$newtags = array();
									if(count($tags)) {
										foreach($tags as $tag)
											if(trim($tag) !== trim($xo->body))
												$newtags[] = trim($tag);
									}
									q("update item set tag = '%s' where id = %d",
										dbesc(implode(',',$newtags)),
										intval($i[0]['id'])
									);
									create_tags_from_item($i[0]['id']);
								}
							}
						}
					}

					if($item['uri'] == $item['parent-uri']) {
						$r = q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s',
							`body` = '', `title` = ''
							WHERE `parent-uri` = '%s' AND `uid` = %d",
							dbesc($when),
							dbesc(datetime_convert()),
							dbesc($item['uri']),
							intval($importer['importer_uid'])
						);
						create_tags_from_itemuri($item['uri'], $importer['importer_uid']);
						create_files_from_itemuri($item['uri'], $importer['importer_uid']);
						update_thread_uri($item['uri'], $importer['importer_uid']);
					}
					else {
						$r = q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s',
							`body` = '', `title` = ''
							WHERE `uri` = '%s' AND `uid` = %d",
							dbesc($when),
							dbesc(datetime_convert()),
							dbesc($uri),
							intval($importer['importer_uid'])
						);
						create_tags_from_itemuri($uri, $importer['importer_uid']);
						create_files_from_itemuri($uri, $importer['importer_uid']);
						update_thread_uri($uri, $importer['importer_uid']);
						if($item['last-child']) {
							// ensure that last-child is set in case the comment that had it just got wiped.
							q("UPDATE `item` SET `last-child` = 0, `changed` = '%s' WHERE `parent-uri` = '%s' AND `uid` = %d ",
								dbesc(datetime_convert()),
								dbesc($item['parent-uri']),
								intval($item['uid'])
							);
							// who is the last child now?
							$r = q("SELECT `id` FROM `item` WHERE `parent-uri` = '%s' AND `type` != 'activity' AND `deleted` = 0 AND `uid` = %d
								ORDER BY `created` DESC LIMIT 1",
									dbesc($item['parent-uri']),
									intval($importer['importer_uid'])
							);
							if(count($r)) {
								q("UPDATE `item` SET `last-child` = 1 WHERE `id` = %d",
									intval($r[0]['id'])
								);
							}
						}
						// if this is a relayed delete, propagate it to other recipients

						if($is_a_remote_delete)
							proc_run('php',"include/notifier.php","drop",$item['id']);
					}
				}
			}
		}
	}


	foreach($feed->get_items() as $item) {

		$is_reply = false;
		$item_id = $item->get_id();
		$rawthread = $item->get_item_tags( NAMESPACE_THREAD, 'in-reply-to');
		if(isset($rawthread[0]['attribs']['']['ref'])) {
			$is_reply = true;
			$parent_uri = $rawthread[0]['attribs']['']['ref'];
		}

		if($is_reply) {
			$community = false;

			if($importer['page-flags'] == PAGE_COMMUNITY || $importer['page-flags'] == PAGE_PRVGROUP ) {
				$sql_extra = '';
				$community = true;
				logger('local_delivery: possible community reply');
			}
			else
				$sql_extra = " and contact.self = 1 and item.wall = 1 ";

			// was the top-level post for this reply written by somebody on this site?
			// Specifically, the recipient?

			$is_a_remote_comment = false;
			$top_uri = $parent_uri;

			$r = q("select `item`.`parent-uri` from `item`
				WHERE `item`.`uri` = '%s'
				LIMIT 1",
				dbesc($parent_uri)
			);
			if($r && count($r)) {
				$top_uri = $r[0]['parent-uri'];

				// POSSIBLE CLEANUP --> Why select so many fields when only forum_mode and wall are used?
				$r = q("select `item`.`id`, `item`.`uri`, `item`.`tag`, `item`.`forum_mode`,`item`.`origin`,`item`.`wall`,
					`contact`.`name`, `contact`.`url`, `contact`.`thumb` from `item`
					INNER JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
					WHERE `item`.`uri` = '%s' AND (`item`.`parent-uri` = '%s' or `item`.`thr-parent` = '%s')
					AND `item`.`uid` = %d
					$sql_extra
					LIMIT 1",
					dbesc($top_uri),
					dbesc($top_uri),
					dbesc($top_uri),
					intval($importer['importer_uid'])
				);
				if($r && count($r))
					$is_a_remote_comment = true;
			}

			// Does this have the characteristics of a community or private group comment?
			// If it's a reply to a wall post on a community/prvgroup page it's a
			// valid community comment. Also forum_mode makes it valid for sure.
			// If neither, it's not.

			if($is_a_remote_comment && $community) {
				if((! $r[0]['forum_mode']) && (! $r[0]['wall'])) {
					$is_a_remote_comment = false;
					logger('local_delivery: not a community reply');
				}
			}

			if($is_a_remote_comment) {
				logger('local_delivery: received remote comment');
				$is_like = false;
				// remote reply to our post. Import and then notify everybody else.

				$datarray = get_atom_elements($feed, $item);

				$r = q("SELECT `id`, `uid`, `last-child`, `edited`, `body`  FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
					dbesc($item_id),
					intval($importer['importer_uid'])
				);

				// Update content if 'updated' changes

				if(count($r)) {
					$iid = $r[0]['id'];
					if (edited_timestamp_is_newer($r[0], $datarray)) {

						// do not accept (ignore) an earlier edit than one we currently have.
						if(datetime_convert('UTC','UTC',$datarray['edited']) < $r[0]['edited'])
							continue;

						logger('received updated comment' , LOGGER_DEBUG);
						$r = q("UPDATE `item` SET `title` = '%s', `body` = '%s', `tag` = '%s', `edited` = '%s', `changed` = '%s' WHERE `uri` = '%s' AND `uid` = %d",
							dbesc($datarray['title']),
							dbesc($datarray['body']),
							dbesc($datarray['tag']),
							dbesc(datetime_convert('UTC','UTC',$datarray['edited'])),
							dbesc(datetime_convert()),
							dbesc($item_id),
							intval($importer['importer_uid'])
						);
						create_tags_from_itemuri($item_id, $importer['importer_uid']);

						proc_run('php',"include/notifier.php","comment-import",$iid);

					}

					continue;
				}



				$own = q("select name,url,thumb from contact where uid = %d and self = 1 limit 1",
					intval($importer['importer_uid'])
				);


				$datarray['type'] = 'remote-comment';
				$datarray['wall'] = 1;
				$datarray['parent-uri'] = $parent_uri;
				$datarray['uid'] = $importer['importer_uid'];
				$datarray['owner-name'] = $own[0]['name'];
				$datarray['owner-link'] = $own[0]['url'];
				$datarray['owner-avatar'] = $own[0]['thumb'];
				$datarray['contact-id'] = $importer['id'];

				if(($datarray['verb'] === ACTIVITY_LIKE)
					|| ($datarray['verb'] === ACTIVITY_DISLIKE)
					|| ($datarray['verb'] === ACTIVITY_ATTEND)
					|| ($datarray['verb'] === ACTIVITY_ATTENDNO)
					|| ($datarray['verb'] === ACTIVITY_ATTENDMAYBE)) {
					$is_like = true;
					$datarray['type'] = 'activity';
					$datarray['gravity'] = GRAVITY_LIKE;
					$datarray['last-child'] = 0;
					// only one like or dislike per person
					// splitted into two queries for performance issues
					$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `author-link` = '%s' AND `verb` = '%s' AND `parent-uri` = '%s' AND NOT `deleted` LIMIT 1",
						intval($datarray['uid']),
						dbesc($datarray['author-link']),
						dbesc($datarray['verb']),
						dbesc($datarray['parent-uri'])
					);
					if($r && count($r))
						continue;

					$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `author-link` = '%s' AND `verb` = '%s' AND `thr-parent` = '%s' AND NOT `deleted` LIMIT 1",
						intval($datarray['uid']),
						dbesc($datarray['author-link']),
						dbesc($datarray['verb']),
						dbesc($datarray['parent-uri'])

					);
					if($r && count($r))
						continue;
				}

				if(($datarray['verb'] === ACTIVITY_TAG) && ($datarray['object-type'] === ACTIVITY_OBJ_TAGTERM)) {

					$xo = parse_xml_string($datarray['object'],false);
					$xt = parse_xml_string($datarray['target'],false);

					if(($xt->type == ACTIVITY_OBJ_NOTE) && ($xt->id)) {

						// fetch the parent item

						$tagp = q("select * from item where uri = '%s' and uid = %d limit 1",
							dbesc($xt->id),
							intval($importer['importer_uid'])
						);
						if(! count($tagp))
							continue;

						// extract tag, if not duplicate, and this user allows tags, add to parent item

						if($xo->id && $xo->content) {
							$newtag = '#[url=' . $xo->id . ']'. $xo->content . '[/url]';
							if(! (stristr($tagp[0]['tag'],$newtag))) {
								$i = q("SELECT `blocktags` FROM `user` where `uid` = %d LIMIT 1",
									intval($importer['importer_uid'])
								);
								if(count($i) && ! intval($i[0]['blocktags'])) {
									q("UPDATE item SET tag = '%s', `edited` = '%s', `changed` = '%s' WHERE id = %d",
										dbesc($tagp[0]['tag'] . (strlen($tagp[0]['tag']) ? ',' : '') . $newtag),
										intval($tagp[0]['id']),
										dbesc(datetime_convert()),
										dbesc(datetime_convert())
									);
									create_tags_from_item($tagp[0]['id']);
								}
							}
						}
					}
				}


				$posted_id = item_store($datarray);
				$parent = 0;

				if($posted_id) {

					$datarray["id"] = $posted_id;

					$r = q("SELECT `parent`, `parent-uri` FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1",
						intval($posted_id),
						intval($importer['importer_uid'])
					);
					if(count($r)) {
						$parent = $r[0]['parent'];
						$parent_uri = $r[0]['parent-uri'];
					}

					if(! $is_like) {
						$r1 = q("UPDATE `item` SET `last-child` = 0, `changed` = '%s' WHERE `uid` = %d AND `parent` = %d",
							dbesc(datetime_convert()),
							intval($importer['importer_uid']),
							intval($r[0]['parent'])
						);

						$r2 = q("UPDATE `item` SET `last-child` = 1, `changed` = '%s' WHERE `uid` = %d AND `id` = %d",
							dbesc(datetime_convert()),
							intval($importer['importer_uid']),
							intval($posted_id)
						);
					}

					if($posted_id && $parent) {
						proc_run('php',"include/notifier.php","comment-import","$posted_id");
					}

					return 0;
					// NOTREACHED
				}
			}
			else {

				// regular comment that is part of this total conversation. Have we seen it? If not, import it.

				$item_id  = $item->get_id();
				$datarray = get_atom_elements($feed,$item);

				if($importer['rel'] == CONTACT_IS_FOLLOWER)
					continue;

				$r = q("SELECT `uid`, `last-child`, `edited`, `body` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
					dbesc($item_id),
					intval($importer['importer_uid'])
				);

				// Update content if 'updated' changes

				if(count($r)) {
					if (edited_timestamp_is_newer($r[0], $datarray)) {

						// do not accept (ignore) an earlier edit than one we currently have.
						if(datetime_convert('UTC','UTC',$datarray['edited']) < $r[0]['edited'])
							continue;

						$r = q("UPDATE `item` SET `title` = '%s', `body` = '%s', `tag` = '%s', `edited` = '%s', `changed` = '%s' WHERE `uri` = '%s' AND `uid` = %d",
							dbesc($datarray['title']),
							dbesc($datarray['body']),
							dbesc($datarray['tag']),
							dbesc(datetime_convert('UTC','UTC',$datarray['edited'])),
							dbesc(datetime_convert()),
							dbesc($item_id),
							intval($importer['importer_uid'])
						);
						create_tags_from_itemuri($item_id, $importer['importer_uid']);
					}

					// update last-child if it changes

					$allow = $item->get_item_tags( NAMESPACE_DFRN, 'comment-allow');
					if(($allow) && ($allow[0]['data'] != $r[0]['last-child'])) {
						$r = q("UPDATE `item` SET `last-child` = 0, `changed` = '%s' WHERE `parent-uri` = '%s' AND `uid` = %d",
							dbesc(datetime_convert()),
							dbesc($parent_uri),
							intval($importer['importer_uid'])
						);
						$r = q("UPDATE `item` SET `last-child` = %d , `changed` = '%s'  WHERE `uri` = '%s' AND `uid` = %d",
							intval($allow[0]['data']),
							dbesc(datetime_convert()),
							dbesc($item_id),
							intval($importer['importer_uid'])
						);
					}
					continue;
				}

				$datarray['parent-uri'] = $parent_uri;
				$datarray['uid'] = $importer['importer_uid'];
				$datarray['contact-id'] = $importer['id'];
				if(($datarray['verb'] === ACTIVITY_LIKE)
					|| ($datarray['verb'] === ACTIVITY_DISLIKE)
					|| ($datarray['verb'] === ACTIVITY_ATTEND)
					|| ($datarray['verb'] === ACTIVITY_ATTENDNO)
					|| ($datarray['verb'] === ACTIVITY_ATTENDMAYBE)) {
					$datarray['type'] = 'activity';
					$datarray['gravity'] = GRAVITY_LIKE;
					// only one like or dislike per person
					// splitted into two queries for performance issues
					$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `author-link` = '%s' AND `verb` = '%s' AND `parent-uri` = '%s' AND NOT `deleted` LIMIT 1",
						intval($datarray['uid']),
						dbesc($datarray['author-link']),
						dbesc($datarray['verb']),
						dbesc($datarray['parent-uri'])
					);
					if($r && count($r))
						continue;

					$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `author-link` = '%s' AND `verb` = '%s' AND `thr-parent` = '%s' AND NOT `deleted` LIMIT 1",
						intval($datarray['uid']),
						dbesc($datarray['author-link']),
						dbesc($datarray['verb']),
						dbesc($datarray['parent-uri'])
					);
					if($r && count($r))
						continue;

				}

				if(($datarray['verb'] === ACTIVITY_TAG) && ($datarray['object-type'] === ACTIVITY_OBJ_TAGTERM)) {

					$xo = parse_xml_string($datarray['object'],false);
					$xt = parse_xml_string($datarray['target'],false);

					if($xt->type == ACTIVITY_OBJ_NOTE) {
						$r = q("select * from item where `uri` = '%s' AND `uid` = %d limit 1",
							dbesc($xt->id),
							intval($importer['importer_uid'])
						);
						if(! count($r))
							continue;

						// extract tag, if not duplicate, add to parent item
						if($xo->content) {
							if(! (stristr($r[0]['tag'],trim($xo->content)))) {
								q("UPDATE item SET tag = '%s' WHERE id = %d",
									dbesc($r[0]['tag'] . (strlen($r[0]['tag']) ? ',' : '') . '#[url=' . $xo->id . ']'. $xo->content . '[/url]'),
									intval($r[0]['id'])
								);
								create_tags_from_item($r[0]['id']);
							}
						}
					}
				}

				$posted_id = item_store($datarray);

				continue;
			}
		}

		else {

			// Head post of a conversation. Have we seen it? If not, import it.


			$item_id  = $item->get_id();
			$datarray = get_atom_elements($feed,$item);

			if((x($datarray,'object-type')) && ($datarray['object-type'] === ACTIVITY_OBJ_EVENT)) {
				$ev = bbtoevent($datarray['body']);
				if((x($ev,'desc') || x($ev,'summary')) && x($ev,'start')) {
					$ev['cid'] = $importer['id'];
					$ev['uid'] = $importer['uid'];
					$ev['uri'] = $item_id;
					$ev['edited'] = $datarray['edited'];
					$ev['private'] = $datarray['private'];
					$ev['guid'] = $datarray['guid'];

					$r = q("SELECT * FROM `event` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
						dbesc($item_id),
						intval($importer['uid'])
					);
					if(count($r))
						$ev['id'] = $r[0]['id'];
					$xyz = event_store($ev);
					continue;
				}
			}

			$r = q("SELECT `uid`, `last-child`, `edited`, `body` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
				dbesc($item_id),
				intval($importer['importer_uid'])
			);

			// Update content if 'updated' changes

			if(count($r)) {
				if (edited_timestamp_is_newer($r[0], $datarray)) {

					// do not accept (ignore) an earlier edit than one we currently have.
					if(datetime_convert('UTC','UTC',$datarray['edited']) < $r[0]['edited'])
						continue;

					$r = q("UPDATE `item` SET `title` = '%s', `body` = '%s', `tag` = '%s', `edited` = '%s', `changed` = '%s' WHERE `uri` = '%s' AND `uid` = %d",
						dbesc($datarray['title']),
						dbesc($datarray['body']),
						dbesc($datarray['tag']),
						dbesc(datetime_convert('UTC','UTC',$datarray['edited'])),
						dbesc(datetime_convert()),
						dbesc($item_id),
						intval($importer['importer_uid'])
					);
					create_tags_from_itemuri($item_id, $importer['importer_uid']);
					update_thread_uri($item_id, $importer['importer_uid']);
				}

				// update last-child if it changes

				$allow = $item->get_item_tags( NAMESPACE_DFRN, 'comment-allow');
				if($allow && $allow[0]['data'] != $r[0]['last-child']) {
					$r = q("UPDATE `item` SET `last-child` = %d , `changed` = '%s' WHERE `uri` = '%s' AND `uid` = %d",
						intval($allow[0]['data']),
						dbesc(datetime_convert()),
						dbesc($item_id),
						intval($importer['importer_uid'])
					);
				}
				continue;
			}

			$datarray['parent-uri'] = $item_id;
			$datarray['uid'] = $importer['importer_uid'];
			$datarray['contact-id'] = $importer['id'];


			if(! link_compare($datarray['owner-link'],$importer['url'])) {
				// The item owner info is not our contact. It's OK and is to be expected if this is a tgroup delivery,
				// but otherwise there's a possible data mixup on the sender's system.
				// the tgroup delivery code called from item_store will correct it if it's a forum,
				// but we're going to unconditionally correct it here so that the post will always be owned by our contact.
				logger('local_delivery: Correcting item owner.', LOGGER_DEBUG);
				$datarray['owner-name']   = $importer['senderName'];
				$datarray['owner-link']   = $importer['url'];
				$datarray['owner-avatar'] = $importer['thumb'];
			}

			if(($importer['rel'] == CONTACT_IS_FOLLOWER) && (! tgroup_check($importer['importer_uid'],$datarray)))
				continue;

			// This is my contact on another system, but it's really me.
			// Turn this into a wall post.
			$notify = item_is_remote_self($importer, $datarray);

			$posted_id = item_store($datarray, false, $notify);

			if(stristr($datarray['verb'],ACTIVITY_POKE)) {
				$verb = urldecode(substr($datarray['verb'],strpos($datarray['verb'],'#')+1));
				if(! $verb)
					continue;
				$xo = parse_xml_string($datarray['object'],false);

				if(($xo->type == ACTIVITY_OBJ_PERSON) && ($xo->id)) {

					// somebody was poked/prodded. Was it me?

					$links = parse_xml_string("<links>".unxmlify($xo->link)."</links>",false);

				foreach($links->link as $l) {
				$atts = $l->attributes();
				switch($atts['rel']) {
					case "alternate":
								$Blink = $atts['href'];
								break;
							default:
								break;
				    }
				}
					if($Blink && link_compare($Blink,$a->get_baseurl() . '/profile/' . $importer['nickname'])) {

						// send a notification
						require_once('include/enotify.php');

						notification(array(
							'type'         => NOTIFY_POKE,
							'notify_flags' => $importer['notify-flags'],
							'language'     => $importer['language'],
							'to_name'      => $importer['username'],
							'to_email'     => $importer['email'],
							'uid'          => $importer['importer_uid'],
							'item'         => $datarray,
							'link'		   => $a->get_baseurl().'/display/'.urlencode(get_item_guid($posted_id)),
							'source_name'  => stripslashes($datarray['author-name']),
							'source_link'  => $datarray['author-link'],
							'source_photo' => ((link_compare($datarray['author-link'],$importer['url']))
								? $importer['thumb'] : $datarray['author-avatar']),
							'verb'         => $datarray['verb'],
							'otype'        => 'person',
							'activity'     => $verb,
							'parent'       => $datarray['parent']
						));
					}
				}
			}

			continue;
		}
	}

	return 0;
	// NOTREACHED

}


function new_follower($importer,$contact,$datarray,$item,$sharing = false) {
	$url = notags(trim($datarray['author-link']));
	$name = notags(trim($datarray['author-name']));
	$photo = notags(trim($datarray['author-avatar']));

	if (is_object($item)) {
		$rawtag = $item->get_item_tags(NAMESPACE_ACTIVITY,'actor');
		if($rawtag && $rawtag[0]['child'][NAMESPACE_POCO]['preferredUsername'][0]['data'])
			$nick = $rawtag[0]['child'][NAMESPACE_POCO]['preferredUsername'][0]['data'];
	} else
		$nick = $item;

	if(is_array($contact)) {
		if(($contact['network'] == NETWORK_OSTATUS && $contact['rel'] == CONTACT_IS_SHARING)
			|| ($sharing && $contact['rel'] == CONTACT_IS_FOLLOWER)) {
			$r = q("UPDATE `contact` SET `rel` = %d, `writable` = 1 WHERE `id` = %d AND `uid` = %d",
				intval(CONTACT_IS_FRIEND),
				intval($contact['id']),
				intval($importer['uid'])
			);
		}
		// send email notification to owner?
	} else {

		// create contact record

		$r = q("INSERT INTO `contact` (`uid`, `created`, `url`, `nurl`, `name`, `nick`, `photo`, `network`, `rel`,
			`blocked`, `readonly`, `pending`, `writable`)
			VALUES (%d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, 0, 0, 1, 1)",
			intval($importer['uid']),
			dbesc(datetime_convert()),
			dbesc($url),
			dbesc(normalise_link($url)),
			dbesc($name),
			dbesc($nick),
			dbesc($photo),
			dbesc(($sharing) ? NETWORK_ZOT : NETWORK_OSTATUS),
			intval(($sharing) ? CONTACT_IS_SHARING : CONTACT_IS_FOLLOWER)
		);
		$r = q("SELECT `id` FROM `contact` WHERE `uid` = %d AND `url` = '%s' AND `pending` = 1 LIMIT 1",
				intval($importer['uid']),
				dbesc($url)
		);
		if(count($r)) {
				$contact_record = $r[0];

				$photos = import_profile_photo($photo,$importer["uid"],$contact_record["id"]);

				q("UPDATE `contact` SET `photo` = '%s', `thumb` = '%s', `micro` = '%s' WHERE `id` = %d",
					dbesc($photos[0]),
					dbesc($photos[1]),
					dbesc($photos[2]),
					intval($contact_record["id"])
				);
		}


		$r = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
			intval($importer['uid'])
		);
		$a = get_app();
		if(count($r) AND !in_array($r[0]['page-flags'], array(PAGE_SOAPBOX, PAGE_FREELOVE))) {

			// create notification
			$hash = random_string();

			if(is_array($contact_record)) {
				$ret = q("INSERT INTO `intro` ( `uid`, `contact-id`, `blocked`, `knowyou`, `hash`, `datetime`)
					VALUES ( %d, %d, 0, 0, '%s', '%s' )",
					intval($importer['uid']),
					intval($contact_record['id']),
					dbesc($hash),
					dbesc(datetime_convert())
				);
			}

			if(intval($r[0]['def_gid'])) {
				require_once('include/group.php');
				group_add_member($r[0]['uid'],'',$contact_record['id'],$r[0]['def_gid']);
			}

			if(($r[0]['notify-flags'] & NOTIFY_INTRO) &&
				in_array($r[0]['page-flags'], array(PAGE_NORMAL))) {

				notification(array(
					'type'         => NOTIFY_INTRO,
					'notify_flags' => $r[0]['notify-flags'],
					'language'     => $r[0]['language'],
					'to_name'      => $r[0]['username'],
					'to_email'     => $r[0]['email'],
					'uid'          => $r[0]['uid'],
					'link'		   => $a->get_baseurl() . '/notifications/intro',
					'source_name'  => ((strlen(stripslashes($contact_record['name']))) ? stripslashes($contact_record['name']) : t('[Name Withheld]')),
					'source_link'  => $contact_record['url'],
					'source_photo' => $contact_record['photo'],
					'verb'         => ($sharing ? ACTIVITY_FRIEND : ACTIVITY_FOLLOW),
					'otype'        => 'intro'
				));

			}
		} elseif (count($r) AND in_array($r[0]['page-flags'], array(PAGE_SOAPBOX, PAGE_FREELOVE))) {
			$r = q("UPDATE `contact` SET `pending` = 0 WHERE `uid` = %d AND `url` = '%s' AND `pending` LIMIT 1",
					intval($importer['uid']),
					dbesc($url)
			);
		}

	}
}

function lose_follower($importer,$contact,$datarray,$item) {

	if(($contact['rel'] == CONTACT_IS_FRIEND) || ($contact['rel'] == CONTACT_IS_SHARING)) {
		q("UPDATE `contact` SET `rel` = %d WHERE `id` = %d",
			intval(CONTACT_IS_SHARING),
			intval($contact['id'])
		);
	}
	else {
		contact_remove($contact['id']);
	}
}

function lose_sharer($importer,$contact,$datarray,$item) {

	if(($contact['rel'] == CONTACT_IS_FRIEND) || ($contact['rel'] == CONTACT_IS_FOLLOWER)) {
		q("UPDATE `contact` SET `rel` = %d WHERE `id` = %d",
			intval(CONTACT_IS_FOLLOWER),
			intval($contact['id'])
		);
	}
	else {
		contact_remove($contact['id']);
	}
}

function subscribe_to_hub($url,$importer,$contact,$hubmode = 'subscribe') {

	$a = get_app();

	if(is_array($importer)) {
		$r = q("SELECT `nickname` FROM `user` WHERE `uid` = %d LIMIT 1",
			intval($importer['uid'])
		);
	}

	// Diaspora has different message-ids in feeds than they do
	// through the direct Diaspora protocol. If we try and use
	// the feed, we'll get duplicates. So don't.

	if((! count($r)) || $contact['network'] === NETWORK_DIASPORA)
		return;

	$push_url = get_config('system','url') . '/pubsub/' . $r[0]['nickname'] . '/' . $contact['id'];

	// Use a single verify token, even if multiple hubs

	$verify_token = ((strlen($contact['hub-verify'])) ? $contact['hub-verify'] : random_string());

	$params= 'hub.mode=' . $hubmode . '&hub.callback=' . urlencode($push_url) . '&hub.topic=' . urlencode($contact['poll']) . '&hub.verify=async&hub.verify_token=' . $verify_token;

	logger('subscribe_to_hub: ' . $hubmode . ' ' . $contact['name'] . ' to hub ' . $url . ' endpoint: '  . $push_url . ' with verifier ' . $verify_token);

	if(!strlen($contact['hub-verify']) OR ($contact['hub-verify'] != $verify_token)) {
		$r = q("UPDATE `contact` SET `hub-verify` = '%s' WHERE `id` = %d",
			dbesc($verify_token),
			intval($contact['id'])
		);
	}

	post_url($url,$params);

	logger('subscribe_to_hub: returns: ' . $a->get_curl_code(), LOGGER_DEBUG);

	return;

}

function fix_private_photos($s, $uid, $item = null, $cid = 0) {

	if(get_config('system','disable_embedded'))
		return $s;

	$a = get_app();

	logger('fix_private_photos: check for photos', LOGGER_DEBUG);
	$site = substr($a->get_baseurl(),strpos($a->get_baseurl(),'://'));

	$orig_body = $s;
	$new_body = '';

	$img_start = strpos($orig_body, '[img');
	$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
	$img_len = ($img_start !== false ? strpos(substr($orig_body, $img_start + $img_st_close + 1), '[/img]') : false);
	while( ($img_st_close !== false) && ($img_len !== false) ) {

		$img_st_close++; // make it point to AFTER the closing bracket
		$image = substr($orig_body, $img_start + $img_st_close, $img_len);

		logger('fix_private_photos: found photo ' . $image, LOGGER_DEBUG);


		if(stristr($image , $site . '/photo/')) {
			// Only embed locally hosted photos
			$replace = false;
			$i = basename($image);
			$i = str_replace(array('.jpg','.png','.gif'),array('','',''),$i);
			$x = strpos($i,'-');

			if($x) {
				$res = substr($i,$x+1);
				$i = substr($i,0,$x);
				$r = q("SELECT * FROM `photo` WHERE `resource-id` = '%s' AND `scale` = %d AND `uid` = %d",
					dbesc($i),
					intval($res),
					intval($uid)
				);
				if($r) {

					// Check to see if we should replace this photo link with an embedded image
					// 1. No need to do so if the photo is public
					// 2. If there's a contact-id provided, see if they're in the access list
					//    for the photo. If so, embed it.
					// 3. Otherwise, if we have an item, see if the item permissions match the photo
					//    permissions, regardless of order but first check to see if they're an exact
					//    match to save some processing overhead.

					if(has_permissions($r[0])) {
						if($cid) {
							$recips = enumerate_permissions($r[0]);
							if(in_array($cid, $recips)) {
								$replace = true;
							}
						}
						elseif($item) {
							if(compare_permissions($item,$r[0]))
								$replace = true;
						}
					}
					if($replace) {
						$data = $r[0]['data'];
						$type = $r[0]['type'];

						// If a custom width and height were specified, apply before embedding
						if(preg_match("/\[img\=([0-9]*)x([0-9]*)\]/is", substr($orig_body, $img_start, $img_st_close), $match)) {
							logger('fix_private_photos: scaling photo', LOGGER_DEBUG);

							$width = intval($match[1]);
							$height = intval($match[2]);

							$ph = new Photo($data, $type);
							if($ph->is_valid()) {
								$ph->scaleImage(max($width, $height));
								$data = $ph->imageString();
								$type = $ph->getType();
							}
						}

						logger('fix_private_photos: replacing photo', LOGGER_DEBUG);
						$image = 'data:' . $type . ';base64,' . base64_encode($data);
						logger('fix_private_photos: replaced: ' . $image, LOGGER_DATA);
					}
				}
			}
		}

		$new_body = $new_body . substr($orig_body, 0, $img_start + $img_st_close) . $image . '[/img]';
		$orig_body = substr($orig_body, $img_start + $img_st_close + $img_len + strlen('[/img]'));
		if($orig_body === false)
			$orig_body = '';

		$img_start = strpos($orig_body, '[img');
		$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
		$img_len = ($img_start !== false ? strpos(substr($orig_body, $img_start + $img_st_close + 1), '[/img]') : false);
	}

	$new_body = $new_body . $orig_body;

	return($new_body);
}

function has_permissions($obj) {
	if(($obj['allow_cid'] != '') || ($obj['allow_gid'] != '') || ($obj['deny_cid'] != '') || ($obj['deny_gid'] != ''))
		return true;
	return false;
}

function compare_permissions($obj1,$obj2) {
	// first part is easy. Check that these are exactly the same.
	if(($obj1['allow_cid'] == $obj2['allow_cid'])
		&& ($obj1['allow_gid'] == $obj2['allow_gid'])
		&& ($obj1['deny_cid'] == $obj2['deny_cid'])
		&& ($obj1['deny_gid'] == $obj2['deny_gid']))
		return true;

	// This is harder. Parse all the permissions and compare the resulting set.

	$recipients1 = enumerate_permissions($obj1);
	$recipients2 = enumerate_permissions($obj2);
	sort($recipients1);
	sort($recipients2);
	if($recipients1 == $recipients2)
		return true;
	return false;
}

// returns an array of contact-ids that are allowed to see this object

function enumerate_permissions($obj) {
	require_once('include/group.php');
	$allow_people = expand_acl($obj['allow_cid']);
	$allow_groups = expand_groups(expand_acl($obj['allow_gid']));
	$deny_people  = expand_acl($obj['deny_cid']);
	$deny_groups  = expand_groups(expand_acl($obj['deny_gid']));
	$recipients   = array_unique(array_merge($allow_people,$allow_groups));
	$deny         = array_unique(array_merge($deny_people,$deny_groups));
	$recipients   = array_diff($recipients,$deny);
	return $recipients;
}

function item_getfeedtags($item) {
	$ret = array();
	$matches = false;
	$cnt = preg_match_all('|\#\[url\=(.*?)\](.*?)\[\/url\]|',$item['tag'],$matches);
	if($cnt) {
		for($x = 0; $x < $cnt; $x ++) {
			if($matches[1][$x])
				$ret[$matches[2][$x]] = array('#',$matches[1][$x], $matches[2][$x]);
		}
	}
	$matches = false;
	$cnt = preg_match_all('|\@\[url\=(.*?)\](.*?)\[\/url\]|',$item['tag'],$matches);
	if($cnt) {
		for($x = 0; $x < $cnt; $x ++) {
			if($matches[1][$x])
				$ret[] = array('@',$matches[1][$x], $matches[2][$x]);
		}
	}
	return $ret;
}

function item_expire($uid, $days, $network = "", $force = false) {

	if((! $uid) || ($days < 1))
		return;

	// $expire_network_only = save your own wall posts
	// and just expire conversations started by others

	$expire_network_only = get_pconfig($uid,'expire','network_only');
	$sql_extra = ((intval($expire_network_only)) ? " AND wall = 0 " : "");

	if ($network != "") {
		$sql_extra .= sprintf(" AND network = '%s' ", dbesc($network));
		// There is an index "uid_network_received" but not "uid_network_created"
		// This avoids the creation of another index just for one purpose.
		// And it doesn't really matter wether to look at "received" or "created"
		$range = "AND `received` < UTC_TIMESTAMP() - INTERVAL %d DAY ";
	} else
		$range = "AND `created` < UTC_TIMESTAMP() - INTERVAL %d DAY ";

	$r = q("SELECT * FROM `item`
		WHERE `uid` = %d $range
		AND `id` = `parent`
		$sql_extra
		AND `deleted` = 0",
		intval($uid),
		intval($days)
	);

	if(! count($r))
		return;

	$expire_items = get_pconfig($uid, 'expire','items');
	$expire_items = (($expire_items===false)?1:intval($expire_items)); // default if not set: 1

	// Forcing expiring of items - but not notes and marked items
	if ($force)
		$expire_items = true;

	$expire_notes = get_pconfig($uid, 'expire','notes');
	$expire_notes = (($expire_notes===false)?1:intval($expire_notes)); // default if not set: 1

	$expire_starred = get_pconfig($uid, 'expire','starred');
	$expire_starred = (($expire_starred===false)?1:intval($expire_starred)); // default if not set: 1

	$expire_photos = get_pconfig($uid, 'expire','photos');
	$expire_photos = (($expire_photos===false)?0:intval($expire_photos)); // default if not set: 0

	logger('expire: # items=' . count($r). "; expire items: $expire_items, expire notes: $expire_notes, expire starred: $expire_starred, expire photos: $expire_photos");

	foreach($r as $item) {

		// don't expire filed items

		if(strpos($item['file'],'[') !== false)
			continue;

		// Only expire posts, not photos and photo comments

		if($expire_photos==0 && strlen($item['resource-id']))
			continue;
		if($expire_starred==0 && intval($item['starred']))
			continue;
		if($expire_notes==0 && $item['type']=='note')
			continue;
		if($expire_items==0 && $item['type']!='note')
			continue;

		drop_item($item['id'],false);
	}

	proc_run('php',"include/notifier.php","expire","$uid");

}


function drop_items($items) {
	$uid = 0;

	if(! local_user() && ! remote_user())
		return;

	if(count($items)) {
		foreach($items as $item) {
			$owner = drop_item($item,false);
			if($owner && ! $uid)
				$uid = $owner;
		}
	}

	// multiple threads may have been deleted, send an expire notification

	if($uid)
		proc_run('php',"include/notifier.php","expire","$uid");
}


function drop_item($id,$interactive = true) {

	$a = get_app();

	// locate item to be deleted

	$r = q("SELECT * FROM `item` WHERE `id` = %d LIMIT 1",
		intval($id)
	);

	if(! count($r)) {
		if(! $interactive)
			return 0;
		notice( t('Item not found.') . EOL);
		goaway($a->get_baseurl() . '/' . $_SESSION['return_url']);
	}

	$item = $r[0];

	$owner = $item['uid'];

	$cid = 0;

	// check if logged in user is either the author or owner of this item

	if(is_array($_SESSION['remote'])) {
		foreach($_SESSION['remote'] as $visitor) {
			if($visitor['uid'] == $item['uid'] && $visitor['cid'] == $item['contact-id']) {
				$cid = $visitor['cid'];
				break;
			}
		}
	}


	if((local_user() == $item['uid']) || ($cid) || (! $interactive)) {

		// Check if we should do HTML-based delete confirmation
		if($_REQUEST['confirm']) {
			// <form> can't take arguments in its "action" parameter
			// so add any arguments as hidden inputs
			$query = explode_querystring($a->query_string);
			$inputs = array();
			foreach($query['args'] as $arg) {
				if(strpos($arg, 'confirm=') === false) {
					$arg_parts = explode('=', $arg);
					$inputs[] = array('name' => $arg_parts[0], 'value' => $arg_parts[1]);
				}
			}

			return replace_macros(get_markup_template('confirm.tpl'), array(
				'$method' => 'get',
				'$message' => t('Do you really want to delete this item?'),
				'$extra_inputs' => $inputs,
				'$confirm' => t('Yes'),
				'$confirm_url' => $query['base'],
				'$confirm_name' => 'confirmed',
				'$cancel' => t('Cancel'),
			));
		}
		// Now check how the user responded to the confirmation query
		if($_REQUEST['canceled']) {
			goaway($a->get_baseurl() . '/' . $_SESSION['return_url']);
		}

		logger('delete item: ' . $item['id'], LOGGER_DEBUG);
		// delete the item

		$r = q("UPDATE `item` SET `deleted` = 1, `title` = '', `body` = '', `edited` = '%s', `changed` = '%s' WHERE `id` = %d",
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			intval($item['id'])
		);
		create_tags_from_item($item['id']);
		create_files_from_item($item['id']);
		delete_thread($item['id'], $item['parent-uri']);

		// clean up categories and tags so they don't end up as orphans

		$matches = false;
		$cnt = preg_match_all('/<(.*?)>/',$item['file'],$matches,PREG_SET_ORDER);
		if($cnt) {
			foreach($matches as $mtch) {
				file_tag_unsave_file($item['uid'],$item['id'],$mtch[1],true);
			}
		}

		$matches = false;

		$cnt = preg_match_all('/\[(.*?)\]/',$item['file'],$matches,PREG_SET_ORDER);
		if($cnt) {
			foreach($matches as $mtch) {
				file_tag_unsave_file($item['uid'],$item['id'],$mtch[1],false);
			}
		}

		// If item is a link to a photo resource, nuke all the associated photos
		// (visitors will not have photo resources)
		// This only applies to photos uploaded from the photos page. Photos inserted into a post do not
		// generate a resource-id and therefore aren't intimately linked to the item.

		if(strlen($item['resource-id'])) {
			q("DELETE FROM `photo` WHERE `resource-id` = '%s' AND `uid` = %d ",
				dbesc($item['resource-id']),
				intval($item['uid'])
			);
			// ignore the result
		}

		// If item is a link to an event, nuke the event record.

		if(intval($item['event-id'])) {
			q("DELETE FROM `event` WHERE `id` = %d AND `uid` = %d",
				intval($item['event-id']),
				intval($item['uid'])
			);
			// ignore the result
		}

		// If item has attachments, drop them

		foreach(explode(",",$item['attach']) as $attach){
			preg_match("|attach/(\d+)|", $attach, $matches);
			q("DELETE FROM `attach` WHERE `id` = %d AND `uid` = %d",
				intval($matches[1]),
				local_user()
			);
			// ignore the result
		}


		// clean up item_id and sign meta-data tables

		/*
		// Old code - caused very long queries and warning entries in the mysql logfiles:

		$r = q("DELETE FROM item_id where iid in (select id from item where parent = %d and uid = %d)",
			intval($item['id']),
			intval($item['uid'])
		);

		$r = q("DELETE FROM sign where iid in (select id from item where parent = %d and uid = %d)",
			intval($item['id']),
			intval($item['uid'])
		);
		*/

		// The new code splits the queries since the mysql optimizer really has bad problems with subqueries

		// Creating list of parents
		$r = q("select id from item where parent = %d and uid = %d",
			intval($item['id']),
			intval($item['uid'])
		);

		$parentid = "";

		foreach ($r AS $row) {
			if ($parentid != "")
				$parentid .= ", ";

			$parentid .= $row["id"];
		}

		// Now delete them
		if ($parentid != "") {
			$r = q("DELETE FROM item_id where iid in (%s)", dbesc($parentid));

			$r = q("DELETE FROM sign where iid in (%s)", dbesc($parentid));
		}

		// If it's the parent of a comment thread, kill all the kids

		if($item['uri'] == $item['parent-uri']) {
			$r = q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s', `body` = '' , `title` = ''
				WHERE `parent-uri` = '%s' AND `uid` = %d ",
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				dbesc($item['parent-uri']),
				intval($item['uid'])
			);
			create_tags_from_itemuri($item['parent-uri'], $item['uid']);
			create_files_from_itemuri($item['parent-uri'], $item['uid']);
			delete_thread_uri($item['parent-uri'], $item['uid']);
			// ignore the result
		}
		else {
			// ensure that last-child is set in case the comment that had it just got wiped.
			q("UPDATE `item` SET `last-child` = 0, `changed` = '%s' WHERE `parent-uri` = '%s' AND `uid` = %d ",
				dbesc(datetime_convert()),
				dbesc($item['parent-uri']),
				intval($item['uid'])
			);
			// who is the last child now?
			$r = q("SELECT `id` FROM `item` WHERE `parent-uri` = '%s' AND `type` != 'activity' AND `deleted` = 0 AND `uid` = %d ORDER BY `edited` DESC LIMIT 1",
				dbesc($item['parent-uri']),
				intval($item['uid'])
			);
			if(count($r)) {
				q("UPDATE `item` SET `last-child` = 1 WHERE `id` = %d",
					intval($r[0]['id'])
				);
			}

			// Add a relayable_retraction signature for Diaspora.
			store_diaspora_retract_sig($item, $a->user, $a->get_baseurl());
		}

		$drop_id = intval($item['id']);

		// send the notification upstream/downstream as the case may be

		proc_run('php',"include/notifier.php","drop","$drop_id");

		if(! $interactive)
			return $owner;
		goaway($a->get_baseurl() . '/' . $_SESSION['return_url']);
		//NOTREACHED
	}
	else {
		if(! $interactive)
			return 0;
		notice( t('Permission denied.') . EOL);
		goaway($a->get_baseurl() . '/' . $_SESSION['return_url']);
		//NOTREACHED
	}

}


function first_post_date($uid,$wall = false) {
	$r = q("select id, created from item
		where uid = %d and wall = %d and deleted = 0 and visible = 1 AND moderated = 0
		and id = parent
		order by created asc limit 1",
		intval($uid),
		intval($wall ? 1 : 0)
	);
	if(count($r)) {
//		logger('first_post_date: ' . $r[0]['id'] . ' ' . $r[0]['created'], LOGGER_DATA);
		return substr(datetime_convert('',date_default_timezone_get(),$r[0]['created']),0,10);
	}
	return false;
}

/* modified posted_dates() {below} to arrange the list in years */
function list_post_dates($uid, $wall) {
	$dnow = datetime_convert('',date_default_timezone_get(),'now','Y-m-d');

	$dthen = first_post_date($uid, $wall);
	if(! $dthen)
		return array();

	// Set the start and end date to the beginning of the month
	$dnow = substr($dnow,0,8).'01';
	$dthen = substr($dthen,0,8).'01';

	$ret = array();

	// Starting with the current month, get the first and last days of every
	// month down to and including the month of the first post
	while(substr($dnow, 0, 7) >= substr($dthen, 0, 7)) {
		$dyear = intval(substr($dnow,0,4));
		$dstart = substr($dnow,0,8) . '01';
		$dend = substr($dnow,0,8) . get_dim(intval($dnow),intval(substr($dnow,5)));
		$start_month = datetime_convert('','',$dstart,'Y-m-d');
		$end_month = datetime_convert('','',$dend,'Y-m-d');
		$str = day_translate(datetime_convert('','',$dnow,'F'));
		if(! $ret[$dyear])
			$ret[$dyear] = array();
		$ret[$dyear][] = array($str,$end_month,$start_month);
		$dnow = datetime_convert('','',$dnow . ' -1 month', 'Y-m-d');
	}
	return $ret;
}

function posted_dates($uid,$wall) {
	$dnow = datetime_convert('',date_default_timezone_get(),'now','Y-m-d');

	$dthen = first_post_date($uid,$wall);
	if(! $dthen)
		return array();

	// Set the start and end date to the beginning of the month
	$dnow = substr($dnow,0,8).'01';
	$dthen = substr($dthen,0,8).'01';

	$ret = array();
	// Starting with the current month, get the first and last days of every
	// month down to and including the month of the first post
	while(substr($dnow, 0, 7) >= substr($dthen, 0, 7)) {
		$dstart = substr($dnow,0,8) . '01';
		$dend = substr($dnow,0,8) . get_dim(intval($dnow),intval(substr($dnow,5)));
		$start_month = datetime_convert('','',$dstart,'Y-m-d');
		$end_month = datetime_convert('','',$dend,'Y-m-d');
		$str = day_translate(datetime_convert('','',$dnow,'F Y'));
		$ret[] = array($str,$end_month,$start_month);
		$dnow = datetime_convert('','',$dnow . ' -1 month', 'Y-m-d');
	}
	return $ret;
}


function posted_date_widget($url,$uid,$wall) {
	$o = '';

	if(! feature_enabled($uid,'archives'))
		return $o;

	// For former Facebook folks that left because of "timeline"

/*	if($wall && intval(get_pconfig($uid,'system','no_wall_archive_widget')))
		return $o;*/

	$visible_years = get_pconfig($uid,'system','archive_visible_years');
	if(! $visible_years)
		$visible_years = 5;

	$ret = list_post_dates($uid,$wall);

	if(! count($ret))
		return $o;

	$cutoff_year = intval(datetime_convert('',date_default_timezone_get(),'now','Y')) - $visible_years;
	$cutoff = ((array_key_exists($cutoff_year,$ret))? true : false);

	$o = replace_macros(get_markup_template('posted_date_widget.tpl'),array(
		'$title' => t('Archives'),
		'$size' => $visible_years,
		'$cutoff_year' => $cutoff_year,
		'$cutoff' => $cutoff,
		'$url' => $url,
		'$dates' => $ret,
		'$showmore' => t('show more')

	));
	return $o;
}

function store_diaspora_retract_sig($item, $user, $baseurl) {
	// Note that we can't add a target_author_signature
	// if the comment was deleted by a remote user. That should be ok, because if a remote user is deleting
	// the comment, that means we're the home of the post, and Diaspora will only
	// check the parent_author_signature of retractions that it doesn't have to relay further
	//
	// I don't think this function gets called for an "unlike," but I'll check anyway

	$enabled = intval(get_config('system','diaspora_enabled'));
	if(! $enabled) {
		logger('drop_item: diaspora support disabled, not storing retraction signature', LOGGER_DEBUG);
		return;
	}

	logger('drop_item: storing diaspora retraction signature');

	$signed_text = $item['guid'] . ';' . ( ($item['verb'] === ACTIVITY_LIKE) ? 'Like' : 'Comment');

	if(local_user() == $item['uid']) {

		$handle = $user['nickname'] . '@' . substr($baseurl, strpos($baseurl,'://') + 3);
		$authorsig = base64_encode(rsa_sign($signed_text,$user['prvkey'],'sha256'));
	}
	else {
		$r = q("SELECT `nick`, `url` FROM `contact` WHERE `id` = '%d' LIMIT 1",
			$item['contact-id'] // If this function gets called, drop_item() has already checked remote_user() == $item['contact-id']
		);
		if(count($r)) {
			// The below handle only works for NETWORK_DFRN. I think that's ok, because this function
			// only handles DFRN deletes
			$handle_baseurl_start = strpos($r['url'],'://') + 3;
			$handle_baseurl_length = strpos($r['url'],'/profile') - $handle_baseurl_start;
			$handle = $r['nick'] . '@' . substr($r['url'], $handle_baseurl_start, $handle_baseurl_length);
			$authorsig = '';
		}
	}

	if(isset($handle))
		q("insert into sign (`retract_iid`,`signed_text`,`signature`,`signer`) values (%d,'%s','%s','%s') ",
			intval($item['id']),
			dbesc($signed_text),
			dbesc($authorsig),
			dbesc($handle)
		);

	return;
}
