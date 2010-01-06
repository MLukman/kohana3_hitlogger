<?php defined('SYSPATH') or die('No direct script access.') ?>
CREATE TABLE IF NOT EXISTS `<?php echo $prefix; ?>hit_stats` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `uri_id` int(10) unsigned NOT NULL,
  `date_id` int(10) unsigned NOT NULL,
  `status` varchar(3) NOT NULL,
  `accesses` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `uri_id` (`uri_id`,`date_id`,`status`),
  KEY `accesses` (`accesses`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;