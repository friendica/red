<?php

	$arr = array();

	$files = array('index.php','boot.php');
	$files = array_merge($files,glob('mod/*'),glob('include/*'));


	foreach($files as $file) {
		$str = file_get_contents($file);

		$pat = '| t\(([^\)]*)\)|';

		preg_match_all($pat,$str,$matches);

		if(! count($matches))
			continue;

		foreach($matches[1] as $match) {
			if(! in_array($match,$arr))
				$arr[] = $match;
		}

	}

	$s = '<?php' . "\n";
	foreach($arr as $a) {
		if(substr($a,0,1) == '$')
			continue;

		$s .= '$a->strings[' . $a . '] = ' . $a . ';' . "\n";
	}

	$zones = timezone_identifiers_list();
	foreach($zones as $zone)
		$s .= '$a->strings[\'' . $zone . '\'] = \'' . $zone . '\';' . "\n";
	
	echo $s;