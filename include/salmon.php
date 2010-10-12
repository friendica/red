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
