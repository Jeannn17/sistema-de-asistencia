# Publicar el sistema J.A.N.E. en internet (Render + Neon)

- **Neon** guarda la base de datos PostgreSQL (plan gratuito: no se vence).
- **Render** ejecuta la aplicación PHP con el `Dockerfile` de este repositorio (plan gratuito).

> Plan gratuito de Render: si pasan **15 minutos sin visitas**, la aplicación se apaga y la
> siguiente visita tarda ~1 minuto en cargar. Antes de una presentación, ábrela unos minutos antes.

---

## 1. Crear la base de datos en Neon

1. En <https://console.neon.tech>, **Crear proyecto**:
   - *Nombre*: `sistema-jane`
   - *Región*: **AWS US East 2 (Ohio)**
   - *Servicios*: solo **Base de datos Postgres** (los demás apagados)
2. En el proyecto, botón verde **Conecta**:
   - Apaga **Connection pooling** (el host no debe decir `-pooler`).
   - Copia la **cadena de conexión** (`postgresql://neondb_owner:...@ep-....us-east-2.aws.neon.tech/neondb?sslmode=require`).
   - Es la llave de tu base de datos: no la subas a GitHub ni la compartas.

## 2. Copiar tus datos locales a Neon

1. Con PostgreSQL encendido en tu PC, doble clic en **`database\migrar_a_neon.bat`**.
2. Escribe la clave de tu PostgreSQL local (no se ve mientras escribes) y pulsa Enter.
3. Pega la cadena de Neon (clic derecho en la ventana) y pulsa Enter.
4. Al final verás cuántos usuarios, trabajadores y marcaciones quedaron en Neon.
   El script borra solo el respaldo temporal.

### Si el script falla con "el servidor ha cerrado la conexión inesperadamente"

Tu red (antivirus o proveedor de internet) está cortando las conexiones de PostgreSQL (puerto 5432).
Render no tiene ese problema; para copiar los datos usa el navegador (HTTPS):

1. Genera `database/respaldo_para_neon.sql` con `pg_dump --inserts --no-owner --no-privileges`,
   sin las líneas `\restrict`, `\unrestrict`, `SET transaction_timeout` ni `COMMENT ON EXTENSION`,
   y envuelto en `BEGIN; ... COMMIT;`.
2. Ábrelo con el Bloc de notas, **Ctrl+A** y **Ctrl+C**.
3. En Neon: menú **SQL Editor** → pega → **Run**. Al final se muestran los totales.
4. Borra el archivo (contiene los datos del personal; git ya lo ignora).

## 3. Subir el código a GitHub

Todo debe estar en la rama `main`, porque Render despliega desde ahí.
El archivo `.env` **no** se sube (está en `.gitignore`).

## 4. Crear el servicio en Render

1. Entra a <https://render.com> con tu cuenta de GitHub.
2. **New → Blueprint** → elige el repositorio `sistema-de-asistencia`.
   Render lee `render.yaml` y prepara el servicio `sistema-jane`.
3. Te pedirá **`DATABASE_URL`**: pega la cadena de Neon.
4. **Apply**. La primera construcción tarda unos minutos.
5. Tendrás una dirección como `https://sistema-jane.onrender.com`.

> A mano: **New → Web Service** → repositorio → *Runtime*: **Docker**, *Instance type*: **Free**,
> y en *Environment* las mismas variables de `render.yaml`.

## 5. Probar

1. Abre la dirección de Render: debe salir el login.
2. Entra con `itaca_32` o `jeanM` y revisa Inicio, Trabajadores, Reportes (descarga un PDF) y Sección.
3. Importa un informe del biométrico (`.xls` o `.xlsx`).

Los errores del servidor se ven en Render → servicio → **Logs**.

---

## Seguridad

- La base de Neon tiene su propia clave larga: no la pongas en el código.
- `APP_DEBUG=false` en Render (ya viene así en `render.yaml`).
- `config/database.php` (con la clave local) se quitó del repositorio, pero **sigue en el historial de git**:
  no reutilices esa clave en ningún otro lado.

## Archivos de despliegue

| Archivo | Para qué sirve |
|---|---|
| `Dockerfile` | PHP 8.4 + Apache, extensiones `pdo_pgsql`, `zip`, `gd` y Composer |
| `docker/apache.conf` | Apache sirve solo `public/` |
| `docker/php.ini` | Límites de subida (12 MB), zona horaria, errores ocultos |
| `docker/iniciar.sh` | Apache escucha en el puerto que asigna Render (`PORT`) |
| `render.yaml` | Configuración del servicio en Render (Blueprint) |
| `database/migrar_a_neon.bat` | Copia la base local a Neon |
| `.env.example` | Todas las variables, con explicación |

## Seguir trabajando en tu PC

Nada cambia: `iniciar.bat` sigue usando tu PostgreSQL local y tu `.env`.
Cada `git push` a `main` vuelve a desplegar en Render.
