<?php

/**
 * Demo plugin for adding various types of Flash games to Friendika.
 * In this case we're using "Hot Shot Sniper" by FlashGames247
 */


function sniper_install() {
    register_hook('app_menu', 'addon/sniper/sniper.php', 'sniper_app_menu');
}

function sniper_uninstall() {
    unregister_hook('app_menu', 'addon/sniper/sniper.php', 'sniper_app_menu');

}

function sniper_app_menu($a,&$b) {
    $b['app_menu'] .= '<div class="app-title"><a href="sniper">Hot Shot Sniper</a></div>';
}


function sniper_module() {}

function sniper_content(&$a) {

$baseurl = $a->get_baseurl() . '/addon/sniper';

$o .= <<< EOT
<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" width="620" height="480" id="hotshotsniper" align="middle">
<param name="allowScriptAccess" value="sameDomain" />
<param name="movie" value="$baseurl/hotshotsniper.swf" /><param name="quality" value="high" /><param name="bgcolor" value="#000000" /><embed src="$baseurl/hotshotsniper.swf" quality="high" bgcolor="#000000" width="620" height="480" name="hotshotsniper" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
</object>
EOT;

return $o;
}