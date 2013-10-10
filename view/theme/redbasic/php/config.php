<?php

function theme_content(&$a) {
	if(!local_user()) { return;}

	$schema = get_pconfig(local_user(),'redbasic', 'schema' );
	$nav_colour = get_pconfig(local_user(),'redbasic', 'nav_colour' );
	$bgcolour = get_pconfig(local_user(),'redbasic', 'bgcolour' );
	$background_image = get_pconfig(local_user(),'redbasic', 'background_image' );
	$item_colour = get_pconfig(local_user(),'redbasic', 'item_colour' );
	$item_opacity = get_pconfig(local_user(),'redbasic', 'item_opacity' );
	$font_size = get_pconfig(local_user(),'redbasic', 'font_size' );
	$font_colour = get_pconfig(local_user(),'redbasic', 'font_colour' );
	$radius = get_pconfig(local_user(),'redbasic', 'radius' );
	$shadow = get_pconfig(local_user(),'redbasic', 'photo_shadow' );
	return redbasic_form($a, $schema, $nav_colour, $bgcolour, $background_image, $item_colour, $item_opacity, 
		$font_size, $font_colour, $radius, $shadow);
}

function theme_post(&$a) {
	if(!local_user()) { return;}

	if (isset($_POST['redbasic-settings-submit'])) {
		set_pconfig(local_user(), 'redbasic', 'schema', $_POST['redbasic_schema']);
		set_pconfig(local_user(), 'redbasic', 'nav_colour', $_POST['redbasic_nav_colour']);
		set_pconfig(local_user(), 'redbasic', 'background_colour', $_POST['redbasic_background_colour']);
		set_pconfig(local_user(), 'redbasic', 'background_image', $_POST['redbasic_background_image']);
		set_pconfig(local_user(), 'redbasic', 'item_colour', $_POST['redbasic_item_colour']);
		set_pconfig(local_user(), 'redbasic', 'item_opacity', $_POST['redbasic_item_opacity']);
		set_pconfig(local_user(), 'redbasic', 'font_size', $_POST['redbasic_font_size']);
		set_pconfig(local_user(), 'redbasic', 'font_colour', $_POST['redbasic_font_colour']);
		set_pconfig(local_user(), 'redbasic', 'radius', $_POST['redbasic_radius']);
		set_pconfig(local_user(), 'redbasic', 'photo_shadow', $_POST['redbasic_shadow']);
	}
}

function redbasic_form(&$a, $schema, $nav_colour, $bgcolour, $background_image, $item_colour, $item_opacity, 
		$font_size, $font_colour, $radius, $shadow) {

	$scheme_choices = array();
	$scheme_choices["---"] = t("Default");
	$files = glob('view/theme/' . current_theme() . '/schema/*');
	if($files) {
		foreach($files as $file) {
			$f = basename($file, ".php");
			$scheme_name = $f;
			$scheme_choices[$f] = $scheme_name;
		}
	}
		
		
		$nav_colours = array (
		  '' => 'Scheme Default',
		  'red' => 'red',
		  'black' => 'black',	
		  'silver' => 'silver',	
		);

if(feature_enabled(local_user(),'expert')) 
				$expert = 1;
					
	  $t = get_markup_template('theme_settings.tpl');
	  $o .= replace_macros($t, array(
		'$submit' => t('Submit'),
		'$baseurl' => $a->get_baseurl(),
		'$expert' => $expert,
		'$title' => t("Theme settings"),
		'$schema' => array('redbasic_schema', t('Set scheme'), $schema, '', $scheme_choices),
		'$nav_colour' => array('redbasic_nav_colour', t('Navigation bar colour'), $nav_colour, '', $nav_colours),
		'$bgcolour' => array('redbasic_background_colour', t('Set the background colour'), $bgcolour),
		'$background_image' => array('redbasic_background_image', t('Set the background image'), $background_image),
		'$item_colour' => array('redbasic_item_colour', t('Set the background colour of items'), $item_colour),
		'$item_opacity' => array('redbasic_item_opacity', t('Set the opacity of items'), $item_opacity),
		'$font_size' => array('redbasic_font_size', t('Set font-size for posts and comments'), $font_size),
		'$font_colour' => array('redbasic_font_colour', t('Set font-colour for posts and comments'), $font_colour),
		'$radius' => array('redbasic_radius', t('Set radius of corners'), $radius),
		'$shadow' => array('redbasic_shadow', t('Set shadow depth of photos'), $shadow),
		));

	return $o;
}
