<?php

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
 * At the top of the file "boot.php" is a define for BUILD_ID. Any time there is a change
 * to the database schema or one which requires an upgrade path from the existing application,
 * the BUILD_ID is incremented.
 *
 * The current BUILD_ID is stored in the config area of the database. If the application starts up
 * and BUILD_ID is greater than the last stored build number, we will process every update function 
 * in order from the currently stored value to the new BUILD_ID. This is expected to bring the system 
 * up to current without requiring re-installation or manual intervention.
 *
 * Once the upgrade functions have completed, the current BUILD_ID is stored as the current value.
 * The BUILD_ID will always be one greater than the last numbered script in this file. 
 *
 * If you change the database schema, the following are required:
 *    1. Update the file database.sql to match the new schema.
 *    2. Update this file by adding a new function at the end with the number of the current BUILD_ID.
 *       This function should modify the current database schema and perform any other steps necessary
 *       to ensure that upgrade is silent and free from requiring interaction.
 *    3. Increment the BUILD_ID in boot.php
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
	
