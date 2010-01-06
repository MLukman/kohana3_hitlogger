<?php defined('SYSPATH') or die('No direct script access.') ?>
CREATE TABLE IF NOT EXISTS `<?php echo $prefix; ?>hit_trails` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uri_id` int(10) unsigned NOT NULL,
  `session_id` int(10) unsigned NOT NULL,
  `stat_id` int(10) unsigned NULL,
  `timestamp` bigint(20) unsigned NOT NULL,
  `referer_id` int(10) unsigned DEFAULT NULL,
  `referer_uri_id` int(10) unsigned DEFAULT NULL,
  `previous` int(10) unsigned,
  PRIMARY KEY (`id`),
  KEY `uri_id` (`uri_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;