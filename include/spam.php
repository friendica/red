<?php /** @file */


function string_splitter($s) {

	if(! $s)
		return array();

	$s = preg_replace('/\pP+/','',$s);

	$x = mb_split("\[|\]|\s",$s);

	$ret = array();
	if($x) {
		foreach($x as $y) {
			if(mb_strlen($y) > 2)
				$ret[] = substr($y,0,64);
		}
	}
	return $ret;
}



function get_words($uid,$list) {

	stringify($list,true);

	$r = q("select * from spam where term in ( " . $list . ") and uid = %d",
		intval($uid)
	);

	return $r;
}

