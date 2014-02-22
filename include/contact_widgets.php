<?php

function follow_widget() {

	return replace_macros(get_markup_template('follow.tpl'),array(
		'$connect' => t('Add New Contact'),
		'$desc' => t('Enter address or web location'),
		'$hint' => t('Example: bob@example.com, http://example.com/barbara'),
		'$follow' => t('Connect')
	));

}

function findpeople_widget() {
	require_once('include/Contact.php');

	$a = get_app();

	if(get_config('system','invitation_only')) {
		$x = get_pconfig(local_user(),'system','invites_remaining');
		if($x || is_site_admin()) {
			$a->page['aside'] .= '<div class="side-link" id="side-invite-remain">' 
			. sprintf( tt('%d invitation available','%d invitations available',$x), $x) 
			. '</div>' . $inv;
		}
	}
 
	return replace_macros(get_markup_template('peoplefind.tpl'),array(
		'$findpeople' => t('Find People'),
		'$desc' => t('Enter name or interest'),
		'$label' => t('Connect/Follow'),
		'$hint' => t('Examples: Robert Morgenstein, Fishing'),
		'$findthem' => t('Find'),
		'$suggest' => t('Friend Suggestions'),
		'$similar' => t('Similar Interests'),
		'$random' => t('Random Profile'),
		'$inv' => t('Invite Friends')
	));

}


function networks_widget($baseurl,$selected = '') {

	$a = get_app();

	if(! local_user())
		return '';

	if(! feature_enabled(local_user(),'networks'))
		return '';
	
	$r = q("select distinct(network) from contact where uid = %d and self = 0",
		intval(local_user())
	);

	$nets = array();
	if(count($r)) {
		require_once('include/contact_selectors.php');
		foreach($r as $rr) {
				if($rr['network'])
					$nets[] = array('ref' => $rr['network'], 'name' => network_to_name($rr['network']), 'selected' => (($selected == $rr['network']) ? 'selected' : '' ));
		}
	}

	if(count($nets) < 2)
		return '';

	return replace_macros(get_markup_template('nets.tpl'),array(
		'$title' => t('Networks'),
		'$desc' => '',
		'$sel_all' => (($selected == '') ? 'selected' : ''),
		'$all' => t('All Networks'),
		'$nets' => $nets,
		'$base' => $baseurl,

	));
}

function fileas_widget($baseurl,$selected = '') {
	$a = get_app();
	if(! local_user())
		return '';

	if(! feature_enabled(local_user(),'filing'))
		return '';

	$saved = get_pconfig(local_user(),'system','filetags');
	if(! strlen($saved))
		return;

	$matches = false;
	$terms = array();
    $cnt = preg_match_all('/\[(.*?)\]/',$saved,$matches,PREG_SET_ORDER);
    if($cnt) {
		foreach($matches as $mtch) {
			$unescaped = xmlify(file_tag_decode($mtch[1]));
			$terms[] = array('name' => $unescaped,'selected' => (($selected == $unescaped) ? 'selected' : ''));
		}
	}

	return replace_macros(get_markup_template('fileas_widget.tpl'),array(
		'$title' => t('Saved Folders'),
		'$desc' => '',
		'$sel_all' => (($selected == '') ? 'selected' : ''),
		'$all' => t('Everything'),
		'$terms' => $terms,
		'$base' => $baseurl,

	));
}

function categories_widget($baseurl,$selected = '') {

	$a = get_app();

	if(! feature_enabled($a->profile['profile_uid'],'categories'))
		return '';

	$saved = get_pconfig($a->profile['profile_uid'],'system','filetags');
	if(! strlen($saved))
		return;

	$matches = false;
	$terms = array();
        $cnt = preg_match_all('/<(.*?)>/',$saved,$matches,PREG_SET_ORDER);
        if($cnt) {
                foreach($matches as $mtch) {
		        $unescaped = xmlify(file_tag_decode($mtch[1]));
			$terms[] = array('name' => $unescaped,'selected' => (($selected == $unescaped) ? 'selected' : ''));
		}
	}

	return replace_macros(get_markup_template('categories_widget.tpl'),array(
		'$title' => t('Categories'),
		'$desc' => '',
		'$sel_all' => (($selected == '') ? 'selected' : ''),
		'$all' => t('Everything'),
		'$terms' => $terms,
		'$base' => $baseurl,

	));
}

function common_friends_visitor_widget($profile_uid) {

	$a = get_app();

	if(local_user() == $profile_uid)
		return;

	$cid = $zcid = 0;

	if(is_array($_SESSION['remote'])) {
		foreach($_SESSION['remote'] as $visitor) {
			if($visitor['uid'] == $profile_uid) {
				$cid = $visitor['cid'];
				break;
			}
		}
	}

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

	require_once('include/socgraph.php');

	if($cid)
		$t = count_common_friends($profile_uid,$cid);
	else
		$t = count_common_friends_zcid($profile_uid,$zcid);
	if(! $t)
		return;

	if($cid)
		$r = common_friends($profile_uid,$cid,0,5,true);
	else
		$r = common_friends_zcid($profile_uid,$zcid,0,5,true);

	return replace_macros(get_markup_template('remote_friends_common.tpl'), array(
		'$desc' =>  sprintf( tt("%d contact in common", "%d contacts in common", $t), $t),
		'$base' => $a->get_baseurl(),
		'$uid' => $profile_uid,
		'$cid' => (($cid) ? $cid : '0'),
		'$linkmore' => (($t > 5) ? 'true' : ''),
		'$more' => t('show more'),
		'$items' => $r
	)); 

};
