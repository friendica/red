<?php
/**
 * @file include/language.php
 *
 * @brief Translation support.
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
	$lang_parse = array();

	if (x($_SERVER, 'HTTP_ACCEPT_LANGUAGE')) {
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
			$lang = strtolower($lang);
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

/**
 * @brief Load string translation table for alternate language.
 *
 * @param string $lang language code in 2-letter ISO 639-1 (en, de, fr) format
 * @param boolean $install (optional) default false
 */
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
 * @brief Translate string if translation exists.
 *
 * @param string $s string that should get translated
 * @param string $ctx (optional) context to appear in po file
 * @return translated string if exists, otherwise return $s
 *
 */
function t($s, $ctx = '') {
	global $a;

	$cs = $ctx ? '__ctx:' . $ctx . '__ ' . $s : $s;
	if (x($a->strings, $cs)) {
		$t = $a->strings[$cs];

		return is_array($t) ? $t[0] : $t;
	}

	return $s;
}

/**
 * @brief
 *
 * @param string $singular
 * @param string $plural
 * @param int $count
 * @param string $ctx
 * @return string
 */
function tt($singular, $plural, $count, $ctx = ''){
	$a = get_app();

	$cs = $ctx ? "__ctx:" . $ctx . "__ " . $singular : $singular;
	if (x($a->strings,$cs)) {
		$t = $a->strings[$cs];
		$f = 'string_plural_select_' . str_replace('-', '_', $a->language);
		if (! function_exists($f))
			$f = 'string_plural_select_default';

		$k = $f($count);

		return is_array($t) ? $t[$k] : $t;
	}

	if ($count != 1) {
		return $plural;
	} else {
		return $singular;
	}
}

/**
 * @brief Provide a fallback which will not collide with a function defined in
 * any language file.
 *
 * @param int $n
 * @return boolean
 */
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
 * @param string $s A string to examine
 * @return Language code in 2-letter ISO 639-1 (en, de, fr) format
 */
function detect_language($s) {
	require_once('Text/LanguageDetect.php');

	$min_length = get_config('system', 'language_detect_min_length');
	if ($min_length === false)
		$min_length = LANGUAGE_DETECT_MIN_LENGTH;

	$min_confidence = get_config('system', 'language_detect_min_confidence');
	if ($min_confidence === false)
		$min_confidence = LANGUAGE_DETECT_MIN_CONFIDENCE;

	// embedded apps have long base64 strings which will trip up the detector.
	$naked_body = preg_replace('/\[app\](.*?)\[\/app\]/','',$s);
	// strip off bbcode
	$naked_body = preg_replace('/\[(.+?)\]/', '', $naked_body);
	if (mb_strlen($naked_body) < intval($min_length)) {
		logger('string length less than ' . intval($min_length), LOGGER_DATA);
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

	if ((! $lng) || (! (x($lng,'language')))) {
		return '';
	}

	if ($lng['confidence'] < (float) $min_confidence) {
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
 * $s and $l should be in 2-letter ISO 639-1 format.
 *
 * If nothing could be looked up it returns $s.
 *
 * @param string $s Language code to look up
 * @param string $l (optional) In which language to return the name
 * @return string with the language name, or $s if unrecognized
 *
 * @todo include CommerceGuys\Intl through composer like SabreDAV.
 */
require_once(__DIR__ . '/../library/intl/vendor/autoload.php');
use CommerceGuys\Intl\Language\LanguageRepository;
function get_language_name($s, $l = null) {
	// get() expects the second part to be in upper case
	if (strpos($s, '-') !== false) $s = substr($s, 0, 2) . strtoupper(substr($s, 2));
	if ($l !== null && strpos($l, '-') !== false) $l = substr($l, 0, 2) . strtoupper(substr($l, 2));

	$languageRepository = new LanguageRepository;

	// Sometimes intl doesn't like the second part at all ...
	try {
		$language = $languageRepository->get($s, $l);
	} catch(CommerceGuys\Intl\Exception\UnknownLanguageException $e) {
		$s = substr($s, 0, 2);
		if($l !== null) $l = substr($s, 0, 2);
		try {
			$language = $languageRepository->get($s, $l);
		} catch (CommerceGuys\Intl\Exception\UnknownLanguageException $e) {
			return $s; // Give up
		}
	}

	return $language->getName();
}
