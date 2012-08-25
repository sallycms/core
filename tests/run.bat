@echo off
cd ../../
call phpunit --configuration sally/tests/%1.xml
cd sally/tests/
