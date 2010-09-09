<?php


function html2bbcode($s) {


// Tags to Find
$htmltags = array(
                        '/\<b\>(.*?)\<\/b\>/is',
                        '/\<i\>(.*?)\<\/i\>/is',
                        '/\<u\>(.*?)\<\/u\>/is',
                        '/\<ul\>(.*?)\<\/ul\>/is',
                        '/\<li\>(.*?)\<\/li\>/is',
                        '/\<img(.*?) src=\"(.*?)\" (.*?)\>/is',
                        '/\<div(.*?)\>(.*?)\<\/div\>/is',
                        '/\<br(.*?)\>/is',
                        '/\<strong\>(.*?)\<\/strong\>/is',
                        '/\<a href=\"(.*?)\"(.*?)\>(.*?)\<\/a\>/is',
			'/\<code\>(.*?)\<\/code\>/is',
			'/\<font color=(.*?)\>(.*?)\<\/font\>',
			'/\<font color=\"(.*?)\"\>(.*?)\<\/font\>',
			'/\<blockquote\>(.*?)\<\/blockquote\>/is',

                        );

// Replace with
$bbtags = array(
                        '[b]$1[/b]',
                        '[i]$1[/i]',
                        '[u]$1[/u]',
                        '[list]$1[/list]',
                        '[*]$1',
                        '[img]$2[/img]',
                        '$2',
                        '\n',
                        '[b]$1[/b]',
                        '[url=$1]$3[/url]',
			'[code]$1[/code],
			'[color="$1"]$2[/color]',
			'[color="$1"]$2[/color]',
			'[quote]$1[/quote]',
                        );

// Replace $htmltags in $text with $bbtags
$text = preg_replace ($htmltags, $bbtags, $s);

// Strip all other HTML tags
$text = strip_tags($text);
return $text;
}