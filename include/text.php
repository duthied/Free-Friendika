<?php
/**
 * @file include/text.php
 */

use Friendica\App;
use Friendica\Content\ContactSelector;
use Friendica\Content\Feature;
use Friendica\Content\Smilies;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Event;
use Friendica\Model\Item;
use Friendica\Render\FriendicaSmarty;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Map;
use Friendica\Util\Proxy as ProxyUtils;

use Friendica\Core\Logger;
use Friendica\Core\Renderer;
use Friendica\Model\FileTag;
use Friendica\Util\XML;
use Friendica\Content\Text\HTML;

require_once "include/conversation.php";

/**
 * @brief Generates a pseudo-random string of hexadecimal characters
 *
 * @param int $size
 * @return string
 */
function random_string($size = 64)
{
	$byte_size = ceil($size / 2);

	$bytes = random_bytes($byte_size);

	$return = substr(bin2hex($bytes), 0, $size);

	return $return;
}

/**
 * This is our primary input filter.
 *
 * The high bit hack only involved some old IE browser, forget which (IE5/Mac?)
 * that had an XSS attack vector due to stripping the high-bit on an 8-bit character
 * after cleansing, and angle chars with the high bit set could get through as markup.
 *
 * This is now disabled because it was interfering with some legitimate unicode sequences
 * and hopefully there aren't a lot of those browsers left.
 *
 * Use this on any text input where angle chars are not valid or permitted
 * They will be replaced with safer brackets. This may be filtered further
 * if these are not allowed either.
 *
 * @param string $string Input string
 * @return string Filtered string
 */
function notags($string) {
	return str_replace(["<", ">"], ['[', ']'], $string);

//  High-bit filter no longer used
//	return str_replace(array("<",">","\xBA","\xBC","\xBE"), array('[',']','','',''), $string);
}


/**
 * use this on "body" or "content" input where angle chars shouldn't be removed,
 * and allow them to be safely displayed.
 * @param string $string
 * @return string
 */
function escape_tags($string) {
	return htmlspecialchars($string, ENT_COMPAT, 'UTF-8', false);
}


/**
 * generate a string that's random, but usually pronounceable.
 * used to generate initial passwords
 * @param int $len
 * @return string
 */
function autoname($len) {

	if ($len <= 0) {
		return '';
	}

	$vowels = ['a','a','ai','au','e','e','e','ee','ea','i','ie','o','ou','u'];
	if (mt_rand(0, 5) == 4) {
		$vowels[] = 'y';
	}

	$cons = [
			'b','bl','br',
			'c','ch','cl','cr',
			'd','dr',
			'f','fl','fr',
			'g','gh','gl','gr',
			'h',
			'j',
			'k','kh','kl','kr',
			'l',
			'm',
			'n',
			'p','ph','pl','pr',
			'qu',
			'r','rh',
			's','sc','sh','sm','sp','st',
			't','th','tr',
			'v',
			'w','wh',
			'x',
			'z','zh'
			];

	$midcons = ['ck','ct','gn','ld','lf','lm','lt','mb','mm', 'mn','mp',
				'nd','ng','nk','nt','rn','rp','rt'];

	$noend = ['bl', 'br', 'cl','cr','dr','fl','fr','gl','gr',
				'kh', 'kl','kr','mn','pl','pr','rh','tr','qu','wh','q'];

	$start = mt_rand(0,2);
	if ($start == 0) {
		$table = $vowels;
	} else {
		$table = $cons;
	}

	$word = '';

	for ($x = 0; $x < $len; $x ++) {
		$r = mt_rand(0,count($table) - 1);
		$word .= $table[$r];

		if ($table == $vowels) {
			$table = array_merge($cons,$midcons);
		} else {
			$table = $vowels;
		}

	}

	$word = substr($word,0,$len);

	foreach ($noend as $noe) {
		$noelen = strlen($noe);
		if ((strlen($word) > $noelen) && (substr($word, -$noelen) == $noe)) {
			$word = autoname($len);
			break;
		}
	}

	return $word;
}

/**
 * Turn user/group ACLs stored as angle bracketed text into arrays
 *
 * @param string $s
 * @return array
 */
function expand_acl($s) {
	// turn string array of angle-bracketed elements into numeric array
	// e.g. "<1><2><3>" => array(1,2,3);
	$ret = [];

	if (strlen($s)) {
		$t = str_replace('<', '', $s);
		$a = explode('>', $t);
		foreach ($a as $aa) {
			if (intval($aa)) {
				$ret[] = intval($aa);
			}
		}
	}
	return $ret;
}


/**
 * Wrap ACL elements in angle brackets for storage
 * @param string $item
 */
function sanitise_acl(&$item) {
	if (intval($item)) {
		$item = '<' . intval(notags(trim($item))) . '>';
	} else {
		unset($item);
	}
}


/**
 * Convert an ACL array to a storable string
 *
 * Normally ACL permissions will be an array.
 * We'll also allow a comma-separated string.
 *
 * @param string|array $p
 * @return string
 */
function perms2str($p) {
	$ret = '';
	if (is_array($p)) {
		$tmp = $p;
	} else {
		$tmp = explode(',', $p);
	}

	if (is_array($tmp)) {
		array_walk($tmp, 'sanitise_acl');
		$ret = implode('', $tmp);
	}
	return $ret;
}

/**
 *  for html,xml parsing - let's say you've got
 *  an attribute foobar="class1 class2 class3"
 *  and you want to find out if it contains 'class3'.
 *  you can't use a normal sub string search because you
 *  might match 'notclass3' and a regex to do the job is
 *  possible but a bit complicated.
 *  pass the attribute string as $attr and the attribute you
 *  are looking for as $s - returns true if found, otherwise false
 *
 * @param string $attr attribute value
 * @param string $s string to search
 * @return boolean True if found, False otherwise
 */
function attribute_contains($attr, $s) {
	$a = explode(' ', $attr);
	return (count($a) && in_array($s,$a));
}

/**
 * Compare activity uri. Knows about activity namespace.
 *
 * @param string $haystack
 * @param string $needle
 * @return boolean
 */
function activity_match($haystack,$needle) {
	return (($haystack === $needle) || ((basename($needle) === $haystack) && strstr($needle, NAMESPACE_ACTIVITY_SCHEMA)));
}


/**
 * @brief Pull out all #hashtags and @person tags from $string.
 *
 * We also get @person@domain.com - which would make
 * the regex quite complicated as tags can also
 * end a sentence. So we'll run through our results
 * and strip the period from any tags which end with one.
 * Returns array of tags found, or empty array.
 *
 * @param string $string Post content
 * @return array List of tag and person names
 */
function get_tags($string) {
	$ret = [];

	// Convert hashtag links to hashtags
	$string = preg_replace('/#\[url\=([^\[\]]*)\](.*?)\[\/url\]/ism', '#$2', $string);

	// ignore anything in a code block
	$string = preg_replace('/\[code\](.*?)\[\/code\]/sm', '', $string);

	// Force line feeds at bbtags
	$string = str_replace(['[', ']'], ["\n[", "]\n"], $string);

	// ignore anything in a bbtag
	$string = preg_replace('/\[(.*?)\]/sm', '', $string);

	// Match full names against @tags including the space between first and last
	// We will look these up afterward to see if they are full names or not recognisable.

	if (preg_match_all('/(@[^ \x0D\x0A,:?]+ [^ \x0D\x0A@,:?]+)([ \x0D\x0A@,:?]|$)/', $string, $matches)) {
		foreach ($matches[1] as $match) {
			if (strstr($match, ']')) {
				// we might be inside a bbcode color tag - leave it alone
				continue;
			}
			if (substr($match, -1, 1) === '.') {
				$ret[] = substr($match, 0, -1);
			} else {
				$ret[] = $match;
			}
		}
	}

	// Otherwise pull out single word tags. These can be @nickname, @first_last
	// and #hash tags.

	if (preg_match_all('/([!#@][^\^ \x0D\x0A,;:?]+)([ \x0D\x0A,;:?]|$)/', $string, $matches)) {
		foreach ($matches[1] as $match) {
			if (strstr($match, ']')) {
				// we might be inside a bbcode color tag - leave it alone
				continue;
			}
			if (substr($match, -1, 1) === '.') {
				$match = substr($match,0,-1);
			}
			// ignore strictly numeric tags like #1
			if ((strpos($match, '#') === 0) && ctype_digit(substr($match, 1))) {
				continue;
			}
			// try not to catch url fragments
			if (strpos($string, $match) && preg_match('/[a-zA-z0-9\/]/', substr($string, strpos($string, $match) - 1, 1))) {
				continue;
			}
			$ret[] = $match;
		}
	}
	return $ret;
}


/**
 * quick and dirty quoted_printable encoding
 *
 * @param string $s
 * @return string
 */
function qp($s) {
	return str_replace("%", "=", rawurlencode($s));
}

/**
 * @brief Check for a valid email string
 *
 * @param string $email_address
 * @return boolean
 */
function valid_email($email_address)
{
	return preg_match('/^[_a-zA-Z0-9\-\+]+(\.[_a-zA-Z0-9\-\+]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$/', $email_address);
}

/**
 * Normalize url
 *
 * @param string $url
 * @return string
 */
function normalise_link($url) {
	$ret = str_replace(['https:', '//www.'], ['http:', '//'], $url);
	return rtrim($ret,'/');
}


/**
 * Compare two URLs to see if they are the same, but ignore
 * slight but hopefully insignificant differences such as if one
 * is https and the other isn't, or if one is www.something and
 * the other isn't - and also ignore case differences.
 *
 * @param string $a first url
 * @param string $b second url
 * @return boolean True if the URLs match, otherwise False
 *
 */
function link_compare($a, $b) {
	return (strcasecmp(normalise_link($a), normalise_link($b)) === 0);
}


/**
 * @brief Find any non-embedded images in private items and add redir links to them
 *
 * @param App $a
 * @param array &$item The field array of an item row
 */
function redir_private_images($a, &$item)
{
	$matches = false;
	$cnt = preg_match_all('|\[img\](http[^\[]*?/photo/[a-fA-F0-9]+?(-[0-9]\.[\w]+?)?)\[\/img\]|', $item['body'], $matches, PREG_SET_ORDER);
	if ($cnt) {
		foreach ($matches as $mtch) {
			if (strpos($mtch[1], '/redir') !== false) {
				continue;
			}

			if ((local_user() == $item['uid']) && ($item['private'] == 1) && ($item['contact-id'] != $a->contact['id']) && ($item['network'] == Protocol::DFRN)) {
				$img_url = 'redir?f=1&quiet=1&url=' . urlencode($mtch[1]) . '&conurl=' . urlencode($item['author-link']);
				$item['body'] = str_replace($mtch[0], '[img]' . $img_url . '[/img]', $item['body']);
			}
		}
	}
}

/**
 * Sets the "rendered-html" field of the provided item
 *
 * Body is preserved to avoid side-effects as we modify it just-in-time for spoilers and private image links
 *
 * @param array $item
 * @param bool  $update
 *
 * @todo Remove reference, simply return "rendered-html" and "rendered-hash"
 */
function put_item_in_cache(&$item, $update = false)
{
	$body = $item["body"];

	$rendered_hash = defaults($item, 'rendered-hash', '');
	$rendered_html = defaults($item, 'rendered-html', '');

	if ($rendered_hash == ''
		|| $rendered_html == ""
		|| $rendered_hash != hash("md5", $item["body"])
		|| Config::get("system", "ignore_cache")
	) {
		$a = get_app();
		redir_private_images($a, $item);

		$item["rendered-html"] = prepare_text($item["body"]);
		$item["rendered-hash"] = hash("md5", $item["body"]);

		$hook_data = ['item' => $item, 'rendered-html' => $item['rendered-html'], 'rendered-hash' => $item['rendered-hash']];
		Addon::callHooks('put_item_in_cache', $hook_data);
		$item['rendered-html'] = $hook_data['rendered-html'];
		$item['rendered-hash'] = $hook_data['rendered-hash'];
		unset($hook_data);

		// Force an update if the generated values differ from the existing ones
		if ($rendered_hash != $item["rendered-hash"]) {
			$update = true;
		}

		// Only compare the HTML when we forcefully ignore the cache
		if (Config::get("system", "ignore_cache") && ($rendered_html != $item["rendered-html"])) {
			$update = true;
		}

		if ($update && !empty($item["id"])) {
			Item::update(['rendered-html' => $item["rendered-html"], 'rendered-hash' => $item["rendered-hash"]],
					['id' => $item["id"]]);
		}
	}

	$item["body"] = $body;
}

/**
 * @brief Given an item array, convert the body element from bbcode to html and add smilie icons.
 * If attach is true, also add icons for item attachments.
 *
 * @param array   $item
 * @param boolean $attach
 * @param boolean $is_preview
 * @return string item body html
 * @hook prepare_body_init item array before any work
 * @hook prepare_body_content_filter ('item'=>item array, 'filter_reasons'=>string array) before first bbcode to html
 * @hook prepare_body ('item'=>item array, 'html'=>body string, 'is_preview'=>boolean, 'filter_reasons'=>string array) after first bbcode to html
 * @hook prepare_body_final ('item'=>item array, 'html'=>body string) after attach icons and blockquote special case handling (spoiler, author)
 */
function prepare_body(array &$item, $attach = false, $is_preview = false)
{
	$a = get_app();
	Addon::callHooks('prepare_body_init', $item);

	// In order to provide theme developers more possibilities, event items
	// are treated differently.
	if ($item['object-type'] === ACTIVITY_OBJ_EVENT && isset($item['event-id'])) {
		$ev = Event::getItemHTML($item);
		return $ev;
	}

	$tags = \Friendica\Model\Term::populateTagsFromItem($item);

	$item['tags'] = $tags['tags'];
	$item['hashtags'] = $tags['hashtags'];
	$item['mentions'] = $tags['mentions'];

	// Compile eventual content filter reasons
	$filter_reasons = [];
	if (!$is_preview && public_contact() != $item['author-id']) {
		if (!empty($item['content-warning']) && (!local_user() || !PConfig::get(local_user(), 'system', 'disable_cw', false))) {
			$filter_reasons[] = L10n::t('Content warning: %s', $item['content-warning']);
		}

		$hook_data = [
			'item' => $item,
			'filter_reasons' => $filter_reasons
		];
		Addon::callHooks('prepare_body_content_filter', $hook_data);
		$filter_reasons = $hook_data['filter_reasons'];
		unset($hook_data);
	}

	// Update the cached values if there is no "zrl=..." on the links.
	$update = (!local_user() && !remote_user() && ($item["uid"] == 0));

	// Or update it if the current viewer is the intented viewer.
	if (($item["uid"] == local_user()) && ($item["uid"] != 0)) {
		$update = true;
	}

	put_item_in_cache($item, $update);
	$s = $item["rendered-html"];

	$hook_data = [
		'item' => $item,
		'html' => $s,
		'preview' => $is_preview,
		'filter_reasons' => $filter_reasons
	];
	Addon::callHooks('prepare_body', $hook_data);
	$s = $hook_data['html'];
	unset($hook_data);

	if (!$attach) {
		// Replace the blockquotes with quotes that are used in mails.
		$mailquote = '<blockquote type="cite" class="gmail_quote" style="margin:0 0 0 .8ex;border-left:1px #ccc solid;padding-left:1ex;">';
		$s = str_replace(['<blockquote>', '<blockquote class="spoiler">', '<blockquote class="author">'], [$mailquote, $mailquote, $mailquote], $s);
		return $s;
	}

	$as = '';
	$vhead = false;
	$matches = [];
	preg_match_all('|\[attach\]href=\"(.*?)\" length=\"(.*?)\" type=\"(.*?)\"(?: title=\"(.*?)\")?|', $item['attach'], $matches, PREG_SET_ORDER);
	foreach ($matches as $mtch) {
		$mime = $mtch[3];

		$the_url = Contact::magicLinkById($item['author-id'], $mtch[1]);

		if (strpos($mime, 'video') !== false) {
			if (!$vhead) {
				$vhead = true;
				$a->page['htmlhead'] .= Renderer::replaceMacros(Renderer::getMarkupTemplate('videos_head.tpl'), [
					'$baseurl' => System::baseUrl(),
				]);
			}

			$url_parts = explode('/', $the_url);
			$id = end($url_parts);
			$as .= Renderer::replaceMacros(Renderer::getMarkupTemplate('video_top.tpl'), [
				'$video' => [
					'id'     => $id,
					'title'  => L10n::t('View Video'),
					'src'    => $the_url,
					'mime'   => $mime,
				],
			]);
		}

		$filetype = strtolower(substr($mime, 0, strpos($mime, '/')));
		if ($filetype) {
			$filesubtype = strtolower(substr($mime, strpos($mime, '/') + 1));
			$filesubtype = str_replace('.', '-', $filesubtype);
		} else {
			$filetype = 'unkn';
			$filesubtype = 'unkn';
		}

		$title = escape_tags(trim(!empty($mtch[4]) ? $mtch[4] : $mtch[1]));
		$title .= ' ' . $mtch[2] . ' ' . L10n::t('bytes');

		$icon = '<div class="attachtype icon s22 type-' . $filetype . ' subtype-' . $filesubtype . '"></div>';
		$as .= '<a href="' . strip_tags($the_url) . '" title="' . $title . '" class="attachlink" target="_blank" >' . $icon . '</a>';
	}

	if ($as != '') {
		$s .= '<div class="body-attach">'.$as.'<div class="clear"></div></div>';
	}

	// Map.
	if (strpos($s, '<div class="map">') !== false && x($item, 'coord')) {
		$x = Map::byCoordinates(trim($item['coord']));
		if ($x) {
			$s = preg_replace('/\<div class\=\"map\"\>/', '$0' . $x, $s);
		}
	}


	// Look for spoiler.
	$spoilersearch = '<blockquote class="spoiler">';

	// Remove line breaks before the spoiler.
	while ((strpos($s, "\n" . $spoilersearch) !== false)) {
		$s = str_replace("\n" . $spoilersearch, $spoilersearch, $s);
	}
	while ((strpos($s, "<br />" . $spoilersearch) !== false)) {
		$s = str_replace("<br />" . $spoilersearch, $spoilersearch, $s);
	}

	while ((strpos($s, $spoilersearch) !== false)) {
		$pos = strpos($s, $spoilersearch);
		$rnd = random_string(8);
		$spoilerreplace = '<br /> <span id="spoiler-wrap-' . $rnd . '" class="spoiler-wrap fakelink" onclick="openClose(\'spoiler-' . $rnd . '\');">' . L10n::t('Click to open/close') . '</span>'.
					'<blockquote class="spoiler" id="spoiler-' . $rnd . '" style="display: none;">';
		$s = substr($s, 0, $pos) . $spoilerreplace . substr($s, $pos + strlen($spoilersearch));
	}

	// Look for quote with author.
	$authorsearch = '<blockquote class="author">';

	while ((strpos($s, $authorsearch) !== false)) {
		$pos = strpos($s, $authorsearch);
		$rnd = random_string(8);
		$authorreplace = '<br /> <span id="author-wrap-' . $rnd . '" class="author-wrap fakelink" onclick="openClose(\'author-' . $rnd . '\');">' . L10n::t('Click to open/close') . '</span>'.
					'<blockquote class="author" id="author-' . $rnd . '" style="display: block;">';
		$s = substr($s, 0, $pos) . $authorreplace . substr($s, $pos + strlen($authorsearch));
	}

	// Replace friendica image url size with theme preference.
	if (x($a->theme_info, 'item_image_size')){
		$ps = $a->theme_info['item_image_size'];
		$s = preg_replace('|(<img[^>]+src="[^"]+/photo/[0-9a-f]+)-[0-9]|', "$1-" . $ps, $s);
	}

	$s = HTML::applyContentFilter($s, $filter_reasons);

	$hook_data = ['item' => $item, 'html' => $s];
	Addon::callHooks('prepare_body_final', $hook_data);

	return $hook_data['html'];
}

/**
 * @brief Given a text string, convert from bbcode to html and add smilie icons.
 *
 * @param string $text String with bbcode.
 * @return string Formattet HTML.
 */
function prepare_text($text) {
	if (stristr($text, '[nosmile]')) {
		$s = BBCode::convert($text);
	} else {
		$s = Smilies::replace(BBCode::convert($text));
	}

	return trim($s);
}

/**
 * return array with details for categories and folders for an item
 *
 * @param array $item
 * @return array
 *
  * [
 *      [ // categories array
 *          {
 *               'name': 'category name',
 *               'removeurl': 'url to remove this category',
 *               'first': 'is the first in this array? true/false',
 *               'last': 'is the last in this array? true/false',
 *           } ,
 *           ....
 *       ],
 *       [ //folders array
 *			{
 *               'name': 'folder name',
 *               'removeurl': 'url to remove this folder',
 *               'first': 'is the first in this array? true/false',
 *               'last': 'is the last in this array? true/false',
 *           } ,
 *           ....
 *       ]
 *  ]
 */
function get_cats_and_terms($item)
{
	$categories = [];
	$folders = [];

	$matches = false;
	$first = true;
	$cnt = preg_match_all('/<(.*?)>/', $item['file'], $matches, PREG_SET_ORDER);
	if ($cnt) {
		foreach ($matches as $mtch) {
			$categories[] = [
				'name' => XML::escape(FileTag::decode($mtch[1])),
				'url' =>  "#",
				'removeurl' => ((local_user() == $item['uid'])?'filerm/' . $item['id'] . '?f=&cat=' . XML::escape(FileTag::decode($mtch[1])):""),
				'first' => $first,
				'last' => false
			];
			$first = false;
		}
	}

	if (count($categories)) {
		$categories[count($categories) - 1]['last'] = true;
	}

	if (local_user() == $item['uid']) {
		$matches = false;
		$first = true;
		$cnt = preg_match_all('/\[(.*?)\]/', $item['file'], $matches, PREG_SET_ORDER);
		if ($cnt) {
			foreach ($matches as $mtch) {
				$folders[] = [
					'name' => XML::escape(FileTag::decode($mtch[1])),
					'url' =>  "#",
					'removeurl' => ((local_user() == $item['uid']) ? 'filerm/' . $item['id'] . '?f=&term=' . XML::escape(FileTag::decode($mtch[1])) : ""),
					'first' => $first,
					'last' => false
				];
				$first = false;
			}
		}
	}

	if (count($folders)) {
		$folders[count($folders) - 1]['last'] = true;
	}

	return [$categories, $folders];
}


/**
 * get private link for item
 * @param array $item
 * @return boolean|array False if item has not plink, otherwise array('href'=>plink url, 'title'=>translated title)
 */
function get_plink($item) {
	$a = get_app();

	if ($a->user['nickname'] != "") {
		$ret = [
				//'href' => "display/" . $a->user['nickname'] . "/" . $item['id'],
				'href' => "display/" . $item['guid'],
				'orig' => "display/" . $item['guid'],
				'title' => L10n::t('View on separate page'),
				'orig_title' => L10n::t('view on separate page'),
			];

		if (x($item, 'plink')) {
			$ret["href"] = $a->removeBaseURL($item['plink']);
			$ret["title"] = L10n::t('link to source');
		}

	} elseif (x($item, 'plink') && ($item['private'] != 1)) {
		$ret = [
				'href' => $item['plink'],
				'orig' => $item['plink'],
				'title' => L10n::t('link to source'),
			];
	} else {
		$ret = [];
	}

	return $ret;
}

/**
 * return number of bytes in size (K, M, G)
 * @param string $size_str
 * @return number
 */
function return_bytes($size_str) {
	switch (substr ($size_str, -1)) {
		case 'M': case 'm': return (int)$size_str * 1048576;
		case 'K': case 'k': return (int)$size_str * 1024;
		case 'G': case 'g': return (int)$size_str * 1073741824;
		default: return $size_str;
	}
}

/**
 * @param string $s
 * @param boolean $strip_padding
 * @return string
 */
function base64url_encode($s, $strip_padding = false) {

	$s = strtr(base64_encode($s), '+/', '-_');

	if ($strip_padding) {
		$s = str_replace('=','',$s);
	}

	return $s;
}

/**
 * @param string $s
 * @return string
 */
function base64url_decode($s) {

	if (is_array($s)) {
		Logger::log('base64url_decode: illegal input: ' . print_r(debug_backtrace(), true));
		return $s;
	}

/*
 *  // Placeholder for new rev of salmon which strips base64 padding.
 *  // PHP base64_decode handles the un-padded input without requiring this step
 *  // Uncomment if you find you need it.
 *
 *	$l = strlen($s);
 *	if (!strpos($s,'=')) {
 *		$m = $l % 4;
 *		if ($m == 2)
 *			$s .= '==';
 *		if ($m == 3)
 *			$s .= '=';
 *	}
 *
 */

	return base64_decode(strtr($s,'-_','+/'));
}


function bb_translate_video($s) {

	$matches = null;
	$r = preg_match_all("/\[video\](.*?)\[\/video\]/ism",$s,$matches,PREG_SET_ORDER);
	if ($r) {
		foreach ($matches as $mtch) {
			if ((stristr($mtch[1], 'youtube')) || (stristr($mtch[1], 'youtu.be'))) {
				$s = str_replace($mtch[0], '[youtube]' . $mtch[1] . '[/youtube]', $s);
			} elseif (stristr($mtch[1], 'vimeo')) {
				$s = str_replace($mtch[0], '[vimeo]' . $mtch[1] . '[/vimeo]', $s);
			}
		}
	}
	return $s;
}

/**
 * get translated item type
 *
 * @param array $itme
 * @return string
 */
function item_post_type($item) {
	if (!empty($item['event-id'])) {
		return L10n::t('event');
	} elseif (!empty($item['resource-id'])) {
		return L10n::t('photo');
	} elseif (!empty($item['verb']) && $item['verb'] !== ACTIVITY_POST) {
		return L10n::t('activity');
	} elseif ($item['id'] != $item['parent']) {
		return L10n::t('comment');
	}

	return L10n::t('post');
}

function normalise_openid($s) {
	return trim(str_replace(['http://', 'https://'], ['', ''], $s), '/');
}


function undo_post_tagging($s) {
	$matches = null;
	$cnt = preg_match_all('/([!#@])\[url=(.*?)\](.*?)\[\/url\]/ism', $s, $matches, PREG_SET_ORDER);
	if ($cnt) {
		foreach ($matches as $mtch) {
			if (in_array($mtch[1], ['!', '@'])) {
				$contact = Contact::getDetailsByURL($mtch[2]);
				$mtch[3] = empty($contact['addr']) ? $mtch[2] : $contact['addr'];
			}
			$s = str_replace($mtch[0], $mtch[1] . $mtch[3],$s);
		}
	}
	return $s;
}

function protect_sprintf($s) {
	return str_replace('%', '%%', $s);
}

/// @TODO Rewrite this
function is_a_date_arg($s) {
	$i = intval($s);

	if ($i > 1900) {
		$y = date('Y');

		if ($i <= $y + 1 && strpos($s, '-') == 4) {
			$m = intval(substr($s, 5));

			if ($m > 0 && $m <= 12) {
				return true;
			}
		}
	}

	return false;
}

/**
 * remove intentation from a text
 */
function deindent($text, $chr = "[\t ]", $count = NULL) {
	$lines = explode("\n", $text);

	if (is_null($count)) {
		$m = [];
		$k = 0;
		while ($k < count($lines) && strlen($lines[$k]) == 0) {
			$k++;
		}
		preg_match("|^" . $chr . "*|", $lines[$k], $m);
		$count = strlen($m[0]);
	}

	for ($k = 0; $k < count($lines); $k++) {
		$lines[$k] = preg_replace("|^" . $chr . "{" . $count . "}|", "", $lines[$k]);
	}

	return implode("\n", $lines);
}

function formatBytes($bytes, $precision = 2) {
	$units = ['B', 'KB', 'MB', 'GB', 'TB'];

	$bytes = max($bytes, 0);
	$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
	$pow = min($pow, count($units) - 1);

	$bytes /= pow(1024, $pow);

	return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * @brief translate and format the networkname of a contact
 *
 * @param string $network
 *	Networkname of the contact (e.g. dfrn, rss and so on)
 * @param sting $url
 *	The contact url
 * @return string
 */
function format_network_name($network, $url = 0) {
	if ($network != "") {
		if ($url != "") {
			$network_name = '<a href="'.$url.'">'.ContactSelector::networkToName($network, $url)."</a>";
		} else {
			$network_name = ContactSelector::networkToName($network);
		}

		return $network_name;
	}
}
