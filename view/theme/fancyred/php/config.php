<?php
/**
 * Theme settings
 */

function theme_content(&$a) {
	if(!local_user()) { return;	}

	$font_size = get_pconfig(local_user(),'fancyred', 'font_size' );
	$line_height = get_pconfig(local_user(), 'fancyred', 'line_height' );
	$colour = get_pconfig(local_user(), 'fancyred', 'colour' );
	
	return fancyred_form($a, $font_size, $line_height, $colour);
}

function theme_post(&$a) {
	if(!local_user()) { return; }
	
	if (isset($_POST['fancyred-settings-submit'])) {
		set_pconfig(local_user(), 'fancyred', 'font_size', $_POST['fancyred_font_size']);
		set_pconfig(local_user(), 'fancyred', 'line_height', $_POST['fancyred_line_height']);
		set_pconfig(local_user(), 'fancyred', 'colour', $_POST['fancyred_colour']);	
	}
}

function theme_admin(&$a) {
	$font_size = get_config('fancyred', 'font_size' );
	$line_height = get_config('fancyred', 'line_height' );
	$colour = get_config('fancyred', 'colour' );	
	
	return fancyred_form($a, $font_size, $line_height, $colour);
}

function theme_admin_post(&$a) {
	if (isset($_POST['fancyred-settings-submit'])) {
		set_config('fancyred', 'font_size', $_POST['fancyred_font_size']);
		set_config('fancyred', 'line_height', $_POST['fancyred_line_height']);
		set_config('fancyred', 'colour', $_POST['fancyred_colour']);
	}
}

function fancyred_form(&$a, $font_size, $line_height, $colour) {
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

	$t = file_get_contents( dirname(__file__). "/theme_settings.tpl" );
	$o .= replace_macros($t, array(
		'$submit' => t('Submit'),
		'$baseurl' => $a->get_baseurl(),
		'$title' => t("Theme settings"),
		'$font_size' => array('fancyred_font_size', t('Set font-size for posts and comments'), $font_size, '', $font_sizes),
		'$line_height' => array('fancyred_line_height', t('Set line-height for posts and comments'), $line_height, '', $line_heights),
		'$colour' => array('fancyred_colour', t('Set colour scheme'), $colour, '', $colours),	
	));

	return $o;
}

