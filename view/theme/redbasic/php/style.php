<?php

// Get the UID of the channel owner
	$uid = get_theme_uid();

	if($uid)
	    load_pconfig($uid,'redbasic');

// Nav colours are mess.  Set $nav_colour as a single word for the sake of letting folk pick one
// but it actually consists of at least two colours to form a gradient - $nav_bg_1 and $nav_bg_2
// A further two - $nav_bg_3 and $nav_bg_4 are used to create the hover, if any particular scheme
// wants to implement that
	    $nav_colour = get_pconfig($uid, "redbasic", "nav_colour");	
		if ($nav_colour == "red") {
					$nav_bg_1 = "#f88";
					$nav_bg_2 = "#b00";
					$nav_bg_3 = "#f00";
					$nav_bg_4 = "#b00";
					$search_background = '#FFDDDD';
		}

		if ($nav_colour == "black") {
				    $nav_bg_1 = $nav_bg_3 = "#000";
		      		    $nav_bg_2 = $nav_bg_4 = "#222";
					$search_background = '#EEEEEE';
		}
		if ($nav_colour == "silver") {
				    $nav_bg_1 = $nav_bg_2 = $nav_bg_3 = $nav_bg_4 = "silver";
					$search_background = '#EEEEEE';
		}

	    $background_colour = get_pconfig($uid, "redbasic", "background_colour");	
	    $background_image = get_pconfig($uid, "redbasic", "background_image");	
	    $item_colour = get_pconfig($uid, "redbasic", "item_colour");	
	    $item_opacity = get_pconfig($uid, "redbasic", "item_opacity");	
	    $font_size = get_pconfig($uid, "redbasic", "font_size");	
	    $font_colour = get_pconfig($uid, "redbasic", "font_colour");	
	    $radius = get_pconfig($uid, "redbasic", "radius");	
		$shadow = get_pconfig($uid,"redbasic","photo_shadow");

//Set some defaults - we have to do this after pulling owner settings, and we have to check for each setting
//individually.  If we don't, we'll have problems if a user has set one, but not all options.

	if (! $nav_colour) {
		$nav_colour = "red";
			$nav_bg_1 = "#f88";
			$nav_bg_2 = "#b00";
			$nav_bg_3 = "#f00";
			$nav_bg_4 = "#b00";
		}
	if (! $background_colour)
		$background_colour = "fff";
	if (! $background_image)
		$background_image ='';
	if (! $item_colour)
		$item_colour = "fff";
	if (! $item_opacity)
		$item_opacity = "1";
	if (! $font_size)
		$font_size = "12";
	if (! $font_colour)
		$font_colour = "000";
	if (! $radius)
		$radius = "5";
	if (! $shadow)
		$shadow = "0";



// Apply the settings
	if(file_exists('view/theme/' . current_theme() . '/css/style.css')) {
		$x = file_get_contents('view/theme/' . current_theme() . '/css/style.css');

$options = array (
'$nav_bg_1' => $nav_bg_1,
'$nav_bg_2' => $nav_bg_2,
'$nav_bg_3' => $nav_bg_3,
'$nav_bg_4' => $nav_bg_4,
'$search_background' => $search_background,
'$background_colour' => $background_colour,
'$background_image' => $background_image,
'$item_colour' => $item_colour,
'$item_opacity' => $item_opacity,
'$font_size' => $font_size,
'$font_colour' => $font_colour,
'$radius' => $radius,
'$shadow' => $shadow
);

echo str_replace(array_keys($options), array_values($options), $x);    

}
