<?php

require_once('library/HTML5/Parser.php');
require_once('library/HTMLPurifier.auto.php');

function parse_url_content(&$a) {

	logger('parse_url: ' . $_GET['url']);

	$url = trim(hex2bin($_GET['url']));

	logger('parse_url: ' . $url);

	$text = null;

	$template = "<a href=\"%s\" >%s</a>\n%s";


	$arr = array('url' => $url, 'text' => '');

	call_hooks('parse_link', $arr);

	if(strlen($arr['text'])) {
		echo $arr['text'];
		killme();
	}

	if($url) {
		$s = fetch_url($url);
	} else {
		echo '';
		killme();
	}

	logger('parse_url: data: ' . $s, LOGGER_DATA);

	if(! $s) {
		echo sprintf($template,$url,$url,'');
		killme();
	}

	$config = HTMLPurifier_Config::createDefault();
	$config->set('Cache.DefinitionImpl', null);

	$purifier = new HTMLPurifier($config);
	$s = $purifier->purify($s);

	$dom = @HTML5_Parser::parse($s);

	if(! $dom) {
		echo sprintf($template,$url,$url,'');
		killme();
	}

	$items = $dom->getElementsByTagName('title');

	if($items) {
		foreach($items as $item) {
			$title = trim($item->textContent);
			break;
		}
	}

	$divs = $dom->getElementsByTagName('div');
	if($divs) {
		foreach($divs as $div) {
			$class = $div->getAttribute('class');
			if($class && stristr($class,'article')) {
				$items = $div->getElementsByTagName('p');
				if($items) {
					foreach($items as $item) {
						if($item->getElementsByTagName('script'))
							continue;
						$text = $item->textContent;
						$text = strip_tags($text);
						if(strlen($text) < 100)
							continue;
						$text = substr($text,0,250) . '...' ;
						break;
					}
				}
			}
		}
	}

	if(! $text) {
		$items = $dom->getElementsByTagName('p');
		if($items) {
			foreach($items as $item) {
				if($item->getElementsByTagName('script'))
					continue;
				$text = $item->textContent;
				$text = strip_tags($text);
				if(strlen($text) < 100)
					continue;
				$text = substr($text,0,250) . '...' ;
				break;
			}
		}
	}

	if(strlen($text)) {
		$text = '<br />' . $text;
	}

	echo sprintf($template,$url,($title) ? $title : $url,$text);
	killme();
}
