@echo off
goto check_Permissions


:install_task
  php -f %~dp0build_task_xml.php
  schtasks /Delete /F /TN "git_php_service" >nul 2>&1
  schtasks /create /XML "%~dp0git_php_service.xml" /TN "git_php_service" >nul 2>&1
  del %~dp0git_php_service.xml
  schtasks /TN "git_php_service" |find ":" > nul 2>&1
  if %errorLevel% == 0 (
    echo Success: Successfuly installed task.
  ) else (
    echo Failure: Cannot install.
  )
  set /p DUMMY=Hit ENTER to continue...
  exit

:check_Permissions
    net session >nul 2>&1
    if %errorLevel% == 0 (
        echo Installing...
        goto install_task
    ) else (
        echo Failure: Administrative permissions required.
        set /p DUMMY=Hit ENTER to continue...
        exit
    )
