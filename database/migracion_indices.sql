-- =====================================================================
-- Migración de rendimiento: índice para las consultas por rango de fechas
-- (ConsultaModel::asistencias, DashboardModel::obtenerResumen, ReporteModel::consultarDias
--  y el max(fecha_hora) del "último día importado").
-- El índice único ux_asistencia_marcacion empieza por id_persona, así que no sirve
-- cuando se filtra solo por fecha. Se puede ejecutar varias veces sin problema.
-- =====================================================================

CREATE INDEX IF NOT EXISTS ix_asistencia_fecha_hora ON asistencia (fecha_hora);

-- Freno a registros masivos desde el formulario público (UsuarioModel::exigirCupoRegistro):
-- se guarda la IP desde la que se pidió la cuenta y se cuentan las solicitudes de la última hora
ALTER TABLE usuario ADD COLUMN IF NOT EXISTS ip_registro varchar(45);
CREATE INDEX IF NOT EXISTS ix_usuario_fecha_registro ON usuario (fecha_registro);
