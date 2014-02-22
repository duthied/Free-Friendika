<?php

function search_saved_searches() {

	$o = '';

	if(! feature_enabled(local_user(),'savedsearch'))
		return $o;

	$r = q("select `id`,`term` from `search` WHERE `uid` = %d",
		intval(local_user())
	);

	if(count($r)) {
		$saved = array();
		foreach($r as $rr) {
			$saved[] = array(
				'id'            => $rr['id'],
				'term'			=> $rr['term'],
				'encodedterm' 	=> urlencode($rr['term']),
				'delete'		=> t('Remove term'),
				'selected'		=> ($search==$rr['term']),
			);
		}


		$tpl = get_markup_template("saved_searches_aside.tpl");

		$o .= replace_macros($tpl, array(
			'$title'	 => t('Saved Searches'),
			'$add'		 => '',
			'$searchbox' => '',
			'$saved' 	 => $saved,
		));
	}

	return $o;

}


function search_init(&$a) {

	$search = ((x($_GET,'search')) ? notags(trim(rawurldecode($_GET['search']))) : '');

	if(local_user()) {
		if(x($_GET,'save') && $search) {
			$r = q("select * from `search` where `uid` = %d and `term` = '%s' limit 1",
				intval(local_user()),
				dbesc($search)
			);
			if(! count($r)) {
				q("insert into `search` ( `uid`,`term` ) values ( %d, '%s') ",
					intval(local_user()),
					dbesc($search)
				);
			}
		}
		if(x($_GET,'remove') && $search) {
			q("delete from `search` where `uid` = %d and `term` = '%s' limit 1",
				intval(local_user()),
				dbesc($search)
			);
		}

		$a->page['aside'] .= search_saved_searches();

	}
	else {
		unset($_SESSION['theme']);
		unset($_SESSION['mobile-theme']);
	}



}



function search_post(&$a) {
	if(x($_POST,'search'))
		$a->data['search'] = $_POST['search'];
}


function search_content(&$a) {

	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		notice( t('Public access denied.') . EOL);
		return;
	}

	nav_set_selected('search');

	require_once("include/bbcode.php");
	require_once('include/security.php');
	require_once('include/conversation.php');

	$o = '<h3>' . t('Search') . '</h3>';

	if(x($a->data,'search'))
		$search = notags(trim($a->data['search']));
	else
		$search = ((x($_GET,'search')) ? notags(trim(rawurldecode($_GET['search']))) : '');

	$tag = false;
	if(x($_GET,'tag')) {
		$tag = true;
		$search = ((x($_GET,'tag')) ? notags(trim(rawurldecode($_GET['tag']))) : '');
	}


	$o .= search($search,'search-box','/search',((local_user()) ? true : false));

	if(strpos($search,'#') === 0) {
		$tag = true;
		$search = substr($search,1);
	}
	if(strpos($search,'@') === 0) {
		require_once('mod/dirfind.php');
		return dirfind_content($a);
	}

	if(! $search)
		return $o;

	if (get_config('system','only_tag_search'))
		$tag = true;

	if($tag) {
		//$sql_extra = sprintf(" AND `term`.`term` = '%s' AND `term`.`otype` = %d AND `term`.`type` = %d",
		//$sql_extra = sprintf(" AND `term`.`term` = '%s' AND `term`.`otype` = %d AND `term`.`type` = %d group by `item`.`uri` ",
		//			dbesc(protect_sprintf($search)), intval(TERM_OBJ_POST), intval(TERM_HASHTAG));
		//$sql_table = "`term` LEFT JOIN `item` ON `item`.`id` = `term`.`oid` AND `item`.`uid` = `term`.`uid` ";
		//$sql_order = "`term`.`tid`";
		//$sql_order = "`item`.`received`";

		//$sql_extra = sprintf(" AND EXISTS (SELECT * FROM `term` WHERE `item`.`id` = `term`.`oid` AND `item`.`uid` = `term`.`uid` AND `term`.`term` = '%s' AND `term`.`otype` = %d AND `term`.`type` = %d) GROUP BY `item`.`uri` ",
		//			dbesc(protect_sprintf($search)), intval(TERM_OBJ_POST), intval(TERM_HASHTAG));
		//$sql_table = "`item` FORCE INDEX (`uri`) ";

		$sql_extra = "";

		$sql_table = sprintf("`item` INNER JOIN (SELECT `oid` FROM `term` WHERE `term` = '%s' AND `otype` = %d AND `type` = %d AND `uid` = %d ORDER BY `tid` DESC) AS `term` ON `item`.`id` = `term`.`oid` ",
					dbesc(protect_sprintf($search)), intval(TERM_OBJ_POST), intval(TERM_HASHTAG), intval(local_user()));

		$sql_order = "`item`.`received`";
	} else {
		if (get_config('system','use_fulltext_engine')) {
			$sql_extra = sprintf(" AND MATCH (`item`.`body`, `item`.`title`) AGAINST ('%s' in boolean mode) ", dbesc(protect_sprintf($search)));
		} else {
			$sql_extra = sprintf(" AND `item`.`body` REGEXP '%s' ", dbesc(protect_sprintf(preg_quote($search))));
		}
		$sql_table = "`item`";
		$sql_order = "`item`.`received`";
	}

	// Here is the way permissions work in the search module...
	// Only public posts can be shown
	// OR your own posts if you are a logged in member
	// No items will be shown if the member has a blocked profile wall. 

	if( (! get_config('alt_pager', 'global')) && (! get_pconfig(local_user(),'system','alt_pager')) ) {
	        $r = q("SELECT distinct(`item`.`uri`) as `total`
		        FROM $sql_table LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id` LEFT JOIN `user` ON `user`.`uid` = `item`.`uid`
		        WHERE `item`.`visible` = 1 AND `item`.`deleted` = 0 and `item`.`moderated` = 0
		        AND (( `item`.`allow_cid` = ''  AND `item`.`allow_gid` = '' AND `item`.`deny_cid`  = '' AND `item`.`deny_gid`  = '' AND `item`.`private` = 0 AND `user`.`hidewall` = 0) 
			        OR ( `item`.`uid` = %d ))
		        AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
		        $sql_extra ",
		        intval(local_user())
	        );
//		        $sql_extra group by `item`.`uri` ",

	        if(count($r))
		        $a->set_pager_total(count($r));

	        if(! count($r)) {
		        info( t('No results.') . EOL);
		        return $o;
	        }
	}

	$r = q("SELECT `item`.`uri`, `item`.*, `item`.`id` AS `item_id`, 
		`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`alias`, `contact`.`rel`,
		`contact`.`network`, `contact`.`thumb`, `contact`.`self`, `contact`.`writable`, 
		`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`,
		`user`.`nickname`, `user`.`uid`, `user`.`hidewall`
		FROM $sql_table LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		LEFT JOIN `user` ON `user`.`uid` = `item`.`uid`
		WHERE `item`.`visible` = 1 AND `item`.`deleted` = 0 and `item`.`moderated` = 0
		AND (( `item`.`allow_cid` = ''  AND `item`.`allow_gid` = '' AND `item`.`deny_cid`  = '' AND `item`.`deny_gid`  = '' AND `item`.`private` = 0 AND `user`.`hidewall` = 0 ) 
			OR ( `item`.`uid` = %d ))
		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
		$sql_extra
		ORDER BY $sql_order DESC LIMIT %d , %d ",
		intval(local_user()),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])

	);
//		group by `item`.`uri`

	if(! count($r)) {
		info( t('No results.') . EOL);
		return $o;
	}


	if($tag)
		$o .= '<h2>Items tagged with: ' . $search . '</h2>';
	else
		$o .= '<h2>Search results for: ' . $search . '</h2>';

	$o .= conversation($a,$r,'search',false);

	if( get_config('alt_pager', 'global') || get_pconfig(local_user(),'system','alt_pager') ) {
	        $o .= alt_pager($a,count($r));
	}
	else {
	        $o .= paginate($a);
	}

	return $o;
}

