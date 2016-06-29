<?php
/*
This file is part of the Diaspora protocol. It is used for fetching single public posts.
*/
require_once("include/diaspora.php");

function fetch_init($a){

	if (($a->argc != 3) OR (!in_array($a->argv[1], array("post", "status_message", "reshare")))) {
		header($_SERVER["SERVER_PROTOCOL"].' 404 '.t('Not Found'));
		killme();
	}

	$guid = $a->argv[2];

	$item = q("SELECT `uid`, `title`, `body`, `guid`, `contact-id`, `private`, `created`, `app`, `location`
			FROM `item` WHERE `wall` AND NOT `private`  AND `guid` = '%s' AND `network` IN ('%s', '%s') AND `id` = `parent` LIMIT 1",
		dbesc($guid), NETWORK_DFRN, NETWORK_DIASPORA);
	if (!$item) {
		header($_SERVER["SERVER_PROTOCOL"].' 404 '.t('Not Found'));
		killme();
	}
	$post = array();

	$reshared = diaspora::is_reshare($item[0]["body"]);

	if ($reshared) {
		$nodename = "reshare";
		$post["root_diaspora_id"] = $reshared["root_handle"];
		$post["root_guid"] = $reshared["root_guid"];
		$post["guid"] = $item[0]["guid"];
		$post["diaspora_handle"] = diaspora::handle_from_contact($item[0]["contact-id"]);
		$post["public"] = (!$item[0]["private"] ? 'true':'false');
		$post["created_at"] = datetime_convert('UTC','UTC',$item[0]["created"]);
	} else {
		$body = bb2diaspora($item[0]["body"]);

		if(strlen($item[0]["title"]))
			$body = "## ".html_entity_decode($item[0]["title"])."\n\n".$body;

		$nodename = "status_message";
		$post["raw_message"] = str_replace("&", "&amp;", $body);
		$post["location"] = $item[0]["location"];
		$post["guid"] = $item[0]["guid"];
		$post["diaspora_handle"] = diaspora::handle_from_contact($item[0]["contact-id"]);
		$post["public"] = (!$item[0]["private"] ? 'true':'false');
		$post["created_at"] = datetime_convert('UTC','UTC',$item[0]["created"]);
		$post["provider_display_name"] = $item[0]["app"];
	}

	$data = array("XML" => array("post" => array($nodename => $post)));
	$xml = xml::from_array($data, $xmlobj);

	$r = q("SELECT `prvkey` FROM `user` WHERE `uid` = %d", intval($item[0]["uid"]));
	if (!$r) {
		header($_SERVER["SERVER_PROTOCOL"].' 404 '.t('Not Found'));
		killme();
	}

	$user = $r[0];

	$key_id = "";

	$b64url_data = base64url_encode($xml);
	$data = str_replace(array("\n", "\r", " ", "\t"), array("", "", "", ""), $b64url_data);

	$type = "application/xml";
	$encoding = "base64url";
	$alg = "RSA-SHA256";
	$signable_data = $data.".".base64url_encode($type).".".base64url_encode($encoding).".".base64url_encode($alg);
	$signature = rsa_sign($signable_data, $user["prvkey"]);
	$sig = base64url_encode($signature);

	$xmldata = array("me:env" => array("me:data" => $data,
							"@attributes" => array("type" => $type),
							"me:encoding" => $encoding,
							"me:alg" => $alg,
							"me:sig" => $sig,
							"@attributes2" => array("key_id" => $key_id)));

	$namespaces = array("me" => "http://salmon-protocol.org/ns/magic-env");

	$envelope = xml::from_array($xmldata, $xml, false, $namespaces);
	header("Content-Type: application/xml; charset=utf-8");
	echo $envelope;

	killme();
}
