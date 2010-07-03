-- phpMyAdmin SQL Dump
-- version 2.11.9.4
-- http://www.phpmyadmin.net
--
-- Generation Time: Jul 02, 2010 at 05:22 PM
-- Server version: 5.1.39
-- PHP Version: 5.2.13

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


-- --------------------------------------------------------

--
-- Table structure for table `contact`
--

CREATE TABLE IF NOT EXISTS `contact` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL COMMENT 'owner uid',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `self` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'boolean 1 == info for local UID, primarily name and photo to use in item displays.',
  `name` char(255) NOT NULL,
  `photo` text NOT NULL COMMENT 'remote photo URL initially until approved',
  `thumb` text NOT NULL,
  `site-pubkey` text NOT NULL,
  `issued-id` char(255) NOT NULL,
  `dfrn-id` char(255) NOT NULL,
  `url` char(255) NOT NULL,
  `pubkey` text NOT NULL,
  `prvkey` text NOT NULL,
  `request` text NOT NULL,
  `notify` text NOT NULL,
  `poll` text NOT NULL,
  `confirm` text NOT NULL,
  `aes_allow` tinyint(1) NOT NULL DEFAULT '0',
  `ret-id` char(255) NOT NULL,
  `ret-pubkey` text NOT NULL,
  `priority` tinyint(3) NOT NULL,
  `blocked` tinyint(1) NOT NULL DEFAULT '1',
  `visible` tinyint(1) NOT NULL DEFAULT '0',
  `rating` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0-5 reputation, 0 unknown, 1 call police, 5 inscrutable',
  `reason` text NOT NULL COMMENT 'why a rating was given - will help friends decide to make friends or not',
  `profile-id` int(11) NOT NULL DEFAULT '0' COMMENT 'which profile to display - 0 is public default',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=100 ;

-- --------------------------------------------------------

--
-- Table structure for table `group`
--

CREATE TABLE IF NOT EXISTS `group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `name` char(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `intro`
--

CREATE TABLE IF NOT EXISTS `intro` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `contact-id` int(11) NOT NULL,
  `knowyou` tinyint(1) NOT NULL,
  `note` text NOT NULL,
  `hash` char(255) NOT NULL,
  `datetime` datetime NOT NULL,
  `blocked` tinyint(1) NOT NULL DEFAULT '1',
  `ignore` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=59 ;

-- --------------------------------------------------------

--
-- Table structure for table `item`
--

CREATE TABLE IF NOT EXISTS `item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `type` char(255) NOT NULL,
  `resource-id` char(255) NOT NULL,
  `contact-id` int(10) unsigned NOT NULL DEFAULT '0',
  `created` datetime NOT NULL,
  `edited` datetime NOT NULL,
  `commented` datetime NOT NULL,
  `hash` char(255) NOT NULL,
  `parent` int(10) unsigned NOT NULL DEFAULT '0',
  `title` char(255) NOT NULL,
  `body` text NOT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT '0',
  `allow_uid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_uid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `created` (`created`),
  KEY `guid` (`hash`),
  KEY `type` (`type`),
  KEY `commented` (`commented`),
  FULLTEXT KEY `body` (`body`),
  FULLTEXT KEY `title` (`title`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=15 ;

-- --------------------------------------------------------

--
-- Table structure for table `photo`
--

CREATE TABLE IF NOT EXISTS `photo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `resource-id` char(255) NOT NULL,
  `created` datetime NOT NULL,
  `edited` datetime NOT NULL,
  `title` char(255) NOT NULL,
  `desc` text NOT NULL,
  `filename` char(255) NOT NULL,
  `height` smallint(6) NOT NULL,
  `width` smallint(6) NOT NULL,
  `data` mediumblob NOT NULL,
  `scale` tinyint(3) NOT NULL,
  `allow_uid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_uid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=83 ;

-- --------------------------------------------------------

--
-- Table structure for table `profile`
--

CREATE TABLE IF NOT EXISTS `profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `profile-name` char(255) NOT NULL,
  `is-default` tinyint(1) NOT NULL DEFAULT '0',
  `name` char(255) NOT NULL,
  `dob` char(32) NOT NULL,
  `address` char(255) NOT NULL,
  `locality` char(255) NOT NULL,
  `region` char(255) NOT NULL,
  `postal-code` char(32) NOT NULL,
  `country-name` char(255) NOT NULL,
  `age` tinyint(3) NOT NULL,
  `gender` char(8) NOT NULL,
  `marital` char(255) NOT NULL,
  `about` text NOT NULL,
  `homepage` char(255) NOT NULL,
  `photo` char(255) NOT NULL,
  `thumb` char(255) NOT NULL,
  `publish` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=16 ;

-- --------------------------------------------------------

--
-- Table structure for table `profile_check`
--

CREATE TABLE IF NOT EXISTS `profile_check` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `dfrn_id` char(255) NOT NULL,
  `expire` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=16 ;

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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=23 ;

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
  `timezone` char(128) NOT NULL,
  `pubkey` text NOT NULL,
  `prvkey` text NOT NULL,
  `verified` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `blocked` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;
