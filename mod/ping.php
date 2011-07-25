<?php
require_once("include/datetime.php");


function ping_init(&$a) {

	if(! local_user())
		xml_status(0);

	
	$r = q("SELECT COUNT(*) AS `total` FROM `item` 
		WHERE `unseen` = 1 AND `visible` = 1 AND `deleted` = 0 AND `uid` = %d AND `wall` = 0 ",
		intval(local_user())
	);
	$network = $r[0]['total'];

	$r = q("SELECT COUNT(*) AS `total` FROM `item` 
		WHERE `unseen` = 1 AND `visible` = 1 AND `deleted` = 0 AND `uid` = %d AND `wall` = 1 ",
		intval(local_user())
	);
	$home = $r[0]['total'];

	$intros = q("SELECT COUNT(`intro`.`id`) AS `total`, `intro`.`id`, `intro`.`datetime`, 
		`fcontact`.`name`, `fcontact`.`url`, `fcontact`.`photo` 
		FROM `intro` LEFT JOIN `fcontact` ON `intro`.`fid` = `fcontact`.`id`
		WHERE `intro`.`uid` = %d  AND `intro`.`blocked` = 0 AND `intro`.`ignore` = 0 ",
		intval(local_user())
	);
	$intro = $intros[0]['total'];

	$myurl = $a->get_baseurl() . '/profile/' . $a->user['nickname'] ;
	$mails = q("SELECT *,  COUNT(*) AS `total` FROM `mail`
		WHERE `uid` = %d AND `seen` = 0 AND `from-url` != '%s' ",
		intval(local_user()),
		dbesc($myurl)
	);
	$mail = $mails[0]['total'];
	
	
	
	header("Content-type: text/xml");
	echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
		<result>
			<intro>$intro</intro>
			<mail>$mail</mail>
			<net>$network</net>
			<home>$home</home>
			<notif count=\"".($mail+$intro)."\">";
	if ($intro>0){
		foreach ($intros as $i) { 
			echo sprintf ('<note href="%s" name="%s" url="%s" photo="%s" date="%s">%s</note>', 
				$a->get_baseurl().'/notification/'.$i['id'], $i['name'], $i['url'], $i['photo'], relative_date($i['datetime']), t("{0} wants to be your friend")
			);
		};
	}
	if ($mail>0){
		foreach ($mails as $i) { 
			var_dump($i);
			echo sprintf ('<note href="%s" name="%s" url="%s" photo="%s" date="%s">%s</note>',
				$a->get_baseurl().'/message/'.$i['id'], $i['from-name'], $i['from-url'], $i['from-photo'], relative_date($i['created']), t("{0} sent you a message")
			);
		};
	}

	echo "  </notif>
		</result>
	";

	killme();
}

