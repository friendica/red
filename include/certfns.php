<?php

require_once('library/ASNValue.class.php');

function DerToPem($Der, $Private=false)
{
    //Encode:
    $Der = base64_encode($Der);
    //Split lines:
    $lines = str_split($Der, 65);
    $body = implode("\n", $lines);
    //Get title:
    $title = $Private? 'RSA PRIVATE KEY' : 'PUBLIC KEY';
    //Add wrapping:
    $result = "-----BEGIN {$title}-----\n";
    $result .= $body . "\n";
    $result .= "-----END {$title}-----\n";
 
    return $result;
}

function pkcs8_encode($Modulus,$PublicExponent) {
	//Encode key sequence
	$modulus = new ASNValue(ASNValue::TAG_INTEGER);
	$modulus->SetIntBuffer($Modulus);
	$publicExponent = new ASNValue(ASNValue::TAG_INTEGER);
	$publicExponent->SetInt($PublicExponent);
	$keySequenceItems = array($modulus, $publicExponent);
	$keySequence = new ASNValue(ASNValue::TAG_SEQUENCE);
	$keySequence->SetSequence($keySequenceItems);
	//Encode bit string
	$bitStringValue = $keySequence->Encode();
	$bitStringValue = chr(0x00) . $bitStringValue; //Add unused bits byte
	$bitString = new ASNValue(ASNValue::TAG_BITSTRING);
	$bitString->Value = $bitStringValue;
	//Encode body
	$bodyValue = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00" . $bitString->Encode();
	$body = new ASNValue(ASNValue::TAG_SEQUENCE);
	$body->Value = $bodyValue;
	//Get DER encoded public key:
	$PublicDER = $body->Encode();
	return $PublicDER;
}


function metopem($m,$e) {
	$der = pkcs8_emcode($m,$e);
	$key = DerToPem($der,true);
	return $key;
}	


