<?php
/**
 * @file
 *
 * @brief translation support
 *
 * This file contains functions to work with translations and other
 * language related tasks.
 */


/**
 * @brief Get the browser's submitted preferred languages.
 *
 * This functions parses the HTTP_ACCEPT_LANGUAGE header sent by the browser and
 * extracts the preferred languages and their priority.
 *
 * Get the language setting directly from system variables, bypassing get_config()
 * as database may not yet be configured.
 * 
 * If possible, we use the value from the browser.
 *
 * @return array with ordered list of preferred languages from browser
 */
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
}

/**
 * @brief Returns the best language for which also a translation exists.
 *
 * This function takes the results from get_browser_language() and compares it
 * with the available translations and returns the best fitting language for 
 * which there exists a translation.
 *
 * If there is no match fall back to config['system']['language']
 *
 * @return Language code in 2-letter ISO 639-1 (en).
 */
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
	global $a;

	$a->langsave = $a->language;

	if($language === $a->language)
		return;

	if(isset($a->strings) && count($a->strings)) {
		$a->stringsave = $a->strings;
	}
	$a->strings = array();
	load_translation_table($language);
	$a->language = $language;
}

function pop_lang() {
	global $a;

	if($a->language === $a->langsave)
		return;

	if(isset($a->stringsave))
		$a->strings = $a->stringsave;
	else
		$a->strings = array();

	$a->language = $a->langsave;
}


// load string translation table for alternate language

function load_translation_table($lang, $install = false) {
	global $a;

	$a->strings = array();
	if(file_exists("view/$lang/strings.php")) {
		include("view/$lang/strings.php");
	}

	if(! $install) {
		$plugins = q("SELECT name FROM addon WHERE installed=1;");
		if ($plugins !== false) {
			foreach($plugins as $p) {
				$name = $p['name'];
				if(file_exists("addon/$name/lang/$lang/strings.php")) {
					include("addon/$name/lang/$lang/strings.php");
				}
			}
		}
	}

	// Allow individual strings to be over-ridden on this site
	// Either for the default language or for all languages

	if(file_exists("view/local-$lang/strings.php")) {
		include("view/local-$lang/strings.php");
	}

}

/**
 * @brief translate string if translation exists.
 *
 * @param s string that should get translated
 * @return translated string if exsists, otherwise s
 */
function t($s) {
	global $a;

	if(x($a->strings,$s)) {
		$t = $a->strings[$s];
		return is_array($t) ? $t[0] : $t;
	}
	return $s;
}


function tt($singular, $plural, $count){
	$a = get_app();

	if(x($a->strings,$singular)) {
		$t = $a->strings[$singular];
		$f = 'string_plural_select_' . str_replace('-', '_', $a->language);
		if(! function_exists($f))
			$f = 'string_plural_select_default';
		$k = $f($count);
		return is_array($t) ? $t[$k] : $t;
	}
	
	if ($count != 1){
		return $plural;
	} else {
		return $singular;
	}
}

// provide a fallback which will not collide with 
// a function defined in any language file 

function string_plural_select_default($n) {
	return ($n != 1);
}

/**
 * @brief Takes a string and tries to identify the language.
 *
 * It uses the pear library Text_LanguageDetect and it can identify 52 human languages.
 * It returns the identified languges and a confidence score for each.
 *
 * Strings need to have a min length config['system']['language_detect_min_length']
 * and you can influence the confidence that must be met before a result will get
 * returned through config['system']['language_detect_min_confidence'].
 *
 * @see http://pear.php.net/package/Text_LanguageDetect
 * @param s A string to examine
 * @return Language code in 2-letter ISO 639-1 (en, de, fr) format
 */
function detect_language($s) {
	require_once('Text/LanguageDetect.php');

	$min_length = get_config('system', 'language_detect_min_length');
	if($min_length === false)
		$min_length = LANGUAGE_DETECT_MIN_LENGTH;

	$min_confidence = get_config('system', 'language_detect_min_confidence');
	if($min_confidence === false)
		$min_confidence = LANGUAGE_DETECT_MIN_CONFIDENCE;

	// strip off bbcode
	$naked_body = preg_replace('/\[(.+?)\]/', '', $s);
	if(mb_strlen($naked_body) < intval($min_length)) {
		logger('detect language: string length less than ' . intval($min_length), LOGGER_DATA);
		return '';
	}

	$l = new Text_LanguageDetect;
	try {
		// return 2-letter ISO 639-1 (en) language code
		$l->setNameMode(2);
		$lng = $l->detectConfidence($naked_body);
		logger('detect language: ' . print_r($lng, true) . $naked_body, LOGGER_DATA);
	} catch (Text_LanguageDetect_Exception $e) {
		logger('detect language exception: ' . $e->getMessage(), LOGGER_DATA);
	}

	if((! $lng) || (! (x($lng,'language')))) {
		return '';
	}

	if($lng['confidence'] < (float) $min_confidence) {
		logger('detect language: confidence less than ' . (float) $min_confidence, LOGGER_DATA);
		return '';
	}

	return($lng['language']);
}

/**
 * @brief Returns the display name of a given language code.
 *
 * By default we use the localized language name. You can switch the result
 * to any language with the optional 2nd parameter $l.
 *
 * $s and $l can be in any format that PHP's Locale understands. We will mostly
 * use the 2-letter ISO 639-1 (en, de, fr) format.
 *
 * If nothing could be looked up it returns $s.
 *
 * @param $s Language code to look up
 * @param $l (optional) In which language to return the name
 * @return string with the language name, or $s if unrecognized
 */
function get_language_name($s, $l = null) {
	if($l === null)
		$l = $s;

	logger('get_language_name: for ' . $s . ' in ' . $l . ' returns: ' . Locale::getDisplayLanguage($s, $l), LOGGER_DEBUG);
	return Locale::getDisplayLanguage($s, $l);
}

