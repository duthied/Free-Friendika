<?php

require_once('include/socgraph.php');

function common_content(&$a) {

	$o = '';

	$cmd = $a->argv[1];
	$uid = intval($a->argv[2]);
	$cid = intval($a->argv[3]);
	$zcid = 0;

	if($cmd !== 'loc' && $cmd != 'rem')
		return;
	if(! $uid)
		return;

	if($cmd === 'loc' && $cid) {
		$c = q("select name, url, photo from contact where id = %d and uid = %d limit 1",
			intval($cid),
			intval($uid)
		);
	}
	else {
		$c = q("select name, url, photo from contact where self = 1 and uid = %d limit 1",
			intval($uid)
		);
	}

	$vcard_widget .= replace_macros(get_markup_template("vcard-widget.tpl"),array(
		'$name' => htmlentities($c[0]['name']),
		'$photo' => $c[0]['photo'],
		'url' => z_root() . '/contacts/' . $cid
	));

	if(! x($a->page,'aside'))
		$a->page['aside'] = '';
	$a->page['aside'] .= $vcard_widget;

	if(! count($c))
		return;

	$o .= replace_macros(get_markup_template("section_title.tpl"),array(
		'$title' => t('Common Friends')
	));


	if(! $cid) {
		if(get_my_url()) {
			$r = q("select id from contact where nurl = '%s' and uid = %d limit 1",
				dbesc(normalise_link(get_my_url())),
				intval($profile_uid)
			);
			if(count($r))
				$cid = $r[0]['id'];
			else {
				$r = q("select id from gcontact where nurl = '%s' limit 1",
					dbesc(normalise_link(get_my_url()))
				);
				if(count($r))
					$zcid = $r[0]['id'];
			}
		}
	}



	if($cid == 0 && $zcid == 0)
		return; 


	if($cid)
		$t = count_common_friends($uid,$cid);
	else
		$t = count_common_friends_zcid($uid,$zcid);


	$a->set_pager_total($t);

	if(! $t) {
		notice( t('No contacts in common.') . EOL);
		return $o;
	}


	if($cid)
		$r = common_friends($uid,$cid);
	else
		$r = common_friends_zcid($uid,$zcid);


	if(! count($r)) {
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
