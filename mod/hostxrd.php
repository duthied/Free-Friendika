<?php
/**
 * @file mod/hostxrd.php
 */
use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\System;
use Friendica\Util\Crypto;

function hostxrd_init(App $a)
{
	header('Access-Control-Allow-Origin: *');
	header("Content-type: text/xml");
	$pubkey = Config::get('system', 'site_pubkey');

	if (! $pubkey) {
		$res = Crypto::newKeypair(1024);

		Config::set('system','site_prvkey', $res['prvkey']);
		Config::set('system','site_pubkey', $res['pubkey']);
	}

	$tpl = get_markup_template('xrd_host.tpl');
	echo replace_macros($tpl, array(
		'$zhost' => $a->get_hostname(),
		'$zroot' => System::baseUrl(),
		'$domain' => System::baseUrl(),
		'$bigkey' => Crypto::salmonKey(Config::get('system', 'site_pubkey')))
	);

	exit();
}
