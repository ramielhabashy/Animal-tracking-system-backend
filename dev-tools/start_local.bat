@echo off
REM Local development start script
REM Usage: start_local.bat

cd /d "%~dp0..\"
if not exist ".env" (
    echo Creating .env from .env.example...
    copy .env.example .env
    php artisan key:generate
)

echo Starting Laravel server on http://localhost:8050...
php artisan serve --host=localhost --port=8050
