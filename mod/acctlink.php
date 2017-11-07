<?php

use Friendica\App;
use Friendica\Network\Probe;

function acctlink_init(App $a) {

	if(x($_GET,'addr')) {
		$addr = trim($_GET['addr']);
		$res = Probe::uri($addr);
		//logger('acctlink: ' . print_r($res,true));
		if($res['url']) {
			goaway($res['url']);
			killme();
		}
	}
}
