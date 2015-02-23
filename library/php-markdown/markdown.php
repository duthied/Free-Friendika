<?php
//# Install PSR-0-compatible class autoloader
//spl_autoload_register(function($class){
//	require preg_replace('{\\\\|_(?!.*\\\\)}', DIRECTORY_SEPARATOR, ltrim($class, '\\')).'.php';
//});

require_once("library/php-markdown/Michelf/MarkdownExtra.inc.php");
# Get Markdown class
use \Michelf\MarkdownExtra;

function Markdown($text) {
	# Read file and pass content through the Markdown parser
	$html = MarkdownExtra::defaultTransform($text);

	return $html;
}
?>
