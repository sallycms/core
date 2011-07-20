CREATE TABLE `%PREFIX%user` (
	`id`          int(11) NOT NULL auto_increment,
	`name`        varchar(255),
	`description` text,
	`login`       varchar(50) NOT NULL,
	`psw`         varchar(50) NOT NULL,
	`status`      varchar(5) NOT NULL,
	`rights`      text NOT NULL,
	`createuser`  varchar(255) NOT NULL,
	`updateuser`  varchar(255) NOT NULL,
	`createdate`  int(11) NOT NULL,
	`updatedate`  int(11) NOT NULL,
	`lasttrydate` int(11) DEFAULT 0,
	`timezone`    varchar(64) DEFAULT NULL,
	`revision`    int(11) NOT NULL,
	PRIMARY KEY(`id`),
	UNIQUE KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
