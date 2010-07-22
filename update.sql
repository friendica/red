CREATE TABLE IF NOT EXISTS `mail` (
  `id` int(10) unsigned NOT NULL,
  `uid` int(10) unsigned NOT NULL,
  `from-name` char(255) NOT NULL,
  `from-photo` char(255) NOT NULL,
  `from-url` char(255) NOT NULL,
  `contact-id` char(255) NOT NULL,
  `title` char(255) NOT NULL,
  `body` text NOT NULL,
  `delivered` tinyint(1) NOT NULL,
  `seen` tinyint(1) NOT NULL,
  `replied` tinyint(1) NOT NULL,
  `uri` char(255) NOT NULL,
  `parent-uri` char(255) NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE `user` ADD `notify-flags` INT(11) UNSIGNED NOT NULL DEFAULT 65535 AFTER `blocked`;

--------------
