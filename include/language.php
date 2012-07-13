<?php


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
