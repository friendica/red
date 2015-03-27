<?php

if(! $a->install) {
	// Get the UID of the channel owner
	$uid = get_theme_uid();

	if($uid)
	    load_pconfig($uid,'redbasic');

// Load the owners pconfig
		$nav_bg = get_pconfig($uid, "redbasic", "nav_bg");
		$nav_gradient_top = get_pconfig($uid, "redbasic", "nav_gradient_top");
		$nav_gradient_bottom = get_pconfig($uid, "redbasic", "nav_gradient_bottom");
		$nav_active_gradient_top = get_pconfig($uid, "redbasic", "nav_active_gradient_top");
		$nav_active_gradient_bottom = get_pconfig($uid, "redbasic", "nav_active_gradient_bottom");
		$nav_bd = get_pconfig($uid, "redbasic", "nav_bd");
		$nav_icon_colour = get_pconfig($uid, "redbasic", "nav_icon_colour");
		$nav_active_icon_colour = get_pconfig($uid, "redbasic", "nav_active_icon_colour");
		$narrow_navbar = get_pconfig($uid,'redbasic','narrow_navbar');
		$banner_colour = get_pconfig($uid,'redbasic','banner_colour');
		$link_colour = get_pconfig($uid, "redbasic", "link_colour");	
		$schema = get_pconfig($uid,'redbasic','schema');
		$bgcolour = get_pconfig($uid, "redbasic", "background_colour");	
		$background_image = get_pconfig($uid, "redbasic", "background_image");	
		$toolicon_colour = get_pconfig($uid,'redbasic','toolicon_colour');
		$toolicon_activecolour = get_pconfig($uid,'redbasic','toolicon_activecolour');
		$item_colour = get_pconfig($uid, "redbasic", "item_colour");
		$comment_item_colour = get_pconfig($uid, "redbasic", "comment_item_colour");
		$comment_border_colour = get_pconfig($uid, "redbasic", "comment_border_colour");
		$comment_indent = get_pconfig($uid, "redbasic", "comment_indent");
		$body_font_size = get_pconfig($uid, "redbasic", "body_font_size");	
		$font_size = get_pconfig($uid, "redbasic", "font_size");	
		$font_colour = get_pconfig($uid, "redbasic", "font_colour");	
		$radius = get_pconfig($uid, "redbasic", "radius");	
		$shadow = get_pconfig($uid,"redbasic","photo_shadow");
		$converse_width=get_pconfig($uid,"redbasic","converse_width");
		$converse_center=get_pconfig($uid,"redbasic","converse_center");
		$nav_min_opacity=get_pconfig($uid,'redbasic','nav_min_opacity');
		$top_photo=get_pconfig($uid,'redbasic','top_photo');
		$reply_photo=get_pconfig($uid,'redbasic','reply_photo');

}

	// Now load the scheme.  If a value is changed above, we'll keep the settings
	// If not, we'll keep those defined by the schema
	// Setting $schema to '' wasn't working for some reason, so we'll check it's
	// not --- like the mobile theme does instead.


	// Allow layouts to over-ride the schema

	if($_REQUEST['schema'])
		$schema = $_REQUEST['schema'];

	if (($schema) && ($schema != '---')) {
		// Check it exists, because this setting gets distributed to clones
		if(file_exists('view/theme/redbasic/schema/' . $schema . '.php')) {
			$schemefile = 'view/theme/redbasic/schema/' . $schema . '.php';
			require_once ($schemefile);
		}
		if(file_exists('view/theme/redbasic/schema/' . $schema . '.css')) {
			$schemecss = file_get_contents('view/theme/redbasic/schema/' . $schema . '.css');
		}

	}

	// If we haven't got a schema, load the default.  We shouldn't touch this - we
	// should leave it for admins to define for themselves.
	if (! $schema) {
		if(file_exists('view/theme/redbasic/schema/default.php')) {
			$schemefile = 'view/theme/redbasic/schema/default.php';
			require_once ($schemefile);
		}
		if(file_exists('view/theme/redbasic/schema/default.css')) {
			$schemecss = file_get_contents('view/theme/redbasic/schema/default.css');
		}
	}
		
		
//Set some defaults - we have to do this after pulling owner settings, and we have to check for each setting
//individually.  If we don't, we'll have problems if a user has set one, but not all options.

	if (! $nav_bg)
		$nav_bg = "#222";
	if (! $nav_gradient_top)
		$nav_gradient_top = "#3c3c3c";
	if (! $nav_gradient_bottom)
		$nav_gradient_bottom = "#222";
	if (! $nav_active_gradient_top)
		$nav_active_gradient_top = "#222";
	if (! $nav_active_gradient_bottom)
		$nav_active_gradient_bottom = "#282828";
	if (! $nav_bd)
		$nav_bd = "#222";
	if (! $nav_icon_colour)
		$nav_icon_colour = "#999";
	if (! $nav_active_icon_colour)
		$nav_active_icon_colour = "#fff";
	if (! $navtabs_borderc)
		$navtabs_borderc = "rgba(204,204,204,0.8)";
	if (! $navtabs_fontcolour)
		$navtabs_fontcolour = "#555";
	if (! $navtabs_bgcolour)
		$navtabs_bgcolour = "rgba(254,254,254,0.4)";
	if (! $navtabs_linkcolour)
		$navtabs_linkcolour = "";
	if (! $navtabs_linkchover)
		$navtabs_linkchover = "";
	if (! $navtabs_decohover)
		$navtabs_decohover = "none";
	if (! $navtabs_bgchover)
		$navtabs_bgchover = "rgba(238,238,238,0.8)";
	if (! $link_colour)
		$link_colour = "#337AB7";
	if (! $navaside_bghover)
		$navaside_bghover = "#eee";
	if (! $link_font_weight)
		$link_font_weight = "normal";
	if (! $banner_colour)
		$banner_colour = "#fff";
	if (! $search_background)
		$search_background = "#eee";
	if (! $bgcolour)
		$bgcolour = "#fdfdfd";
	if (! $background_image)
		$background_image ='';
	if (! $item_colour)
		$item_colour = "rgba(238,238,238,0.8)";
	if (! $comment_item_colour)
		$comment_item_colour = "rgba(254,254,254,0.4)";
	if (! $genericcontent_bgcolour)
		$genericcontent_bgcolour = $comment_item_colour;
	if (! $comment_border_colour)
		$comment_border_colour = "rgba(238,238,238,0.8)";
	if (! $toolicon_colour)
		$toolicon_colour = '#777';
	if (! $toolicon_activecolour)
		$toolicon_activecolour = '#000';
	if (! $item_opacity)
		$item_opacity = "1";
	if (! $font_size)
		$font_size = "0.9rem";
	if (! $body_font_size)
		$body_font_size = "0.75rem";
	if (! $font_colour)
		$font_colour = "#4d4d4d";
	if (! $selected_active_colour)
		$selected_active_colour = "#444";
	if (! $selected_active_deco)
		$selected_active_deco = "none";
	if (! $blockquote_colour)
		$blockquote_colour = "#4d4d4d";
	if (! $blockquote_bgcolour)
		$blockquote_bgcolour = "";
	if (! $blockquote_bordercolour)
		$blockquote_bordercolour = "#ccc";
	if (! $code_borderc)
		$code_borderc = "#ccc";
	if (! $code_bgcolour)
		$code_bgcolour = "#ccc";
	if (! $code_txtcolour)
		$code_txtcolour = "#000";
	if (! $pre_borderc)
		$pre_borderc = "#ccc";
	if (! $pre_bgcolour)
		$pre_bgcolour = "#F5F5F5";
	if (! $pre_txtcolour)
		$pre_txtcolour = "#333";
	if (! $notif_itemcolour)
		$notif_itemcolour = "#000";
	if (! $notif_itemhovercolour)
		$notif_itemhovercolour = "#000";
	if (! $dropdown_bgcolour)
		$dropdown_bgcolour = "#FFF";
	if (! $dropdown_textcolour)
		$dropdown_textcolour = "#333";
	if (! $dropdown_txtcolhover)
		$dropdown_txtcolhover = "#262626";
	if (! $dropdown_bgcolhover)
		$dropdown_bgcolhover = "#F5F5F5";
	if (! $dropdown_bgimghover)
		$dropdown_bgimghover = "";
	if (! $dropdown_togglecol)
		$dropdown_togglecol = "#333";
	if (! $dropdown_togglebgcol)
		$dropdown_togglebgcol = "#EBEBEB";
	if (! $dropdown_bordercol)
		$dropdown_bordercol = "#ADADAD";
	if (! $preview_backgroundimg)
		$preview_backgroundimg = "gray_and_white_diagonal_stripes_background_seamless.gif";
	if (! $acpopup_bgcolour)
		$acpopup_bgcolour = "#fff";
	if (! $acpopup_bordercolour)
		$acpopup_bordercolour = "#ccc";
	if (! $acpopup_tgbl_bgcolour)
		$acpopup_tgbl_bgcolour = "#ddddff";
	if (! $acpopup_hovercolour)
		$acpopup_hovercolour = "#000";

	if (! $radius)
		$radius = "4";
	if (! $shadow)
		$shadow = "0";
	if(! $active_colour)
		$active_colour = "#fff";
	if (! $converse_width)
		$converse_width = "1024";
	if(! $top_photo)
		$top_photo = '48px';
	if(! $comment_indent)
		$comment_indent = '0px';
	if(! $reply_photo)
		$reply_photo = '32px';

	if($nav_min_opacity === false || $nav_min_opacity === '') {
		$nav_float_min_opacity = 1.0;
		$nav_percent_min_opacity = 100;
	}
	else {
		$nav_float_min_opacity = (float) $nav_min_opacity;
		$nav_percent_min_opacity = (int) 100 * $nav_min_opacity;
	}

// Apply the settings
	if(file_exists('view/theme/redbasic/css/style.css')) {
		$x = file_get_contents('view/theme/redbasic/css/style.css');


// left aside is 231px + converse width
$main_width = (231 + intval($converse_width));

// prevent main_width smaller than 768px
$main_width = (($main_width < 768) ? 768 : $main_width) . 'px';

$options = array (
'$nav_bg' => $nav_bg,
'$nav_gradient_top' => $nav_gradient_top,
'$nav_gradient_bottom' => $nav_gradient_bottom,
'$nav_active_gradient_top' => $nav_active_gradient_top,
'$nav_active_gradient_bottom' => $nav_active_gradient_bottom,
'$nav_bd' => $nav_bd,
'$nav_icon_colour' => $nav_icon_colour,
'$nav_active_icon_colour' => $nav_active_icon_colour,
'$navtabs_borderc' => $navtabs_borderc,
'$navtabs_fontcolour' => $navtabs_fontcolour,
'$navtabs_bgcolour' => $navtabs_bgcolour,
'$navtabs_linkcolour' => $navtabs_linkcolour,
'$navtabs_linkchover' => $navtabs_linkchover,
'$navtabs_bgchover' => $navtabs_bgchover,
'$navtabs_decohover' => $navtabs_decohover,
'$navaside_bghover' => $navaside_bghover,
'$link_colour' => $link_colour,
'$link_font_weight' => $link_font_weight,
'$banner_colour' => $banner_colour,
'$search_background' => $search_background,
'$bgcolour' => $bgcolour,
'$background_image' => $background_image,
'$genericcontent_bgcolour' => $genericcontent_bgcolour,
'$item_colour' => $item_colour,
'$comment_item_colour' => $comment_item_colour,
'$comment_border_colour' => $comment_border_colour,
'$toolicon_colour' => $toolicon_colour,
'$toolicon_activecolour' => $toolicon_activecolour,
'$font_size' => $font_size,
'$font_colour' => $font_colour,
'$selected_active_colour' => $selected_active_colour,
'$selected_active_deco' => $selected_active_deco,
'$body_font_size' => $body_font_size,
'$blockquote_colour' => $blockquote_colour,
'$blockquote_bgcolour' => $blockquote_bgcolour,
'$blockquote_bordercolour' => $blockquote_bordercolour,
'$blockquote_bgcolourhover' => $blockquote_bgcolourhover,
'$code_borderc' => $code_borderc,
'$code_bgcolour' => $code_bgcolour,
'$code_txtcolour' => $code_txtcolour,
'$pre_borderc' => $pre_borderc,
'$pre_bgcolour' => $pre_bgcolour,
'$pre_txtcolour' => $pre_txtcolour,
'$notif_itemcolour' => $notif_itemcolour,
'$notif_itemhovercolour' => $notif_itemhovercolour,
'$dropdown_bgcolour' => $dropdown_bgcolour,
'$dropdown_textcolour' => $dropdown_textcolour,
'$dropdown_txtcolhover' => $dropdown_txtcolhover,
'$dropdown_bgcolhover' => $dropdown_bgcolhover,
'$dropdown_bgimghover' => $dropdown_bgimghover,
'$dropdown_togglecol' => $dropdown_togglecol,
'$dropdown_togglebgcol' => $dropdown_togglebgcol,
'$dropdown_bordercol' => $dropdown_bordercol,
'$preview_backgroundimg' => $preview_backgroundimg,
'$acpopup_bgcolour' => $acpopup_bgcolour,
'$acpopup_bordercolour' => $acpopup_bordercolour,
'$acpopup_tgbl_bgcolour' => $acpopup_tgbl_bgcolour,
'$acpopup_hovercolour' => $acpopup_hovercolour,
'$radius' => $radius,
'$shadow' => $shadow,
'$active_colour' => $active_colour,
'$converse_width' => $converse_width,
'$nav_float_min_opacity' => $nav_float_min_opacity,
'$nav_percent_min_opacity' => $nav_percent_min_opacity,
'$top_photo' => $top_photo,
'$reply_photo' => $reply_photo,
'$pmenu_top' => $pmenu_top,
'$pmenu_reply' => $pmenu_reply,
'$comment_indent' => $comment_indent,
'$main_width' => $main_width,
);

echo str_replace(array_keys($options), array_values($options), $x);    
}

if($narrow_navbar && file_exists('view/theme/redbasic/css/narrow_navbar.css')) {
	echo file_get_contents('view/theme/redbasic/css/narrow_navbar.css');
} 
if($converse_center && file_exists('view/theme/redbasic/css/converse_center.css')) {
	echo str_replace(array_keys($options), array_values($options), $x);
}

if($schemecss) {
	echo $schemecss;
}
