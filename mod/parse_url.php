<?php

require_once('library/HTML5/Parser.php');

function parse_url_content(&$a) {

	$url = trim($_GET['url']);

	$template = "<a href=\"%s\" >%s</a>%s";

	if($url) 
		$s = fetch_url($url);
	else {
		echo '';
		killme();
	}
	
	if(! $s) {
		echo sprintf($template,$url,$url,'');
		killme();
	}

	$dom = HTML5_Parser::parse($s);

	if(! $dom)
		return $ret;

	$items = $dom->getElementsByTagName('title');

	if($items) {
		foreach($items as $item) {
			$title = $item->textContent;
			break;
		}
	}

	$items = $dom->getElementsByTagName('p');
	if($items) {
		foreach($items as $item) {
			$text = $item->textContent;
			$text = strip_tags($text);
			if(strlen($text) < 100)
				continue;
			$text = substr($text,0,250) . '...' ;
			break;
		}
	}

	if(strlen($text)) {
		$text = '<br />' . $text;
	}

	echo sprintf($template,$url,$title,$text);
	killme();
}