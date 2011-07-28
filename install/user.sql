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
