<?php
include_once('include/text.php');
require_once('include/socgraph.php');
require_once('include/contact_widgets.php');
require_once('mod/proxy.php');

function match_content(&$a) {

	$o = '';
	if(! local_user())
		return;

	$a->page['aside'] .= follow_widget();
	$a->page['aside'] .= findpeople_widget();

	$_SESSION['return_url'] = $a->get_baseurl() . '/' . $a->cmd;

	$o .= replace_macros(get_markup_template("section_title.tpl"),array(
		'$title' => t('Profile Match')
	));

	$r = q("SELECT `pub_keywords`, `prv_keywords` FROM `profile` WHERE `is-default` = 1 AND `uid` = %d LIMIT 1",
		intval(local_user())
	);
	if(! count($r))
		return;
	if(! $r[0]['pub_keywords'] && (! $r[0]['prv_keywords'])) {
		notice( t('No keywords to match. Please add keywords to your default profile.') . EOL);
		return;

	}

	$params = array();
	$tags = trim($r[0]['pub_keywords'] . ' ' . $r[0]['prv_keywords']);

	if($tags) {
		$params['s'] = $tags;
		if($a->pager['page'] != 1)
			$params['p'] = $a->pager['page'];

		if(strlen(get_config('system','directory')))
			$x = post_url(get_server().'/msearch', $params);
		else
			$x = post_url($a->get_baseurl() . '/msearch', $params);

		$j = json_decode($x);

		if($j->total) {
			$a->set_pager_total($j->total);
			$a->set_pager_itemspage($j->items_page);
		}

		if(count($j->results)) {



			$tpl = get_markup_template('match.tpl');
			foreach($j->results as $jj) {
				$match_nurl = normalise_link($jj->url);
				$match = q("SELECT `nurl` FROM `contact` WHERE `uid` = '%d' AND nurl='%s' LIMIT 1",
					intval(local_user()),
					dbesc($match_nurl));
				if (!count($match)) {
					$jj->photo = str_replace("http:///photo/", get_server()."/photo/", $jj->photo);
					$connlnk = $a->get_baseurl() . '/follow/?url=' . $jj->url;
					$o .= replace_macros($tpl,array(
						'$url' => zrl($jj->url),
						'$name' => $jj->name,
						'$photo' => proxy_url($jj->photo, false, PROXY_SIZE_THUMB),
						'$inttxt' => ' ' . t('is interested in:'),
						'$conntxt' => t('Connect'),
						'$connlnk' => $connlnk,
						'$tags' => $jj->tags
					));
				}
			}
		} else {
			info( t('No matches') . EOL);
		}

	}

	$o .= cleardiv();
	$o .= paginate($a);
	return $o;
}
