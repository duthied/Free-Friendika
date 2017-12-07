<?php

/**
 * @file src/Model/Photo.php
 * @brief This file contains the Photo class for database interface
 */

namespace Friendica\Model;

use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Object\Image;
use dba;

require_once "include/photos.php";
/**
 * Class to handle photo dabatase table
 */
class Photo
{
	/**
	 * @param integer $uid       uid
	 * @param integer $cid       cid
	 * @param integer $rid       rid
	 * @param string  $filename  filename
	 * @param string  $album     album name
	 * @param integer $scale     scale
	 * @param integer $profile   optional, default = 0
	 * @param string  $allow_cid optional, default = ''
	 * @param string  $allow_gid optional, default = ''
	 * @param string  $deny_cid  optional, default = ''
	 * @param string  $deny_gid  optional, default = ''
	 * @param string  $desc      optional, default = ''
	 * @return object
	 */
	public static function store(Image $Image, $uid, $cid, $rid, $filename, $album, $scale, $profile = 0, $allow_cid = '', $allow_gid = '', $deny_cid = '', $deny_gid = '', $desc = '')
	{
		$r = dba::select('photo', array('guid'), array("`resource-id` = ? AND `guid` != ?", $rid, ''), array('limit' => 1));
		if (DBM::is_result($r)) {
			$guid = $r['guid'];
		} else {
			$guid = get_guid();
		}

		$x = dba::select('photo', array('id'), array('resource-id' => $rid, 'uid' => $uid, 'contact-id' => $cid, 'scale' => $scale), array('limit' => 1));

		$fields = array(
			'uid' => $uid,
			'contact-id' => $cid,
			'guid' => $guid,
			'resource-id' => $rid,
			'created' => datetime_convert(),
			'edited' => datetime_convert(),
			'filename' => basename($filename),
			'type' => $Image->getType(),
			'album' => $album,
			'height' => $Image->getHeight(),
			'width' => $Image->getWidth(),
			'datasize' => strlen($Image->asString()),
			'data' => $Image->asString(),
			'scale' => $scale,
			'profile' => $profile,
			'allow_cid' => $allow_cid,
			'allow_gid' => $allow_gid,
			'deny_cid' => $deny_cid,
			'deny_gid' => $deny_gid,
			'desc' => $desc
		);

		if (DBM::is_result($x)) {
			$r = dba::update('photo', $fields, array('id' => $x['id']));
		} else {
			$r = dba::insert('photo', $fields);
		}

		return $r;
	}

	/**
	 * @param string  $photo         photo
	 * @param integer $uid           user id
	 * @param integer $cid           contact id
	 * @param boolean $quit_on_error optional, default false
	 * @return array
	 */
	public static function importProfilePhoto($photo, $uid, $cid, $quit_on_error = false)
	{
		$r = dba::select(
			'photo', array('resource-id'), array('uid' => $uid, 'contact-id' => $cid, 'scale' => 4, 'album' => 'Contact Photos'), array('limit' => 1)
		);

		if (DBM::is_result($r) && strlen($r['resource-id'])) {
			$hash = $r['resource-id'];
		} else {
			$hash = photo_new_resource();
		}

		$photo_failure = false;

		$filename = basename($photo);
		$img_str = fetch_url($photo, true);

		if ($quit_on_error && ($img_str == "")) {
			return false;
		}

		$type = Image::guessType($photo, true);
		$Image = new Image($img_str, $type);
		if ($Image->isValid()) {
			$Image->scaleToSquare(175);

			$r = self::store($Image, $uid, $cid, $hash, $filename, 'Contact Photos', 4);

			if ($r === false) {
				$photo_failure = true;
			}

			$Image->scaleDown(80);

			$r = self::store($Image, $uid, $cid, $hash, $filename, 'Contact Photos', 5);

			if ($r === false) {
				$photo_failure = true;
			}

			$Image->scaleDown(48);

			$r = self::store($Image, $uid, $cid, $hash, $filename, 'Contact Photos', 6);

			if ($r === false) {
				$photo_failure = true;
			}

			$suffix = '?ts=' . time();

			$photo = System::baseUrl() . '/photo/' . $hash . '-4.' . $Image->getExt() . $suffix;
			$thumb = System::baseUrl() . '/photo/' . $hash . '-5.' . $Image->getExt() . $suffix;
			$micro = System::baseUrl() . '/photo/' . $hash . '-6.' . $Image->getExt() . $suffix;

			// Remove the cached photo
			$a = get_app();
			$basepath = $a->get_basepath();

			if (is_dir($basepath . "/photo")) {
				$filename = $basepath . '/photo/' . $hash . '-4.' . $Image->getExt();
				if (file_exists($filename)) {
					unlink($filename);
				}
				$filename = $basepath . '/photo/' . $hash . '-5.' . $Image->getExt();
				if (file_exists($filename)) {
					unlink($filename);
				}
				$filename = $basepath . '/photo/' . $hash . '-6.' . $Image->getExt();
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
			$photo = System::baseUrl() . '/images/person-175.jpg';
			$thumb = System::baseUrl() . '/images/person-80.jpg';
			$micro = System::baseUrl() . '/images/person-48.jpg';
		}

		return array($photo, $thumb, $micro);
	}
}
