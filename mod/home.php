<?php

if(! function_exists('home_init')) {
function home_init(&$a) {

	if(x($_SESSION,'authenticated') && (x($_SESSION,'uid'))) {
		if($a->user['nickname'])
			goaway( $a->get_baseurl() . "/profile/" . $a->user['nickname'] );
		else
			goaway( $a->get_baseurl() . "/profile/" . $_SESSION['uid'] );
	}
	$a->page['htmlhead'] .= "<meta name=\"dfrn-template\" content=\"" . $a->get_baseurl() . "/profile/%s" . "\" />\r\n";
 
}}


if(! function_exists('home_content')) {
function home_content(&$a) {
	$a->page['header'] .= '<div id="logo">mistpark</div>';
	$a->page['footer'] .= "<div class=\"powered\" >Powered by <a href=\"http://mistpark.com\" name=\"mistpark\" >mistpark</a></div>";
	$o .= '<h1>Welcome' . ((x($a->config,'sitename')) ? " to {$a->config['sitename']}" : "" ) . '</h1>';
	$o .= login(($a->config['register_policy'] == REGISTER_CLOSED) ? 0 : 1);
	return $o;

	
}} 