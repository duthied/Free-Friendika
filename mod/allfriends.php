<?php

require_once('include/socgraph.php');

function allfriends_content(&$a) {

	$o = '';
	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	if($a->argc > 1)
		$cid = intval($a->argv[1]);

	if(! $cid)
		return;

	$c = q("SELECT `name`, `url`, `photo` FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
		intval($cid),
		intval(local_user())
	);

	$vcard_widget .= replace_macros(get_markup_template("vcard-widget.tpl"),array(
		'$name'  => htmlentities($c[0]['name']),
		'$photo' => $c[0]['photo'],
		'url'    => z_root() . '/contacts/' . $cid
	));

	if(! x($a->page,'aside'))
		$a->page['aside'] = '';
	$a->page['aside'] .= $vcard_widget;

	if(! count($c))
		return;


	$r = all_friends(local_user(),$cid);

	if(! count($r)) {
		$o .= t('No friends to display.');
		return $o;
	}

	$id = 0;

	foreach($r as $rr) {

		$entry = array(
			'url' => $rr['url'],
			'itemurl' => $rr['url'],
			'name' => htmlentities($rr['name']),
			'thumb' => $rr['photo'],
			'img_hover' => htmlentities($rr['name']),
			'tags' => '',
			'id' => ++$id,
		);
		$entries[] = $entry;
	}

	$tpl = get_markup_template('viewcontact_template.tpl');

	$o .= replace_macros($tpl,array(
		'$title' => sprintf( t('Friends of %s'), htmlentities($c[0]['name'])),
		'$contacts' => $entries,
	));

//	$o .= paginate($a);
	return $o;
}
