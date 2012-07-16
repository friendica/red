<?php


/**
 * translation support
 */


/**
 *
 * Get the language setting directly from system variables, bypassing get_config()
 * as database may not yet be configured.
 * 
 * If possible, we use the value from the browser.
 *
 */


if(! function_exists('get_browser_language')) {
function get_browser_language() {

	$langs = array();

	if (x($_SERVER,'HTTP_ACCEPT_LANGUAGE')) {
	    // break up string into pieces (languages and q factors)
    	preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', 
			$_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);

    	if (count($lang_parse[1])) {
        	// create a list like "en" => 0.8
        	$langs = array_combine($lang_parse[1], $lang_parse[4]);
    	
        	// set default to 1 for any without q factor
        	foreach ($langs as $lang => $val) {
            	if ($val === '') $langs[$lang] = 1;
        	}

        	// sort list based on value	
        	arsort($langs, SORT_NUMERIC);
    	}
	}
	else
		$langs['en'] = 1;

	return $langs;
}}


function get_best_language() {

	$langs = get_browser_language();

	if(isset($langs) && count($langs)) {
		foreach ($langs as $lang => $v) {
			if(file_exists("view/$lang") && is_dir("view/$lang")) {
				$preferred = $lang;
				break;
			}
		}
	}

	if(isset($preferred))
		return $preferred;

    $a = get_app();
	return ((isset($a->config['system']['language'])) ? $a->config['system']['language'] : 'en');
}


function push_lang($language) {
	global $lang, $a;

	$a->langsave = $lang;

	if($language === $lang)
		return;

	if(isset($a->strings) && count($a->strings)) {
		$a->stringsave = $a->strings;
	}
	$a->strings = array();
	load_translation_table($language);
	$lang = $language;
}

function pop_lang() {
	global $lang, $a;

	if($lang === $a->langsave)
		return;

	if(isset($a->stringsave))
		$a->strings = $a->stringsave;
	else
		$a->strings = array();

	$lang = $a->langsave;
}


// load string translation table for alternate language

if(! function_exists('load_translation_table')) {
function load_translation_table($lang) {
	global $a;

	if(file_exists("view/$lang/strings.php")) {
		include("view/$lang/strings.php");
	}
	else
		$a->strings = array();
}}

// translate string if translation exists

if(! function_exists('t')) {
function t($s) {

	$a = get_app();

	if(x($a->strings,$s)) {
		$t = $a->strings[$s];
		return is_array($t)?$t[0]:$t;
	}
	return $s;
}}

if(! function_exists('tt')){
function tt($singular, $plural, $count){
	global $lang;
	$a = get_app();

	if(x($a->strings,$singular)) {
		$t = $a->strings[$singular];
		$f = 'string_plural_select_' . str_replace('-','_',$lang);
		if(! function_exists($f))
			$f = 'string_plural_select_default';
		$k = $f($count);
		return is_array($t)?$t[$k]:$t;
	}
	
	if ($count!=1){
		return $plural;
	} else {
		return $singular;
	}
}}

// provide a fallback which will not collide with 
// a function defined in any language file 

if(! function_exists('string_plural_select_default')) {
function string_plural_select_default($n) {
	return ($n != 1);
}}



function detect_language($s) {

	$detected_languages = array(
		'Albanian'   => 'sq',
		'Arabic'     => 'ar',
		'Azeri'      => 'az',
		'Bengali'    => 'bn',
		'Bulgarian'  => 'bg',
		'Cebuano'    => '',
		'Croatian'   => 'hr',
		'Czech'      => 'cz',
		'Danish'     => 'da',
		'Dutch'      => 'nl',
		'English'    => 'en',
		'Estonian'   => 'et',
		'Farsi'      => 'fa',
		'Finnish'    => 'fi',
		'French'     => 'fr',
		'German'     => 'de',
		'Hausa'      => 'ha',
		'Hawaiian'   => '',
		'Hindi'      => 'hi',
		'Hungarian'  => 'hu',
		'Icelandic'  => 'is',
		'Indonesian' => 'id',
		'Italian'    => 'it',
		'Kazakh'     => 'kk',
		'Kyrgyz'     => 'ky',
		'Latin'      => 'la',
		'Latvian'    => 'lv',
		'Lithuanian' => 'lt',
		'Macedonian' => 'mk',
		'Mongolian'  => 'mn',
		'Nepali'     => 'ne',
		'Norwegian'  => 'no',
		'Pashto'     => 'ps',
		'Pidgin'     => '',
		'Polish'     => 'pl',
		'Portuguese' => 'pt',
		'Romanian'   => 'ro',
		'Russian'    => 'ru',
		'Serbian'    => 'sr',
		'Slovak'     => 'sk',
		'Slovene'    => 'sl',
		'Somali'     => 'so',
		'Spanish'    => 'es',
		'Swahili'    => 'sw',
		'Swedish'    => 'sv',
		'Tagalog'    => 'tl',
		'Turkish'    => 'tr',
		'Ukrainian'  => 'uk',
		'Urdu'       => 'ur',
		'Uzbek'      => 'uz',
		'Vietnamese' => 'vi',
		'Welsh'      => 'cy'
	);

	require_once('Text/LanguageDetect.php');

	$min_length = get_config('system','language_detect_min_length');
	if($min_length === false)
		$min_length = LANGUAGE_DETECT_MIN_LENGTH;

	$min_confidence = get_config('system','language_detect_min_confidence');
	if($min_confidence === false)
		$min_confidence = LANGUAGE_DETECT_MIN_CONFIDENCE;


	$naked_body = preg_replace('/\[(.+?)\]/','',$s);
	if(mb_strlen($naked_body) < intval($min_length))
		return '';

	$l = new Text_LanguageDetect;
	$lng = $l->detectConfidence($naked_body);

	logger('detect language: ' . print_r($lng,true) . $naked_body, LOGGER_DATA);

	if((! $lng) || (! (x($lng,'language')))) {
		return '';
	}

	if($lng['confidence'] < (float) $min_confidence) {
		logger('detect language: confidence less than ' . (float) $min_confidence, LOGGER_DATA);
		return '';
	}

	return(($lng && (x($lng,'language'))) ? $detected_languages[ucfirst($lng['language'])] : '');

}
