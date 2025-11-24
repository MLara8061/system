<?php 
// Incluir configuración y conexión a base de datos
if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
} elseif (file_exists(__DIR__ . '/db_connect.php')) {
    include __DIR__ . '/db_connect.php';
} else {
    die('Error: No se encuentra el archivo de configuración');
}
?>

<div class="container-fluid">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h4 class="mb-0"><i class="fas fa-file-excel"></i> Carga Masiva de Equipos desde Excel</h4>
            </div>
            <div class="card-body">
                <div class="alert" style="background-color: #e3f2fd; border-left: 4px solid #2196F3; color: #1976d2;">
                    <strong><i class="fas fa-info-circle"></i> Instrucciones de uso:</strong>
                    <ol class="mt-3 mb-2">
                        <li><strong>Descarga la plantilla Excel</strong> con listas desplegables y validaciones pre-configuradas</li>
                        <li><strong>Completa los datos</strong> de los equipos (las columnas amarillas tienen listas desplegables ▼)</li>
                        <li><strong>Guarda el archivo</strong> y súbelo usando el formulario de abajo</li>
                    </ol>
                    
                    <div class="mt-3 p-3" style="background-color: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px;">
                        <strong><i class="fas fa-exclamation-triangle text-warning"></i> Campos obligatorios en el Excel:</strong>
                        <ul class="mb-2 mt-2">
                            <li><strong>Serie</strong> - Número único del equipo</li>
                            <li><strong>Nombre</strong> - Nombre descriptivo</li>
                            <li><strong>Modelo</strong> - Modelo del equipo</li>
                            <li><strong>Tipo de Adquisición</strong> - Seleccionar de la lista desplegable (columna F, amarilla)</li>
                            <li><strong>Disciplina</strong> - Seleccionar de la lista desplegable (columna G, amarilla)</li>
                            <li><strong>Proveedor</strong> - Seleccionar de la lista desplegable (columna H, amarilla)</li>
                            <li><strong>Departamento</strong> - Seleccionar de la lista desplegable (columna N, amarilla)</li>
                            <li><strong>Ubicación</strong> - Seleccionar de la lista desplegable (columna O, amarilla)</li>
                            <li><strong>Responsable</strong> - Nombre completo de la persona responsable</li>
                        </ul>
                    </div>
                    
                    <div class="mt-3 p-3" style="background-color: #d1ecf1; border-left: 4px solid #17a2b8; border-radius: 5px;">
                        <strong><i class="fas fa-lightbulb text-info"></i> Datos adicionales que puedes incluir:</strong>
                        <ul class="mb-2 mt-2">
                            <li><strong>Marca, Valor, Cantidad, Características</strong> - Información básica del equipo</li>
                            <li><strong>Voltaje, Amperaje, Frecuencia</strong> - Especificaciones eléctricas (columnas K, L, M)</li>
                            <li><strong>Cargo Responsable</strong> - Lista desplegable (columna Q, amarilla)</li>
                            <li><strong>Fecha Capacitación, Factura, Garantía, Fecha Adquisición</strong> - Información adicional</li>
                        </ul>
                    </div>
                    
                    <div class="mt-3 p-3" style="background-color: #d4edda; border-left: 4px solid #28a745; border-radius: 5px;">
                        <strong><i class="fas fa-check-circle text-success"></i> Notas importantes:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Las <strong>columnas amarillas (F, G, H, N, O, Q)</strong> tienen listas desplegables con datos de tu sistema</li>
                            <li>Haz <strong>clic en la celda</strong> para ver la flecha del desplegable (▼)</li>
                            <li>Las <strong>filas de ejemplo</strong> (EQ-001-2024, EQ-002-2024, EQ-003-2024) se omiten automáticamente</li>
                            <li>Todos los equipos importados tendrán <strong>Mantenimiento Preventivo</strong> por defecto</li>
                            <li>Si hay <strong>especificaciones eléctricas</strong> (voltaje y amperaje), la potencia se calcula automáticamente</li>
                            <li>La plantilla tiene <strong>21 columnas (A-U)</strong> con instrucciones al final del archivo</li>
                        </ul>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card shadow-sm" style="border-top: 3px solid #28a745;">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-download text-success"></i> Descargar Plantilla</h5>
                                <p class="card-text text-muted">Descarga la plantilla de Excel con listas desplegables y validaciones.</p>
                                <a href="generate_excel_template.php" class="btn btn-success btn-block">
                                    <i class="fas fa-file-excel"></i> Descargar Plantilla Excel
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card shadow-sm" style="border-top: 3px solid #007bff;">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-truck text-primary"></i> Gestionar Proveedores</h5>
                                <p class="card-text text-muted">Verifica que los proveedores estén registrados (opcional).</p>
                                <a href="index.php?page=suppliers" class="btn btn-primary btn-block">
                                    <i class="fas fa-list"></i> Ver Lista de Proveedores
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm" style="border-top: 3px solid #667eea;">
                    <div class="card-header" style="background-color: #f8f9fa;">
                        <h5 class="mb-0"><i class="fas fa-cloud-upload-alt text-primary"></i> Subir Archivo Excel</h5>
                    </div>
                    <div class="card-body">
                        <form id="upload-excel-form" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="excel_file"><strong>Seleccionar archivo Excel:</strong></label>
                                <div class="custom-file">
                                    <input type="file" name="excel_file" class="custom-file-input" id="excel_file" accept=".xlsx,.xls" required>
                                    <label class="custom-file-label" for="excel_file">Seleccionar archivo...</label>
                                </div>
                                <small class="form-text text-muted"><i class="fas fa-info-circle"></i> Formatos permitidos: .xlsx, .xls (máximo 10MB)</small>
                            </div>
                            <hr>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-upload"></i> Cargar Equipos
                            </button>
                            <button type="button" class="btn btn-secondary btn-lg ml-2" onclick="$('#upload-excel-form')[0].reset(); $('.custom-file-label').text('Seleccionar archivo...');">
                                <i class="fas fa-times"></i> Limpiar
                            </button>
                        </form>
                    </div>
                </div>

                <div id="upload-result" class="mt-4"></div>
            </div>
        </div>
    </div>
</div>

<style>
.table-sm td, .table-sm th {
    padding: 0.5rem;
    font-size: 0.9rem;
}
.card {
    border-radius: 8px;
}
.card-header {
    border-radius: 8px 8px 0 0 !important;
}
</style>

<script>
// Actualizar nombre del archivo en el input
$('.custom-file-input').on('change', function() {
    var fileName = $(this).val().split('\\').pop();
    $(this).next('.custom-file-label').html(fileName);
});

$('#upload-excel-form').submit(function(e) {
    e.preventDefault();
    
    var formData = new FormData(this);
    formData.append('action', 'upload_excel_equipment');
    
    // Validar que se seleccionó un archivo
    if (!$('#excel_file')[0].files.length) {
        alert('Por favor selecciona un archivo Excel');
        return;
    }
    
    // Mostrar loading con estilos actualizados
    $('#upload-result').html(`
        <div class="alert" style="background-color: #e3f2fd; border-left: 4px solid #2196F3; color: #1976d2;">
            <div class="d-flex align-items-center">
                <div class="spinner-border text-primary mr-3" role="status">
                    <span class="sr-only">Cargando...</span>
                </div>
                <div>
                    <strong><i class="fas fa-sync-alt"></i> Procesando archivo...</strong><br>
                    <small>Esto puede tomar varios minutos dependiendo del tamaño del archivo. Por favor espere.</small>
                </div>
            </div>
        </div>
    `);
    
    // Deshabilitar botón
    $('#upload-excel-form button[type="submit"]').prop('disabled', true);
    
    $.ajax({
        url: 'ajax.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(resp) {
            console.log('Respuesta del servidor:', resp);
            
            try {
                var data = JSON.parse(resp);
                
                if (data.status == 1) {
                    var html = '<div class="alert alert-success alert-dismissible fade show shadow-sm" style="border-left: 4px solid #28a745;">';
                    html += '<button type="button" class="close" data-dismiss="alert">&times;</button>';
                    html += '<h5><i class="fas fa-check-circle"></i> ' + data.msg + '</h5>';
                    
                    if (data.success > 0) {
                        html += '<hr><div class="p-2" style="background-color: #d4edda; border-radius: 5px;">';
                        html += '<p class="mb-0"><strong><i class="fas fa-check text-success"></i> Equipos insertados:</strong> <span class="badge badge-success">' + data.success + '</span></p>';
                        html += '</div>';
                    }
                    
                    if (data.skipped > 0) {
                        html += '<div class="p-2 mt-2" style="background-color: #fff3cd; border-radius: 5px;">';
                        html += '<p class="mb-0"><strong><i class="fas fa-exclamation-circle text-warning"></i> Filas omitidas:</strong> <span class="badge badge-warning">' + data.skipped + '</span></p>';
                        html += '</div>';
                    }
                    
                    if (data.errors && data.errors.length > 0) {
                        html += '<hr><div class="p-2" style="background-color: #f8d7da; border-radius: 5px;">';
                        html += '<strong><i class="fas fa-exclamation-triangle text-danger"></i> Errores encontrados:</strong>';
                        html += '<div class="mt-2" style="max-height: 200px; overflow-y: auto; background-color: white; padding: 10px; border-radius: 5px;">';
                        html += '<ul class="mb-0">';
                        data.errors.forEach(function(error) {
                            html += '<li>' + error + '</li>';
                        });
                        html += '</ul></div></div>';
                    }
                    
                    html += '</div>';
                    $('#upload-result').html(html);
                    
                    // Limpiar formulario
                    $('#upload-excel-form')[0].reset();
                    $('.custom-file-label').text('Seleccionar archivo...');
                    
                    // Recargar después de 4 segundos si no hubo errores
                    if (!data.errors || data.errors.length == 0) {
                        setTimeout(function() {
                            location.href = 'index.php?page=equipment_list';
                        }, 4000);
                    }
                    
                } else {
                    $('#upload-result').html(`
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm" style="border-left: 4px solid #dc3545;">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <h5><i class="fas fa-times-circle"></i> Error</h5>
                            <p class="mb-0">` + data.msg + `</p>
                        </div>
                    `);
                }
            } catch (e) {
                console.error('Error al parsear respuesta:', e);
                console.error('Respuesta recibida:', resp);
                $('#upload-result').html(`
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" style="border-left: 4px solid #dc3545;">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <h5><i class="fas fa-times-circle"></i> Error al procesar la respuesta</h5>
                        <p>La respuesta del servidor no tiene el formato esperado.</p>
                        <details>
                            <summary style="cursor: pointer;">Ver detalles técnicos</summary>
                            <pre class="mt-2 p-2 bg-light" style="border-radius: 5px;">` + resp + `</pre>
                        </details>
                    </div>
                `);
            }
            
            // Rehabilitar botón
            $('#upload-excel-form button[type="submit"]').prop('disabled', false);
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', status, error);
            $('#upload-result').html(`
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" style="border-left: 4px solid #dc3545;">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <h5><i class="fas fa-times-circle"></i> Error de conexión</h5>
                    <p>No se pudo conectar con el servidor. Por favor verifica tu conexión e intenta nuevamente.</p>
                    <small class="text-muted">Error técnico: ` + error + `</small>
                </div>
            `);
            
            // Rehabilitar botón
            $('#upload-excel-form button[type="submit"]').prop('disabled', false);
        }
    });
});
</script>
