<?php

/**
 * @file include/dir_fns.php
 */


/**
 * @brief This class handels directory related functions
 */
class dir {

	/**
	 * @brief Search global contact table by nick or name
	 *  * 
	 * @param string $search Name or nick
	 * @return array
	 */
	public static function global_search_by_name($search) {

		if($search) {
			// check supported networks
			if (get_config('system','diaspora_enabled'))
				$diaspora = NETWORK_DIASPORA;
			else
				$diaspora = NETWORK_DFRN;

			if (!get_config('system','ostatus_disabled'))
				$ostatus = NETWORK_OSTATUS;
			else
				$ostatus = NETWORK_DFRN;

			$results = q("SELECT `contact`.`id` AS `cid`, `gcontact`.`url`, `gcontact`.`name`, `gcontact`.`nick`, `gcontact`.`photo`,
							`gcontact`.`network`, `gcontact`.`keywords`, `gcontact`.`addr`
						FROM `gcontact`
						LEFT JOIN `contact` ON `contact`.`nurl` = `gcontact`.`nurl`
							AND `contact`.`uid` = %d AND NOT `contact`.`blocked`
							AND NOT `contact`.`pending` AND `contact`.`rel` IN ('%s', '%s')
						WHERE (`contact`.`id` > 0 OR (NOT `gcontact`.`hide` AND `gcontact`.`network` IN ('%s', '%s', '%s') AND
						((`gcontact`.`last_contact` >= `gcontact`.`last_failure`) OR (`gcontact`.`updated` >= `gcontact`.`last_failure`)))) AND
						(`gcontact`.`url` REGEXP '%s' OR `gcontact`.`name` REGEXP '%s' OR `gcontact`.`nick` REGEXP '%s'
							) 
							GROUP BY `gcontact`.`nurl`
							ORDER BY `gcontact`.`updated` DESC ",
						intval(local_user()), dbesc(CONTACT_IS_SHARING), dbesc(CONTACT_IS_FRIEND),
						dbesc(NETWORK_DFRN), dbesc($ostatus), dbesc($diaspora),
						dbesc(escape_tags($search)), dbesc(escape_tags($search)), dbesc(escape_tags($search)));
			return $results;
		}

	}
}
