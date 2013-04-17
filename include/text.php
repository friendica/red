<?php /** @file */

// This is our template processor.
// $s is the string requiring macro substitution.
// $r is an array of key value pairs (search => replace)
// returns substituted string.


require_once("include/template_processor.php");


function replace_macros($s,$r) {
	global $t;

//	$ts = microtime();
	$a = get_app();

	if($a->get_template_engine() === 'smarty3') {
		$output = '';
		if(gettype($s) !== 'NULL') {
			$template = '';
			if(gettype($s) === 'string') {
				$template = $s;
				$s = new FriendicaSmarty();
			}
			foreach($r as $key=>$value) {
				if($key[0] === '$') {
					$key = substr($key, 1);
				}
				$s->assign($key, $value);
			}
			$output = $s->parsed($template);
		}
	}
	else {
		$r =  $t->replace($s,$r);
	
		$output = template_unescape($r);
	}
//	$tt = microtime() - $ts;
//	$a->page['debug'] .= "$tt <br>\n";
	return $output;
}


// random string, there are 86 characters max in text mode, 128 for hex
// output is urlsafe

define('RANDOM_STRING_HEX',  0x00 );
define('RANDOM_STRING_TEXT', 0x01 );


function random_string($size = 64,$type = RANDOM_STRING_HEX) {
	// generate a bit of entropy and run it through the whirlpool
	$s = hash('whirlpool', (string) rand() . uniqid(rand(),true) . (string) rand(),(($type == RANDOM_STRING_TEXT) ? true : false));
	$s = (($type == RANDOM_STRING_TEXT) ? str_replace("\n","",base64url_encode($s,true)) : $s);
	return(substr($s,0,$size));
}

/**
 * This is our primary input filter. 
 *
 * The high bit hack only involved some old IE browser, forget which (IE5/Mac?)
 * that had an XSS attack vector due to stripping the high-bit on an 8-bit character
 * after cleansing, and angle chars with the high bit set could get through as markup.
 * 
 * This is now disabled because it was interfering with some legitimate unicode sequences 
 * and hopefully there aren't a lot of those browsers left. 
 *
 * Use this on any text input where angle chars are not valid or permitted
 * They will be replaced with safer brackets. This may be filtered further
 * if these are not allowed either.   
 *
 */


function notags($string) {

	return(str_replace(array("<",">"), array('[',']'), $string));

//  High-bit filter no longer used
//	return(str_replace(array("<",">","\xBA","\xBC","\xBE"), array('[',']','','',''), $string));
}

// use this on "body" or "content" input where angle chars shouldn't be removed,
// and allow them to be safely displayed.


function escape_tags($string) {

	return(htmlspecialchars($string, ENT_COMPAT, 'UTF-8', false));

}


// generate a string that's random, but usually pronounceable. 
// used to generate initial passwords


function autoname($len) {

	if($len <= 0)
		return '';

	$vowels = array('a','a','ai','au','e','e','e','ee','ea','i','ie','o','ou','u'); 
	if(mt_rand(0,5) == 4)
		$vowels[] = 'y';

	$cons = array(
			'b','bl','br',
			'c','ch','cl','cr',
			'd','dr',
			'f','fl','fr',
			'g','gh','gl','gr',
			'h',
			'j',
			'k','kh','kl','kr',
			'l',
			'm',
			'n',
			'p','ph','pl','pr',
			'qu',
			'r','rh',
			's','sc','sh','sm','sp','st',
			't','th','tr',
			'v',
			'w','wh',
			'x',
			'z','zh'
			);

	$midcons = array('ck','ct','gn','ld','lf','lm','lt','mb','mm', 'mn','mp',
				'nd','ng','nk','nt','rn','rp','rt');

	$noend = array('bl', 'br', 'cl','cr','dr','fl','fr','gl','gr',
				'kh', 'kl','kr','mn','pl','pr','rh','tr','qu','wh');

	$start = mt_rand(0,2);
  	if($start == 0)
    		$table = $vowels;
  	else
    		$table = $cons;

	$word = '';

	for ($x = 0; $x < $len; $x ++) {
  		$r = mt_rand(0,count($table) - 1);
  		$word .= $table[$r];
  
  		if($table == $vowels)
    			$table = array_merge($cons,$midcons);
  		else
    			$table = $vowels;

	}

	$word = substr($word,0,$len);

	foreach($noend as $noe) {
  		if((strlen($word) > 2) && (substr($word,-2) == $noe)) {
    			$word = substr($word,0,-1);
    			break;
  		}
	}
	if(substr($word,-1) == 'q')
		$word = substr($word,0,-1);    
	return $word;
}


// escape text ($str) for XML transport
// returns escaped text.


function xmlify($str) {
	$buffer = '';
	
	$len = mb_strlen($str);
	for($x = 0; $x < $len; $x ++) {
		$char = mb_substr($str,$x,1);
        
		switch( $char ) {

			case "\r" :
				break;
			case "&" :
				$buffer .= '&amp;';
				break;
			case "'" :
				$buffer .= '&apos;';
				break;
			case "\"" :
				$buffer .= '&quot;';
				break;
			case '<' :
				$buffer .= '&lt;';
				break;
			case '>' :
				$buffer .= '&gt;';
				break;
			case "\n" :
				$buffer .= "\n";
				break;
			default :
				$buffer .= $char;
				break;
		}	
	}
	$buffer = trim($buffer);
	return($buffer);
}

// undo an xmlify
// pass xml escaped text ($s), returns unescaped text


function unxmlify($s) {
	$ret = str_replace('&amp;','&', $s);
	$ret = str_replace(array('&lt;','&gt;','&quot;','&apos;'),array('<','>','"',"'"),$ret);
	return $ret;	
}

// convenience wrapper, reverse the operation "bin2hex"

// This is a built-in function in php >= 5.4

if(! function_exists('hex2bin')) {
function hex2bin($s) {
	if(! (is_string($s) && strlen($s)))
		return '';

	if(! ctype_xdigit($s)) {
		return($s);
	}

	return(pack("H*",$s));
}}


// Automatic pagination.
// To use, get the count of total items.
// Then call $a->set_pager_total($number_items);
// Optionally call $a->set_pager_itemspage($n) to the number of items to display on each page
// Then call paginate($a) after the end of the display loop to insert the pager block on the page
// (assuming there are enough items to paginate).
// When using with SQL, the setting LIMIT %d, %d => $a->pager['start'],$a->pager['itemspage']
// will limit the results to the correct items for the current page. 
// The actual page handling is then accomplished at the application layer. 


function paginate(&$a) {
	$o = '';
	$stripped = preg_replace('/(&page=[0-9]*)/','',$a->query_string);

//	$stripped = preg_replace('/&zid=(.*?)([\?&]|$)/ism','',$stripped);

	$stripped = str_replace('q=','',$stripped);
	$stripped = trim($stripped,'/');
	$pagenum = $a->pager['page'];
	$url = $a->get_baseurl() . '/' . $stripped;


	  if($a->pager['total'] > $a->pager['itemspage']) {
		$o .= '<div class="pager">';
    		if($a->pager['page'] != 1)
			$o .= '<span class="pager_prev">'."<a href=\"$url".'&page='.($a->pager['page'] - 1).'">' . t('prev') . '</a></span> ';

		$o .=  "<span class=\"pager_first\"><a href=\"$url"."&page=1\">" . t('first') . "</a></span> ";

    		$numpages = $a->pager['total'] / $a->pager['itemspage'];

			$numstart = 1;
    		$numstop = $numpages;

    		if($numpages > 14) {
      			$numstart = (($pagenum > 7) ? ($pagenum - 7) : 1);
      			$numstop = (($pagenum > ($numpages - 7)) ? $numpages : ($numstart + 14));
    		}
   
		for($i = $numstart; $i <= $numstop; $i++){
      			if($i == $a->pager['page'])
				$o .= '<span class="pager_current">'.(($i < 10) ? '&nbsp;'.$i : $i);
			else
				$o .= "<span class=\"pager_n\"><a href=\"$url"."&page=$i\">".(($i < 10) ? '&nbsp;'.$i : $i)."</a>";
			$o .= '</span> ';
		}

		if(($a->pager['total'] % $a->pager['itemspage']) != 0) {
			if($i == $a->pager['page'])
				$o .= '<span class="pager_current">'.(($i < 10) ? '&nbsp;'.$i : $i);
			else
				$o .= "<span class=\"pager_n\"><a href=\"$url"."&page=$i\">".(($i < 10) ? '&nbsp;'.$i : $i)."</a>";
			$o .= '</span> ';
		}

		$lastpage = (($numpages > intval($numpages)) ? intval($numpages)+1 : $numpages);
		$o .= "<span class=\"pager_last\"><a href=\"$url"."&page=$lastpage\">" . t('last') . "</a></span> ";

    		if(($a->pager['total'] - ($a->pager['itemspage'] * $a->pager['page'])) > 0)
			$o .= '<span class="pager_next">'."<a href=\"$url"."&page=".($a->pager['page'] + 1).'">' . t('next') . '</a></span>';
		$o .= '</div>'."\r\n";
	}
	return $o;
}


function alt_pager(&$a, $i, $more = '', $less = '') {

	$o = '';

	if(! $more)
		$more = t('older');
	if(! $less)
		$less = t('newer');

	$stripped = preg_replace('/(&page=[0-9]*)/','',$a->query_string);
	$stripped = str_replace('q=','',$stripped);
	$stripped = trim($stripped,'/');
	$pagenum = $a->pager['page'];
	$url = $a->get_baseurl() . '/' . $stripped;

	$o .= '<div class="pager">';

	if($a->pager['page'] > 1)
	  $o .= "<a href=\"$url"."&page=".($a->pager['page'] - 1).'">' . $less . '</a>';
	if($i > 0 && $i == $a->pager['itemspage']) {
		if($a->pager['page']>1)
			$o .= " | ";
		$o .= "<a href=\"$url"."&page=".($a->pager['page'] + 1).'">' . $more . '</a>';
	}


	$o .= '</div>'."\r\n";

	return $o;
}

// Turn user/group ACLs stored as angle bracketed text into arrays


function expand_acl($s) {

	// turn string array of angle-bracketed elements into string array
	// e.g. "<123xyz><246qyo><sxo33e>" => array(123xyz,246qyo,sxo33e);

	$ret = array();

	if(strlen($s)) {
		$t = str_replace('<','',$s);
		$a = explode('>',$t);
		foreach($a as $aa) {
			if($aa)
				$ret[] = $aa;
		}
	}
	return $ret;
}		

// Used to wrap ACL elements in angle brackets for storage 


function sanitise_acl(&$item) {
	if(strlen($item))
		$item = '<' . notags(trim($item)) . '>';
	else
		unset($item);
}


// Convert an ACL array to a storable string


function perms2str($p) {
	$ret = '';

	if(is_array($p))
		$tmp = $p;
	else
		$tmp = explode(',',$p);

	if(is_array($tmp)) {
		array_walk($tmp,'sanitise_acl');
		$ret = implode('',$tmp);
	}
	return $ret;
}

// generate a guaranteed unique (for this domain) item ID for ATOM
// safe from birthday paradox


function item_message_id() {

	do {
		$dups = false;
		$hash = random_string();

		$mid = $hash . '@' . get_app()->get_hostname();

		$r = q("SELECT `id` FROM `item` WHERE `mid` = '%s' LIMIT 1",
			dbesc($mid));
		if(count($r))
			$dups = true;
	} while($dups == true);
	return $mid;
}

// Generate a guaranteed unique photo ID.
// safe from birthday paradox


function photo_new_resource() {

	do {
		$found = false;
		$resource = hash('md5',uniqid(mt_rand(),true));
		$r = q("SELECT `id` FROM `photo` WHERE `resource_id` = '%s' LIMIT 1",
			dbesc($resource)
		);
		if(count($r))
			$found = true;
	} while($found == true);
	return $resource;
}





// for html,xml parsing - let's say you've got
// an attribute foobar="class1 class2 class3"
// and you want to find out if it contains 'class3'.
// you can't use a normal sub string search because you
// might match 'notclass3' and a regex to do the job is 
// possible but a bit complicated. 
// pass the attribute string as $attr and the attribute you 
// are looking for as $s - returns true if found, otherwise false

function attribute_contains($attr,$s) {
	$a = explode(' ', $attr);
	if(count($a) && in_array($s,$a))
		return true;
	return false;
}


function logger($msg,$level = 0) {
	// turn off logger in install mode
	global $a;
	global $db;

	if(($a->module == 'install') || (! ($db && $db->connected))) return;

	$debugging = get_config('system','debugging');
	$loglevel  = intval(get_config('system','loglevel'));
	$logfile   = get_config('system','logfile');

	if((! $debugging) || (! $logfile) || ($level > $loglevel))
		return;
	
	@file_put_contents($logfile, datetime_convert() . ':' . session_id() . ' ' . $msg . "\n", FILE_APPEND);
	return;
}


// This is a special logging facility for developers. It allows one to target specific things to trace/debug
// and is identical to logger() with the exception of the log filename. This allows one to isolate specific
// calls while allowing logger() to paint a bigger picture of overall activity and capture more detail.
// If you find dlogger() calls in checked in code, you are free to remove them - so as to provide a noise-free
// development environment which responds to events you are targetting personally. 


function dlogger($msg,$level = 0) {
	// turn off logger in install mode
	global $a;
	global $db;

	if(($a->module == 'install') || (! ($db && $db->connected))) return;

	$debugging = get_config('system','debugging');
	$loglevel  = intval(get_config('system','loglevel'));
	$logfile   = get_config('system','dlogfile');

	if((! $debugging) || (! $logfile) || ($level > $loglevel))
		return;
	
	@file_put_contents($logfile, datetime_convert() . ':' . session_id() . ' ' . $msg . "\n", FILE_APPEND);
	return;
}


function profiler($t1,$t2,$label) {
	if(file_exists('profiler.out') && $t1 && t2)
		@file_put_contents('profiler.out', sprintf('%01.4f %s',$t2 - $t1,$label) . "\n", FILE_APPEND);
}



function activity_match($haystack,$needle) {
	if(($haystack === $needle) || ((basename($needle) === $haystack) && strstr($needle,NAMESPACE_ACTIVITY_SCHEMA)))
		return true;
	return false;
}


// Pull out all #hashtags and @person tags from $s;
// We also get @person@domain.com - which would make 
// the regex quite complicated as tags can also
// end a sentence. So we'll run through our results
// and strip the period from any tags which end with one.
// Returns array of tags found, or empty array.



function get_tags($s) {
	$ret = array();

	// ignore anything in a code block

	$s = preg_replace('/\[code\](.*?)\[\/code\]/sm','',$s);

	// Match full names against @tags including the space between first and last
	// We will look these up afterward to see if they are full names or not recognisable.

	if(preg_match_all('/(@[^ \x0D\x0A,:?]+ [^ \x0D\x0A@,:?]+)([ \x0D\x0A@,:?]|$)/',$s,$match)) {
		foreach($match[1] as $mtch) {
			if(strstr($mtch,"]")) {
				// we might be inside a bbcode color tag - leave it alone
				continue;
			}
			if(substr($mtch,-1,1) === '.')
				$ret[] = substr($mtch,0,-1);
			else
				$ret[] = $mtch;
		}
	}

	// Otherwise pull out single word tags. These can be @nickname, @first_last
	// and #hash tags.

	if(preg_match_all('/([@#][^ \x0D\x0A,;:?]+)([ \x0D\x0A,;:?]|$)/',$s,$match)) {
		foreach($match[1] as $mtch) {
			if(strstr($mtch,"]")) {
				// we might be inside a bbcode color tag - leave it alone
				continue;
			}
			if(substr($mtch,-1,1) === '.')
				$mtch = substr($mtch,0,-1);
			// ignore strictly numeric tags like #1
			if((strpos($mtch,'#') === 0) && ctype_digit(substr($mtch,1)))
				continue;
			// try not to catch url fragments
			if(strpos($s,$mtch) && preg_match('/[a-zA-z0-9\/]/',substr($s,strpos($s,$mtch)-1,1)))
				continue;
			$ret[] = $mtch;
		}
	}
	return $ret;
}


// quick and dirty quoted_printable encoding


function qp($s) {
return str_replace ("%","=",rawurlencode($s));
} 




function get_mentions($item,$tags) {
	$o = '';

	if(! count($tags))
		return $o;

	foreach($tags as $x) {
		if($x['type'] == TERM_MENTION) {
			$o .= "\t\t" . '<link rel="mentioned" href="' . $x['url'] . '" />' . "\r\n";
			$o .= "\t\t" . '<link rel="ostatus:attention" href="' . $x['url'] . '" />' . "\r\n";
		}
	}
	return $o;
}


function contact_block() {
	$o = '';
	$a = get_app();

	$shown = get_pconfig($a->profile['uid'],'system','display_friend_count');

	if($shown === false)
		$shown = 24;
	if($shown == 0)
		return;

	if((! is_array($a->profile)) || ($a->profile['hide_friends']))
		return $o;
	$r = q("SELECT COUNT(abook_id) AS total FROM abook WHERE abook_channel = %d and abook_flags = 0",
			intval($a->profile['uid'])
	);
	if(count($r)) {
		$total = intval($r[0]['total']);
	}
	if(! $total) {
		$contacts = t('No connections');
		$micropro = Null;
		
	} else {

		$r = q("SELECT abook.*, xchan.* FROM abook left join xchan on abook.abook_xchan = xchan.xchan_hash WHERE abook_channel = %d AND abook_flags = 0 ORDER BY RAND() LIMIT %d",
				intval($a->profile['uid']),
				intval($shown)
		);

		if(count($r)) {
			$contacts = sprintf( tt('%d Connection','%d Connections', $total),$total);
			$micropro = Array();
			foreach($r as $rr) {
				$micropro[] = micropro($rr,true,'mpfriend');
			}
		}
	}
	
	$tpl = get_markup_template('contact_block.tpl');
	$o = replace_macros($tpl, array(
		'$contacts' => $contacts,
		'$nickname' => $a->profile['channel_address'],
		'$viewconnections' => t('View Connections'),
		'$micropro' => $micropro,
	));

	$arr = array('contacts' => $r, 'output' => $o);

	call_hooks('contact_block_end', $arr);
	return $o;

}


function chanlink_hash($s) {
	return z_root() . '/chanview?f=&hash=' . urlencode($s);
}

function chanlink_url($s) {
	return z_root() . '/chanview?f=&url=' . urlencode($s);
}


function chanlink_cid($d) {
	return z_root() . '/chanview?f=&cid=' . intval($d);
}

function magiclink_url($observer,$myaddr,$url) {
	return (($observer) 
		? z_root() . '/magic?f=&dest=' . $url . '&addr=' . $myaddr 
		: $url
	);
}



function micropro($contact, $redirect = false, $class = '', $textmode = false) {

	if($contact['click'])
		$url = '#';
	else
		$url = chanlink_hash($contact['xchan_hash']);

	return replace_macros(get_markup_template(($textmode)?'micropro_txt.tpl':'micropro_img.tpl'),array(
		'$click' => (($contact['click']) ? $contact['click'] : ''),
		'$class' => $class,
		'$url' => $url,
		'$photo' => $contact['xchan_photo_s'],
		'$name' => $contact['xchan_name'],
		'$title' => $contact['xchan_name'] . ' [' . $contact['xchan_addr'] . ']',
	));
}




function search($s,$id='search-box',$url='/search',$save = false) {
	$a = get_app();
	$o  = '<div id="' . $id . '">';
	$o .= '<form action="' . $a->get_baseurl((stristr($url,'network')) ? true : false) . $url . '" method="get" >';
	$o .= '<input type="text" name="search" id="search-text" placeholder="' . t('Search') . '" value="' . $s .'" />';
	$o .= '<input type="submit" name="submit" id="search-submit" value="' . t('Search') . '" />'; 
	if($save)
		$o .= '<input type="submit" name="save" id="search-save" value="' . t('Save') . '" />'; 
	$o .= '</form></div>';
	return $o;
}


function valid_email($x){

	if(get_config('system','disable_email_validation'))
		return true;

	if(preg_match('/^[_a-zA-Z0-9\-\+]+(\.[_a-zA-Z0-9\-\+]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$/',$x))
		return true;
	return false;
}


/**
 *
 * Function: linkify
 *
 * Replace naked text hyperlink with HTML formatted hyperlink
 *
 */


function linkify($s) {
	$s = preg_replace("/(https?\:\/\/[a-zA-Z0-9\:\/\-\?\&\;\.\=\_\@\~\#\'\%\$\!\+]*)/", ' <a href="$1" >$1</a>', $s);
	$s = preg_replace("/\<(.*?)(src|href)=(.*?)\&amp\;(.*?)\>/ism",'<$1$2=$3&$4>',$s);
	return($s);
}

function get_poke_verbs() {
	
	// index is present tense verb
	// value is array containing past tense verb, translation of present, translation of past

	$arr = array(
		'poke' => array( 'poked', t('poke'), t('poked')),
		'ping' => array( 'pinged', t('ping'), t('pinged')),
		'prod' => array( 'prodded', t('prod'), t('prodded')),
		'slap' => array( 'slapped', t('slap'), t('slapped')),
		'finger' => array( 'fingered', t('finger'), t('fingered')),
		'rebuff' => array( 'rebuffed', t('rebuff'), t('rebuffed')),
	);
	call_hooks('poke_verbs', $arr);
	return $arr;
}

function get_mood_verbs() {
	
	// index is present tense verb
	// value is array containing past tense verb, translation of present, translation of past

	$arr = array(
		'happy'      => t('happy'),
		'sad'        => t('sad'),
		'mellow'     => t('mellow'),
		'tired'      => t('tired'),
		'perky'      => t('perky'),
		'angry'      => t('angry'),
		'stupefied'  => t('stupified'),
		'puzzled'    => t('puzzled'),
		'interested' => t('interested'),
		'bitter'     => t('bitter'),
		'cheerful'   => t('cheerful'),
		'alive'      => t('alive'),
		'annoyed'    => t('annoyed'),
		'anxious'    => t('anxious'),
		'cranky'     => t('cranky'),
		'disturbed'  => t('disturbed'),
		'frustrated' => t('frustrated'),
		'motivated'  => t('motivated'),
		'relaxed'    => t('relaxed'),
		'surprised'  => t('surprised'),
	);

	call_hooks('mood_verbs', $arr);
	return $arr;
}


/**
 * 
 * Function: smilies
 *
 * Description:
 * Replaces text emoticons with graphical images
 *
 * @Parameter: string $s
 *
 * Returns string
 *
 * It is expected that this function will be called using HTML text.
 * We will escape text between HTML pre and code blocks from being 
 * processed. 
 * 
 * At a higher level, the bbcode [nosmile] tag can be used to prevent this 
 * function from being executed by the prepare_text() routine when preparing
 * bbcode source for HTML display
 *
 */


function smilies($s, $sample = false) {

	$a = get_app();

	if(intval(get_config('system','no_smilies')) 
		|| (local_user() && intval(get_pconfig(local_user(),'system','no_smilies'))))
		return $s;

	$s = preg_replace_callback('/<pre>(.*?)<\/pre>/ism','smile_encode',$s);
	$s = preg_replace_callback('/<code>(.*?)<\/code>/ism','smile_encode',$s);

	$texts =  array( 
		'&lt;3', 
		'&lt;/3', 
		'&lt;\\3', 
		':-)', 
		';-)', 
		':-(', 
		':-P', 
		':-p', 
		':-"', 
		':-&quot;', 
		':-x', 
		':-X', 
		':-D', 
		'8-|', 
		'8-O', 
		':-O', 
		'\\o/', 
		'o.O', 
		'O.o', 
		'o_O', 
		'O_o', 
		":'(", 
		":-!", 
		":-/", 
		":-[", 
		"8-)",
		':beer', 
		':homebrew', 
		':coffee', 
		':facepalm',
		':like',
		':dislike',
		'~friendika', 
		'~friendica'

	);

	$icons = array(
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-heart.gif" alt="<3" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-brokenheart.gif" alt="</3" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-brokenheart.gif" alt="<\\3" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-smile.gif" alt=":-)" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-wink.gif" alt=";-)" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-frown.gif" alt=":-(" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-tongue-out.gif" alt=":-P" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-tongue-out.gif" alt=":-p" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-kiss.gif" alt=":-\"" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-kiss.gif" alt=":-\"" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-kiss.gif" alt=":-x" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-kiss.gif" alt=":-X" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-laughing.gif" alt=":-D" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-surprised.gif" alt="8-|" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-surprised.gif" alt="8-O" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-surprised.gif" alt=":-O" />',                
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-thumbsup.gif" alt="\\o/" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-Oo.gif" alt="o.O" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-Oo.gif" alt="O.o" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-Oo.gif" alt="o_O" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-Oo.gif" alt="O_o" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-cry.gif" alt=":\'(" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-foot-in-mouth.gif" alt=":-!" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-undecided.gif" alt=":-/" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-embarassed.gif" alt=":-[" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-cool.gif" alt="8-)" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/beer_mug.gif" alt=":beer" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/beer_mug.gif" alt=":homebrew" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/coffee.gif" alt=":coffee" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-facepalm.gif" alt=":facepalm" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/like.gif" alt=":like" />',
		'<img class="smiley" src="' . $a->get_baseurl() . '/images/dislike.gif" alt=":dislike" />',
		'<a href="http://project.friendika.com">~friendika <img class="smiley" src="' . $a->get_baseurl() . '/images/friendika-16.png" alt="~friendika" /></a>',
		'<a href="http://friendica.com">~friendica <img class="smiley" src="' . $a->get_baseurl() . '/images/friendica-16.png" alt="~friendica" /></a>'
	);

	$params = array('texts' => $texts, 'icons' => $icons, 'string' => $s);
	call_hooks('smilie', $params);

	if($sample) {
		$s = '<div class="smiley-sample">';
		for($x = 0; $x < count($params['texts']); $x ++) {
			$s .= '<dl><dt>' . $params['texts'][$x] . '</dt><dd>' . $params['icons'][$x] . '</dd></dl>';
		}
	}
	else {
		$params['string'] = preg_replace_callback('/&lt;(3+)/','preg_heart',$params['string']);
		$s = str_replace($params['texts'],$params['icons'],$params['string']);
	}

	$s = preg_replace_callback('/<pre>(.*?)<\/pre>/ism','smile_decode',$s);
	$s = preg_replace_callback('/<code>(.*?)<\/code>/ism','smile_decode',$s);

	return $s;

}

function smile_encode($m) {
	return(str_replace($m[1],base64url_encode($m[1]),$m[0]));
}

function smile_decode($m) {
	return(str_replace($m[1],base64url_decode($m[1]),$m[0]));
}

// expand <3333 to the correct number of hearts

function preg_heart($x) {
	$a = get_app();
	if(strlen($x[1]) == 1)
		return $x[0];
	$t = '';
	for($cnt = 0; $cnt < strlen($x[1]); $cnt ++)
		$t .= '<img class="smiley" src="' . $a->get_baseurl() . '/images/smiley-heart.gif" alt="<3" />';
	$r =  str_replace($x[0],$t,$x[0]);
	return $r;
}



function day_translate($s) {
	$ret = str_replace(array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
		array( t('Monday'), t('Tuesday'), t('Wednesday'), t('Thursday'), t('Friday'), t('Saturday'), t('Sunday')),
		$s);

	$ret = str_replace(array('January','February','March','April','May','June','July','August','September','October','November','December'),
		array( t('January'), t('February'), t('March'), t('April'), t('May'), t('June'), t('July'), t('August'), t('September'), t('October'), t('November'), t('December')),
		$ret);

	return $ret;
}



function normalise_link($url) {
	$ret = str_replace(array('https:','//www.'), array('http:','//'), $url);
	return(rtrim($ret,'/'));
}

/**
 *
 * Compare two URLs to see if they are the same, but ignore
 * slight but hopefully insignificant differences such as if one 
 * is https and the other isn't, or if one is www.something and 
 * the other isn't - and also ignore case differences.
 *
 * Return true if the URLs match, otherwise false.
 *
 */


function link_compare($a,$b) {
	if(strcasecmp(normalise_link($a),normalise_link($b)) === 0)
		return true;
	return false;
}

// Given an item array, convert the body element from bbcode to html and add smilie icons.
// If attach is true, also add icons for item attachments



function prepare_body($item,$attach = false) {

	$a = get_app();
	call_hooks('prepare_body_init', $item); 

	$s = prepare_text($item['body'],$item['mimetype']);

	$prep_arr = array('item' => $item, 'html' => $s);
	call_hooks('prepare_body', $prep_arr);
	$s = $prep_arr['html'];

	if(! $attach) {
		return $s;
	}

	$arr = json_decode($item['attach'],true);
	if(count($arr)) {
		$s .= '<div class="body-attach">';
		foreach($arr as $r) {
			$matches = false;
			$icon = '';
			$icontype = substr($r['type'],0,strpos($r['type'],'/'));

			switch($icontype) {
				case 'video':
				case 'audio':
				case 'image':
				case 'text':
					$icon = '<div class="attachtype icon s22 type-' . $icontype . '"></div>';
					break;
				default:
					$icon = '<div class="attachtype icon s22 type-unkn"></div>';
					break;
			}

			$title = htmlentities($r['title'], ENT_COMPAT,'UTF-8');
			if(! $title)
				$title = t('unknown.???');
			$title .= ' ' . $r['length'] . ' ' . t('bytes');

			$url = $a->get_baseurl() . '/magic?f=&hash=' . $item['author_xchan'] . '&dest=' . $r['href'] . '/' . $r['revision'];
			$s .= '<a href="' . $url . '" title="' . $title . '" class="attachlink"  >' . $icon . '</a>';
		}
		$s .= '<div class="clear"></div></div>';
	}

	$writeable = ((get_observer_hash() == $item['owner_xchan']) ? true : false); 

	$x = '';
	$terms = get_terms_oftype($item['term'],TERM_CATEGORY);
	if($terms) {
		foreach($terms as $t) {
			if(strlen($x))
				$x .= ',';
			$x .= htmlspecialchars($t['term'],ENT_COMPAT,'UTF-8') 
				. (($writeable) ? ' <a href="' . $a->get_baseurl() . '/filerm/' . $item['id'] . '?f=&cat=' . urlencode($t['term']) . '" title="' . t('remove') . '" >' . t('[remove]') . '</a>' : '');
		}
		if(strlen($x))
			$s .= '<div class="categorytags"><span>' . t('Categories:') . ' </span>' . $x . '</div>'; 


	}

	$x = '';
	$terms = get_terms_oftype($item['term'],TERM_FILE);
	if($terms) {
		foreach($terms as $t) {
			if(strlen($x))
				$x .= '&nbsp;&nbsp;&nbsp;';
			$x .= htmlspecialchars($t['term'],ENT_COMPAT,'UTF-8') 
				. ' <a href="' . $a->get_baseurl() . '/filerm/' . $item['id'] . '?f=&term=' . urlencode($t['term']) . '" title="' . t('remove') . '" >' . t('[remove]') . '</a>';
		}
		if(strlen($x) && (local_user() == $item['uid']))
			$s .= '<div class="filesavetags"><span>' . t('Filed under:') . ' </span>' . $x . '</div>'; 
	}

	// Look for spoiler
	$spoilersearch = '<blockquote class="spoiler">';

	// Remove line breaks before the spoiler
	while ((strpos($s, "\n".$spoilersearch) !== false))
		$s = str_replace("\n".$spoilersearch, $spoilersearch, $s);
	while ((strpos($s, "<br />".$spoilersearch) !== false))
		$s = str_replace("<br />".$spoilersearch, $spoilersearch, $s);

	while ((strpos($s, $spoilersearch) !== false)) {

		$pos = strpos($s, $spoilersearch);
		$rnd = random_string(8);
		$spoilerreplace = '<br /> <span id="spoiler-wrap-'.$rnd.'" style="white-space:nowrap;" class="fakelink" onclick="openClose(\'spoiler-'.$rnd.'\');">'.sprintf(t('Click to open/close')).'</span>'.
	                                '<blockquote class="spoiler" id="spoiler-'.$rnd.'" style="display: none;">';
		$s = substr($s, 0, $pos).$spoilerreplace.substr($s, $pos+strlen($spoilersearch));
	}

	// Look for quote with author
	$authorsearch = '<blockquote class="author">';

	while ((strpos($s, $authorsearch) !== false)) {

		$pos = strpos($s, $authorsearch);
		$rnd = random_string(8);
		$authorreplace = '<br /> <span id="author-wrap-'.$rnd.'" style="white-space:nowrap;" class="fakelink" onclick="openClose(\'author-'.$rnd.'\');">'.sprintf(t('Click to open/close')).'</span>'.
	                                '<blockquote class="author" id="author-'.$rnd.'" style="display: block;">';
		$s = substr($s, 0, $pos).$authorreplace.substr($s, $pos+strlen($authorsearch));
	}

	$prep_arr = array('item' => $item, 'html' => $s);
	call_hooks('prepare_body_final', $prep_arr);

	return $prep_arr['html'];
}


// Given a text string, convert from bbcode to html and add smilie icons.


function prepare_text($text,$content_type = 'text/bbcode') {


	switch($content_type) {

		case 'text/plain':
			$s = escape_tags($text);
			break;

		case 'text/html':
			$s = $text;
			break;

		case 'text/markdown':
			require_once('library/markdown.php');
			$s = Markdown($text);
			break;

		case 'text/bbcode':
		case '':
		default:
			require_once('include/bbcode.php');

			if(stristr($text,'[nosmile]'))
				$s = bbcode($text);
			else
				$s = smilies(bbcode($text));
			$s = zidify_links($s);
			break;
	}

	return $s;
}


/**
 * zidify_callback() and zidify_links() work together to turn any HTML a tags with class="zrl" into zid links
 * These will typically be generated by a bbcode '[zrl]' tag. This is done inside prepare_text() rather than bbcode() 
 * because the latter is used for general purpose conversions and the former is used only when preparing text for
 * immediate display.
 * 
 * Issues: Currently the order of HTML parameters in the text is somewhat rigid and inflexible.
 *    We assume it looks like <a class="zrl" href="xxxxxxxxxx"> and will not work if zrl and href appear in a different order. 
 */


function zidify_callback($match) {
  if (feature_enabled(local_user(),'sendzid')) {
	$replace = '<a' . $match[1] . ' href="' . zid($match[2]) . '"';}

      else {
	  $replace = '<a' . $match[1] . 'class="zrl"' . $match[2] . ' href="' . zid($match[3]) . '"';}
    
	$x = str_replace($match[0],$replace,$match[0]);
	return $x;
}

function zidify_links($s) {
    if (feature_enabled(local_user(),'sendzid')) {
	  $s = preg_replace_callback('/\<a(.*?)href\=\"(.*?)\"/ism','zidify_callback',$s);}
    else {
      $s = preg_replace_callback('/\<a(.*?)class\=\"zrl\"(.*?)href\=\"(.*?)\"/ism','zidify_callback',$s);}

	return $s;
}






/**
 * return atom link elements for all of our hubs
 */


function feed_hublinks() {

	$hub = get_config('system','huburl');

	$hubxml = '';
	if(strlen($hub)) {
		$hubs = explode(',', $hub);
		if(count($hubs)) {
			foreach($hubs as $h) {
				$h = trim($h);
				if(! strlen($h))
					continue;
				$hubxml .= '<link rel="hub" href="' . xmlify($h) . '" />' . "\n" ;
			}
		}
	}
	return $hubxml;
}

/* return atom link elements for salmon endpoints */


function feed_salmonlinks($nick) {

	$a = get_app();

	$salmon  = '<link rel="salmon" href="' . xmlify($a->get_baseurl() . '/salmon/' . $nick) . '" />' . "\n" ;

	// old style links that status.net still needed as of 12/2010 

	$salmon .= '  <link rel="http://salmon-protocol.org/ns/salmon-replies" href="' . xmlify($a->get_baseurl() . '/salmon/' . $nick) . '" />' . "\n" ; 
	$salmon .= '  <link rel="http://salmon-protocol.org/ns/salmon-mention" href="' . xmlify($a->get_baseurl() . '/salmon/' . $nick) . '" />' . "\n" ; 
	return $salmon;
}


function get_plink($item) {
	$a = get_app();	
	if (x($item,'plink') && ($item['private'] != 1)) {
		return array(
			'href' => $item['plink'],
			'title' => t('link to source'),
		);
	} 
	else {
		return false;
	}
}


function unamp($s) {
	return str_replace('&amp;', '&', $s);
}





function lang_selector() {
	global $a;
	
	$langs = glob('view/*/strings.php');
	
	$lang_options = array();
	$selected = "";
	
	if(is_array($langs) && count($langs)) {
		$langs[] = '';
		if(! in_array('view/en/strings.php',$langs))
			$langs[] = 'view/en/';
		asort($langs);
		foreach($langs as $l) {
			if($l == '') {
				$lang_options[""] = t('default');
				continue;
			}
			$ll = substr($l,5);
			$ll = substr($ll,0,strrpos($ll,'/'));
			$selected = (($ll === $a->language && (x($_SESSION, 'language'))) ? $ll : $selected);
			$lang_options[$ll]=$ll;
		}
	}

	$tpl = get_markup_template("lang_selector.tpl");	
	$o = replace_macros($tpl, array(
		'$title' => t('Select an alternate language'),
		'$langs' => array($lang_options, $selected),
		
	));
	return $o;
}



function return_bytes ($size_str) {
    switch (substr ($size_str, -1))
    {
        case 'M': case 'm': return (int)$size_str * 1048576;
        case 'K': case 'k': return (int)$size_str * 1024;
        case 'G': case 'g': return (int)$size_str * 1073741824;
        default: return $size_str;
    }
}

function generate_user_guid() {
	$found = true;
	do {
		$guid = random_string(16);
		$x = q("SELECT `uid` FROM `user` WHERE `guid` = '%s' LIMIT 1",
			dbesc($guid)
		);
		if(! count($x))
			$found = false;
	} while ($found == true );
	return $guid;
}



function base64url_encode($s, $strip_padding = true) {

	$s = strtr(base64_encode($s),'+/','-_');

	if($strip_padding)
		$s = str_replace('=','',$s);

	return $s;
}

function base64url_decode($s) {

	if(is_array($s)) {
		logger('base64url_decode: illegal input: ' . print_r(debug_backtrace(), true));
		return $s;
	}

/*
 *  // Placeholder for new rev of salmon which strips base64 padding.
 *  // PHP base64_decode handles the un-padded input without requiring this step
 *  // Uncomment if you find you need it.
 *
 *	$l = strlen($s);
 *	if(! strpos($s,'=')) {
 *		$m = $l % 4;
 *		if($m == 2)
 *			$s .= '==';
 *		if($m == 3)
 *			$s .= '=';
 *	}
 *
 */

	return base64_decode(strtr($s,'-_','+/'));
}


function cleardiv() {
	return '<div class="clear"></div>';
}


function bb_translate_video($s) {

	$matches = null;
	$r = preg_match_all("/\[video\](.*?)\[\/video\]/ism",$s,$matches,PREG_SET_ORDER);
	if($r) {
		foreach($matches as $mtch) {
			if((stristr($mtch[1],'youtube')) || (stristr($mtch[1],'youtu.be')))
				$s = str_replace($mtch[0],'[youtube]' . $mtch[1] . '[/youtube]',$s);
			elseif(stristr($mtch[1],'vimeo'))
				$s = str_replace($mtch[0],'[vimeo]' . $mtch[1] . '[/vimeo]',$s);
		}
	}
	return $s;	
}

function html2bb_video($s) {

	$s = preg_replace('#<object[^>]+>(.*?)https?://www.youtube.com/((?:v|cp)/[A-Za-z0-9\-_=]+)(.*?)</object>#ism',
			'[youtube]$2[/youtube]', $s);

	$s = preg_replace('#<iframe[^>](.*?)https?://www.youtube.com/embed/([A-Za-z0-9\-_=]+)(.*?)</iframe>#ism',
			'[youtube]$2[/youtube]', $s);

	$s = preg_replace('#<iframe[^>](.*?)https?://player.vimeo.com/video/([0-9]+)(.*?)</iframe>#ism',
			'[vimeo]$2[/vimeo]', $s);

	return $s;
}

/**
 * apply xmlify() to all values of array $val, recursively
 */
function array_xmlify($val){
	if (is_bool($val)) return $val?"true":"false";
	if (is_array($val)) return array_map('array_xmlify', $val);
	return xmlify((string) $val);
}


function reltoabs($text, $base)
{
  if (empty($base))
	return $text;

  $base = rtrim($base,'/');

  $base2 = $base . "/";
 	
  // Replace links
  $pattern = "/<a([^>]*) href=\"(?!http|https|\/)([^\"]*)\"/";
  $replace = "<a\${1} href=\"" . $base2 . "\${2}\"";
  $text = preg_replace($pattern, $replace, $text);

  $pattern = "/<a([^>]*) href=\"(?!http|https)([^\"]*)\"/";
  $replace = "<a\${1} href=\"" . $base . "\${2}\"";
  $text = preg_replace($pattern, $replace, $text);

  // Replace images
  $pattern = "/<img([^>]*) src=\"(?!http|https|\/)([^\"]*)\"/";
  $replace = "<img\${1} src=\"" . $base2 . "\${2}\"";
  $text = preg_replace($pattern, $replace, $text); 

  $pattern = "/<img([^>]*) src=\"(?!http|https)([^\"]*)\"/";
  $replace = "<img\${1} src=\"" . $base . "\${2}\"";
  $text = preg_replace($pattern, $replace, $text); 


  // Done
  return $text;
}

function item_post_type($item) {
	if(intval($item['event-id']))
		return t('event');
	if(strlen($item['resource_id']))
		return t('photo');
	if(strlen($item['verb']) && $item['verb'] !== ACTIVITY_POST)
		return t('activity');
	if($item['id'] != $item['parent'])
		return t('comment');
	return t('post');
}

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

// ex. given music,video return <music><video> or [music][video]
function file_tag_list_to_file($list,$type = 'file') {
		$tag_list = '';
		if(strlen($list)) {
				$list_array = explode(",",$list);
				if($type == 'file') {
					$lbracket = '[';
					$rbracket = ']';
			}
				else {
					$lbracket = '<';
					$rbracket = '>';
			}

				foreach($list_array as $item) {
		  if(strlen($item)) {
						$tag_list .= $lbracket . file_tag_encode(trim($item))  . $rbracket;
			}
				}
	}
		return $tag_list;
}

// ex. given <music><video>[friends], return music,video or friends
function file_tag_file_to_list($file,$type = 'file') {
		$matches = false;
		$list = '';
		if($type == 'file') {
				$cnt = preg_match_all('/\[(.*?)\]/',$file,$matches,PREG_SET_ORDER);
	}
		else {
				$cnt = preg_match_all('/<(.*?)>/',$file,$matches,PREG_SET_ORDER);
	}
	if($cnt) {
		foreach($matches as $mtch) {
			if(strlen($list))
				$list .= ',';
			$list .= file_tag_decode($mtch[1]);
		}
	}

		return $list;
}

function file_tag_update_pconfig($uid,$file_old,$file_new,$type = 'file') {
		// $file_old - categories previously associated with an item
		// $file_new - new list of categories for an item

	if(! intval($uid))
		return false;

		if($file_old == $file_new)
			return true;

	$saved = get_pconfig($uid,'system','filetags');
		if(strlen($saved)) {
				if($type == 'file') {
					$lbracket = '[';
					$rbracket = ']';
			}
				else {
					$lbracket = '<';
					$rbracket = '>';
			}

				$filetags_updated = $saved;

		// check for new tags to be added as filetags in pconfig
				$new_tags = array();
				$check_new_tags = explode(",",file_tag_file_to_list($file_new,$type));

			foreach($check_new_tags as $tag) {
				if(! stristr($saved,$lbracket . file_tag_encode($tag) . $rbracket))
					$new_tags[] = $tag;
			}

		$filetags_updated .= file_tag_list_to_file(implode(",",$new_tags),$type);

		// check for deleted tags to be removed from filetags in pconfig
				$deleted_tags = array();
				$check_deleted_tags = explode(",",file_tag_file_to_list($file_old,$type));

			foreach($check_deleted_tags as $tag) {
				if(! stristr($file_new,$lbracket . file_tag_encode($tag) . $rbracket))
						$deleted_tags[] = $tag;
			}

				foreach($deleted_tags as $key => $tag) {
				$r = q("select file from item where uid = %d " . file_tag_file_query('item',$tag,$type),
						intval($uid)
					);

					if(count($r)) {
					unset($deleted_tags[$key]);
					}
			else {
					$filetags_updated = str_replace($lbracket . file_tag_encode($tag) . $rbracket,'',$filetags_updated);
			}
		}

				if($saved != $filetags_updated) {
				set_pconfig($uid,'system','filetags', $filetags_updated);
				}
		return true;
	}
		else
				if(strlen($file_new)) {
				set_pconfig($uid,'system','filetags', $file_new);
				}
		return true;
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
	if(count($r))
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



function file_tag_save_file($uid,$item,$file) {
	$result = false;
	if(! intval($uid))
		return false;

	$r = q("select file from item where id = %d and uid = %d limit 1",
		intval($item),
		intval($uid)
	);
	if(count($r)) {
		if(! stristr($r[0]['file'],'[' . file_tag_encode($file) . ']'))
			q("update item set file = '%s' where id = %d and uid = %d limit 1",
				dbesc($r[0]['file'] . '[' . file_tag_encode($file) . ']'),
				intval($item),
				intval($uid)
			);
		$saved = get_pconfig($uid,'system','filetags');
		if((! strlen($saved)) || (! stristr($saved,'[' . file_tag_encode($file) . ']')))
			set_pconfig($uid,'system','filetags',$saved . '[' . file_tag_encode($file) . ']');
		info( t('Item filed') );
	}
	return true;
}

function file_tag_unsave_file($uid,$item,$file,$cat = false) {
	$result = false;
	if(! intval($uid))
		return false;

	if($cat == true)
		$pattern = '<' . file_tag_encode($file) . '>' ;
	else
		$pattern = '[' . file_tag_encode($file) . ']' ;


	$r = q("select file from item where id = %d and uid = %d limit 1",
		intval($item),
		intval($uid)
	);
	if(! count($r))
		return false;

	q("update item set file = '%s' where id = %d and uid = %d limit 1",
		dbesc(str_replace($pattern,'',$r[0]['file'])),
		intval($item),
		intval($uid)
	);

	$r = q("select file from item where uid = %d and deleted = 0 " . file_tag_file_query('item',$file,(($cat) ? 'category' : 'file')),
		intval($uid)
	);

	if(! count($r)) {
		$saved = get_pconfig($uid,'system','filetags');
		set_pconfig($uid,'system','filetags',str_replace($pattern,'',$saved));

	}
	return true;
}

function normalise_openid($s) {
	return trim(str_replace(array('http://','https://'),array('',''),$s),'/');
}


function undo_post_tagging($s) {
	$matches = null;
	$cnt = preg_match_all('/([@#])\[zrl=(.*?)\](.*?)\[\/zrl\]/ism',$s,$matches,PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$s = str_replace($mtch[0], $mtch[1] . $mtch[3],$s);
		}
	}
	return $s;
}

function fix_mce_lf($s) {
	$s = str_replace("\r\n","\n",$s);
//	$s = str_replace("\n\n","\n",$s);
	return $s;
}


function protect_sprintf($s) {
	return(str_replace('%','%%',$s));
}


function is_a_date_arg($s) {
	$i = intval($s);
	if($i > 1900) {
		$y = date('Y');
		if($i <= $y+1 && strpos($s,'-') == 4) {
			$m = intval(substr($s,5));
			if($m > 0 && $m <= 12)
				return true;
		}
	}
	return false;
}

function legal_webbie($s) {
	if(! strlen($s))
		return '';

	$x = $s;
	do {
		$s = $x;
		$x = preg_replace('/^([^a-z])(.*?)/',"$2",$s);
	} while($x != $s);

	return preg_replace('/([^a-z0-9\-\_])/','',$x);
}


function check_webbie($arr) {

	$str = '';
	$taken = array();
	if(count($arr)) {
		foreach($arr as $x) {
			$y = legal_webbie($x);
			if(strlen($y)) {
				if($str)
					$str .= ',';
				$str .= "'" . dbesc($y) . "'";
			}
		}
		if(strlen($str)) {
			$r = q("select channel_address from channel where channel_address in ( $str ) ");
			if(count($r)) {
				foreach($r as $rr) {
					$taken[] = $rr['channel_address'];
				}
			}
			foreach($arr as $x) {
				if(! in_array($x,$taken)) {
					return $x;
				}
			}
		}
	}
	return '';
}
	

function ids_to_querystr($arr,$idx = 'id') {
	$t = array();
	foreach($arr as $x)
		$t[] = $x[$idx];
	return(implode(',', $t));
}

// Fetches xchan and hubloc data for an array of items with only an 
// author_xchan and owner_xchan. If $abook is true also include the abook info. 
// This is needed in the API to save extra per item lookups there.

function xchan_query(&$items,$abook = false) {
	$arr = array();
	if($items && count($items)) {
		foreach($items as $item) {
			if($item['owner_xchan'] && (! in_array($item['owner_xchan'],$arr)))
				$arr[] = "'" . dbesc($item['owner_xchan']) . "'";
			if($item['author_xchan'] && (! in_array($item['author_xchan'],$arr)))
				$arr[] = "'" . dbesc($item['author_xchan']) . "'";
		}
	}
	if(count($arr)) {
		if($abook) {
			$chans = q("select * from xchan left join hubloc on hubloc_hash = xchan_hash left join abook on abook_xchan = xchan_hash
				where xchan_hash in (" . implode(',', $arr) . ") and ( hubloc_flags & " . intval(HUBLOC_FLAGS_PRIMARY) . " )");
		}
		else {
			$chans = q("select xchan.*,hubloc.* from xchan left join hubloc on hubloc_hash = xchan_hash
				where xchan_hash in (" . implode(',', $arr) . ") and ( hubloc_flags & " . intval(HUBLOC_FLAGS_PRIMARY) . " )");
		}
	}
	if($items && count($items) && $chans && count($chans)) {
		for($x = 0; $x < count($items); $x ++) {
			$items[$x]['owner'] = find_xchan_in_array($items[$x]['owner_xchan'],$chans);
			$items[$x]['author'] = find_xchan_in_array($items[$x]['author_xchan'],$chans);
		}
	}

}

function xchan_mail_query(&$item) {
	$arr = array();
	$chans = null;
	if($item) {
		if($item['from_xchan'] && (! in_array($item['from_xchan'],$arr)))
			$arr[] = "'" . dbesc($item['from_xchan']) . "'";
		if($item['to_xchan'] && (! in_array($item['to_xchan'],$arr)))
			$arr[] = "'" . dbesc($item['to_xchan']) . "'";
	}

	if(count($arr)) {
		$chans = q("select xchan.*,hubloc.* from xchan left join hubloc on hubloc_hash = xchan_hash
			where xchan_hash in (" . implode(',', $arr) . ") and ( hubloc_flags & " . intval(HUBLOC_FLAGS_PRIMARY) . " )");
	}
	if($chans) {
		$item['from'] = find_xchan_in_array($item['from_xchan'],$chans);
		$item['to']  = find_xchan_in_array($item['to_xchan'],$chans);
	}
}


function find_xchan_in_array($xchan,$arr) {
	if(count($arr)) {
		foreach($arr as $x) {
			if($x['xchan_hash'] === $xchan) {
				return $x;
			}
		}
	}
	return array();
}

function get_rel_link($j,$rel) {
	if(count($j))
		foreach($j as $l)
			if($l['rel'] === $rel)
				return $l['href'];
	return '';
}


// Lots of code to write here

function magic_link($s) {
	return $s;
}
	
// if $escape is true, dbesc() each element before adding quotes

function stringify_array_elms(&$arr,$escape = false) {
	for($x = 0; $x < count($arr); $x ++)
		$arr[$x] = "'" . (($escape) ? dbesc($arr[$x]) : $arr[$x]) . "'";
}

/**
 * Indents a flat JSON string to make it more human-readable.
 *
 * @param string $json The original JSON string to process.
 *
 * @return string Indented version of the original JSON string.
 */
function jindent($json) {

	$result	  = '';
	$pos		 = 0;
	$strLen	  = strlen($json);
	$indentStr   = '  ';
	$newLine	 = "\n";
	$prevChar	= '';
	$outOfQuotes = true;

	for ($i=0; $i<=$strLen; $i++) {

		// Grab the next character in the string.
		$char = substr($json, $i, 1);

		// Are we inside a quoted string?
		if ($char == '"' && $prevChar != '\\') {
			$outOfQuotes = !$outOfQuotes;
		
		// If this character is the end of an element, 
		// output a new line and indent the next line.
		} else if(($char == '}' || $char == ']') && $outOfQuotes) {
			$result .= $newLine;
			$pos --;
			for ($j=0; $j<$pos; $j++) {
				$result .= $indentStr;
			}
		}
		
		// Add the character to the result string.
		$result .= $char;

		// If the last character was the beginning of an element, 
		// output a new line and indent the next line.
		if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
			$result .= $newLine;
			if ($char == '{' || $char == '[') {
				$pos ++;
			}
			
			for ($j = 0; $j < $pos; $j++) {
				$result .= $indentStr;
			}
		}
		
		$prevChar = $char;
	}

	return $result;
}


// Tag cloud functions - need to be adpated to this database format


function tagadelic($uid, $count = 0, $type = TERM_HASHTAG) {

	// Fetch tags
	$r = q("select term, count(term) as total from term
		where uid = %d and type = %d 
		and otype = %d
		group by term order by total desc %s",
		intval($uid),
		intval($type),
		intval(TERM_OBJ_POST),
		((intval($count)) ? "limit $count" : '')
	);

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


function tagblock($link,$uid,$count = 0,$type = TERM_HASHTAG) {
  $tab = 0;
  $r = tagadelic($uid,$count,$type);

  if($r) {
	echo '<div class="tags" align="center">';
	foreach($r as $rr) { 
	  echo '<a href="'.$link .'/' . '?f=&tag=' . urlencode($rr[0]).'" class="tag'.$rr[2].'">'.$rr[0].'</a> ';
	}
	echo '</div>';
  }
}
