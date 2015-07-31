@echo off
php -f %~dp0build_task_xml.php
schtasks /Delete /F /TN "git_php_service"
schtasks /create /XML "%~dp0git_php_service.xml" /TN "git_php_service"
set /p DUMMY=Hit ENTER to continue...
