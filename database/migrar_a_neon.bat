@echo off
REM =====================================================================
REM Copia la base de datos LOCAL (sistema_asistencia) a Neon: estructura + datos.
REM   1. Exporta la base local con pg_dump (te pide la clave del usuario postgres local)
REM   2. La carga en Neon con psql (pega la "connection string" de Neon cuando la pida)
REM Ejecutalo UNA sola vez, con la base de Neon recien creada y vacia.
REM Ninguna clave se guarda en archivos.
REM =====================================================================
setlocal
set "PGBIN=C:\Program Files\PostgreSQL\18\bin"
set "RESPALDO=%~dp0respaldo_para_neon.sql"

if not exist "%PGBIN%\pg_dump.exe" (
    echo No se encontro pg_dump en %PGBIN%. Ajusta la variable PGBIN de este archivo.
    goto :fin
)

echo.
echo === Paso 1 de 2: exportar la base local "sistema_asistencia" ===
echo Escribe la clave de tu PostgreSQL local (la del usuario postgres) y pulsa Enter.
echo (Mientras escribes no se ve nada en pantalla: es normal.)
"%PGBIN%\pg_dump.exe" -h localhost -U postgres -d sistema_asistencia --no-owner --no-privileges --encoding=UTF8 -W -f "%RESPALDO%"
if errorlevel 1 (
    echo.
    echo No se pudo exportar la base local. Revisa la clave y que PostgreSQL este encendido.
    goto :fin
)
echo Exportada en: %RESPALDO%

echo.
echo === Paso 2 de 2: cargar en Neon ===
echo Pega la connection string de Neon (boton Conecta, con "Connection pooling" apagado).
echo Para pegar en esta ventana: clic derecho. Luego pulsa Enter.
set /p "NEON_URL=> "
if "%NEON_URL%"=="" (
    echo No escribiste la direccion de Neon.
    goto :fin
)

"%PGBIN%\psql.exe" "%NEON_URL%" -v ON_ERROR_STOP=0 -f "%RESPALDO%"
echo.
echo === Comprobacion en Neon ===
"%PGBIN%\psql.exe" "%NEON_URL%" -c "SELECT (SELECT count(*) FROM usuario) AS usuarios, (SELECT count(*) FROM personal) AS trabajadores, (SELECT count(*) FROM asistencia) AS marcaciones;"
echo.
echo Listo. Si arriba ves los numeros de usuarios, trabajadores y marcaciones, la copia salio bien.
echo (Un aviso sobre "transaction_timeout" o "pgcrypto" ya existente no importa.)
echo Se borra el respaldo temporal, porque contiene los datos del personal.
del "%RESPALDO%"

:fin
set "NEON_URL="
endlocal
pause
