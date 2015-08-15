@echo off
goto check_Permissions


:install_task
  schtasks /TN "git_php_service" |find ":" > nul 2>&1
  if %errorLevel% == 1 (
    php -f %~dp0build_task_xml.php
    schtasks /create /XML "%~dp0git_php_service.xml" /TN "git_php_service" >nul 2>&1
    del %~dp0git_php_service.xml
  )
  schtasks /TN "git_php_service" |find ":" > nul 2>&1
  if %errorLevel% == 0 (
    echo Success: Successfuly installed task.
  ) else (
    echo Failure: Cannot install.
  )
  WHERE sh > nul 2>&1
  if %errorLevel% == 1 (
    SETX /M PATH "%PATH%;%programfiles(x86)%\Git\bin"
  )
  set /p DUMMY=Hit ENTER to continue...
  exit

:check_Permissions
    net session >nul 2>&1
    if NOT %errorLevel% == 0 (
        echo Failure: Administrative permissions required.
        set /p DUMMY=Hit ENTER to continue...
        exit
    )
    WHERE git > nul 2>&1
    if NOT %errorLevel% == 0 (
      echo Failure: GitSCM for Windows Require.
      set /p DUMMY=Hit ENTER to continue...
      exit
    )
    WHERE php > nul 2>&1
    if NOT %errorLevel% == 0 (
      echo Failure: PHP Require.
      set /p DUMMY=Hit ENTER to continue...
      exit
    )
    echo Installing...
    goto install_task
