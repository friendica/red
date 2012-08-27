SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


CREATE TABLE IF NOT EXISTS `account` (
  `account_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `account_parent` int(10) unsigned NOT NULL DEFAULT '0',
  `account_default_entity` int(10) unsigned NOT NULL DEFAULT '0',
  `account_salt` char(32) NOT NULL DEFAULT '',
  `account_password` char(255) NOT NULL DEFAULT '',
  `account_email` char(255) NOT NULL DEFAULT '',
  `account_language` char(16) NOT NULL DEFAULT 'en',
  `account_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `account_lastlog` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `account_flags` int(10) unsigned NOT NULL DEFAULT '0',
  `account_roles` int(10) unsigned NOT NULL DEFAULT '0',
  `account_reset` char(255) NOT NULL DEFAULT '',
  `account_expires` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `account_expire_notified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `account_service_class` char(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`account_id`),
  KEY `account_email` (`account_email`),
  KEY `account_service_class` (`account_service_class`),
  KEY `account_parent` (`account_parent`),
  KEY `account_flags` (`account_flags`),
  KEY `account_roles` (`account_roles`),
  KEY `account_lastlog` (`account_lastlog`),
  KEY `account_expires` (`account_expires`),
  KEY `account_default_entity` (`account_default_entity`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `addon` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` char(255) NOT NULL,
  `version` char(255) NOT NULL,
  `installed` tinyint(1) NOT NULL DEFAULT '0',
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `timestamp` bigint(20) NOT NULL DEFAULT '0',
  `plugin_admin` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `hidden` (`hidden`),
  KEY `name` (`name`),
  KEY `installed` (`installed`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `attach` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `aid` int(10) unsigned NOT NULL DEFAULT '0',
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `hash` char(64) NOT NULL DEFAULT '',
  `filename` char(255) NOT NULL DEFAULT '',
  `filetype` char(64) NOT NULL DEFAULT '',
  `filesize` int(10) unsigned NOT NULL DEFAULT '0',
  `data` longblob NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `edited` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `uid` (`uid`),
  KEY `hash` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `auth_codes` (
  `id` varchar(40) NOT NULL,
  `client_id` varchar(20) NOT NULL,
  `redirect_uri` varchar(200) NOT NULL,
  `expires` int(11) NOT NULL,
  `scope` varchar(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `cache` (
  `k` char(255) NOT NULL,
  `v` text NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`k`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `challenge` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `challenge` char(255) NOT NULL,
  `dfrn-id` char(255) NOT NULL,
  `expire` int(11) NOT NULL,
  `type` char(255) NOT NULL,
  `last_update` char(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `clients` (
  `client_id` varchar(20) NOT NULL,
  `pw` varchar(20) NOT NULL,
  `redirect_uri` varchar(200) NOT NULL,
  `name` text,
  `icon` text,
  `uid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`client_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cat` char(255) CHARACTER SET ascii NOT NULL,
  `k` char(255) CHARACTER SET ascii NOT NULL,
  `v` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `access` (`cat`,`k`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `contact` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL COMMENT 'owner uid',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `my_perms` int(10) unsigned NOT NULL DEFAULT '0',
  `their_perms` int(10) unsigned NOT NULL DEFAULT '0',
  `self` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'boolean 1 == info for local UID, primarily name and photo to use in item displays.',
  `remote_self` tinyint(1) NOT NULL DEFAULT '0',
  `rel` tinyint(1) NOT NULL DEFAULT '0',
  `duplex` tinyint(1) NOT NULL DEFAULT '0',
  `network` char(255) NOT NULL,
  `name` char(255) NOT NULL,
  `nick` char(255) NOT NULL,
  `attag` char(255) NOT NULL,
  `photo` text NOT NULL COMMENT 'remote photo URL initially until approved',
  `thumb` text NOT NULL,
  `micro` text NOT NULL,
  `site-pubkey` text NOT NULL,
  `issued-id` char(255) NOT NULL,
  `dfrn-id` char(255) NOT NULL,
  `url` char(255) NOT NULL,
  `nurl` char(255) NOT NULL,
  `addr` char(255) NOT NULL,
  `alias` char(255) NOT NULL,
  `pubkey` text NOT NULL,
  `prvkey` text NOT NULL,
  `batch` char(255) NOT NULL,
  `request` text NOT NULL,
  `notify` char(255) NOT NULL,
  `poll` text NOT NULL,
  `confirm` text NOT NULL,
  `poco` text NOT NULL,
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
  `writable` tinyint(1) NOT NULL DEFAULT '0',
  `forum` tinyint(1) NOT NULL DEFAULT '0',
  `prv` tinyint(1) NOT NULL DEFAULT '0',
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `archive` tinyint(1) NOT NULL DEFAULT '0',
  `pending` tinyint(1) NOT NULL DEFAULT '1',
  `rating` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0-5 reputation, 0 unknown, 1 call police, 5 inscrutable',
  `reason` text NOT NULL COMMENT 'why a rating was given - will help friends decide to make friends or not',
  `closeness` tinyint(2) NOT NULL DEFAULT '99',
  `info` mediumtext NOT NULL,
  `profile-id` int(11) NOT NULL DEFAULT '0' COMMENT 'which profile to display - 0 is public default',
  `bdyear` char(4) NOT NULL COMMENT 'birthday notify flag',
  `bd` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `self` (`self`),
  KEY `issued-id` (`issued-id`),
  KEY `dfrn-id` (`dfrn-id`),
  KEY `blocked` (`blocked`),
  KEY `readonly` (`readonly`),
  KEY `network` (`network`),
  KEY `name` (`name`),
  KEY `nick` (`nick`),
  KEY `attag` (`attag`),
  KEY `addr` (`addr`),
  KEY `url` (`url`),
  KEY `batch` (`batch`),
  KEY `nurl` (`nurl`),
  KEY `pending` (`pending`),
  KEY `hidden` (`hidden`),
  KEY `archive` (`archive`),
  KEY `forum` (`forum`),
  KEY `notify` (`notify`),
  KEY `my_perms` (`my_perms`),
  KEY `their_perms` (`their_perms`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `conv` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `guid` char(64) NOT NULL,
  `recips` mediumtext NOT NULL,
  `uid` int(11) NOT NULL,
  `creator` char(255) NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `subject` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `created` (`created`),
  KEY `updated` (`updated`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `deliverq` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cmd` char(32) NOT NULL,
  `item` int(11) NOT NULL,
  `contact` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `item` (`item`),
  KEY `contact` (`contact`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `entity` (
  `entity_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entity_account_id` int(10) unsigned NOT NULL DEFAULT '0',
  `entity_primary` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `entity_name` char(255) NOT NULL DEFAULT '',
  `entity_address` char(255) NOT NULL DEFAULT '',
  `entity_global_id` char(255) NOT NULL DEFAULT '',
  `entity_timezone` char(128) NOT NULL DEFAULT '',
  `entity_location` char(255) NOT NULL DEFAULT '',
  `entity_theme` char(255) NOT NULL DEFAULT '',
  `entity_pubkey` text NOT NULL,
  `entity_prvkey` text NOT NULL,
  `entity_privacyflags` int(10) unsigned NOT NULL DEFAULT '0',
  `entity_notifyflags` int(10) unsigned NOT NULL DEFAULT '65535',
  `entity_pageflags` int(10) unsigned NOT NULL DEFAULT '0',
  `entity_max_anon_mail` int(10) unsigned NOT NULL DEFAULT '10',
  `entity_max_friend_req` int(10) unsigned NOT NULL DEFAULT '10',
  `entity_passwd_reset` char(255) NOT NULL DEFAULT '',
  `entity_default_gid` int(10) unsigned NOT NULL DEFAULT '0',
  `entity_allow_cid` mediumtext NOT NULL,
  `entity_allow_gid` mediumtext NOT NULL,
  `entity_deny_cid` mediumtext NOT NULL,
  `entity_deny_gid` mediumtext NOT NULL,
  PRIMARY KEY (`entity_id`),
  KEY `entity_account_id` (`entity_account_id`),
  KEY `entity_name` (`entity_name`),
  KEY `entity_address` (`entity_address`),
  KEY `entity_global_id` (`entity_global_id`),
  KEY `entity_timezone` (`entity_timezone`),
  KEY `entity_location` (`entity_location`),
  KEY `entity_theme` (`entity_theme`),
  KEY `entity_privacyflags` (`entity_privacyflags`),
  KEY `entity_notifyflags` (`entity_notifyflags`),
  KEY `entity_pageflags` (`entity_pageflags`),
  KEY `entity_max_anon_mail` (`entity_max_anon_mail`),
  KEY `entity_max_friend_req` (`entity_max_friend_req`),
  KEY `entity_default_gid` (`entity_default_gid`),
  KEY `entity_primary` (`entity_primary`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `cid` int(11) NOT NULL,
  `uri` char(255) NOT NULL,
  `created` datetime NOT NULL,
  `edited` datetime NOT NULL,
  `start` datetime NOT NULL,
  `finish` datetime NOT NULL,
  `summary` text NOT NULL,
  `desc` text NOT NULL,
  `location` text NOT NULL,
  `type` char(255) NOT NULL,
  `nofinish` tinyint(1) NOT NULL DEFAULT '0',
  `adjust` tinyint(1) NOT NULL DEFAULT '1',
  `ignore` tinyint(1) NOT NULL DEFAULT '0',
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `cid` (`cid`),
  KEY `uri` (`uri`),
  KEY `type` (`type`),
  KEY `start` (`start`),
  KEY `finish` (`finish`),
  KEY `adjust` (`adjust`),
  KEY `nofinish` (`nofinish`),
  KEY `ignore` (`ignore`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `fcontact` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `url` char(255) NOT NULL,
  `name` char(255) NOT NULL,
  `photo` char(255) NOT NULL,
  `request` char(255) NOT NULL,
  `nick` char(255) NOT NULL,
  `addr` char(255) NOT NULL,
  `batch` char(255) NOT NULL,
  `notify` char(255) NOT NULL,
  `poll` char(255) NOT NULL,
  `confirm` char(255) NOT NULL,
  `priority` tinyint(1) NOT NULL,
  `network` char(32) NOT NULL,
  `alias` char(255) NOT NULL,
  `pubkey` text NOT NULL,
  `updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `addr` (`addr`),
  KEY `network` (`network`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ffinder` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  `fid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `cid` (`cid`),
  KEY `fid` (`fid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `fserver` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server` char(255) NOT NULL,
  `posturl` char(255) NOT NULL,
  `key` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `server` (`server`),
  KEY `server_2` (`server`),
  KEY `posturl` (`posturl`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `fsuggest` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `cid` int(11) NOT NULL,
  `name` char(255) NOT NULL,
  `url` char(255) NOT NULL,
  `request` char(255) NOT NULL,
  `photo` char(255) NOT NULL,
  `note` text NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `gcign` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `gcid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `gcid` (`gcid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `gcontact` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(255) NOT NULL,
  `url` char(255) NOT NULL,
  `nurl` char(255) NOT NULL,
  `photo` char(255) NOT NULL,
  `connect` char(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `nurl` (`nurl`),
  KEY `name` (`name`),
  KEY `url` (`url`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `glink` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `gcid` int(11) NOT NULL,
  `zcid` int(11) NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cid` (`cid`),
  KEY `uid` (`uid`),
  KEY `gcid` (`gcid`),
  KEY `zcid` (`zcid`),
  KEY `updated` (`updated`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `name` char(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `visible` (`visible`),
  KEY `deleted` (`deleted`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `group_member` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `gid` int(10) unsigned NOT NULL,
  `contact-id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `gid` (`gid`),
  KEY `contact-id` (`contact-id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `guid` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `guid` char(64) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `guid` (`guid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `hook` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hook` char(255) NOT NULL,
  `file` char(255) NOT NULL,
  `function` char(255) NOT NULL,
  `priority` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `hook` (`hook`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `hubloc` (
  `hubloc_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hubloc_guid` char(255) NOT NULL DEFAULT '',
  `hubloc_guid_sig` char(255) NOT NULL,
  `hubloc_flags` int(10) unsigned NOT NULL DEFAULT '0',
  `hubloc_url` char(255) NOT NULL DEFAULT '',
  `hubloc_url_sig` char(255) NOT NULL,
  `hubloc_callback` char(255) NOT NULL DEFAULT '',
  `hubloc_sitekey` text NOT NULL,
  PRIMARY KEY (`hubloc_id`),
  KEY `hubloc_url` (`hubloc_url`),
  KEY `hubloc_guid` (`hubloc_guid`),
  KEY `hubloc_flags` (`hubloc_flags`),
  KEY `hubloc_guid_sig` (`hubloc_guid_sig`),
  KEY `hubloc_url_sig` (`hubloc_url_sig`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `intro` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `fid` int(11) NOT NULL DEFAULT '0',
  `contact-id` int(11) NOT NULL,
  `knowyou` tinyint(1) NOT NULL,
  `duplex` tinyint(1) NOT NULL DEFAULT '0',
  `note` text NOT NULL,
  `hash` char(255) NOT NULL,
  `datetime` datetime NOT NULL,
  `blocked` tinyint(1) NOT NULL DEFAULT '1',
  `ignore` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `fid` (`fid`),
  KEY `hash` (`hash`),
  KEY `datetime` (`datetime`),
  KEY `blocked` (`blocked`),
  KEY `ignore` (`ignore`),
  KEY `contact-id` (`contact-id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uri` char(255) CHARACTER SET ascii NOT NULL,
  `aid` int(10) unsigned NOT NULL DEFAULT '0',
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `contact-id` int(10) unsigned NOT NULL DEFAULT '0',
  `type` char(255) NOT NULL,
  `wall` tinyint(1) NOT NULL DEFAULT '0',
  `gravity` tinyint(1) NOT NULL DEFAULT '0',
  `parent` int(10) unsigned NOT NULL DEFAULT '0',
  `parent_uri` char(255) CHARACTER SET ascii NOT NULL,
  `thr-parent` char(255) NOT NULL,
  `created` datetime NOT NULL,
  `edited` datetime NOT NULL,
  `commented` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `received` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `changed` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `owner-name` char(255) NOT NULL,
  `owner-link` char(255) NOT NULL,
  `owner-avatar` char(255) NOT NULL,
  `author-name` char(255) NOT NULL,
  `author-link` char(255) NOT NULL,
  `author-avatar` char(255) NOT NULL,
  `title` char(255) NOT NULL,
  `body` mediumtext NOT NULL,
  `app` char(255) NOT NULL,
  `lang` char(64) NOT NULL,
  `verb` char(255) NOT NULL,
  `obj_type` char(255) NOT NULL,
  `object` text NOT NULL,
  `tgt_type` char(255) NOT NULL,
  `target` text NOT NULL,
  `postopts` text NOT NULL,
  `plink` char(255) NOT NULL,
  `resource-id` char(255) NOT NULL,
  `event-id` int(11) NOT NULL,
  `attach` mediumtext NOT NULL,
  `inform` mediumtext NOT NULL,
  `location` char(255) NOT NULL,
  `coord` char(255) NOT NULL,
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  `private` tinyint(1) NOT NULL DEFAULT '0',
  `pubmail` tinyint(1) NOT NULL DEFAULT '0',
  `moderated` tinyint(1) NOT NULL DEFAULT '0',
  `visible` tinyint(1) NOT NULL DEFAULT '0',
  `spam` tinyint(1) NOT NULL DEFAULT '0',
  `starred` tinyint(1) NOT NULL DEFAULT '0',
  `bookmark` tinyint(1) NOT NULL DEFAULT '0',
  `unseen` tinyint(1) NOT NULL DEFAULT '1',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `origin` tinyint(1) NOT NULL DEFAULT '0',
  `forum_mode` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uri` (`uri`),
  KEY `uid` (`uid`),
  KEY `contact-id` (`contact-id`),
  KEY `type` (`type`),
  KEY `parent` (`parent`),
  KEY `created` (`created`),
  KEY `edited` (`edited`),
  KEY `visible` (`visible`),
  KEY `deleted` (`deleted`),
  KEY `unseen` (`unseen`),
  KEY `received` (`received`),
  KEY `starred` (`starred`),
  KEY `origin` (`origin`),
  KEY `wall` (`wall`),
  KEY `forum_mode` (`forum_mode`),
  KEY `author-link` (`author-link`),
  KEY `bookmark` (`bookmark`),
  KEY `moderated` (`moderated`),
  KEY `spam` (`spam`),
  KEY `author-name` (`author-name`),
  KEY `uid_commented` (`uid`,`commented`),
  KEY `uid_created` (`uid`,`created`),
  KEY `uid_unseen` (`uid`,`unseen`),
  KEY `parent_uri` (`parent_uri`),
  KEY `aid` (`aid`),
  FULLTEXT KEY `title` (`title`),
  FULLTEXT KEY `body` (`body`),
  FULLTEXT KEY `allow_cid` (`allow_cid`),
  FULLTEXT KEY `allow_gid` (`allow_gid`),
  FULLTEXT KEY `deny_cid` (`deny_cid`),
  FULLTEXT KEY `deny_gid` (`deny_gid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `item_id` (
  `iid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `sid` char(255) NOT NULL,
  `service` char(255) NOT NULL,
  PRIMARY KEY (`iid`),
  KEY `uid` (`uid`),
  KEY `sid` (`sid`),
  KEY `service` (`service`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `locks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` char(128) NOT NULL,
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `mail` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `aid` int(10) unsigned NOT NULL DEFAULT '0',
  `uid` int(10) unsigned NOT NULL,
  `guid` char(64) NOT NULL,
  `from-name` char(255) NOT NULL,
  `from-photo` char(255) NOT NULL,
  `from-url` char(255) NOT NULL,
  `contact-id` char(255) NOT NULL,
  `convid` int(11) NOT NULL,
  `title` char(255) NOT NULL,
  `body` mediumtext NOT NULL,
  `seen` tinyint(1) NOT NULL,
  `reply` tinyint(1) NOT NULL DEFAULT '0',
  `replied` tinyint(1) NOT NULL,
  `unknown` tinyint(1) NOT NULL DEFAULT '0',
  `uri` char(255) NOT NULL,
  `parent_uri` char(255) NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `reply` (`reply`),
  KEY `uid` (`uid`),
  KEY `guid` (`guid`),
  KEY `seen` (`seen`),
  KEY `uri` (`uri`),
  KEY `created` (`created`),
  KEY `convid` (`convid`),
  KEY `unknown` (`unknown`),
  KEY `contact-id` (`contact-id`),
  KEY `parent_uri` (`parent_uri`),
  KEY `aid` (`aid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `manage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `mid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `mid` (`mid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `notify` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hash` char(64) NOT NULL,
  `name` char(255) NOT NULL,
  `url` char(255) NOT NULL,
  `photo` char(255) NOT NULL,
  `date` datetime NOT NULL,
  `msg` mediumtext NOT NULL,
  `uid` int(11) NOT NULL,
  `link` char(255) NOT NULL,
  `parent` int(11) NOT NULL,
  `seen` tinyint(1) NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL,
  `verb` char(255) NOT NULL,
  `otype` char(16) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `seen` (`seen`),
  KEY `uid` (`uid`),
  KEY `date` (`date`),
  KEY `hash` (`hash`),
  KEY `parent` (`parent`),
  KEY `link` (`link`),
  KEY `otype` (`otype`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `notify-threads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notify-id` int(11) NOT NULL,
  `master-parent-item` int(10) unsigned NOT NULL DEFAULT '0',
  `parent-item` int(10) unsigned NOT NULL DEFAULT '0',
  `receiver-uid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `master-parent-item` (`master-parent-item`),
  KEY `receiver-uid` (`receiver-uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `pconfig` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `cat` char(255) CHARACTER SET ascii NOT NULL,
  `k` char(255) CHARACTER SET ascii NOT NULL,
  `v` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `access` (`uid`,`cat`,`k`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `photo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `aid` int(10) unsigned NOT NULL DEFAULT '0',
  `uid` int(10) unsigned NOT NULL,
  `contact-id` int(10) unsigned NOT NULL DEFAULT '0',
  `guid` char(64) NOT NULL,
  `resource-id` char(255) NOT NULL,
  `created` datetime NOT NULL,
  `edited` datetime NOT NULL,
  `title` char(255) NOT NULL,
  `desc` text NOT NULL,
  `album` char(255) NOT NULL,
  `filename` char(255) NOT NULL,
  `type` char(128) NOT NULL DEFAULT 'image/jpeg',
  `height` smallint(6) NOT NULL,
  `width` smallint(6) NOT NULL,
  `data` mediumblob NOT NULL,
  `scale` tinyint(3) NOT NULL,
  `profile` tinyint(1) NOT NULL DEFAULT '0',
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `resource-id` (`resource-id`),
  KEY `album` (`album`),
  KEY `scale` (`scale`),
  KEY `profile` (`profile`),
  KEY `type` (`type`),
  KEY `contact-id` (`contact-id`),
  KEY `aid` (`aid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(10) unsigned NOT NULL DEFAULT '0',
  `uid` int(11) NOT NULL,
  `profile_name` char(255) NOT NULL,
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
  `hometown` char(255) NOT NULL,
  `gender` char(32) NOT NULL,
  `marital` char(255) NOT NULL,
  `with` text NOT NULL,
  `howlong` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `sexual` char(255) NOT NULL,
  `politic` char(255) NOT NULL,
  `religion` char(255) NOT NULL,
  `pub_keywords` text NOT NULL,
  `prv_keywords` text NOT NULL,
  `likes` text NOT NULL,
  `dislikes` text NOT NULL,
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
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `is-default` (`is-default`),
  KEY `locality` (`locality`),
  KEY `postal-code` (`postal-code`),
  KEY `country-name` (`country-name`),
  KEY `hometown` (`hometown`),
  KEY `gender` (`gender`),
  KEY `marital` (`marital`),
  KEY `sexual` (`sexual`),
  KEY `publish` (`publish`),
  KEY `net-publish` (`net-publish`),
  KEY `aid` (`aid`),
  FULLTEXT KEY `pub_keywords` (`pub_keywords`),
  FULLTEXT KEY `prv_keywords` (`prv_keywords`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `profile_check` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `cid` int(10) unsigned NOT NULL DEFAULT '0',
  `dfrn_id` char(255) NOT NULL,
  `sec` char(255) NOT NULL,
  `expire` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `cid` (`cid`),
  KEY `dfrn_id` (`dfrn_id`),
  KEY `sec` (`sec`),
  KEY `expire` (`expire`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cid` int(11) NOT NULL,
  `network` char(32) NOT NULL,
  `created` datetime NOT NULL,
  `last` datetime NOT NULL,
  `content` mediumtext NOT NULL,
  `batch` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `cid` (`cid`),
  KEY `network` (`network`),
  KEY `created` (`created`),
  KEY `last` (`last`),
  KEY `batch` (`batch`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `register` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hash` char(255) NOT NULL,
  `created` datetime NOT NULL,
  `uid` int(10) unsigned NOT NULL,
  `password` char(255) NOT NULL,
  `language` char(16) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `hash` (`hash`),
  KEY `created` (`created`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `session` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sid` char(255) NOT NULL,
  `data` text NOT NULL,
  `expire` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sid` (`sid`),
  KEY `expire` (`expire`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `spam` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `spam` int(11) NOT NULL DEFAULT '0',
  `ham` int(11) NOT NULL DEFAULT '0',
  `term` char(255) NOT NULL,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `spam` (`spam`),
  KEY `ham` (`ham`),
  KEY `term` (`term`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `term` (
  `tid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `aid` int(10) unsigned NOT NULL DEFAULT '0',
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `oid` int(10) unsigned NOT NULL,
  `otype` tinyint(3) unsigned NOT NULL,
  `type` tinyint(3) unsigned NOT NULL,
  `term` char(255) NOT NULL,
  `url` char(255) NOT NULL,
  PRIMARY KEY (`tid`),
  KEY `oid` (`oid`),
  KEY `otype` (`otype`),
  KEY `type` (`type`),
  KEY `term` (`term`),
  KEY `uid` (`uid`),
  KEY `aid` (`aid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tokens` (
  `id` varchar(40) NOT NULL,
  `secret` text NOT NULL,
  `client_id` varchar(20) NOT NULL,
  `expires` int(11) NOT NULL,
  `scope` varchar(200) NOT NULL,
  `uid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `expires` (`expires`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `user` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(10) unsigned NOT NULL,
  `guid` char(16) NOT NULL,
  `username` char(255) NOT NULL,
  `password` char(255) NOT NULL,
  `nickname` char(255) NOT NULL,
  `webid` char(255) NOT NULL,
  `email` char(255) NOT NULL,
  `openid` char(255) NOT NULL,
  `timezone` char(128) NOT NULL,
  `language` char(16) NOT NULL DEFAULT 'en',
  `register_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `login_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `default-location` char(255) NOT NULL,
  `allow_location` tinyint(1) NOT NULL DEFAULT '0',
  `theme` char(255) NOT NULL,
  `pubkey` text NOT NULL,
  `prvkey` text NOT NULL,
  `verified` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `blocked` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `blockwall` tinyint(1) NOT NULL DEFAULT '0',
  `hidewall` tinyint(1) NOT NULL DEFAULT '0',
  `blocktags` tinyint(1) NOT NULL DEFAULT '0',
  `unkmail` tinyint(1) NOT NULL DEFAULT '0',
  `cntunkmail` int(11) NOT NULL DEFAULT '10',
  `notify-flags` int(11) unsigned NOT NULL DEFAULT '65535',
  `page-flags` int(11) NOT NULL DEFAULT '0',
  `pwdreset` char(255) NOT NULL,
  `maxreq` int(11) NOT NULL DEFAULT '10',
  `expire` int(10) unsigned NOT NULL DEFAULT '0',
  `account_removed` tinyint(1) NOT NULL DEFAULT '0',
  `account_expired` tinyint(1) NOT NULL DEFAULT '0',
  `account_expires_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `expire_notification_sent` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `service_class` char(32) NOT NULL,
  `def_gid` int(11) NOT NULL DEFAULT '0',
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  `openidserver` text NOT NULL,
  PRIMARY KEY (`uid`),
  KEY `nickname` (`nickname`),
  KEY `login_date` (`login_date`),
  KEY `account_expired` (`account_expired`),
  KEY `hidewall` (`hidewall`),
  KEY `blockwall` (`blockwall`),
  KEY `blocked` (`blocked`),
  KEY `verified` (`verified`),
  KEY `unkmail` (`unkmail`),
  KEY `cntunkmail` (`cntunkmail`),
  KEY `account_removed` (`account_removed`),
  KEY `service_class` (`service_class`),
  KEY `webid` (`webid`),
  KEY `email` (`email`),
  KEY `account_id` (`account_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `userd` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` char(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
