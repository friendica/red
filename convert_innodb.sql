

ALTER TABLE `profile` DROP INDEX `pub_keywords` ; 
ALTER TABLE `profile` DROP INDEX `prv_keywords` ;

ALTER TABLE `item` DROP INDEX `title` ;
ALTER TABLE `item` DROP INDEX `body` ;
ALTER TABLE `item` DROP INDEX `allow_cid` ;
ALTER TABLE `item` DROP INDEX `allow_gid` ;
ALTER TABLE `item` DROP INDEX `deny_cid` ;
ALTER TABLE `item` DROP INDEX `deny_gid` ;
ALTER TABLE `item` DROP INDEX `tag` ;
ALTER TABLE `item` DROP INDEX `file` ;


SELECT CONCAT('ALTER TABLE ',table_schema,'.',table_name,' engine=InnoDB;') 
FROM information_schema.tables 
WHERE engine = 'MyISAM';

