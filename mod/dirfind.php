<?php

function dirfind_init(&$a) {

	require_once('include/contact_widgets.php');

	if(! x($a->page,'aside'))
		$a->page['aside'] = '';

	$a->page['aside'] .= follow_widget();

	$a->page['aside'] .= findpeople_widget();
}



function dirfind_content(&$a) {

	$local = true;

	$search = notags(trim($_REQUEST['search']));

	if(strpos($search,'@') === 0)
		$search = substr($search,1);

	$o = '';

	$o .= replace_macros(get_markup_template("section_title.tpl"),array(
		'$title' => sprintf( t('People Search - %s'), $search)
	));

	if($search) {

		if ($local) {

			$perpage = 80;
			$startrec = (($a->pager['page']) * $perpage) - $perpage;

			$count = q("SELECT count(*) AS `total` FROM `gcontact` WHERE `network` IN ('%s', '%s', '%s') AND
					(`url` REGEXP '%s' OR `name` REGEXP '%s' OR `location` REGEXP '%s' OR
						`about` REGEXP '%s' OR `keywords` REGEXP '%s')",
					dbesc(NETWORK_DFRN), dbesc(NETWORK_OSTATUS), dbesc(NETWORK_DIASPORA),
					dbesc(escape_tags($search)), dbesc(escape_tags($search)), dbesc(escape_tags($search)),
					dbesc(escape_tags($search)), dbesc(escape_tags($search)));

			$results = q("SELECT `url`, `name`, `photo`, `keywords` FROM `gcontact`WHERE `network` IN ('%s', '%s', '%s') AND
					(`url` REGEXP '%s' OR `name` REGEXP '%s' OR `location` REGEXP '%s' OR `about` REGEXP '%s' OR
						`keywords` REGEXP '%s') ORDER BY `name` ASC LIMIT %d, %d",
					dbesc(NETWORK_DFRN), dbesc(NETWORK_OSTATUS), dbesc(NETWORK_DIASPORA),
					dbesc(escape_tags($search)), dbesc(escape_tags($search)), dbesc(escape_tags($search)),
					dbesc(escape_tags($search)), dbesc(escape_tags($search)),
					intval($startrec), intval($perpage));

			$j = new stdClass();
			$j->total = $count[0]["total"];
			$j->items_page = $perpage;
			$j->page = $a->pager['page'];
			foreach ($results AS $result) {
				if ($result["name"] == "") {
					$urlparts = parse_url($result["url"]);
					$result["name"] = end(explode("/", $urlparts["path"]));
				}

				$objresult = new stdClass();
				$objresult->name = $result["name"];
				$objresult->url = $result["url"];
				$objresult->photo = $result["photo"];
				$objresult->tags = $result["keywords"];

				$j->results[] = $objresult;
			}
		} else {

			$p = (($a->pager['page'] != 1) ? '&p=' . $a->pager['page'] : '');

			if(strlen(get_config('system','directory_submit_url')))
				$x = fetch_url('http://dir.friendica.com/lsearch?f=' . $p .  '&search=' . urlencode($search));

			$j = json_decode($x);
		}

		if($j->total) {
			$a->set_pager_total($j->total);
			$a->set_pager_itemspage($j->items_page);
		}

		if(count($j->results)) {

			$tpl = get_markup_template('match.tpl');
			foreach($j->results as $jj) {

				$o .= replace_macros($tpl,array(
					'$url' => zrl($jj->url),
					'$name' => $jj->name,
					'$photo' => proxy_url($jj->photo),
					'$tags' => $jj->tags
				));
			}
		}
		else {
			info( t('No matches') . EOL);
		}

	}

	$o .= '<div class="clear"></div>';
	$o .= paginate($a);
	return $o;
}
