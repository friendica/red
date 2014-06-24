<?php /** @file */


/**
 * Functions to assist in probing various legacy networks to figure out what kind of capabilities might be present.
 */


function net_have_driver($net) {

	if(function_exists('net_discover_' . $net))
		return true;
	return false;
}

function probe_well_known($addr) {

	$ret = array();

	$ret['src'] = $addr;

	if(strpos($addr,'@') !== false) {
		$ret['address'] = $addr;
	}
	else {
		$ret['url'] = $addr;
	}

	if(stristr($addr,'facebook.com')) {
		$ret['network'] = 'facebook';
	}
	if(stristr($addr,'google.com')) {
		$ret['network'] = 'google';
	}
	if(stristr($addr,'linkedin.com')) {
		$ret['network'] = 'linkedin';
	}

	call_hooks('probe_well_known', $ret);

	if(array_key_exists('network',$ret) && net_have_driver($ret['network'])) {
		$fn = 'net_discover_' . $ret['network'];
		$ret = $fn($ret);
	}


	return $ret;

}




function probe_webfinger($addr) {





}


function probe_legacy_webfinger($addr) {




}

function probe_zot($addr) {



}

function probe_dfrn($addr) {


}


function probe_diaspora($addr) {


}


function probe_legacy_feed($addr) {



}


function probe_activity_stream($addr) {


}

