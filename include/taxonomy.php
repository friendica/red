<?php /** @file */

// post categories and "save to file" use the same item.file table for storage.
// We will differentiate the different uses by wrapping categories in angle brackets
// and save to file categories in square brackets.
// To do this we need to escape these characters if they appear in our tag. 

function file_tag_encode($s) {
	return str_replace(array('<','>','[',']'),array('%3c','%3e','%5b','%5d'),$s);
}

function file_tag_decode($s) {
	return str_replace(array('%3c','%3e','%5b','%5d'),array('<','>','[',']'),$s);
}

function file_tag_file_query($table,$s,$type = 'file') {

	if($type == 'file')
		$termtype = TERM_FILE;
	else
		$termtype = TERM_CATEGORY;

	return sprintf(" AND " . (($table) ? dbesc($table) . '.' : '') . "id in (select term.oid from term where term.type = %d and term.term = '%s' and term.uid = " . (($table) ? dbesc($table) . '.' : '') . "uid ) ",
		intval($termtype),
		protect_sprintf(dbesc($s))
	);
}

function term_query($table,$s,$type = TERM_UNKNOWN) {

	return sprintf(" AND " . (($table) ? dbesc($table) . '.' : '') . "id in (select term.oid from term where term.type = %d and term.term = '%s' and term.uid = " . (($table) ? dbesc($table) . '.' : '') . "uid ) ",
		intval($type),
		protect_sprintf(dbesc($s))
	);
}


function store_item_tag($uid,$iid,$otype,$type,$term,$url = '') {
	if(! $term) 
		return false;
	$r = q("select * from term 
		where uid = %d and oid = %d and otype = %d and type = %d 
		and term = '%s' and url = '%s' ",
		intval($uid),
		intval($iid),
		intval($otype),
		intval($type),
		dbesc($term),
		dbesc($url)
	);
	if($r)
		return false;
	$r = q("insert into term (uid, oid, otype, type, term, url)
		values( %d, %d, %d, %d, '%s', '%s') ",
		intval($uid),
		intval($iid),
		intval($otype),
		intval($type),
		dbesc($term),
		dbesc($url)
	);
	return $r;
}
		
function get_terms_oftype($arr,$type) {
	$ret = array();
	if(! (is_array($arr) && count($arr)))
		return $ret;

	if(! is_array($type))
		$type = array($type);

	foreach($type as $t)
		foreach($arr as $x)
			if($x['type'] == $t)
				$ret[] = $x;
	return $ret;
}

function format_term_for_display($term) {
	$s = '';
	if($term['type'] == TERM_HASHTAG)
		$s .= '#';
	elseif($term['type'] == TERM_MENTION)
		$s .= '@';

	if($term['url']) $s .= '<a target="extlink" href="' . $term['url'] . '">' . htmlspecialchars($term['term']) . '</a>';
	else $s .= htmlspecialchars($term['term']);
	return $s;
}

