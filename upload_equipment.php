<?php include 'db_connect.php' ?>

<div class="container-fluid">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-file-upload"></i> Carga Masiva de Equipos</h4>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong><i class="fas fa-info-circle"></i> Formato del archivo Excel:</strong>
                    <table class="table table-sm table-bordered mt-2 bg-white">
                        <thead class="bg-light">
                            <tr>
                                <th>Columna</th>
                                <th>Campo</th>
                                <th>Descripción</th>
                                <th>Requerido</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>A</td>
                                <td>Serie</td>
                                <td>Número de serie único del equipo</td>
                                <td><span class="badge badge-danger">Sí</span></td>
                            </tr>
                            <tr>
                                <td>B</td>
                                <td>Nombre</td>
                                <td>Nombre del equipo</td>
                                <td><span class="badge badge-warning">Opcional</span></td>
                            </tr>
                            <tr>
                                <td>C</td>
                                <td>Marca</td>
                                <td>Marca del equipo</td>
                                <td><span class="badge badge-warning">Opcional</span></td>
                            </tr>
                            <tr>
                                <td>D</td>
                                <td>Modelo</td>
                                <td>Modelo del equipo</td>
                                <td><span class="badge badge-warning">Opcional</span></td>
                            </tr>
                            <tr>
                                <td>E</td>
                                <td>Tipo de Adquisición</td>
                                <td>Compra, Donación, Comodato, etc.</td>
                                <td><span class="badge badge-warning">Opcional</span></td>
                            </tr>
                            <tr>
                                <td>F</td>
                                <td>Características</td>
                                <td>Descripción de características técnicas</td>
                                <td><span class="badge badge-warning">Opcional</span></td>
                            </tr>
                            <tr>
                                <td>G</td>
                                <td>Disciplina</td>
                                <td>Área o disciplina del equipo</td>
                                <td><span class="badge badge-warning">Opcional</span></td>
                            </tr>
                            <tr>
                                <td>H</td>
                                <td>Proveedor</td>
                                <td>Nombre del proveedor (debe existir en el sistema)</td>
                                <td><span class="badge badge-warning">Opcional</span></td>
                            </tr>
                            <tr>
                                <td>I</td>
                                <td>Cantidad</td>
                                <td>Cantidad de equipos (número entero)</td>
                                <td><span class="badge badge-warning">Opcional</span></td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="mb-0 mt-2"><strong>Nota:</strong> La primera fila debe contener los encabezados de las columnas.</p>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-download text-success"></i> Descargar Plantilla</h5>
                                <p class="card-text">Descarga la plantilla de Excel con el formato correcto para cargar tus equipos.</p>
                                <a href="assets/templates/plantilla_equipos.xlsx" class="btn btn-success" download>
                                    <i class="fas fa-file-excel"></i> Descargar Plantilla Excel
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-users text-primary"></i> Gestionar Proveedores</h5>
                                <p class="card-text">Asegúrate de que los proveedores estén registrados antes de importar.</p>
                                <a href="index.php?page=suppliers" class="btn btn-primary">
                                    <i class="fas fa-truck"></i> Ver Proveedores
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-cloud-upload-alt"></i> Subir Archivo Excel</h5>
                    </div>
                    <div class="card-body">
                        <form id="upload-excel-form" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="excel_file"><strong>Seleccionar archivo Excel:</strong></label>
                                <div class="custom-file">
                                    <input type="file" name="excel_file" class="custom-file-input" id="excel_file" accept=".xlsx,.xls" required>
                                    <label class="custom-file-label" for="excel_file">Seleccionar archivo...</label>
                                </div>
                                <small class="form-text text-muted">Formatos permitidos: .xlsx, .xls (máximo 10MB)</small>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-upload"></i> Cargar Equipos
                            </button>
                            <button type="button" class="btn btn-secondary btn-lg" onclick="$('#upload-excel-form')[0].reset(); $('.custom-file-label').text('Seleccionar archivo...');">
                                <i class="fas fa-times"></i> Cancelar
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
    padding: 0.3rem;
    font-size: 0.9rem;
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
    
    // Mostrar loading
    $('#upload-result').html(`
        <div class="alert alert-info">
            <div class="d-flex align-items-center">
                <div class="spinner-border text-primary mr-3" role="status">
                    <span class="sr-only">Cargando...</span>
                </div>
                <div>
                    <strong>Procesando archivo...</strong><br>
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
                    var html = '<div class="alert alert-success alert-dismissible fade show">';
                    html += '<button type="button" class="close" data-dismiss="alert">&times;</button>';
                    html += '<h5><i class="fas fa-check-circle"></i> ' + data.msg + '</h5>';
                    
                    if (data.success > 0) {
                        html += '<hr><p class="mb-0"><strong>✓ Equipos insertados exitosamente:</strong> ' + data.success + '</p>';
                    }
                    
                    if (data.skipped > 0) {
                        html += '<p class="mb-0"><strong>⊘ Filas omitidas (vacías):</strong> ' + data.skipped + '</p>';
                    }
                    
                    if (data.errors && data.errors.length > 0) {
                        html += '<hr><strong><i class="fas fa-exclamation-triangle"></i> Errores encontrados:</strong>';
                        html += '<div class="mt-2" style="max-height: 200px; overflow-y: auto;">';
                        html += '<ul class="mb-0">';
                        data.errors.forEach(function(error) {
                            html += '<li>' + error + '</li>';
                        });
                        html += '</ul></div>';
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
                        <div class="alert alert-danger alert-dismissible fade show">
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
                    <div class="alert alert-danger alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <h5><i class="fas fa-times-circle"></i> Error al procesar la respuesta</h5>
                        <p>La respuesta del servidor no tiene el formato esperado.</p>
                        <details>
                            <summary>Ver detalles técnicos</summary>
                            <pre class="mt-2 p-2 bg-light">` + resp + `</pre>
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
                <div class="alert alert-danger alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <h5><i class="fas fa-times-circle"></i> Error de conexión</h5>
                    <p>No se pudo conectar con el servidor. Por favor verifica tu conexión e intenta nuevamente.</p>
                    <small>Error técnico: ` + error + `</small>
                </div>
            `);
            
            // Rehabilitar botón
            $('#upload-excel-form button[type="submit"]').prop('disabled', false);
        }
    });
});
</script>
