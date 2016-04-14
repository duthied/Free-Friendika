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

$a->page['htmlhead'] .= <<< EOT
<script type="text/javascript">

function insertFormatting(comment,BBcode,id) {

		var tmpStr = $("#comment-edit-text-" + id).val();
		if(tmpStr == comment) {
			tmpStr = "";
			$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
			$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
			openMenu("comment-edit-submit-wrapper-" + id);
			$("#comment-edit-text-" + id).val(tmpStr);
		}

	textarea = document.getElementById("comment-edit-text-" +id);
	if (document.selection) {
		textarea.focus();
		selected = document.selection.createRange();
		if (BBcode == "url"){
			selected.text = "["+BBcode+"]" + "http://" +  selected.text + "[/"+BBcode+"]";
			} else
		selected.text = "["+BBcode+"]" + selected.text + "[/"+BBcode+"]";
	} else if (textarea.selectionStart || textarea.selectionStart == "0") {
		var start = textarea.selectionStart;
		var end = textarea.selectionEnd;
		if (BBcode == "url"){
			textarea.value = textarea.value.substring(0, start) + "["+BBcode+"]" + "http://" + textarea.value.substring(start, end) + "[/"+BBcode+"]" + textarea.value.substring(end, textarea.value.length);
			} else
		textarea.value = textarea.value.substring(0, start) + "["+BBcode+"]" + textarea.value.substring(start, end) + "[/"+BBcode+"]" + textarea.value.substring(end, textarea.value.length);
	}
	return true;
}


function showThread(id) {
	$("#collapsed-comments-" + id).show()
	$("#collapsed-comments-" + id + " .collapsed-comments").show()
}
function hideThread(id) {
	$("#collapsed-comments-" + id).hide()
	$("#collapsed-comments-" + id + " .collapsed-comments").hide()
}


function cmtBbOpen(id) {
	$("#comment-edit-bb-" + id).show();
}
function cmtBbClose(id) {
	$("#comment-edit-bb-" + id).hide();
}
</script>
EOT;
}

function frio_install() {
	register_hook('prepare_body_final', 'view/theme/frio/theme.php', 'frio_item_photo_links');

	logger("installed theme frio");
}

function frio_uninstall() {
	unregister_hook('prepare_body_final', 'view/theme/frio/theme.php', 'frio_item_photo_links');

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
