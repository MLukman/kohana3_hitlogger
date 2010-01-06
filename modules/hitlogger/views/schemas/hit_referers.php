<?php defined('SYSPATH') or die('No direct script access.') ?>
CREATE TABLE IF NOT EXISTS `<?php echo $prefix; ?>hit_referers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NOT NULL,
  `domain` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `url` (`url`),
  KEY `domain` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
