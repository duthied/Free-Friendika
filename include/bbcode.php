<?php
/**
 * @file include/bbcode.php
 */

use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\Plaintext;
use Friendica\Core\Network;

require_once 'include/event.php';
require_once 'mod/proxy.php';

function bb_remove_share_information($Text, $plaintext = false, $nolink = false) {
	return BBCode::removeShareInformation($Text, $plaintext, $nolink);
}

function bb_find_open_close($s, $open, $close, $occurence = 1) {
	return Plaintext::getBoundariesPosition($s, $open, $close, $occurence - 1);
}

function get_bb_tag_pos($s, $name, $occurence = 1) {
	return BBCode::getTagPosition($s, $name, $occurence - 1);
}

function bb_tag_preg_replace($pattern, $replace, $name, $s) {
	return BBCode::pregReplaceInTag($pattern, $replace, $name, $s);
}

function bb_extract_images($body) {
	return BBCode::extractImagesFromItemBody($body);
}

function bb_replace_images($body, $images) {
	return BBCode::interpolateSavedImagesIntoItemBody($body, $images);
}

function bb_ShareAttributes($share, $simplehtml) {
	return BBCode::convertShare($share, $simplehtml);
}

function GetProfileUsername($profile, $username, $compact = false, $getnetwork = false) {
	if ($getnetwork) {
		return Network::matchByProfileUrl($profile);
	} elseif ($compact) {
		return Network::getAddrFromProfileUrl($profile);
	} else {
		return Network::formatMention($profile, $username);
	}
}

function bb_CleanPictureLinks($text) {
	return BBCode::cleanPictureLinks($text);
}

function bbcode($Text, $preserve_nl = false, $tryoembed = true, $simplehtml = false, $forplaintext = false)
{
	return BBCode::convert($Text, $preserve_nl, $tryoembed, $simplehtml, $forplaintext);
}

function remove_abstract($text) {
	return BBCode::removeAbstract($text);
}

function fetch_abstract($text, $addon = "") {
	return BBCode::getAbstract($text, $addon);
}
