-- Run this with mysql or import into phpmyadmin if you installed mistpark between Sep22 and Oct 6 2010.
-- The database schema was missing some updates

ALTER TABLE `item` DROP `like`, DROP `dislike` ;

ALTER TABLE `item` ADD `verb` CHAR( 255 ) NOT NULL AFTER `body` ,
		ADD `object-type` CHAR( 255 ) NOT NULL AFTER `verb` ,
		ADD `object` TEXT NOT NULL AFTER `object-type` ;

ALTER TABLE `intro` ADD `duplex` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `knowyou` ;
ALTER TABLE `contact` ADD `duplex` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `rel` ;
ALTER TABLE `contact` ADD `term-date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `avatar-date`;

