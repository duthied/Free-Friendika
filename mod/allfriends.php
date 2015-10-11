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

	$c = q("select name, url, photo from contact where id = %d and uid = %d limit 1",
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

	$o .= replace_macros(get_markup_template("section_title.tpl"),array(
		'$title' => sprintf( t('Friends of %s'), htmlentities($c[0]['name']))
	));


	$r = all_friends(local_user(),$cid);

	if(! count($r)) {
		$o .= t('No friends to display.');
		return $o;
	}

	$tpl = get_markup_template('common_friends.tpl');

	foreach($r as $rr) {

		$o .= replace_macros($tpl,array(
			'$url' => $rr['url'],
			'$name' => htmlentities($rr['name']),
			'$photo' => $rr['photo'],
			'$tags' => ''
		));
	}

	$o .= cleardiv();
//	$o .= paginate($a);
	return $o;
}
