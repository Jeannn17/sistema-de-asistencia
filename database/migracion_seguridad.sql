-- =====================================================================
-- Migración de seguridad: bloqueo temporal por intentos fallidos de login
-- (la usa UsuarioModel: fallosRecientes, registrarFallo, limpiarFallos)
-- Se puede ejecutar varias veces sin problema.
-- =====================================================================

CREATE TABLE IF NOT EXISTS login_intentos (
    id      bigserial    PRIMARY KEY,
    usuario varchar(50)  NOT NULL,
    ip      varchar(45)  NOT NULL,          -- 45 = largo máximo de una IPv6
    fecha   timestamptz  NOT NULL DEFAULT now()
);

-- Las consultas siempre filtran por fecha reciente y por usuario o por IP
CREATE INDEX IF NOT EXISTS ix_login_intentos_usuario_fecha ON login_intentos (usuario, fecha);
CREATE INDEX IF NOT EXISTS ix_login_intentos_ip_fecha      ON login_intentos (ip, fecha);
