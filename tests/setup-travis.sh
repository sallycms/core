#!/bin/sh

resetVersion=0

if [ $TRAVIS_PHP_VERSION = '5.2' ]
then
  phpenv global 5.3
  resetVersion=1
fi

composer self-update
composer update --dev

mysql -e "CREATE DATABASE sally_test CHARACTER SET utf8 COLLATE utf8_general_ci"
mysql --database=sally_test < install/mysql.sql
mysql --database=sally_test -e "INSERT INTO sly_user (id,name,description,login,password,status,attributes,lasttrydate,timezone,createdate,updatedate,createuser,updateuser) VALUES (1, 'Admin', '', 'admin', 'c5e4335577bb89540b15e2f5251e8bc02ced5b32', 1, '{\"isAdmin\":true}', '2011-10-03 04:15:32', 'Europe/Berlin', '2011-04-13 02:37:08', '2011-07-22 02:05:11', 'setup', 'admin')"

if [ $resetVersion -eq 1 ]
then
  phpenv global 5.2
fi
