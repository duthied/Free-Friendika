<?php
require_once("library/parsedown/Parsedown.php");

function Markdown($text) {

	// Bugfix for the library:
	// "[Title](http://domain.tld/ )" isn't handled correctly
	$text = preg_replace("/\[(.*?)\]\s*?\(\s*?(\S*?)\s*?\)/ism", '[$1]($2)', $text);

	$Parsedown = new Parsedown();
	return($Parsedown->text($text));
}
?>
