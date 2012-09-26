<?php

if(! function_exists('home_init')) {
function home_init(&$a) {

	$ret = array();
	call_hooks('home_init',$ret);

	$channel = $a->get_channel();

	if(local_user() && $channel && $channel['channel_address']))
		goaway( $a->get_baseurl() . '/profile/' . $channel['channel_address']);

}}


if(! function_exists('home_content')) {
function home_content(&$a) {

	$o = '';

	if(x($_SESSION,'theme'))
		unset($_SESSION['theme']);
	if(x($_SESSION,'mobile-theme'))
		unset($_SESSION['mobile-theme']);

	$o .= '<h1>' . ((x($a->config,'sitename')) ? sprintf( t("Welcome to %s") ,$a->config['sitename']) : "" ) . '</h1>';
	if(file_exists('home.html'))
 		$o .= file_get_contents('home.html');

	$o .= login(($a->config['system']['register_policy'] == REGISTER_CLOSED) ? 0 : 1);
	
	call_hooks("home_content",$o);
	
	return $o;

	
}} 
