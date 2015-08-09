<?php
require_once("mod/hostxrd.php");

function _well_known_init(&$a){
	if ($a->argc > 1) {
		switch($a->argv[1]) {
			case "host-meta":
				hostxrd_init($a);
				break;
			case "x-social-relay":
				wk_social_relay($a);
				break;
		}
	}
	http_status_exit(404);
	killme();
}

function wk_social_relay(&$a) {

	define('SR_SCOPE_ALL', 'all');
	define('SR_SCOPE_TAGS', 'tags');

	$subscribe = (bool)true;
	$scope = SR_SCOPE_ALL;
	//$scope = SR_SCOPE_TAGS;

	$tags = array();

	if ($scope == SR_SCOPE_TAGS) {
		$terms = q("SELECT DISTINCT(`term`) FROM `search`");

		foreach($terms AS $term) {
			$tag = trim($term["term"], "#");
			$tags[] = $tag;
		}
	}

	$relay = array("subscribe" => $subscribe,
			"scope" => $scope,
			"tags" => array_unique($tags));

	header('Content-type: application/json; charset=utf-8');
	echo json_encode($relay, true);
	exit;
}
