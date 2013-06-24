-- Sally Database Dump Version 0.9.*
-- Prefix sly_

CREATE TABLE sly_article (id INT NOT NULL, clang INT NOT NULL, revision INT DEFAULT 0 NOT NULL, online BOOLEAN DEFAULT 'false' NOT NULL, deleted BOOLEAN DEFAULT 'false' NOT NULL, type VARCHAR(64) NOT NULL, re_id INT NOT NULL, path VARCHAR(255) NOT NULL, pos INT NOT NULL, name VARCHAR(255) NOT NULL, catpos INT NOT NULL, catname VARCHAR(255) NOT NULL, startpage BOOLEAN DEFAULT 'false' NOT NULL, createdate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updatedate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, createuser VARCHAR(128) NOT NULL, updateuser VARCHAR(128) NOT NULL, attributes TEXT NOT NULL, PRIMARY KEY(id, clang, revision));
CREATE INDEX parents ON sly_article (re_id);
CREATE INDEX types ON sly_article (type);
CREATE TABLE sly_article_slice (id SERIAL NOT NULL, article_id INT NOT NULL, clang INT NOT NULL, revision INT DEFAULT 0 NOT NULL, pos INT NOT NULL, slot VARCHAR(64) NOT NULL, slice_id INT NOT NULL, createdate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updatedate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, createuser VARCHAR(128) NOT NULL, updateuser VARCHAR(128) NOT NULL, PRIMARY KEY(id));
CREATE INDEX find_article ON sly_article_slice (article_id, clang, revision);
CREATE TABLE sly_clang (id SERIAL NOT NULL, revision INT DEFAULT 0 NOT NULL, name VARCHAR(255) NOT NULL, locale VARCHAR(5) NOT NULL, PRIMARY KEY(id));
CREATE TABLE sly_config (id VARCHAR(255) NOT NULL, value TEXT NOT NULL, PRIMARY KEY(id));
CREATE TABLE sly_file (id SERIAL NOT NULL, revision INT DEFAULT 0 NOT NULL, category_id INT NOT NULL, title VARCHAR(255) NOT NULL, filename VARCHAR(255) NOT NULL, originalname VARCHAR(255) NOT NULL, filetype VARCHAR(255) NOT NULL, filesize INT NOT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, createdate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updatedate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, createuser VARCHAR(128) NOT NULL, updateuser VARCHAR(128) NOT NULL, attributes TEXT DEFAULT NULL, PRIMARY KEY(id));
CREATE INDEX filename ON sly_file (filename);
CREATE TABLE sly_file_category (id SERIAL NOT NULL, revision INT DEFAULT 0 NOT NULL, name VARCHAR(255) NOT NULL, re_id INT NOT NULL, path VARCHAR(255) NOT NULL, createdate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updatedate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, createuser VARCHAR(128) NOT NULL, updateuser VARCHAR(128) NOT NULL, attributes TEXT DEFAULT NULL, PRIMARY KEY(id));
CREATE TABLE sly_registry (name VARCHAR(255) NOT NULL, value BYTEA NOT NULL, PRIMARY KEY(name));
CREATE TABLE sly_slice (id SERIAL NOT NULL, module VARCHAR(64) NOT NULL, serialized_values TEXT NOT NULL, PRIMARY KEY(id));
CREATE TABLE sly_user (id SERIAL NOT NULL, revision INT DEFAULT 0 NOT NULL, login VARCHAR(128) NOT NULL, password VARCHAR(128) NOT NULL, status BOOLEAN DEFAULT 'false' NOT NULL, name VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, timezone VARCHAR(64) DEFAULT NULL, lasttrydate TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, createdate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updatedate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, createuser VARCHAR(128) NOT NULL, updateuser VARCHAR(128) NOT NULL, attributes TEXT DEFAULT NULL, PRIMARY KEY(id));
CREATE UNIQUE INDEX logins ON sly_user (login);

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
