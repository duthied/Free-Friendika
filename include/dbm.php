<?php
class dbm {
	public static function processlist() {
		$r = q("SHOW PROCESSLIST");
		$s = array();

		$states = array();
		foreach ($r AS $process) {
			$state = trim($process["State"]);
			if (!in_array($state, array("", "init", "statistics")))
				++$states[$state];
		}
		// query end
		// Sending data
		// updating

		$statelist = "";
		$processes = 0;
		foreach ($states AS $state => $usage) {
			if ($statelist != "")
				$statelist .= ", ";
			$statelist .= $state.": ".$usage;
			++$processes;
		}
		return(array("list" => $statelist, "amount" => $processes));
	}
}
?>
