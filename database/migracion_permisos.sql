-- =====================================================================
-- Migración del módulo Permisos / Justificaciones (PostgreSQL)
-- Ejecutar UNA vez sobre la base "sistema_asistencia".
-- La tabla justificacion solo tenía: id, id_persona, fecha_inicio, fecha_fin, motivo.
-- Se agregan el tipo de justificación, la hora (para salida temprana / llegada tarde)
-- y la fecha en que se registró.
-- =====================================================================
BEGIN;

ALTER TABLE public.justificacion ADD COLUMN IF NOT EXISTS tipo           varchar(30);
ALTER TABLE public.justificacion ADD COLUMN IF NOT EXISTS hora           time;
ALTER TABLE public.justificacion ADD COLUMN IF NOT EXISTS fecha_registro timestamp DEFAULT now();

UPDATE public.justificacion SET tipo = 'permiso_dia' WHERE tipo IS NULL;
ALTER TABLE public.justificacion ALTER COLUMN tipo SET NOT NULL;

ALTER TABLE public.justificacion DROP CONSTRAINT IF EXISTS justificacion_tipo_check;
ALTER TABLE public.justificacion
    ADD CONSTRAINT justificacion_tipo_check
    CHECK (tipo IN ('reposo', 'accidente_laboral', 'permiso_dia', 'salida_temprana', 'llegada_tarde'));

ALTER TABLE public.justificacion DROP CONSTRAINT IF EXISTS justificacion_fechas_check;
ALTER TABLE public.justificacion
    ADD CONSTRAINT justificacion_fechas_check CHECK (fecha_fin >= fecha_inicio);

-- Para buscar rápido por trabajador y rango de fechas
CREATE INDEX IF NOT EXISTS ix_justificacion_persona_fechas
    ON public.justificacion (id_persona, fecha_inicio, fecha_fin);

COMMIT;
