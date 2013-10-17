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

// Load the owners pconfig
		$schema = get_pconfig($uid,'redbasic','schema');
	    $bgcolour = get_pconfig($uid, "redbasic", "background_colour");	
	    $background_image = get_pconfig($uid, "redbasic", "background_image");	
	    $item_colour = get_pconfig($uid, "redbasic", "item_colour");	
	    $item_opacity = get_pconfig($uid, "redbasic", "item_opacity");	
	    $font_size = get_pconfig($uid, "redbasic", "font_size");	
	    $font_colour = get_pconfig($uid, "redbasic", "font_colour");	
	    $radius = get_pconfig($uid, "redbasic", "radius");	
	    $shadow = get_pconfig($uid,"redbasic","photo_shadow");

// Now load the scheme.  If a value is changed above, we'll keep the settings
// If not, we'll keep those defined by the schema
// Setting $scheme to '' wasn't working for some reason, so we'll check it's
// not --- like the mobile theme does instead.

		if (($schema) && ($schema != '---')) {
			$schemefile = 'view/theme/redbasic/schema/' . $schema . '.php';
			require_once ($schemefile);
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
	if (! $bgcolour)
		$bgcolour = "fff";
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
	if(! $active_colour)
		$active_colour = '#FFFFFF';

		

// Nav colours have nested values, so we have to define the actual variables
// used in the CSS from the higher level "red", "black", etc here
		if ($nav_colour == "red") {
					$nav_bg_1 = "#f88";
					$nav_bg_2 = "#b00";
					$nav_bg_3 = "#f00";
					$nav_bg_4 = "#b00";
					$search_background = '#FFDDDD';
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

		
// Apply the settings
	if(file_exists('view/theme/redbasic/css/style.css')) {
		$x = file_get_contents('view/theme/redbasic/css/style.css');

$options = array (
'$nav_bg_1' => $nav_bg_1,
'$nav_bg_2' => $nav_bg_2,
'$nav_bg_3' => $nav_bg_3,
'$nav_bg_4' => $nav_bg_4,
'$search_background' => $search_background,
'$bgcolour' => $bgcolour,
'$background_image' => $background_image,
'$item_colour' => $item_colour,
'$item_opacity' => $item_opacity,
'$font_size' => $font_size,
'$font_colour' => $font_colour,
'$radius' => $radius,
'$shadow' => $shadow,
'$active_colour' => $active_colour
);

echo str_replace(array_keys($options), array_values($options), $x);    

}
