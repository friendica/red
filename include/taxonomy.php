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
	else
		return $s;

	if($term['url']) 
		$s .= '<a href="' . $term['url'] . '">' . htmlspecialchars($term['term']) . '</a>';
	else 
		$s .= htmlspecialchars($term['term']);
	return $s;
}

// Tag cloud functions - need to be adpated to this database format


function tagadelic($uid, $count = 0, $flags = 0, $type = TERM_HASHTAG) {
dbg(1);
	if($flags)
		$sql_options = " and (item_flags & " . intval($flags) . ") ";
	// Fetch tags
	$r = q("select term, count(term) as total from term left join item on term.oid = item.id
		where term.uid = %d and term.type = %d 
		and otype = %d
		$sql_options
		group by term order by total desc %s",
		intval($uid),
		intval($type),
		intval(TERM_OBJ_POST),
		((intval($count)) ? "limit $count" : '')
	);
dbg(0);
	if(! $r)
		return array();
  
  	// Find minimum and maximum log-count.
	$tags = array();
	$min = 1e9;
	$max = -1e9;

	$x = 0;
	foreach($r as $rr) {
		$tags[$x][0] = $rr['term'];
		$tags[$x][1] = log($rr['total']);
		$tags[$x][2] = 0;
		$min = min($min,$tags[$x][1]);
		$max = max($max,$tags[$x][1]);
		$x ++;
	}

	usort($tags,'tags_sort');

	$range = max(.01, $max - $min) * 1.0001;

	for($x = 0; $x < count($tags); $x ++) {
		$tags[$x][2] = 1 + floor(5 * ($tags[$x][1] - $min) / $range);
	}

	return $tags;
}

function tags_sort($a,$b) {
   if($a[0] == $b[0])
	 return 0;
   return((strtolower($a[0]) < strtolower($b[0])) ? -1 : 1);
}


function tagblock($link,$uid,$count = 0,$flags = 0,$type = TERM_HASHTAG) {
  $o = '';
  $tab = 0;
  $r = tagadelic($uid,$count,$flags,$type);

  if($r) {
	$o = '<div class="tagblock widget"><h3>' . t('Tags') . '</h3><div class="tags" align="center">';
	foreach($r as $rr) { 
	  $o .= '<a href="'.$link .'/' . '?f=&tag=' . urlencode($rr[0]).'" class="tag'.$rr[2].'">'.$rr[0].'</a> ' . "\r\n";
	}
	$o .= '</div></div>';
  }
	return $o;
}
