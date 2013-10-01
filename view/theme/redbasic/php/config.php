<?php

function theme_content(&$a) {
	if(!local_user()) { return;}

	$nav_colour = get_pconfig(local_user(),'redbasic', 'nav_colour' );
	$background_colour = get_pconfig(local_user(),'redbasic', 'background_colour' );
	$background_image = get_pconfig(local_user(),'redbasic', 'background_image' );
	$item_colour = get_pconfig(local_user(),'redbasic', 'item_colour' );
	$item_opacity = get_pconfig(local_user(),'redbasic', 'item_opacity' );
	$font_size = get_pconfig(local_user(),'redbasic', 'font_size' );
	$font_colour = get_pconfig(local_user(),'redbasic', 'font_colour' );
	$radius = get_pconfig(local_user(),'redbasic', 'radius' );
	return redbasic_form($a, $nav_colour, $background_colour, $background_image, $item_colour, $item_opacity, 
		$font_size, $font_colour, $radius);
}

function theme_post(&$a) {
	if(!local_user()) { return;}

	if (isset($_POST['redbasic-settings-submit'])) {
		set_pconfig(local_user(), 'redbasic', 'nav_colour', $_POST['redbasic_nav_colour']);
		set_pconfig(local_user(), 'redbasic', 'background_colour', $_POST['redbasic_background_colour']);
		set_pconfig(local_user(), 'redbasic', 'background_image', $_POST['redbasic_background_image']);
		set_pconfig(local_user(), 'redbasic', 'item_colour', $_POST['redbasic_item_colour']);
		set_pconfig(local_user(), 'redbasic', 'item_opacity', $_POST['redbasic_item_opacity']);
		set_pconfig(local_user(), 'redbasic', 'font_size', $_POST['redbasic_font_size']);
		set_pconfig(local_user(), 'redbasic', 'font_colour', $_POST['redbasic_font_colour']);
		set_pconfig(local_user(), 'redbasic', 'radius', $_POST['redbasic_radius']);
	}
}

function redbasic_form(&$a, $nav_colour, $background_colour, $background_image, $item_colour, $item_opacity, 
		$font_size, $font_colour, $radius) {

		$nav_colours = array (
		  'red' => 'red',
		  'black' => 'black',	
		);

	  $t = get_markup_template('theme_settings.tpl');
	  $o .= replace_macros($t, array(
		'$submit' => t('Submit'),
		'$baseurl' => $a->get_baseurl(),
		'$title' => t("Theme settings"),
		'$nav_colour' => array('redbasic_nav_colour', t('Navigation bar colour'), $nav_colour, '', $nav_colours),
		'$background_colour' => array('redbasic_background_colour', t('Set the background colour'), $background_colour),
		'$background_image' => array('redbasic_background_image', t('Set the background image'), $background_image),
		'$item_colour' => array('redbasic_item_colour', t('Set the background colour of items'), $item_colour),
		'$item_opacity' => array('redbasic_item_opacity', t('Set the opacity of items'), $item_opacity),
		'$font_size' => array('redbasic_font_size', t('Set font-size for posts and comments'), $font_size),
		'$font_colour' => array('redbasic_font_colour', t('Set font-colour for posts and comments'), $font_colour),
		'$radius' => array('redbasic_radius', t('Set radius of corners'), $radius),
		));

	return $o;
}
