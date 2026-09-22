@echo off
setlocal
set "PROJECT_ROOT=%~dp0"
set "PHP_BIN=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"

if not exist "%PHP_BIN%" (
    echo Laragon PHP was not found at %PHP_BIN%.
    echo Update PHP_BIN in start-local-server.bat to your Laragon PHP path, then run it again.
    exit /b 1
)

"%PHP_BIN%" -d "upload_tmp_dir=%PROJECT_ROOT%storage\uploads_tmp" -d "session.save_path=%PROJECT_ROOT%storage\sessions" -S 127.0.0.1:3000 -t "%PROJECT_ROOT%"
