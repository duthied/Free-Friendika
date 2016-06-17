<?php
/**
 * @brief This class contain functions for the database management
 *
 */
class dbm {
	/**
	 * @brief Return a list of database processes
	 *
	 * @return array
	 *      'list' => List of processes, separated in their different states
	 *      'amount' => Number of concurrent database processes
	 */
	public static function processlist() {
		$r = q("SHOW PROCESSLIST");
		$s = array();

		$processes = 0;
		$states = array();
		foreach ($r AS $process) {
			$state = trim($process["State"]);

			// Filter out all idle processes
			if (!in_array($state, array("", "init", "statistics"))) {
				++$states[$state];
				++$processes;
			}
		}

		$statelist = "";
		foreach ($states AS $state => $usage) {
			if ($statelist != "")
				$statelist .= ", ";
			$statelist .= $state.": ".$usage;
		}
		return(array("list" => $statelist, "amount" => $processes));
	}
}
?>
