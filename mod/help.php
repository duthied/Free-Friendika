<?php
require_once('library/markdown.php');

if (!function_exists('load_doc_file')) {

	function load_doc_file($s) {
		global $lang;
		if (!isset($lang))
			$lang = 'en';
		$b = basename($s);
		$d = dirname($s);
		if (file_exists("$d/$lang/$b"))
			return file_get_contents("$d/$lang/$b");
		if (file_exists($s))
			return file_get_contents($s);
		return '';
	}

}

function help_content(&$a) {

	nav_set_selected('help');

	global $lang;

	$text = '';

	if ($a->argc > 1) {
		$path = '';
		for($x = 1; $x < argc(); $x ++) {
			if(strlen($path))
				$path .= '/';
			$path .= argv($x);
		}
		$title = basename($path);

		$text = load_doc_file('doc/' . $path . '.md');
		$a->page['title'] = t('Help:') . ' ' . str_replace('-', ' ', notags($title));
	}
	$home = load_doc_file('doc/Home.md');
	if (!$text) {
		$text = $home;
		$a->page['title'] = t('Help');
	} else {
		$a->page['aside'] = Markdown($home);
	}

	if (!strlen($text)) {
		header($_SERVER["SERVER_PROTOCOL"] . ' 404 ' . t('Not Found'));
		$tpl = get_markup_template("404.tpl");
		return replace_macros($tpl, array(
					'$message' => t('Page not found.')
				));
	}

	$html = Markdown($text);
	$html = "<style>.md_warning { padding: 1em; border: #ff0000 solid 2px; background-color: #f9a3a3; color: #ffffff;</style>".$html;
	return $html;

}
