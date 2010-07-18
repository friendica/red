 ALTER TABLE `item` CHANGE `allow_uid` `allow_cid` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
CHANGE `deny_uid` `deny_cid` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;

 ALTER TABLE `item` CHANGE `last-child` `last-child` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1';

alter table `item` insert `remote-id` char( 255 ) character set utf-8 collate utf8_general_ci NOT NULL;

ALTER TABLE `item` ADD `remote-name` CHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `remote-id` ,
ADD `remote-link` CHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `remote-name` ,
ADD `remote-avatar` CHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `remote-link` ;

ALTER TABLE `item` ADD `owner-name` CHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `contact-id` ,
ADD `owner-link` CHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `owner-name` ,
ADD `owner-avatar` CHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `owner-link` ;

ALTER TABLE `user` ADD `pwdreset` CHAR( 255 ) NOT NULL AFTER `blocked` ;

 ALTER TABLE `challenge` ADD `cmd` CHAR( 255 ) NOT NULL AFTER `expire` ,
ADD `url` CHAR( 255 ) NOT NULL AFTER `cmd` ,
ADD `last_update` CHAR( 255 ) NOT NULL AFTER `url` ;

