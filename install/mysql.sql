-- Sally Database Dump Version 0.9.*
-- Prefix sly_

CREATE TABLE sly_article (id INT UNSIGNED NOT NULL, clang INT UNSIGNED NOT NULL, revision INT UNSIGNED DEFAULT 0 NOT NULL, latest TINYINT(1) DEFAULT '0' NOT NULL, online TINYINT(1) DEFAULT '0' NOT NULL, deleted TINYINT(1) DEFAULT '0' NOT NULL, type VARCHAR(64) NOT NULL, re_id INT UNSIGNED NOT NULL, path VARCHAR(255) NOT NULL, pos INT UNSIGNED NOT NULL, name VARCHAR(255) NOT NULL, catpos INT UNSIGNED NOT NULL, catname VARCHAR(255) NOT NULL, startpage TINYINT(1) DEFAULT '0' NOT NULL, createdate DATETIME NOT NULL, updatedate DATETIME NOT NULL, createuser VARCHAR(128) NOT NULL, updateuser VARCHAR(128) NOT NULL, attributes LONGTEXT NOT NULL, INDEX parents (re_id), INDEX types (type), PRIMARY KEY(id, clang, revision)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
CREATE TABLE sly_article_slice (id INT UNSIGNED AUTO_INCREMENT NOT NULL, article_id INT UNSIGNED NOT NULL, clang INT UNSIGNED NOT NULL, revision INT UNSIGNED DEFAULT 0 NOT NULL, pos INT UNSIGNED NOT NULL, slot VARCHAR(64) NOT NULL, slice_id INT UNSIGNED NOT NULL, createdate DATETIME NOT NULL, updatedate DATETIME NOT NULL, createuser VARCHAR(128) NOT NULL, updateuser VARCHAR(128) NOT NULL, INDEX find_article (article_id, clang, revision), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
CREATE TABLE sly_clang (id INT UNSIGNED AUTO_INCREMENT NOT NULL, revision INT UNSIGNED DEFAULT 0 NOT NULL, name VARCHAR(255) NOT NULL, locale VARCHAR(5) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
CREATE TABLE sly_config (id VARCHAR(255) NOT NULL, value LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
CREATE TABLE sly_file (id INT UNSIGNED AUTO_INCREMENT NOT NULL, revision INT UNSIGNED DEFAULT 0 NOT NULL, category_id INT UNSIGNED NOT NULL, title VARCHAR(255) NOT NULL, filename VARCHAR(255) NOT NULL, originalname VARCHAR(255) NOT NULL, filetype VARCHAR(255) NOT NULL, filesize INT UNSIGNED NOT NULL, width INT UNSIGNED DEFAULT NULL, height INT UNSIGNED DEFAULT NULL, createdate DATETIME NOT NULL, updatedate DATETIME NOT NULL, createuser VARCHAR(128) NOT NULL, updateuser VARCHAR(128) NOT NULL, attributes LONGTEXT DEFAULT NULL, UNIQUE INDEX filenames (filename), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
CREATE TABLE sly_file_category (id INT UNSIGNED AUTO_INCREMENT NOT NULL, revision INT UNSIGNED DEFAULT 0 NOT NULL, name VARCHAR(255) NOT NULL, re_id INT UNSIGNED NOT NULL, path VARCHAR(255) NOT NULL, createdate DATETIME NOT NULL, updatedate DATETIME NOT NULL, createuser VARCHAR(128) NOT NULL, updateuser VARCHAR(128) NOT NULL, attributes LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
CREATE TABLE sly_registry (name VARCHAR(255) NOT NULL, value LONGBLOB NOT NULL, PRIMARY KEY(name)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
CREATE TABLE sly_slice (id INT UNSIGNED AUTO_INCREMENT NOT NULL, module VARCHAR(64) NOT NULL, serialized_values LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
CREATE TABLE sly_user (id INT UNSIGNED AUTO_INCREMENT NOT NULL, revision INT UNSIGNED DEFAULT 0 NOT NULL, login VARCHAR(128) NOT NULL, password VARCHAR(128) NOT NULL, status TINYINT(1) DEFAULT '0' NOT NULL, name VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, timezone VARCHAR(64) DEFAULT NULL, lasttrydate DATETIME DEFAULT NULL, createdate DATETIME NOT NULL, updatedate DATETIME NOT NULL, createuser VARCHAR(128) NOT NULL, updateuser VARCHAR(128) NOT NULL, attributes LONGTEXT DEFAULT NULL, UNIQUE INDEX logins (login), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

-- populate database with some initial data
INSERT INTO sly_clang (name, locale) VALUES ('deutsch', 'de_DE');
INSERT INTO sly_config (id, value) VALUES ('start_article_id', '1');
INSERT INTO sly_config (id, value) VALUES ('notfound_article_id', '1');
INSERT INTO sly_config (id, value) VALUES ('default_clang_id', '1');
INSERT INTO sly_config (id, value) VALUES ('default_article_type', '""');
INSERT INTO sly_config (id, value) VALUES ('projectname', '"SallyCMS-Projekt"');
INSERT INTO sly_config (id, value) VALUES ('timezone', '"Europe/Berlin"');
INSERT INTO sly_config (id, value) VALUES ('default_locale', '"de_de"');
INSERT INTO sly_config (id, value) VALUES ('addons', '[]');
