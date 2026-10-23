document.addEventListener('DOMContentLoaded', () => {
    const inputExcel = document.getElementById('inputExcel');
    const fileNameText = document.getElementById('fileNameText');

    // Verificación de seguridad por si algún ID no coincide
    if (!inputExcel || !fileNameText) {
        console.warn('Advertencia: No se encontró "inputExcel" o "fileNameText" en esta vista.');
        return;
    }

    inputExcel.addEventListener('change', (e) => {
        const archivos = e.target.files;
        
        if (archivos && archivos.length > 0) {
            const nombreArchivo = archivos[0].name;
            // Muestra el nombre del archivo seleccionado con estilo destacado
            fileNameText.textContent = `📄 Archivo seleccionado: ${nombreArchivo}`;
            fileNameText.style.color = '#1e3a8a';
            fileNameText.style.fontWeight = 'bold';
        } else {
            // Restablece el texto original si se cancela la selección
            fileNameText.textContent = '📂 Arrastra tu archivo Excel aquí o haz clic para buscar (.xlsx, .xls)';
            fileNameText.style.color = '#334155';
            fileNameText.style.fontWeight = '500';
        }
    });
});