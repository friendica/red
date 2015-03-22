<?php
/**
 * @file include/bb2diaspora.php
 * @brief Some functions for BB conversions for Diaspora protocol.
 */

require_once("include/oembed.php");
require_once("include/event.php");
require_once("library/markdown.php");
require_once("include/html2bbcode.php");
require_once("include/bbcode.php");
require_once("library/markdownify/markdownify.php");


function get_bb_tag_pos($s, $name, $occurance = 1) {

	if($occurance < 1)
		$occurance = 1;

	$start_open = -1;
	for($i = 1; $i <= $occurance; $i++) {
		if( $start_open !== false)
			$start_open = strpos($s, '[' . $name, $start_open + 1); // allow [name= type tags
	}

	if( $start_open === false)
		return false;

	$start_equal = strpos($s, '=', $start_open);
	$start_close = strpos($s, ']', $start_open);

	if( $start_close === false)
		return false;

	$start_close++;

	$end_open = strpos($s, '[/' . $name . ']', $start_close);

	if( $end_open === false)
		return false;

	$res = array( 'start' => array('open' => $start_open, 'close' => $start_close),
	              'end' => array('open' => $end_open, 'close' => $end_open + strlen('[/' . $name . ']')) );
	if( $start_equal !== false)
		$res['start']['equal'] = $start_equal + 1;

	return $res;
}

function bb_tag_preg_replace($pattern, $replace, $name, $s) {

	$string = $s;

	$occurance = 1;
	$pos = get_bb_tag_pos($string, $name, $occurance);
	while($pos !== false && $occurance < 1000) {

		$start = substr($string, 0, $pos['start']['open']);
		$subject = substr($string, $pos['start']['open'], $pos['end']['close'] - $pos['start']['open']);
		$end = substr($string, $pos['end']['close']);
		if($end === false)
			$end = '';

		$subject = preg_replace($pattern, $replace, $subject);
		$string = $start . $subject . $end;

		$occurance++;
		$pos = get_bb_tag_pos($string, $name, $occurance);
	}

	return $string;
}

function share_shield($m) {
	return str_replace($m[1],'!=+=+=!' . base64url_encode($m[1]) . '=+!=+!=',$m[0]);
} 

function share_unshield($m) {
	$x = str_replace(array('!=+=+=!','=+!=+!='),array('',''),$m[1]);
	return str_replace($m[1], base64url_decode($x), $m[0]);
}


function diaspora_mention_callback($matches) {

	$webbie = $matches[2] . '@' . $matches[3];
	$link = '';
	if($webbie) {
		$r = q("select * from hubloc left join xchan on hubloc_hash = xchan_hash where hubloc_addr = '%s' limit 1",
			dbesc($webbie)
		);
		if(! $r) {
			$x = discover_by_webbie($webbie);
			if($x) {
				$r = q("select * from hubloc left join xchan on hubloc_hash = xchan_hash where hubloc_addr = '%s' limit 1",
					dbesc($webbie)
				);
			}
		}
		if($r)
			$link = $r[0]['xchan_url'];
	}
	if(! $link)
		$link = 'https://' . $matches[3] . '/u/' . $matches[2];

	if($r && $r[0]['hubloc_network'] === 'zot')
		return '@[zrl=' . $link . ']' . trim($matches[1]) . ((substr($matches[0],-1,1) === '+') ? '+' : '') . '[/zrl]' ;
	else
		return '@[url=' . $link . ']' . trim($matches[1]) . ((substr($matches[0],-1,1) === '+') ? '+' : '') . '[/url]' ;

}


/**
 * @brief
 *
 * We don't want to support a bbcode specific markdown interpreter
 * and the markdown library we have is pretty good, but provides HTML output.
 * So we'll use that to convert to HTML, then convert the HTML back to bbcode,
 * and then clean up a few Diaspora specific constructs.
 *
 * @param string $s
 * @param boolean $use_zrl default false
 * @return string
 */
function diaspora2bb($s, $use_zrl = false) {

	$s = str_replace("&#xD;","\r",$s);
	$s = str_replace("&#xD;\n&gt;","",$s);

	$s = html_entity_decode($s,ENT_COMPAT,'UTF-8');

	// first try plustags

	$s = preg_replace_callback('/\@\{(.+?)\; (.+?)\@(.+?)\}\+/','diaspora_mention_callback',$s);

	$s = preg_replace_callback('/\@\{(.+?)\; (.+?)\@(.+?)\}/','diaspora_mention_callback',$s);

	// Escaping the hash tags - doesn't always seem to work
	// $s = preg_replace('/\#([^\s\#])/','\\#$1',$s);
	// This seems to work
	$s = preg_replace('/\#([^\s\#])/','&#35;$1',$s);

	$s = Markdown($s);

	$s = str_replace("\r","",$s);

	$s = str_replace('&#35;','#',$s);

	$s = html2bbcode($s);

	// protect the recycle symbol from turning into a tag, but without unescaping angles and naked ampersands
	$s = str_replace('&#x2672;',html_entity_decode('&#x2672;',ENT_QUOTES,'UTF-8'),$s);

	// Convert everything that looks like a link to a link
	if($use_zrl) {
		$s = str_replace(array('[img','/img]'),array('[zmg','/zmg]'),$s);
		$s = preg_replace("/([^\]\=]|^)(https?\:\/\/)([a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\%\$\!\+\,]+)/ism", '$1[zrl=$2$3]$2$3[/zrl]',$s);
	}
	else {
		$s = preg_replace("/([^\]\=]|^)(https?\:\/\/)([a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\%\$\!\+\,]+)/ism", '$1[url=$2$3]$2$3[/url]',$s);
	}

	//$s = preg_replace("/([^\]\=]|^)(https?\:\/\/)(vimeo|youtu|www\.youtube|soundcloud)([a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\%\$\!\+\,]+)/ism", '$1[url=$2$3$4]$2$3$4[/url]',$s);
	$s = bb_tag_preg_replace("/\[url\=?(.*?)\]https?:\/\/www.youtube.com\/watch\?v\=(.*?)\[\/url\]/ism",'[youtube]$2[/youtube]','url',$s);
	$s = bb_tag_preg_replace("/\[url\=https?:\/\/www.youtube.com\/watch\?v\=(.*?)\].*?\[\/url\]/ism",'[youtube]$1[/youtube]','url',$s);
	$s = bb_tag_preg_replace("/\[url\=?(.*?)\]https?:\/	\/vimeo.com\/([0-9]+)(.*?)\[\/url\]/ism",'[vimeo]$2[/vimeo]','url',$s);
	$s = bb_tag_preg_replace("/\[url\=https?:\/\/vimeo.com\/([0-9]+)\](.*?)\[\/url\]/ism",'[vimeo]$1[/vimeo]','url',$s);
	// remove duplicate adjacent code tags
	$s = preg_replace("/(\[code\])+(.*?)(\[\/code\])+/ism","[code]$2[/code]", $s);

	// Don't show link to full picture (until it is fixed)
	$s = scale_external_images($s, false);

	return $s;
}


function stripdcode_br_cb($s) {
	return '[code]' . str_replace('<br />', "\n\t", $s[1]) . '[/code]';
}


//////////////////////
// The following "diaspora_ul" and "diaspora_ol" are only appropriate for the
// pre-Markdownify conversion. If Markdownify isn't used, use the non-Markdownify
// versions below
//////////////////////
/*
function diaspora_ul($s) {
	// Replace "[*]" followed by any number (including zero) of
	// spaces by "* " to match Diaspora's list format
	if( strpos($s[0], "[list]") === 0 )
		return '<ul class="listbullet" style="list-style-type: circle;">' . preg_replace("/\[\*\]( *)/", "* ", $s[1]) . '</ul>';
	elseif( strpos($s[0], "[ul]") === 0 )
		return '<ul class="listbullet" style="list-style-type: circle;">' . preg_replace("/\[\*\]( *)/", "* ", $s[1]) . '</ul>';
	else
		return $s[0];
}


function diaspora_ol($s) {
	// A hack: Diaspora will create a properly-numbered ordered list even
	// if you use '1.' for each element of the list, like:
	//		1. First element
	//		1. Second element
	//		1. Third element
	if( strpos($s[0], "[list=1]") === 0 )
		return '<ul class="listdecimal" style="list-style-type: decimal;">' . preg_replace("/\[\*\]( *)/", "1. ", $s[1]) . '</ul>';
	elseif( strpos($s[0], "[list=i]") === 0 )
		return '<ul class="listlowerroman" style="list-style-type: lower-roman;">' . preg_replace("/\[\*\]( *)/", "1. ", $s[1]) . '</ul>';
	elseif( strpos($s[0], "[list=I]") === 0 )
		return '<ul class="listupperroman" style="list-style-type: upper-roman;">' . preg_replace("/\[\*\]( *)/", "1. ", $s[1]) . '</ul>';
	elseif( strpos($s[0], "[list=a]") === 0 )
		return '<ul class="listloweralpha" style="list-style-type: lower-alpha;">' . preg_replace("/\[\*\]( *)/", "1. ", $s[1]) . '</ul>';
	elseif( strpos($s[0], "[list=A]") === 0 )
		return '<ul class="listupperalpha" style="list-style-type: upper-alpha;">' . preg_replace("/\[\*\]( *)/", "1. ", $s[1]) . '</ul>';
	elseif( strpos($s[0], "[ol]") === 0 )
		return '<ul class="listdecimal" style="list-style-type: decimal;">' . preg_replace("/\[\*\]( *)/", "1. ", $s[1]) . '</ul>';
	else
		return $s[0];
}
*/

//////////////////////
// Non-Markdownify versions of "diaspora_ol" and "diaspora_ul"
//////////////////////
/**
 * @brief
 *
 * Replace "[\\*]" followed by any number (including zero) of
 * spaces by "* " to match Diaspora's list format.
 *
 * @param string $s
 * @return string
 */
function diaspora_ul($s) {
	return preg_replace("/\[\\\\\*\]( *)/", "* ", $s[1]);
}

/**
 * @brief
 *
 * A hack: Diaspora will create a properly-numbered ordered list even
 * if you use '1.' for each element of the list, like:
 * \code
 * 1. First element
 * 1. Second element
 * 1. Third element
 * \endcode
 * @param string $s
 * @return string
 */
function diaspora_ol($s) {
	return preg_replace("/\[\\\\\*\]( *)/", "1. ", $s[1]);
}

function bb2dmention_callback($match) {

	$r = q("select xchan_addr from xchan where xchan_url = '%s'",
		dbesc($match[2])
	); 

	if($r)
		return '@{' . $match[3] . ' ; ' . $r[0]['xchan_addr'] . '}';

	return '@' . $match[3];
}


function bb2diaspora_itemwallwall(&$item) {

	$author_exists = true;
	if(! array_key_exists('author',$item)) {
		$author_exists = false;
		logger('bb2diaspora_itemwallwall: no author');
		$r = q("select * from xchan where xchan_hash = '%s' limit 1",
			dbesc($item['author_xchan'])
		);
		if($r)
			$item['author'] = $r[0];
	}

	if(($item['mid'] == $item['parent_mid']) && ($item['author_xchan'] != $item['owner_xchan']) && (is_array($item['author']))) {
		logger('bb2diaspora_itemwallwall: author: ' . print_r($item['author'],true), LOGGER_DATA);
	}

	if(($item['mid'] == $item['parent_mid']) && ($item['author_xchan'] != $item['owner_xchan']) && (is_array($item['author'])) && $item['author']['xchan_url'] && $item['author']['xchan_name'] && $item['author']['xchan_photo_m']) {
		logger('bb2diaspora_itemwallwall: wall to wall post',LOGGER_DEBUG);
		// post will come across with the owner's identity. Throw a preamble onto the post to indicate the true author.
		$item['body'] = "\n\n" 
			. '[img]' . $item['author']['xchan_photo_m'] . '[/img]' 
			. '[url=' . $item['author']['xchan_url'] . ']' . $item['author']['xchan_name'] . '[/url]' . "\n\n" 
			. $item['body'];
	}

	// $item['author'] might cause a surprise further down the line if it wasn't expected to be here.

	if(! $author_exists)
		unset($item['author']);
}


function bb2diaspora_itembody($item, $force_update = false) {

	$matches = array();

	if(($item['diaspora_meta']) && (! $force_update)) {
		$diaspora_meta = json_decode($item['diaspora_meta'],true);
		if($diaspora_meta) {
			if(array_key_exists('iv',$diaspora_meta)) {
				$key = get_config('system','prvkey');
				$meta = json_decode(crypto_unencapsulate($diaspora_meta,$key),true);
			}
			else {
				$meta = $diaspora_meta;
			}
			if($meta) {
				logger('bb2diaspora_itembody: cached ');
				$newitem = $item;
				$newitem['body'] = $meta['body'];
				return $newitem['body'];
			}
		}
	}

	$newitem = $item;

	if(array_key_exists('item_flags',$item) && ($item['item_flags'] & ITEM_OBSCURED)) {
		$key = get_config('system','prvkey');
		$b = json_decode($item['body'],true);
		// if called from diaspora_process_outbound, this decoding has already been done.
		// Everything else that calls us will not yet be decoded.
		if($b && is_array($b) && array_key_exists('iv',$b)) {
			$newitem['title'] = (($item['title']) ? crypto_unencapsulate(json_decode($item['title'],true),$key) : '');
			$newitem['body']  = (($item['body'])  ? crypto_unencapsulate(json_decode($item['body'],true),$key) : '');
		}
	}

	bb2diaspora_itemwallwall($newitem);

	$title = $newitem['title'];
	$body  = preg_replace('/\#\^http/i', 'http', $newitem['body']);

	// protect tags and mentions from hijacking

	if(intval(get_pconfig($item['uid'],'system','prevent_tag_hijacking'))) {
		$new_tag	 = html_entity_decode('&#x22d5;',ENT_COMPAT,'UTF-8');
		$new_mention = html_entity_decode('&#xff20;',ENT_COMPAT,'UTF-8');

		// #-tags
		$body = preg_replace('/\#\[url/i', $new_tag . '[url', $body);
		$body = preg_replace('/\#\[zrl/i', $new_tag . '[zrl', $body);
		// @-mentions
		$body = preg_replace('/\@\!?\[url/i', $new_mention . '[url', $body);
		$body = preg_replace('/\@\!?\[zrl/i', $new_mention . '[zrl', $body);
	}

	// remove multiple newlines
	do {
		$oldbody = $body;
		$body = str_replace("\n\n\n", "\n\n", $body);
	} while ($oldbody != $body);

	$body = bb2diaspora($body);

	if(strlen($title))
		$body = "## " . $title . "\n\n" . $body;

	if($item['attach']) {
		$cnt = preg_match_all('/href=\"(.*?)\"(.*?)title=\"(.*?)\"/ism', $item['attach'], $matches, PREG_SET_ORDER);
		if($cnt) {
			$body .= "\n" . t('Attachments:') . "\n";
			foreach($matches as $mtch) {
				$body .= '[' . $mtch[3] . '](' . $mtch[1] . ')' . "\n";
			}
		}
	}

//	logger('bb2diaspora_itembody : ' . $body, LOGGER_DATA);

	return html_entity_decode($body);
}

function bb2diaspora($Text,$preserve_nl = false, $fordiaspora = true) {

	// Re-enabling the converter again.
	// The bbcode parser now handles youtube-links (and the other stuff) correctly.
	// Additionally the html code is now fixed so that lists are now working.

	/*
	 * Transform #tags, strip off the [url] and replace spaces with underscore
	 */
	$Text = preg_replace_callback('/#\[([zu])rl\=(\w+.*?)\](\w+.*?)\[\/[(zu)]rl\]/i', create_function('$match',
		'return \'#\'. str_replace(\' \', \'_\', $match[3]);'
	), $Text);

	$Text = preg_replace('/#\^\[([zu])rl\=(\w+.*?)\](\w+.*?)\[\/([zu])rl\]/i', '[$1rl=$2]$3[/$4rl]', $Text);

	$Text = preg_replace_callback('/\@\!?\[([zu])rl\=(\w+.*?)\](\w+.*?)\[\/([zu])rl\]/i', 'bb2dmention_callback', $Text);


	// Converting images with size parameters to simple images. Markdown doesn't know it.
	$Text = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '[img]$3[/img]', $Text);

	// the following was added on 10-January-2012 due to an inability of Diaspora's
	// new javascript markdown processor to handle links with images as the link "text"
	// It is not optimal and may be removed if this ability is restored in the future
	//if ($fordiaspora)
	//	$Text = preg_replace("/\[url\=([^\[\]]*)\]\s*\[img\](.*?)\[\/img\]\s*\[\/url\]/ism",
	//				"[url]$1[/url]\n[img]$2[/img]", $Text);

	// Convert it to HTML - don't try oembed
	$Text = bbcode($Text, $preserve_nl, false);

	// Now convert HTML to Markdown
	$md = new Markdownify(false, false, false);
	$Text = $md->parseString($Text);

	// If the text going into bbcode() has a plain URL in it, i.e.
	// with no [url] tags around it, it will come out of parseString()
	// looking like: <http://url.com>, which gets removed by strip_tags().
	// So take off the angle brackets of any such URL
	$Text = preg_replace("/<http(.*?)>/is", "http$1", $Text);

	// Remove all unconverted tags
	$Text = strip_tags($Text);

	// Remove any leading or trailing whitespace, as this will mess up
	// the Diaspora signature verification and cause the item to disappear

	$Text = trim($Text);

	call_hooks('bb2diaspora',$Text);

	return $Text;
}

function unescape_underscores_in_links($m) {
	$y = str_replace('\\_','_', $m[2]);
	return('[' . $m[1] . '](' . $y . ')');
}

function format_event_diaspora($ev) {

	$a = get_app();

	if(! ((is_array($ev)) && count($ev)))
		return '';

	$bd_format = t('l F d, Y \@ g:i A') ; // Friday January 18, 2011 @ 8 AM

	$o = t('Redmatrix event notification:') . "\n";

	$o .= '**' . (($ev['summary']) ? bb2diaspora($ev['summary']) : bb2diaspora($ev['desc'])) .  '**' . "\n";

	$o .= t('Starts:') . ' ' . '['
		. (($ev['adjust']) ? day_translate(datetime_convert('UTC', 'UTC', 
			$ev['start'] , $bd_format ))
			:  day_translate(datetime_convert('UTC', 'UTC', 
			$ev['start'] , $bd_format)))
		.  '](' . $a->get_baseurl() . '/localtime/?f=&time=' . urlencode(datetime_convert('UTC','UTC',$ev['start'])) . ")\n";

	if(! $ev['nofinish'])
		$o .= t('Finishes:') . ' ' . '[' 
			. (($ev['adjust']) ? day_translate(datetime_convert('UTC', 'UTC', 
				$ev['finish'] , $bd_format ))
				:  day_translate(datetime_convert('UTC', 'UTC', 
				$ev['finish'] , $bd_format )))
			. '](' . $a->get_baseurl() . '/localtime/?f=&time=' . urlencode(datetime_convert('UTC','UTC',$ev['finish'])) . ")\n";

	if(strlen($ev['location']))
		$o .= t('Location:') . bb2diaspora($ev['location']) 
			. "\n";

	$o .= "\n";

	return $o;
}
