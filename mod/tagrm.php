<?php

require_once('include/bbcode.php');

function tagrm_post(&$a) {

	if(! local_user())
		goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);


	if((x($_POST,'submit')) && ($_POST['submit'] === t('Cancel')))
		goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);

	$tag =  ((x($_POST,'tag'))  ? hex2bin(notags(trim($_POST['tag']))) : '');
	$item = ((x($_POST,'item')) ? intval($_POST['item'])               : 0 );

	$r = q("SELECT * FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1",
		intval($item),
		intval(local_user())
	);

	if(! count($r))
		goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);

	$arr = explode(',', $r[0]['tag']);
	for($x = 0; $x < count($arr); $x ++) {
		if($arr[$x] === $tag) {
			unset($arr[$x]);
			break;
		}
	}

	$tag_str = implode(',',$arr);

	q("UPDATE `item` SET `tag` = '%s' WHERE `id` = %d AND `uid` = %d LIMIT 1",
		dbesc($tag_str),
		intval($item),
		intval(local_user())
	);

	info( t('Tag removed') . EOL );
	goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);
	
	// NOTREACHED

}



function tagrm_content(&$a) {

	$o = '';

	if(! local_user()) {
		goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);
		// NOTREACHED
	}

	$item = (($a->argc > 1) ? intval($a->argv[1]) : 0);
	if(! $item) {
		goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);
		// NOTREACHED
	}


	$r = q("SELECT * FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1",
		intval($item),
		intval(local_user())
	);

	if(! count($r))
		goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);

	$arr = explode(',', $r[0]['tag']);

	if(! count($arr))
		goaway($a->get_baseurl() . '/' . $_SESSION['photo_return']);

	$o .= '<h3>' . t('Remove Item Tag') . '</h3>';

	$o .= '<p id="tag-remove-desc">' . t('Select a tag to remove: ') . '</p>';

	$o .= '<form id="tagrm" action="tagrm" method="post" >';
	$o .= '<input type="hidden" name="item" value="' . $item . '" />';
	$o .= '<ul>';


	foreach($arr as $x) {
		$o .= '<li><input type="checkbox" name="tag" value="' . bin2hex($x) . '" >' . bbcode($x) . '</input></li>';
	}

	$o .= '</ul>';
	$o .= '<input id="tagrm-submit" type="submit" name="submit" value="' . t('Remove') .'" />';
	$o .= '<input id="tagrm-cancel" type="submit" name="submit" value="' . t('Cancel') .'" />';
	$o .= '</form>';

	return $o;
	
}
