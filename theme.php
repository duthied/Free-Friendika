<?php
/*
 * Name: frio
 * Description: Bootstrap V3 theme. The theme is currently under construction, so it is far from finished. For further information have a look at the <a href="https://github.com/rabuzarus/frio/blob/master/README.md">ReadMe</a> and <a href="https://github.com/rabuzarus/frio">GitHub</a>.
 * Version: V.0.1 Alpha
 * Author: Rabuzarus <https://friendica.kommune4.de/profile/rabuzarus>
 * 
 */

$frio = "view/theme/frio";

global $frio;

function frio_init(&$a) {
	set_template_engine($a, 'smarty3');

	$baseurl = $a->get_baseurl();

	$style = get_pconfig(local_user(), 'frio', 'style');

	$frio = "view/theme/frio";

	global $frio;
	
	


	if ($style == "")
		$style = get_config('frio', 'style');
}

function frio_install() {
	register_hook('prepare_body_final', 'view/theme/frio/theme.php', 'frio_item_photo_links');
	register_hook('item_photo_menu', 'view/theme/frio/theme.php', 'frio_item_photo_menu');

	logger("installed theme frio");
}

function frio_uninstall() {
	unregister_hook('prepare_body_final', 'view/theme/frio/theme.php', 'frio_item_photo_links');
	unregister_hook('item_photo_menu', 'view/theme/frio/theme.php', 'frio_item_photo_menu');

	logger("uninstalled theme frio");
}
/**
 * @brief Replace friendica photo links
 * 
 *  This function does replace the links to photos
 *  of other friendica users. Original the photos are
 *  linked to the photo page. Now they will linked directly
 *  to the photo file. This function is nessesary to use colorbox
 *  in the network stream
 * 
 * @param App $a
 * @param array $body_info The item and its html output
 */
function frio_item_photo_links(&$a, &$body_info) {
	require_once('include/Photo.php');

	$phototypes = Photo::supportedTypes();
	$occurence = 1;
	$p = bb_find_open_close($body_info['html'], "<a", ">");

	while($p !== false && ($occurence++ < 500)) {
		$link = substr($body_info['html'], $p['start'], $p['end'] - $p['start']);
		$matches = array();

		preg_match("/\/photos\/[\w]+\/image\/([\w]+)/", $link, $matches);
		if($matches) {
			// Replace the link for the photo's page with a direct link to the photo itself
			$newlink = str_replace($matches[0], "/photo/{$matches[1]}", $link);

			// Add a "quiet" parameter to any redir links to prevent the "XX welcomes YY" info boxes
			$newlink = preg_replace("/href=\"([^\"]+)\/redir\/([^\"]+)&url=([^\"]+)\"/", 'href="$1/redir/$2&quiet=1&url=$3"', $newlink);

			 // Having any arguments to the link for Colorbox causes it to fetch base64 code instead of the image
			$newlink = preg_replace("/\/[?&]zrl=([^&\"]+)/", '', $newlink);

			$body_info['html'] = str_replace($link, $newlink, $body_info['html']);
		}

		$p = bb_find_open_close($body_info['html'], "<a", ">", $occurence);
	}
}

/**
 * @brief Replace links of the item_photo_menu
 * 
 *  This function replaces the original poke and the message links
 *  to call the addToModal javascript function so this pages can
 *  be loaded in a bootstrap modal
 * 
 * @param app $a The app data
 * @param array $arr Contains item data and the original photo_menu
 */
function frio_item_photo_menu($a, &$arr){

	foreach($arr["menu"] as $k =>$v) {
		if(strpos($v,'poke/?f=&c=') === 0 || strpos($v,'message/new/') === 0) {
			$v = "javascript:addToModal('" . $v . "'); return false;";
			$arr["menu"][$k] = $v;
			$testvariable = $testvariable+1;
		}
	}
	$args = array('item' => $item, 'menu' => $menu);
}
