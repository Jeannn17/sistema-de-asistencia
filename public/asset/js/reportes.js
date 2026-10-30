// =========================================
// VISTA REPORTES: muestra el rango de fechas que tendrá el PDF.
// Es solo una vista previa: el rango real lo calcula ReporteModel::calcularPeriodo (servidor).
// =========================================
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formReporte');
    if (!form) return;

    const fecha = document.getElementById('reporteFecha');
    const rango = document.getElementById('reporteRango');
    const dosDigitos = n => String(n).padStart(2, '0');
    const formato = d => `${dosDigitos(d.getDate())}/${dosDigitos(d.getMonth() + 1)}/${d.getFullYear()}`;

    function periodo(tipo, d) {
        const anio = d.getFullYear();
        const mes = d.getMonth();
        if (tipo === 'semanal') {
            const desde = new Date(d);
            desde.setDate(d.getDate() - ((d.getDay() + 6) % 7));   // lunes de esa semana
            const hasta = new Date(desde);
            hasta.setDate(desde.getDate() + 6);
            return [desde, hasta];
        }
        if (tipo === 'quincenal') {
            return d.getDate() <= 15
                ? [new Date(anio, mes, 1), new Date(anio, mes, 15)]
                : [new Date(anio, mes, 16), new Date(anio, mes + 1, 0)];
        }
        return [new Date(anio, mes, 1), new Date(anio, mes + 1, 0)];
    }

    // "Incluir el detalle diario" solo aplica al resumen: la planilla detallada ya trae cada día
    const opcionDetalle = document.getElementById('reporteOpcionDetalle');
    function actualizarOpcionDetalle() {
        const reporte = form.querySelector('input[name="tipo"]:checked');
        if (opcionDetalle) {
            opcionDetalle.style.visibility = reporte && reporte.value === 'detallado' ? 'hidden' : 'visible';
        }
    }

    function actualizar() {
        actualizarOpcionDetalle();
        const tipo = form.querySelector('input[name="frecuencia"]:checked');
        if (!tipo || !fecha.value) {
            rango.textContent = '';
            return;
        }
        const [a, m, d] = fecha.value.split('-').map(Number);
        const [desde, hasta] = periodo(tipo.value, new Date(a, m - 1, d));
        rango.textContent = `📅 El reporte abarcará del ${formato(desde)} al ${formato(hasta)}`;
    }

    form.addEventListener('change', actualizar);
    actualizar();
});
