<?php

echo getcwd();
require_once("boot.php");

$a = new App;

@include(".htconfig.php");
require_once("dba.php");
$db = new dba($db_host, $db_user, $db_pass, $db_data, $install);
	unset($db_host, $db_user, $db_pass, $db_data);

require_once("session.php");
require_once("datetime.php");

if(($argc != 2) || (! intval($argv[1])))
	exit;



	// fetch item

	// if not parent, fetch it too

	// atomify

	// expand list of recipients

	// grab the contact records

	// foreach recipient

	// if no dfrn-id continue

	// fetch_url dfrn-notify

	// decrypt challenge

	// post result

	// continue

		killme();

