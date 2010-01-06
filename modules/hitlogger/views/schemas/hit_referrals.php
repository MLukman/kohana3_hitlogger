<?php defined('SYSPATH') or die('No direct script access.') ?>
CREATE TABLE IF NOT EXISTS `<?php echo $prefix; ?>hit_referrals` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `referer_id` int(10) unsigned NOT NULL,
  `stat_id` int(10) unsigned NOT NULL,
  `count` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `referer` (`referer_id`,`stat_id`),
  KEY `stat_id` (`stat_id`,`referer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
