<?php

function toggle_mobile_init(&$a) {

	if(isset($_GET['off']))
		$_SESSION['show_mobile'] = false;
	else
		$_SESSION['show_mobile'] = true;

	if(isset($_GET['address']))
		$address = $_GET['address'];
	else
		$address = $a->get_baseurl();

	goaway($address);
}

