<?php

/**
 * You can create local site resources in doc/Site.md and either link to doc/Home.md for the standard resources
 * or use our include mechanism to include it on your local page.
 *
 * #include doc/Home.md;
 *
 * The syntax is somewhat strict. 
 *
 */


if(! function_exists('load_doc_file')) {
function load_doc_file($s) {
	$lang = get_app()->language;
	if(! isset($lang))
		$lang = 'en';
	$b = basename($s);
	$d = dirname($s);
	if(file_exists("$d/$lang/$b"))
		return file_get_contents("$d/$lang/$b");
	if(file_exists($s))
		return file_get_contents($s);
	return '';
}}



function help_content(&$a) {
	nav_set_selected('help');

	global $lang;

	$doctype = 'markdown';

	require_once('library/markdown.php');

	$text = '';

	if(argc() > 1) {
		$text = load_doc_file('doc/' . $a->argv[1] . '.md');
		$a->page['title'] = t('Help:') . ' ' . str_replace('-',' ',notags(argv(1)));
	}
	if(! $text) {
		$text = load_doc_file('doc/' . $a->argv[1] . '.bb');
		if($text)
			$doctype = 'bbcode';
		$a->page['title'] = t('Help:') . ' ' . str_replace('_',' ',ucfirst(notags(argv(1))));
	}
	if(! $text) {
		$text = load_doc_file('doc/' . $a->argv[1] . '.html');
		if($text)
			$doctype = 'html';
		$a->page['title'] = t('Help:') . ' ' . str_replace('-',' ',notags(argv(1)));
	}

	if(! $text) {
		$text = load_doc_file('doc/Site.md');
		$a->page['title'] = t('Help');
	}
	if(! $text) {
		$doctype = 'bbcode';
		$text = load_doc_file('doc/main.bb');
		$a->page['title'] = t('Help');
	}
	
	if(! strlen($text)) {
		header($_SERVER["SERVER_PROTOCOL"] . ' 404 ' . t('Not Found'));
		$tpl = get_markup_template("404.tpl");
		return replace_macros($tpl, array(
			'$message' =>  t('Page not found.' )
		));
	}

	$text = preg_replace_callback("/#include (.*?)\;/ism", 'preg_callback_help_include', $text);

	if($doctype === 'html')
		return $text;
	if($doctype === 'markdown')	
		return Markdown($text);
	if($doctype === 'bbcode') {
		require_once('include/bbcode.php');
		return bbcode($text);
	} 

}


function preg_callback_help_include($matches) {

	if($matches[1])
		return str_replace($matches[0],load_doc_file($matches[1]),$matches[0]);

}

