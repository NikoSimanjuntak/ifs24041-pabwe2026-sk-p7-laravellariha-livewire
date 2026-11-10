@echo off
REM Menjalankan seluruh pengujian + coverage (butuh Xdebug/PCOV aktif)
set XDEBUG_MODE=coverage
call php artisan config:clear
call vendor\bin\phpunit --coverage-text
