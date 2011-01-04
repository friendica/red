<?php

if(! function_exists('home_init')) {
function home_init(&$a) {

	if(local_user() && ($a->user['nickname']))
			goaway( $a->get_baseurl() . "/profile/" . $a->user['nickname'] );

	$a->page['htmlhead'] .= "<meta name=\"dfrn-template\" content=\"" . $a->get_baseurl() . "/profile/%s" . "\" />\r\n"; 
}}


if(! function_exists('home_content')) {
function home_content(&$a) {

	$o = '';
/*
 *	if(! (x($a->page,'footer')))
 *		$a->page['footer'] = '';
 *	$a->page['footer'] .= "<div class=\"powered\" >Powered by <a href=\"http://friendika.com\" title=\"friendika\" >friendika</a></div>";
 */
	$o .= '<h1>' . ((x($a->config,'sitename')) ? t("Welcome to ").$a->config['sitename'] : "" ) . '</h1>';
	if(file_exists('home.html'))
 		$o .= file_get_contents('home.html');

	$o .= login(($a->config['register_policy'] == REGISTER_CLOSED) ? 0 : 1);
	
	call_hooks("home_content",$o);
	
	return $o;

	
}} 
