<?php



function ping_init(&$a) {

	if(! local_user())
		xml_status(0);

	$r = q("SELECT COUNT(*) AS `total` FROM `item` 
		WHERE `unseen` = 1 AND `visible` = 1 AND `deleted` = 0 AND `uid` = %d",
		intval(local_user())
	);
	$network = $r[0]['total'];

	$r = q("SELECT COUNT(*) AS `total` FROM `item` 
		WHERE `unseen` = 1 AND `visible` = 1 AND `deleted` = 0 AND `uid` = %d AND `type` != 'remote' ",
		intval(local_user())
	);
	$home = $r[0]['total'];

	$r = q("SELECT COUNT(*) AS `total` FROM `intro` 
		WHERE `uid` = %d  AND `blocked` = 0 AND `ignore` = 0 ",
		intval(local_user())
	);
	$intro = $r[0]['total'];

	if ($a->config['register_policy'] == REGISTER_APPROVE &&	
		$a->config['admin_email'] === $a->user['email']){
			$r = q("SELECT COUNT(*) AS `total` FROM `register`");
			$register = $r[0]['total'];
	} else {
		$register = "0";
	}


	$myurl = $a->get_baseurl() . '/profile/' . $a->user['nickname'] ;
	$r = q("SELECT COUNT(*) AS `total` FROM `mail`
		WHERE `uid` = %d AND `seen` = 0 AND `from-url` != '%s' ",
		intval(local_user()),
		dbesc($myurl)
	);

	$mail = $r[0]['total'];
	
	header("Content-type: text/xml");
	echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\r\n<result><register>$register</register><intro>$intro</intro><mail>$mail</mail><net>$network</net><home>$home</home></result>\r\n";

	killme();
}

