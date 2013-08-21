SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


CREATE TABLE IF NOT EXISTS `abook` (
  `abook_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `abook_account` int(10) unsigned NOT NULL,
  `abook_channel` int(10) unsigned NOT NULL,
  `abook_xchan` char(255) NOT NULL DEFAULT '',
  `abook_my_perms` int(11) NOT NULL DEFAULT '0',
  `abook_their_perms` int(11) NOT NULL DEFAULT '0',
  `abook_closeness` tinyint(3) unsigned NOT NULL DEFAULT '99',
  `abook_rating` int(11) NOT NULL DEFAULT '0',
  `abook_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `abook_updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `abook_connected` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `abook_dob` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `abook_flags` int(11) NOT NULL DEFAULT '0',
  `abook_profile` char(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`abook_id`),
  KEY `abook_account` (`abook_account`),
  KEY `abook_channel` (`abook_channel`),
  KEY `abook_xchan` (`abook_xchan`),
  KEY `abook_my_perms` (`abook_my_perms`),
  KEY `abook_their_perms` (`abook_their_perms`),
  KEY `abook_closeness` (`abook_closeness`),
  KEY `abook_created` (`abook_created`),
  KEY `abook_updated` (`abook_updated`),
  KEY `abook_flags` (`abook_flags`),
  KEY `abook_profile` (`abook_profile`),
  KEY `abook_dob` (`abook_dob`),
  KEY `abook_connected` (`abook_connected`),
  KEY `abook_rating` (`abook_rating`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `account` (
  `account_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `account_parent` int(10) unsigned NOT NULL DEFAULT '0',
  `account_default_channel` int(10) unsigned NOT NULL DEFAULT '0',
  `account_salt` char(32) NOT NULL DEFAULT '',
  `account_password` char(255) NOT NULL DEFAULT '',
  `account_email` char(255) NOT NULL DEFAULT '',
  `account_external` char(255) NOT NULL DEFAULT '',
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
  KEY `account_default_channel` (`account_default_channel`),
  KEY `account_external` (`account_external`)
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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `attach` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `aid` int(10) unsigned NOT NULL DEFAULT '0',
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `hash` char(64) NOT NULL DEFAULT '',
  `filename` char(255) NOT NULL DEFAULT '',
  `filetype` char(64) NOT NULL DEFAULT '',
  `filesize` int(10) unsigned NOT NULL DEFAULT '0',
  `revision` int(10) unsigned NOT NULL DEFAULT '0',
  `folder` char(64) NOT NULL DEFAULT '',
  `flags` int(10) unsigned NOT NULL DEFAULT '0',
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
  KEY `hash` (`hash`),
  KEY `filename` (`filename`),
  KEY `filetype` (`filetype`),
  KEY `filesize` (`filesize`),
  KEY `created` (`created`),
  KEY `edited` (`edited`),
  KEY `revision` (`revision`),
  KEY `folder` (`folder`),
  KEY `flags` (`flags`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `channel` (
  `channel_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `channel_account_id` int(10) unsigned NOT NULL DEFAULT '0',
  `channel_primary` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `channel_name` char(255) NOT NULL DEFAULT '',
  `channel_address` char(255) NOT NULL DEFAULT '',
  `channel_guid` char(255) NOT NULL DEFAULT '',
  `channel_guid_sig` text NOT NULL,
  `channel_hash` char(255) NOT NULL DEFAULT '',
  `channel_timezone` char(128) NOT NULL DEFAULT 'UTC',
  `channel_location` char(255) NOT NULL DEFAULT '',
  `channel_theme` char(255) NOT NULL DEFAULT '',
  `channel_startpage` char(255) NOT NULL DEFAULT '',
  `channel_pubkey` text NOT NULL,
  `channel_prvkey` text NOT NULL,
  `channel_notifyflags` int(10) unsigned NOT NULL DEFAULT '65535',
  `channel_pageflags` int(10) unsigned NOT NULL DEFAULT '0',
  `channel_deleted` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `channel_max_anon_mail` int(10) unsigned NOT NULL DEFAULT '10',
  `channel_max_friend_req` int(10) unsigned NOT NULL DEFAULT '10',
  `channel_expire_days` int(11) NOT NULL DEFAULT '0',
  `channel_passwd_reset` char(255) NOT NULL DEFAULT '',
  `channel_default_group` char(255) NOT NULL DEFAULT '',
  `channel_allow_cid` mediumtext NOT NULL,
  `channel_allow_gid` mediumtext NOT NULL,
  `channel_deny_cid` mediumtext NOT NULL,
  `channel_deny_gid` mediumtext NOT NULL,
  `channel_r_stream` tinyint(3) unsigned NOT NULL DEFAULT '128',
  `channel_r_profile` tinyint(3) unsigned NOT NULL DEFAULT '128',
  `channel_r_photos` tinyint(3) unsigned NOT NULL DEFAULT '128',
  `channel_r_abook` tinyint(3) unsigned NOT NULL DEFAULT '128',
  `channel_w_stream` tinyint(3) unsigned NOT NULL DEFAULT '128',
  `channel_w_wall` tinyint(3) unsigned NOT NULL DEFAULT '128',
  `channel_w_tagwall` tinyint(3) unsigned NOT NULL DEFAULT '128',
  `channel_w_comment` tinyint(3) unsigned NOT NULL DEFAULT '128',
  `channel_w_mail` tinyint(3) unsigned NOT NULL DEFAULT '128',
  `channel_w_photos` tinyint(3) unsigned NOT NULL DEFAULT '128',
  `channel_w_chat` tinyint(3) unsigned NOT NULL DEFAULT '128',
  `channel_a_delegate` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `channel_r_storage` int(10) unsigned NOT NULL DEFAULT '128',
  `channel_w_storage` int(10) unsigned NOT NULL DEFAULT '128',
  `channel_r_pages` int(10) unsigned NOT NULL DEFAULT '128',
  `channel_w_pages` int(10) unsigned NOT NULL DEFAULT '128',
  PRIMARY KEY (`channel_id`),
  UNIQUE KEY `channel_address_unique` (`channel_address`),
  KEY `channel_account_id` (`channel_account_id`),
  KEY `channel_primary` (`channel_primary`),
  KEY `channel_name` (`channel_name`),
  KEY `channel_timezone` (`channel_timezone`),
  KEY `channel_location` (`channel_location`),
  KEY `channel_theme` (`channel_theme`),
  KEY `channel_notifyflags` (`channel_notifyflags`),
  KEY `channel_pageflags` (`channel_pageflags`),
  KEY `channel_max_anon_mail` (`channel_max_anon_mail`),
  KEY `channel_max_friend_req` (`channel_max_friend_req`),
  KEY `channel_default_gid` (`channel_default_group`),
  KEY `channel_r_stream` (`channel_r_stream`),
  KEY `channel_r_profile` (`channel_r_profile`),
  KEY `channel_r_photos` (`channel_r_photos`),
  KEY `channel_r_abook` (`channel_r_abook`),
  KEY `channel_w_stream` (`channel_w_stream`),
  KEY `channel_w_wall` (`channel_w_wall`),
  KEY `channel_w_tagwall` (`channel_w_tagwall`),
  KEY `channel_w_comment` (`channel_w_comment`),
  KEY `channel_w_mail` (`channel_w_mail`),
  KEY `channel_w_photos` (`channel_w_photos`),
  KEY `channel_w_chat` (`channel_w_chat`),
  KEY `channel_guid` (`channel_guid`),
  KEY `channel_hash` (`channel_hash`),
  KEY `channel_expire_days` (`channel_expire_days`),
  KEY `channel_a_delegate` (`channel_a_delegate`),
  KEY `channel_r_storage` (`channel_r_storage`),
  KEY `channel_w_storage` (`channel_w_storage`),
  KEY `channel_r_pages` (`channel_r_pages`),
  KEY `channel_w_pages` (`channel_w_pages`),
  KEY `channel_deleted` (`channel_deleted`)
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

CREATE TABLE IF NOT EXISTS `event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(10) unsigned NOT NULL DEFAULT '0',
  `uid` int(11) NOT NULL,
  `event_xchan` char(255) NOT NULL DEFAULT '',
  `event_hash` char(255) NOT NULL DEFAULT '',
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
  KEY `type` (`type`),
  KEY `start` (`start`),
  KEY `finish` (`finish`),
  KEY `adjust` (`adjust`),
  KEY `nofinish` (`nofinish`),
  KEY `ignore` (`ignore`),
  KEY `aid` (`aid`),
  KEY `event_hash` (`event_hash`),
  KEY `event_xchan` (`event_xchan`)
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

CREATE TABLE IF NOT EXISTS `group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hash` char(255) NOT NULL DEFAULT '',
  `uid` int(10) unsigned NOT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `name` char(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `visible` (`visible`),
  KEY `deleted` (`deleted`),
  KEY `hash` (`hash`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `group_member` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `gid` int(10) unsigned NOT NULL,
  `xchan` char(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `gid` (`gid`),
  KEY `xchan` (`xchan`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `hook` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hook` char(255) NOT NULL,
  `file` char(255) NOT NULL,
  `function` char(255) NOT NULL,
  `priority` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `hook` (`hook`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `hubloc` (
  `hubloc_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hubloc_guid` char(255) NOT NULL DEFAULT '',
  `hubloc_guid_sig` text NOT NULL,
  `hubloc_hash` char(255) NOT NULL,
  `hubloc_addr` char(255) NOT NULL DEFAULT '',
  `hubloc_flags` int(10) unsigned NOT NULL DEFAULT '0',
  `hubloc_url` char(255) NOT NULL DEFAULT '',
  `hubloc_url_sig` text NOT NULL,
  `hubloc_host` char(255) NOT NULL DEFAULT '',
  `hubloc_callback` char(255) NOT NULL DEFAULT '',
  `hubloc_connect` char(255) NOT NULL DEFAULT '',
  `hubloc_sitekey` text NOT NULL,
  `hubloc_updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `hubloc_connected` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`hubloc_id`),
  KEY `hubloc_url` (`hubloc_url`),
  KEY `hubloc_guid` (`hubloc_guid`),
  KEY `hubloc_flags` (`hubloc_flags`),
  KEY `hubloc_connect` (`hubloc_connect`),
  KEY `hubloc_host` (`hubloc_host`),
  KEY `hubloc_addr` (`hubloc_addr`),
  KEY `hubloc_updated` (`hubloc_updated`),
  KEY `hubloc_connected` (`hubloc_connected`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `issue` (
  `issue_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `issue_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `issue_updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `issue_assigned` char(255) NOT NULL,
  `issue_priority` int(11) NOT NULL,
  `issue_status` int(11) NOT NULL,
  `issue_component` char(255) NOT NULL,
  PRIMARY KEY (`issue_id`),
  KEY `issue_created` (`issue_created`),
  KEY `issue_updated` (`issue_updated`),
  KEY `issue_assigned` (`issue_assigned`),
  KEY `issue_priority` (`issue_priority`),
  KEY `issue_status` (`issue_status`),
  KEY `issue_component` (`issue_component`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mid` char(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  `aid` int(10) unsigned NOT NULL DEFAULT '0',
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `wall` tinyint(1) NOT NULL DEFAULT '0',
  `parent` int(10) unsigned NOT NULL DEFAULT '0',
  `parent_mid` char(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  `thr_parent` char(255) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `edited` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `expires` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `commented` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `received` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `changed` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `owner_xchan` char(255) NOT NULL DEFAULT '',
  `author_xchan` char(255) NOT NULL DEFAULT '',
  `mimetype` char(255) NOT NULL DEFAULT '',
  `title` text NOT NULL,
  `body` mediumtext NOT NULL,
  `app` char(255) NOT NULL DEFAULT '',
  `lang` char(64) NOT NULL DEFAULT '',
  `revision` int(10) unsigned NOT NULL DEFAULT '0',
  `verb` char(255) NOT NULL DEFAULT '',
  `obj_type` char(255) NOT NULL DEFAULT '',
  `object` text NOT NULL,
  `tgt_type` char(255) NOT NULL DEFAULT '',
  `target` text NOT NULL,
  `postopts` text NOT NULL,
  `llink` char(255) NOT NULL DEFAULT '',
  `plink` char(255) NOT NULL DEFAULT '',
  `resource_id` char(255) NOT NULL DEFAULT '',
  `resource_type` char(16) NOT NULL DEFAULT '',
  `attach` mediumtext NOT NULL,
  `inform` mediumtext NOT NULL,
  `location` char(255) NOT NULL DEFAULT '',
  `coord` char(255) NOT NULL DEFAULT '',
  `comment_policy` char(255) NOT NULL DEFAULT '',
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  `item_restrict` int(11) NOT NULL DEFAULT '0',
  `item_flags` int(11) NOT NULL DEFAULT '0',
  `item_private` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `parent` (`parent`),
  KEY `created` (`created`),
  KEY `edited` (`edited`),
  KEY `received` (`received`),
  KEY `wall` (`wall`),
  KEY `uid_commented` (`uid`,`commented`),
  KEY `uid_created` (`uid`,`created`),
  KEY `aid` (`aid`),
  KEY `owner_xchan` (`owner_xchan`),
  KEY `author_xchan` (`author_xchan`),
  KEY `resource_type` (`resource_type`),
  KEY `item_restrict` (`item_restrict`),
  KEY `item_flags` (`item_flags`),
  KEY `commented` (`commented`),
  KEY `verb` (`verb`),
  KEY `item_private` (`item_private`),
  KEY `llink` (`llink`),
  KEY `expires` (`expires`),
  KEY `revision` (`revision`),
  KEY `mimetype` (`mimetype`),
  KEY `mid` (`mid`),
  KEY `parent_mid` (`parent_mid`),
  KEY `uid_mid` (`mid`,`uid`),
  KEY `comment_policy` (`comment_policy`),
  FULLTEXT KEY `title` (`title`),
  FULLTEXT KEY `body` (`body`),
  FULLTEXT KEY `allow_cid` (`allow_cid`),
  FULLTEXT KEY `allow_gid` (`allow_gid`),
  FULLTEXT KEY `deny_cid` (`deny_cid`),
  FULLTEXT KEY `deny_gid` (`deny_gid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `item_id` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `iid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `sid` char(255) NOT NULL,
  `service` char(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `sid` (`sid`),
  KEY `service` (`service`),
  KEY `iid` (`iid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `mail` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mail_flags` int(10) unsigned NOT NULL DEFAULT '0',
  `from_xchan` char(255) NOT NULL DEFAULT '',
  `to_xchan` char(255) NOT NULL DEFAULT '',
  `account_id` int(10) unsigned NOT NULL DEFAULT '0',
  `channel_id` int(10) unsigned NOT NULL,
  `title` text NOT NULL,
  `body` mediumtext NOT NULL,
  `attach` mediumtext NOT NULL,
  `mid` char(255) NOT NULL,
  `parent_mid` char(255) NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `created` (`created`),
  KEY `mail_flags` (`mail_flags`),
  KEY `account_id` (`account_id`),
  KEY `channel_id` (`channel_id`),
  KEY `from_xchan` (`from_xchan`),
  KEY `to_xchan` (`to_xchan`),
  KEY `mid` (`mid`),
  KEY `parent_mid` (`parent_mid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `manage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `xchan` char(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `xchan` (`xchan`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `menu` (
  `menu_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `menu_channel_id` int(10) unsigned NOT NULL DEFAULT '0',
  `menu_name` char(255) NOT NULL DEFAULT '',
  `menu_desc` char(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`menu_id`),
  KEY `menu_channel_id` (`menu_channel_id`),
  KEY `menu_name` (`menu_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `menu_item` (
  `mitem_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mitem_link` char(255) NOT NULL DEFAULT '',
  `mitem_desc` char(255) NOT NULL DEFAULT '',
  `mitem_flags` int(11) NOT NULL DEFAULT '0',
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  `mitem_channel_id` int(10) unsigned NOT NULL,
  `mitem_menu_id` int(10) unsigned NOT NULL DEFAULT '0',
  `mitem_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`mitem_id`),
  KEY `mitem_channel_id` (`mitem_channel_id`),
  KEY `mitem_menu_id` (`mitem_menu_id`),
  KEY `mitem_flags` (`mitem_flags`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

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

CREATE TABLE IF NOT EXISTS `obj` (
  `obj_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `obj_page` char(64) NOT NULL DEFAULT '',
  `obj_verb` char(255) NOT NULL DEFAULT '',
  `obj_type` int(10) unsigned NOT NULL DEFAULT '0',
  `obj_obj` char(255) NOT NULL DEFAULT '',
  `obj_channel` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`obj_id`),
  KEY `obj_verb` (`obj_verb`),
  KEY `obj_page` (`obj_page`),
  KEY `obj_type` (`obj_type`),
  KEY `obj_channel` (`obj_channel`),
  KEY `obj_obj` (`obj_obj`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `outq` (
  `outq_hash` char(255) NOT NULL,
  `outq_account` int(10) unsigned NOT NULL DEFAULT '0',
  `outq_channel` int(10) unsigned NOT NULL DEFAULT '0',
  `outq_driver` char(32) NOT NULL DEFAULT '',
  `outq_posturl` char(255) NOT NULL DEFAULT '',
  `outq_async` tinyint(1) NOT NULL DEFAULT '0',
  `outq_delivered` tinyint(1) NOT NULL DEFAULT '0',
  `outq_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `outq_updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `outq_notify` mediumtext NOT NULL,
  `outq_msg` mediumtext NOT NULL,
  PRIMARY KEY (`outq_hash`),
  KEY `outq_account` (`outq_account`),
  KEY `outq_channel` (`outq_channel`),
  KEY `outq_hub` (`outq_posturl`),
  KEY `outq_created` (`outq_created`),
  KEY `outq_updated` (`outq_updated`),
  KEY `outq_async` (`outq_async`),
  KEY `outq_delivered` (`outq_delivered`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

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
  `xchan` char(255) NOT NULL DEFAULT '',
  `resource_id` char(255) NOT NULL,
  `created` datetime NOT NULL,
  `edited` datetime NOT NULL,
  `title` char(255) NOT NULL,
  `desc` text NOT NULL,
  `album` char(255) NOT NULL,
  `filename` char(255) NOT NULL,
  `type` char(128) NOT NULL DEFAULT 'image/jpeg',
  `height` smallint(6) NOT NULL,
  `width` smallint(6) NOT NULL,
  `size` int(10) unsigned NOT NULL DEFAULT '0',
  `data` mediumblob NOT NULL,
  `scale` tinyint(3) NOT NULL,
  `profile` tinyint(1) NOT NULL DEFAULT '0',
  `photo_flags` int(10) unsigned NOT NULL DEFAULT '0',
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `album` (`album`),
  KEY `scale` (`scale`),
  KEY `profile` (`profile`),
  KEY `photo_flags` (`photo_flags`),
  KEY `type` (`type`),
  KEY `aid` (`aid`),
  KEY `xchan` (`xchan`),
  KEY `size` (`size`),
  KEY `resource_id` (`resource_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `poll` (
  `poll_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `poll_channel` int(10) unsigned NOT NULL DEFAULT '0',
  `poll_desc` text NOT NULL,
  `poll_flags` int(11) NOT NULL DEFAULT '0',
  `poll_votes` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`poll_id`),
  KEY `poll_channel` (`poll_channel`),
  KEY `poll_flags` (`poll_flags`),
  KEY `poll_votes` (`poll_votes`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `poll_elm` (
  `pelm_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pelm_poll` int(10) unsigned NOT NULL DEFAULT '0',
  `pelm_desc` text NOT NULL,
  `pelm_flags` int(11) NOT NULL DEFAULT '0',
  `pelm_result` float NOT NULL DEFAULT '0',
  PRIMARY KEY (`pelm_id`),
  KEY `pelm_poll` (`pelm_poll`),
  KEY `pelm_result` (`pelm_result`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profile_guid` char(64) NOT NULL DEFAULT '',
  `aid` int(10) unsigned NOT NULL DEFAULT '0',
  `uid` int(11) NOT NULL,
  `profile_name` char(255) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `hide_friends` tinyint(1) NOT NULL DEFAULT '0',
  `name` char(255) NOT NULL,
  `pdesc` char(255) NOT NULL,
  `chandesc` text NOT NULL,
  `dob` char(32) NOT NULL DEFAULT '0000-00-00',
  `dob_tz` char(255) NOT NULL DEFAULT 'UTC',
  `address` char(255) NOT NULL,
  `locality` char(255) NOT NULL,
  `region` char(255) NOT NULL,
  `postal_code` char(32) NOT NULL,
  `country_name` char(255) NOT NULL,
  `hometown` char(255) NOT NULL,
  `gender` char(32) NOT NULL,
  `marital` char(255) NOT NULL,
  `with` text NOT NULL,
  `howlong` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `sexual` char(255) NOT NULL,
  `politic` char(255) NOT NULL,
  `religion` char(255) NOT NULL,
  `keywords` text NOT NULL,
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
  `channels` text NOT NULL,
  `homepage` char(255) NOT NULL,
  `photo` char(255) NOT NULL,
  `thumb` char(255) NOT NULL,
  `publish` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `guid` (`profile_guid`,`uid`),
  KEY `uid` (`uid`),
  KEY `locality` (`locality`),
  KEY `hometown` (`hometown`),
  KEY `gender` (`gender`),
  KEY `marital` (`marital`),
  KEY `sexual` (`sexual`),
  KEY `publish` (`publish`),
  KEY `aid` (`aid`),
  KEY `is_default` (`is_default`),
  KEY `hide_friends` (`hide_friends`),
  KEY `postal_code` (`postal_code`),
  KEY `country_name` (`country_name`),
  KEY `profile_guid` (`profile_guid`)
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

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
  `expire` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sid` (`sid`),
  KEY `expire` (`expire`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `shares` (
  `share_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `share_type` int(11) NOT NULL DEFAULT '0',
  `share_target` int(10) unsigned NOT NULL DEFAULT '0',
  `share_xchan` char(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`share_id`),
  KEY `share_type` (`share_type`),
  KEY `share_target` (`share_target`),
  KEY `share_xchan` (`share_xchan`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `site` (
  `site_url` char(255) NOT NULL,
  `site_flags` int(11) NOT NULL DEFAULT '0',
  `site_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `site_directory` char(255) NOT NULL DEFAULT '',
  `site_register` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`site_url`),
  KEY `site_flags` (`site_flags`),
  KEY `site_update` (`site_update`),
  KEY `site_directory` (`site_directory`),
  KEY `site_register` (`site_register`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

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
  `imgurl` char(255) NOT NULL,
  `term_hash` char(255) NOT NULL DEFAULT '',
  `parent_hash` char(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`tid`),
  KEY `oid` (`oid`),
  KEY `otype` (`otype`),
  KEY `type` (`type`),
  KEY `term` (`term`),
  KEY `uid` (`uid`),
  KEY `aid` (`aid`),
  KEY `imgurl` (`imgurl`),
  KEY `term_hash` (`term_hash`),
  KEY `parent_hash` (`parent_hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tokens` (
  `id` varchar(40) NOT NULL,
  `secret` text NOT NULL,
  `client_id` varchar(20) NOT NULL,
  `expires` bigint(20) unsigned NOT NULL,
  `scope` varchar(200) NOT NULL,
  `uid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `expires` (`expires`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `updates` (
  `ud_hash` char(128) NOT NULL,
  `ud_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`ud_hash`),
  KEY `ud_date` (`ud_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `verify` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `channel` int(10) unsigned NOT NULL DEFAULT '0',
  `type` char(32) NOT NULL DEFAULT '',
  `token` char(255) NOT NULL DEFAULT '',
  `meta` char(255) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `channel` (`channel`),
  KEY `type` (`type`),
  KEY `token` (`token`),
  KEY `meta` (`meta`),
  KEY `created` (`created`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `vote` (
  `vote_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vote_poll` int(11) NOT NULL DEFAULT '0',
  `vote_element` int(11) NOT NULL DEFAULT '0',
  `vote_result` text NOT NULL,
  `vote_xchan` char(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`vote_id`),
  UNIQUE KEY `vote_vote` (`vote_poll`,`vote_element`,`vote_xchan`),
  KEY `vote_poll` (`vote_poll`),
  KEY `vote_element` (`vote_element`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `xchan` (
  `xchan_hash` char(255) NOT NULL,
  `xchan_guid` char(255) NOT NULL DEFAULT '',
  `xchan_guid_sig` text NOT NULL,
  `xchan_pubkey` text NOT NULL,
  `xchan_photo_mimetype` char(32) NOT NULL DEFAULT 'image/jpeg',
  `xchan_photo_l` char(255) NOT NULL DEFAULT '',
  `xchan_photo_m` char(255) NOT NULL DEFAULT '',
  `xchan_photo_s` char(255) NOT NULL DEFAULT '',
  `xchan_addr` char(255) NOT NULL DEFAULT '',
  `xchan_url` char(255) NOT NULL DEFAULT '',
  `xchan_connurl` char(255) NOT NULL DEFAULT '',
  `xchan_name` char(255) NOT NULL DEFAULT '',
  `xchan_network` char(255) NOT NULL DEFAULT '',
  `xchan_instance_url` char(255) NOT NULL DEFAULT '',
  `xchan_flags` int(10) unsigned NOT NULL DEFAULT '0',
  `xchan_photo_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `xchan_name_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`xchan_hash`),
  KEY `xchan_guid` (`xchan_guid`),
  KEY `xchan_addr` (`xchan_addr`),
  KEY `xchan_name` (`xchan_name`),
  KEY `xchan_network` (`xchan_network`),
  KEY `xchan_url` (`xchan_url`),
  KEY `xchan_flags` (`xchan_flags`),
  KEY `xchan_connurl` (`xchan_connurl`),
  KEY `xchan_instance_url` (`xchan_instance_url`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `xconfig` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `xchan` char(255) NOT NULL,
  `cat` char(255) NOT NULL,
  `k` char(255) NOT NULL,
  `v` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `xchan` (`xchan`),
  KEY `cat` (`cat`),
  KEY `k` (`k`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `xign` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `xchan` char(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `xchan` (`xchan`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `xlink` (
  `xlink_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `xlink_xchan` char(255) NOT NULL DEFAULT '',
  `xlink_link` char(255) NOT NULL DEFAULT '',
  `xlink_rating` int(11) NOT NULL DEFAULT '0',
  `xlink_updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`xlink_id`),
  KEY `xlink_xchan` (`xlink_xchan`),
  KEY `xlink_link` (`xlink_link`),
  KEY `xlink_updated` (`xlink_updated`),
  KEY `xlink_rating` (`xlink_rating`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `xprof` (
  `xprof_hash` char(255) NOT NULL,
  `xprof_age` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `xprof_desc` char(255) NOT NULL DEFAULT '',
  `xprof_dob` char(12) NOT NULL DEFAULT '',
  `xprof_gender` char(255) NOT NULL DEFAULT '',
  `xprof_marital` char(255) NOT NULL DEFAULT '',
  `xprof_sexual` char(255) NOT NULL DEFAULT '',
  `xprof_locale` char(255) NOT NULL DEFAULT '',
  `xprof_region` char(255) NOT NULL DEFAULT '',
  `xprof_postcode` char(32) NOT NULL DEFAULT '',
  `xprof_country` char(255) NOT NULL DEFAULT '',
  `xprof_keywords` text NOT NULL,
  PRIMARY KEY (`xprof_hash`),
  KEY `xprof_desc` (`xprof_desc`),
  KEY `xprof_dob` (`xprof_dob`),
  KEY `xprof_gender` (`xprof_gender`),
  KEY `xprof_marital` (`xprof_marital`),
  KEY `xprof_sexual` (`xprof_sexual`),
  KEY `xprof_locale` (`xprof_locale`),
  KEY `xprof_region` (`xprof_region`),
  KEY `xprof_postcode` (`xprof_postcode`),
  KEY `xprof_country` (`xprof_country`),
  KEY `xprof_age` (`xprof_age`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `xtag` (
  `xtag_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `xtag_hash` char(255) NOT NULL,
  `xtag_term` char(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`xtag_id`),
  KEY `xtag_term` (`xtag_term`),
  KEY `xtag_hash` (`xtag_hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
