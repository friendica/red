<?php
/**
 * Theme settings
 */



function theme_content(&$a){
	if(!local_user())
		return;		
	
	$font_size = get_pconfig(local_user(), 'diabook', 'font_size' );
	$line_height = get_pconfig(local_user(), 'diabook', 'line_height' );
	$resolution = get_pconfig(local_user(), 'diabook', 'resolution' );
	$color = get_pconfig(local_user(), 'diabook', 'color' );
	$TSearchTerm = get_pconfig(local_user(), 'diabook', 'TSearchTerm' );
	$ELZoom = get_pconfig(local_user(), 'diabook', 'ELZoom' );
	$ELPosX = get_pconfig(local_user(), 'diabook', 'ELPosX' );
	$ELPosY = get_pconfig(local_user(), 'diabook', 'ELPosY' );
	
	return diabook_form($a,$font_size, $line_height, $resolution, $color, $TSearchTerm, $ELZoom, $ELPosX, $ELPosY);
}

function theme_post(&$a){
	if(! local_user())
		return;
	
	if (isset($_POST['diabook-settings-submit'])){
		set_pconfig(local_user(), 'diabook', 'font_size', $_POST['diabook_font_size']);
		set_pconfig(local_user(), 'diabook', 'line_height', $_POST['diabook_line_height']);
		set_pconfig(local_user(), 'diabook', 'resolution', $_POST['diabook_resolution']);
		set_pconfig(local_user(), 'diabook', 'color', $_POST['diabook_color']);	
		set_pconfig(local_user(), 'diabook', 'TSearchTerm', $_POST['diabook_TSearchTerm']);	
		set_pconfig(local_user(), 'diabook', 'ELZoom', $_POST['diabook_ELZoom']);	
		set_pconfig(local_user(), 'diabook', 'ELPosX', $_POST['diabook_ELPosX']);	
		set_pconfig(local_user(), 'diabook', 'ELPosY', $_POST['diabook_ELPosY']);	
	}
}


function theme_admin(&$a){
	$font_size = get_config('diabook', 'font_size' );
	$line_height = get_config('diabook', 'line_height' );
	$resolution = get_config('diabook', 'resolution' );
	$color = get_config('diabook', 'color' );	
	$TSearchTerm = get_config('diabook', 'TSearchTerm' );	
	$ELZoom = get_config('diabook', 'ELZoom' );
	$ELPosX = get_config('diabook', 'ELPosX' );
	$ELPosY = get_config('diabook', 'ELPosY' );
	
	return diabook_form($a,$font_size, $line_height, $resolution, $color, $TSearchTerm, $ELZoom, $ELPosX, $ELPosY);
}

function theme_admin_post(&$a){
	if (isset($_POST['diabook-settings-submit'])){
		set_config('diabook', 'font_size', $_POST['diabook_font_size']);
		set_config('diabook', 'line_height', $_POST['diabook_line_height']);
		set_config('diabook', 'resolution', $_POST['diabook_resolution']);
		set_config('diabook', 'color', $_POST['diabook_color']);
		set_config('diabook', 'TSearchTerm', $_POST['diabook_TSearchTerm']);
		set_config('diabook', 'ELZoom', $_POST['diabook_ELZoom']);
		set_config('diabook', 'ELPosX', $_POST['diabook_ELPosX']);
		set_config('diabook', 'ELPosY', $_POST['diabook_ELPosY']);
	}
}


function diabook_form(&$a, $font_size, $line_height, $resolution, $color, $TSearchTerm, $ELZoom, $ELPosX, $ELPosY){
	$line_heights = array(
		"1.3"=>"1.3",
		"---"=>"---",
		"1.6"=>"1.6",				
		"1.5"=>"1.5",		
		"1.4"=>"1.4",
		"1.2"=>"1.2",
		"1.1"=>"1.1",
	);
	
	$font_sizes = array(
		'14'=>'14',
		"---"=>"---",
		"16"=>"16",		
		"15"=>"15",
		'13.5'=>'13.5',
		'13'=>'13',		
		'12.5'=>'12.5',
		'12'=>'12',
		);
	$resolutions = array(
		'normal'=>'normal',
		'wide'=>'wide',		
		);
	$colors = array(
		'diabook'=>'diabook',
		'aerith'=>'aerith',		
		'blue'=>'blue',		
		'green'=>'green',
		'pink'=>'pink',	
		'red'=>'red',
		'dark'=>'dark',						
		);
	
	
	
	$t = file_get_contents( dirname(__file__). "/theme_settings.tpl" );
	$o .= replace_macros($t, array(
		'$submit' => t('Submit'),
		'$baseurl' => $a->get_baseurl(),
		'$title' => t("Theme settings"),
		'$font_size' => array('diabook_font_size', t('Set font-size for posts and comments'), $font_size, '', $font_sizes),
		'$line_height' => array('diabook_line_height', t('Set line-height for posts and comments'), $line_height, '', $line_heights),
		'$resolution' => array('diabook_resolution', t('Set resolution for middle column'), $resolution, '', $resolutions),
		'$color' => array('diabook_color', t('Set color scheme'), $color, '', $colors),	
		'$TSearchTerm' => array('diabook_TSearchTerm', t('Set twitter search term'), $TSearchTerm, '', $TSearchTerm),	
		'$ELZoom' => array('diabook_ELZoom', t('Set zoomfactor for Earth Layer'), $ELZoom, '', $ELZoom),	
		'$ELPosX' => array('diabook_ELPosX', t('Set longitude (X) for Earth Layer'), $ELPosX, '', $ELPosX),	
		'$ELPosY' => array('diabook_ELPosY', t('Set latitude (Y) for Earth Layer'), $ELPosY, '', $ELPosY),	
	));
	return $o;
}
