<?php defined('SYSPATH') or die('No direct script access.') ?>
CREATE TABLE IF NOT EXISTS `<?php echo $prefix; ?>hit_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `visitor_id` int(10) unsigned NOT NULL,
  `user_agent` varchar(1024) DEFAULT NULL,
  `session` varchar(32) DEFAULT NULL,
  `timestamp` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `visitor_id` (`visitor_id`,`user_agent`(255),`session`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
