#!/usr/bin/env php
<?php

/**
 * @file util/extract.php
 *
 * Extracts translation strings from the Friendica project's files to be exported
 * to Transifex for translation.
 *
 * Outputs a PHP file with language strings used by Friendica
 */

$s = '<?php' . PHP_EOL;
$s .= '
function string_plural_select($n){
	return ($n != 1);
}

';

$arr = [];

$files = ['index.php', 'boot.php'];
$files = array_merge(
	$files,
	glob('mod/*'),
	glob('include/*'),
	glob('addon/*/*'),
	glob_recursive('src')
);

foreach ($files as $file) {
	$str = file_get_contents($file);

	$pat = '|L10n::t\(([^\)]*+)[\)]|';
	$patt = '|L10n::tt\(([^\)]*+)[\)]|';

	preg_match_all($pat, $str, $matches);
	preg_match_all($patt, $str, $matchestt);

	if (count($matches) || count($matchestt)) {
		$s .= '// ' . $file . PHP_EOL;
	}

	if (count($matches)) {
		foreach ($matches[1] as $long_match) {
			$match_arr = preg_split('/(?<=[\'"])\s*,/', $long_match);
			$match = $match_arr[0];
			if (!in_array($match, $arr)) {
				if (substr($match, 0, 1) == '$') {
					continue;
				}

				$arr[] = $match;

				$s .= '$a->strings[' . $match . '] = ' . $match . ';' . "\n";
			}
		}
	}
	if (count($matchestt)) {
		foreach ($matchestt[1] as $match) {
			$matchtkns = preg_split("|[ \t\r\n]*,[ \t\r\n]*|", $match);
			if (count($matchtkns) == 3 && !in_array($matchtkns[0], $arr)) {
				if (substr($matchtkns[1], 0, 1) == '$') {
					continue;
				}

				$arr[] = $matchtkns[0];

				$s .= '$a->strings[' . $matchtkns[0] . "] = array(\n";
				$s .= "\t0 => " . $matchtkns[0] . ",\n";
				$s .= "\t1 => " . $matchtkns[1] . ",\n";
				$s .= ");\n";
			}
		}
	}
}

$s .= '// Timezones' . PHP_EOL;

$zones = timezone_identifiers_list();
foreach ($zones as $zone) {
	$s .= '$a->strings[\'' . $zone . '\'] = \'' . $zone . '\';' . "\n";
}

echo $s;

function glob_recursive($path) {
	$dir_iterator = new RecursiveDirectoryIterator($path);
	$iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);

	$return = [];
	foreach ($iterator as $file) {
		if ($file->getBasename() != '.' && $file->getBasename() != '..') {
			$return[] = $file->getPathname();
		}
	}

	return $return;
}
