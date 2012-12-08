<?php
/**
 * Theme settings
 */

function theme_content(&$a) {
	if(!local_user()) { return;	}

	$font_size = get_pconfig(local_user(),'redbasic', 'font_size' );
	$line_height = get_pconfig(local_user(), 'redbasic', 'line_height' );
	$colour = get_pconfig(local_user(), 'redbasic', 'colour' );
	
	return redbasic_form($a, $font_size, $line_height, $colour);
}

function theme_post(&$a) {
	if(!local_user()) { return; }
	
	if (isset($_POST['redbasic-settings-submit'])) {
		set_pconfig(local_user(), 'redbasic', 'font_size', $_POST['redbasic_font_size']);
		set_pconfig(local_user(), 'redbasic', 'line_height', $_POST['redbasic_line_height']);
		set_pconfig(local_user(), 'redbasic', 'colour', $_POST['redbasic_colour']);	
	}
}

function theme_admin(&$a) {
	$font_size = get_config('redbasic', 'font_size' );
	$line_height = get_config('redbasic', 'line_height' );
	$colour = get_config('redbasic', 'colour' );	
	
	return redbasic_form($a, $font_size, $line_height, $colour);
}

function theme_admin_post(&$a) {
	if (isset($_POST['redbasic-settings-submit'])) {
		set_config('redbasic', 'font_size', $_POST['redbasic_font_size']);
		set_config('redbasic', 'line_height', $_POST['redbasic_line_height']);
		set_config('redbasic', 'colour', $_POST['redbasic_colour']);
	}
}

function redbasic_form(&$a, $font_size, $line_height, $colour) {
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
		'$font_size' => array('redbasic_font_size', t('Set font-size for posts and comments'), $font_size, '', $font_sizes),
		'$line_height' => array('redbasic_line_height', t('Set line-height for posts and comments'), $line_height, '', $line_heights),
		'$colour' => array('redbasic_colour', t('Set colour scheme'), $colour, '', $colours),	
	));

	return $o;
}

