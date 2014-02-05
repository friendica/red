<?php

if(! $a->install) {
	// Get the UID of the channel owner
	$uid = get_theme_uid();

	if($uid)
	    load_pconfig($uid,'redbasic');

// Nav colours are mess.  Set $nav_colour as a single word for the sake of letting folk pick one
// but it actually consists of at least two colours to form a gradient - $nav_bg_1 and $nav_bg_2
// A further two - $nav_bg_3 and $nav_bg_4 are used to create the hover, if any particular scheme
// wants to implement that
	    $nav_colour = get_pconfig($uid, "redbasic", "nav_colour");	

// Load the owners pconfig
		$banner_colour = get_pconfig($uid,'redbasic','banner_colour');
	    $link_colour = get_pconfig($uid, "redbasic", "link_colour");	
		$schema = get_pconfig($uid,'redbasic','schema');
	    $bgcolour = get_pconfig($uid, "redbasic", "background_colour");	
	    $background_image = get_pconfig($uid, "redbasic", "background_image");	
		$toolicon_colour = get_pconfig($uid,'redbasic','toolicon_colour');
		$toolicon_activecolour = get_pconfig($uid,'redbasic','toolicon_activecolour');
	    $item_colour = get_pconfig($uid, "redbasic", "item_colour");	
	    $item_opacity = get_pconfig($uid, "redbasic", "item_opacity");	
	    $body_font_size = get_pconfig($uid, "redbasic", "body_font_size");	
	    $font_size = get_pconfig($uid, "redbasic", "font_size");	
	    $font_colour = get_pconfig($uid, "redbasic", "font_colour");	
	    $radius = get_pconfig($uid, "redbasic", "radius");	
	    $shadow = get_pconfig($uid,"redbasic","photo_shadow");
	    $converse_width=get_pconfig($uid,"redbasic","converse_width");
		$nav_min_opacity=get_pconfig($uid,'redbasic','nav_min_opacity');
		$sloppy_photos=get_pconfig($uid,'redbasic','sloppy_photos');
		$top_photo=get_pconfig($uid,'redbasic','top_photo');
		$reply_photo=get_pconfig($uid,'redbasic','reply_photo');

}

// Now load the scheme.  If a value is changed above, we'll keep the settings
// If not, we'll keep those defined by the schema
// Setting $scheme to '' wasn't working for some reason, so we'll check it's
// not --- like the mobile theme does instead.

	if (($schema) && ($schema != '---')) {
		// Check it exists, because this setting gets distributed to clones
		if(file_exists('view/theme/redbasic/schema/' . $schema . '.php')) {
			$schemefile = 'view/theme/redbasic/schema/' . $schema . '.php';
			require_once ($schemefile);
		}
	}
		// If we haven't got a schema, load the default.  We shouldn't touch this - we
		// should leave it for admins to define for themselves.
			if (! $schema) {
			      if(file_exists('view/theme/redbasic/schema/default.php')) {
				    $schemefile = 'view/theme/redbasic/schema/' . 'default.php';
				    require_once ($schemefile);
				    }
			}
		
		
//Set some defaults - we have to do this after pulling owner settings, and we have to check for each setting
//individually.  If we don't, we'll have problems if a user has set one, but not all options.

	if (! $nav_colour) {
		$nav_colour = "red";
			$nav_bg_1 = "#f88";
			$nav_bg_2 = "#b00";
			$nav_bg_3 = "#f00";
			$nav_bg_4 = "#b00";
		}
	if (! $link_colour)
		$link_colour = "#0080FF";
	if (! $banner_colour)
		$banner_colour = "fff";
	if (! $bgcolour)
		$bgcolour = "#fdfdfd";
	if (! $background_image)
		$background_image ='';
	if (! $item_colour)
		$item_colour = "#fdfdfd";
	if (! $toolicon_colour)
		$toolicon_colour = '#777777';
	if (! $toolicon_activecolour)
		$toolicon_activecolour = '#000';
	if (! $item_opacity)
		$item_opacity = "1";
	if (! $font_size)
		$font_size = "1.0em";
	if (! $body_font_size)
		$body_font_size = "11px";
	if (! $font_colour)
		$font_colour = "#4D4D4D";
	if (! $radius)
		$radius = "0";
	if (! $shadow)
		$shadow = "0";
	if(! $active_colour)
		$active_colour = '#FFFFFF';
    if (! $converse_width)
    	$converse_width="1024px";
	if(! $top_photo)
		$top_photo = '64px';
	$pmenu_top = intval($top_photo) - 16 . 'px';
	$wwtop = intval($top_photo) - 15 . 'px';
	$comment_indent = intval($top_photo) + 10 . 'px';

	if(! $reply_photo)
		$reply_photo = '32px';
	$pmenu_reply = intval($reply_photo) - 16 . 'px';
	
	if($nav_min_opacity === false || $nav_min_opacity === '') {
		$nav_float_min_opacity = 1.0;
		$nav_percent_min_opacity = 100;
	}
	else {
		$nav_float_min_opacity = (float) $nav_min_opacity;
		$nav_percent_min_opacity = (int) 100 * $nav_min_opacity;
	}
			

// Nav colours have nested values, so we have to define the actual variables
// used in the CSS from the higher level "red", "black", etc here
		if ($nav_colour == "red") {
					$nav_bg_1 = $nav_bg_3 = "#ba002f";
					$nav_bg_2 = $nav_bg_4 = "#ad002c";
					$search_background = "#EEEEEE";
					$active_colour = '#444444';
		}

		if ($nav_colour == "black") {
					$nav_bg_1 = $nav_bg_3 = "#000";
		      			$nav_bg_2 = $nav_bg_4 = "#222";
					$search_background = '#EEEEEE';
					$active_colour = '#AAAAAA';
		}
		if ($nav_colour == "silver") {
					$nav_bg_1 = $nav_bg_2 = $nav_bg_3 = $nav_bg_4 = "silver";
					$search_background = '#EEEEEE';
		}
		if($nav_colour === "pink") {
		      $nav_bg_1 = $nav_bg_3 = "#FFC1CA";
		      $nav_bg_2 = $nav_bg_4 = "#FFC1CA";
	}
		if($nav_colour === "green") {
		      $nav_bg_1 = $nav_bg_3 = "#5CD65C";
		      $nav_bg_2 = $nav_bg_4 = "#5CD65C";
	}
		if($nav_colour === "blue") {
		      $nav_bg_1 = $nav_bg_3 = "#1872a2";
		      $nav_bg_2 = $nav_bg_4 = "#1872a2";
	}
		if($nav_colour === "purple") {
		      $nav_bg_1 = $nav_bg_3 = "#551A8B";
		      $nav_bg_2 = $nav_bg_4 = "#551A8B";
	}
		if($nav_colour === "orange") {
		      $nav_bg_1 = $nav_bg_3 = "#FF3D0D";
		      $nav_bg_2 = $nav_bg_4 = "#FF3D0D";
	}	
		if($nav_colour === "brown") {
		      $nav_bg_1 = $nav_bg_3 = "#330000";
		      $nav_bg_2 = $nav_bg_4 = "#330000";
	}
		if($nav_colour === "grey") {
		      $nav_bg_1 = $nav_bg_3 = "#2e2f2e";
		      $nav_bg_2 = $nav_bg_4 = "#2e2f2e";
	}
		if($nav_colour === "gold") {
		      $nav_bg_1 = $nav_bg_3 = "#FFAA00";
		      $nav_bg_2 = $nav_bg_4 = "#FFAA00";
	}

		
// Apply the settings
	if(file_exists('view/theme/redbasic/css/style.css')) {
		$x = file_get_contents('view/theme/redbasic/css/style.css');

$options = array (
'$nav_bg_1' => $nav_bg_1,
'$nav_bg_2' => $nav_bg_2,
'$nav_bg_3' => $nav_bg_3,
'$nav_bg_4' => $nav_bg_4,
'$link_colour' => $link_colour,
'$banner_colour' => $banner_colour,
'$search_background' => $search_background,
'$bgcolour' => $bgcolour,
'$background_image' => $background_image,
'$item_colour' => $item_colour,
'$item_opacity' => $item_opacity,
'$toolicon_colour' => $toolicon_colour,
'$toolicon_activecolour' => $toolicon_activecolour,
'$font_size' => $font_size,
'$font_colour' => $font_colour,
'$body_font_size' => $body_font_size,
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
'$wwtop' => $wwtop,
'$comment_indent' => $comment_indent
);

echo str_replace(array_keys($options), array_values($options), $x);    

}

if($sloppy_photos && file_exists('view/theme/redbasic/css/sloppy_photos.css')) {
	echo file_get_contents('view/theme/redbasic/css/sloppy_photos.css');
} 
