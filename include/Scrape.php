<?php

require_once('library/HTML5/Parser.php');

if(! function_exists('scrape_dfrn')) {
function scrape_dfrn($url) {

	$ret = array();
	$s = fetch_url($url);

	if(! $s) 
		return $ret;

	$dom = HTML5_Parser::parse($s);

	if(! $dom)
		return $ret;

	$items = $dom->getElementsByTagName('link');

	// get DFRN link elements

	foreach($items as $item) {
		$x = $item->getAttribute('rel');
		if(($x === 'alternate') && ($item->getAttribute('type') === 'application/atom+xml'))
			$ret['feed_atom'] = $item->getAttribute('href');
		if(substr($x,0,5) == "dfrn-")
			$ret[$x] = $item->getAttribute('href');
		if($x === 'lrdd') {
			$decoded = urldecode($item->getAttribute('href'));
			if(preg_match('/acct:([^@]*)@/',$decoded,$matches))
				$ret['nick'] = $matches[1];
		}
	}

	// Pull out hCard profile elements

	$items = $dom->getElementsByTagName('*');
	foreach($items as $item) {
		if(attribute_contains($item->getAttribute('class'), 'vcard')) {
			$level2 = $item->getElementsByTagName('*');
			foreach($level2 as $x) {
				if(attribute_contains($x->getAttribute('class'),'fn'))
					$ret['fn'] = $x->textContent;
				if(attribute_contains($x->getAttribute('class'),'photo'))
					$ret['photo'] = $x->getAttribute('src');
				if(attribute_contains($x->getAttribute('class'),'key'))
					$ret['key'] = $x->textContent;
			}
		}
	}

	return $ret;
}}






if(! function_exists('validate_dfrn')) {
function validate_dfrn($a) {
	$errors = 0;
	if(! x($a,'key'))
		$errors ++;
	if(! x($a,'dfrn-request'))
		$errors ++;
	if(! x($a,'dfrn-confirm'))
		$errors ++;
	if(! x($a,'dfrn-notify'))
		$errors ++;
	if(! x($a,'dfrn-poll'))
		$errors ++;
	return $errors;
}}

if(! function_exists('scrape_meta')) {
function scrape_meta($url) {

	$ret = array();
	$s = fetch_url($url);

	if(! $s) 
		return $ret;

	$dom = HTML5_Parser::parse($s);

	if(! $dom)
		return $ret;

	$items = $dom->getElementsByTagName('meta');

	// get DFRN link elements

	foreach($items as $item) {
		$x = $item->getAttribute('name');
		if(substr($x,0,5) == "dfrn-")
			$ret[$x] = $item->getAttribute('content');
	}

	return $ret;
}}


if(! function_exists('scrape_vcard')) {
function scrape_vcard($url) {

	$ret = array();
	$s = fetch_url($url);

	if(! $s) 
		return $ret;

	$dom = HTML5_Parser::parse($s);

	if(! $dom)
		return $ret;

	// Pull out hCard profile elements

	$items = $dom->getElementsByTagName('*');
	foreach($items as $item) {
		if(attribute_contains($item->getAttribute('class'), 'vcard')) {
			$level2 = $item->getElementsByTagName('*');
			foreach($level2 as $x) {
				if(attribute_contains($x->getAttribute('class'),'fn'))
					$ret['fn'] = $x->textContent;
				if((attribute_contains($x->getAttribute('class'),'photo'))
					|| (attribute_contains($x->getAttribute('class'),'avatar')))
					$ret['photo'] = $x->getAttribute('src');
				if((attribute_contains($x->getAttribute('class'),'nickname'))
					|| (attribute_contains($x->getAttribute('class'),'uid')))
					$ret['nick'] = $x->textContent;
			}
		}
	}

	return $ret;
}}


if(! function_exists('scrape_feed')) {
function scrape_feed($url) {

	$ret = array();
	$s = fetch_url($url);

	if(! $s) 
		return $ret;

	$dom = HTML5_Parser::parse($s);

	if(! $dom)
		return $ret;

	$items = $dom->getElementsByTagName('link');

	// get Atom link elements

	foreach($items as $item) {
		$x = $item->getAttribute('rel');
		if(($x === 'alternate') && ($item->getAttribute('type') === 'application/atom+xml'))
			$ret['feed_atom'] = $item->getAttribute('href');
		if(($x === 'alternate') && ($item->getAttribute('type') === 'application/rss+xml'))
			$ret['feed_rss'] = $item->getAttribute('href');
	}

	return $ret;
}}