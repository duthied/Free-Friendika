<?php

use Friendica\App;
use Friendica\Core\PConfig;
use Friendica\Core\System;
use Friendica\Network\Probe;

require_once 'include/follow.php';

function ostatus_subscribe_content(App $a) {

	if (! local_user()) {
		notice( t('Permission denied.') . EOL);
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	$o = "<h2>".t("Subscribing to OStatus contacts")."</h2>";

	$uid = local_user();

	$a = get_app();

	$counter = intval($_REQUEST['counter']);

	if (PConfig::get($uid, "ostatus", "legacy_friends") == "") {

		if ($_REQUEST["url"] == "") {
			return $o.t("No contact provided.");
		}

		$contact = Probe::uri($_REQUEST["url"]);

		if (!$contact) {
			return $o.t("Couldn't fetch information for contact.");
		}

		$api = $contact["baseurl"]."/api/";

		// Fetching friends
		$data = z_fetch_url($api."statuses/friends.json?screen_name=".$contact["nick"]);

		if (!$data["success"]) {
			return $o.t("Couldn't fetch friends for contact.");
		}

		PConfig::set($uid, "ostatus", "legacy_friends", $data["body"]);
	}

	$friends = json_decode(PConfig::get($uid, "ostatus", "legacy_friends"));

	$total = sizeof($friends);

	if ($counter >= $total) {
		$a->page['htmlhead'] = '<meta http-equiv="refresh" content="0; URL='.System::baseUrl().'/settings/connectors">';
		PConfig::delete($uid, "ostatus", "legacy_friends");
		PConfig::delete($uid, "ostatus", "legacy_contact");
		$o .= t("Done");
		return $o;
	}

	$friend = $friends[$counter++];

	$url = $friend->statusnet_profile_url;

	$o .= "<p>".$counter."/".$total.": ".$url;

	$data = Probe::uri($url);
	if ($data["network"] == NETWORK_OSTATUS) {
		$result = new_contact($uid, $url, true, NETWORK_OSTATUS);
		if ($result["success"]) {
			$o .= " - ".t("success");
		} else {
			$o .= " - ".t("failed");
		}
	} else {
		$o .= " - ".t("ignored");
	}

	$o .= "</p>";

	$o .= "<p>".t("Keep this window open until done.")."</p>";

	$a->page['htmlhead'] = '<meta http-equiv="refresh" content="0; URL='.System::baseUrl().'/ostatus_subscribe?counter='.$counter.'">';

	return $o;
}
