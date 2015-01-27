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

	$text = '';

	if(argc() > 1) {
		$path = '';
		for($x = 1; $x < argc(); $x ++) {
			if(strlen($path))
				$path .= '/';
			$path .= argv($x);
		}
		$title = basename($path);

		$text = load_doc_file('doc/' . $path . '.md');
		$a->page['title'] = t('Help:') . ' ' . ucwords(str_replace('-',' ',notags($title)));

		if(! $text) {
			$text = load_doc_file('doc/' . $path . '.bb');
			if($text)
				$doctype = 'bbcode';
			$a->page['title'] = t('Help:') . ' ' . ucwords(str_replace('_',' ',notags($title)));
		}
		if(! $text) {
			$text = load_doc_file('doc/' . $path . '.html');
			if($text)
				$doctype = 'html';
			$a->page['title'] = t('Help:') . ' ' . ucwords(str_replace('-',' ',notags($title)));
		}
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

	if($doctype === 'html')
		$content = $text;
	if($doctype === 'markdown')	{
		require_once('library/markdown.php');
		# escape #include tags
		$text = preg_replace('/#include/ism', '%%include', $text);
		$content = Markdown($text);
		$content = preg_replace('/%%include/ism', '#include', $content);
	}
	if($doctype === 'bbcode') {
		require_once('include/bbcode.php');
		$content = bbcode($text);
	} 

	$content = preg_replace_callback("/#include (.*?)\;/ism", 'preg_callback_help_include', $content);

	return replace_macros(get_markup_template("help.tpl"), array(
		'$content' => $content
	));

}


function preg_callback_help_include($matches) {

	if($matches[1]) {
		$include = str_replace($matches[0],load_doc_file($matches[1]),$matches[0]);
		if(preg_match('/\.bb$/', $matches[1]) || preg_match('/\.txt$/', $matches[1])) {
			require_once('include/bbcode.php');
			$include = bbcode($include);
		} elseif(preg_match('/\.md$/', $matches[1])) {
			require_once('library/markdown.php');
			$include = Markdown($include);
		}
		return $include;
	}

}

