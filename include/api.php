<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * Friendica implementation of statusnet/twitter API
 *
 * @file include/api.php
 * @todo Automatically detect if incoming data is HTML or BBCode
 */

use Friendica\App;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Model\Item;
use Friendica\Model\Mail;
use Friendica\Model\Photo;
use Friendica\Model\Post;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Network\HTTPException\TooManyRequestsException;
use Friendica\Network\HTTPException\UnauthorizedException;
use Friendica\Object\Image;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Images;
use Friendica\Util\Network;
use Friendica\Util\Strings;

require_once __DIR__ . '/../mod/item.php';
require_once __DIR__ . '/../mod/wall_upload.php';

$API = [];

/**
 * Register a function to be the endpoint for defined API path.
 *
 * @param string $path   API URL path, relative to DI::baseUrl()
 * @param string $func   Function name to call on path request
 */
function api_register_func($path, $func)
{
	global $API;

	$API[$path] = [
		'func'   => $func,
	];

	// Workaround for hotot
	$path = str_replace("api/", "api/1.1/", $path);

	$API[$path] = [
		'func'   => $func,
	];
}

/**
 * Main API entry point
 *
 * Authenticate user, call registered API function, set HTTP headers
 *
 * @param App\Arguments $args The app arguments (optional, will retrieved by the DI-Container in case of missing)
 * @return string|array API call result
 * @throws Exception
 */
function api_call($command, $extension)
{
	global $API;

	Logger::info('Legacy API call', ['command' => $command, 'extension' => $extension]);

	try {
		foreach ($API as $p => $info) {
			if (strpos($command, $p) === 0) {
				Logger::debug(BaseApi::LOG_PREFIX . 'parameters', ['module' => 'api', 'action' => 'call', 'parameters' => $_REQUEST]);

				$stamp =  microtime(true);
				$return = call_user_func($info['func'], $extension);
				$duration = floatval(microtime(true) - $stamp);

				Logger::info(BaseApi::LOG_PREFIX . 'duration {duration}', ['module' => 'api', 'action' => 'call', 'duration' => round($duration, 2)]);

				DI::profiler()->saveLog(DI::logger(), BaseApi::LOG_PREFIX . 'performance');

				if (false === $return) {
					/*
						* api function returned false withour throw an
						* exception. This should not happend, throw a 500
						*/
					throw new InternalServerErrorException();
				}

				switch ($extension) {
					case "xml":
						header("Content-Type: text/xml");
						break;
					case "json":
						header("Content-Type: application/json");
						if (!empty($return)) {
							$json = json_encode(end($return));
							if (!empty($_GET['callback'])) {
								$json = $_GET['callback'] . "(" . $json . ")";
							}
							$return = $json;
						}
						break;
					case "rss":
						header("Content-Type: application/rss+xml");
						$return  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $return;
						break;
					case "atom":
						header("Content-Type: application/atom+xml");
						$return = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $return;
						break;
				}
				return $return;
			}
		}

		Logger::warning(BaseApi::LOG_PREFIX . 'not implemented', ['module' => 'api', 'action' => 'call', 'query' => DI::args()->getQueryString()]);
		throw new NotFoundException();
	} catch (HTTPException $e) {
		Logger::notice(BaseApi::LOG_PREFIX . 'got exception', ['module' => 'api', 'action' => 'call', 'query' => DI::args()->getQueryString(), 'error' => $e]);
		DI::apiResponse()->error($e->getCode(), $e->getDescription(), $e->getMessage(), $extension);
	}
}

/**
 *
 * @param array $item
 * @param array $recipient
 * @param array $sender
 *
 * @return array
 * @throws InternalServerErrorException
 */
function api_format_messages($item, $recipient, $sender)
{
	// standard meta information
	$ret = [
		'id'                    => $item['id'],
		'sender_id'             => $sender['id'],
		'text'                  => "",
		'recipient_id'          => $recipient['id'],
		'created_at'            => DateTimeFormat::utc($item['created'] ?? 'now', DateTimeFormat::API),
		'sender_screen_name'    => $sender['screen_name'],
		'recipient_screen_name' => $recipient['screen_name'],
		'sender'                => $sender,
		'recipient'             => $recipient,
		'title'                 => "",
		'friendica_seen'        => $item['seen'] ?? 0,
		'friendica_parent_uri'  => $item['parent-uri'] ?? '',
	];

	// "uid" is only needed for some internal stuff, so remove it from here
	if (isset($ret['sender']['uid'])) {
		unset($ret['sender']['uid']);
	}
	if (isset($ret['recipient']['uid'])) {
		unset($ret['recipient']['uid']);
	}

	//don't send title to regular StatusNET requests to avoid confusing these apps
	if (!empty($_GET['getText'])) {
		$ret['title'] = $item['title'];
		if ($_GET['getText'] == 'html') {
			$ret['text'] = BBCode::convertForUriId($item['uri-id'], $item['body'], BBCode::API);
		} elseif ($_GET['getText'] == 'plain') {
			$ret['text'] = trim(HTML::toPlaintext(BBCode::convertForUriId($item['uri-id'], api_clean_plain_items($item['body']), BBCode::API), 0));
		}
	} else {
		$ret['text'] = $item['title'] . "\n" . HTML::toPlaintext(BBCode::convertForUriId($item['uri-id'], api_clean_plain_items($item['body']), BBCode::API), 0);
	}
	if (!empty($_GET['getUserObjects']) && $_GET['getUserObjects'] == 'false') {
		unset($ret['sender']);
		unset($ret['recipient']);
	}

	return $ret;
}

/**
 *
 * @param string $acl_string
 * @param int    $uid
 * @return bool
 * @throws Exception
 */
function check_acl_input($acl_string, $uid)
{
	if (empty($acl_string)) {
		return false;
	}

	$contact_not_found = false;

	// split <x><y><z> into array of cid's
	preg_match_all("/<[A-Za-z0-9]+>/", $acl_string, $array);

	// check for each cid if it is available on server
	$cid_array = $array[0];
	foreach ($cid_array as $cid) {
		$cid = str_replace("<", "", $cid);
		$cid = str_replace(">", "", $cid);
		$condition = ['id' => $cid, 'uid' => $uid];
		$contact_not_found |= !DBA::exists('contact', $condition);
	}
	return $contact_not_found;
}

/**
 * @param string  $mediatype
 * @param array   $media
 * @param string  $type
 * @param string  $album
 * @param string  $allow_cid
 * @param string  $deny_cid
 * @param string  $allow_gid
 * @param string  $deny_gid
 * @param string  $desc
 * @param integer $phototype
 * @param boolean $visibility
 * @param string  $photo_id
 * @param int     $uid
 * @return array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws NotFoundException
 * @throws UnauthorizedException
 */
function save_media_to_database($mediatype, $media, $type, $album, $allow_cid, $deny_cid, $allow_gid, $deny_gid, $desc, $phototype, $visibility, $photo_id, $uid)
{
	$visitor   = 0;
	$src = "";
	$filetype = "";
	$filename = "";
	$filesize = 0;

	if (is_array($media)) {
		if (is_array($media['tmp_name'])) {
			$src = $media['tmp_name'][0];
		} else {
			$src = $media['tmp_name'];
		}
		if (is_array($media['name'])) {
			$filename = basename($media['name'][0]);
		} else {
			$filename = basename($media['name']);
		}
		if (is_array($media['size'])) {
			$filesize = intval($media['size'][0]);
		} else {
			$filesize = intval($media['size']);
		}
		if (is_array($media['type'])) {
			$filetype = $media['type'][0];
		} else {
			$filetype = $media['type'];
		}
	}

	$filetype = Images::getMimeTypeBySource($src, $filename, $filetype);

	logger::info(
		"File upload src: " . $src . " - filename: " . $filename .
		" - size: " . $filesize . " - type: " . $filetype);

	// check if there was a php upload error
	if ($filesize == 0 && $media['error'] == 1) {
		throw new InternalServerErrorException("image size exceeds PHP config settings, file was rejected by server");
	}
	// check against max upload size within Friendica instance
	$maximagesize = DI::config()->get('system', 'maximagesize');
	if ($maximagesize && ($filesize > $maximagesize)) {
		$formattedBytes = Strings::formatBytes($maximagesize);
		throw new InternalServerErrorException("image size exceeds Friendica config setting (uploaded size: $formattedBytes)");
	}

	// create Photo instance with the data of the image
	$imagedata = @file_get_contents($src);
	$Image = new Image($imagedata, $filetype);
	if (!$Image->isValid()) {
		throw new InternalServerErrorException("unable to process image data");
	}

	// check orientation of image
	$Image->orient($src);
	@unlink($src);

	// check max length of images on server
	$max_length = DI::config()->get('system', 'max_image_length');
	if ($max_length > 0) {
		$Image->scaleDown($max_length);
		logger::info("File upload: Scaling picture to new size " . $max_length);
	}
	$width = $Image->getWidth();
	$height = $Image->getHeight();

	// create a new resource-id if not already provided
	$resource_id = ($photo_id == null) ? Photo::newResource() : $photo_id;

	if ($mediatype == "photo") {
		// upload normal image (scales 0, 1, 2)
		logger::info("photo upload: starting new photo upload");

		$r = Photo::store($Image, $uid, $visitor, $resource_id, $filename, $album, 0, Photo::DEFAULT, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $desc);
		if (!$r) {
			logger::notice("photo upload: image upload with scale 0 (original size) failed");
		}
		if ($width > 640 || $height > 640) {
			$Image->scaleDown(640);
			$r = Photo::store($Image, $uid, $visitor, $resource_id, $filename, $album, 1, Photo::DEFAULT, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $desc);
			if (!$r) {
				logger::notice("photo upload: image upload with scale 1 (640x640) failed");
			}
		}

		if ($width > 320 || $height > 320) {
			$Image->scaleDown(320);
			$r = Photo::store($Image, $uid, $visitor, $resource_id, $filename, $album, 2, Photo::DEFAULT, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $desc);
			if (!$r) {
				logger::notice("photo upload: image upload with scale 2 (320x320) failed");
			}
		}
		logger::info("photo upload: new photo upload ended");
	} elseif ($mediatype == "profileimage") {
		// upload profile image (scales 4, 5, 6)
		logger::info("photo upload: starting new profile image upload");

		if ($width > 300 || $height > 300) {
			$Image->scaleDown(300);
			$r = Photo::store($Image, $uid, $visitor, $resource_id, $filename, $album, 4, $phototype, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $desc);
			if (!$r) {
				logger::notice("photo upload: profile image upload with scale 4 (300x300) failed");
			}
		}

		if ($width > 80 || $height > 80) {
			$Image->scaleDown(80);
			$r = Photo::store($Image, $uid, $visitor, $resource_id, $filename, $album, 5, $phototype, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $desc);
			if (!$r) {
				logger::notice("photo upload: profile image upload with scale 5 (80x80) failed");
			}
		}

		if ($width > 48 || $height > 48) {
			$Image->scaleDown(48);
			$r = Photo::store($Image, $uid, $visitor, $resource_id, $filename, $album, 6, $phototype, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $desc);
			if (!$r) {
				logger::notice("photo upload: profile image upload with scale 6 (48x48) failed");
			}
		}
		$Image->__destruct();
		logger::info("photo upload: new profile image upload ended");
	}

	if (!empty($r)) {
		// create entry in 'item'-table on new uploads to enable users to comment/like/dislike the photo
		if ($photo_id == null && $mediatype == "photo") {
			post_photo_item($resource_id, $allow_cid, $deny_cid, $allow_gid, $deny_gid, $filetype, $visibility, $uid);
		}
		// on success return image data in json/xml format (like /api/friendica/photo does when no scale is given)
		return prepare_photo_data($type, false, $resource_id, $uid);
	} else {
		throw new InternalServerErrorException("image upload failed");
	}
}

/**
 *
 * @param string  $hash
 * @param string  $allow_cid
 * @param string  $deny_cid
 * @param string  $allow_gid
 * @param string  $deny_gid
 * @param string  $filetype
 * @param boolean $visibility
 * @param int     $uid
 * @throws InternalServerErrorException
 */
function post_photo_item($hash, $allow_cid, $deny_cid, $allow_gid, $deny_gid, $filetype, $visibility, $uid)
{
	// get data about the api authenticated user
	$uri = Item::newURI(intval($uid));
	$owner_record = DBA::selectFirst('contact', [], ['uid' => $uid, 'self' => true]);

	$arr = [];
	$arr['guid']          = System::createUUID();
	$arr['uid']           = intval($uid);
	$arr['uri']           = $uri;
	$arr['type']          = 'photo';
	$arr['wall']          = 1;
	$arr['resource-id']   = $hash;
	$arr['contact-id']    = $owner_record['id'];
	$arr['owner-name']    = $owner_record['name'];
	$arr['owner-link']    = $owner_record['url'];
	$arr['owner-avatar']  = $owner_record['thumb'];
	$arr['author-name']   = $owner_record['name'];
	$arr['author-link']   = $owner_record['url'];
	$arr['author-avatar'] = $owner_record['thumb'];
	$arr['title']         = "";
	$arr['allow_cid']     = $allow_cid;
	$arr['allow_gid']     = $allow_gid;
	$arr['deny_cid']      = $deny_cid;
	$arr['deny_gid']      = $deny_gid;
	$arr['visible']       = $visibility;
	$arr['origin']        = 1;

	$typetoext = [
			'image/jpeg' => 'jpg',
			'image/png' => 'png',
			'image/gif' => 'gif'
			];

	// adds link to the thumbnail scale photo
	$arr['body'] = '[url=' . DI::baseUrl() . '/photos/' . $owner_record['nick'] . '/image/' . $hash . ']'
				. '[img]' . DI::baseUrl() . '/photo/' . $hash . '-' . "2" . '.'. $typetoext[$filetype] . '[/img]'
				. '[/url]';

	// do the magic for storing the item in the database and trigger the federation to other contacts
	Item::insert($arr);
}

/**
 *
 * @param string $type
 * @param int    $scale
 * @param string $photo_id
 *
 * @return array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws NotFoundException
 * @throws UnauthorizedException
 */
function prepare_photo_data($type, $scale, $photo_id, $uid)
{
	$scale_sql = ($scale === false ? "" : sprintf("AND scale=%d", intval($scale)));
	$data_sql = ($scale === false ? "" : "data, ");

	// added allow_cid, allow_gid, deny_cid, deny_gid to output as string like stored in database
	// clients needs to convert this in their way for further processing
	$r = DBA::toArray(DBA::p(
		"SELECT $data_sql `resource-id`, `created`, `edited`, `title`, `desc`, `album`, `filename`,
					`type`, `height`, `width`, `datasize`, `profile`, `allow_cid`, `deny_cid`, `allow_gid`, `deny_gid`,
					MIN(`scale`) AS `minscale`, MAX(`scale`) AS `maxscale`
			FROM `photo` WHERE `uid` = ? AND `resource-id` = ? $scale_sql GROUP BY
				   `resource-id`, `created`, `edited`, `title`, `desc`, `album`, `filename`,
				   `type`, `height`, `width`, `datasize`, `profile`, `allow_cid`, `deny_cid`, `allow_gid`, `deny_gid`",
		$uid,
		$photo_id
	));

	$typetoext = [
		'image/jpeg' => 'jpg',
		'image/png' => 'png',
		'image/gif' => 'gif'
	];

	// prepare output data for photo
	if (DBA::isResult($r)) {
		$data = ['photo' => $r[0]];
		$data['photo']['id'] = $data['photo']['resource-id'];
		if ($scale !== false) {
			$data['photo']['data'] = base64_encode($data['photo']['data']);
		} else {
			unset($data['photo']['datasize']); //needed only with scale param
		}
		if ($type == "xml") {
			$data['photo']['links'] = [];
			for ($k = intval($data['photo']['minscale']); $k <= intval($data['photo']['maxscale']); $k++) {
				$data['photo']['links'][$k . ":link"]["@attributes"] = ["type" => $data['photo']['type'],
										"scale" => $k,
										"href" => DI::baseUrl() . "/photo/" . $data['photo']['resource-id'] . "-" . $k . "." . $typetoext[$data['photo']['type']]];
			}
		} else {
			$data['photo']['link'] = [];
			// when we have profile images we could have only scales from 4 to 6, but index of array always needs to start with 0
			$i = 0;
			for ($k = intval($data['photo']['minscale']); $k <= intval($data['photo']['maxscale']); $k++) {
				$data['photo']['link'][$i] = DI::baseUrl() . "/photo/" . $data['photo']['resource-id'] . "-" . $k . "." . $typetoext[$data['photo']['type']];
				$i++;
			}
		}
		unset($data['photo']['resource-id']);
		unset($data['photo']['minscale']);
		unset($data['photo']['maxscale']);
	} else {
		throw new NotFoundException();
	}

	// retrieve item element for getting activities (like, dislike etc.) related to photo
	$condition = ['uid' => $uid, 'resource-id' => $photo_id];
	$item = Post::selectFirst(['id', 'uid', 'uri', 'parent', 'allow_cid', 'deny_cid', 'allow_gid', 'deny_gid'], $condition);
	if (!DBA::isResult($item)) {
		throw new NotFoundException('Photo-related item not found.');
	}

	$data['photo']['friendica_activities'] = DI::friendicaActivities()->createFromUriId($item['uri-id'], $item['uid'], $type);

	// retrieve comments on photo
	$condition = ["`parent` = ? AND `uid` = ? AND `gravity` IN (?, ?)",
		$item['parent'], $uid, GRAVITY_PARENT, GRAVITY_COMMENT];

	$statuses = Post::selectForUser($uid, [], $condition);

	// prepare output of comments
	$commentData = [];
	while ($status = DBA::fetch($statuses)) {
		$commentData[] = DI::twitterStatus()->createFromUriId($status['uri-id'], $status['uid'])->toArray();
	}
	DBA::close($statuses);

	$comments = [];
	if ($type == "xml") {
		$k = 0;
		foreach ($commentData as $comment) {
			$comments[$k++ . ":comment"] = $comment;
		}
	} else {
		foreach ($commentData as $comment) {
			$comments[] = $comment;
		}
	}
	$data['photo']['friendica_comments'] = $comments;

	// include info if rights on photo and rights on item are mismatching
	$rights_mismatch = $data['photo']['allow_cid'] != $item['allow_cid'] ||
		$data['photo']['deny_cid'] != $item['deny_cid'] ||
		$data['photo']['allow_gid'] != $item['allow_gid'] ||
		$data['photo']['deny_gid'] != $item['deny_gid'];
	$data['photo']['rights_mismatch'] = $rights_mismatch;

	return $data;
}

/**
 *
 * @param string $text
 *
 * @return string
 * @throws InternalServerErrorException
 */
function api_clean_plain_items($text)
{
	$include_entities = strtolower($_REQUEST['include_entities'] ?? 'false');

	$text = BBCode::cleanPictureLinks($text);
	$URLSearchString = "^\[\]";

	$text = preg_replace("/([!#@])\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", '$1$3', $text);

	if ($include_entities == "true") {
		$text = preg_replace("/\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", '[url=$1]$1[/url]', $text);
	}

	// Simplify "attachment" element
	$text = BBCode::removeAttachment($text);

	return $text;
}

/**
 * Add a new group to the database.
 *
 * @param  string $name  Group name
 * @param  int    $uid   User ID
 * @param  array  $users List of users to add to the group
 *
 * @return array
 * @throws BadRequestException
 */
function group_create($name, $uid, $users = [])
{
	// error if no name specified
	if ($name == "") {
		throw new BadRequestException('group name not specified');
	}

	// error message if specified group name already exists
	if (DBA::exists('group', ['uid' => $uid, 'name' => $name, 'deleted' => false])) {
		throw new BadRequestException('group name already exists');
	}

	// Check if the group needs to be reactivated
	if (DBA::exists('group', ['uid' => $uid, 'name' => $name, 'deleted' => true])) {
		$reactivate_group = true;
	}

	// create group
	$ret = Group::create($uid, $name);
	if ($ret) {
		$gid = Group::getIdByName($uid, $name);
	} else {
		throw new BadRequestException('other API error');
	}

	// add members
	$erroraddinguser = false;
	$errorusers = [];
	foreach ($users as $user) {
		$cid = $user['cid'];
		if (DBA::exists('contact', ['id' => $cid, 'uid' => $uid])) {
			Group::addMember($gid, $cid);
		} else {
			$erroraddinguser = true;
			$errorusers[] = $cid;
		}
	}

	// return success message incl. missing users in array
	$status = ($erroraddinguser ? "missing user" : ((isset($reactivate_group) && $reactivate_group) ? "reactivated" : "ok"));

	return ['success' => true, 'gid' => $gid, 'name' => $name, 'status' => $status, 'wrong users' => $errorusers];
}

/**
 * TWITTER API
 */

/**
 * Deprecated function to upload media.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_statuses_mediap($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
	$uid = BaseApi::getCurrentUserID();

	$a = DI::app();

	$_REQUEST['profile_uid'] = $uid;
	$_REQUEST['api_source'] = true;
	$txt = $_REQUEST['status'] ?? '';

	if ((strpos($txt, '<') !== false) || (strpos($txt, '>') !== false)) {
		$txt = HTML::toBBCodeVideo($txt);
		$config = HTMLPurifier_Config::createDefault();
		$config->set('Cache.DefinitionImpl', null);
		$purifier = new HTMLPurifier($config);
		$txt = $purifier->purify($txt);
	}
	$txt = HTML::toBBCode($txt);

	$picture = wall_upload_post($a, false);

	// now that we have the img url in bbcode we can add it to the status and insert the wall item.
	$_REQUEST['body'] = $txt . "\n\n" . '[url=' . $picture["albumpage"] . '][img]' . $picture["preview"] . "[/img][/url]";
	$item_id = item_post($a);

	$include_entities = strtolower(($_REQUEST['include_entities'] ?? 'false') == 'true');

	// output the post that we just posted.
	$status_info = DI::twitterStatus()->createFromItemId($item_id, $include_entities)->toArray();
	return DI::apiResponse()->formatData('statuses', $type, ['status' => $status_info]);
}

/// @TODO move this to top of file or somewhere better!
api_register_func('api/statuses/mediap', 'api_statuses_mediap', true);

/**
 * Updates the user’s current status.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws TooManyRequestsException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/post-statuses-update
 */
function api_statuses_update($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
	$uid = BaseApi::getCurrentUserID();

	$a = DI::app();

	// convert $_POST array items to the form we use for web posts.
	if (!empty($_REQUEST['htmlstatus'])) {
		$txt = $_REQUEST['htmlstatus'];
		if ((strpos($txt, '<') !== false) || (strpos($txt, '>') !== false)) {
			$txt = HTML::toBBCodeVideo($txt);

			$config = HTMLPurifier_Config::createDefault();
			$config->set('Cache.DefinitionImpl', null);

			$purifier = new HTMLPurifier($config);
			$txt = $purifier->purify($txt);

			$_REQUEST['body'] = HTML::toBBCode($txt);
		}
	} else {
		$_REQUEST['body'] = $_REQUEST['status'] ?? null;
	}

	$_REQUEST['title'] = $_REQUEST['title'] ?? null;

	$parent = $_REQUEST['in_reply_to_status_id'] ?? null;

	// Twidere sends "-1" if it is no reply ...
	if ($parent == -1) {
		$parent = "";
	}

	if (ctype_digit($parent)) {
		$_REQUEST['parent'] = $parent;
	} else {
		$_REQUEST['parent_uri'] = $parent;
	}

	if (!empty($_REQUEST['lat']) && !empty($_REQUEST['long'])) {
		$_REQUEST['coord'] = sprintf("%s %s", $_REQUEST['lat'], $_REQUEST['long']);
	}
	$_REQUEST['profile_uid'] = $uid;

	if (!$parent) {
		// Check for throttling (maximum posts per day, week and month)
		$throttle_day = DI::config()->get('system', 'throttle_limit_day');
		if ($throttle_day > 0) {
			$datefrom = date(DateTimeFormat::MYSQL, time() - 24*60*60);

			$condition = ["`gravity` = ? AND `uid` = ? AND `wall` AND `received` > ?", GRAVITY_PARENT, $uid, $datefrom];
			$posts_day = Post::count($condition);

			if ($posts_day > $throttle_day) {
				logger::info('Daily posting limit reached for user ' . $uid);
				// die(api_error($type, DI::l10n()->t("Daily posting limit of %d posts reached. The post was rejected.", $throttle_day));
				throw new TooManyRequestsException(DI::l10n()->tt("Daily posting limit of %d post reached. The post was rejected.", "Daily posting limit of %d posts reached. The post was rejected.", $throttle_day));
			}
		}

		$throttle_week = DI::config()->get('system', 'throttle_limit_week');
		if ($throttle_week > 0) {
			$datefrom = date(DateTimeFormat::MYSQL, time() - 24*60*60*7);

			$condition = ["`gravity` = ? AND `uid` = ? AND `wall` AND `received` > ?", GRAVITY_PARENT, $uid, $datefrom];
			$posts_week = Post::count($condition);

			if ($posts_week > $throttle_week) {
				logger::info('Weekly posting limit reached for user ' . $uid);
				// die(api_error($type, DI::l10n()->t("Weekly posting limit of %d posts reached. The post was rejected.", $throttle_week)));
				throw new TooManyRequestsException(DI::l10n()->tt("Weekly posting limit of %d post reached. The post was rejected.", "Weekly posting limit of %d posts reached. The post was rejected.", $throttle_week));
			}
		}

		$throttle_month = DI::config()->get('system', 'throttle_limit_month');
		if ($throttle_month > 0) {
			$datefrom = date(DateTimeFormat::MYSQL, time() - 24*60*60*30);

			$condition = ["`gravity` = ? AND `uid` = ? AND `wall` AND `received` > ?", GRAVITY_PARENT, $uid, $datefrom];
			$posts_month = Post::count($condition);

			if ($posts_month > $throttle_month) {
				logger::info('Monthly posting limit reached for user ' . $uid);
				// die(api_error($type, DI::l10n()->t("Monthly posting limit of %d posts reached. The post was rejected.", $throttle_month));
				throw new TooManyRequestsException(DI::l10n()->t("Monthly posting limit of %d post reached. The post was rejected.", "Monthly posting limit of %d posts reached. The post was rejected.", $throttle_month));
			}
		}
	}

	if (!empty($_REQUEST['media_ids'])) {
		$ids = explode(',', $_REQUEST['media_ids']);
	} elseif (!empty($_FILES['media'])) {
		// upload the image if we have one
		$picture = wall_upload_post($a, false);
		if (is_array($picture)) {
			$ids[] = $picture['id'];
		}
	}

	$attachments = [];
	$ressources = [];

	if (!empty($ids)) {
		foreach ($ids as $id) {
			$media = DBA::toArray(DBA::p("SELECT `resource-id`, `scale`, `nickname`, `type`, `desc`, `filename`, `datasize`, `width`, `height` FROM `photo`
					INNER JOIN `user` ON `user`.`uid` = `photo`.`uid` WHERE `resource-id` IN
						(SELECT `resource-id` FROM `photo` WHERE `id` = ?) AND `photo`.`uid` = ?
					ORDER BY `photo`.`width` DESC LIMIT 2", $id, $uid));

			if (!empty($media)) {
				$ressources[] = $media[0]['resource-id'];
				$phototypes = Images::supportedTypes();
				$ext = $phototypes[$media[0]['type']];

				$attachment = ['type' => Post\Media::IMAGE, 'mimetype' => $media[0]['type'],
					'url' => DI::baseUrl() . '/photo/' . $media[0]['resource-id'] . '-' . $media[0]['scale'] . '.' . $ext,
					'size' => $media[0]['datasize'],
					'name' => $media[0]['filename'] ?: $media[0]['resource-id'],
					'description' => $media[0]['desc'] ?? '',
					'width' => $media[0]['width'],
					'height' => $media[0]['height']];

				if (count($media) > 1) {
					$attachment['preview'] = DI::baseUrl() . '/photo/' . $media[1]['resource-id'] . '-' . $media[1]['scale'] . '.' . $ext;
					$attachment['preview-width'] = $media[1]['width'];
					$attachment['preview-height'] = $media[1]['height'];
				}
				$attachments[] = $attachment;
			}
		}

		// We have to avoid that the post is rejected because of an empty body
		if (empty($_REQUEST['body'])) {
			$_REQUEST['body'] = '[hr]';
		}
	}

	if (!empty($attachments)) {
		$_REQUEST['attachments'] = $attachments;
	}

	// set this so that the item_post() function is quiet and doesn't redirect or emit json

	$_REQUEST['api_source'] = true;

	if (empty($_REQUEST['source'])) {
		$_REQUEST['source'] = BaseApi::getCurrentApplication()['name'] ?: 'API';
	}

	// call out normal post function
	$item_id = item_post($a);

	if (!empty($ressources) && !empty($item_id)) {
		$item = Post::selectFirst(['uri-id', 'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid'], ['id' => $item_id]);
		foreach ($ressources as $ressource) {
			Photo::setPermissionForRessource($ressource, $uid, $item['allow_cid'], $item['allow_gid'], $item['deny_cid'], $item['deny_gid']);
		}
	}

	$include_entities = strtolower(($_REQUEST['include_entities'] ?? 'false') == 'true');

	// output the post that we just posted.
	$status_info = DI::twitterStatus()->createFromItemId($item_id, $include_entities)->toArray();
	return DI::apiResponse()->formatData('statuses', $type, ['status' => $status_info]);
}

api_register_func('api/statuses/update', 'api_statuses_update', true);
api_register_func('api/statuses/update_with_media', 'api_statuses_update', true);

/**
 * Uploads an image to Friendica.
 *
 * @return array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/media/upload-media/api-reference/post-media-upload
 */
function api_media_upload()
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);

	if (empty($_FILES['media'])) {
		// Output error
		throw new BadRequestException("No media.");
	}

	$media = wall_upload_post(DI::app(), false);
	if (!$media) {
		// Output error
		throw new InternalServerErrorException();
	}

	$returndata = [];
	$returndata["media_id"] = $media["id"];
	$returndata["media_id_string"] = (string)$media["id"];
	$returndata["size"] = $media["size"];
	$returndata["image"] = ["w" => $media["width"],
				"h" => $media["height"],
				"image_type" => $media["type"],
				"friendica_preview_url" => $media["preview"]];

	Logger::info('Media uploaded', ['return' => $returndata]);

	return ["media" => $returndata];
}

api_register_func('api/media/upload', 'api_media_upload', true);

/**
 * Updates media meta data (picture descriptions)
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws TooManyRequestsException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/post-statuses-update
 *
 * @todo Compare the corresponding Twitter function for correct return values
 */
function api_media_metadata_create($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
	$uid = BaseApi::getCurrentUserID();

	$postdata = Network::postdata();

	if (empty($postdata)) {
		throw new BadRequestException("No post data");
	}

	$data = json_decode($postdata, true);
	if (empty($data)) {
		throw new BadRequestException("Invalid post data");
	}

	if (empty($data['media_id']) || empty($data['alt_text'])) {
		throw new BadRequestException("Missing post data values");
	}

	if (empty($data['alt_text']['text'])) {
		throw new BadRequestException("No alt text.");
	}

	Logger::info('Updating metadata', ['media_id' => $data['media_id']]);

	$condition = ['id' => $data['media_id'], 'uid' => $uid];
	$photo = DBA::selectFirst('photo', ['resource-id'], $condition);
	if (!DBA::isResult($photo)) {
		throw new BadRequestException("Metadata not found.");
	}

	DBA::update('photo', ['desc' => $data['alt_text']['text']], ['resource-id' => $photo['resource-id']]);
}

api_register_func('api/media/metadata/create', 'api_media_metadata_create', true);

/**
 * Repeats a status.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/post-statuses-retweet-id
 */
function api_statuses_repeat($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
	$uid = BaseApi::getCurrentUserID();

	// params
	$id = intval(DI::args()->getArgv()[3] ?? 0);

	if ($id == 0) {
		$id = intval($_REQUEST['id'] ?? 0);
	}

	// Hotot workaround
	if ($id == 0) {
		$id = intval(DI::args()->getArgv()[4] ?? 0);
	}

	logger::notice('API: api_statuses_repeat: ' . $id);

	$fields = ['uri-id', 'network', 'body', 'title', 'author-name', 'author-link', 'author-avatar', 'guid', 'created', 'plink'];
	$item = Post::selectFirst($fields, ['id' => $id, 'private' => [Item::PUBLIC, Item::UNLISTED]]);

	if (DBA::isResult($item) && !empty($item['body'])) {
		if (in_array($item['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::TWITTER])) {
			if (!Item::performActivity($id, 'announce', $uid)) {
				throw new InternalServerErrorException();
			}

			$item_id = $id;
		} else {
			if (strpos($item['body'], "[/share]") !== false) {
				$pos = strpos($item['body'], "[share");
				$post = substr($item['body'], $pos);
			} else {
				$post = BBCode::getShareOpeningTag($item['author-name'], $item['author-link'], $item['author-avatar'], $item['plink'], $item['created'], $item['guid']);

				if (!empty($item['title'])) {
					$post .= '[h3]' . $item['title'] . "[/h3]\n";
				}

				$post .= $item['body'];
				$post .= "[/share]";
			}
			$_REQUEST['body'] = $post;
			$_REQUEST['profile_uid'] = $uid;
			$_REQUEST['api_source'] = true;

			if (empty($_REQUEST['source'])) {
				$_REQUEST['source'] = BaseApi::getCurrentApplication()['name'] ?: 'API';
			}

			$item_id = item_post(DI::app());
		}
	} else {
		throw new ForbiddenException();
	}

	$include_entities = strtolower(($_REQUEST['include_entities'] ?? 'false') == 'true');

	// output the post that we just posted.
	$status_info = DI::twitterStatus()->createFromItemId($item_id, $include_entities)->toArray();
	return DI::apiResponse()->formatData('statuses', $type, ['status' => $status_info]);
}

api_register_func('api/statuses/retweet', 'api_statuses_repeat', true);

/**
 * Star/unstar an item.
 * param: id : id of the item
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see https://web.archive.org/web/20131019055350/https://dev.twitter.com/docs/api/1/post/favorites/create/%3Aid
 */
function api_favorites_create_destroy($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
	$uid = BaseApi::getCurrentUserID();

	// for versioned api.
	/// @TODO We need a better global soluton
	$action_argv_id = 2;
	if (count(DI::args()->getArgv()) > 1 && DI::args()->getArgv()[1] == "1.1") {
		$action_argv_id = 3;
	}

	if (DI::args()->getArgc() <= $action_argv_id) {
		throw new BadRequestException("Invalid request.");
	}
	$action = str_replace("." . $type, "", DI::args()->getArgv()[$action_argv_id]);
	if (DI::args()->getArgc() == $action_argv_id + 2) {
		$itemid = intval(DI::args()->getArgv()[$action_argv_id + 1] ?? 0);
	} else {
		$itemid = intval($_REQUEST['id'] ?? 0);
	}

	$item = Post::selectFirstForUser($uid, [], ['id' => $itemid, 'uid' => $uid]);

	if (!DBA::isResult($item)) {
		throw new BadRequestException("Invalid item.");
	}

	switch ($action) {
		case "create":
			$item['starred'] = 1;
			break;
		case "destroy":
			$item['starred'] = 0;
			break;
		default:
			throw new BadRequestException("Invalid action ".$action);
	}

	$r = Item::update(['starred' => $item['starred']], ['id' => $itemid]);

	if ($r === false) {
		throw new InternalServerErrorException("DB error");
	}

	$include_entities = strtolower(($_REQUEST['include_entities'] ?? 'false') == 'true');

	$ret = DI::twitterStatus()->createFromUriId($item['uri-id'], $item['uid'], $include_entities)->toArray();

	return DI::apiResponse()->formatData("status", $type, ['status' => $ret], Contact::getPublicIdByUserId($uid));
}

api_register_func('api/favorites/create', 'api_favorites_create_destroy', true);
api_register_func('api/favorites/destroy', 'api_favorites_create_destroy', true);

/**
 * Returns all lists the user subscribes to.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/get-lists-list
 */
function api_lists_list($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
	$ret = [];
	/// @TODO $ret is not filled here?
	return DI::apiResponse()->formatData('lists', $type, ["lists_list" => $ret]);
}

api_register_func('api/lists/list', 'api_lists_list', true);
api_register_func('api/lists/subscriptions', 'api_lists_list', true);

/**
 * Returns all groups the user owns.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/get-lists-ownerships
 */
function api_lists_ownerships($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
	$uid = BaseApi::getCurrentUserID();

	// params
	$user_info = DI::twitterUser()->createFromUserId($uid, true)->toArray();

	$groups = DBA::select('group', [], ['deleted' => 0, 'uid' => $uid]);

	// loop through all groups
	$lists = [];
	foreach ($groups as $group) {
		if ($group['visible']) {
			$mode = 'public';
		} else {
			$mode = 'private';
		}
		$lists[] = [
			'name' => $group['name'],
			'id' => intval($group['id']),
			'id_str' => (string) $group['id'],
			'user' => $user_info,
			'mode' => $mode
		];
	}
	return DI::apiResponse()->formatData("lists", $type, ['lists' => ['lists' => $lists]]);
}

api_register_func('api/lists/ownerships', 'api_lists_ownerships', true);

/**
 * Returns either the friends of the follower list
 *
 * Considers friends and followers lists to be private and won't return
 * anything if any user_id parameter is passed.
 *
 * @param string $qtype Either "friends" or "followers"
 * @return boolean|array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_statuses_f($qtype)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
	$uid = BaseApi::getCurrentUserID();

	// pagination
	$count = $_GET['count'] ?? 20;
	$page = $_GET['page'] ?? 1;

	$start = max(0, ($page - 1) * $count);

	if (!empty($_GET['cursor']) && $_GET['cursor'] == 'undefined') {
		/* this is to stop Hotot to load friends multiple times
		*  I'm not sure if I'm missing return something or
		*  is a bug in hotot. Workaround, meantime
		*/

		/*$ret=Array();
		return array('$users' => $ret);*/
		return false;
	}

	$sql_extra = '';
	if ($qtype == 'friends') {
		$sql_extra = sprintf(" AND ( `rel` = %d OR `rel` = %d ) ", intval(Contact::SHARING), intval(Contact::FRIEND));
	} elseif ($qtype == 'followers') {
		$sql_extra = sprintf(" AND ( `rel` = %d OR `rel` = %d ) ", intval(Contact::FOLLOWER), intval(Contact::FRIEND));
	}

	if ($qtype == 'blocks') {
		$sql_filter = 'AND `blocked` AND NOT `pending`';
	} elseif ($qtype == 'incoming') {
		$sql_filter = 'AND `pending`';
	} else {
		$sql_filter = 'AND (NOT `blocked` OR `pending`)';
	}

	// @todo This query most likely can be replaced with a Contact::select...
	$r = DBA::toArray(DBA::p(
		"SELECT `id`
		FROM `contact`
		WHERE `uid` = ?
		AND NOT `self`
		$sql_filter
		$sql_extra
		ORDER BY `nick`
		LIMIT ?, ?",
		$uid,
		$start,
		$count
	));

	$ret = [];
	foreach ($r as $cid) {
		$user = DI::twitterUser()->createFromContactId($cid['id'], $uid, false)->toArray();
		// "uid" is only needed for some internal stuff, so remove it from here
		unset($user['uid']);

		if ($user) {
			$ret[] = $user;
		}
	}

	return ['user' => $ret];
}

/**
 * Returns the list of friends of the provided user
 *
 * @deprecated By Twitter API in favor of friends/list
 *
 * @param string $type Either "json" or "xml"
 * @return boolean|string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 */
function api_statuses_friends($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
	$data =  api_statuses_f("friends");
	if ($data === false) {
		return false;
	}
	return DI::apiResponse()->formatData("users", $type, $data);
}

api_register_func('api/statuses/friends', 'api_statuses_friends', true);

/**
 * Returns the list of followers of the provided user
 *
 * @deprecated By Twitter API in favor of friends/list
 *
 * @param string $type Either "json" or "xml"
 * @return boolean|string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 */
function api_statuses_followers($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
	$data = api_statuses_f("followers");
	if ($data === false) {
		return false;
	}
	return DI::apiResponse()->formatData("users", $type, $data);
}

api_register_func('api/statuses/followers', 'api_statuses_followers', true);

/**
 * Returns the list of blocked users
 *
 * @see https://developer.twitter.com/en/docs/accounts-and-users/mute-block-report-users/api-reference/get-blocks-list
 *
 * @param string $type Either "json" or "xml"
 *
 * @return boolean|string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 */
function api_blocks_list($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
	$data =  api_statuses_f('blocks');
	if ($data === false) {
		return false;
	}
	return DI::apiResponse()->formatData("users", $type, $data);
}

api_register_func('api/blocks/list', 'api_blocks_list', true);

/**
 * Returns the list of pending users IDs
 *
 * @see https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-friendships-incoming
 *
 * @param string $type Either "json" or "xml"
 *
 * @return boolean|string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 */
function api_friendships_incoming($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
	$data =  api_statuses_f('incoming');
	if ($data === false) {
		return false;
	}

	$ids = [];
	foreach ($data['user'] as $user) {
		$ids[] = $user['id'];
	}

	return DI::apiResponse()->formatData("ids", $type, ['id' => $ids]);
}

api_register_func('api/friendships/incoming', 'api_friendships_incoming', true);

/**
 * Sends a new direct message.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws NotFoundException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/direct-messages/sending-and-receiving/api-reference/new-message
 */
function api_direct_messages_new($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
	$uid = BaseApi::getCurrentUserID();

	if (empty($_POST["text"]) || empty($_POST['screen_name']) && empty($_POST['user_id'])) {
		return;
	}

	$sender = DI::twitterUser()->createFromUserId($uid, true)->toArray();

	$cid = BaseApi::getContactIDForSearchterm($_REQUEST['screen_name'] ?? '', $_REQUEST['profileurl'] ?? '', $_REQUEST['user_id'] ?? 0, $uid);
	if (empty($cid)) {
		throw new NotFoundException('Recipient not found');
	}

	$replyto = '';
	if (!empty($_REQUEST['replyto'])) {
		$mail    = DBA::selectFirst('mail', ['parent-uri', 'title'], ['uid' => $uid, 'id' => $_REQUEST['replyto']]);
		$replyto = $mail['parent-uri'];
		$sub     = $mail['title'];
	} else {
		if (!empty($_REQUEST['title'])) {
			$sub = $_REQUEST['title'];
		} else {
			$sub = ((strlen($_POST['text'])>10) ? substr($_POST['text'], 0, 10)."...":$_POST['text']);
		}
	}

	$cdata = Contact::getPublicAndUserContactID($cid, $uid);

	$id = Mail::send($cdata['user'], $_POST['text'], $sub, $replyto);

	if ($id > -1) {
		$mail = DBA::selectFirst('mail', [], ['id' => $id]);
		$ret = api_format_messages($mail, DI::twitterUser()->createFromContactId($cid, $uid, true)->toArray(), $sender);
	} else {
		$ret = ["error" => $id];
	}

	return DI::apiResponse()->formatData("direct-messages", $type, ['direct_message' => $ret], Contact::getPublicIdByUserId($uid));
}

api_register_func('api/direct_messages/new', 'api_direct_messages_new', true);

/**
 * delete a direct_message from mail table through api
 *
 * @param string $type Known types are 'atom', 'rss', 'xml' and 'json'
 * @return string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see   https://developer.twitter.com/en/docs/direct-messages/sending-and-receiving/api-reference/delete-message
 */
function api_direct_messages_destroy($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
	$uid = BaseApi::getCurrentUserID();

	//required
	$id = $_REQUEST['id'] ?? 0;
	// optional
	$parenturi = $_REQUEST['friendica_parenturi'] ?? '';
	$verbose = (!empty($_GET['friendica_verbose']) ? strtolower($_GET['friendica_verbose']) : "false");
	/// @todo optional parameter 'include_entities' from Twitter API not yet implemented

	// error if no id or parenturi specified (for clients posting parent-uri as well)
	if ($verbose == "true" && ($id == 0 || $parenturi == "")) {
		$answer = ['result' => 'error', 'message' => 'message id or parenturi not specified'];
		return DI::apiResponse()->formatData("direct_messages_delete", $type, ['$result' => $answer]);
	}

	// BadRequestException if no id specified (for clients using Twitter API)
	if ($id == 0) {
		throw new BadRequestException('Message id not specified');
	}

	// add parent-uri to sql command if specified by calling app
	$sql_extra = ($parenturi != "" ? " AND `parent-uri` = '" . DBA::escape($parenturi) . "'" : "");

	// error message if specified id is not in database
	if (!DBA::exists('mail', ["`uid` = ? AND `id` = ? " . $sql_extra, $uid, $id])) {
		if ($verbose == "true") {
			$answer = ['result' => 'error', 'message' => 'message id not in database'];
			return DI::apiResponse()->formatData("direct_messages_delete", $type, ['$result' => $answer]);
		}
		/// @todo BadRequestException ok for Twitter API clients?
		throw new BadRequestException('message id not in database');
	}

	// delete message
	$result = DBA::delete('mail', ["`uid` = ? AND `id` = ? " . $sql_extra, $uid, $id]);

	if ($verbose == "true") {
		if ($result) {
			// return success
			$answer = ['result' => 'ok', 'message' => 'message deleted'];
			return DI::apiResponse()->formatData("direct_message_delete", $type, ['$result' => $answer]);
		} else {
			$answer = ['result' => 'error', 'message' => 'unknown error'];
			return DI::apiResponse()->formatData("direct_messages_delete", $type, ['$result' => $answer]);
		}
	}
	/// @todo return JSON data like Twitter API not yet implemented
}

api_register_func('api/direct_messages/destroy', 'api_direct_messages_destroy', true);

/**
 * Unfollow Contact
 *
 * @param string $type Known types are 'atom', 'rss', 'xml' and 'json'
 * @return string|array
 * @throws HTTPException\BadRequestException
 * @throws HTTPException\ExpectationFailedException
 * @throws HTTPException\ForbiddenException
 * @throws HTTPException\InternalServerErrorException
 * @throws HTTPException\NotFoundException
 * @see   https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/post-friendships-destroy.html
 */
function api_friendships_destroy($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
	$uid = BaseApi::getCurrentUserID();

	$owner = User::getOwnerDataById($uid);
	if (!$owner) {
		Logger::notice(BaseApi::LOG_PREFIX . 'No owner {uid} found', ['module' => 'api', 'action' => 'friendships_destroy', 'uid' => $uid]);
		throw new HTTPException\NotFoundException('Error Processing Request');
	}

	$contact_id = $_REQUEST['user_id'] ?? 0;

	if (empty($contact_id)) {
		Logger::notice(BaseApi::LOG_PREFIX . 'No user_id specified', ['module' => 'api', 'action' => 'friendships_destroy']);
		throw new HTTPException\BadRequestException('no user_id specified');
	}

	// Get Contact by given id
	$contact = DBA::selectFirst('contact', ['url'], ['id' => $contact_id, 'uid' => 0, 'self' => false]);

	if(!DBA::isResult($contact)) {
		Logger::notice(BaseApi::LOG_PREFIX . 'No public contact found for ID {contact}', ['module' => 'api', 'action' => 'friendships_destroy', 'contact' => $contact_id]);
		throw new HTTPException\NotFoundException('no contact found to given ID');
	}

	$url = $contact['url'];

	$condition = ["`uid` = ? AND (`rel` = ? OR `rel` = ?) AND (`nurl` = ? OR `alias` = ? OR `alias` = ?)",
			$uid, Contact::SHARING, Contact::FRIEND, Strings::normaliseLink($url),
			Strings::normaliseLink($url), $url];
	$contact = DBA::selectFirst('contact', [], $condition);

	if (!DBA::isResult($contact)) {
		Logger::notice(BaseApi::LOG_PREFIX . 'Not following contact', ['module' => 'api', 'action' => 'friendships_destroy']);
		throw new HTTPException\NotFoundException('Not following Contact');
	}

	try {
		$result = Contact::terminateFriendship($owner, $contact);

		if ($result === null) {
			Logger::notice(BaseApi::LOG_PREFIX . 'Not supported for {network}', ['module' => 'api', 'action' => 'friendships_destroy', 'network' => $contact['network']]);
			throw new HTTPException\ExpectationFailedException('Unfollowing is currently not supported by this contact\'s network.');
		}

		if ($result === false) {
			throw new HTTPException\ServiceUnavailableException('Unable to unfollow this contact, please retry in a few minutes or contact your administrator.');
		}
	} catch (Exception $e) {
		Logger::error(BaseApi::LOG_PREFIX . $e->getMessage(), ['owner' => $owner, 'contact' => $contact]);
		throw new HTTPException\InternalServerErrorException('Unable to unfollow this contact, please contact your administrator');
	}

	// "uid" is only needed for some internal stuff, so remove it from here
	unset($contact['uid']);

	// Set screen_name since Twidere requests it
	$contact['screen_name'] = $contact['nick'];

	return DI::apiResponse()->formatData('friendships-destroy', $type, ['user' => $contact]);
}

api_register_func('api/friendships/destroy', 'api_friendships_destroy', true);

/**
 *
 * @param string $type Return type (atom, rss, xml, json)
 * @param string $box
 * @param string $verbose
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_direct_messages_box($type, $box, $verbose)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
	$uid = BaseApi::getCurrentUserID();

	// params
	$count = $_GET['count'] ?? 20;
	$page = $_REQUEST['page'] ?? 1;

	$since_id = $_REQUEST['since_id'] ?? 0;
	$max_id = $_REQUEST['max_id'] ?? 0;

	$user_id = $_REQUEST['user_id'] ?? '';
	$screen_name = $_REQUEST['screen_name'] ?? '';

	$user_info = DI::twitterUser()->createFromUserId($uid, true)->toArray();

	$profile_url = $user_info["url"];

	// pagination
	$start = max(0, ($page - 1) * $count);

	$sql_extra = "";

	// filters
	if ($box=="sentbox") {
		$sql_extra = "`mail`.`from-url`='" . DBA::escape($profile_url) . "'";
	} elseif ($box == "conversation") {
		$sql_extra = "`mail`.`parent-uri`='" . DBA::escape($_GET['uri'] ?? '')  . "'";
	} elseif ($box == "all") {
		$sql_extra = "true";
	} elseif ($box == "inbox") {
		$sql_extra = "`mail`.`from-url`!='" . DBA::escape($profile_url) . "'";
	}

	if ($max_id > 0) {
		$sql_extra .= ' AND `mail`.`id` <= ' . intval($max_id);
	}

	if ($user_id != "") {
		$sql_extra .= ' AND `mail`.`contact-id` = ' . intval($user_id);
	} elseif ($screen_name !="") {
		$sql_extra .= " AND `contact`.`nick` = '" . DBA::escape($screen_name). "'";
	}

	$r = DBA::toArray(DBA::p(
		"SELECT `mail`.*, `contact`.`nurl` AS `contact-url` FROM `mail`,`contact` WHERE `mail`.`contact-id` = `contact`.`id` AND `mail`.`uid` = ? AND $sql_extra AND `mail`.`id` > ? ORDER BY `mail`.`id` DESC LIMIT ?,?",
		$uid,
		$since_id,
		$start,
		$count
	));
	if ($verbose == "true" && !DBA::isResult($r)) {
		$answer = ['result' => 'error', 'message' => 'no mails available'];
		return DI::apiResponse()->formatData("direct_messages_all", $type, ['$result' => $answer]);
	}

	$ret = [];
	foreach ($r as $item) {
		if ($box == "inbox" || $item['from-url'] != $profile_url) {
			$recipient = $user_info;
			$sender = DI::twitterUser()->createFromContactId($item['contact-id'], $uid, true)->toArray();
		} elseif ($box == "sentbox" || $item['from-url'] == $profile_url) {
			$recipient = DI::twitterUser()->createFromContactId($item['contact-id'], $uid, true)->toArray();
			$sender = $user_info;
		}

		if (isset($recipient) && isset($sender)) {
			$ret[] = api_format_messages($item, $recipient, $sender);
		}
	}

	return DI::apiResponse()->formatData("direct-messages", $type, ['direct_message' => $ret], Contact::getPublicIdByUserId($uid));
}

/**
 * Returns the most recent direct messages sent by the user.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @see https://developer.twitter.com/en/docs/direct-messages/sending-and-receiving/api-reference/get-sent-message
 */
function api_direct_messages_sentbox($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
	$verbose = !empty($_GET['friendica_verbose']) ? strtolower($_GET['friendica_verbose']) : "false";
	return api_direct_messages_box($type, "sentbox", $verbose);
}

api_register_func('api/direct_messages/sent', 'api_direct_messages_sentbox', true);

/**
 * Returns the most recent direct messages sent to the user.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @see https://developer.twitter.com/en/docs/direct-messages/sending-and-receiving/api-reference/get-messages
 */
function api_direct_messages_inbox($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
	$verbose = !empty($_GET['friendica_verbose']) ? strtolower($_GET['friendica_verbose']) : "false";
	return api_direct_messages_box($type, "inbox", $verbose);
}

api_register_func('api/direct_messages', 'api_direct_messages_inbox', true);

/**
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 */
function api_direct_messages_all($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
	$verbose = !empty($_GET['friendica_verbose']) ? strtolower($_GET['friendica_verbose']) : "false";
	return api_direct_messages_box($type, "all", $verbose);
}

api_register_func('api/direct_messages/all', 'api_direct_messages_all', true);

/**
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 */
function api_direct_messages_conversation($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
	$verbose = !empty($_GET['friendica_verbose']) ? strtolower($_GET['friendica_verbose']) : "false";
	return api_direct_messages_box($type, "conversation", $verbose);
}

api_register_func('api/direct_messages/conversation', 'api_direct_messages_conversation', true);

/**
 * list all photos of the authenticated user
 *
 * @param string $type Known types are 'atom', 'rss', 'xml' and 'json'
 * @return string|array
 * @throws ForbiddenException
 * @throws InternalServerErrorException
 */
function api_fr_photos_list($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
	$uid = BaseApi::getCurrentUserID();

	$r = DBA::toArray(DBA::p(
		"SELECT `resource-id`, MAX(scale) AS `scale`, `album`, `filename`, `type`, MAX(`created`) AS `created`,
		MAX(`edited`) AS `edited`, MAX(`desc`) AS `desc` FROM `photo`
		WHERE `uid` = ? AND NOT `photo-type` IN (?, ?) GROUP BY `resource-id`, `album`, `filename`, `type`",
		$uid, Photo::CONTACT_AVATAR, Photo::CONTACT_BANNER
	));
	$typetoext = [
		'image/jpeg' => 'jpg',
		'image/png' => 'png',
		'image/gif' => 'gif'
	];
	$data = ['photo'=>[]];
	if (DBA::isResult($r)) {
		foreach ($r as $rr) {
			$photo = [];
			$photo['id'] = $rr['resource-id'];
			$photo['album'] = $rr['album'];
			$photo['filename'] = $rr['filename'];
			$photo['type'] = $rr['type'];
			$thumb = DI::baseUrl() . "/photo/" . $rr['resource-id'] . "-" . $rr['scale'] . "." . $typetoext[$rr['type']];
			$photo['created'] = $rr['created'];
			$photo['edited'] = $rr['edited'];
			$photo['desc'] = $rr['desc'];

			if ($type == "xml") {
				$data['photo'][] = ["@attributes" => $photo, "1" => $thumb];
			} else {
				$photo['thumb'] = $thumb;
				$data['photo'][] = $photo;
			}
		}
	}
	return DI::apiResponse()->formatData("photos", $type, $data);
}

api_register_func('api/friendica/photos/list', 'api_fr_photos_list', true);

/**
 * upload a new photo or change an existing photo
 *
 * @param string $type Known types are 'atom', 'rss', 'xml' and 'json'
 * @return string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws NotFoundException
 */
function api_fr_photo_create_update($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
	$uid = BaseApi::getCurrentUserID();

	// input params
	$photo_id  = $_REQUEST['photo_id']  ?? null;
	$desc      = $_REQUEST['desc']      ?? null;
	$album     = $_REQUEST['album']     ?? null;
	$album_new = $_REQUEST['album_new'] ?? null;
	$allow_cid = $_REQUEST['allow_cid'] ?? null;
	$deny_cid  = $_REQUEST['deny_cid' ] ?? null;
	$allow_gid = $_REQUEST['allow_gid'] ?? null;
	$deny_gid  = $_REQUEST['deny_gid' ] ?? null;
	$visibility = !$allow_cid && !$deny_cid && !$allow_gid && !$deny_gid;

	// do several checks on input parameters
	// we do not allow calls without album string
	if ($album == null) {
		throw new BadRequestException("no albumname specified");
	}
	// if photo_id == null --> we are uploading a new photo
	if ($photo_id == null) {
		$mode = "create";

		// error if no media posted in create-mode
		if (empty($_FILES['media'])) {
			// Output error
			throw new BadRequestException("no media data submitted");
		}

		// album_new will be ignored in create-mode
		$album_new = "";
	} else {
		$mode = "update";

		// check if photo is existing in databasei
		if (!Photo::exists(['resource-id' => $photo_id, 'uid' => $uid, 'album' => $album])) {
			throw new BadRequestException("photo not available");
		}
	}

	// checks on acl strings provided by clients
	$acl_input_error = false;
	$acl_input_error |= check_acl_input($allow_cid, $uid);
	$acl_input_error |= check_acl_input($deny_cid, $uid);
	$acl_input_error |= check_acl_input($allow_gid, $uid);
	$acl_input_error |= check_acl_input($deny_gid, $uid);
	if ($acl_input_error) {
		throw new BadRequestException("acl data invalid");
	}
	// now let's upload the new media in create-mode
	if ($mode == "create") {
		$media = $_FILES['media'];
		$data = save_media_to_database("photo", $media, $type, $album, trim($allow_cid), trim($deny_cid), trim($allow_gid), trim($deny_gid), $desc, Photo::DEFAULT, $visibility, null, $uid);

		// return success of updating or error message
		if (!is_null($data)) {
			return DI::apiResponse()->formatData("photo_create", $type, $data);
		} else {
			throw new InternalServerErrorException("unknown error - uploading photo failed, see Friendica log for more information");
		}
	}

	// now let's do the changes in update-mode
	if ($mode == "update") {
		$updated_fields = [];

		if (!is_null($desc)) {
			$updated_fields['desc'] = $desc;
		}

		if (!is_null($album_new)) {
			$updated_fields['album'] = $album_new;
		}

		if (!is_null($allow_cid)) {
			$allow_cid = trim($allow_cid);
			$updated_fields['allow_cid'] = $allow_cid;
		}

		if (!is_null($deny_cid)) {
			$deny_cid = trim($deny_cid);
			$updated_fields['deny_cid'] = $deny_cid;
		}

		if (!is_null($allow_gid)) {
			$allow_gid = trim($allow_gid);
			$updated_fields['allow_gid'] = $allow_gid;
		}

		if (!is_null($deny_gid)) {
			$deny_gid = trim($deny_gid);
			$updated_fields['deny_gid'] = $deny_gid;
		}

		$result = false;
		if (count($updated_fields) > 0) {
			$nothingtodo = false;
			$result = Photo::update($updated_fields, ['uid' => $uid, 'resource-id' => $photo_id, 'album' => $album]);
		} else {
			$nothingtodo = true;
		}

		if (!empty($_FILES['media'])) {
			$nothingtodo = false;
			$media = $_FILES['media'];
			$data = save_media_to_database("photo", $media, $type, $album, $allow_cid, $deny_cid, $allow_gid, $deny_gid, $desc, Photo::DEFAULT, $visibility, $photo_id, $uid);
			if (!is_null($data)) {
				return DI::apiResponse()->formatData("photo_update", $type, $data);
			}
		}

		// return success of updating or error message
		if ($result) {
			$answer = ['result' => 'updated', 'message' => 'Image id `' . $photo_id . '` has been updated.'];
			return DI::apiResponse()->formatData("photo_update", $type, ['$result' => $answer]);
		} else {
			if ($nothingtodo) {
				$answer = ['result' => 'cancelled', 'message' => 'Nothing to update for image id `' . $photo_id . '`.'];
				return DI::apiResponse()->formatData("photo_update", $type, ['$result' => $answer]);
			}
			throw new InternalServerErrorException("unknown error - update photo entry in database failed");
		}
	}
	throw new InternalServerErrorException("unknown error - this error on uploading or updating a photo should never happen");
}

api_register_func('api/friendica/photo/create', 'api_fr_photo_create_update', true);
api_register_func('api/friendica/photo/update', 'api_fr_photo_create_update', true);

/**
 * returns the details of a specified photo id, if scale is given, returns the photo data in base 64
 *
 * @param string $type Known types are 'atom', 'rss', 'xml' and 'json'
 * @return string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws InternalServerErrorException
 * @throws NotFoundException
 */
function api_fr_photo_detail($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
	$uid = BaseApi::getCurrentUserID();

	if (empty($_REQUEST['photo_id'])) {
		throw new BadRequestException("No photo id.");
	}

	$scale = (!empty($_REQUEST['scale']) ? intval($_REQUEST['scale']) : false);
	$photo_id = $_REQUEST['photo_id'];

	// prepare json/xml output with data from database for the requested photo
	$data = prepare_photo_data($type, $scale, $photo_id, $uid);

	return DI::apiResponse()->formatData("photo_detail", $type, $data);
}

api_register_func('api/friendica/photo', 'api_fr_photo_detail', true);

/**
 * updates the profile image for the user (either a specified profile or the default profile)
 *
 * @param string $type Known types are 'atom', 'rss', 'xml' and 'json'
 *
 * @return string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws NotFoundException
 * @see   https://developer.twitter.com/en/docs/accounts-and-users/manage-account-settings/api-reference/post-account-update_profile_image
 */
function api_account_update_profile_image($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
	$uid = BaseApi::getCurrentUserID();

	// input params
	$profile_id = $_REQUEST['profile_id'] ?? 0;

	// error if image data is missing
	if (empty($_FILES['image'])) {
		throw new BadRequestException("no media data submitted");
	}

	// check if specified profile id is valid
	if ($profile_id != 0) {
		$profile = DBA::selectFirst('profile', ['is-default'], ['uid' => $uid, 'id' => $profile_id]);
		// error message if specified profile id is not in database
		if (!DBA::isResult($profile)) {
			throw new BadRequestException("profile_id not available");
		}
		$is_default_profile = $profile['is-default'];
	} else {
		$is_default_profile = 1;
	}

	// get mediadata from image or media (Twitter call api/account/update_profile_image provides image)
	$media = null;
	if (!empty($_FILES['image'])) {
		$media = $_FILES['image'];
	} elseif (!empty($_FILES['media'])) {
		$media = $_FILES['media'];
	}
	// save new profile image
	$data = save_media_to_database("profileimage", $media, $type, DI::l10n()->t(Photo::PROFILE_PHOTOS), "", "", "", "", "", Photo::USER_AVATAR, false, null, $uid);

	// get filetype
	if (is_array($media['type'])) {
		$filetype = $media['type'][0];
	} else {
		$filetype = $media['type'];
	}
	if ($filetype == "image/jpeg") {
		$fileext = "jpg";
	} elseif ($filetype == "image/png") {
		$fileext = "png";
	} else {
		throw new InternalServerErrorException('Unsupported filetype');
	}

	// change specified profile or all profiles to the new resource-id
	if ($is_default_profile) {
		$condition = ["`profile` AND `resource-id` != ? AND `uid` = ?", $data['photo']['id'], $uid];
		Photo::update(['profile' => false, 'photo-type' => Photo::DEFAULT], $condition);
	} else {
		$fields = ['photo' => DI::baseUrl() . '/photo/' . $data['photo']['id'] . '-4.' . $fileext,
			'thumb' => DI::baseUrl() . '/photo/' . $data['photo']['id'] . '-5.' . $fileext];
		DBA::update('profile', $fields, ['id' => $_REQUEST['profile'], 'uid' => $uid]);
	}

	Contact::updateSelfFromUserID($uid, true);

	// Update global directory in background
	Profile::publishUpdate($uid);

	// output for client
	if ($data) {
		$skip_status = $_REQUEST['skip_status'] ?? false;

		$user_info = DI::twitterUser()->createFromUserId($uid, $skip_status)->toArray();

		// "verified" isn't used here in the standard
		unset($user_info["verified"]);

		// "uid" is only needed for some internal stuff, so remove it from here
		unset($user_info['uid']);

		return DI::apiResponse()->formatData("user", $type, ['user' => $user_info]);
	} else {
		// SaveMediaToDatabase failed for some reason
		throw new InternalServerErrorException("image upload failed");
	}
}

api_register_func('api/account/update_profile_image', 'api_account_update_profile_image', true);

/**
 * Return all or a specified group of the user with the containing contacts.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_friendica_group_show($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
	$uid = BaseApi::getCurrentUserID();

	// params
	$gid = $_REQUEST['gid'] ?? 0;

	// get data of the specified group id or all groups if not specified
	if ($gid != 0) {
		$groups = DBA::selectToArray('group', [], ['deleted' => false, 'uid' => $uid, 'id' => $gid]);

		// error message if specified gid is not in database
		if (!DBA::isResult($groups)) {
			throw new BadRequestException("gid not available");
		}
	} else {
		$groups = DBA::selectToArray('group', [], ['deleted' => false, 'uid' => $uid]);
	}

	// loop through all groups and retrieve all members for adding data in the user array
	$grps = [];
	foreach ($groups as $rr) {
		$members = Contact\Group::getById($rr['id']);
		$users = [];

		if ($type == "xml") {
			$user_element = "users";
			$k = 0;
			foreach ($members as $member) {
				$user = DI::twitterUser()->createFromContactId($member['contact-id'], $uid, true)->toArray();
				$users[$k++.":user"] = $user;
			}
		} else {
			$user_element = "user";
			foreach ($members as $member) {
				$user = DI::twitterUser()->createFromContactId($member['contact-id'], $uid, true)->toArray();
				$users[] = $user;
			}
		}
		$grps[] = ['name' => $rr['name'], 'gid' => $rr['id'], $user_element => $users];
	}
	return DI::apiResponse()->formatData("groups", $type, ['group' => $grps]);
}

api_register_func('api/friendica/group_show', 'api_friendica_group_show', true);

/**
 * Delete a group.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/post-lists-destroy
 */
function api_lists_destroy($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
	$uid = BaseApi::getCurrentUserID();

	// params
	$gid = $_REQUEST['list_id'] ?? 0;

	// error if no gid specified
	if ($gid == 0) {
		throw new BadRequestException('gid not specified');
	}

	// get data of the specified group id
	$group = DBA::selectFirst('group', [], ['uid' => $uid, 'id' => $gid]);
	// error message if specified gid is not in database
	if (!$group) {
		throw new BadRequestException('gid not available');
	}

	if (Group::remove($gid)) {
		$list = [
			'name' => $group['name'],
			'id' => intval($gid),
			'id_str' => (string) $gid,
			'user' => DI::twitterUser()->createFromUserId($uid, true)->toArray()
		];

		return DI::apiResponse()->formatData("lists", $type, ['lists' => $list]);
	}
}

api_register_func('api/lists/destroy', 'api_lists_destroy', true);

/**
 * Create the specified group with the posted array of contacts.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_friendica_group_create($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
	$uid = BaseApi::getCurrentUserID();

	// params
	$name = $_REQUEST['name'] ?? '';
	$json = json_decode($_POST['json'], true);
	$users = $json['user'];

	$success = group_create($name, $uid, $users);

	return DI::apiResponse()->formatData("group_create", $type, ['result' => $success]);
}

api_register_func('api/friendica/group_create', 'api_friendica_group_create', true);

/**
 * Create a new group.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/post-lists-create
 */
function api_lists_create($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
	$uid = BaseApi::getCurrentUserID();

	// params
	$name = $_REQUEST['name'] ?? '';

	$success = group_create($name, $uid);
	if ($success['success']) {
		$grp = [
			'name' => $success['name'],
			'id' => intval($success['gid']),
			'id_str' => (string) $success['gid'],
			'user' => DI::twitterUser()->createFromUserId($uid, true)->toArray()
		];

		return DI::apiResponse()->formatData("lists", $type, ['lists' => $grp]);
	}
}

api_register_func('api/lists/create', 'api_lists_create', true);

/**
 * Update the specified group with the posted array of contacts.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_friendica_group_update($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
	$uid = BaseApi::getCurrentUserID();

	// params
	$gid = $_REQUEST['gid'] ?? 0;
	$name = $_REQUEST['name'] ?? '';
	$json = json_decode($_POST['json'], true);
	$users = $json['user'];

	// error if no name specified
	if ($name == "") {
		throw new BadRequestException('group name not specified');
	}

	// error if no gid specified
	if ($gid == "") {
		throw new BadRequestException('gid not specified');
	}

	// remove members
	$members = Contact\Group::getById($gid);
	foreach ($members as $member) {
		$cid = $member['id'];
		foreach ($users as $user) {
			$found = ($user['cid'] == $cid ? true : false);
		}
		if (!isset($found) || !$found) {
			$gid = Group::getIdByName($uid, $name);
			Group::removeMember($gid, $cid);
		}
	}

	// add members
	$erroraddinguser = false;
	$errorusers = [];
	foreach ($users as $user) {
		$cid = $user['cid'];

		if (DBA::exists('contact', ['id' => $cid, 'uid' => $uid])) {
			Group::addMember($gid, $cid);
		} else {
			$erroraddinguser = true;
			$errorusers[] = $cid;
		}
	}

	// return success message incl. missing users in array
	$status = ($erroraddinguser ? "missing user" : "ok");
	$success = ['success' => true, 'gid' => $gid, 'name' => $name, 'status' => $status, 'wrong users' => $errorusers];
	return DI::apiResponse()->formatData("group_update", $type, ['result' => $success]);
}

api_register_func('api/friendica/group_update', 'api_friendica_group_update', true);

/**
 * Update information about a group.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/post-lists-update
 */
function api_lists_update($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
	$uid = BaseApi::getCurrentUserID();

	// params
	$gid = $_REQUEST['list_id'] ?? 0;
	$name = $_REQUEST['name'] ?? '';

	// error if no gid specified
	if ($gid == 0) {
		throw new BadRequestException('gid not specified');
	}

	// get data of the specified group id
	$group = DBA::selectFirst('group', [], ['uid' => $uid, 'id' => $gid]);
	// error message if specified gid is not in database
	if (!$group) {
		throw new BadRequestException('gid not available');
	}

	if (Group::update($gid, $name)) {
		$list = [
			'name' => $name,
			'id' => intval($gid),
			'id_str' => (string) $gid,
			'user' => DI::twitterUser()->createFromUserId($uid, true)->toArray()
		];

		return DI::apiResponse()->formatData("lists", $type, ['lists' => $list]);
	}
}

api_register_func('api/lists/update', 'api_lists_update', true);

/**
 * search for direct_messages containing a searchstring through api
 *
 * @param string $type      Known types are 'atom', 'rss', 'xml' and 'json'
 * @param string $box
 * @return string|array (success: success=true if found and search_result contains found messages,
 *                          success=false if nothing was found, search_result='nothing found',
 *                          error: result=error with error message)
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_friendica_direct_messages_search($type, $box = "")
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
	$uid = BaseApi::getCurrentUserID();

	// params
	$user_info = DI::twitterUser()->createFromUserId($uid, true)->toArray();
	$searchstring = $_REQUEST['searchstring'] ?? '';

	// error if no searchstring specified
	if ($searchstring == "") {
		$answer = ['result' => 'error', 'message' => 'searchstring not specified'];
		return DI::apiResponse()->formatData("direct_messages_search", $type, ['$result' => $answer]);
	}

	// get data for the specified searchstring
	$r = DBA::toArray(DBA::p(
		"SELECT `mail`.*, `contact`.`nurl` AS `contact-url` FROM `mail`,`contact` WHERE `mail`.`contact-id` = `contact`.`id` AND `mail`.`uid` = ? AND `body` LIKE ? ORDER BY `mail`.`id` DESC",
		$uid,
		'%'.$searchstring.'%'
	));

	$profile_url = $user_info["url"];

	// message if nothing was found
	if (!DBA::isResult($r)) {
		$success = ['success' => false, 'search_results' => 'problem with query'];
	} elseif (count($r) == 0) {
		$success = ['success' => false, 'search_results' => 'nothing found'];
	} else {
		$ret = [];
		foreach ($r as $item) {
			if ($box == "inbox" || $item['from-url'] != $profile_url) {
				$recipient = $user_info;
				$sender = DI::twitterUser()->createFromContactId($item['contact-id'], $uid, true)->toArray();
			} elseif ($box == "sentbox" || $item['from-url'] == $profile_url) {
				$recipient = DI::twitterUser()->createFromContactId($item['contact-id'], $uid, true)->toArray();
				$sender = $user_info;
			}

			if (isset($recipient) && isset($sender)) {
				$ret[] = api_format_messages($item, $recipient, $sender);
			}
		}
		$success = ['success' => true, 'search_results' => $ret];
	}

	return DI::apiResponse()->formatData("direct_message_search", $type, ['$result' => $success]);
}

api_register_func('api/friendica/direct_messages_search', 'api_friendica_direct_messages_search', true);
