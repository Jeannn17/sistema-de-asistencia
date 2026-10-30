-- =====================================================================
-- Migración: registro de cuentas con aprobación de la directiva
--   estado          'pendiente' (recién registrada, no puede iniciar sesión) | 'aprobado'
--   fecha_registro  cuándo se pidió la cuenta (para ordenar las solicitudes)
-- Las cuentas que ya existían quedan aprobadas. Se puede ejecutar varias veces.
-- =====================================================================

ALTER TABLE usuario ADD COLUMN IF NOT EXISTS estado varchar(10) NOT NULL DEFAULT 'aprobado';
ALTER TABLE usuario ADD COLUMN IF NOT EXISTS fecha_registro timestamptz NOT NULL DEFAULT now();

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'usuario_estado_check') THEN
        ALTER TABLE usuario ADD CONSTRAINT usuario_estado_check CHECK (estado IN ('pendiente', 'aprobado'));
    END IF;
END $$;

-- La clave guardada es un hash con sal aleatoria: exigir que sea única no aporta nada
-- y revelaría si dos personas eligen la misma contraseña.
ALTER TABLE usuario DROP CONSTRAINT IF EXISTS administrador_clave_key;

CREATE INDEX IF NOT EXISTS ix_usuario_estado ON usuario (estado);
