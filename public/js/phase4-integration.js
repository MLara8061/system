/**
 * public/js/phase4-integration.js
 * Ejemplos de integración Frontend con nuevos endpoints (Fase 4)
 * Demuestra cómo usar los nuevos endpoints AJAX con jQuery
 */

// ============================================================================
// USUARIO - Ejemplos de integración
// ============================================================================

var UserAPI = {
    /**
     * Crear nuevo usuario
     */
    create: function(formData) {
        return $.ajax({
            url: './ajax/user.php?action=create',
            type: 'POST',
            data: formData,
            dataType: 'json'
        });
    },
    
    /**
     * Actualizar usuario
     */
    update: function(userId, formData) {
        formData.id = userId;
        return $.ajax({
            url: './ajax/user.php?action=update',
            type: 'POST',
            data: formData,
            dataType: 'json'
        });
    },
    
    /**
     * Obtener usuario
     */
    get: function(userId) {
        return $.ajax({
            url: './ajax/user.php?action=get&id=' + userId,
            type: 'GET',
            dataType: 'json'
        });
    },
    
    /**
     * Listar usuarios
     */
    list: function(role) {
        var url = './ajax/user.php?action=list';
        if (role) url += '&role=' + role;
        
        return $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json'
        });
    },
    
    /**
     * Buscar usuarios
     */
    search: function(query) {
        return $.ajax({
            url: './ajax/user.php?action=search&q=' + encodeURIComponent(query),
            type: 'GET',
            dataType: 'json'
        });
    },
    
    /**
     * Cambiar contraseña
     */
    changePassword: function(userId, oldPassword, newPassword) {
        return $.ajax({
            url: './ajax/user.php?action=change_password',
            type: 'POST',
            data: {
                id: userId,
                old_password: oldPassword,
                new_password: newPassword
            },
            dataType: 'json'
        });
    },
    
    /**
     * Eliminar usuario
     */
    delete: function(userId) {
        if (!confirm('¿Está seguro de que desea eliminar este usuario?')) {
            return $.Deferred().reject('Cancelado por usuario');
        }
        
        return $.ajax({
            url: './ajax/user.php?action=delete',
            type: 'POST',
            data: { id: userId },
            dataType: 'json'
        });
    }
};

// Ejemplo: Crear usuario desde formulario
$('#userForm').on('submit', function(e) {
    e.preventDefault();
    
    UserAPI.create($(this).serialize())
        .done(function(response) {
            if (response.success) {
                toastr.success(response.message);
                // Recargar tabla, etc.
                location.reload();
            } else {
                toastr.error(response.message);
            }
        })
        .fail(function() {
            toastr.error('Error en la solicitud');
        });
});

// Ejemplo: Buscar usuarios en tiempo real
$('#userSearch').on('keyup', function() {
    var query = $(this).val();
    
    if (query.length < 2) {
        $('#searchResults').empty();
        return;
    }
    
    UserAPI.search(query)
        .done(function(response) {
            if (response.success && response.data) {
                var html = '';
                response.data.forEach(function(user) {
                    html += '<li><a href="#" data-id="' + user.id + '">' + 
                            user.username + ' (' + user.firstname + ')' + '</a></li>';
                });
                $('#searchResults').html(html);
            }
        });
});

// ============================================================================
// EQUIPMENT - Ejemplos de integración
// ============================================================================

var EquipmentAPI = {
    /**
     * Crear nuevo equipo
     */
    create: function(formData) {
        return $.ajax({
            url: './ajax/equipment.php?action=create',
            type: 'POST',
            data: formData,
            dataType: 'json'
        });
    },
    
    /**
     * Actualizar equipo
     */
    update: function(equipmentId, formData) {
        formData.id = equipmentId;
        return $.ajax({
            url: './ajax/equipment.php?action=update',
            type: 'POST',
            data: formData,
            dataType: 'json'
        });
    },
    
    /**
     * Obtener equipo con relaciones (categoría, proveedor, ubicación, usuario)
     */
    get: function(equipmentId) {
        return $.ajax({
            url: './ajax/equipment.php?action=get&id=' + equipmentId,
            type: 'GET',
            dataType: 'json'
        });
    },
    
    /**
     * Listar equipos con filtros opcionales
     */
    list: function(filters) {
        var url = './ajax/equipment.php?action=list';
        if (filters) {
            if (filters.status) url += '&status=' + filters.status;
            if (filters.category_id) url += '&category_id=' + filters.category_id;
            if (filters.location_id) url += '&location_id=' + filters.location_id;
            if (filters.assigned_to) url += '&assigned_to=' + filters.assigned_to;
        }
        
        return $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json'
        });
    },
    
    /**
     * Buscar equipos por asset_tag, nombre, serial_number
     */
    search: function(query) {
        return $.ajax({
            url: './ajax/equipment.php?action=search&q=' + encodeURIComponent(query),
            type: 'GET',
            dataType: 'json'
        });
    },
    
    /**
     * Obtener estadísticas (totales, por estado, valor total, asignados)
     */
    getStatistics: function() {
        return $.ajax({
            url: './ajax/equipment.php?action=statistics',
            type: 'GET',
            dataType: 'json'
        });
    },
    
    /**
     * Cambiar estado del equipo
     */
    changeStatus: function(equipmentId, newStatus) {
        return $.ajax({
            url: './ajax/equipment.php?action=change_status',
            type: 'POST',
            data: {
                id: equipmentId,
                status: newStatus
            },
            dataType: 'json'
        });
    },
    
    /**
     * Asignar equipo a usuario
     */
    assignToUser: function(equipmentId, userId) {
        return $.ajax({
            url: './ajax/equipment.php?action=assign_to_user',
            type: 'POST',
            data: {
                id: equipmentId,
                user_id: userId
            },
            dataType: 'json'
        });
    },
    
    /**
     * Eliminar equipo
     */
    delete: function(equipmentId) {
        if (!confirm('¿Está seguro de que desea eliminar este equipo?')) {
            return $.Deferred().reject('Cancelado por usuario');
        }
        
        return $.ajax({
            url: './ajax/equipment.php?action=delete',
            type: 'POST',
            data: { id: equipmentId },
            dataType: 'json'
        });
    }
};

// Ejemplo: Listar equipos y mostrar en tabla DataTables
$(document).ready(function() {
    EquipmentAPI.list()
        .done(function(response) {
            if (response.success && response.data) {
                var rows = [];
                response.data.forEach(function(eq) {
                    rows.push([
                        eq.asset_tag,
                        eq.name,
                        eq.serial_number,
                        eq.category_name,
                        eq.status,
                        '$' + parseFloat(eq.purchase_price).toFixed(2),
                        '<button class="btn btn-sm btn-info" onclick="editEquipment(' + eq.id + ')">Editar</button>'
                    ]);
                });
                
                $('#equipmentTable').DataTable({
                    data: rows,
                    columnDefs: [{targets: -1, orderable: false}]
                });
            }
        });
});

// Ejemplo: Cambiar estado del equipo
function changeEquipmentStatus(equipmentId, newStatus) {
    EquipmentAPI.changeStatus(equipmentId, newStatus)
        .done(function(response) {
            if (response.success) {
                toastr.success(response.message);
                location.reload();
            } else {
                toastr.error(response.message);
            }
        });
}

// Ejemplo: Mostrar estadísticas en dashboard
$('#loadStatistics').click(function() {
    EquipmentAPI.getStatistics()
        .done(function(response) {
            if (response.success) {
                var stats = response.data;
                $('#totalEquipment').text(stats.total || 0);
                $('#activeEquipment').text(stats.active || 0);
                $('#inactiveEquipment').text(stats.inactive || 0);
                $('#totalValue').text('$' + (stats.total_value || 0).toFixed(2));
                $('#assignedCount').text(stats.assigned_count || 0);
            }
        });
});

// Ejemplo: Buscar equipo por asset_tag en tiempo real
$('#equipmentSearch').on('keyup', function() {
    var query = $(this).val();
    
    if (query.length < 2) {
        $('#searchResults').empty();
        return;
    }
    
    EquipmentAPI.search(query)
        .done(function(response) {
            if (response.success && response.data) {
                var html = '<ul class="list-group">';
                response.data.forEach(function(eq) {
                    html += '<li class="list-group-item">' +
                            '<strong>' + eq.asset_tag + '</strong> - ' + eq.name +
                            ' <span class="badge badge-' + (eq.status === 'active' ? 'success' : 'danger') + '">' +
                            eq.status + '</span></li>';
                });
                html += '</ul>';
                $('#searchResults').html(html);
            }
        });
});

// Ejemplo: Asignar equipo a usuario
function assignEquipmentToUser(equipmentId) {
    var userId = prompt('Ingrese ID del usuario:');
    if (userId) {
        EquipmentAPI.assignToUser(equipmentId, userId)
            .done(function(response) {
                toastr.success(response.message);
                location.reload();
            })
            .fail(function() {
                toastr.error('Error al asignar equipo');
            });
    }
}

// ============================================================================
// RESPUESTA ESTÁNDAR - Manejador genérico para TODOS los endpoints
// ============================================================================

/**
 * Patrón de respuesta estándar para todos los endpoints:
 * {
 *     success: true/false,
 *     message: "Descripción del resultado",
 *     data: {...},           // Puede ser object, array, o null
 *     errors: ["error1", "error2"]  // Array de errores de validación
 * }
 * 
 * Así puedes crear un manejador genérico:
 */

function handleAPIResponse(response, successCallback, errorCallback) {
    if (response.success) {
        if (successCallback) successCallback(response.data);
        toastr.success(response.message);
    } else {
        if (errorCallback) errorCallback(response.errors);
        
        if (response.errors && response.errors.length > 0) {
            response.errors.forEach(function(err) {
                toastr.error(err);
            });
        } else {
            toastr.error(response.message);
        }
    }
}

// Ejemplo de uso:
function createEquipmentExample() {
    var formData = {
        name: 'Laptop Dell XPS',
        serial_number: 'ABC123DEF456',
        category_id: 1,
        supplier_id: 2,
        location_id: 3,
        purchase_price: 1500.00,
        purchase_date: '2025-01-01'
    };
    
    EquipmentAPI.create(formData)
        .done(function(response) {
            handleAPIResponse(response, function(data) {
                console.log('Equipo creado con ID:', data.id);
            });
        })
        .fail(function() {
            toastr.error('Error de conexión');
        });
}
