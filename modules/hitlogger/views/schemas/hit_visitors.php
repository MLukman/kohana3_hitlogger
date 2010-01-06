<?php defined('SYSPATH') or die('No direct script access.') ?>
CREATE TABLE IF NOT EXISTS `<?php echo $prefix; ?>hit_visitors` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `ip` varchar(15) collate utf8_unicode_ci NOT NULL,
  `hostname` varchar(64) collate utf8_unicode_ci DEFAULT NULL,
  `incognito` BOOL NOT NULL DEFAULT '0',
  `date_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `ip` (`ip`),
  KEY `date_id` (`date_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;