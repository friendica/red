<?php
/**
 * Name: OEmbed
 * Description: OEmbed is a format for allowing an embedded representation of a URL on third party sites http://www.oembed.com/
 * Version: 1.2
 * Author: Fabio Comuni <http://kirgroup.com/profile/fabrix>
 */

require_once('include/oembed.php');

function oembed_install() {
	register_hook('jot_tool', 'addon/oembed/oembed.php', 'oembed_hook_jot_tool');
	register_hook('page_header', 'addon/oembed/oembed.php', 'oembed_hook_page_header');
	register_hook('plugin_settings', 'addon/oembed/oembed.php', 'oembed_settings'); 
	register_hook('plugin_settings_post', 'addon/oembed/oembed.php', 'oembed_settings_post');
}

function oembed_uninstall() {
	unregister_hook('jot_tool', 'addon/oembed/oembed.php', 'oembed_hook_jot_tool');
	unregister_hook('page_header', 'addon/oembed/oembed.php', 'oembed_hook_page_header');
	unregister_hook('plugin_settings', 'addon/oembed/oembed.php', 'oembed_settings'); 
	unregister_hook('plugin_settings_post', 'addon/oembed/oembed.php', 'oembed_settings_post');
}

function oembed_settings_post($a,$b){
    if(! local_user())
		return;
	if (isset($_POST['oembed-submit'])){
		set_pconfig(local_user(), 'oembed', 'use_for_youtube', (isset($_POST['oembed_use_for_youtube'])? intval($_POST['oembed_use_for_youtube']):0));
		notice( t('OEmbed settings updated') . EOL);
	}
}

function oembed_settings(&$a,&$o) {
    if(! local_user())
		return;
	$uofy = intval(get_pconfig(local_user(), 'oembed', 'use_for_youtube' ));

	$o.='
	<div class="settings-block">
	<h3 class="settings-heading">OEmbed</h3>
	<div id="settings-username-wrapper">
		<label for="oembed_use_for_youtube">'
			.t('Use OEmbed for YouTube videos: ') 
		.'</label><input type="checkbox" id="oembed_use_for_youtube" name="oembed_use_for_youtube" value="1"'
		. ($uofy==1?'checked="true"':'')
		.' />
	</div>
	<div id="settings-username-end"></div>
	<div class="settings-submit-wrapper">
		<input type="submit" value="'.t('Submit').'" class="settings-submit" name="oembed-submit" />
	</div>
	</div>';
}


function oembed_hook_page_header($a, &$b){
	$a->page['htmlhead'] .= sprintf('<script src="%s/oembed/oembed.js"></script>', $a->get_baseurl());
}


function oembed_hook_jot_tool($a, &$b) {
	$b .= '
	<div class="tool-wrapper" style="display: $visitor;" >
	  <img class="tool-link" src="addon/oembed/oembed.png" alt="Embed" title="Embed" onclick="oembed();" />
	</div> 
	';
}


function oembed_module() {
	return;
}

function oembed_init(&$a) {
	if ($a->argv[1]=='oembed.js'){
		$tpl = file_get_contents('addon/oembed/oembed.js');
		echo replace_macros($tpl, array(
			'$oembed_message' =>  t('URL to embed:'),
		));
	}
	
	if ($a->argv[1]=='b2h'){
		$url = array( "", trim(hex2bin($_GET['url'])));
		echo oembed_replacecb($url);
	}
	
	if ($a->argv[1]=='h2b'){
		$text = trim(hex2bin($_GET['text']));
		echo oembed_html2bbcode($text);
	}
	
	killme();
	
}

?>
