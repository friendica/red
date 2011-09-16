<?php

/**
 * html2bbcode
 */


function html2bbcode($s) {


	// only keep newlines from source that are within pre tags

	$s = stripnl_exceptinpre($s);


	// Tags to Find

	$htmltags = array(
		'/\<pre\>(.*?)\<\/pre\>/is',
		'/\<p(.*?)\>/is',
		'/\<\/p\>/is',
		'/\<b\>(.*?)\<\/b\>/is',
		'/\<i\>(.*?)\<\/i\>/is',
		'/\<u\>(.*?)\<\/u\>/is',
		'/\<ul\>(.*?)\<\/ul\>/is',
		'/\<li\>(.*?)\<\/li\>/is',
		'/\<img(.*?)width: *([0-9]+)(.*?)height: *([0-9]+)(.*?)src=\"(.*?)\" (.*?)\>/is',
		'/\<img(.*?)height: *([0-9]+)(.*?)width: *([0-9]+)(.*?)src=\"(.*?)\" (.*?)\>/is',
		'/\<img(.*?)src=\"(.*?)\"(.*?)width: *([0-9]+)(.*?)height: *([0-9]+)(.*?)\>/is',
		'/\<img(.*?)src=\"(.*?)\"(.*?)height: *([0-9]+)(.*?)width: *([0-9]+)(.*?)\>/is',
		'/\<img(.*?) src=\"(.*?)\" (.*?)\>/is',
		'/\<div(.*?)\>(.*?)\<\/div\>/is',
		'/\<br(.*?)\>/is',
		'/\<strong\>(.*?)\<\/strong\>/is',
		'/\<a (.*?)href=\"(.*?)\"(.*?)\>(.*?)\<\/a\>/is',
		'/\<code\>(.*?)\<\/code\>/is',
		'/\<span style=\"color:(.*?)\"\>(.*?)\<\/span\>/is',
		'/\<span style=\"font-size:(.*?)\"\>(.*?)\<\/span\>/is',
		'/\<blockquote\>(.*?)\<\/blockquote\>/is',
		'/\<video(.*?) src=\"(.*?)\" (.*?)\>(.*?)\<\/video\>/is',
		'/\<audio(.*?) src=\"(.*?)\" (.*?)\>(.*?)\<\/audio\>/is',
		'/\<iframe(.*?) src=\"(.*?)\" (.*?)\>(.*?)\<\/iframe\>/is',

	);

	// Replace with

	$bbtags = array(
		'[code]$1[/code]',
		'',
		"\n",
		'[b]$1[/b]',
		'[i]$1[/i]',
		'[u]$1[/u]',
		'[list]$1[/list]',
		'[*]$1',
		'[img=$2x$4]$6[/img]',
		'[img=$4x$2]$6[/img]',
		'[img=$4x$6]$2[/img]',
		'[img=$6x$4]$2[/img]',
		'[img]$2[/img]',
		'$2',
		"\n",
		'[b]$1[/b]',
		'[url=$2]$4[/url]',
		'[code]$1[/code]',
		'[color="$1"]$2[/color]',
		'[size=$1]$2[/size]',
		'[quote]$1[/quote]',
		'[video]$1[/video]',
		'[audio]$1[/audio]',
		'[iframe]$1[/iframe]',
	);

	// Replace $htmltags in $text with $bbtags
	$text = preg_replace ($htmltags, $bbtags, $s);

	call_hooks('html2bbcode', $text);

	// Strip all other HTML tags
	$text = strip_tags($text);
	return $text;

}

function stripnl_exceptinpre($string)
{
    // First, check for <pre> tag
    if(strpos($string, '<pre>') === false)
    {
        return str_replace("\n","", $string);
    }

    // If there is a <pre>, we have to split by line
    // and manually replace the linebreaks

    $strArr=explode("\n", $string);

    $output="";
    $preFound=false;

    // Loop over each line
    foreach($strArr as $line)
    {    // See if the line has a <pre>. If it does, set $preFound to true
        if(strpos($line, "<pre>") !== false)
        {
            $preFound=true;
        }
        elseif(strpos($line, "</pre>") !== false)
        {
            $preFound=false;
        }
       
        // If we are in a pre tag, add line and also add \n, else add the line without \n
        if($preFound)
        {
            $output .= $line . "\n";
        }
        else
        {
            $output .= $line ;
        }
    }

    return $output;
}

