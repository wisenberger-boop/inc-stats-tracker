@echo off
setlocal

if /I "%~1"=="Development" goto target_ok
if /I "%~1"=="Production" goto target_ok
echo ERROR: Explicit target required: Development or Production.
echo Usage: scripts\deploy-plugin.bat Development [live]
echo        scripts\deploy-plugin.bat Production [live]
exit /b 2

:target_ok
set "LIVE_ARG="
set "PROD_ARG="
if /I "%~2"=="live" set "LIVE_ARG=-Live"
if /I "%~1"=="Production" if /I "%~2"=="live" set "PROD_ARG=-ConfirmProduction"

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0deploy-plugin-ssh.ps1" -Target "%~1" %LIVE_ARG% %PROD_ARG%
if errorlevel 1 exit /b %errorlevel%
endlocal
