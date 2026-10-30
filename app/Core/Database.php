<?php

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Conexión única a PostgreSQL. Los datos de acceso salen de .env o de las variables de entorno:
 *   - DATABASE_URL=postgresql://usuario:clave@host/base?sslmode=require  (como la entrega Neon), o
 *   - DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS y, opcional, DB_SSLMODE (disable | require | verify-full)
 * Si existe DATABASE_URL, tiene prioridad.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function conectar(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $c = self::datosDeConexion();
        $dsn = "pgsql:host={$c['host']};port={$c['port']};dbname={$c['name']}";
        if ($c['sslmode'] !== '') {
            $dsn .= ";sslmode={$c['sslmode']}";   // Neon y la mayoría de los hosting exigen conexión cifrada
        }

        try {
            $pdo = new PDO($dsn, $c['user'], $c['pass'], [PDO::ATTR_TIMEOUT => 10]);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            // La misma zona horaria que PHP, para que now() y CURRENT_DATE coincidan con date()
            $pdo->prepare('SELECT set_config(\'TimeZone\', ?, false)')->execute([date_default_timezone_get()]);
        } catch (PDOException $e) {
            // No se relanza el error original: podría incluir datos de la conexión
            error_log('No se pudo conectar a la base de datos: ' . $e->getMessage());
            throw new RuntimeException('No se pudo conectar a la base de datos.');
        }

        return self::$pdo = $pdo;
    }

    /** @return array{host:string, port:string, name:string, user:string, pass:string, sslmode:string} */
    private static function datosDeConexion(): array
    {
        $url = (string) Env::get('DATABASE_URL', '');
        if ($url !== '') {
            $p = parse_url($url);
            if ($p === false || empty($p['host']) || !in_array($p['scheme'] ?? '', ['postgres', 'postgresql'], true)) {
                throw new RuntimeException('DATABASE_URL no es una dirección de PostgreSQL válida.');
            }
            parse_str($p['query'] ?? '', $opciones);
            return [
                'host'    => $p['host'],
                'port'    => (string) ($p['port'] ?? '5432'),
                'name'    => ltrim(rawurldecode($p['path'] ?? ''), '/'),
                'user'    => rawurldecode($p['user'] ?? ''),
                'pass'    => rawurldecode($p['pass'] ?? ''),
                'sslmode' => (string) ($opciones['sslmode'] ?? 'require'),
            ];
        }

        return [
            'host'    => (string) Env::get('DB_HOST', 'localhost'),
            'port'    => (string) Env::get('DB_PORT', '5432'),
            'name'    => (string) Env::get('DB_NAME', 'sistema_asistencia'),
            'user'    => (string) Env::get('DB_USER', 'postgres'),
            'pass'    => (string) Env::get('DB_PASS', ''),
            'sslmode' => (string) Env::get('DB_SSLMODE', ''),
        ];
    }
}
