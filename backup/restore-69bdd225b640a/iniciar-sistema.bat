@echo off
setlocal

set "PROJECT_DIR=C:\BolosDaGal"
set "PHP_EXE=C:\php\php.exe"

if not exist "%PHP_EXE%" set "PHP_EXE=php"

cd /d "%PROJECT_DIR%"

start "" http://localhost:8000
"%PHP_EXE%" -S 0.0.0.0:8000 -t "%PROJECT_DIR%"
pause
