<?php /** Sección: Importar asistencia del biométrico  (se incluye desde app/Views/dashboard.php; usa $e, $puede, etc.) */ ?>
<div class="import-container">
    <div class="import-card">
        <div class="import-header">
            <h3>📊 Cargar Asistencia del Dispositivo Biométrico</h3>
        </div>
        <p class="import-subtitle">
            Selecciona el archivo <strong>.xlsx o .xls</strong> exportado para procesar los registros de asistencia mediante el módulo ETL.
        </p>
        
        <form action="<?= url('asistencia/importar') ?>" method="POST" enctype="multipart/form-data" class="import-form">
<?= csrf_campo() ?>
            <div class="file-drop-area">
                <!-- Se actualizó el texto para reflejar ambos formatos -->
                <span class="file-msg" id="fileNameText">📂 Arrastra tu archivo Excel aquí o haz clic para buscar (.xlsx, .xls)</span>
                
                <!-- Se modificó accept para incluir .xls -->
                <input type="file" name="archivo_excel" id="inputExcel" accept=".xlsx, .xls" required class="file-input">
            </div>
            
            <button type="submit" class="btn-primary-etl">
                ⚙️ Procesar Archivo ETL
            </button>
        </form>
    </div>
</div>
