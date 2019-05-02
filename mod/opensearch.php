<?php

use Friendica\App;
use Friendica\Core\Renderer;

function opensearch_content(App $a) {

	$tpl = Renderer::getMarkupTemplate('opensearch.tpl');

	header("Content-type: application/opensearchdescription+xml");

	$o = Renderer::replaceMacros($tpl, [
		'$nodename' => $a->getHostName(),
	]);

	echo $o;

	exit();
}
