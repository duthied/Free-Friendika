<?php

	$arr = [];

	$files = ['index.php','boot.php'];
	$files = array_merge($files,glob('mod/*'),glob('include/*'),glob('addon/*/*'));


	foreach($files as $file) {
		$str = file_get_contents($file);

		$pat = '| L10n::t\(([^\)]*)\)|';
		$patt = '| L10n::tt\(([^\)]*)\)|';

		preg_match_all($pat,$str,$matches);
		preg_match_all($patt, $str, $matchestt);


		if(count($matches)){
			foreach($matches[1] as $match) {
				if(! in_array($match,$arr))
					$arr[] = $match;
			}
		}
		if(count($matchestt)){
			foreach($matchestt[1] as $match) {
				$matchtkns = preg_split("|[ \t\r\n]*,[ \t\r\n]*|",$match);
				if (count($matchtkns)==3 && !in_array($matchtkns,$arr)){
					$arr[] = $matchtkns;
				}
			}
		}

	}

	$s = '<?php' . "\n" . 'use Friendica\Core\L10n;' . "\n";
	$s .= '
function string_plural_select($n){
	return ($n != 1);
}

';

	foreach($arr as $a) {
		if (is_array($a)){
			if(substr($a[1],0,1) == '$')
				continue;
			$s .= '$a->strings[' . $a[0] . "] = array(\n";
			$s .= "\t0 => ". $a[0]. ",\n";
			$s .= "\t1 => ". $a[1]. ",\n";
			$s .= ");\n";
		} else {
			if(substr($a,0,1) == '$')
				continue;
			$s .= '$a->strings[' . $a . '] = '. $a . ';' . "\n";
		}
	}

	$zones = timezone_identifiers_list();
	foreach($zones as $zone)
		$s .= '$a->strings[\'' . $zone . '\'] = \'' . $zone . '\';' . "\n";

	echo $s;