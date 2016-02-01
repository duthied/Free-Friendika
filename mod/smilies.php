<?php

/**
 * @file mod/smilies.php
 */

require_once("include/smilies.php");

function smilies_content(&$a) {
	if ($a->argv[1]==="json"){
		$tmp = smilies::list_smilies();
		$results = array();
		for($i = 0; $i < count($tmp['texts']); $i++) {
			$results[] = array('text' => $tmp['texts'][$i], 'icon' => $tmp['icons'][$i]);
		}
		json_return_and_die($results);
	}
	else {
		return smilies('',true);
	}
}
