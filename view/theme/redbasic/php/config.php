<?php

function theme_content(&$a) {
	if(!local_user()) { return;}

	$arr = array();

	$arr['schema'] = get_pconfig(local_user(),'redbasic', 'schema' );
	$arr['nav_colour'] = get_pconfig(local_user(),'redbasic', 'nav_colour' );
	$arr['link_colour'] = get_pconfig(local_user(),'redbasic', 'link_colour' );
	$arr['banner_colour'] = get_pconfig(local_user(),'redbasic', 'banner_colour' );
	$arr['bgcolour'] = get_pconfig(local_user(),'redbasic', 'background_colour' );
	$arr['background_image'] = get_pconfig(local_user(),'redbasic', 'background_image' );
	$arr['item_colour'] = get_pconfig(local_user(),'redbasic', 'item_colour' );
	$arr['item_opacity'] = get_pconfig(local_user(),'redbasic', 'item_opacity' );
	$arr['toolicon_colour'] = get_pconfig(local_user(),'redbasic','toolicon_colour');
	$arr['toolicon_activecolour'] = get_pconfig(local_user(),'redbasic','toolicon_activecolour');
	$arr['font_size'] = get_pconfig(local_user(),'redbasic', 'font_size' );
	$arr['body_font_size'] = get_pconfig(local_user(),'redbasic', 'body_font_size' );
	$arr['font_colour'] = get_pconfig(local_user(),'redbasic', 'font_colour' );
	$arr['radius'] = get_pconfig(local_user(),'redbasic', 'radius' );
	$arr['shadow'] = get_pconfig(local_user(),'redbasic', 'photo_shadow' );
	$arr['converse_width']=get_pconfig(local_user(),"redbasic","converse_width");
	$arr['nav_min_opacity']=get_pconfig(local_user(),"redbasic","nav_min_opacity");
	$arr['top_photo']=get_pconfig(local_user(),"redbasic","top_photo");
	$arr['reply_photo']=get_pconfig(local_user(),"redbasic","reply_photo");
	$arr['sloppy_photos']=get_pconfig(local_user(),"redbasic","sloppy_photos");
	return redbasic_form($a, $arr);
}

function theme_post(&$a) {
	if(!local_user()) { return;}

	if (isset($_POST['redbasic-settings-submit'])) {
		set_pconfig(local_user(), 'redbasic', 'schema', $_POST['redbasic_schema']);
		set_pconfig(local_user(), 'redbasic', 'nav_colour', $_POST['redbasic_nav_colour']);
		set_pconfig(local_user(), 'redbasic', 'link_colour', $_POST['redbasic_link_colour']);
		set_pconfig(local_user(), 'redbasic', 'background_colour', $_POST['redbasic_background_colour']);
		set_pconfig(local_user(), 'redbasic', 'banner_colour', $_POST['redbasic_banner_colour']);
		set_pconfig(local_user(), 'redbasic', 'background_image', $_POST['redbasic_background_image']);
		set_pconfig(local_user(), 'redbasic', 'item_colour', $_POST['redbasic_item_colour']);
		set_pconfig(local_user(), 'redbasic', 'item_opacity', $_POST['redbasic_item_opacity']);
		set_pconfig(local_user(), 'redbasic', 'toolicon_colour', $_POST['redbasic_toolicon_colour']);
		set_pconfig(local_user(), 'redbasic', 'toolicon_activecolour', $_POST['redbasic_toolicon_activecolour']);
		set_pconfig(local_user(), 'redbasic', 'font_size', $_POST['redbasic_font_size']);
		set_pconfig(local_user(), 'redbasic', 'body_font_size', $_POST['redbasic_body_font_size']);
		set_pconfig(local_user(), 'redbasic', 'font_colour', $_POST['redbasic_font_colour']);
		set_pconfig(local_user(), 'redbasic', 'radius', $_POST['redbasic_radius']);
		set_pconfig(local_user(), 'redbasic', 'photo_shadow', $_POST['redbasic_shadow']);
		set_pconfig(local_user(), 'redbasic', 'converse_width', $_POST['redbasic_converse_width']);
		set_pconfig(local_user(), 'redbasic', 'nav_min_opacity', $_POST['redbasic_nav_min_opacity']);
		set_pconfig(local_user(), 'redbasic', 'top_photo', $_POST['redbasic_top_photo']);
		set_pconfig(local_user(), 'redbasic', 'reply_photo', $_POST['redbasic_reply_photo']);
		set_pconfig(local_user(), 'redbasic', 'sloppy_photos', $_POST['redbasic_sloppy_photos']);
	}
}



function redbasic_form(&$a, $arr) {


	$scheme_choices = array();
	$scheme_choices["---"] = t("Default");
	$files = glob('view/theme/redbasic/schema/*.php');
	if($files) {
		foreach($files as $file) {
			$f = basename($file, ".php");
			$scheme_name = $f;
			$scheme_choices[$f] = $scheme_name;
		}
	}
		
		
		$nav_colours = array (
		  '' => t('Scheme Default'),
		'red' => 'red',	
		'pink' => 'pink',
		'green' => 'green',
		'blue' => 'blue',
		'purple' => 'purple',
		'black' => 'black',
		'orange' => 'orange',
		'brown' => 'brown',
		'grey' => 'grey',
		'gold' => 'gold',
		'silver' => t('silver'),	
		);

if(feature_enabled(local_user(),'expert')) 
				$expert = 1;
					
	  $t = get_markup_template('theme_settings.tpl');
	  $o .= replace_macros($t, array(
		'$submit' => t('Submit'),
		'$baseurl' => $a->get_baseurl(),
		'$expert' => $expert,
		'$title' => t("Theme settings"),
		'$schema' => array('redbasic_schema', t('Set scheme'), $arr['schema'], '', $scheme_choices),
		'$nav_colour' => array('redbasic_nav_colour', t('Navigation bar colour'), $arr['nav_colour'], '', $nav_colours),
		'$link_colour' => array('redbasic_link_colour', t('link colour'), $arr['link_colour'], '', $link_colours),
		'$banner_colour' => array('redbasic_banner_colour', t('Set font-colour for banner'), $arr['banner_colour']),
		'$bgcolour' => array('redbasic_background_colour', t('Set the background colour'), $arr['bgcolour']),
		'$background_image' => array('redbasic_background_image', t('Set the background image'), $arr['background_image']),
		'$item_colour' => array('redbasic_item_colour', t('Set the background colour of items'), $arr['item_colour']),
		'$item_opacity' => array('redbasic_item_opacity', t('Set the opacity of items'), $arr['item_opacity']),
		'$toolicon_colour' => array('redbasic_toolicon_colour',t('Set the basic colour for item icons'),$arr['toolicon_colour']),
		'$toolicon_activecolour' => array('redbasic_toolicon_activecolour',t('Set the hover colour for item icons'),$arr['toolicon_activecolour']),
		'$body_font_size' => array('redbasic_body_font_size', t('Set font-size for the entire application'), $arr['body_font_size']),
		'$font_size' => array('redbasic_font_size', t('Set font-size for posts and comments'), $arr['font_size']),
		'$font_colour' => array('redbasic_font_colour', t('Set font-colour for posts and comments'), $arr['font_colour']),
		'$radius' => array('redbasic_radius', t('Set radius of corners'), $arr['radius']),
		'$shadow' => array('redbasic_shadow', t('Set shadow depth of photos'), $arr['shadow']),
		'$converse_width' => array('redbasic_converse_width',t('Set maximum width of conversation regions'),$arr['converse_width']),
		'$nav_min_opacity' => array('redbasic_nav_min_opacity',t('Set minimum opacity of nav bar - to hide it'),$arr['nav_min_opacity']),
		'$top_photo' => array('redbasic_top_photo', t('Set size of conversation author photo'), $arr['top_photo']),
		'$reply_photo' => array('redbasic_reply_photo', t('Set size of followup author photos'), $arr['reply_photo']),
		'$sloppy_photos' => array('redbasic_sloppy_photos',t('Sloppy photo albums'),$arr['sloppy_photos'],t('Are you a clean desk or a messy desk person?')),
		));

	return $o;
}
