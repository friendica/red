<?php

define( 'UPDATE_VERSION' , 1072 );

/**
 *
 * update.php - automatic system update
 *
 * Automatically update database schemas and any other development changes such that
 * copying the latest files from the source code repository will always perform a clean
 * and painless upgrade.
 *
 * Each function in this file is named update_nnnn() where nnnn is an increasing number 
 * which began counting at 1000.
 * 
 * At the top of the file "boot.php" is a define for DB_UPDATE_VERSION. Any time there is a change
 * to the database schema or one which requires an upgrade path from the existing application,
 * the DB_UPDATE_VERSION and the UPDATE_VERSION at the top of this file are incremented.
 *
 * The current DB_UPDATE_VERSION is stored in the config area of the database. If the application starts up
 * and DB_UPDATE_VERSION is greater than the last stored build number, we will process every update function 
 * in order from the currently stored value to the new DB_UPDATE_VERSION. This is expected to bring the system 
 * up to current without requiring re-installation or manual intervention.
 *
 * Once the upgrade functions have completed, the current DB_UPDATE_VERSION is stored as the current value.
 * The DB_UPDATE_VERSION will always be one greater than the last numbered script in this file. 
 *
 * If you change the database schema, the following are required:
 *    1. Update the file database.sql to match the new schema.
 *    2. Update this file by adding a new function at the end with the number of the current DB_UPDATE_VERSION.
 *       This function should modify the current database schema and perform any other steps necessary
 *       to ensure that upgrade is silent and free from requiring interaction.
 *    3. Increment the DB_UPDATE_VERSION in boot.php *AND* the UPDATE_VERSION in this file to match it
 *    4. TEST the upgrade prior to checkin and filing a pull request.
 *
 */



function update_1000() {

	q("ALTER TABLE `item` DROP `like`, DROP `dislike` ");

	q("ALTER TABLE `item` ADD `verb` CHAR( 255 ) NOT NULL AFTER `body` ,
		ADD `object-type` CHAR( 255 ) NOT NULL AFTER `verb` ,
		ADD `object` TEXT NOT NULL AFTER `object-type` ");

	q("ALTER TABLE `intro` ADD `duplex` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `knowyou` ");
	q("ALTER TABLE `contact` ADD `duplex` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `rel` ");
 	q("ALTER TABLE `contact` CHANGE `issued-pubkey` `issued-pubkey` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");  
	q("ALTER TABLE `contact` ADD `term-date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `avatar-date`");
}

function update_1001() {
	q("ALTER TABLE `item` ADD `wall` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `type` ");
	q("ALTER TABLE `item` ADD INDEX ( `wall` )");  
}

function update_1002() {
	q("ALTER TABLE `item` ADD `gravity` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `wall` ");
}

function update_1003() {
	q("ALTER TABLE `contact` DROP `issued-pubkey` , DROP `ret-id` , DROP `ret-pubkey` ");
	q("ALTER TABLE `contact` ADD `usehub` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `ret-aes`");
	q("ALTER TABLE `contact` ADD `hub-verify` CHAR( 255 ) NOT NULL AFTER `usehub`");
	q("ALTER TABLE `contact` ADD INDEX ( `uid` ) ,  ADD INDEX ( `self` ),  ADD INDEX ( `issued-id` ),  ADD INDEX ( `dfrn-id` )"); 
	q("ALTER TABLE `contact` ADD INDEX ( `blocked` ),   ADD INDEX ( `readonly` )");
}

function update_1004() {
	q("ALTER TABLE `contact` ADD `subhub` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `usehub`");
}

function update_1005() {

	q("ALTER TABLE `user` ADD `spubkey` TEXT NOT NULL AFTER `prvkey` ,
		ADD `sprvkey` TEXT NOT NULL AFTER `spubkey`");

}

function update_1006() {

	// create 's' keys for everybody that does not have one

	$r = q("SELECT * FROM `user` WHERE `spubkey` = '' ");
	if(count($r)) {
		foreach($r as $rr) {
			$sres=openssl_pkey_new(array('encrypt_key' => false ));
			$sprvkey = '';
			openssl_pkey_export($sres, $sprvkey);
			$spkey = openssl_pkey_get_details($sres);
			$spubkey = $spkey["key"];
			$r = q("UPDATE `user` SET `spubkey` = '%s', `sprvkey` = '%s'
				WHERE `uid` = %d LIMIT 1",
				dbesc($spubkey),
				dbesc($sprvkey),
				intval($rr['uid'])
			);
		}
	}
}

function update_1007() {
	q("ALTER TABLE `user` ADD `page-flags` INT NOT NULL DEFAULT '0' AFTER `notify-flags`");
	q("ALTER TABLE `user` ADD INDEX ( `nickname` )");  
}

function update_1008() {
	q("ALTER TABLE `profile` ADD `with` TEXT NOT NULL AFTER `marital` ");
}

function update_1009() {
	q("ALTER TABLE `user` ADD `allow_location` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `default-location` ");
}

function update_1010() {
	q("ALTER TABLE `contact` ADD `lrdd` CHAR( 255 ) NOT NULL AFTER `url` ");
}

function update_1011() {
	q("ALTER TABLE `contact` ADD `nick` CHAR( 255 ) NOT NULL AFTER `name` ");
	$r = q("SELECT * FROM `contact` WHERE 1");
	if(count($r)) {
		foreach($r as $rr) {
				q("UPDATE `contact` SET `nick` = '%s' WHERE `id` = %d LIMIT 1",
					dbesc(basename($rr['url'])),
					intval($rr['id'])
				);
		}
	}
}

function update_1012() {
	q("ALTER TABLE `item` ADD `inform` MEDIUMTEXT NOT NULL AFTER `tag` ");
}

function update_1013() {
	q("ALTER TABLE `item` ADD `target-type` CHAR( 255 ) NOT NULL 
		AFTER `object` , ADD `target` TEXT NOT NULL AFTER `target-type`");
} 

function update_1014() {
	require_once('include/Photo.php');
	q("ALTER TABLE `contact` ADD `micro` TEXT NOT NULL AFTER `thumb` ");
	$r = q("SELECT * FROM `photo` WHERE `scale` = 4");
	if(count($r)) {
		foreach($r as $rr) {
			$ph = new Photo($rr['data']);
			if($ph->is_valid()) {
				$ph->scaleImage(48);
				$ph->store($rr['uid'],$rr['contact-id'],$rr['resource-id'],$rr['filename'],$rr['album'],6,(($rr['profile']) ? 1 : 0));
			}
		}
	}
	$r = q("SELECT * FROM `contact` WHERE 1");
	if(count($r)) {
		foreach($r as $rr) {		
			if(stristr($rr['thumb'],'avatar'))
				q("UPDATE `contact` SET `micro` = '%s' WHERE `id` = %d LIMIT 1",
					dbesc(str_replace('avatar','micro',$rr['thumb'])),
					intval($rr['id']));
			else
				q("UPDATE `contact` SET `micro` = '%s' WHERE `id` = %d LIMIT 1",
					dbesc(str_replace('5.jpg','6.jpg',$rr['thumb'])),
					intval($rr['id']));
		}
	}
}

function update_1015() {
	q("ALTER TABLE `item` CHANGE `body` `body` mediumtext NOT NULL");
}

function update_1016() {
	q("ALTER TABLE `user` ADD `openid` CHAR( 255 ) NOT NULL AFTER `email` ");
}

function update_1017() {

	q(" CREATE TABLE IF NOT EXISTS `clients` (
`client_id` VARCHAR( 20 ) NOT NULL ,
`pw` VARCHAR( 20 ) NOT NULL ,
`redirect_uri` VARCHAR( 200 ) NOT NULL ,
PRIMARY KEY ( `client_id` )
) ENGINE = MYISAM DEFAULT CHARSET=utf8 ");

	q(" CREATE TABLE IF NOT EXISTS `tokens` (
`id` VARCHAR( 40 ) NOT NULL ,
`client_id` VARCHAR( 20 ) NOT NULL ,
`expires` INT NOT NULL ,
`scope` VARCHAR( 200 ) NOT NULL ,
PRIMARY KEY ( `id` )
) ENGINE = MYISAM DEFAULT CHARSET=utf8 ");

	q("CREATE TABLE IF NOT EXISTS `auth_codes` (
`id` VARCHAR( 40 ) NOT NULL ,
`client_id` VARCHAR( 20 ) NOT NULL ,
`redirect_uri` VARCHAR( 200 ) NOT NULL ,
`expires` INT NOT NULL ,
`scope` VARCHAR( 250 ) NOT NULL ,
PRIMARY KEY ( `id` )
) ENGINE = MYISAM DEFAULT CHARSET=utf8 ");

}

function update_1018() {
	q("CREATE TABLE IF NOT EXISTS `queue` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`cid` INT NOT NULL ,
`created` DATETIME NOT NULL ,
`last` DATETIME NOT NULL ,
`content` MEDIUMTEXT NOT NULL
) ENGINE = MYISAM DEFAULT CHARSET=utf8 ");
}

function update_1019() {
	q("ALTER TABLE `mail` DROP `delivered`");
	q("ALTER TABLE `profile` ADD `showwith` TINYINT(1) NOT NULL DEFAULT '0' AFTER `marital` ");
}

function update_1020() {
	q("ALTER TABLE `profile` DROP `showwith`");
	q("ALTER TABLE `item` ADD `thr-parent` CHAR( 255 ) NOT NULL AFTER `parent-uri` ");
}

function update_1021() {
	q("ALTER TABLE `profile_check` ADD `sec` CHAR( 255 ) NOT NULL AFTER `dfrn_id` ");
	q("ALTER TABLE `profile_check` ADD `cid` INT(10) unsigned  NOT NULL DEFAULT '0' AFTER `uid`");
	q("ALTER TABLE `item` ADD `private` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `deny_gid` ");
}

function update_1022() {
	q("CREATE TABLE `pconfig` (
		`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`uid` INT NOT NULL DEFAULT '0',
		`cat` CHAR( 255 ) NOT NULL ,
		`k` CHAR( 255 ) NOT NULL ,
		`v` MEDIUMTEXT NOT NULL
		) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci ");
}

function update_1023() {
	q("ALTER TABLE `user` ADD `register_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `timezone` ,
	ADD `login_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `register_date` ");
}

function update_1024() {
	q("ALTER TABLE `profile` ADD `keywords` TEXT NOT NULL AFTER `religion` ");
}

function update_1025() {
	q("ALTER TABLE `user` ADD `maxreq` int(11) NOT NULL DEFAULT '10' AFTER `pwdreset` ");
}

function update_1026() {
	q("CREATE TABLE IF NOT EXISTS `hook` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`hook` CHAR( 255 ) NOT NULL ,
	`file` CHAR( 255 ) NOT NULL ,
	`function` CHAR( 255 ) NOT NULL
	) ENGINE = MYISAM DEFAULT CHARSET=utf8 ");
}


function update_1027() {
	q("CREATE TABLE IF NOT EXISTS `addon` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`name` CHAR( 255 ) NOT NULL ,
	`version` CHAR( 255 ) NOT NULL ,
	`installed` TINYINT( 1 ) NOT NULL DEFAULT '0' 
	) ENGINE = MYISAM DEFAULT CHARSET=utf8 ");
}

function update_1028() {
	q("ALTER TABLE `user` ADD `openidserver` text NOT NULL AFTER `deny_gid` ");
}

function update_1029() {
	q("ALTER TABLE `contact` ADD `info` MEDIUMTEXT NOT NULL AFTER `reason` ");
}

function update_1030() {
	q("ALTER TABLE `contact` ADD `bdyear` CHAR( 4 ) NOT NULL COMMENT 'birthday notify flag' AFTER `profile-id` ");

	q("CREATE TABLE IF NOT EXISTS `event` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`uid` INT NOT NULL ,
	`cid` INT NOT NULL ,
	`created` DATETIME NOT NULL ,
	`edited` DATETIME NOT NULL ,
	`start` DATETIME NOT NULL ,
	`finish` DATETIME NOT NULL ,
	`desc` TEXT NOT NULL ,
	`location` TEXT NOT NULL ,
	`type` CHAR( 255 ) NOT NULL ,
	`adjust` TINYINT( 1 ) NOT NULL DEFAULT '1',
	`allow_cid` MEDIUMTEXT NOT NULL ,
	`allow_gid` MEDIUMTEXT NOT NULL ,
	`deny_cid` MEDIUMTEXT NOT NULL ,
	`deny_gid` MEDIUMTEXT NOT NULL
	) ENGINE = MYISAM DEFAULT CHARSET=utf8 ");


}

function update_1031() {
	// Repair any bad links that slipped into the item table
	$r = q("SELECT `id`, `object` FROM `item` WHERE `object` != '' ");
	if($r && count($r)) {
		foreach($r as $rr) {
			if(strstr($rr['object'],'type=&quot;http')) {
				q("UPDATE `item` SET `object` = '%s' WHERE `id` = %d LIMIT 1",
					dbesc(str_replace('type=&quot;http','href=&quot;http',$rr['object'])),
					intval($rr['id'])
				);
			}
		}
	}
}
	
function update_1032() {
	q("ALTER TABLE `profile` ADD `pdesc` CHAR( 255 ) NOT NULL AFTER `name` ");
}

function update_1033() {
	q("CREATE TABLE IF NOT EXISTS `cache` (
 		`k` CHAR( 255 ) NOT NULL PRIMARY KEY ,
 		`v` TEXT NOT NULL,
 		`updated` DATETIME NOT NULL
		) ENGINE = MYISAM DEFAULT CHARSET=utf8 ");
}


function update_1034() {

	// If you have any of these parent-less posts they can cause problems, and 
	// we need to delete them. You can't see them anyway.
	// Legitimate items will usually get re-created on the next 
	// pull from the hub.
	// But don't get rid of a post that may have just come in 
	// and may not yet have the parent id set.

	q("DELETE FROM `item` WHERE `parent` = 0 AND `created` < UTC_TIMESTAMP() - INTERVAL 2 MINUTE");

}


function update_1035() {

	q("ALTER TABLE `contact` ADD `success_update` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `last-update` ");

}

function update_1036() {

	$r = dbq("SELECT * FROM `contact` WHERE `network` = 'dfrn' && `photo` LIKE '%include/photo%' ");
	if(count($r)) {
		foreach($r as $rr) {
			q("UPDATE `contact` SET `photo` = '%s', `thumb` = '%s', `micro` = '%s' WHERE `id` = %d LIMIT 1",
				dbesc(str_replace('include/photo','photo',$rr['photo'])),
				dbesc(str_replace('include/photo','photo',$rr['thumb'])),
				dbesc(str_replace('include/photo','photo',$rr['micro'])),
				intval($rr['id']));
		}
	}
}

function update_1037() {

	q("ALTER TABLE `contact` CHANGE `lrdd` `alias` CHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ");

}

function update_1038() {
 q("ALTER TABLE `item` ADD `plink` CHAR( 255 ) NOT NULL AFTER `target` ");
}

function update_1039() {
	q("ALTER TABLE `addon` ADD `timestamp` BIGINT NOT NULL DEFAULT '0'");
}


function update_1040() {

	q("CREATE TABLE IF NOT EXISTS `fcontact` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`url` CHAR( 255 ) NOT NULL ,
	`name` CHAR( 255 ) NOT NULL ,
	`photo` CHAR( 255 ) NOT NULL
	) ENGINE = MYISAM DEFAULT CHARSET=utf8 ");

	q("CREATE TABLE IF NOT EXISTS `ffinder` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`uid` INT UNSIGNED NOT NULL ,
	`cid` INT UNSIGNED NOT NULL ,
	`fid` INT UNSIGNED NOT NULL
	) ENGINE = MYISAM DEFAULT CHARSET=utf8 ");

}

function update_1041() {
	q("ALTER TABLE `profile` CHANGE `keywords` `prv_keywords` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ");
	q("ALTER TABLE `profile` ADD `pub_keywords` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `religion` ");
}

function update_1042() {
	q("ALTER TABLE `user` ADD `expire` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `maxreq` ");
}


function update_1043() {
	q("ALTER TABLE `user` ADD `blockwall` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `blocked` ");
}

function update_1044() {
	q("ALTER TABLE `profile` ADD FULLTEXT ( `pub_keywords` ) ");
	q("ALTER TABLE `profile` ADD FULLTEXT ( `prv_keywords` ) ");
}

function update_1045() {
	q("ALTER TABLE `user` ADD `language` CHAR( 16 ) NOT NULL DEFAULT 'en' AFTER `timezone` ");
}

function update_1046() {
	q("ALTER TABLE `item` ADD `attach` MEDIUMTEXT NOT NULL AFTER `tag` ");
}

function update_1047() {
	q("ALTER TABLE `contact` ADD `writable` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `readonly` ");
}

function update_1048() {
	q("UPDATE `contact` SET `writable` = 1 WHERE `network` = 'stat' AND `notify` != '' ");
}

function update_1049() {
	q("CREATE TABLE `mailacct` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`uid` INT NOT NULL,
	`server` CHAR( 255 ) NOT NULL ,
	`user` CHAR( 255 ) NOT NULL ,
	`pass` CHAR( 255 ) NOT NULL ,
	`reply_to` CHAR( 255 ) NOT NULL ,
	`last_check` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'
	) ENGINE = MYISAM ");
}

function update_1050() {
	q("CREATE TABLE `attach` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`uid` INT NOT NULL ,
	`filetype` CHAR( 64 ) NOT NULL ,
	`filesize` INT NOT NULL ,
	`data` LONGBLOB NOT NULL ,
	`created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
	`edited` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
	`allow_cid` MEDIUMTEXT NOT NULL ,
	`allow_gid` MEDIUMTEXT NOT NULL ,
	`deny_cid` MEDIUMTEXT NOT NULL ,
	`deny_gid` MEDIUMTEXT NOT NULL
	) ENGINE = MYISAM ");

}

function update_1051() {
	q("ALTER TABLE `mailacct` ADD `port` INT NOT NULL AFTER `server` ,
		ADD `ssltype` CHAR( 16 ) NOT NULL AFTER `port` ,
		ADD `mailbox` CHAR( 255 ) NOT NULL AFTER `ssltype` ");

	q("ALTER TABLE `contact` ADD `addr` CHAR( 255 ) NOT NULL AFTER `url` ");
}

function update_1052() {
	q("ALTER TABLE `mailacct` CHANGE `pass` `pass` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
	q("ALTER TABLE `mailacct` ADD `pubmail` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `reply_to` ");
	q("ALTER TABLE `item` ADD `pubmail` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `private` ");
}


function update_1053() {
	q("ALTER TABLE `item` ADD `extid` CHAR( 255 ) NOT NULL AFTER `parent-uri` , ADD INDEX ( `extid` ) ");
}

function update_1054() {
	q("ALTER TABLE `register` ADD `language` CHAR( 16 ) NOT NULL AFTER `password` ");
}

function update_1055() {
	q("ALTER TABLE `profile` ADD `hidewall` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `hide-friends` ");
}

function update_1056() {
	q("ALTER TABLE `attach` ADD `hash` CHAR( 64 ) NOT NULL AFTER `uid` ");
}

function update_1057() {
	q("ALTER TABLE `attach` ADD `filename` CHAR( 255 ) NOT NULL AFTER `hash` ");
}

function update_1058() {
	q("ALTER TABLE `item` ADD `event-id` INT NOT NULL AFTER `resource-id` ");
}

function update_1059() {
	q("ALTER TABLE `queue` ADD `network` CHAR( 32 ) NOT NULL AFTER `cid` ");
}

function update_1060() {
	q("ALTER TABLE `event` ADD `uri` CHAR( 255 ) NOT NULL AFTER `cid` ");
}

function update_1061() {
	q("ALTER TABLE `event` ADD `nofinish` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `type` ");
}

function update_1062() {
	q("ALTER TABLE `user` ADD `prvnets` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `page-flags` ");
}
function update_1063() {
	q("ALTER TABLE `addon` ADD `plugin_admin` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `timestamp` ");
}

function update_1064() {
	q("ALTER TABLE `item` ADD `app` CHAR( 255 ) NOT NULL AFTER `body` ");
}

function update_1065() {
	q("ALTER TABLE `intro` ADD `fid` INT NOT NULL DEFAULT '0' AFTER `uid`");
}

function update_1066() {
	$r = q("ALTER TABLE `item` ADD `received` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `edited` ");
	if($r)
		q("ALTER TABLE `item` ADD INDEX ( `received` ) ");

	$r = q("UPDATE `item` SET `received` = `edited` WHERE 1");
}

function update_1067() {
	q("ALTER TABLE `ffinder` ADD `type` CHAR( 16 ) NOT NULL AFTER `id` ,
	ADD `note` TEXT NOT NULL AFTER `type` ");
}

function update_1068() {
	// 1067 was short-sighted. Undo it.
	q("ALTER TABLE `ffinder` DROP `type` , DROP `note` ");

	// and do this instead.

	q("CREATE TABLE IF NOT EXISTS `fsuggest` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`uid` INT NOT NULL ,
	`cid` INT NOT NULL ,
	`name` CHAR( 255 ) NOT NULL ,
	`url` CHAR( 255 ) NOT NULL ,
	`photo` CHAR( 255 ) NOT NULL ,
	`note` TEXT NOT NULL ,
	`created` DATETIME NOT NULL 
	) ENGINE = MYISAM DEFAULT CHARSET=utf8");

}

function update_1069() {
	q("ALTER TABLE `fsuggest` ADD `request` CHAR( 255 ) NOT NULL AFTER `url` ");
	q("ALTER TABLE `fcontact` ADD `request` CHAR( 255 ) NOT NULL AFTER `photo` ");
}

// mail body needs to accomodate private photos

function update_1070() {
	q("ALTER TABLE `mail` CHANGE `body` `body` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ");
}

function update_1071() {
	q("ALTER TABLE `photo` ADD INDEX ( `uid` ) ");
	q("ALTER TABLE `photo` ADD INDEX ( `resource-id` ) ");
	q("ALTER TABLE `photo` ADD INDEX ( `album` ) ");
	q("ALTER TABLE `photo` ADD INDEX ( `scale` ) ");
	q("ALTER TABLE `photo` ADD INDEX ( `profile` ) ");

}
