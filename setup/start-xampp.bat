@echo off
setlocal EnableExtensions

set "APP_ROOT=%~dp0.."
set "XAMPP_ROOT=%XAMPP_ROOT%"
if not defined XAMPP_ROOT set "XAMPP_ROOT=C:\xampp"
set "PHP_BIN=%XAMPP_ROOT%\php\php.exe"
set "DB_HOST=%DB_HOST%"
if not defined DB_HOST set "DB_HOST=127.0.0.1"
set "DB_PORT=%DB_PORT%"
if not defined DB_PORT set "DB_PORT=3306"
set "DB_USER=%DB_USER%"
if not defined DB_USER set "DB_USER=root"
set "DB_PASSWORD=%DB_PASSWORD%"
set "DB_NAME=%DB_NAME%"
if not defined DB_NAME set "DB_NAME=vaccination_management_system"

if not exist "%PHP_BIN%" (
    echo XAMPP PHP was not found at %PHP_BIN%.
    echo Set XAMPP_ROOT to your XAMPP installation directory.
    exit /b 1
)

set "DB_READY="
"%PHP_BIN%" -r "mysqli_report(MYSQLI_REPORT_OFF); $c=@new mysqli(getenv('DB_HOST'),getenv('DB_USER'),getenv('DB_PASSWORD'),'',(int)getenv('DB_PORT')); exit($c->connect_errno ? 1 : 0);" >nul 2>&1
if not errorlevel 1 set "DB_READY=1"

if defined DB_READY goto db_ready

if exist "%XAMPP_ROOT%\mysql_start.bat" (
    start "" "%XAMPP_ROOT%\mysql_start.bat"
) else (
    echo XAMPP MySQL launcher was not found at %XAMPP_ROOT%\mysql_start.bat.
    exit /b 1
)

for /L %%N in (1,1,30) do (
    "%PHP_BIN%" -r "mysqli_report(MYSQLI_REPORT_OFF); $c=@new mysqli(getenv('DB_HOST'),getenv('DB_USER'),getenv('DB_PASSWORD'),'',(int)getenv('DB_PORT')); exit($c->connect_errno ? 1 : 0);" >nul 2>&1
    if not errorlevel 1 goto db_ready
    timeout /t 1 /nobreak >nul
)

echo MySQL is not available on %DB_HOST%:%DB_PORT%.
echo Start MySQL in XAMPP or set DB_PORT/DB_PASSWORD correctly.
exit /b 1

:db_ready
cd /d "%APP_ROOT%"
set "DB_HOST=%DB_HOST%"
set "DB_PORT=%DB_PORT%"
set "DB_NAME=%DB_NAME%"
set "DB_USER=%DB_USER%"
set "DB_PASSWORD=%DB_PASSWORD%"
"%PHP_BIN%" setup\scripts\deploy.php
if errorlevel 1 exit /b 1

set "APP_PORT=%APP_PORT%"
if not defined APP_PORT set APP_PORT=8080
start "" "%PHP_BIN%" -S "127.0.0.1:%APP_PORT%" -t "%APP_ROOT%"
start "" "http://127.0.0.1:%APP_PORT%/index.php"
echo ImmuniCare is ready.
