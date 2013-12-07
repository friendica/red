<?php /** @file */


require_once("include/template_processor.php");
require_once("include/friendica_smarty.php");

/**
 * This is our template processor
 * 
 * @param string|FriendicaSmarty $s the string requiring macro substitution, 
 *									or an instance of FriendicaSmarty
 * @param array $r key value pairs (search => replace)
 * @return string substituted string
 */
function replace_macros($s,$r) {
	$a = get_app();

	$arr = array('template' => $s, 'params' => $r);
	call_hooks('replace_macros', $arr);
	
	$t = $a->template_engine();
	$output = $t->replace_macros($arr['template'],$arr['params']);
	
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
 * @param string $string Input string
 * @return string Filtered string
 */


function notags($string) {

	return(str_replace(array("<",">"), array('[',']'), $string));

//  High-bit filter no longer used
//	return(str_replace(array("<",">","\xBA","\xBC","\xBE"), array('[',']','','',''), $string));
}

// use this on "body" or "content" input where angle chars shouldn't be removed,
// and allow them to be safely displayed.



/**
 * use this on "body" or "content" input where angle chars shouldn't be removed,
 * and allow them to be safely displayed.
 * @param string $string
 * @return string
 */
function escape_tags($string) {

	return(htmlspecialchars($string, ENT_COMPAT, 'UTF-8', false));

}


function z_input_filter($channel_id,$s,$type = 'text/bbcode') {

	if($type === 'text/bbcode')
		return escape_tags($s);
	if($type === 'text/markdown')
		return escape_tags($s);
	if($type == 'text/plain')
		return escape_tags($s);
	$r = q("select account_id, account_roles from account left join channel on channel_account_id = account_id where channel_id = %d limit 1",
		intval($channel_id)
	);
	if($r && ($r[0]['account_roles'] & ACCOUNT_ROLE_ALLOWCODE)) {
		if(local_user() && (get_account_id() == $r[0]['account_id'])) {
			return $s;
		}
	}

	if($type === 'text/html')
		return purify_html($s);

	return escape_tags($s);
	
}





function purify_html($s) {
	require_once('library/HTMLPurifier.auto.php');
	require_once('include/html2bbcode.php');

// FIXME this function has html output, not bbcode - so safely purify these
//	$s = html2bb_video($s);
//	$s = oembed_html2bbcode($s);

	$config = HTMLPurifier_Config::createDefault();
	$config->set('Cache.DefinitionImpl', null);

	$purifier = new HTMLPurifier($config);
	return $purifier->purify($s);
}





// generate a string that's random, but usually pronounceable. 
// used to generate initial passwords


/**
 * generate a string that's random, but usually pronounceable. 
 * used to generate initial passwords
 * @param int $len
 * @return string
 */
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


/**
 * escape text ($str) for XML transport
 * @param string $str
 * @return string Escaped text.
 */
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

	return replace_macros(get_markup_template('alt_pager.tpl'),array(
		'$has_less' => (($a->pager['page'] > 1) ? true : false),
		'$has_more' => (($i > 0 && $i >= $a->pager['itemspage']) ? true : false),
		'$less' => $less,
		'$more' => $more,
		'$url' => $url,
		'$prevpage' => $a->pager['page'] - 1,
		'$nextpage' => $a->pager['page'] + 1,
	));

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
	$o .= '<input type="text" class="icon-search" name="search" id="search-text" placeholder="&#xf002;" value="' . $s .'" onclick="this.submit();" />';
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
		'red#',
		'r#',
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
		'<a href="http://getzot.com"><strong>red<img class="smiley" src="' . $a->get_baseurl() . '/images/rm-16.png" alt="red#" />matrix</strong></a>',
		'<a href="http://getzot.com"><strong>red<img class="smiley" src="' . $a->get_baseurl() . '/images/rm-16.png" alt="r#" />matrix</strong></a>',
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


function unobscure(&$item) {
	if(array_key_exists('item_flags',$item) && ($item['item_flags'] & ITEM_OBSCURED)) {
		$key = get_config('system','prvkey');
		if($item['title'])
			$item['title'] = crypto_unencapsulate(json_decode_plus($item['title']),$key);
		if($item['body'])
			$item['body'] = crypto_unencapsulate(json_decode_plus($item['body']),$key);
	}

}

function theme_attachments(&$item) {

	$arr = json_decode_plus($item['attach']);
	if(is_array($arr) && count($arr)) {
		$attaches = array();
		foreach($arr as $r) {
			$icon = '';
			$icontype = substr($r['type'],0,strpos($r['type'],'/'));

			// FIXME This should probably be a giant "if" statement in the template so that we don't have icon names
			// embedded in php code

			switch($icontype) {
				case 'video':
					$icon = 'icon-facetime-video';
					break;
				case 'audio':
					$icon = 'icon-volume-up';
					break;
				case 'image':
					$icon = 'icon-picture';
					break;
				case 'text':
					$icon = 'icon-align-justify';
					break;
				default:
					$icon = 'icon-question';
					break;
			}

			$title = htmlentities($r['title'], ENT_COMPAT,'UTF-8');
			if(! $title)
				$title = t('unknown.???');
			$title .= ' ' . $r['length'] . ' ' . t('bytes');

			$url = z_root() . '/magic?f=&hash=' . $item['author_xchan'] . '&dest=' . $r['href'] . '/' . $r['revision'];
			$s .= '<a href="' . $url . '" title="' . $title . '" class="attachlink"  >' . $icon . '</a>';
			$attaches[] = array('title' => $title, 'url' => $url, 'icon' => $icon );

		}


	}

	$s = replace_macros(get_markup_template('item_attach.tpl'), array(
		'$attaches' => $attaches
	));

	return $s;

}


function format_categories(&$item,$writeable) {

	$s = '';
	$terms = get_terms_oftype($item['term'],TERM_CATEGORY);
	if($terms) {
		$categories = array();
		foreach($terms as $t) {
			$term = htmlspecialchars($t['term'],ENT_COMPAT,'UTF-8') ;
			if(! trim($term))
				continue;
			$removelink = (($writeable) ?  z_root() . '/filerm/' . $item['id'] . '?f=&cat=' . urlencode($t['term']) : '');
			$categories[] = array('term' => $term, 'writeable' => $writeable, 'removelink' => $removelink, 'url' => zid($t['url']));
		}
	}
	$s = replace_macros(get_markup_template('item_categories.tpl'),array(
		'$remove' => t('remove category'),
		'$categories' => $categories
	));
	return $s;
}


function format_filer(&$item) {

	$s = '';
	$terms = get_terms_oftype($item['term'],TERM_FILE);
	if($terms) {
		$categories = array();
		foreach($terms as $t) {
			$term = htmlspecialchars($t['term'],ENT_COMPAT,'UTF-8') ;
			if(! trim($term))
				continue;
			$removelink = z_root() . '/filerm/' . $item['id'] . '?f=&term=' . urlencode($t['term']);
			$categories[] = array('term' => $term, 'removelink' => $removelink);
		}
	}
	$s = replace_macros(get_markup_template('item_filer.tpl'),array(
		'$remove' => t('remove from file'),
		'$categories' => $categories
	));
	return $s;
}





function prepare_body(&$item,$attach = false) {

	$a = get_app();



	call_hooks('prepare_body_init', $item); 

	unobscure($item);

	$s = prepare_text($item['body'],$item['mimetype']);

	$prep_arr = array('item' => $item, 'html' => $s);
	call_hooks('prepare_body', $prep_arr);
	$s = $prep_arr['html'];

	if(! $attach) {
		return $s;
	}


	$s .= theme_attachments($item);


	$writeable = ((get_observer_hash() == $item['owner_xchan']) ? true : false); 

	$s .= format_categories($item,$writeable);

	if(local_user() == $item['uid'])
		$s .= format_filer($item);

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

		// No security checking is done here at display time - so we need to verify 
		// that the author is allowed to use PHP before storing. We also cannot allow 
		// importation of PHP text bodies from other sites. Therefore this content 
		// type is only valid for web pages (and profile details).

		// It may be possible to provide a PHP message body which is evaluated on the 
		// sender's site before sending it elsewhere. In that case we will have a 
		// different content-type here.  

		case 'application/x-php':
			ob_start();
			eval($text);
			$s = ob_get_contents();
			ob_end_clean();
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

//logger('prepare_text: ' . $s);

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
		$replace = '<a' . $match[1] . ' href="' . zid($match[2]) . '"';
	}
	else {
		$replace = '<a' . $match[1] . 'class="zrl"' . $match[2] . ' href="' . zid($match[3]) . '"';
	}

	$x = str_replace($match[0],$replace,$match[0]);
	return $x;
}

function zidify_img_callback($match) {
  if (feature_enabled(local_user(),'sendzid')) {
	$replace = '<img' . $match[1] . ' src="' . zid($match[2]) . '"';
	}
	else {
	  $replace = '<img' . $match[1] . ' src="' . zid($match[2]) . '"';
	}
    
	$x = str_replace($match[0],$replace,$match[0]);
	return $x;
}


function zidify_links($s) {
	if(feature_enabled(local_user(),'sendzid')) {
		$s = preg_replace_callback('/\<a(.*?)href\=\"(.*?)\"/ism','zidify_callback',$s);
		$s = preg_replace_callback('/\<img(.*?)src\=\"(.*?)\"/ism','zidify_img_callback',$s);
	}
    else {
		$s = preg_replace_callback('/\<a(.*?)class\=\"zrl\"(.*?)href\=\"(.*?)\"/ism','zidify_callback',$s);
		$s = preg_replace_callback('/\<img class\=\"zrl\"(.*?)src\=\"(.*?)\"/ism','zidify_img_callback',$s);
// FIXME - remove the following line and redo the regex for the prev line once all Red images are converted to zmg
		$s = preg_replace_callback('/\<img(.*?)src\=\"(.*?)\"/ism','zidify_img_callback',$s);
	}

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


function get_plink($item,$mode) {
	$a = get_app();
	if($mode == 'display')
		$key = 'plink';
	else
		$key = 'llink';
	
	if(x($item,$key)) {
		return array(
			'href' => zid($item[$key]),
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

function layout_select($channel_id, $current = '') {
	$r = q("select mid,sid from item left join item_id on iid = item.id where service = 'PDL' and item.uid = item_id.uid and item_id.uid = %d and (item_restrict & %d)",
		intval($channel_id),
		intval(ITEM_PDL)
	);
	if($r) {
		$o = t('Select a page layout: ');
		$o .= '<select name="layout_mid" id="select-layout_mid" >';
		$empty_selected = (($current === '') ? ' selected="selected" ' : '');
		$o .= '<option value="" ' . $empty_selected . '>' . t('default') . '</option>';
		foreach($r as $rr) {
			$selected = (($rr['mid'] == $current) ? ' selected="selected" ' : '');
			$o .= '<option value="' . $rr['mid'] . '"' . $selected . '>' . $rr['sid'] . '</option>';
		}
		$o .= '</select>';
	}

	return $o;
}





function mimetype_select($channel_id, $current = 'text/bbcode') {

	$x = array(
		'text/bbcode',
		'text/html',
		'text/markdown',
		'text/plain'
	);

	$r = q("select account_id, account_roles from account left join channel on account_id = channel_account_id where
		channel_id = %d limit 1",
		intval($channel_id)
	);

	if($r) {
		if($r[0]['account_roles'] & ACCOUNT_ROLE_ALLOWCODE) {
			if(local_user() && get_account_id() == $r[0]['account_id'])
				$x[] = 'application/x-php';
		}
	}

	$o = t('Page content type: ');
	$o .= '<select name="mimetype" id="mimetype-select">';
	foreach($x as $y) {
		$select = (($y == $current)	? ' selected="selected" ' : '');
		$o .= '<option name="' . $y . '"' . $select . '>' . $y . '</option>';
	}
	$o .= '</select>';

	return $o;

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


    switch($item['resource_type']) {
        case 'photo':
            $post_type = t('photo');
            break;
        case 'event':
            $post_type = t('event');
            break;
        default:
            $post_type = t('status');
            if($item['mid'] != $item['parent_mid'])
                $post_type = t('comment');
            break;
    }

	if(strlen($item['verb']) && (! activity_match($item['verb'],ACTIVITY_POST)))
		$post_type = t('activity');

	return $post_type;
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

function xchan_query(&$items,$abook = true) {
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
			$chans = q("select * from xchan left join hubloc on hubloc_hash = xchan_hash left join abook on abook_xchan = xchan_hash and abook_channel = %d
				where xchan_hash in (" . implode(',', $arr) . ") and ( hubloc_flags & " . intval(HUBLOC_FLAGS_PRIMARY) . " )",
				intval($item['uid'])
			);
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


function json_decode_plus($s) {

	$x = json_decode($s,true);
	if(! $x)
		$x = json_decode(str_replace(array('\\"','\\\\'),array('"','\\'),$s),true);
	return $x;	

}


function design_tools() {
$channel  = get_app()->get_channel();
$who = $channel['channel_address'];

return replace_macros(get_markup_template('design_tools.tpl'), array(
                        '$title' => t('Design'),
			'$who' => $who,
                      	'$blocks' => t('Blocks'),
			'$menus' => t('Menus'),
			'$layout' => t('Layouts'),
			'$pages' => t('Pages')
                        ));

}

/* case insensitive in_array() */

function in_arrayi($needle, $haystack) {
	return in_array(strtolower($needle), array_map('strtolower', $haystack));
}

