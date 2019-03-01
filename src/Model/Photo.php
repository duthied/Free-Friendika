<?php

/**
 * @file src/Model/Photo.php
 * @brief This file contains the Photo class for database interface
 */
namespace Friendica\Model;

use Friendica\BaseObject;
use Friendica\Core\Cache;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\StorageManager;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\Model\Storage\IStorage;
use Friendica\Object\Image;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;
use Friendica\Util\Security;

require_once "include/dba.php";

/**
 * Class to handle photo dabatase table
 */
class Photo extends BaseObject
{
	/**
	 * @brief Select rows from the photo table
	 *
	 * @param array $fields     Array of selected fields, empty for all
	 * @param array $conditions Array of fields for conditions
	 * @param array $params     Array of several parameters
	 *
	 * @return boolean|array
	 *
	 * @throws \Exception
	 * @see   \Friendica\Database\DBA::select
	 */
	public static function select(array $fields = [], array $conditions = [], array $params = [])
	{
		if (empty($fields)) {
			$fields = self::getFields();
		}

		$r = DBA::select("photo", $fields, $conditions, $params);
		return DBA::toArray($r);
	}

	/**
	 * @brief Retrieve a single record from the photo table
	 *
	 * @param array $fields     Array of selected fields, empty for all
	 * @param array $conditions Array of fields for conditions
	 * @param array $params     Array of several parameters
	 *
	 * @return bool|array
	 *
	 * @throws \Exception
	 * @see   \Friendica\Database\DBA::select
	 */
	public static function selectFirst(array $fields = [], array $conditions = [], array $params = [])
	{
		if (empty($fields)) {
			$fields = self::getFields();
		}

		return DBA::selectFirst("photo", $fields, $conditions, $params);
	}

	/**
	 * @brief Get photos for user id
	 *
	 * @param integer $uid        User id
	 * @param string  $resourceid Rescource ID of the photo
	 * @param array   $conditions Array of fields for conditions
	 * @param array   $params     Array of several parameters
	 *
	 * @return bool|array
	 *
	 * @throws \Exception
	 * @see   \Friendica\Database\DBA::select
	 */
	public static function getPhotosForUser($uid, $resourceid, array $conditions = [], array $params = [])
	{
		$conditions["resource-id"] = $resourceid;
		$conditions["uid"] = $uid;

		return self::select([], $conditions, $params);
	}

	/**
	 * @brief Get a photo for user id
	 *
	 * @param integer $uid        User id
	 * @param string  $resourceid Rescource ID of the photo
	 * @param integer $scale      Scale of the photo. Defaults to 0
	 * @param array   $conditions Array of fields for conditions
	 * @param array   $params     Array of several parameters
	 *
	 * @return bool|array
	 *
	 * @throws \Exception
	 * @see   \Friendica\Database\DBA::select
	 */
	public static function getPhotoForUser($uid, $resourceid, $scale = 0, array $conditions = [], array $params = [])
	{
		$conditions["resource-id"] = $resourceid;
		$conditions["uid"] = $uid;
		$conditions["scale"] = $scale;

		return self::selectFirst([], $conditions, $params);
	}

	/**
	 * @brief Get a single photo given resource id and scale
	 *
	 * This method checks for permissions. Returns associative array
	 * on success, "no sign" image info, if user has no permission,
	 * false if photo does not exists
	 *
	 * @param string  $resourceid Rescource ID of the photo
	 * @param integer $scale      Scale of the photo. Defaults to 0
	 *
	 * @return boolean|array
	 * @throws \Exception
	 */
	public static function getPhoto($resourceid, $scale = 0)
	{
		$r = self::selectFirst(["uid"], ["resource-id" => $resourceid]);
		if ($r === false) {
			return false;
		}

		$sql_acl = Security::getPermissionsSQLByUserId($r["uid"]);

		$conditions = [
			"`resource-id` = ? AND `scale` <= ? " . $sql_acl,
			$resourceid, $scale
		];

		$params = ["order" => ["scale" => true]];

		$photo = self::selectFirst([], $conditions, $params);

		return $photo;
	}

	/**
	 * @brief Check if photo with given conditions exists
	 *
	 * @param array $conditions Array of extra conditions
	 *
	 * @return boolean
	 * @throws \Exception
	 */
	public static function exists(array $conditions)
	{
		return DBA::exists("photo", $conditions);
	}


	/**
	 * @brief Get Image object for given row id. null if row id does not exist
	 *
	 * @param array $photo Photo data. Needs at least 'id', 'type', 'backend-class', 'backend-ref'
	 *
	 * @return \Friendica\Object\Image
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getImageForPhoto(array $photo)
	{
		$data = "";

		if ($photo["backend-class"] == "") {
			// legacy data storage in "data" column
			$i = self::selectFirst(["data"], ["id" => $photo["id"]]);
			if ($i === false) {
				return null;
			}
			$data = $i["data"];
		} else {
			$backendClass = $photo["backend-class"];
			$backendRef = $photo["backend-ref"];
			$data = $backendClass::get($backendRef);
		}

		if ($data === "") {
			return null;
		}

		return new Image($data, $photo["type"]);
	}

	/**
	 * @brief Return a list of fields that are associated with the photo table
	 *
	 * @return array field list
	 * @throws \Exception
	 */
	private static function getFields()
	{
		$allfields = DBStructure::definition(self::getApp()->getBasePath(), false);
		$fields = array_keys($allfields["photo"]["fields"]);
		array_splice($fields, array_search("data", $fields), 1);
		return $fields;
	}

	/**
	 * @brief Construct a photo array for a system resource image
	 *
	 * @param string $filename Image file name relative to code root
	 * @param string $mimetype Image mime type. Defaults to "image/jpeg"
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function createPhotoForSystemResource($filename, $mimetype = "image/jpeg")
	{
		$fields = self::getFields();
		$values = array_fill(0, count($fields), "");

		$photo = array_combine($fields, $values);
		$photo["backend-class"] = Storage\SystemResource::class;
		$photo["backend-ref"] = $filename;
		$photo["type"] = $mimetype;
		$photo["cacheable"] = false;

		return $photo;
	}


	/**
	 * @brief store photo metadata in db and binary in default backend
	 *
	 * @param Image   $Image     Image object with data
	 * @param integer $uid       User ID
	 * @param integer $cid       Contact ID
	 * @param integer $rid       Resource ID
	 * @param string  $filename  Filename
	 * @param string  $album     Album name
	 * @param integer $scale     Scale
	 * @param integer $profile   Is a profile image? optional, default = 0
	 * @param string  $allow_cid Permissions, allowed contacts. optional, default = ""
	 * @param string  $allow_gid Permissions, allowed groups. optional, default = ""
	 * @param string  $deny_cid  Permissions, denied contacts.optional, default = ""
	 * @param string  $deny_gid  Permissions, denied greoup.optional, default = ""
	 * @param string  $desc      Photo caption. optional, default = ""
	 *
	 * @return boolean True on success
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function store(Image $Image, $uid, $cid, $rid, $filename, $album, $scale, $profile = 0, $allow_cid = "", $allow_gid = "", $deny_cid = "", $deny_gid = "", $desc = "")
	{
		$photo = self::selectFirst(["guid"], ["`resource-id` = ? AND `guid` != ?", $rid, ""]);
		if (DBA::isResult($photo)) {
			$guid = $photo["guid"];
		} else {
			$guid = System::createGUID();
		}

		$existing_photo = self::selectFirst(["id", "created", "backend-class", "backend-ref"], ["resource-id" => $rid, "uid" => $uid, "contact-id" => $cid, "scale" => $scale]);
		$created = DateTimeFormat::utcNow();
		if (DBA::isResult($existing_photo)) {
			$created = $existing_photo["created"];
		}

		// Get defined storage backend.
		// if no storage backend, we use old "data" column in photo table.
		// if is an existing photo, reuse same backend
		$data = "";
		$backend_ref = "";

		/** @var IStorage $backend_class */
		if (DBA::isResult($existing_photo)) {
			$backend_ref = (string)$existing_photo["backend-ref"];
			$backend_class = (string)$existing_photo["backend-class"];
		} else {
			$backend_class = StorageManager::getBackend();
		}

		if ($backend_class === "") {
			$data = $Image->asString();
		} else {
			$backend_ref = $backend_class::put($Image->asString(), $backend_ref);
		}


		$fields = [
			"uid" => $uid,
			"contact-id" => $cid,
			"guid" => $guid,
			"resource-id" => $rid,
			"created" => $created,
			"edited" => DateTimeFormat::utcNow(),
			"filename" => basename($filename),
			"type" => $Image->getType(),
			"album" => $album,
			"height" => $Image->getHeight(),
			"width" => $Image->getWidth(),
			"datasize" => strlen($Image->asString()),
			"data" => $data,
			"scale" => $scale,
			"profile" => $profile,
			"allow_cid" => $allow_cid,
			"allow_gid" => $allow_gid,
			"deny_cid" => $deny_cid,
			"deny_gid" => $deny_gid,
			"desc" => $desc,
			"backend-class" => $backend_class,
			"backend-ref" => $backend_ref
		];

		if (DBA::isResult($existing_photo)) {
			$r = DBA::update("photo", $fields, ["id" => $existing_photo["id"]]);
		} else {
			$r = DBA::insert("photo", $fields);
		}

		return $r;
	}


	/**
	 * @brief Delete info from table and data from storage
	 *
	 * @param array $conditions Field condition(s)
	 * @param array $options    Options array, Optional
	 *
	 * @return boolean
	 *
	 * @throws \Exception
	 * @see   \Friendica\Database\DBA::delete
	 */
	public static function delete(array $conditions, array $options = [])
	{
		// get photo to delete data info
		$photos = self::select(["backend-class","backend-ref"], $conditions);

		foreach($photos as $photo) {
			/** @var IStorage $backend_class */
			$backend_class = (string)$photo["backend-class"];
			if ($backend_class !== "") {
				$backend_class::delete($photo["backend-ref"]);
			}
		}

		return DBA::delete("photo", $conditions, $options);
	}

	/**
	 * @brief This function is used by the fromgplus addon
	 * @param integer $uid       user id
	 * @param string  $imagedata optional, default empty
	 * @param string  $url       optional, default empty
	 * @return array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function storePhoto($uid, $imagedata = "", $url = "")
	{
		$a = self::getApp();
		$logger = $a->getLogger();
		$profiler = $a->getProfiler();

		$r = DBA::p(
			"SELECT `user`.`nickname`, `user`.`page-flags`, `contact`.`id` FROM `user` INNER JOIN `contact` on `user`.`uid` = `contact`.`uid`
			WHERE `user`.`uid` = %d AND `user`.`blocked` = 0 AND `contact`.`self` = 1 LIMIT 1",
			intval($uid)
		);

		if (!DBA::isResult($r)) {
			$logger->info("Can't detect user data.", ['uid' => $uid]);
			return([]);
		}

		$page_owner_nick  = $r[0]['nickname'];

		/// @TODO
		/// $default_cid      = $r[0]['id'];
		/// $community_page   = (($r[0]['page-flags'] == User::PAGE_FLAGS_COMMUNITY) ? true : false);

		if ((strlen($imagedata) == 0) && ($url == "")) {
			$logger->info("No image data and no url provided");
			return([]);
		} elseif (strlen($imagedata) == 0) {
			$logger->info("Uploading picture,", ['url' => $url]);

			$stamp1 = microtime(true);
			$imagedata = @file_get_contents($url);
			$profiler->saveTimestamp($stamp1, "file", System::callstack());
		}

		$maximagesize = Config::get('system', 'maximagesize');

		if (($maximagesize) && (strlen($imagedata) > $maximagesize)) {
			$logger->info("Image exceeds size limit.", ['max' => $maximagesize, 'current' => strlen($imagedata)]);
			return([]);
		}

		$tempfile = tempnam(get_temppath(), "cache");

		$stamp1 = microtime(true);
		file_put_contents($tempfile, $imagedata);
		$profiler->saveTimestamp($stamp1, "file", System::callstack());

		$data = getimagesize($tempfile);

		if (!isset($data["mime"])) {
			unlink($tempfile);
			$logger->info("File is no picture");
			return([]);
		}

		$Image = new Image($imagedata, $data["mime"]);

		if (!$Image->isValid()) {
			unlink($tempfile);
			$logger->info("Picture is no valid picture");
			return([]);
		}

		$Image->orient($tempfile);
		unlink($tempfile);

		$max_length = Config::get('system', 'max_image_length');
		if (! $max_length) {
			$max_length = MAX_IMAGE_LENGTH;
		}

		if ($max_length > 0) {
			$Image->scaleDown($max_length);
		}

		$width = $Image->getWidth();
		$height = $Image->getHeight();

		$hash = Photo::newResource();

		// Pictures are always public by now
		//$defperm = '<'.$default_cid.'>';
		$defperm = "";
		$visitor = 0;

		$r = Photo::store($Image, $uid, $visitor, $hash, $tempfile, L10n::t('Wall Photos'), 0, 0, $defperm);

		if (!$r) {
			$logger->info("Picture couldn't be stored");
			return([]);
		}

		$image = ["page" => System::baseUrl().'/photos/'.$page_owner_nick.'/image/'.$hash,
			"full" => $a->getBaseURL()."/photo/{$hash}-0.".$Image->getExt()];

		if ($width > 800 || $height > 800) {
			$image["large"] = System::baseUrl()."/photo/{$hash}-0.".$Image->getExt();
		}

		if ($width > 640 || $height > 640) {
			$Image->scaleDown(640);
			$r = Photo::store($Image, $uid, $visitor, $hash, $tempfile, L10n::t('Wall Photos'), 1, 0, $defperm);
			if ($r) {
				$image["medium"] = System::baseUrl()."/photo/{$hash}-1.".$Image->getExt();
			}
		}

		if ($width > 320 || $height > 320) {
			$Image->scaleDown(320);
			$r = Photo::store($Image, $uid, $visitor, $hash, $tempfile, L10n::t('Wall Photos'), 2, 0, $defperm);
			if ($r) {
				$image["small"] = System::baseUrl()."/photo/{$hash}-2.".$Image->getExt();
			}
		}

		if ($width > 160 && $height > 160) {
			$x = 0;
			$y = 0;

			$min = $Image->getWidth();
			if ($min > 160) {
				$x = ($min - 160) / 2;
			}

			if ($Image->getHeight() < $min) {
				$min = $Image->getHeight();
				if ($min > 160) {
					$y = ($min - 160) / 2;
				}
			}

			$min = 160;
			$Image->crop(160, $x, $y, $min, $min);

			$r = Photo::store($Image, $uid, $visitor, $hash, $tempfile, L10n::t('Wall Photos'), 3, 0, $defperm);
			if ($r) {
				$image["thumb"] = $a->getBaseURL() . "/photo/{$hash}-3." . $Image->getExt();
			}
		}

		// Set the full image as preview image. This will be overwritten, if the picture is larger than 640.
		$image["preview"] = $image["full"];

		// Deactivated, since that would result in a cropped preview, if the picture wasn't larger than 320
		//if (isset($image["thumb"]))
		//  $image["preview"] = $image["thumb"];

		// Unsure, if this should be activated or deactivated
		//if (isset($image["small"]))
		//  $image["preview"] = $image["small"];

		if (isset($image["medium"])) {
			$image["preview"] = $image["medium"];
		}

		return($image);
	}

	/**
	 * @brief Update a photo
	 *
	 * @param array         $fields     Contains the fields that are updated
	 * @param array         $conditions Condition array with the key values
	 * @param Image         $img        Image to update. Optional, default null.
	 * @param array|boolean $old_fields Array with the old field values that are about to be replaced (true = update on duplicate)
	 *
	 * @return boolean  Was the update successfull?
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @see   \Friendica\Database\DBA::update
	 */
	public static function update($fields, $conditions, Image $img = null, array $old_fields = [])
	{
		if (!is_null($img)) {
			// get photo to update
			$photos = self::select(["backend-class","backend-ref"], $conditions);

			foreach($photos as $photo) {
				/** @var IStorage $backend_class */
				$backend_class = (string)$photo["backend-class"];
				if ($backend_class !== "") {
					$fields["backend-ref"] = $backend_class::put($img->asString(), $photo["backend-ref"]);
				} else {
					$fields["data"] = $img->asString();
				}
			}
			$fields['updated'] = DateTimeFormat::utcNow();
		}

		$fields['edited'] = DateTimeFormat::utcNow();

		return DBA::update("photo", $fields, $conditions, $old_fields);
	}

	/**
	 * @param string  $image_url     Remote URL
	 * @param integer $uid           user id
	 * @param integer $cid           contact id
	 * @param boolean $quit_on_error optional, default false
	 * @return array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function importProfilePhoto($image_url, $uid, $cid, $quit_on_error = false)
	{
		$thumb = "";
		$micro = "";

		$photo = DBA::selectFirst(
			"photo", ["resource-id"], ["uid" => $uid, "contact-id" => $cid, "scale" => 4, "album" => "Contact Photos"]
		);
		if (!empty($photo['resource-id'])) {
			$hash = $photo["resource-id"];
		} else {
			$hash = self::newResource();
		}

		$photo_failure = false;

		$filename = basename($image_url);
		$img_str = Network::fetchUrl($image_url, true);

		if ($quit_on_error && ($img_str == "")) {
			return false;
		}

		$type = Image::guessType($image_url, true);
		$Image = new Image($img_str, $type);
		if ($Image->isValid()) {
			$Image->scaleToSquare(300);

			$r = self::store($Image, $uid, $cid, $hash, $filename, "Contact Photos", 4);

			if ($r === false) {
				$photo_failure = true;
			}

			$Image->scaleDown(80);

			$r = self::store($Image, $uid, $cid, $hash, $filename, "Contact Photos", 5);

			if ($r === false) {
				$photo_failure = true;
			}

			$Image->scaleDown(48);

			$r = self::store($Image, $uid, $cid, $hash, $filename, "Contact Photos", 6);

			if ($r === false) {
				$photo_failure = true;
			}

			$suffix = "?ts=" . time();

			$image_url = System::baseUrl() . "/photo/" . $hash . "-4." . $Image->getExt() . $suffix;
			$thumb = System::baseUrl() . "/photo/" . $hash . "-5." . $Image->getExt() . $suffix;
			$micro = System::baseUrl() . "/photo/" . $hash . "-6." . $Image->getExt() . $suffix;

			// Remove the cached photo
			$a = \get_app();
			$basepath = $a->getBasePath();

			if (is_dir($basepath . "/photo")) {
				$filename = $basepath . "/photo/" . $hash . "-4." . $Image->getExt();
				if (file_exists($filename)) {
					unlink($filename);
				}
				$filename = $basepath . "/photo/" . $hash . "-5." . $Image->getExt();
				if (file_exists($filename)) {
					unlink($filename);
				}
				$filename = $basepath . "/photo/" . $hash . "-6." . $Image->getExt();
				if (file_exists($filename)) {
					unlink($filename);
				}
			}
		} else {
			$photo_failure = true;
		}

		if ($photo_failure && $quit_on_error) {
			return false;
		}

		if ($photo_failure) {
			$image_url = System::baseUrl() . "/images/person-300.jpg";
			$thumb = System::baseUrl() . "/images/person-80.jpg";
			$micro = System::baseUrl() . "/images/person-48.jpg";
		}

		return [$image_url, $thumb, $micro];
	}

	/**
	 * @param array $exifCoord coordinate
	 * @param string $hemi      hemi
	 * @return float
	 */
	public static function getGps($exifCoord, $hemi)
	{
		$degrees = count($exifCoord) > 0 ? self::gps2Num($exifCoord[0]) : 0;
		$minutes = count($exifCoord) > 1 ? self::gps2Num($exifCoord[1]) : 0;
		$seconds = count($exifCoord) > 2 ? self::gps2Num($exifCoord[2]) : 0;

		$flip = ($hemi == "W" || $hemi == "S") ? -1 : 1;

		return floatval($flip * ($degrees + ($minutes / 60) + ($seconds / 3600)));
	}

	/**
	 * @param string $coordPart coordPart
	 * @return float
	 */
	private static function gps2Num($coordPart)
	{
		$parts = explode("/", $coordPart);

		if (count($parts) <= 0) {
			return 0;
		}

		if (count($parts) == 1) {
			return $parts[0];
		}

		return floatval($parts[0]) / floatval($parts[1]);
	}

	/**
	 * @brief Fetch the photo albums that are available for a viewer
	 *
	 * The query in this function is cost intensive, so it is cached.
	 *
	 * @param int  $uid    User id of the photos
	 * @param bool $update Update the cache
	 *
	 * @return array Returns array of the photo albums
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getAlbums($uid, $update = false)
	{
		$sql_extra = Security::getPermissionsSQLByUserId($uid);

		$key = "photo_albums:".$uid.":".local_user().":".remote_user();
		$albums = Cache::get($key);
		if (is_null($albums) || $update) {
			if (!Config::get("system", "no_count", false)) {
				/// @todo This query needs to be renewed. It is really slow
				// At this time we just store the data in the cache
				$albums = q("SELECT COUNT(DISTINCT `resource-id`) AS `total`, `album`, ANY_VALUE(`created`) AS `created`
					FROM `photo`
					WHERE `uid` = %d  AND `album` != '%s' AND `album` != '%s' $sql_extra
					GROUP BY `album` ORDER BY `created` DESC",
					intval($uid),
					DBA::escape("Contact Photos"),
					DBA::escape(L10n::t("Contact Photos"))
				);
			} else {
				// This query doesn't do the count and is much faster
				$albums = q("SELECT DISTINCT(`album`), '' AS `total`
					FROM `photo` USE INDEX (`uid_album_scale_created`)
					WHERE `uid` = %d  AND `album` != '%s' AND `album` != '%s' $sql_extra",
					intval($uid),
					DBA::escape("Contact Photos"),
					DBA::escape(L10n::t("Contact Photos"))
				);
			}
			Cache::set($key, $albums, Cache::DAY);
		}
		return $albums;
	}

	/**
	 * @param int $uid User id of the photos
	 * @return void
	 * @throws \Exception
	 */
	public static function clearAlbumCache($uid)
	{
		$key = "photo_albums:".$uid.":".local_user().":".remote_user();
		Cache::set($key, null, Cache::DAY);
	}

	/**
	 * Generate a unique photo ID.
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function newResource()
	{
		return System::createGUID(32, false);
	}
}
