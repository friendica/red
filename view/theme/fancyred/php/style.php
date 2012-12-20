<?php
    $line_height = false;
    $fancyred_font_size = false;
    $resolution = false;
    $colour = false;
    $site_line_height = get_config("fancyred","line_height");
    $site_fancyred_font_size = get_config("fancyred", "font_size" );
    $site_colour = get_config("fancyred", "colour" );

    if (local_user()) {
        $line_height = get_pconfig(local_user(), "fancyred","line_height");
        $fancyred_font_size = get_pconfig(local_user(), "fancyred", "font_size");
        $colour = get_pconfig(local_user(), "fancyred", "colour");
    }

    if ($line_height === false) {
		$line_height = $site_line_height;
	}
    if ($line_height === false) {
		$line_height = "1.2";
	}
    if ($fancyred_font_size === false) {
		$fancyred_font_size = $site_fancyred_font_size;
	}
    if ($fancyred_font_size === false) {
		$fancyred_font_size = "12";
	}
    if ($colour === false) {
		$colour = $site_colour;
	}
		$colour = "light";

    
        if (file_exists("$THEMEPATH/css/style.css")) {
            echo file_get_contents("$THEMEPATH/css/style.css");
        }
    

	if($fancyred_font_size == "16") {
		echo ".wall-item-content {
				font-size: 16px;
			}";
	}
	if($fancyred_font_size == "15") {
		echo ".wall-item-content {
				font-size: 15px;
			}";
	}	
	if($fancyred_font_size == "14") {
		echo ".wall-item-content {
				font-size: 14px;
			}";
	}
	if($fancyred_font_size == "13.5") {
		echo ".wall-item-content {
				font-size: 13.5px;
			}";
	}
	if($fancyred_font_size == "13") {
		echo ".wall-item-content {
				font-size: 13px;
			}";
	}
	if($fancyred_font_size == "12.5") {
		echo ".wall-item-content {
				font-size: 12.5px;
			}";
	}
	if($fancyred_font_size == "12") {
		echo ".wall-item-content {
				font-size: 12px;
			}";
	}
	if($line_height == "1.5") {
		echo ".wall-item-content {
				line-height: 1.5;
			}";
	}	
	if($line_height == "1.4") {
		echo ".wall-item-content {
				line-height: 1.4;
			}";
	}
	if($line_height == "1.3") {
		echo ".wall-item-content {
				line-height: 1.3;
			}";
	}
	if($line_height == "1.2") {
		echo ".wall-item-content {
				line-height: 1.2;
			}";
	}
	if($line_height == "1.1") {
		echo ".wall-item-content {
				line-height: 1.1;
			}";
	}

