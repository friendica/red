<?php
    $line_height = false;
    $redbasic_font_size = false;
    $resolution = false;
    $colour = false;
    $site_line_height = get_config("redbasic","line_height");
    $site_redbasic_font_size = get_config("redbasic", "font_size" );
    $site_colour = get_config("redbasic", "colour" );

    if (local_user()) {
        $line_height = get_pconfig(local_user(), "redbasic","line_height");
        $redbasic_font_size = get_pconfig(local_user(), "redbasic", "font_size");
        $colour = get_pconfig(local_user(), "redbasic", "colour");
    }

    if ($line_height === false) {
		$line_height = $site_line_height;
	}
    if ($line_height === false) {
		$line_height = "1.2";
	}
    if ($redbasic_font_size === false) {
		$redbasic_font_size = $site_redbasic_font_size;
	}
    if ($redbasic_font_size === false) {
		$redbasic_font_size = "12";
	}
    if ($colour === false) {
		$colour = $site_colour;
	}
	if($colour === false) {
		$colour = "light";
	}
    
	if(file_exists('view/theme/' . current_theme() . '/css/style.css')) {
		echo file_get_contents('view/theme/' . current_theme() . '/css/style.css');
    }
    echo "\r\n";

	if(($redbasic_font_size >= 10.0) && ($redbasic_font_size <= 16.0)) {
		echo ".wall-item-content { font-size: $redbasic_font_size; }\r\n";
	}

	if(($line_height >= 1.0) && ($line_height <= 1.5)) {
		echo ".wall-item-content { line-height: $line_height; }\r\n";
	}	
