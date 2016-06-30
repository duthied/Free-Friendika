<?php
/*
This file is part of the Diaspora protocol. It is used for fetching single public posts.
*/
require_once("include/diaspora.php");

function p_init($a){
	if ($a->argc != 2) {
		header($_SERVER["SERVER_PROTOCOL"].' 510 '.t('Not Extended'));
		killme();
	}

	$guid = $a->argv[1];

	if (strtolower(substr($guid, -4)) != ".xml") {
		header($_SERVER["SERVER_PROTOCOL"].' 404 '.t('Not Found'));
		killme();
	}

	$guid = strtolower(substr($guid, 0, -4));

	// Fetch the item
	$item = q("SELECT `uid`, `title`, `body`, `guid`, `contact-id`, `private`, `created`, `app`, `location`, `coord`
			FROM `item` WHERE `wall` AND NOT `private` AND `guid` = '%s' AND `network` IN ('%s', '%s') AND `id` = `parent` LIMIT 1",
		dbesc($guid), NETWORK_DFRN, NETWORK_DIASPORA);
	if (!$item) {
		header($_SERVER["SERVER_PROTOCOL"].' 404 '.t('Not Found'));
		killme();
	}

	// Fetch some data from the author (We could combine both queries - but I think this is more readable)
	$r = q("SELECT `user`.`prvkey`, `contact`.`addr`, `user`.`nickname`, `contact`.`nick` FROM `user`
		INNER JOIN `contact` ON `contact`.`uid` = `user`.`uid`
		WHERE `user`.`uid` = %d", intval($item[0]["uid"]));
	if (!$r) {
		header($_SERVER["SERVER_PROTOCOL"].' 404 '.t('Not Found'));
		killme();
	}
	$user = $r[0];

	$status = diaspora::build_status($item[0], $user);
	$data = array("XML" => array("post" => array($status["type"] => $status["message"])));
	$xml = xml::from_array($data, $xmlobj);

	header("Content-Type: application/xml; charset=utf-8");
	echo $xml;

	killme();
}
