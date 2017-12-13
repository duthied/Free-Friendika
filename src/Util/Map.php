<?php
/**
 * @file src/Util/Map.php
 */
namespace Friendica\Util;

/**
 * Leaflet Map related functions
 */
class Map {
	public static function byCoordinates($coord) {
		$coord = trim($coord);
		$coord = str_replace(array(',','/','  '),array(' ',' ',' '),$coord);
		$arr = array('lat' => trim(substr($coord,0,strpos($coord,' '))), 'lon' => trim(substr($coord,strpos($coord,' ')+1)), 'html' => '');
		call_hooks('generate_map',$arr);
		return ($arr['html']) ? $arr['html'] : $coord;
	}

	public static function byLocation($location) {
		$arr = array('location' => $location, 'html' => '');
		call_hooks('generate_named_map',$arr);
		return ($arr['html']) ? $arr['html'] : $location;
	}
}
