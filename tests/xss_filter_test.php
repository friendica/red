<?php
/**
* Tests, without pHPUnit by now
* @package test.util
*/

require_once('include/text.php'); 

class AntiXSSTest extends PHPUnit_Framework_TestCase {

/**
* test no tags
*/
	public function testEscapeTags() {
		$invalidstring='<submit type="button" onclick="alert(\'failed!\');" />';

		$validstring=notags($invalidstring);
		$escapedString=escape_tags($invalidstring);

		$this->assertEquals('[submit type="button" onclick="alert(\'failed!\');" /]', $validstring);
		$this->assertEquals("&lt;submit type=&quot;button&quot; onclick=&quot;alert('failed!');&quot; /&gt;", $escapedString);
	}

	/**
	 *autonames should be random, even length
	 */
	public function testAutonameEven() {
		$autoname1=autoname(10);
		$autoname2=autoname(10);

		$this->assertNotEquals($autoname1, $autoname2);
	}

	/**
	 *autonames should be random, odd length
	 */
	public function testAutonameOdd() {
		$autoname1=autoname(9);
		$autoname2=autoname(9);

		$this->assertNotEquals($autoname1, $autoname2);
	}

	/**
	 * try to fail autonames
	 */
	public function testAutonameNoLength() {
		$autoname1=autoname(0);
		$this->assertEquals(0, count($autoname1));
	}

	public function testAutonameNegativeLength() {
		$autoname1=autoname(-23);
		$this->assertEquals(0, count($autoname1));
	}
	
// 	public function testAutonameMaxLength() {
// 		$autoname2=autoname(PHP_INT_MAX);
// 		$this->assertEquals(PHP_INT_MAX, count($autoname2));
// 	}
	
	public function testAutonameLength1() {
		$autoname3=autoname(1);
		$this->assertEquals(1, count($autoname3));
	}

	/**
	 *xmlify and unxmlify
	 */
	public function testXmlify() {
		$text="<tag>I want to break\n this!11!<?hard?></tag>"; 
		$xml=xmlify($text); //test whether it actually may be part of a xml document
		$retext=unxmlify($text);

		$this->assertEquals($text, $retext);
	}

	/**
	 * test hex2bin and reverse
	 */

	public function testHex2Bin() {
		$this->assertEquals(-3, hex2bin(bin2hex(-3)));
		$this->assertEquals(0, hex2bin(bin2hex(0)));
		$this->assertEquals(12, hex2bin(bin2hex(12)));
		$this->assertEquals(PHP_INT_MAX, hex2bin(bin2hex(PHP_INT_MAX)));
	}
	
	/**
	 * test expand_acl
	 */
	public function testExpandAclNormal() {
		$text="<1><2><3>";
		$this->assertEquals(array(1, 2, 3), expand_acl($text));
	}
	
	public function testExpandAclBigNumber() {
		$text="<1><279012><15>";
		$this->assertEquals(array(1, 279012, 15), expand_acl($text));
	}

	public function testExpandAclString() {
		$text="<1><279012><tt>"; //maybe that's invalid
		$this->assertEquals(array(1, 279012, 'tt'), expand_acl($text));
	}

	public function testExpandAclSpace() {
		$text="<1><279 012><32>"; //maybe that's invalid
		$this->assertEquals(array(1, "279 012", "32"), expand_acl($text));
	}

	public function testExpandAclEmpty() {
		$text=""; //maybe that's invalid
		$this->assertEquals(array(), expand_acl($text));
	}

	public function testExpandAclNoBrackets() {
		$text="According to documentation, that's invalid. "; //should be invalid
		$this->assertEquals(array(), expand_acl($text));
	}

	public function testExpandAclJustOneBracket1() {
		$text="<Another invalid string"; //should be invalid
		$this->assertEquals(array(), expand_acl($text));
	}

	public function testExpandAclJustOneBracket2() {
		$text="Another invalid> string"; //should be invalid
		$this->assertEquals(array(), expand_acl($text));
	}

	public function testExpandAclCloseOnly() {
		$text="Another> invalid> string>"; //should be invalid
		$this->assertEquals(array(), expand_acl($text));
	}
	
	public function testExpandAclOpenOnly() {
		$text="<Another< invalid string<"; //should be invalid
		$this->assertEquals(array(), expand_acl($text));
	}
	
	public function testExpandAclNoMatching1() {
		$text="<Another<> invalid <string>"; //should be invalid
		$this->assertEquals(array(), expand_acl($text));
	}
	
	public function testExpandAclNoMatching2() {
		$text="<1>2><3>"; 
		$this->assertEquals(array(), expand_acl($text));
	}

	/**
	 * test attribute contains
	 */
	public function testAttributeContains1() {
		$testAttr="class1 notclass2 class3";
		$this->assertTrue(attribute_contains($testAttr, "class3"));
		$this->assertFalse(attribute_contains($testAttr, "class2"));
	}

	/**
	 * test attribute contains
	 */
	public function testAttributeContains2() {
		$testAttr="class1 not-class2 class3";
		$this->assertTrue(attribute_contains($testAttr, "class3"));
		$this->assertFalse(attribute_contains($testAttr, "class2"));
	}
	
	public function testAttributeContainsEmpty() {
		$testAttr="";
		$this->assertFalse(attribute_contains($testAttr, "class2"));
	}

	public function testAttributeContainsSpecialChars() {
		$testAttr="--... %\$ä() /(=?}";
		$this->assertFalse(attribute_contains($testAttr, "class2"));
	}
	
	/**
	 * test get_tags
	 */
	public function testGetTags() {
		$text="hi @Mike, I'm just writing #test_cases, "
		." so @somebody@friendica.com may change #things. Of course I "
		."look for a lot of #pitfalls, like #tags at the end of a sentence "
		."@comment. I hope noone forgets about @fullstops.because that might"
		." break #things. @Mike@campino@friendica.eu is also #nice, isn't it? "
		."Now, add a @first_last tag. "; 
		//check whether this are all variants (no, auto-stuff is missing).

		$tags=get_tags($text);

		$this->assertEquals("@Mike", $tags[0]);
		$this->assertEquals("#test_cases", $tags[1]);
		$this->assertEquals("@somebody@friendica.com", $tags[2]);
		$this->assertEquals("#things", $tags[3]);
		$this->assertEquals("#pitfalls", $tags[4]);
		$this->assertEquals("#tags", $tags[5]);
		$this->assertEquals("@comment", $tags[6]);
		$this->assertEquals("@fullstops", $tags[7]);
		$this->assertEquals("#things", $tags[8]);
		$this->assertEquals("@Mike", $tags[9]);
		$this->assertEquals("@campino@friendica.eu", $tags[10]);
		$this->assertEquals("#nice", $tags[11]);
		$this->assertEquals("@first_last", $tags[12]);
	}

	public function testGetTagsEmpty() {
		$tags=get_tags("");
		$this->assertEquals(0, count($tags));
	}
//function qp, quick and dirty??
//get_mentions
//get_contact_block, bis Zeile 538
}
?>
