ALTER TABLE `sly_article_slice` ADD `slice_id` BIGINT(20) NOT NULL DEFAULT '0' AFTER `re_article_slice_id`;

CREATE TABLE IF NOT EXISTS `sly_slice` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `module_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `sly_slice_value` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `slice_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `finder` varchar(50) NOT NULL,
  `value` text,
  PRIMARY KEY (`id`),
  KEY `slice_id` (`slice_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
