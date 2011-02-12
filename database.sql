-- phpMyAdmin SQL Dump
-- version 2.11.9.4
-- http://www.phpmyadmin.net
--

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
--

-- --------------------------------------------------------

--
-- Table structure for table `challenge`
--

CREATE TABLE IF NOT EXISTS `challenge` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `challenge` char(255) NOT NULL,
  `dfrn-id` char(255) NOT NULL,
  `expire` int(11) NOT NULL,
  `type` char(255) NOT NULL,
  `last_update` char(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cat` char(255) NOT NULL,
  `k` char(255) NOT NULL,
  `v` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MYISAM DEFAULT CHARSET=utf8;




--
-- Table structure for table `contact`
--

CREATE TABLE IF NOT EXISTS `contact` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL COMMENT 'owner uid',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `self` tinyint(1) NOT NULL DEFAULT '0',
  `rel` tinyint(1) NOT NULL DEFAULT '0',
  `duplex` tinyint(1) NOT NULL DEFAULT '0',
  `network` char(255) NOT NULL,
  `name` char(255) NOT NULL,
  `nick` char(255) NOT NULL,
  `photo` text NOT NULL, 
  `thumb` text NOT NULL,
  `micro` text NOT NULL,
  `site-pubkey` text NOT NULL,
  `issued-id` char(255) NOT NULL,
  `dfrn-id` char(255) NOT NULL,
  `url` char(255) NOT NULL,
  `alias` char(255) NOT NULL,
  `pubkey` text NOT NULL,
  `prvkey` text NOT NULL,
  `request` text NOT NULL,
  `notify` text NOT NULL,
  `poll` text NOT NULL,
  `confirm` text NOT NULL,
  `aes_allow` tinyint(1) NOT NULL DEFAULT '0',
  `ret-aes` tinyint(1) NOT NULL DEFAULT '0',
  `usehub` tinyint(1) NOT NULL DEFAULT '0',
  `subhub` tinyint(1) NOT NULL DEFAULT '0',
  `hub-verify` char(255) NOT NULL,
  `last-update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `success_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `name-date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `uri-date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `avatar-date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `term-date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `priority` tinyint(3) NOT NULL,
  `blocked` tinyint(1) NOT NULL DEFAULT '1',
  `readonly` tinyint(1) NOT NULL DEFAULT '0',
  `pending` tinyint(1) NOT NULL DEFAULT '1',
  `rating` tinyint(1) NOT NULL DEFAULT '0',
  `reason` text NOT NULL,
  `info` mediumtext NOT NULL,
  `profile-id` int(11) NOT NULL DEFAULT '0',
  `bdyear` CHAR( 4 ) NOT NULL COMMENT 'birthday notify flag',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `self` (`self`),
  KEY `issued-id` (`issued-id`),
  KEY `dfrn-id` (`dfrn-id`),
  KEY `blocked` (`blocked`),
  KEY `readonly` (`readonly`)  
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `group`
--

CREATE TABLE IF NOT EXISTS `group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `name` char(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `group_member`
--

CREATE TABLE IF NOT EXISTS `group_member` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `gid` int(10) unsigned NOT NULL,
  `contact-id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `intro`
--

CREATE TABLE IF NOT EXISTS `intro` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `contact-id` int(11) NOT NULL,
  `knowyou` tinyint(1) NOT NULL,
  `duplex` tinyint(1) NOT NULL DEFAULT '0',
  `note` text NOT NULL,
  `hash` char(255) NOT NULL,
  `datetime` datetime NOT NULL,
  `blocked` tinyint(1) NOT NULL DEFAULT '1',
  `ignore` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `item`
--

CREATE TABLE IF NOT EXISTS `item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uri` char(255) NOT NULL,
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `contact-id` int(10) unsigned NOT NULL DEFAULT '0',
  `type` char(255) NOT NULL,
  `wall` tinyint(1) NOT NULL DEFAULT '0',
  `gravity` tinyint(1) NOT NULL DEFAULT '0',
  `parent` int(10) unsigned NOT NULL DEFAULT '0',
  `parent-uri` char(255) NOT NULL,
  `thr-parent` char(255) NOT NULL,
  `created` datetime NOT NULL,
  `edited` datetime NOT NULL,
  `changed` datetime NOT NULL,
  `owner-name` char(255) NOT NULL,
  `owner-link` char(255) NOT NULL,
  `owner-avatar` char(255) NOT NULL,
  `author-name` char(255) NOT NULL,
  `author-link` char(255) NOT NULL,
  `author-avatar` char(255) NOT NULL,
  `title` char(255) NOT NULL,
  `body` mediumtext NOT NULL,
  `verb` char(255) NOT NULL,
  `object-type` char(255) NOT NULL,
  `object` text NOT NULL,
  `target-type` char(255) NOT NULL,
  `target` text NOT NULL,
  `resource-id` char(255) NOT NULL,
  `tag` mediumtext NOT NULL,
  `inform` mediumtext NOT NULL,
  `location` char(255) NOT NULL,
  `coord` char(255) NOT NULL,
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  `private` tinyint(1) NOT NULL DEFAULT '0',
  `visible` tinyint(1) NOT NULL DEFAULT '0',
  `unseen` tinyint(1) NOT NULL DEFAULT '1',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `last-child` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `uri` (`uri`),
  KEY `uid` (`uid`),
  KEY `contact-id` (`contact-id`),
  KEY `type` (`type`),
  KEY `wall` (`wall`),
  KEY `parent` (`parent`),
  KEY `parent-uri` (`parent-uri`),
  KEY `created` (`created`),
  KEY `edited` (`edited`),
  KEY `visible` (`visible`),
  KEY `deleted` (`deleted`),
  KEY `last-child` (`last-child`),
  KEY `unseen` (`unseen`),
  FULLTEXT KEY `title` (`title`),
  FULLTEXT KEY `body` (`body`),
  FULLTEXT KEY `allow_cid` (`allow_cid`),
  FULLTEXT KEY `allow_gid` (`allow_gid`),
  FULLTEXT KEY `deny_cid` (`deny_cid`),
  FULLTEXT KEY `deny_gid` (`deny_gid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mail`
--

CREATE TABLE IF NOT EXISTS `mail` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `from-name` char(255) NOT NULL,
  `from-photo` char(255) NOT NULL,
  `from-url` char(255) NOT NULL,
  `contact-id` char(255) NOT NULL,
  `title` char(255) NOT NULL,
  `body` text NOT NULL,
  `seen` tinyint(1) NOT NULL,
  `replied` tinyint(1) NOT NULL,
  `uri` char(255) NOT NULL,
  `parent-uri` char(255) NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `photo`
--

CREATE TABLE IF NOT EXISTS `photo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `contact-id` int(10) unsigned NOT NULL,
  `resource-id` char(255) NOT NULL,
  `created` datetime NOT NULL,
  `edited` datetime NOT NULL,
  `title` char(255) NOT NULL,
  `desc` text NOT NULL,
  `album` char(255) NOT NULL,
  `filename` char(255) NOT NULL,
  `height` smallint(6) NOT NULL,
  `width` smallint(6) NOT NULL,
  `data` mediumblob NOT NULL,
  `scale` tinyint(3) NOT NULL,
  `profile` tinyint(1) NOT NULL DEFAULT '0',
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `profile`
--

CREATE TABLE IF NOT EXISTS `profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `profile-name` char(255) NOT NULL,
  `is-default` tinyint(1) NOT NULL DEFAULT '0',
  `hide-friends` tinyint(1) NOT NULL DEFAULT '0',
  `name` char(255) NOT NULL,
  `pdesc` char(255) NOT NULL,
  `dob` char(32) NOT NULL DEFAULT '0000-00-00',
  `address` char(255) NOT NULL,
  `locality` char(255) NOT NULL,
  `region` char(255) NOT NULL,
  `postal-code` char(32) NOT NULL,
  `country-name` char(255) NOT NULL,
  `gender` char(32) NOT NULL,
  `marital` char(255) NOT NULL,
  `showwith` tinyint(1) NOT NULL DEFAULT '0',
  `with` text NOT NULL,
  `sexual` char(255) NOT NULL,
  `politic` char(255) NOT NULL,
  `religion` char(255) NOT NULL,
  `keywords` text NOT NULL,
  `about` text NOT NULL,
  `summary` char(255) NOT NULL,
  `music` text NOT NULL,
  `book` text NOT NULL,
  `tv` text NOT NULL,
  `film` text NOT NULL,
  `interest` text NOT NULL,
  `romance` text NOT NULL,
  `work` text NOT NULL,
  `education` text NOT NULL,
  `contact` text NOT NULL,
  `homepage` char(255) NOT NULL,
  `photo` char(255) NOT NULL,
  `thumb` char(255) NOT NULL,
  `publish` tinyint(1) NOT NULL DEFAULT '0',
  `net-publish` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `profile_check`
--

CREATE TABLE IF NOT EXISTS `profile_check` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  `dfrn_id` char(255) NOT NULL,
  `sec` char(255) NOT NULL,
  `expire` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `session`
--

CREATE TABLE IF NOT EXISTS `session` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sid` char(255) NOT NULL,
  `data` text NOT NULL,
  `expire` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sid` (`sid`),
  KEY `expire` (`expire`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `username` char(255) NOT NULL,
  `password` char(255) NOT NULL,
  `nickname` char(255) NOT NULL,
  `email` char(255) NOT NULL,
  `openid` char(255) NOT NULL,
  `timezone` char(128) NOT NULL,
  `register_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `login_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `default-location` char(255) NOT NULL,
  `allow_location` tinyint(1) NOT NULL DEFAULT '0',
  `theme` char(255) NOT NULL,
  `pubkey` text NOT NULL,
  `prvkey` text NOT NULL,
  `spubkey` text NOT NULL,
  `sprvkey` text NOT NULL,
  `verified` tinyint(1) unsigned NOT NULL DEFAULT '0', 
  `blocked` tinyint(1) unsigned NOT NULL DEFAULT '0', 
  `notify-flags` int(11) unsigned NOT NULL DEFAULT '65535', 
  `page-flags` int(11) unsigned NOT NULL DEFAULT '0',
  `pwdreset` char(255) NOT NULL,
  `maxreq` int(11) NOT NULL DEFAULT '10',
  `allow_cid` mediumtext NOT NULL, 
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL, 
  `deny_gid` mediumtext NOT NULL,
  `openidserver` text NOT NULL,
  PRIMARY KEY (`uid`), 
  KEY `nickname` (`nickname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `register` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `hash` CHAR( 255 ) NOT NULL ,
  `created` DATETIME NOT NULL ,
  `uid` INT(11) UNSIGNED NOT NULL,
  `password` CHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `clients` (
`client_id` VARCHAR( 20 ) NOT NULL ,
`pw` VARCHAR( 20 ) NOT NULL ,
`redirect_uri` VARCHAR( 200 ) NOT NULL ,
PRIMARY KEY ( `client_id` )
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tokens` (
`id` VARCHAR( 40 ) NOT NULL ,
`client_id` VARCHAR( 20 ) NOT NULL ,
`expires` INT NOT NULL ,
`scope` VARCHAR( 200 ) NOT NULL ,
PRIMARY KEY ( `id` )
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `auth_codes` (
`id` VARCHAR( 40 ) NOT NULL ,
`client_id` VARCHAR( 20 ) NOT NULL ,
`redirect_uri` VARCHAR( 200 ) NOT NULL ,
`expires` INT NOT NULL ,
`scope` VARCHAR( 250 ) NOT NULL ,
PRIMARY KEY ( `id` )
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `queue` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`cid` INT NOT NULL ,
`created` DATETIME NOT NULL ,
`last` DATETIME NOT NULL ,
`content` MEDIUMTEXT NOT NULL
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `pconfig` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`uid` INT NOT NULL DEFAULT '0',
`cat` CHAR( 255 ) NOT NULL ,
`k` CHAR( 255 ) NOT NULL ,
`v` MEDIUMTEXT NOT NULL
) ENGINE = MYISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `hook` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`hook` CHAR( 255 ) NOT NULL ,
`file` CHAR( 255 ) NOT NULL ,
`function` CHAR( 255 ) NOT NULL
) ENGINE = MYISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `addon` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`name` CHAR( 255 ) NOT NULL ,
`version` CHAR( 255 ) NOT NULL ,
`installed` TINYINT( 1 ) NOT NULL DEFAULT '0' 
) ENGINE = MYISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `event` (
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
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `cache` (
 `k` CHAR( 255 ) NOT NULL PRIMARY KEY ,
 `v` TEXT NOT NULL,
 `updated` DATETIME NOT NULL
) ENGINE = MYISAM DEFAULT CHARSET=utf8;
