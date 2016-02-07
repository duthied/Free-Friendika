<?php
/* identi.ca -> friendica items permanent-url compatibility */

if(! function_exists('notice_init')) {
	function notice_init(&$a) {
		$id = $a->argv[1];
		$r = q("SELECT user.nickname FROM user LEFT JOIN item ON item.uid=user.uid WHERE item.id=%d",
				intval($id)
				);
		if (count($r)){
			$nick = $r[0]['nickname'];
			$url = $a->get_baseurl()."/display/$nick/$id";
			goaway($url);
		} else {
			$a->error = 404;
			notice( t('Item not found.') . EOL);

		}
		return;
	}
}
