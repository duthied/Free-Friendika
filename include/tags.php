<?php
/*
require_once("boot.php");
if(@is_null($a)) {
        $a = new App;
}

if(is_null($db)) {
        @include(".htconfig.php");
        require_once("dba.php");
        $db = new dba($db_host, $db_user, $db_pass, $db_data);
        unset($db_host, $db_user, $db_pass, $db_data);
};

$a->set_baseurl(get_config('system','url'));
*/

function create_tags_from_item($itemid) {
	global $a;

	$searchpath = $a->get_baseurl()."/search?tag=";

	$messages = q("SELECT `uri`, `uid`, `id`, `created`, `edited`, `commented`, `received`, `changed`, `deleted`, `title`, `body`, `tag` FROM `item` WHERE `id` = %d LIMIT 1", intval($itemid));

	if (!$messages)
		return;

	$message = $messages[0];

	// Clean up all tags
	q("DELETE FROM `tag` WHERE `iid` = %d", intval($itemid));

	if ($message["deleted"])
		return;

	$taglist = explode(",", $message["tag"]);

	$tags = "";
	foreach ($taglist as $tag)
		if ((substr(trim($tag), 0, 1) == "#") OR (substr(trim($tag), 0, 1) == "@"))
			$tags .= " ".trim($tag);
		else
			$tags .= " #".trim($tag);

	$data = " ".$message["title"]." ".$message["body"]." ".$tags." ";

	$tags = array();

	$pattern = "/\W\#([^\[].*?)[\s'\".,:;\?!\[\]\/]/ism";
	if (preg_match_all($pattern, $data, $matches))
		foreach ($matches[1] as $match)
			$tags["#".strtolower($match)] = $searchpath.strtolower($match);

	$pattern = "/\W([\#@])\[url\=(.*?)\](.*?)\[\/url\]/ism";
	if (preg_match_all($pattern, $data, $matches, PREG_SET_ORDER)) {
		foreach ($matches as $match)
			$tags[$match[1].strtolower(trim($match[3], ',.:;[]/\"?!'))] = $match[2];
	}

	foreach ($tags as $tag=>$link)
		$r = q("INSERT INTO `tag` (`iid`, `tag`, `link`, `created`, `edited`, `commented`, `received`, `changed`) VALUES (%d, '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
			intval($itemid), dbesc($tag), dbesc($link), dbesc($message["created"]),
			dbesc($message["edited"]), dbesc($message["commented"]), dbesc($message["received"]), dbesc($message["changed"]));
}

function create_tags_from_itemuri($itemuri, $uid) {
	$messages = q("SELECT `id` FROM `item` WHERE uri ='%s' AND uid=%d", dbesc($itemuri), intval($uid));

	foreach ($messages as $message)
		create_tags_from_item($message["id"]);
}

function update_items() {
	$messages = q("SELECT `id` FROM `item` where tag !='' ORDER BY `created` DESC LIMIT 100");

	foreach ($messages as $message)
		create_tags_from_item($message["id"]);
}

//update_items();
//create_tags_from_item(265194);
//create_tags_from_itemuri("infoagent@diasp.org:cce94abd104c06e8", 2);
?>
