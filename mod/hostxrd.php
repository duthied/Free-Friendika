<?php

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\System;

require_once('include/crypto.php');

function hostxrd_init(App $a) {
	header('Access-Control-Allow-Origin: *');
	header("Content-type: text/xml");
	$pubkey = Config::get('system','site_pubkey');

	if(! $pubkey) {
		$res = new_keypair(1024);

		Config::set('system','site_prvkey', $res['prvkey']);
		Config::set('system','site_pubkey', $res['pubkey']);
	}

	//$tpl = file_get_contents('view/xrd_host.tpl');
	/*echo str_replace(array(
		'$zhost','$zroot','$domain','$zot_post','$bigkey'),array($a->get_hostname(),System::baseUrl(),System::baseUrl(),System::baseUrl() . '/post', salmon_key(Config::get('system','site_pubkey'))),$tpl);*/
	$tpl = get_markup_template('xrd_host.tpl');
	echo replace_macros($tpl, array(
		'$zhost' => $a->get_hostname(),
		'$zroot' => System::baseUrl(),
		'$domain' => System::baseUrl(),
		'$zot_post' => System::baseUrl() . '/post',
		'$bigkey' => salmon_key(Config::get('system','site_pubkey')),
	));
	exit();

}
