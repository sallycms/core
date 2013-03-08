-- Sally Database Dump Version 0.9.*
-- Prefix sly_

CREATE TABLE sly_article (id INTEGER NOT NULL, clang INTEGER NOT NULL, revision INTEGER DEFAULT 0 NOT NULL, re_id INTEGER NOT NULL, name VARCHAR(255) NOT NULL, catname VARCHAR(255) NOT NULL, catpos INTEGER NOT NULL, attributes TEXT NOT NULL, startpage BOOLEAN NOT NULL, pos INTEGER NOT NULL, path VARCHAR(255) NOT NULL, type VARCHAR(64) NOT NULL, deleted BOOLEAN NOT NULL, createdate DATETIME NOT NULL, updatedate DATETIME NOT NULL, createuser VARCHAR(255) NOT NULL, updateuser VARCHAR(255) NOT NULL, PRIMARY KEY(id, clang, revision));
CREATE TABLE sly_article_slice (id INTEGER NOT NULL, clang INTEGER NOT NULL, slot VARCHAR(64) NOT NULL, pos INTEGER NOT NULL, slice_id INTEGER DEFAULT 0 NOT NULL, article_id INTEGER NOT NULL, createdate DATETIME NOT NULL, updatedate DATETIME NOT NULL, createuser VARCHAR(255) NOT NULL, updateuser VARCHAR(255) NOT NULL, revision INTEGER DEFAULT 0 NOT NULL, PRIMARY KEY(id));
CREATE INDEX find_article ON sly_article_slice (article_id, clang, revision);
CREATE TABLE sly_clang (id INTEGER NOT NULL, name VARCHAR(255) NOT NULL, locale VARCHAR(5) NOT NULL, revision INTEGER DEFAULT 0 NOT NULL, PRIMARY KEY(id));
CREATE TABLE sly_file (id INTEGER NOT NULL, re_file_id INTEGER NOT NULL, category_id INTEGER NOT NULL, attributes TEXT NULL, filetype VARCHAR(255) NOT NULL, filename VARCHAR(255) NOT NULL, originalname VARCHAR(255) NOT NULL, filesize INTEGER NOT NULL, width INTEGER NOT NULL, height INTEGER NOT NULL, title VARCHAR(255) NOT NULL, createdate DATETIME NOT NULL, updatedate DATETIME NOT NULL, createuser VARCHAR(255) NOT NULL, updateuser VARCHAR(255) NOT NULL, revision INTEGER DEFAULT 0 NOT NULL, PRIMARY KEY(id));
CREATE INDEX filename ON sly_file (filename);
CREATE TABLE sly_file_category (id INTEGER NOT NULL, name VARCHAR(255) NOT NULL, re_id INTEGER NOT NULL, path VARCHAR(255) NOT NULL, attributes TEXT NULL, createdate DATETIME NOT NULL, updatedate DATETIME NOT NULL, createuser VARCHAR(255) NOT NULL, updateuser VARCHAR(255) NOT NULL, revision INTEGER DEFAULT 0 NOT NULL, PRIMARY KEY(id));
CREATE TABLE sly_user (id INTEGER NOT NULL, name VARCHAR(255) NULL, description VARCHAR(255) NULL, login VARCHAR(128) NOT NULL, password CHAR(128), status BOOLEAN NOT NULL, attributes TEXT NULL, lasttrydate DATETIME NULL, timezone VARCHAR(64) NULL, createdate DATETIME NOT NULL, updatedate DATETIME NOT NULL, createuser VARCHAR(255) NOT NULL, updateuser VARCHAR(255) NOT NULL, revision INTEGER DEFAULT 0 NOT NULL, PRIMARY KEY(id));
CREATE TABLE sly_slice (id INTEGER NOT NULL, module VARCHAR(64) NOT NULL, serialized_values LONGTEXT NOT NULL, PRIMARY KEY(id));
CREATE TABLE sly_registry (name VARCHAR(255) NOT NULL, value BLOB NOT NULL, PRIMARY KEY(name));

-- populate database with some initial data
INSERT INTO sly_clang (name, locale) VALUES ('deutsch', 'de_DE');
