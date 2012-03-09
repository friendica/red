<?php
/**
 * @package test.util
 */

require_once 'include/template_processor.php';
require_once 'include/text.php';
require_once 'mod/item.php';

function q($sql) {
	return array(array('id'=>15, 'network'=>'stat', 'alias'=>'Mike', 'nick'=>'Mike', 'url'=>"http://justatest.de"));

}
function dbesc($str) {
	echo $str; 
}

class GetTagsTest extends PHPUnit_Framework_TestCase {

	public function setUp() {
		set_include_path(
				get_include_path() . PATH_SEPARATOR
				. 'include' . PATH_SEPARATOR
				. 'library' . PATH_SEPARATOR
				. 'library/phpsec' . PATH_SEPARATOR
				. '.' );
	}

	/**
	 * test with one Person tag
	 */
	public function testGetTagsShortPerson() {
		$text="hi @Mike";

		$tags=get_tags($text);

		$inform=''; 
		$str_tags='';
		handle_body($text, $inform, $str_tags, 11, $tags[0]);

		$this->assertEquals("@Mike", $tags[0]);
		$this->assertEquals($text, "hi @[url=http://justatest.de]Mike[/url]");
	}

	/**
	 * Test with one hash tag.
	 */
	public function testGetTagsShortTag() {
		$text="This is a #test_case";

		$tags=get_tags($text);

		$this->assertEquals("#test_case", $tags[0]);
	}

	/**
	 * test with a person and a hash tag
	 */
	public function testGetTagsShortTagAndPerson() {
		$text="hi @Mike This is a #test_case";

		$tags=get_tags($text);

		$inform='';
		$str_tags='';
		handle_body($text, $inform, $str_tags, 11, $tags[0]);
		
		$this->assertEquals("hi @[url=http://justatest.de]Mike[/url] This is a #test_case", $text); 
		$this->assertEquals("@Mike", $tags[0]);
		$this->assertEquals("#test_case", $tags[1]);
	}

	/**
	 * test with a person, a hash tag and some special chars.
	 */
	public function testGetTagsShortTagAndPersonSpecialChars() {
		$text="hi @Mike, This is a #test_case.";

		$tags=get_tags($text);

		$this->assertEquals("@Mike", $tags[0]);
		$this->assertEquals("#test_case", $tags[1]);
	}

	/**
	 * Test with a person tag and text behind it.
	 */
	public function testGetTagsPersonOnly() {
		$text="@Test I saw the Theme Dev group was created.";

		$tags=get_tags($text);

		$this->assertEquals("@Test", $tags[0]);
	}

	/**
	 * test with two persons and one special tag.
	 */
	public function testGetTags2Persons1TagSpecialChars() {
		$text="hi @Mike, I'm just writing #test_cases, so"
		." so @somebody@friendica.com may change #things.";

		$tags=get_tags($text);

		$this->assertEquals("@Mike", $tags[0]);
		$this->assertEquals("#test_cases", $tags[1]);
		$this->assertEquals("@somebody@friendica.com", $tags[2]);
		$this->assertEquals("#things", $tags[3]);
	}

	/**
	 * test with a long text.
	 */
	public function testGetTags() {
		$text="hi @Mike, I'm just writing #test_cases, "
		." so @somebody@friendica.com may change #things. Of course I "
		."look for a lot of #pitfalls, like #tags at the end of a sentence "
		."@comment. I hope noone forgets about @fullstops.because that might"
		." break #things. @Mike@campino@friendica.eu is also #nice, isn't it? "
		."Now, add a @first_last tag. ";
		//TODO check whether this are all variants (no, auto-stuff is missing).

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

	/**
	 * test with an empty string
	 */
	public function testGetTagsEmpty() {
		$tags=get_tags("");
		$this->assertEquals(0, count($tags));
	}
}