<?php
require_once("library/parsedown/Parsedown.php");

function Markdown($text) {
	$Parsedown = new Parsedown();
	return($Parsedown->text($text));
}
?>
