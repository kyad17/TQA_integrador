@echo off
setlocal
cd /d "%~dp0"
if not exist "storage\app\private\operador.hash" (
    php artisan tqa:clave
    if errorlevel 1 goto fallo
)
echo Abre http://127.0.0.1:8010 en tu navegador. Para detener TQA presiona Ctrl+C.
php artisan serve --host=127.0.0.1 --port=8010 --tries=1
if errorlevel 1 goto fallo
exit /b 0
:fallo
pause
exit /b 1
