@echo off
setlocal

:: ── Configuration ────────────────────────────────────────────────────────────
:: Keep VERSION in sync with IST_VERSION in plugin/inc-stats-tracker/inc-stats-tracker.php
set PLUGIN_NAME=inc-stats-tracker
set VERSION=1.0.1-rc1

:: Paths resolved relative to this script's location (tools\)
set ROOT=%~dp0..
set PLUGIN_DIR=%ROOT%\plugin\%PLUGIN_NAME%
set RELEASES_DIR=%ROOT%\build\releases
set ZIP_PATH=%RELEASES_DIR%\%PLUGIN_NAME%-%VERSION%.zip
set CSV_STAGING=%PLUGIN_DIR%\docs\source-assets\csv

:: ── Safety check 1: plugin folder must exist ─────────────────────────────────
if not exist "%PLUGIN_DIR%\" (
    echo.
    echo  ERROR: Plugin folder not found.
    echo         Expected: %PLUGIN_DIR%
    echo         Ensure this script is inside the tools\ folder.
    goto :fail
)

:: ── Safety check 2: no CSV files in the runtime staging folder ───────────────
dir /b "%CSV_STAGING%\*.csv" >nul 2>&1
if not errorlevel 1 (
    echo.
    echo  ERROR: CSV files found in the import staging folder.
    echo         Location: %CSV_STAGING%
    echo.
    echo         These are runtime-only import files and must NOT ship in a release.
    echo         Remove all .csv files from that folder, then re-run this script.
    echo.
    dir /b "%CSV_STAGING%\*.csv"
    echo.
    goto :fail
)

:: ── Safety check 3: warn before overwriting an existing ZIP ──────────────────
if exist "%ZIP_PATH%" (
    echo.
    echo  WARNING: %PLUGIN_NAME%-%VERSION%.zip already exists.
    echo           Press any key to overwrite it, or Ctrl+C to cancel.
    pause >nul
    del "%ZIP_PATH%"
)

:: ── Create releases folder if needed ─────────────────────────────────────────
if not exist "%RELEASES_DIR%\" (
    mkdir "%RELEASES_DIR%"
    echo  Created output folder: %RELEASES_DIR%
)

:: ── Package ───────────────────────────────────────────────────────────────────
echo.
echo  Packaging %PLUGIN_NAME% v%VERSION% ...
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0package-plugin.ps1" ^
    -PluginName "%PLUGIN_NAME%" ^
    -Version   "%VERSION%"     ^
    -PluginDir "%PLUGIN_DIR%"  ^
    -ZipPath   "%ZIP_PATH%"

if errorlevel 1 (
    echo.
    echo  ERROR: PowerShell packaging script failed. See output above.
    goto :fail
)

:: ── Safety check 4: verify the ZIP was actually created ──────────────────────
if not exist "%ZIP_PATH%" (
    echo.
    echo  ERROR: ZIP file was not created. Check PowerShell output above.
    goto :fail
)

echo.
echo  Done: %ZIP_PATH%
echo.
endlocal
pause
exit /b 0

:fail
echo.
echo  Package FAILED. See errors above.
echo.
endlocal
pause
exit /b 1
