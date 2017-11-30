<?php

/**
 * @file src/Object/Profile.php
 */

namespace Friendica\Object;

use Friendica\BaseObject;

class Profile extends BaseObject
{
	/**
	 * @brief Returns a formatted location string from the given profile array
	 *
	 * @param array $profile Profile array (Generated from the "profile" table)
	 *
	 * @return string Location string
	 */
	public static function formatLocation(array $profile)
	{
		$location = '';

		if ($profile['locality']) {
			$location .= $profile['locality'];
		}

		if ($profile['region'] && ($profile['locality'] != $profile['region'])) {
			if ($location) {
				$location .= ', ';
			}

			$location .= $profile['region'];
		}

		if ($profile['country-name']) {
			if ($location) {
				$location .= ', ';
			}

			$location .= $profile['country-name'];
		}

		return $location;
	}
}
