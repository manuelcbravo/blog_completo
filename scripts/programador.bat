@echo off
REM Ejecuta el scheduler de Laravel una vez. Programalo en el Task Scheduler de
REM Windows con disparador "cada 1 minuto" para que las publicaciones
REM programadas salgan solas.
REM
REM   schtasks /create /tn "blog_plataforma scheduler" /tr "C:\laragon\www\blog_plataforma\scripts\programador.bat" /sc minute /mo 1

set "PHP_BIN=C:\laragon\bin\php\php-8.4.16-Win32-vs17-x64\php.exe"
if not exist "%PHP_BIN%" set "PHP_BIN=php"

cd /d "%~dp0.."

"%PHP_BIN%" artisan schedule:run >> storage\logs\scheduler.log 2>&1
