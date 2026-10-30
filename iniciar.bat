@echo off
REM Inicia el sistema J.A.N.E. en http://localhost:8000 (requiere PostgreSQL encendido)
cd /d "%~dp0"
echo.
echo   Sistema J.A.N.E. disponible en:  http://localhost:8000
echo   Para detenerlo, cierra esta ventana o pulsa Ctrl+C.
echo.
start "" http://localhost:8000
C:\xampp\php\php.exe -S localhost:8000 -t public servidor.php
pause
