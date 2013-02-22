-- Sally Database Dump Version 0.9.*
-- Prefix sly_

CREATE TABLE sly_article (id INT NOT NULL, clang INT NOT NULL, re_id INT NOT NULL, name VARCHAR(255) NOT NULL, catname VARCHAR(255) NOT NULL, catpos INT NOT NULL, attributes TEXT NOT NULL, startpage BOOLEAN NOT NULL, pos INT NOT NULL, path VARCHAR(255) NOT NULL, status INT NOT NULL, type VARCHAR(64) NOT NULL, createdate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updatedate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, createuser VARCHAR(255) NOT NULL, updateuser VARCHAR(255) NOT NULL, revision INT DEFAULT 0 NOT NULL, PRIMARY KEY(id, clang));
CREATE TABLE sly_article_slice (id SERIAL NOT NULL, clang INT NOT NULL, slot VARCHAR(64) NOT NULL, pos INT NOT NULL, slice_id INT DEFAULT 0 NOT NULL, article_id INT NOT NULL, createdate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updatedate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, createuser VARCHAR(255) NOT NULL, updateuser VARCHAR(255) NOT NULL, revision INT DEFAULT 0 NOT NULL, PRIMARY KEY(id));
CREATE INDEX find_article ON sly_article_slice (article_id, clang);
CREATE TABLE sly_clang (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, locale VARCHAR(5) NOT NULL, revision INT DEFAULT 0 NOT NULL, PRIMARY KEY(id));
CREATE TABLE sly_file (id SERIAL NOT NULL, re_file_id INT NOT NULL, category_id INT NOT NULL, attributes TEXT NULL, filetype VARCHAR(255) NOT NULL, filename VARCHAR(255) NOT NULL, originalname VARCHAR(255) NOT NULL, filesize INT NOT NULL, width INT NOT NULL, height INT NOT NULL, title VARCHAR(255) NOT NULL, createdate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updatedate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, createuser VARCHAR(255) NOT NULL, updateuser VARCHAR(255) NOT NULL, revision INT DEFAULT 0 NOT NULL, PRIMARY KEY(id));
CREATE INDEX filename ON sly_file (filename);
CREATE TABLE sly_file_category (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, re_id INT NOT NULL, path VARCHAR(255) NOT NULL, attributes TEXT NULL, createdate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updatedate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, createuser VARCHAR(255) NOT NULL, updateuser VARCHAR(255) NOT NULL, revision INT DEFAULT 0 NOT NULL, PRIMARY KEY(id));
CREATE TABLE sly_user (id SERIAL NOT NULL, name VARCHAR(255) NULL, description VARCHAR(255) NULL, login VARCHAR(128) NOT NULL, password CHAR(128), status BOOLEAN NOT NULL, attributes TEXT NULL, lasttrydate DATETIME NULL, timezone VARCHAR(64) NULL, createdate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updatedate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, createuser VARCHAR(255) NOT NULL, updateuser VARCHAR(255) NOT NULL, revision INT DEFAULT 0 NOT NULL, PRIMARY KEY(id));
CREATE TABLE sly_slice (id SERIAL NOT NULL, module VARCHAR(64) NOT NULL, serialized_values LONGTEXT NOT NULL, PRIMARY KEY(id));
CREATE TABLE sly_registry (name VARCHAR(255) NOT NULL, value BYTEA NOT NULL, PRIMARY KEY(name));

-- populate database with some initial data
INSERT INTO sly_clang (name, locale) VALUES ('deutsch', 'de_DE');
