<?php

require_once('include/security.php');
require_once('include/bbcode.php');
require_once('include/items.php');


function poke_init(&$a) {

	if(! local_user())
		return;

	$uid = local_user();
	$verb = notags(trim($_GET['verb']));
	
	if(! $verb) 
		return;

	$verbs = get_poke_verbs();

	if(! array_key_exists($verb,$verbs))
		return;

	$activity = ACTIVITY_POKE . '#' . urlencode($verbs[$verb][0]);

	$contact_id = intval($_GET['cid']);
	if(! $contact_id)
		return;

	$parent = ((x($_GET,'parent')) ? intval($_GET['parent']) : 0);


	logger('poke: verb ' . $verb . ' contact ' . $contact_id, LOGGER_DEBUG);


	$r = q("SELECT * FROM `contact` WHERE `id` = %d and  `uid` = %d LIMIT 1",
		intval($contact_id),
		intval($uid)
	);

	if(! count($r)) {
		logger('poke: no contact ' . $contact_id);
		return;
	}

	$target = $r[0];

	if($parent) {
		$r = q("select uri, private, allow_cid, allow_gid, deny_cid, deny_gid 
			from item where id = %d and parent = %d and uid = %d limit 1",
			intval($parent),
			intval($parent),
			intval($uid)
		);
		if(count($r)) {
			$parent_uri = $r[0]['uri'];
			$private    = $r[0]['private'];
			$allow_cid  = $r[0]['allow_cid'];
			$allow_gid  = $r[0]['allow_gid'];
			$deny_cid   = $r[0]['deny_cid'];
			$deny_gid   = $r[0]['deny_gid'];
		}
	}
	else {

		$private = ((x($_GET,'private')) ? intval($_GET['private']) : 0);

		$allow_cid     = (($private) ? '<' . $target['id']. '>' : $a->user['allow_cid']);
		$allow_gid     = (($private) ? '' : $a->user['allow_gid']);
		$deny_cid      = (($private) ? '' : $a->user['deny_cid']);
		$deny_gid      = (($private) ? '' : $a->user['deny_gid']);
	}

	$poster = $a->contact;

	$uri = item_new_uri($a->get_hostname(),$uid);

	$arr = array();

	$arr['uid']           = $uid;
	$arr['uri']           = $uri;
	$arr['parent-uri']    = (($parent_uri) ? $parent_uri : $uri);
	$arr['type']          = 'activity';
	$arr['wall']          = 1;
	$arr['contact-id']    = $poster['id'];
	$arr['owner-name']    = $poster['name'];
	$arr['owner-link']    = $poster['url'];
	$arr['owner-avatar']  = $poster['thumb'];
	$arr['author-name']   = $poster['name'];
	$arr['author-link']   = $poster['url'];
	$arr['author-avatar'] = $poster['thumb'];
	$arr['title']         = '';
	$arr['allow_cid']     = $allow_cid;
	$arr['allow_gid']     = $allow_gid;
	$arr['deny_cid']      = $deny_cid;
	$arr['deny_gid']      = $deny_gid;
	$arr['last-child']    = 1;
	$arr['visible']       = 1;
	$arr['verb']          = $activity;
	$arr['private']       = $private;
	$arr['object-type']   = ACTIVITY_OBJ_PERSON;

	$arr['origin']        = 1;
	$arr['body']          = '[url=' . $poster['url'] . ']' . $poster['name'] . '[/url]' . ' ' . t($verbs[$verb][0]) . ' ' . '[url=' . $target['url'] . ']' . $target['name'] . '[/url]';

	$arr['object'] = '<object><type>' . ACTIVITY_OBJ_PERSON . '</type><title>' . $target['name'] . '</title><id>' . $a->get_baseurl() . '/contact/' . $target['id'] . '</id>';
	$arr['object'] .= '<link>' . xmlify('<link rel="alternate" type="text/html" href="' . $target['url'] . '" />' . "\n");

	$arr['object'] .= xmlify('<link rel="photo" type="image/jpeg" href="' . $target['photo'] . '" />' . "\n");
	$arr['object'] .= '</link></object>' . "\n";

	$item_id = item_store($arr);
	if($item_id) {
		q("UPDATE `item` SET `plink` = '%s' WHERE `uid` = %d AND `id` = %d LIMIT 1",
			dbesc($a->get_baseurl() . '/display/' . $poster['nickname'] . '/' . $item_id),
			intval($uid),
			intval($item_id)
		);
		proc_run('php',"include/notifier.php","tag","$item_id");
	}


	call_hooks('post_local_end', $arr);

	proc_run('php',"include/notifier.php","like","$post_id");

	return;
}



function poke_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$name = '';
	$id = '';

	if(intval($_GET['c'])) {
		$r = q("select id,name from contact where id = %d and uid = %d limit 1",
			intval($_GET['c']),
			intval(local_user())
		);
		if(count($r)) {
			$name = $r[0]['name'];
			$id = $r[0]['id'];
		}
	}


	$base = $a->get_baseurl();

	$a->page['htmlhead'] .= '<script src="' . $a->get_baseurl(true) . '/library/jquery_ac/friendica.complete.js" ></script>';
	$a->page['htmlhead'] .= <<< EOT

<script>$(document).ready(function() { 
	var a; 
	a = $("#poke-recip").autocomplete({ 
		serviceUrl: '$base/acl',
		minChars: 2,
		width: 350,
		onSelect: function(value,data) {
			$("#poke-recip-complete").val(data);
		}			
	});
	a.setOptions({ params: { type: 'a' }});


}); 

</script>
EOT;

	$parent = ((x($_GET,'parent')) ? intval($_GET['parent']) : '0');



	$verbs = get_poke_verbs();

	$shortlist = array();
	foreach($verbs as $k => $v)
		if($v[1] !== 'NOTRANSLATION')
			$shortlist[] = array($k,$v[1]);


	$tpl = get_markup_template('poke_content.tpl');

	$o = replace_macros($tpl,array(
		'$title' => t('Poke/Prod'),
		'$desc' => t('poke, prod or do other things to somebody'),
		'$clabel' => t('Recipient'),
		'$choice' => t('Choose what you wish to do to recipient'),
		'$verbs' => $shortlist,
		'$parent' => $parent,
		'$prv_desc' => t('Make this post private'),
		'$submit' => t('Submit'),
		'$name' => $name,
		'$id' => $id
	));

	return $o;

}