DROP TABLE IF EXISTS `history`;
CREATE TABLE IF NOT EXISTS `history` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `uid` int(10) unsigned NOT NULL default '0',
  `isbn` varchar(255) NOT NULL default '',
  `time` datetime NOT NULL default '0000-00-00 00:00:00',
  KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=83 ;

DROP TABLE IF EXISTS `links`;
CREATE TABLE IF NOT EXISTS `links` (
  `order` int(10) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `url` varchar(255) NOT NULL default '',
  `runlevs` varchar(255) NOT NULL default '1',
  `external` enum('Yes','No') NOT NULL default 'No'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `news`;
CREATE TABLE IF NOT EXISTS `news` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `uid_create` int(10) unsigned NOT NULL default '0',
  `uid_update` int(10) unsigned NOT NULL default '0',
  `date_create` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_update` datetime NOT NULL default '0000-00-00 00:00:00',
  `title` varchar(255) NOT NULL default '',
  `body` text NOT NULL,
  KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

DROP TABLE IF EXISTS `queries`;
CREATE TABLE IF NOT EXISTS `queries` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `uid_create` int(10) unsigned NOT NULL default '0',
  `uid_update` int(10) unsigned NOT NULL default '0',
  `date_create` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_update` datetime NOT NULL default '0000-00-00 00:00:00',
  `early_date` date NOT NULL default '0000-00-00',
  `keywords` varchar(255) NOT NULL default '',
  `interval` int(10) unsigned NOT NULL default '0',
  KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9 ;

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `uid` int(10) unsigned NOT NULL default '0',
  `ip_addr` varchar(255) NOT NULL default '',
  `sesh_key` varchar(255) NOT NULL default '',
  `date_create` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_update` datetime NOT NULL default '0000-00-00 00:00:00',
  KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=243 ;

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `uid_create` int(10) unsigned NOT NULL default '1',
  `uid_update` int(10) unsigned NOT NULL default '1',
  `date_create` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_update` datetime NOT NULL default '0000-00-00 00:00:00',
  `username` varchar(255) NOT NULL default '',
  `realname` varchar(255) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `password` varchar(255) NOT NULL default '',
  `sec_ques` varchar(255) NOT NULL default '',
  `sec_ans` varchar(255) NOT NULL default '',
  `sec_level` int(10) unsigned NOT NULL default '1',
  `profile_notify` enum('Yes','No') NOT NULL default 'Yes',
  `search_notify` enum('Yes','No') NOT NULL default 'Yes',
  `reg_state` enum('Yes','No') NOT NULL default 'Yes',
  `locked` enum('Yes','No') NOT NULL default 'No',
  KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 PACK_KEYS=0 AUTO_INCREMENT=42 ;

