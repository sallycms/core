@echo off
call phpunit --strict --bootstrap bootstrap.php %* tests
php cleanup.php
