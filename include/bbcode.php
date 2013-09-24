<?php /** @file */

require_once("include/oembed.php");
require_once('include/event.php');



function tryoembed($match) {
	$url = ((count($match)==2)?$match[1]:$match[2]);

	$o = oembed_fetch_url($url);

	if ($o->type=="error") return $match[0];

	$html = oembed_format_object($o);
	return $html; 
}

// [noparse][i]italic[/i][/noparse] turns into
// [noparse][ i ]italic[ /i ][/noparse],
// to hide them from parser.

function bb_spacefy($st) {
  $whole_match = $st[0];
  $captured = $st[1];
  $spacefied = preg_replace("/\[(.*?)\]/", "[ $1 ]", $captured);
  $new_str = str_replace($captured, $spacefied, $whole_match);
  return $new_str;
}

// The previously spacefied [noparse][ i ]italic[ /i ][/noparse],
// now turns back and the [noparse] tags are trimed
// returning [i]italic[/i]

function bb_unspacefy_and_trim($st) {
  $whole_match = $st[0];
  $captured = $st[1];
  $unspacefied = preg_replace("/\[ (.*?)\ ]/", "[$1]", $captured);
  return $unspacefied;
}

if(! function_exists('bb_extract_images')) {
function bb_extract_images($body) {

	$saved_image = array();
	$orig_body = $body;
	$new_body = '';

	$cnt = 0;
	$img_start = strpos($orig_body, '[img');
	$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
	$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
	while(($img_st_close !== false) && ($img_end !== false)) {

		$img_st_close++; // make it point to AFTER the closing bracket
		$img_end += $img_start;

		if(! strcmp(substr($orig_body, $img_start + $img_st_close, 5), 'data:')) {
			// This is an embedded image

			$saved_image[$cnt] = substr($orig_body, $img_start + $img_st_close, $img_end - ($img_start + $img_st_close));
			$new_body = $new_body . substr($orig_body, 0, $img_start) . '[$#saved_image' . $cnt . '#$]';

			$cnt++;
		}
		else
			$new_body = $new_body . substr($orig_body, 0, $img_end + strlen('[/img]'));

		$orig_body = substr($orig_body, $img_end + strlen('[/img]'));

		if($orig_body === false) // in case the body ends on a closing image tag
			$orig_body = '';

		$img_start = strpos($orig_body, '[img');
		$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
		$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
	}

	$new_body = $new_body . $orig_body;

	return array('body' => $new_body, 'images' => $saved_image);
}}

if(! function_exists('bb_replace_images')) {
function bb_replace_images($body, $images) {

	$newbody = $body;

	$cnt = 0;
	foreach($images as $image) {
		// We're depending on the property of 'foreach' (specified on the PHP website) that
		// it loops over the array starting from the first element and going sequentially
		// to the last element
		$newbody = str_replace('[$#saved_image' . $cnt . '#$]', '<img class="zrl" src="' . $image .'" alt="' . t('Image/photo') . '" />', $newbody);
		$cnt++;
	}

	return $newbody;
}}


function bb_ShareAttributes($match) {

	$attributes = $match[1];

	$author = "";
	preg_match("/author='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$author = urldecode($matches[1]);

	$link = "";
	preg_match("/link='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$link = $matches[1];

	$avatar = "";
	preg_match("/avatar='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$avatar = $matches[1];

	$profile = "";
	preg_match("/profile='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$profile = $matches[1];

	$posted = "";
	preg_match("/posted='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$posted = $matches[1];

	// FIXME - this should really be a wall-item-ago so it will get updated on the client
	$reldate = (($posted) ? relative_date($posted) : ''); 

	$headline = '<div class="shared_header">';

	if ($avatar != "")
		$headline .= '<img src="' . $avatar . '" alt="' . $author . '" height="32" width="32" />';

	// Bob Smith wrote the following post 2 hours ago

	$fmt = sprintf( t('%1$s wrote the following %2$s %3$s'),
		'<a href="' . zid($profile) . '" >' . $author . '</a>',
		'<a href="' . zid($link) . '" >' . t('post') . '</a>',
		$reldate
	);

	$headline .= '<span>' . $fmt . '</span></div>';

	$text = $headline . '<div class="reshared-content">' . trim($match[2]) . '</div>';

	return($text);
}

function bb_ShareAttributesSimple($match) {

	$attributes = $match[1];

	$author = "";
	preg_match("/author='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$author = html_entity_decode($matches[1],ENT_QUOTES,'UTF-8');

	preg_match('/author="(.*?)"/ism', $attributes, $matches);
	if ($matches[1] != "")
		$author = $matches[1];

	$profile = "";
	preg_match("/profile='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$profile = $matches[1];

	preg_match('/profile="(.*?)"/ism', $attributes, $matches);
	if ($matches[1] != "")
		$profile = $matches[1];

	$text = "<br />".html_entity_decode("&#x2672; ", ENT_QUOTES, 'UTF-8').' <a href="'.$profile.'">'.$author."</a>: div class=\"reshared-content\">" .$match[2]."</div>";

	return($text);
}

	// BBcode 2 HTML was written by WAY2WEB.net
	// extended to work with Mistpark/Friendica/Red - Mike Macgirvin

function bbcode($Text,$preserve_nl = false, $tryoembed = true) {

	$a = get_app();

	// Extract the private images which use data url's since preg has issues with
	// large data sizes. Stash them away while we do bbcode conversion, and then put them back
	// in after we've done all the regex matching. We cannot use any preg functions to do this.

	$extracted = bb_extract_images($Text);
	$Text = $extracted['body'];
	$saved_image = $extracted['images'];


	// Move all spaces out of the tags
	$Text = preg_replace("/\[(\w*)\](\s*)/ism", '$2[$1]', $Text);
	$Text = preg_replace("/(\s*)\[\/(\w*)\]/ism", '[/$2]$1', $Text);

	// Hide all [noparse] contained bbtags by spacefying them
	if (strpos($Text,'[noparse]') !== false) {
		$Text = preg_replace_callback("/\[noparse\](.*?)\[\/noparse\]/ism", 'bb_spacefy',$Text);
	}
	if (strpos($Text,'[nobb]') !== false) {
		$Text = preg_replace_callback("/\[nobb\](.*?)\[\/nobb\]/ism", 'bb_spacefy',$Text);
	}
	if (strpos($Text,'[pre]') !== false) {
		$Text = preg_replace_callback("/\[pre\](.*?)\[\/pre\]/ism", 'bb_spacefy',$Text);
	}



	// If we find any event code, turn it into an event.
	// After we're finished processing the bbcode we'll
	// replace all of the event code with a reformatted version.

	$ev = bbtoevent($Text);

	// process [observer] tags before we do anything else because we might
	// be stripping away stuff that then doesn't need to be worked on anymore
	$observer = $a->get_observer();
	if (strpos($Text,'[/observer]') !== false) {
		if ($observer) {
			$Text = preg_replace("/\[observer\=1\](.*?)\[\/observer\]/ism", '$1', $Text);
			$Text = preg_replace("/\[observer\=0\].*?\[\/observer\]/ism", '', $Text);
		} else {
			$Text = preg_replace("/\[observer\=1\].*?\[\/observer\]/ism", '', $Text);
			$Text = preg_replace("/\[observer\=0\](.*?)\[\/observer\]/ism", '$1', $Text);
		}
    }

	// Replace any html brackets with HTML Entities to prevent executing HTML or script
	// Don't use strip_tags here because it breaks [url] search by replacing & with amp

	$Text = str_replace("<", "&lt;", $Text);
	$Text = str_replace(">", "&gt;", $Text);

	
	// Convert new line chars to html <br /> tags

	// nlbr seems to be hopelessly messed up
	//	$Text = nl2br($Text);

	// We'll emulate it.

	$Text = str_replace("\r\n","\n", $Text);
	$Text = str_replace(array("\r","\n"), array('<br />','<br />'), $Text);

	if($preserve_nl)
		$Text = str_replace(array("\n","\r"), array('',''),$Text);


	$Text = str_replace(array("\t","  "),array("&nbsp;&nbsp;&nbsp;&nbsp;","&nbsp;&nbsp;"),$Text);

	// Set up the parameters for a URL search string
	$URLSearchString = "^\[\]";
	// Set up the parameters for a MAIL search string
	$MAILSearchString = $URLSearchString;

	// replace [observer.baseurl]
	if ($observer) {
		$obsBaseURL = $observer['xchan_url'];
		$obsBaseURL = preg_replace("/\/channel\/.*$/", '', $obsBaseURL);
		$Text = str_replace('[observer.baseurl]', $obsBaseURL, $Text);
		$Text = str_replace('[observer.url]',$observer['xchan_url'], $Text);
		$Text = str_replace('[observer.name]',$observer['xchan_name'], $Text);
		$Text = str_replace('[observer.address]',$observer['xchan_addr'], $Text);
		$Text = str_replace('[observer.photo]','[zmg]'.$observer['xchan_photo_l'].'[/zmg]', $Text);		
	} else {
		$Text = str_replace('[observer.baseurl]', '', $Text);
		$Text = str_replace('[observer.url]','', $Text);
		$Text = str_replace('[observer.name]','', $Text);
		$Text = str_replace('[observer.address]','', $Text);
		$Text = str_replace('[observer.photo]','', $Text);		
	}

	// Perform URL Search

	$urlchars = '[a-zA-Z0-9\:\/\-\?\&\;\.\=\@\_\~\#\%\$\!\+\,]';

	if (strpos($Text,'http') !== false) {
		$Text = preg_replace("/([^\]\='".'"'."]|^)(https?\:\/\/$urlchars+)/ism", '$1<a href="$2" >$2</a>', $Text);
	}
	if (strpos($Text,'[/share]') !== false) {
		$Text = preg_replace_callback("/\[share(.*?)\](.*?)\[\/share\]/ism","bb_ShareAttributes",$Text);	
	}
	if($tryoembed) {
		if (strpos($Text,'[/url]') !== false) {
			$Text = preg_replace_callback("/\[url\]([$URLSearchString]*)\[\/url\]/ism",'tryoembed',$Text);
		}
	  }
	if (strpos($Text,'[/url]') !== false) {
		$Text = preg_replace("/\[url\]([$URLSearchString]*)\[\/url\]/ism", '<a href="$1" >$1</a>', $Text);
		$Text = preg_replace("/\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", '<a href="$1" >$2</a>', $Text);
    }
	if (strpos($Text,'[/zrl]') !== false) {
		$Text = preg_replace("/\[zrl\]([$URLSearchString]*)\[\/zrl\]/ism", '<a class="zrl" href="$1" >$1</a>', $Text);
		$Text = preg_replace("/\[zrl\=([$URLSearchString]*)\](.*?)\[\/zrl\]/ism", '<a class="zrl" href="$1" >$2</a>', $Text);
    }
	// Perform MAIL Search
	if (strpos($Text,'[/mail]') !== false) {	
		$Text = preg_replace("/\[mail\]([$MAILSearchString]*)\[\/mail\]/", '<a href="mailto:$1">$1</a>', $Text);
		$Text = preg_replace("/\[mail\=([$MAILSearchString]*)\](.*?)\[\/mail\]/", '<a href="mailto:$1">$2</a>', $Text);
	}
	// Check for bold text
	if (strpos($Text,'[b]') !== false) {	
		$Text = preg_replace("(\[b\](.*?)\[\/b\])ism",'<strong>$1</strong>',$Text);
	}
	// Check for Italics text
	if (strpos($Text,'[i]') !== false) {		
		$Text = preg_replace("(\[i\](.*?)\[\/i\])ism",'<em>$1</em>',$Text);
	}
	// Check for Underline text
	if (strpos($Text,'[u]') !== false) {	
		$Text = preg_replace("(\[u\](.*?)\[\/u\])ism",'<u>$1</u>',$Text);
	}
	// Check for strike-through text
	if (strpos($Text,'[s]') !== false) {
		$Text = preg_replace("(\[s\](.*?)\[\/s\])ism",'<strike>$1</strike>',$Text);
	}
	// Check for over-line text
	if (strpos($Text,'[o]') !== false) {	
		$Text = preg_replace("(\[o\](.*?)\[\/o\])ism",'<span class="overline">$1</span>',$Text);
	}
	// Check for colored text
	if (strpos($Text,'[/color]') !== false) {	
		$Text = preg_replace("(\[color=(.*?)\](.*?)\[\/color\])ism","<span style=\"color: $1;\">$2</span>",$Text);
	}
	// Check for sized text
		// [size=50] --> font-size: 50px (with the unit).
	if (strpos($Text,'[/size]') !== false) {		
		$Text = preg_replace("(\[size=(\d*?)\](.*?)\[\/size\])ism","<span style=\"font-size: $1px;\">$2</span>",$Text);
		$Text = preg_replace("(\[size=(.*?)\](.*?)\[\/size\])ism","<span style=\"font-size: $1;\">$2</span>",$Text);
	}
	// Check for centered text
	if (strpos($Text,'[/center]') !== false) {	
	$Text = preg_replace("(\[center\](.*?)\[\/center\])ism","<div style=\"text-align:center;\">$1</div>",$Text);
	}
	// Check for list text
	$Text = str_replace("[*]", "<li>", $Text);

	// Check for style sheet commands
	if (strpos($Text,'[/style]') !== false) {		
		$Text = preg_replace("(\[style=(.*?)\](.*?)\[\/style\])ism","<span style=\"$1;\">$2</span>",$Text);
	}
	// Check for CSS classes
	if (strpos($Text,'[/class]') !== false) {	
		$Text = preg_replace("(\[class=(.*?)\](.*?)\[\/class\])ism","<span class=\"$1\">$2</span>",$Text);
	}
 	// handle nested lists
	$endlessloop = 0;

	while ((((strpos($Text, "[/list]") !== false) && (strpos($Text, "[list") !== false)) ||
		   ((strpos($Text, "[/ol]") !== false) && (strpos($Text, "[ol]") !== false)) || 
		   ((strpos($Text, "[/ul]") !== false) && (strpos($Text, "[ul]") !== false)) || 
		   ((strpos($Text, "[/li]") !== false) && (strpos($Text, "[li]") !== false))) && (++$endlessloop < 20)) {
		$Text = preg_replace("/\[list\](.*?)\[\/list\]/ism", '<ul class="listbullet" style="list-style-type: circle;">$1</ul>' ,$Text);
		$Text = preg_replace("/\[list=\](.*?)\[\/list\]/ism", '<ul class="listnone" style="list-style-type: none;">$1</ul>' ,$Text);
		$Text = preg_replace("/\[list=1\](.*?)\[\/list\]/ism", '<ul class="listdecimal" style="list-style-type: decimal;">$1</ul>' ,$Text);
		$Text = preg_replace("/\[list=((?-i)i)\](.*?)\[\/list\]/ism",'<ul class="listlowerroman" style="list-style-type: lower-roman;">$2</ul>' ,$Text);
		$Text = preg_replace("/\[list=((?-i)I)\](.*?)\[\/list\]/ism", '<ul class="listupperroman" style="list-style-type: upper-roman;">$2</ul>' ,$Text);
		$Text = preg_replace("/\[list=((?-i)a)\](.*?)\[\/list\]/ism", '<ul class="listloweralpha" style="list-style-type: lower-alpha;">$2</ul>' ,$Text);
		$Text = preg_replace("/\[list=((?-i)A)\](.*?)\[\/list\]/ism", '<ul class="listupperalpha" style="list-style-type: upper-alpha;">$2</ul>' ,$Text);
		$Text = preg_replace("/\[ul\](.*?)\[\/ul\]/ism", '<ul class="listbullet" style="list-style-type: circle;">$1</ul>' ,$Text);
		$Text = preg_replace("/\[ol\](.*?)\[\/ol\]/ism", '<ul class="listdecimal" style="list-style-type: decimal;">$1</ul>' ,$Text);
		$Text = preg_replace("/\[li\](.*?)\[\/li\]/ism", '<li>$1</li>' ,$Text);
	}
	if (strpos($Text,'[th]') !== false) {	
		$Text = preg_replace("/\[th\](.*?)\[\/th\]/sm", '<th>$1</th>' ,$Text);
	}
	if (strpos($Text,'[td]') !== false) {
		$Text = preg_replace("/\[td\](.*?)\[\/td\]/sm", '<td>$1</td>' ,$Text);
	}
	if (strpos($Text,'[tr]') !== false) {	
		$Text = preg_replace("/\[tr\](.*?)\[\/tr\]/sm", '<tr>$1</tr>' ,$Text);
	}
	if (strpos($Text,'[table]') !== false) {	
		$Text = preg_replace("/\[table\](.*?)\[\/table\]/sm", '<table>$1</table>' ,$Text);
		$Text = preg_replace("/\[table border=1\](.*?)\[\/table\]/sm", '<table border="1" >$1</table>' ,$Text);
		$Text = preg_replace("/\[table border=0\](.*?)\[\/table\]/sm", '<table border="0" >$1</table>' ,$Text);
	}
	$Text = str_replace('[hr]','<hr />', $Text);

	// This is actually executed in prepare_body()

	$Text = str_replace('[nosmile]','',$Text);

	// Check for font change text
	if (strpos($Text,'[/font]') !== false) {	
		$Text = preg_replace("/\[font=(.*?)\](.*?)\[\/font\]/sm","<span style=\"font-family: $1;\">$2</span>",$Text);
	}
	// Declare the format for [code] layout

	$CodeLayout = '<code>$1</code>';
	// Check for [code] text
	if (strpos($Text,'[code]') !== false) {	
		$Text = preg_replace("/\[code\](.*?)\[\/code\]/ism","$CodeLayout", $Text);
	}
	// Declare the format for [spoiler] layout
	$SpoilerLayout = '<blockquote class="spoiler">$1</blockquote>';

	// Check for [spoiler] text
	// handle nested quotes
	$endlessloop = 0;
	while ((strpos($Text, "[/spoiler]") !== false) and (strpos($Text, "[spoiler]") !== false) and (++$endlessloop < 20))
		$Text = preg_replace("/\[spoiler\](.*?)\[\/spoiler\]/ism","$SpoilerLayout", $Text);

	// Check for [spoiler=Author] text

	$t_wrote = t('$1 wrote:');

	// handle nested quotes
	$endlessloop = 0;
	while ((strpos($Text, "[/spoiler]")!== false)  and (strpos($Text, "[spoiler=") !== false) and (++$endlessloop < 20))
		$Text = preg_replace("/\[spoiler=[\"\']*(.*?)[\"\']*\](.*?)\[\/spoiler\]/ism",
			"<br /><strong class=".'"spoiler"'.">" . $t_wrote . "</strong><blockquote class=".'"spoiler"'.">$2</blockquote>",
			$Text);

	// Declare the format for [quote] layout
	$QuoteLayout = '<blockquote>$1</blockquote>';

	// Check for [quote] text
	// handle nested quotes
	$endlessloop = 0;
	while ((strpos($Text, "[/quote]") !== false) and (strpos($Text, "[quote]") !== false) and (++$endlessloop < 20))
		$Text = preg_replace("/\[quote\](.*?)\[\/quote\]/ism","$QuoteLayout", $Text);

	// Check for [quote=Author] text

	$t_wrote = t('$1 wrote:');

	// handle nested quotes
	$endlessloop = 0;
	while ((strpos($Text, "[/quote]")!== false)  and (strpos($Text, "[quote=") !== false) and (++$endlessloop < 20))
		$Text = preg_replace("/\[quote=[\"\']*(.*?)[\"\']*\](.*?)\[\/quote\]/ism",
			"<br /><strong class=".'"author"'.">" . $t_wrote . "</strong><blockquote>$2</blockquote>",
			$Text);

	// [img=widthxheight]image source[/img]
	//$Text = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '<img src="$3" style="height: $2px; width: $1px;" >', $Text);
	if (strpos($Text,'[/img]') !== false) {
		$Text = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '<img src="$3" style="width: $1px;" >', $Text);
	}
	if (strpos($Text,'[/zmg]') !== false) {	
		$Text = preg_replace("/\[zmg\=([0-9]*)x([0-9]*)\](.*?)\[\/zmg\]/ism", '<img class="zrl" src="$3" style="width: $1px;" >', $Text);
	}
	// Images
	// [img]pathtoimage[/img]
	if (strpos($Text,'[/img]') !== false) {	
		$Text = preg_replace("/\[img\](.*?)\[\/img\]/ism", '<img src="$1" alt="' . t('Image/photo') . '" />', $Text);
	}
	if (strpos($Text,'[/zmg]') !== false) {	
		$Text = preg_replace("/\[zmg\](.*?)\[\/zmg\]/ism", '<img class="zrl" src="$1" alt="' . t('Image/photo') . '" />', $Text);
	}

	if (strpos($Text,'[crypt]') !== false) {	
		$Text = preg_replace("/\[crypt\](.*?)\[\/crypt\]/ism",'<br/><img src="' .$a->get_baseurl() . '/images/lock_icon.gif" alt="' . t('Encrypted content') . '" title="' . t('Encrypted content') . '" /><br />', $Text);
		$Text = preg_replace("/\[crypt=(.*?)\](.*?)\[\/crypt\]/ism",'<br/><img src="' .$a->get_baseurl() . '/images/lock_icon.gif" alt="' . t('Encrypted content') . '" title="' . '$1' . ' ' . t('Encrypted content') . '" /><br />', $Text);
	}
	// Try to Oembed
	if ($tryoembed) {
		if (strpos($Text,'[/video]') !== false) {	
			$Text = preg_replace("/\[video\](.*?\.(ogg|ogv|oga|ogm|webm|mp4))\[\/video\]/ism", '<video src="$1" controls="controls" width="' . $a->videowidth . '" height="' . $a->videoheight . '"><a href="$1">$1</a></video>', $Text);
		}
		if (strpos($Text,'[/audio]') !== false) {		
			$Text = preg_replace("/\[audio\](.*?\.(ogg|ogv|oga|ogm|webm|mp4|mp3))\[\/audio\]/ism", '<audio src="$1" controls="controls"><a href="$1">$1</a></audio>', $Text);
		}
		if (strpos($Text,'[/video]') !== false) {			
			$Text = preg_replace_callback("/\[video\](.*?)\[\/video\]/ism", 'tryoembed', $Text);
		}
		if (strpos($Text,'[/audio]') !== false) {
			$Text = preg_replace_callback("/\[audio\](.*?)\[\/audio\]/ism", 'tryoembed', $Text);
		}
	}

	// if video couldn't be embedded, link to it instead.
	if (strpos($Text,'[/video]') !== false) {
		$Text = preg_replace("/\[video\](.*?)\[\/video\]/", '<a href="$1">$1</a>', $Text);
	}
	if (strpos($Text,'[/audio]') !== false) {
		$Text = preg_replace("/\[audio\](.*?)\[\/audio\]/", '<a href="$1">$1</a>', $Text);
	}


	// html5 video and audio


	if ($tryoembed){
		if (strpos($Text,'[/iframe]') !== false) {
			$Text = preg_replace("/\[iframe\](.*?)\[\/iframe\]/ism", '<iframe src="$1" width="' . $a->videowidth . '" height="' . $a->videoheight . '"><a href="$1">$1</a></iframe>', $Text);
		}
	}
	else {
		if (strpos($Text,'[/iframe]') !== false) {
			$Text = preg_replace("/\[iframe\](.*?)\[\/iframe\]/ism", '<a href="$1">$1</a>', $Text);
		}
	}
	// Youtube extensions
	if (strpos($Text,'[youtube]') !== false) {
		if ($tryoembed) {
			$Text = preg_replace_callback("/\[youtube\](https?:\/\/www.youtube.com\/watch\?v\=.*?)\[\/youtube\]/ism", 'tryoembed', $Text);		
			$Text = preg_replace_callback("/\[youtube\](www.youtube.com\/watch\?v\=.*?)\[\/youtube\]/ism", 'tryoembed', $Text);		
			$Text = preg_replace_callback("/\[youtube\](https?:\/\/youtu.be\/.*?)\[\/youtube\]/ism",'tryoembed',$Text); 
		}
			$Text = preg_replace("/\[youtube\]https?:\/\/www.youtube.com\/watch\?v\=(.*?)\[\/youtube\]/ism",'[youtube]$1[/youtube]',$Text); 
			$Text = preg_replace("/\[youtube\]https?:\/\/www.youtube.com\/embed\/(.*?)\[\/youtube\]/ism",'[youtube]$1[/youtube]',$Text); 
			$Text = preg_replace("/\[youtube\]https?:\/\/youtu.be\/(.*?)\[\/youtube\]/ism",'[youtube]$1[/youtube]',$Text);

		if ($tryoembed)
			$Text = preg_replace("/\[youtube\]([A-Za-z0-9\-_=]+)(.*?)\[\/youtube\]/ism", '<iframe width="' . $a->videowidth . '" height="' . $a->videoheight . '" src="http://www.youtube.com/embed/$1" frameborder="0" ></iframe>', $Text);
		else 
			$Text = preg_replace("/\[youtube\]([A-Za-z0-9\-_=]+)(.*?)\[\/youtube\]/ism", "http://www.youtube.com/watch?v=$1", $Text);
	}
	if (strpos($Text,'[vimeo]') !== false) {
		if ($tryoembed) {
			$Text = preg_replace_callback("/\[vimeo\](https?:\/\/player.vimeo.com\/video\/[0-9]+).*?\[\/vimeo\]/ism",'tryoembed',$Text); 
			$Text = preg_replace_callback("/\[vimeo\](https?:\/\/vimeo.com\/[0-9]+).*?\[\/vimeo\]/ism",'tryoembed',$Text); 
		}

		$Text = preg_replace("/\[vimeo\]https?:\/\/player.vimeo.com\/video\/([0-9]+)(.*?)\[\/vimeo\]/ism",'[vimeo]$1[/vimeo]',$Text); 
		$Text = preg_replace("/\[vimeo\]https?:\/\/vimeo.com\/([0-9]+)(.*?)\[\/vimeo\]/ism",'[vimeo]$1[/vimeo]',$Text);

		if ($tryoembed)
			$Text = preg_replace("/\[vimeo\]([0-9]+)(.*?)\[\/vimeo\]/ism", '<iframe width="' . $a->videowidth . '" height="' . $a->videoheight . '" src="http://player.vimeo.com/video/$1" frameborder="0" ></iframe>', $Text);
		else
			$Text = preg_replace("/\[vimeo\]([0-9]+)(.*?)\[\/vimeo\]/ism", "http://vimeo.com/$1", $Text);
	}
//	$Text = preg_replace("/\[youtube\](.*?)\[\/youtube\]/", '<object width="425" height="350" type="application/x-shockwave-flash" data="http://www.youtube.com/v/$1" ><param name="movie" value="http://www.youtube.com/v/$1"></param><!--[if IE]><embed src="http://www.youtube.com/v/$1" type="application/x-shockwave-flash" width="425" height="350" /><![endif]--></object>', $Text);


	// oembed tag
	$Text = oembed_bbcode2html($Text);

	// Avoid triple linefeeds through oembed
	$Text = str_replace("<br style='clear:left'></span><br /><br />", "<br style='clear:left'></span><br />", $Text);

	// If we found an event earlier, strip out all the event code and replace with a reformatted version.
	// Replace the event-start section with the entire formatted event. The other bbcode is stripped.
	// Summary (e.g. title) is required, earlier revisions only required description (in addition to 
	// start which is always required). Allow desc with a missing summary for compatibility.

	if((x($ev,'desc') || x($ev,'summary')) && x($ev,'start')) {
		$sub = format_event_html($ev);

		$Text = preg_replace("/\[event\-summary\](.*?)\[\/event\-summary\]/ism",'',$Text);
		$Text = preg_replace("/\[event\-description\](.*?)\[\/event\-description\]/ism",'',$Text);
		$Text = preg_replace("/\[event\-start\](.*?)\[\/event\-start\]/ism",$sub,$Text); 
		$Text = preg_replace("/\[event\-finish\](.*?)\[\/event\-finish\]/ism",'',$Text);
		$Text = preg_replace("/\[event\-location\](.*?)\[\/event\-location\]/ism",'',$Text);
		$Text = preg_replace("/\[event\-adjust\](.*?)\[\/event\-adjust\]/ism",'',$Text);
	}

	// Unhide all [noparse] contained bbtags unspacefying them 
	// and triming the [noparse] tag.
	if (strpos($Text,'[noparse]') !== false) {
		$Text = preg_replace_callback("/\[noparse\](.*?)\[\/noparse\]/ism", 'bb_unspacefy_and_trim',$Text);
	}
	if (strpos($Text,'[nobb]') !== false) {
		$Text = preg_replace_callback("/\[nobb\](.*?)\[\/nobb\]/ism", 'bb_unspacefy_and_trim',$Text);
	}
	if (strpos($Text,'[pre]') !== false) {
		$Text = preg_replace_callback("/\[pre\](.*?)\[\/pre\]/ism", 'bb_unspacefy_and_trim',$Text);
	}

	$Text = preg_replace('/\[\&amp\;([#a-z0-9]+)\;\]/','&$1;',$Text);

	// fix any escaped ampersands that may have been converted into links
	$Text = preg_replace("/\<(.*?)(src|href)=(.*?)\&amp\;(.*?)\>/ism",'<$1$2=$3&$4>',$Text);

	$Text = preg_replace("/\<(.*?)(src|href)=\"[^hfm](.*?)\>/ism",'<$1$2="">',$Text);

	if($saved_image)
		$Text = bb_replace_images($Text, $saved_image);

	// Clean up the HTML by loading and saving the HTML with the DOM
	// Only do it when it has to be done - for performance reasons
//	if (!$tryoembed) {//
//		$doc = new DOMDocument();
//		$doc->preserveWhiteSpace = false;

//		$Text = mb_convert_encoding($Text, 'HTML-ENTITIES', "UTF-8");

//		$doctype = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">';
//		@$doc->loadHTML($doctype."<html><body>".$Text."</body></html>");

//		$Text = $doc->saveHTML();
//		$Text = str_replace(array("<html><body>", "</body></html>", $doctype), array("", "", ""), $Text);

//		$Text = str_replace('<br></li>','</li>', $Text);

//		$Text = mb_convert_encoding($Text, "UTF-8", 'HTML-ENTITIES');
//	}

	call_hooks('bbcode',$Text);

	return $Text;
}

