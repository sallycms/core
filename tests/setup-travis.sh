#!/bin/sh

mysql -e "CREATE DATABASE IF NOT EXISTS sally_test CHARACTER SET utf8 COLLATE utf8_general_ci"
psql -c 'create database sally_test;' -U postgres
