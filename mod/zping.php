<?php /** @file */

require_once('include/zot.php');

function zping_content(&$a) {

	// This is just a test utility function and may go away once we build these tools into
	// the address book and directory to do dead site discovery.

	// The response packet include the current URL and key so we can discover if the server 
	// has been re-installed and clean up (e.g. get rid of) any old hublocs and xchans. 

	// Remember to add '/post' to the url

	if(! local_user())
		return;

	$url = $_REQUEST['url'];

	if(! $url)
		return;


	$m = zot_build_packet($a->get_channel(),'ping');
	$r = zot_zot($url,$m);
	return print_r($r,true);

}