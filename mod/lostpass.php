<?php


function lostpass_post(&$a) {

	$email = notags(trim($_POST['login-name']));
	if(! $email)
		goaway($a->get_baseurl());

	$r = q("SELECT * FROM `user` WHERE `email` = '%s' LIMIT 1",
		dbesc($email)
	);
	if(! count($r))
		goaway($a->get_baseurl());
	$uid = $r[0]['uid'];
	$username = $r[0]['username'];

	$new_password = autoname(12) . mt_rand(100,9999);
	$new_password_encoded = hash('whirlpool',$new_password);

	$r = q("UPDATE `user` SET `pwdreset` = '%s' WHERE `uid` = %d LIMIT 1",
		dbesc($new_password_encoded),
		intval($uid)
	);
	if($r)
		notice("Password reset request issued. Check your email.");

	$email_tpl = file_get_contents("view/lostpass_eml.tpl");
	$email_tpl = replace_macros($email_tpl, array(
			'$sitename' => $a->config['sitename'],
			'$siteurl' =>  $a->get_baseurl(),
			'$username' => $username,
			'$email' => $email,
			'$reset_link' => $a->get_baseurl() . '/lostpass?verify=' . $new_password
	));

	$res = mail($email,"Password reset requested at {$a->config['sitename']}",$email_tpl,"From: Administrator@{$_SERVER[SERVER_NAME]}");

	

	goaway($a->get_baseurl());
}


function lostpass_content(&$a) {


	if(x($_GET,'verify')) {
		$verify = $_GET['verify'];
		$hash = hash('whirlpool', $verify);

		$r = q("SELECT * FROM `user` WHERE `pwdreset` = '%s' LIMIT 1",
			dbesc($hash)
		);
		if(! count($r)) {
			notice("Request could not be verified. (You may have previously submitted it.) Password reset failed." . EOL);
			goaway($a->get_baseurl());
			return;
		}
		$uid = $r[0]['uid'];
		$username = $r[0]['username'];
		$email = $r[0]['email'];

		$new_password = autoname(6) . mt_rand(100,9999);
		$new_password_encoded = hash('whirlpool',$new_password);

		$r = q("UPDATE `user` SET `password` = '%s', `pwdreset` = ''  WHERE `uid` = %d LIMIT 1",
			dbesc($new_password_encoded),
			intval($uid)
		);
		if($r) {
			$tpl = file_get_contents('view/pwdreset.tpl');
			$o .= replace_macros($tpl,array(
				'$newpass' => $new_password,
				'$baseurl' => $a->get_baseurl()
			));
				notice("Your password has been reset." . EOL);



			$email_tpl = file_get_contents("view/passchanged_eml.tpl");
			$email_tpl = replace_macros($email_tpl, array(
			'$sitename' => $a->config['sitename'],
			'$siteurl' =>  $a->get_baseurl(),
			'$username' => $username,
			'$email' => $email,
			'$new_password' => $new_password,
			'$uid' => $newuid ));

			$res = mail($email,"Your password has changed at {$a->config['sitename']}",$email_tpl,"From: Administrator@{$_SERVER[SERVER_NAME]}");

			return $o;
		}
	
	}
	else {
		$tpl = file_get_contents('view/lostpass.tpl');

		$o .= $tpl;

		return $o;
	}

}