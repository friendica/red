<?php
/**
 * translation support
 */

// load string translation table for alternate language

if(! function_exists('load_translation_table')) {
function load_translation_table($lang) {
	global $a;

	if(file_exists("view/$lang/strings.php"))
		include("view/$lang/strings.php");
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
	
	$a = get_app();

	if(x($a->strings,$singular)) {
		$t = $a->strings[$singular];
		$k = string_plural_select($count);
		return is_array($t)?$t[$k]:$t;
	}
	
	if ($count!=1){
		return $plural;
	} else {
		return $singular;
	}
}}