<?php

function noscrape_init(&$a) {
	
	if(get_config('system','disable_noscrape'))
		killme();
	
	if($a->argc > 1)
		$which = $a->argv[1];
	else 
		killme();
	
	$profile = 0;
	if((local_user()) && ($a->argc > 2) && ($a->argv[2] === 'view')) {
		$which = $a->user['nickname'];
		$profile = $a->argv[1];
	}
	
	profile_load($a,$which,$profile);
	
	if(!$a->profile['net-publish'])
		killme();
	
	$keywords = ((x($a->profile,'pub_keywords')) ? $a->profile['pub_keywords'] : '');
	$keywords = str_replace(array('#',',',' ',',,'),array('',' ',',',','),$keywords);
	$keywords = explode(',', $keywords);
	
	$json_info = array(
		'fn' => $a->profile['name'],
		'key' => $a->profile['pubkey'],
		'homepage' => $a->get_baseurl()."/profile/{$which}",
		'comm' => (x($a->profile,'page-flags')) && ($a->profile['page-flags'] == PAGE_COMMUNITY),
		'photo' => $a->profile['photo'],
		'tags' => $keywords
	);
	
	//These are optional fields.
	$profile_fields = array('pdesc', 'locality', 'region', 'postal-code', 'country-name', 'gender', 'marital');
	foreach($profile_fields as $field)
		if(!empty($a->profile[$field])) $json_info["$field"] = $a->profile[$field];
	
	$dfrn_pages = array('request', 'confirm', 'notify', 'poll');
	foreach($dfrn_pages as $dfrn)
		$json_info["dfrn-{$dfrn}"] = $a->get_baseurl()."/dfrn_{$dfrn}/{$which}";
	
	//Output all the JSON!
	header('Content-type: application/json; charset=utf-8');
	echo json_encode($json_info);
	exit;
	
}
