<?php
/**
 * oembed plugin
 * 
 * oEmbed is a format for allowing an embedded representation of a URL on third party sites
 * http://www.oembed.com/
 * 
 */

require_once('include/oembed.php');

function oembed_install() {
  register_hook('jot_tool', 'addon/oembed/oembed.php', 'oembed_hook_jot_tool');
  register_hook('page_header', 'addon/oembed/oembed.php', 'oembed_hook_page_header');
}

function oembed_uninstall() {
  unregister_hook('jot_tool', 'addon/oembed/oembed.php', 'oembed_hook_jot_tool');
  unregister_hook('page_header', 'addon/oembed/oembed.php', 'oembed_hook_page_header');
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
		echo "<span class='oembed'>".oembed_replacecb($url)."</span>";
	}
	
	if ($a->argv[1]=='h2b'){
		$text = trim(hex2bin($_GET['text']));
		echo oembed_html2bbcode($text);
	}
	
	killme();
	
}

?>