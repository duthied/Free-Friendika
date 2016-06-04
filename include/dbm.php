<?php
class dbm {
	public static function processlist() {
		$r = q("SHOW PROCESSLIST");
		$s = array();

		$processes = 0;
		$states = array();
		foreach ($r AS $process) {
			$state = trim($process["State"]);
			if (!in_array($state, array("", "init", "statistics"))) {
				++$states[$state];
				++$processes;
			}
		}
		// query end
		// Sending data
		// updating

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
