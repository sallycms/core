## Sally Database Dump Version 0.6
## Prefix sly_

CREATE TABLE `sly_article` (
  `id` INT(11) NOT NULL,
  `re_id` INT(11) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `catname` VARCHAR(255) NOT NULL,
  `catprior` INT(11) NOT NULL,
  `attributes` TEXT NOT NULL,
  `startpage` TINYINT(1) NOT NULL,
  `prior` INT(11) NOT NULL,
  `path` VARCHAR(255) NOT NULL,
  `status` TINYINT(1) NOT NULL,
  `type` VARCHAR(64) NOT NULL,
  `clang` INT(11) NOT NULL,
  `createdate` INT(11) NOT NULL,
  `updatedate` INT(11) NOT NULL,
  `createuser` VARCHAR(255) NOT NULL,
  `updateuser` VARCHAR(255) NOT NULL,
  `revision` INT(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`, `clang`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `sly_article_slice` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `clang` INT(11) NOT NULL,
  `slot` VARCHAR(64) NOT NULL,
  `prior` INT(5) NOT NULL,
  `slice_id` BIGINT(20) NOT NULL DEFAULT '0',
  `article_id` INT(11) NOT NULL,
  `createdate` INT(11) NOT NULL,
  `updatedate` INT(11) NOT NULL,
  `createuser` VARCHAR(255) NOT NULL,
  `updateuser` VARCHAR(255) NOT NULL,
  `revision` INT(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  INDEX `find_article` (`article_id`, `clang`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `sly_clang` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `locale` VARCHAR(5) NOT NULL,
  `revision` INT(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `sly_file` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `re_file_id` INT(11) NOT NULL,
  `category_id` INT(11) NOT NULL,
  `attributes` TEXT NULL,
  `filetype` VARCHAR(255) NULL,
  `filename` VARCHAR(255) NULL,
  `originalname` VARCHAR(255) NULL,
  `filesize` VARCHAR(255) NULL,
  `width` INT(11) NULL,
  `height` INT(11) NULL,
  `title` VARCHAR(255) NULL,
  `createdate` INT(11) NOT NULL,
  `updatedate` INT(11) NOT NULL,
  `createuser` VARCHAR(255) NOT NULL,
  `updateuser` VARCHAR(255) NOT NULL,
  `revision` INT(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  INDEX `filename` (`filename`(255))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `sly_file_category` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `re_id` INT(11) NOT NULL,
  `path` VARCHAR(255) NOT NULL,
  `attributes` TEXT NULL,
  `createdate` INT(11) NOT NULL,
  `updatedate` INT(11) NOT NULL,
  `createuser` VARCHAR(255) NOT NULL,
  `updateuser` VARCHAR(255) NOT NULL,
  `revision` INT(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `sly_user` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255),
  `description` VARCHAR(255),
  `login` VARCHAR(50) NOT NULL,
  `psw` CHAR(40) NOT NULL,
  `status` TINYINT(1) NOT NULL,
  `rights` TEXT NOT NULL,
  `lasttrydate` INT(11) DEFAULT 0,
  `timezone` VARCHAR(64) DEFAULT NULL,
  `createuser` VARCHAR(255) NOT NULL,
  `updateuser` VARCHAR(255) NOT NULL,
  `createdate` INT(11) NOT NULL,
  `updatedate` INT(11) NOT NULL ,
  `revision` INT(11) NOT NULL DEFAULT '0',
  PRIMARY KEY(`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `sly_slice` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `module` VARCHAR(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `sly_slice_value` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `slice_id` INT(11) NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `finder` VARCHAR(50) NOT NULL,
  `value` text,
  PRIMARY KEY (`id`),
  INDEX `slice_id` (`slice_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `sly_registry` (
  `name` VARCHAR(255) NOT NULL,
  `value` MEDIUMTEXT,
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- populate database with some initial data
INSERT INTO `sly_clang` (`name`, `locale`) VALUES ('deutsch', 'de_DE');
