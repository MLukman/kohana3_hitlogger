<?php defined('SYSPATH') or die('No direct script access.') ?>
CREATE TABLE IF NOT EXISTS `<?php echo $prefix; ?>hit_uris` (
  `id` int(10) UNSIGNED NOT NULL auto_increment,
  `uri` varchar(255) NOT NULL,
  `hits` int(10) UNSIGNED DEFAULT '0',
  `since_date_id` int(10) UNSIGNED DEFAULT '1',
  PRIMARY KEY  (`id`),
  KEY `uri` (`uri`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
