<?php

function theme_content(&$a) {
	if(!local_channel()) { return;}

	$arr = array();

	$arr['schema'] = get_pconfig(local_channel(),'redbasic', 'schema' );
	$arr['narrow_navbar'] = get_pconfig(local_channel(),'redbasic', 'narrow_navbar' );
	$arr['nav_bg'] = get_pconfig(local_channel(),'redbasic', 'nav_bg' );
	$arr['nav_gradient_top'] = get_pconfig(local_channel(),'redbasic', 'nav_gradient_top' );
	$arr['nav_gradient_bottom'] = get_pconfig(local_channel(),'redbasic', 'nav_gradient_bottom' );
	$arr['nav_active_gradient_top'] = get_pconfig(local_channel(),'redbasic', 'nav_active_gradient_top' );
	$arr['nav_active_gradient_bottom'] = get_pconfig(local_channel(),'redbasic', 'nav_active_gradient_bottom' );
	$arr['nav_bd'] = get_pconfig(local_channel(),'redbasic', 'nav_bd' );
	$arr['nav_icon_colour'] = get_pconfig(local_channel(),'redbasic', 'nav_icon_colour' );
	$arr['nav_active_icon_colour'] = get_pconfig(local_channel(),'redbasic', 'nav_active_icon_colour' );
	$arr['link_colour'] = get_pconfig(local_channel(),'redbasic', 'link_colour' );
	$arr['banner_colour'] = get_pconfig(local_channel(),'redbasic', 'banner_colour' );
	$arr['bgcolour'] = get_pconfig(local_channel(),'redbasic', 'background_colour' );
	$arr['background_image'] = get_pconfig(local_channel(),'redbasic', 'background_image' );
	$arr['item_colour'] = get_pconfig(local_channel(),'redbasic', 'item_colour' );
	$arr['comment_item_colour'] = get_pconfig(local_channel(),'redbasic', 'comment_item_colour' );
	$arr['comment_border_colour'] = get_pconfig(local_channel(),'redbasic', 'comment_border_colour' );
	$arr['comment_indent'] = get_pconfig(local_channel(),'redbasic', 'comment_indent' );
	$arr['toolicon_colour'] = get_pconfig(local_channel(),'redbasic','toolicon_colour');
	$arr['toolicon_activecolour'] = get_pconfig(local_channel(),'redbasic','toolicon_activecolour');
	$arr['font_size'] = get_pconfig(local_channel(),'redbasic', 'font_size' );
	$arr['body_font_size'] = get_pconfig(local_channel(),'redbasic', 'body_font_size' );
	$arr['font_colour'] = get_pconfig(local_channel(),'redbasic', 'font_colour' );
	$arr['radius'] = get_pconfig(local_channel(),'redbasic', 'radius' );
	$arr['shadow'] = get_pconfig(local_channel(),'redbasic', 'photo_shadow' );
	$arr['converse_width']=get_pconfig(local_channel(),"redbasic","converse_width");
	$arr['converse_center']=get_pconfig(local_channel(),"redbasic","converse_center");
	$arr['nav_min_opacity']=get_pconfig(local_channel(),"redbasic","nav_min_opacity");
	$arr['top_photo']=get_pconfig(local_channel(),"redbasic","top_photo");
	$arr['reply_photo']=get_pconfig(local_channel(),"redbasic","reply_photo");
	return redbasic_form($a, $arr);
}

function theme_post(&$a) {
	if(!local_channel()) { return;}

	if (isset($_POST['redbasic-settings-submit'])) {
		set_pconfig(local_channel(), 'redbasic', 'schema', $_POST['redbasic_schema']);
		set_pconfig(local_channel(), 'redbasic', 'narrow_navbar', $_POST['redbasic_narrow_navbar']);
		set_pconfig(local_channel(), 'redbasic', 'nav_bg', $_POST['redbasic_nav_bg']);
		set_pconfig(local_channel(), 'redbasic', 'nav_gradient_top', $_POST['redbasic_nav_gradient_top']);
		set_pconfig(local_channel(), 'redbasic', 'nav_gradient_bottom', $_POST['redbasic_nav_gradient_bottom']);
		set_pconfig(local_channel(), 'redbasic', 'nav_active_gradient_top', $_POST['redbasic_nav_active_gradient_top']);
		set_pconfig(local_channel(), 'redbasic', 'nav_active_gradient_bottom', $_POST['redbasic_nav_active_gradient_bottom']);
		set_pconfig(local_channel(), 'redbasic', 'nav_bd', $_POST['redbasic_nav_bd']);
		set_pconfig(local_channel(), 'redbasic', 'nav_icon_colour', $_POST['redbasic_nav_icon_colour']);
		set_pconfig(local_channel(), 'redbasic', 'nav_active_icon_colour', $_POST['redbasic_nav_active_icon_colour']);
		set_pconfig(local_channel(), 'redbasic', 'link_colour', $_POST['redbasic_link_colour']);
		set_pconfig(local_channel(), 'redbasic', 'background_colour', $_POST['redbasic_background_colour']);
		set_pconfig(local_channel(), 'redbasic', 'banner_colour', $_POST['redbasic_banner_colour']);
		set_pconfig(local_channel(), 'redbasic', 'background_image', $_POST['redbasic_background_image']);
		set_pconfig(local_channel(), 'redbasic', 'item_colour', $_POST['redbasic_item_colour']);
		set_pconfig(local_channel(), 'redbasic', 'comment_item_colour', $_POST['redbasic_comment_item_colour']);
		set_pconfig(local_channel(), 'redbasic', 'comment_border_colour', $_POST['redbasic_comment_border_colour']);
		set_pconfig(local_channel(), 'redbasic', 'comment_indent', $_POST['redbasic_comment_indent']);
		set_pconfig(local_channel(), 'redbasic', 'toolicon_colour', $_POST['redbasic_toolicon_colour']);
		set_pconfig(local_channel(), 'redbasic', 'toolicon_activecolour', $_POST['redbasic_toolicon_activecolour']);
		set_pconfig(local_channel(), 'redbasic', 'font_size', $_POST['redbasic_font_size']);
		set_pconfig(local_channel(), 'redbasic', 'body_font_size', $_POST['redbasic_body_font_size']);
		set_pconfig(local_channel(), 'redbasic', 'font_colour', $_POST['redbasic_font_colour']);
		set_pconfig(local_channel(), 'redbasic', 'radius', $_POST['redbasic_radius']);
		set_pconfig(local_channel(), 'redbasic', 'photo_shadow', $_POST['redbasic_shadow']);
		set_pconfig(local_channel(), 'redbasic', 'converse_width', $_POST['redbasic_converse_width']);
		set_pconfig(local_channel(), 'redbasic', 'converse_center', $_POST['redbasic_converse_center']);
		set_pconfig(local_channel(), 'redbasic', 'nav_min_opacity', $_POST['redbasic_nav_min_opacity']);
		set_pconfig(local_channel(), 'redbasic', 'top_photo', $_POST['redbasic_top_photo']);
		set_pconfig(local_channel(), 'redbasic', 'reply_photo', $_POST['redbasic_reply_photo']);
	}
}



function redbasic_form(&$a, $arr) {
	$scheme_choices = array();
	$scheme_choices["---"] = t("Light (Red Matrix default)");
	$files = glob('view/theme/redbasic/schema/*.php');
	if($files) {
		foreach($files as $file) {
			$f = basename($file, ".php");
			$scheme_name = $f;
			$scheme_choices[$f] = $scheme_name;
		}
	}

if(feature_enabled(local_channel(),'expert')) 
				$expert = 1;
					
	  $t = get_markup_template('theme_settings.tpl');
	  $o .= replace_macros($t, array(
		'$submit' => t('Submit'),
		'$baseurl' => $a->get_baseurl(),
		'$expert' => $expert,
		'$title' => t("Theme settings"),
		'$schema' => array('redbasic_schema', t('Select scheme'), $arr['schema'], '', $scheme_choices),
		'$narrow_navbar' => array('redbasic_narrow_navbar',t('Narrow navbar'),$arr['narrow_navbar'], '', array(t('No'),t('Yes'))),
		'$nav_bg' => array('redbasic_nav_bg', t('Navigation bar background color'), $arr['nav_bg']),
		'$nav_gradient_top' => array('redbasic_nav_gradient_top', t('Navigation bar gradient top color'), $arr['nav_gradient_top']),
		'$nav_gradient_bottom' => array('redbasic_nav_gradient_bottom', t('Navigation bar gradient bottom color'), $arr['nav_gradient_bottom']),
		'$nav_active_gradient_top' => array('redbasic_nav_active_gradient_top', t('Navigation active button gradient top color'), $arr['nav_active_gradient_top']),
		'$nav_active_gradient_bottom' => array('redbasic_nav_active_gradient_bottom', t('Navigation active button gradient bottom color'), $arr['nav_active_gradient_bottom']),
		'$nav_bd' => array('redbasic_nav_bd', t('Navigation bar border color '), $arr['nav_bd']),
		'$nav_icon_colour' => array('redbasic_nav_icon_colour', t('Navigation bar icon color '), $arr['nav_icon_colour']),
		'$nav_active_icon_colour' => array('redbasic_nav_active_icon_colour', t('Navigation bar active icon color '), $arr['nav_active_icon_colour']),
		'$link_colour' => array('redbasic_link_colour', t('link color'), $arr['link_colour'], '', $link_colours),
		'$banner_colour' => array('redbasic_banner_colour', t('Set font-color for banner'), $arr['banner_colour']),
		'$bgcolour' => array('redbasic_background_colour', t('Set the background color'), $arr['bgcolour']),
		'$background_image' => array('redbasic_background_image', t('Set the background image'), $arr['background_image']),
		'$item_colour' => array('redbasic_item_colour', t('Set the background color of items'), $arr['item_colour']),
		'$comment_item_colour' => array('redbasic_comment_item_colour', t('Set the background color of comments'), $arr['comment_item_colour']),
		'$comment_border_colour' => array('redbasic_comment_border_colour', t('Set the border color of comments'), $arr['comment_border_colour']),
		'$comment_indent' => array('redbasic_comment_indent', t('Set the indent for comments'), $arr['comment_indent']),
		'$toolicon_colour' => array('redbasic_toolicon_colour',t('Set the basic color for item icons'),$arr['toolicon_colour']),
		'$toolicon_activecolour' => array('redbasic_toolicon_activecolour',t('Set the hover color for item icons'),$arr['toolicon_activecolour']),
		'$body_font_size' => array('redbasic_body_font_size', t('Set font-size for the entire application'), $arr['body_font_size'], t('Example: 14px')),
		'$font_size' => array('redbasic_font_size', t('Set font-size for posts and comments'), $arr['font_size']),
		'$font_colour' => array('redbasic_font_colour', t('Set font-color for posts and comments'), $arr['font_colour']),
		'$radius' => array('redbasic_radius', t('Set radius of corners'), $arr['radius']),
		'$shadow' => array('redbasic_shadow', t('Set shadow depth of photos'), $arr['shadow']),
		'$converse_width' => array('redbasic_converse_width',t('Set maximum width of content region in pixel'),$arr['converse_width'], t('Leave empty for default width')),
		'$converse_center' => array('redbasic_converse_center',t('Center page content'),$arr['converse_center'], '', array(t('No'),t('Yes'))),
		'$nav_min_opacity' => array('redbasic_nav_min_opacity',t('Set minimum opacity of nav bar - to hide it'),$arr['nav_min_opacity']),
		'$top_photo' => array('redbasic_top_photo', t('Set size of conversation author photo'), $arr['top_photo']),
		'$reply_photo' => array('redbasic_reply_photo', t('Set size of followup author photos'), $arr['reply_photo']),
		));

	return $o;
}
