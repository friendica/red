<?php

require_once('library/HTML5/Parser.php');

function parse_url_content(&$a) {
	$url = trim($_GET['url']);

	$template = "<a href=\"%s\" >%s</a>";

	if($url) 
		$s = fetch_url($url);
	
	if(! $s) {
		echo sprintf($template,$url,$url);
		killme();
	}

	$dom = HTML5_Parser::parse($s);

	if(! $dom)
		return $ret;

	$items = $dom->getElementsByTagName('title');
	
	foreach($items as $item) {
		$title = $item->textContent;
		break;
	}

	echo sprintf($template,$url,$title);
	killme();
}