<?php
/**
 * @package test.util
 */

require_once("include/template_processor.php");
require_once('include/text.php');

class AntiXSSTest extends PHPUnit_Framework_TestCase {

	public function setUp() {
		set_include_path(
				get_include_path() . PATH_SEPARATOR
				. 'include' . PATH_SEPARATOR
				. 'library' . PATH_SEPARATOR
				. 'library/phpsec' . PATH_SEPARATOR
				. '.' );
	}

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

	//function qp, quick and dirty??
	//get_mentions
	//get_contact_block, bis Zeile 538
}
?>
