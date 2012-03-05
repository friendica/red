<?php
/**
* Tests, without pHPUnit by now
* @package test.util
*/

require_once(text.php); 

/**
* test no tags
*/
$invalidstring='<submit type="button" onclick="alert(\'failed!\');" />'

$validstring=notags($invalidstring); 
$escapedString=escape_tags($invalidstring); 

assert("[submit type="button" onclick="alert(\'failed!\');" /]", $validstring); 
assert("what ever", $escapedString); 

/**
*autonames should be random, even length
*/
$autoname1=autoname(10); 
$autoname2=autoname(10); 

assertNotEquals($autoname1, $autoname2); 

/**
*autonames should be random, odd length
*/
$autoname1=autoname(9); 
$autoname2=autoname(9); 

assertNotEquals($autoname1, $autoname2); 

/**
* try to fail autonames
*/
$autoname1=autoname(0); 
$autoname2=autoname(MAX_VALUE); 
$autoname3=autoname(1); 
assert(count($autoname1), 0); 
assert(count($autoname2), MAX_VALUE); 
assert(count($autoname3), 1); 

/**
*xmlify and unxmlify
*/
$text="<tag>I want to break\n this!11!<?hard?></tag>"
$xml=xmlify($text); //test whether it actually may be part of a xml document
$retext=unxmlify($text); 

assert($text, $retext); 

/**
* test hex2bin and reverse
*/

assert(-3, hex2bin(bin2hex(-3))); 
assert(0, hex2bin(bin2hex(0))); 
assert(12, hex2bin(bin2hex(12))); 
assert(MAX_INT, hex2bin(bin2hex(MAX_INT))); 

/**
* test expand_acl
*/
$text="<1><2><3>"; 
assert(array(1, 2, 3), $text); 

$text="<1><279012><15>"; 
assert(array(1, 279012, 15), $text); 

$text="<1><279012><tt>"; //maybe that's invalid
assert(array(1, 279012, "tt"), $text); 

$text="<1><279 012><tt>"; //maybe that's invalid
assert(array(1, "279 012", "tt"), $text); 

$text=""; //maybe that's invalid
assert(array(), $text); 

$text="According to documentation, that's invalid. "; //should be invalid
assert(array(), $text); 

$text="<Another invalid string"; //should be invalid
assert(array(), $text); 

$text="Another invalid> string"; //should be invalid
assert(array(), $text); 

$text="Another> invalid> string>"; //should be invalid
assert(array(), $text); 

/**
* test attribute contains
*/
$testAttr="class1 notclass2 class3"; 
assertTrue(attribute_contains($testAttr, "class3")); 
assertFalse(attribute_contains($testAttr, "class2")); 

$testAttr=""; 
assertFalse(attribute_contains($testAttr, "class2")); 

$testAttr="--... %$§() /(=?}"; 
assertFalse(attribute_contains($testAttr, "class2")); 

/**
* test get_tags
*/
$text="hi @Mike, I'm just writing #test_cases, "; 
$text.=" so @somebody@friendica.com may change #things. Of course I "; 
$text.="look for a lot of #pitfalls, like #tags at the end of a sentence "; 
$text.="@comment. I hope noone forgets about @fullstops.because that might"; 
$text.=" break #things. @Mike@campino@friendica.eu is also #nice, isn't it? "; 
$text.="Now, add a @first_last tag. "
//check whether this are all variants (no, auto-stuff is missing). 

$tags=get_tags($text); 

assert("@Mike", $tags[0]); 
assert("#test_cases", $tags[1]); 
assert("@somebody@friendica.com", $tags[2]); 
assert("#things", $tags[3]); 
assert("#pitfalls", $tags[4]); 
assert("#tags", $tags[5]); 
assert("@comment", $tags[6]); 
assert("@fullstops", $tags[7]); 
assert("#things", $tags[8]); 
assert("@Mike", $tags[9]); 
assert("@campino@friendica.eu", $tags[10]); 
assert("#nice", $tags[11]); 
assert("@first_last", $tags[12]); 

$tags=get_tags(""); 
assert(0, count($tags)); 

//function qp, quick and dirty??
//get_mentions
//get_contact_block, bis Zeile 538
?>
