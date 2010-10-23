<?php

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