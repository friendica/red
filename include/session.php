<?php
/**
 * @file include/session.php
 *
 * @brief This file includes session related functions.
 *
 * Session management functions. These provide database storage of PHP
 * session info.
 */

$session_exists = 0;
$session_expire = 180000;

function new_cookie($time) {
	$old_sid = session_id();

// ??? This shouldn't have any effect if called after session_start()
// We probably need to set the session expiration and change the PHPSESSID cookie.

	session_set_cookie_params($time);
	session_regenerate_id(false);

	q("UPDATE session SET sid = '%s' WHERE sid = '%s'",
			dbesc(session_id()),
			dbesc($old_sid)
	);

	if (x($_COOKIE, 'jsAvailable')) {
		if ($time) {
			$expires = time() + $time;
		} else {
			$expires = 0;
		}
		setcookie('jsAvailable', $_COOKIE['jsAvailable'], $expires);
	}
}


function ref_session_open ($s, $n) {
	return true;
}


function ref_session_read ($id) {
	global $session_exists;
	if(x($id))
		$r = q("SELECT `data` FROM `session` WHERE `sid`= '%s'", dbesc($id));

	if(count($r)) {
		$session_exists = true;
		return $r[0]['data'];
	}

	return '';
}


function ref_session_write ($id, $data) {
	global $session_exists, $session_expire;

	if(! $id || ! $data) {
		return false;
	}

	$expire = time() + $session_expire;
	$default_expire = time() + 300;

	if($session_exists) {
		q("UPDATE `session`
				SET `data` = '%s', `expire` = '%s' WHERE `sid` = '%s'",
				dbesc($data),
				dbesc($expire),
				dbesc($id)
		);
	} else {
		q("INSERT INTO `session` (sid, expire, data) values ('%s', '%s', '%s')",
				//SET `sid` = '%s', `expire` = '%s', `data` = '%s'",
				dbesc($id),
				dbesc($default_expire),
				dbesc($data)
		);
	}

	return true;
}


function ref_session_close() {
	return true;
}


function ref_session_destroy ($id) {
	q("DELETE FROM `session` WHERE `sid` = '%s'", dbesc($id));
	return true;
}


function ref_session_gc($expire) {
	q("DELETE FROM session WHERE expire < %d", dbesc(time()));
	if (! get_config('system', 'innodb'))
		db_optimizetable('session');

	return true;
}

$gc_probability = 50;

ini_set('session.gc_probability', $gc_probability);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);

/*
 * PHP function which sets our user-level session storage functions.
 */
session_set_save_handler(
		'ref_session_open',
		'ref_session_close',
		'ref_session_read',
		'ref_session_write',
		'ref_session_destroy',
		'ref_session_gc'
);