<?php include 'db_connect.php' ?>

<div class="container-fluid">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h4 class="mb-0"><i class="fas fa-file-excel"></i> Carga Masiva de Equipos desde Excel</h4>
            </div>
            <div class="card-body">
                <div class="alert" style="background-color: #e3f2fd; border-left: 4px solid #2196F3; color: #1976d2;">
                    <strong><i class="fas fa-info-circle"></i> Formato del archivo Excel:</strong>
                    <table class="table table-sm table-bordered mt-3" style="background: white;">
                        <thead style="background-color: #f5f5f5;">
                            <tr>
                                <th style="width: 80px;">Columna</th>
                                <th>Campo</th>
                                <th>Descripción</th>
                                <th style="width: 100px;">Requerido</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>A</strong></td>
                                <td><strong>Serie</strong></td>
                                <td>Número de serie único del equipo</td>
                                <td><span class="badge badge-danger">Obligatorio</span></td>
                            </tr>
                            <tr>
                                <td><strong>B</strong></td>
                                <td>Nombre</td>
                                <td>Nombre del equipo</td>
                                <td><span class="badge badge-secondary">Opcional</span></td>
                            </tr>
                            <tr>
                                <td><strong>C</strong></td>
                                <td>Marca</td>
                                <td>Marca del equipo</td>
                                <td><span class="badge badge-secondary">Opcional</span></td>
                            </tr>
                            <tr>
                                <td><strong>D</strong></td>
                                <td>Modelo</td>
                                <td>Modelo del equipo</td>
                                <td><span class="badge badge-secondary">Opcional</span></td>
                            </tr>
                            <tr>
                                <td><strong>E</strong></td>
                                <td>Tipo de Adquisición</td>
                                <td>Compra, Donación, Comodato, etc.</td>
                                <td><span class="badge badge-secondary">Opcional</span></td>
                            </tr>
                            <tr>
                                <td><strong>F</strong></td>
                                <td>Características</td>
                                <td>Descripción de características técnicas</td>
                                <td><span class="badge badge-secondary">Opcional</span></td>
                            </tr>
                            <tr>
                                <td><strong>G</strong></td>
                                <td>Disciplina</td>
                                <td>Área o disciplina del equipo</td>
                                <td><span class="badge badge-secondary">Opcional</span></td>
                            </tr>
                            <tr>
                                <td><strong>H</strong></td>
                                <td>Proveedor</td>
                                <td>Nombre del proveedor (si no existe, se omite)</td>
                                <td><span class="badge badge-secondary">Opcional</span></td>
                            </tr>
                            <tr>
                                <td><strong>I</strong></td>
                                <td>Cantidad</td>
                                <td>Cantidad de equipos (por defecto: 1)</td>
                                <td><span class="badge badge-secondary">Opcional</span></td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="mt-3 p-2" style="background-color: #fff3cd; border-left: 4px solid #ffc107;">
                        <strong><i class="fas fa-lightbulb text-warning"></i> Notas importantes:</strong>
                        <ul class="mb-0 mt-2">
                            <li>La primera fila debe contener los encabezados de las columnas</li>
                            <li><strong>Solo el campo "Serie" es obligatorio</strong>, los demás pueden quedar vacíos</li>
                            <li>Si un proveedor no existe en el sistema, el equipo se importará sin proveedor</li>
                            <li>Los campos vacíos no interferirán con la importación</li>
                        </ul>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card shadow-sm" style="border-top: 3px solid #28a745;">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-download text-success"></i> Descargar Plantilla</h5>
                                <p class="card-text text-muted">Descarga la plantilla de Excel con el formato correcto y ejemplos.</p>
                                <a href="ajax.php?action=download_template" class="btn btn-success btn-block">
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
