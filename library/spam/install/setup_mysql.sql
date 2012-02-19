
--   Copyright (C) 2010 Tobias Leupold <tobias.leupold@web.de>
--
--   This file is part of the b8 package
--
--   This program is free software; you can redistribute it and/or modify it
--   under the terms of the GNU Lesser General Public License as published by
--   the Free Software Foundation in version 2.1 of the License.
--
--   This program is distributed in the hope that it will be useful, but
--   WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
--   or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Lesser General Public
--   License for more details.
--
--   You should have received a copy of the GNU Lesser General Public License
--   along with this program; if not, write to the Free Software Foundation,
--   Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, USA.

CREATE TABLE `b8_wordlist` (
  `token` varchar(255) character set utf8 collate utf8_bin NOT NULL,
  `count` varchar(255) default NULL,
  PRIMARY KEY  (`token`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `b8_wordlist` VALUES ('bayes*dbversion', '2');
INSERT INTO `b8_wordlist` VALUES ('bayes*texts.ham', '0');
INSERT INTO `b8_wordlist` VALUES ('bayes*texts.spam', '0');
