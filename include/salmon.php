<?php

require_once('library/asn1.php');

function salmon_key($pubkey) {
	$lines = explode("\n",$pubkey);
	unset($lines[0]);
	unset($lines[count($lines)]);
	$x = base64_decode(implode('',$lines));

	$r = ASN_BASE::parseASNString($x);

	$m = $r[0]->asnData[1]->asnData[0]->asnData[0]->asnData;
	$e = $r[0]->asnData[1]->asnData[0]->asnData[1]->asnData;


	return 'RSA' . '.' . $m . '.' . $e ;
}


function base64url_encode($s) {
	return strtr(base64_encode($s),'+/','-_');
}

function base64url_decode($s) {
	return base64_decode(strtr($s,'-_','+/'));
}

function get_salmon_key($uri,$keyhash) {
	$ret = array();

	$debugging = get_config('system','debugging');
	if($debugging)		
		file_put_contents('salmon.out', "\n" . 'Fetch key' . "\n", FILE_APPEND);

	if(strstr($uri,'@')) {	
		$arr = webfinger($uri);
		if($debugging)
			file_put_contents('salmon.out', "\n" . 'Fetch key from webfinger' . "\n", FILE_APPEND);
	}
	else {
		$html = fetch_url($uri);
		$a = get_app();
		$h = $a->get_curl_headers();
		if($debugging)
			file_put_contents('salmon.out', "\n" . 'Fetch key via HTML header: ' . $h . "\n", FILE_APPEND);

		$l = explode("\n",$h);
		if(count($l)) {
			foreach($l as $line) {
				
				if($debugging)
					file_put_contents('salmon.out', "\n" . $line . "\n", FILE_APPEND);
				if((stristr($line,'link:')) && preg_match('/<([^>].*)>.*rel\=[\'\"]lrdd[\'\"]/',$line,$matches)) {
					$link = $matches[1];
					if($debugging)
						file_put_contents('salmon.out', "\n" . 'Fetch key via Link from header: ' . $link . "\n", FILE_APPEND);
					break;
				}
			}
		}
	}

	if(! isset($link)) {
		require_once('library/HTML5/Parser.php');
		$dom = HTML5_Parser::parse($html);

		if(! $dom)
			return '';

		$items = $dom->getElementsByTagName('link');

		foreach($items as $item) {
			$x = $item->getAttribute('rel');
			if($x == "lrdd") {
				$link = $item->getAttribute('href');
				if($debugging)
					file_put_contents('salmon.out', "\n" . 'Fetch key via HTML body' . $link . "\n", FILE_APPEND);
				break;
			}
		}
	}

	if(! isset($link))
		return '';

	$arr = fetch_xrd_links($link);

	if($arr) {
		foreach($arr as $a) {
			if($a['@attributes']['rel'] === 'magic-public-key') {
				$ret[] = $a['@attributes']['href'];
			}
		}
	}
	if(count($ret)) {
		for($x = 0; $x < count($ret); $x ++) {
			if(substr($ret[$x],0,5) === 'data:') {
				if(strstr($ret[$x],','))
					$ret[$x] = substr($ret[$x],strpos($ret[$x],',')+1);
				else
					$ret[$x] = substr($ret[$x],5);
			}
			else
				$ret[$x] = fetch_url($ret[$x]);
		}
	}
	if($debugging)
		file_put_contents('salmon.out', "\n" . 'Key located: ' . print_r($ret,true) . "\n", FILE_APPEND);

	if(count($ret) == 1) {
		return $ret[0];
	}
	else {
		foreach($ret as $a) {
			$hash = base64url_encode(hash('sha256',$a));
			if($hash == $keyhash)
				return $a;
		}
	}

	return '';
}

	
		
				