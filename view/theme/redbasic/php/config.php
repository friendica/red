<?php
/**
 * Theme settings
 */

function theme_content(&$a) {
	// Doesn't yet work for anyone other than the channel owner, and stupid mode isn't finished, so return both for now.
	if(!local_user()) { return;	}
	$font_size = get_pconfig(local_user(),'redbasic', 'font_size' );
	$line_height = get_pconfig(local_user(), 'redbasic', 'line_height' );
	$colour = get_pconfig(local_user(), 'redbasic', 'colour' );
	$shadow = get_pconfig(local_user(), 'redbasic', 'shadow' );
	$navcolour = get_pconfig(local_user(), 'redbasic', 'navcolour');
	$displaystyle = get_pconfig(local_user(), 'redbasic', 'displaystyle');
	$linkcolour = get_pconfig(local_user(), 'redbasic', 'linkcolour');
	$iconset = get_pconfig(local_user(), 'redbasic', 'iconset');
	$shiny = get_pconfig(local_user(), 'redbasic', 'shiny');
	$colour_scheme = get_pconfig(local_user(), 'redbasic', 'colour_scheme');
	$radius = get_pconfig(local_user(),'redbasic','radius');

	return redbasic_form($a, $font_size, $line_height, $colour, $shadow, $navcolour, $opaquenav, $displaystyle, $linkcolour, $iconset, $shiny, $colour_scheme,$radius);
}

function theme_post(&$a) {
	if(!local_user()) { return; }
	
	if (isset($_POST['redbasic-settings-submit'])) {
		set_pconfig(local_user(), 'redbasic', 'font_size', $_POST['redbasic_font_size']);
		set_pconfig(local_user(), 'redbasic', 'line_height', $_POST['redbasic_line_height']);
		set_pconfig(local_user(), 'redbasic', 'colour', $_POST['redbasic_colour']);	
		set_pconfig(local_user(), 'redbasic', 'shadow', $_POST['redbasic_shadow']);	
		set_pconfig(local_user(), 'redbasic', 'navcolour', $_POST['redbasic_navcolour']);
		set_pconfig(local_user(), 'redbasic', 'displaystyle', $_POST['redbasic_displaystyle']);
		set_pconfig(local_user(), 'redbasic', 'linkcolour', $_POST['redbasic_linkcolour']);
		set_pconfig(local_user(), 'redbasic', 'iconset', $_POST['redbasic_iconset']);
		set_pconfig(local_user(), 'redbasic', 'shiny', $_POST['redbasic_shiny']);
		set_pconfig(local_user(), 'redbasic', 'colour_scheme', $_POST['redbasic_colour_scheme']);
		set_pconfig(local_user(), 'redbasic', 'radius', $_POST['redbasic_radius']);
	}
}

// We probably don't want these if we're having global settings, but we'll comment out for now, just in case
//function theme_admin(&$a) {	$font_size = get_config('redbasic', 'font_size' );
//	$line_height = get_config('redbasic', 'line_height' );
//	$colour = get_config('redbasic', 'colour' );	
//	$shadow = get_config('redbasic', 'shadow' );	
//	$navcolour = get_config('redbasic', 'navcolour' );
//	$opaquenav = get_config('redbasic', 'opaquenav' );
//	$itemstyle = get_config('redbasic', 'itemstyle' );
//	$linkcolour = get_config('redbasic', 'linkcolour' );
//	$iconset = get_config('redbasic', 'iconset' );
//	$shiny = get_config('redbasic', 'shiny' );
//	
//	return redbasic_form($a, $font_size, $line_height, $colour, $shadow, $navcolour, $opaquenav, $itemstyle, $linkcolour, $iconset, $shiny);
//}

//function theme_admin_post(&$a) {
//	if (isset($_POST['redbasic-settings-submit'])) {
//		set_config('redbasic', 'font_size', $_POST['redbasic_font_size']);
//		set_config('redbasic', 'line_height', $_POST['redbasic_line_height']);
//		set_config('redbasic', 'colour', $_POST['redbasic_colour']);
//		set_config('redbasic', 'shadow', $_POST['redbasic_shadow']);
//		set_config('redbasic', 'navcolour', $_POST['redbasic_navcolour']);
//		set_config('redbasic', 'opaquenav', $_POST['redbasic_opaquenav']);
//		set_config('redbasic', 'itemstyle', $_POST['redbasic_itemstyle']);
//		set_config('redbasic', 'linkcolour', $_POST['redbasic_linkcolour']);
//		set_config('redbasic', 'iconset', $_POST['redbasic_iconset']);
//		set_config('redbasic', 'shiny', $_POST['redbasic_shiny']);
//	}
//}

// These aren't all used yet, but they're not bloat - we'll use drop down menus in idiot mode.
function redbasic_form(&$a, $font_size, $line_height, $colour, $shadow, $navcolour, $opaquenav, $displaystyle, $linkcolour, $iconset, $shiny, $colour_scheme,$radius) {
	$line_heights = array(
		"1.3" => "1.3",
		"---" => "---",
		"1.6" => "1.6",				
		"1.5" => "1.5",		
		"1.4" => "1.4",
		"1.2" => "1.2",
		"1.1" => "1.1",
	);	
	$font_sizes = array(
		'12' => '12',
		'14' => '14',
		"---" => "---",
		"16" => "16",		
		"15" => "15",
		'13.5' => '13.5',
		'13' => '13',		
		'12.5' => '12.5',
		'12' => '12',
	);
	$colours = array(
		'light' => 'light',		
		'dark' => 'dark',						
	);

	$colour_schemes = array(
		'redbasic' => 'redbasic',		
		'fancyred' => 'fancyred',						
		'dark' => 'dark',	
	);
	$shadows = array(
		  'true' => 'Yes',
		  'false' => 'No',
	);

	$navcolours = array (
		  'red' => 'red',
		  'black' => 'black',	
	);
	
	$displaystyles = array (
		    'fancy' => 'fancy',
		    'classic' => 'classic',
	);
	
	$linkcolours = array (
		    'blue' => 'blue',
		    'red' => 'red',
	);
	
	$iconsets = array (
		    'default' => 'default',
	);
	
	$shinys = array (
		    'none' => 'none',
		    'opaque' => 'opaque',
	);
	if(feature_enabled(local_user(),'expert')) {
	  $t = get_markup_template('theme_settings.tpl');
	  $o .= replace_macros($t, array(
		'$submit' => t('Submit'),
		'$baseurl' => $a->get_baseurl(),
		'$title' => t("Theme settings"),
		'$font_size' => array('redbasic_font_size', t('Set font-size for posts and comments'), $font_size, '', $font_sizes),
		'$line_height' => array('redbasic_line_height', t('Set line-height for posts and comments'), $line_height, '', $line_heights),
		'$colour' => array('redbasic_colour', t('Set colour scheme'), $colour, '', $colours),	
		'$shadow' => array('redbasic_shadow', t('Draw shadows'), $shadow, '', $shadows),
		'$navcolour' => array('redbasic_navcolour', t('Navigation bar colour'), $navcolour, '', $navcolours),
		'$displaystyle' => array('redbasic_displaystyle', t('Display style'), $displaystyle, '', $displaystyles),
		'$linkcolour' => array('redbasic_linkcolour', t('Display colour of links - hex value, do not include the #'), $linkcolour, '', $linkcolours),
		'$iconset' => array('redbasic_iconset', t('Icons'), $iconset, '', $iconsets),
		'$shiny' => array('redbasic_shiny', t('Shiny style'), $shiny, '', $shinys),
		'$radius' => array('redbasic_radius', t('Corner radius'), $radius, t('0-99 default: 5')),
	  ));}
	 
	 if(! feature_enabled(local_user(),'expert')) {
	    $t = get_markup_template('basic_theme_settings.tpl');
	    $o .= replace_macros($t, array(
		'$submit' => t('Submit'),
		'$baseurl' => $a->get_baseurl(),
		'$title' => t("Theme settings"),
		'$font_size' => array('redbasic_font_size', t('Set font-size for posts and comments'), $font_size, '', $font_sizes),
		'$line_height' => array('redbasic_line_height', t('Set line-height for posts and comments'), $line_height, '', $line_heights),
		'$colour_scheme' => array('redbasic_colour_scheme', t('Set colour scheme'), $colour_scheme, '', $colour_schemes),	
	 ));}
	 
	return $o;
}

