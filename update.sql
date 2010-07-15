 ALTER TABLE `item` CHANGE `allow_uid` `allow_cid` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
CHANGE `deny_uid` `deny_cid` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;

 ALTER TABLE `item` CHANGE `last-child` `last-child` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1';

alter table `item` insert `remote-id` char( 255 ) character set utf-8 collate utf8_general_ci NOT NULL;
 