<?php

define( 'UPDATE_VERSION' , 1036 );

/**
 *
 * update.php - automatic system update
 *
 * Automatically update database schemas and any other development changes such that
 * copying the latest files from the source code repository will always perform a clean
 * and painless upgrade.
 *
 * Each function in this file is named update_rnnnn() where nnnn is an increasing number 
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

function update_r1000() {
	$r = q("ALTER TABLE `channel` ADD `channel_a_delegate` TINYINT( 3 ) UNSIGNED NOT NULL DEFAULT '0', ADD INDEX ( `channel_a_delegate` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1001() {
	$r = q("CREATE TABLE if not exists `verify` (
		`id` INT(10) UNSIGNED NOT NULL ,
		`channel` INT(10) UNSIGNED NOT NULL DEFAULT '0',
		`type` CHAR( 32 ) NOT NULL DEFAULT '',
		`token` CHAR( 255 ) NOT NULL DEFAULT '',
		`meta` CHAR( 255 ) NOT NULL DEFAULT '',
		`created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY ( `id` )
		) ENGINE = MYISAM DEFAULT CHARSET=utf8");

	$r2 = q("alter table `verify` add index (`channel`), add index (`type`), add index (`token`),
		add index (`meta`), add index (`created`)");

	if($r && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1002() {
	$r = q("ALTER TABLE `event` CHANGE `account` `aid` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'");
	$r2 = q("alter table `event` drop index `account`, add index (`aid`)");

	q("drop table contact");
	q("drop table deliverq");

	if($r && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1003() {
	$r = q("ALTER TABLE `xchan` ADD `xchan_flags` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `xchan_network` ,
ADD INDEX ( `xchan_flags` ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1004() {
	$r = q("CREATE TABLE if not exists `site` (
`site_url` CHAR( 255 ) NOT NULL ,
`site_flags` INT NOT NULL DEFAULT '0',
`site_update` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
`site_directory` CHAR( 255 ) NOT NULL DEFAULT '',
PRIMARY KEY ( `site_url` )
) ENGINE = MYISAM DEFAULT CHARSET=utf8");

	$r2 = q("alter table site add index (site_flags), add index (site_update), add index (site_directory) ");

	if($r && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1005() {
	q("drop table guid");
	q("drop table `notify-threads`");
	return UPDATE_SUCCESS;
}

function update_r1006() {

	$r = q("CREATE TABLE IF NOT EXISTS `xprof` (
  `xprof_hash` char(255) NOT NULL,
  `xprof_desc` char(255) NOT NULL DEFAULT '',
  `xprof_dob` char(12) NOT NULL DEFAULT '',
  `xprof_gender` char(255) NOT NULL DEFAULT '',
  `xprof_marital` char(255) NOT NULL DEFAULT '',
  `xprof_sexual` char(255) NOT NULL DEFAULT '',
  `xprof_locale` char(255) NOT NULL DEFAULT '',
  `xprof_region` char(255) NOT NULL DEFAULT '',
  `xprof_postcode` char(32) NOT NULL DEFAULT '',
  `xprof_country` char(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`xprof_hash`),
  KEY `xprof_desc` (`xprof_desc`),
  KEY `xprof_dob` (`xprof_dob`),
  KEY `xprof_gender` (`xprof_gender`),
  KEY `xprof_marital` (`xprof_marital`),
  KEY `xprof_sexual` (`xprof_sexual`),
  KEY `xprof_locale` (`xprof_locale`),
  KEY `xprof_region` (`xprof_region`),
  KEY `xprof_postcode` (`xprof_postcode`),
  KEY `xprof_country` (`xprof_country`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8");

	$r2 = q("CREATE TABLE IF NOT EXISTS `xtag` (
  `xtag_hash` char(255) NOT NULL,
  `xtag_term` char(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`xtag_hash`),
  KEY `xtag_term` (`xtag_term`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8");

	if($r && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1007() {
	$r = q("ALTER TABLE `channel` ADD `channel_r_storage` INT UNSIGNED NOT NULL DEFAULT '128', ADD `channel_w_storage` INT UNSIGNED NOT NULL DEFAULT '128', add index ( channel_r_storage ), add index ( channel_w_storage )");

	if($r && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1008() {
	$r = q("alter table profile drop prv_keywords,  CHANGE `pub_keywords` `keywords` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, drop index pub_keywords");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1009() {
	$r = q("ALTER TABLE `xprof` ADD `xprof_keywords` TEXT NOT NULL");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1010() {
	$r = q("ALTER TABLE `abook` ADD `abook_dob` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `abook_connnected` ,
ADD INDEX ( `abook_dob` )");

	$r2 = q("ALTER TABLE `profile` ADD `dob_tz` CHAR( 255 ) NOT NULL DEFAULT 'UTC' AFTER `dob`");

	if($r && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1011() {
	$r = q("ALTER TABLE `item` ADD `expires` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `edited` ,
ADD INDEX ( `expires` )");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}
	
function update_r1012() {
	$r = q("ALTER TABLE `xchan` ADD `xchan_connurl` CHAR( 255 ) NOT NULL DEFAULT '' AFTER `xchan_url` ,
ADD INDEX ( `xchan_connurl` )");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1013() {
	$r = q("CREATE TABLE if not exists `xlink` (
`xlink_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`xlink_xchan` CHAR( 255 ) NOT NULL DEFAULT '',
`xlink_link` CHAR( 255 ) NOT NULL DEFAULT '',
`xlink_updated` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE = MYISAM DEFAULT CHARSET=utf8");

	$r2 = q("alter table xlink add index ( xlink_xchan ), add index ( xlink_link ), add index ( xlink_updated ) ");
	if($r && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1014() {
	$r = q("ALTER TABLE `verify` CHANGE `id` `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1015() {
	$r = q("ALTER TABLE `channel` ADD `channel_r_pages` INT UNSIGNED NOT NULL DEFAULT '128',
ADD `channel_w_pages` INT UNSIGNED NOT NULL DEFAULT '128'");

	$r2 = q("ALTER TABLE `channel` ADD INDEX ( `channel_r_pages` ) , ADD INDEX ( `channel_w_pages` ) ");

	if($r && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1016() {

	$r = q("CREATE TABLE IF NOT EXISTS `menu` (
  `menu_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `menu_channel_id` int(10) unsigned NOT NULL DEFAULT '0',
  `menu_desc` char(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`menu_id`),
  KEY `menu_channel_id` (`menu_channel_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ");

	$r2 = q("CREATE TABLE IF NOT EXISTS `menu_item` (
  `mitem_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mitem_link` char(255) NOT NULL DEFAULT '',
  `mitem_desc` char(255) NOT NULL DEFAULT '',
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  `mitem_channel_id` int(10) unsigned NOT NULL,
  `mitem_menu_id` int(10) unsigned NOT NULL DEFAULT '0',
  `mitem_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`mitem_id`),
  KEY `mitem_channel_id` (`mitem_channel_id`),
  KEY `mitem_menu_id` (`mitem_menu_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ");


	if($r && $r2)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1017() {
	$r = q("ALTER TABLE `event` CHANGE `cid` `event_xchan` CHAR( 255 ) NOT NULL DEFAULT '', ADD INDEX ( `event_xchan` ), drop index cid  ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1018() {
	$r = q("ALTER TABLE `event` ADD `event_hash` CHAR( 255 ) NOT NULL DEFAULT '' AFTER `event_xchan` ,
ADD INDEX ( `event_hash` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1019() {
	$r = q("ALTER TABLE `event` DROP `message_id` ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1020() {
	$r = q("alter table photo drop `contact-id`, drop guid, drop index `resource-id`, add index ( `resource_id` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1021() {

	$r = q("ALTER TABLE `abook` CHANGE `abook_connnected` `abook_connected` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
		drop index `abook_connnected`, add index ( `abook_connected` ) ");
	
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1022() {
	$r = q("alter table attach add index ( filename ), add index ( filetype ), add index ( filesize ), add index ( created ), add index ( edited ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1023() {
	$r = q("ALTER TABLE `item` ADD `revision` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `lang` , add index ( revision ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1024() {
	$r = q("ALTER TABLE `attach` ADD `revision` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `filesize` ,
ADD INDEX ( `revision` ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1025() {
	$r = q("ALTER TABLE `attach` ADD `folder` CHAR( 64 ) NOT NULL DEFAULT '' AFTER `revision` ,
ADD `flags` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `folder` , add index ( folder ), add index ( flags )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1026() {
	$r = q("ALTER TABLE `item` ADD `mimetype` CHAR( 255 ) NOT NULL DEFAULT '' AFTER `author_xchan` ,
ADD INDEX ( `mimetype` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1027() {
	$r = q("ALTER TABLE `abook` ADD `abook_rating` INT NOT NULL DEFAULT '0' AFTER `abook_closeness` ,
ADD INDEX ( `abook_rating` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1028() {
	$r = q("ALTER TABLE `xlink` ADD `xlink_rating` INT NOT NULL DEFAULT '0' AFTER `xlink_link` ,
ADD INDEX ( `xlink_rating` ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1029() {
	$r = q("ALTER TABLE `channel` ADD `channel_deleted` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `channel_pageflags` ,
ADD INDEX ( `channel_deleted` ) ");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1030() {
	$r = q("CREATE TABLE IF NOT EXISTS `issue` (
`issue_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`issue_created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
`issue_updated` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
`issue_assigned` CHAR( 255 ) NOT NULL ,
`issue_priority` INT NOT NULL ,
`issue_status` INT NOT NULL ,
`issue_component` CHAR( 255 ) NOT NULL,
KEY `issue_created` (`issue_created`),
KEY `issue_updated` (`issue_updated`),
KEY `issue_assigned` (`issue_assigned`),
KEY `issue_priority` (`issue_priority`),
KEY `issue_status` (`issue_status`),
KEY `issue_component` (`issue_component`)
) ENGINE = MYISAM DEFAULT CHARSET=utf8");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1031() {
	$r = q("ALTER TABLE `account` ADD `account_external` CHAR( 255 ) NOT NULL DEFAULT '' AFTER `account_email` ,
ADD INDEX ( `account_external` )");
	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}


function update_r1032() {
	$r = q("CREATE TABLE if not exists `xign` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`uid` INT NOT NULL DEFAULT '0',
`xchan` CHAR( 255 ) NOT NULL DEFAULT '',
KEY `uid` (`uid`),
KEY `xchan` (`xchan`)
) ENGINE = MYISAM DEFAULT CHARSET = utf8");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1033() {
	$r = q("CREATE TABLE if not exists `shares` (
`share_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`share_type` INT NOT NULL DEFAULT '0',
`share_target` INT UNSIGNED NOT NULL DEFAULT '0',
`share_xchan` CHAR( 255 ) NOT NULL DEFAULT '',
KEY `share_type` (`share_type`),
KEY `share_target` (`share_target`),
KEY `share_xchan` (`share_xchan`)
) ENGINE = MYISAM DEFAULT CHARSET = utf8");

	// if these fail don't bother reporting it

	q("drop table gcign");
	q("drop table gcontact");
	q("drop table glink");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1034() {
	$r = q("CREATE TABLE if not exists `updates` (
`ud_hash` CHAR( 128 ) NOT NULL ,
`ud_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
PRIMARY KEY ( `ud_hash` ),
KEY `ud_date` ( `ud_date` )
) ENGINE = MYISAM DEFAULT CHARSET = utf8");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}

function update_r1035() {
	$r = q("CREATE TABLE if not exists `xconfig` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`xchan` CHAR( 255 ) NOT NULL ,
`cat` CHAR( 255 ) NOT NULL ,
`k` CHAR( 255 ) NOT NULL ,
`v` MEDIUMTEXT NOT NULL,
KEY `xchan` ( `xchan` ),
KEY `cat` ( `cat` ),
KEY `k` ( `k` )
) ENGINE = MYISAM DEFAULT CHARSET = utf8");

	if($r)
		return UPDATE_SUCCESS;
	return UPDATE_FAILED;
}
