# Fase 4 - Guía Rápida de Endpoints AJAX

## Resumen Ejecutivo

La Fase 4 introduce dos nuevos endpoints para integración frontend:

1. **`/public/ajax/user.php`** - Gestión de usuarios
2. **`/public/ajax/equipment.php`** - Gestión de equipos

Todos los endpoints siguen un patrón estándar de respuesta JSON:

```json
{
  "success": true,
  "message": "Descripción del resultado",
  "data": {...},
  "errors": ["error1", "error2"]
}
```

---

## USER ENDPOINT - `/public/ajax/user.php`

### Crear Usuario
```javascript
POST /public/ajax/user.php?action=create

// Datos requeridos:
{
  username: "juan.doe",
  email: "juan@example.com",
  firstname: "Juan",
  lastname: "Doe",
  password: "SecurePass123!",
  role: "staff"  // 'admin' o 'staff'
}

// Respuesta exitosa:
{
  "success": true,
  "message": "Usuario creado exitosamente",
  "data": {
    "id": 42,
    "username": "juan.doe"
  }
}
```

### Actualizar Usuario
```javascript
POST /public/ajax/user.php?action=update

// Datos (todos opcionales):
{
  id: 42,
  firstname: "Juan",
  lastname: "Doe",
  email: "juan.new@example.com",
  role: "admin"
}
```

### Obtener Usuario
```javascript
GET /public/ajax/user.php?action=get&id=42

// Respuesta:
{
  "success": true,
  "data": {
    "id": 42,
    "username": "juan.doe",
    "firstname": "Juan",
    "email": "juan@example.com",
    "role": "admin",
    "created_at": "2025-01-10",
    // NOTE: NO RETORNA PASSWORD
  }
}
```

### Listar Usuarios
```javascript
GET /public/ajax/user.php?action=list
GET /public/ajax/user.php?action=list&role=admin  // Filtrar por rol

// Respuesta:
{
  "success": true,
  "data": [
    { id: 1, username: "admin", firstname: "Admin", role: "admin" },
    { id: 2, username: "juan", firstname: "Juan", role: "staff" }
  ]
}
```

### Buscar Usuarios
```javascript
GET /public/ajax/user.php?action=search&q=juan

// Busca en: username, firstname, lastname, email
// Respuesta: Array de usuarios coincidentes
```

### Cambiar Contraseña
```javascript
POST /public/ajax/user.php?action=change_password

{
  id: 42,
  old_password: "OldPass123!",
  new_password: "NewPass456!"
}

// Validaciones:
// - old_password debe ser correcto
// - new_password min 8 caracteres
// - No puede ser igual a la anterior
```

### Eliminar Usuario
```javascript
POST /public/ajax/user.php?action=delete

{
  id: 42
}

// Solo admin puede eliminar
```

---

## EQUIPMENT ENDPOINT - `/public/ajax/equipment.php`

### Crear Equipo
```javascript
POST /public/ajax/equipment.php?action=create

// Campos requeridos:
{
  name: "Laptop Dell XPS",
  category_id: 1,
  purchase_price: 1500.00
}

// Campos opcionales:
{
  serial_number: "ABC123DEF456",
  supplier_id: 2,
  location_id: 3,
  purchase_date: "2025-01-01",
  warranty_expiry: "2027-01-01",
  status: "active",
  notes: "Equipo de oficina"
}

// Respuesta:
{
  "success": true,
  "message": "Equipo creado exitosamente",
  "data": {
    "id": 42,
    "asset_tag": "AST-2025-00042"  // Auto-generado
  }
}
```

### Actualizar Equipo
```javascript
POST /public/ajax/equipment.php?action=update

{
  id: 42,
  name: "Laptop Dell XPS 15",
  status: "inactive",
  notes: "Actualizado"
}

// Todos los campos son opcionales
```

### Obtener Equipo (Con Relaciones)
```javascript
GET /public/ajax/equipment.php?action=get&id=42

// Respuesta:
{
  "success": true,
  "data": {
    "id": 42,
    "asset_tag": "AST-2025-00042",
    "name": "Laptop Dell XPS",
    "serial_number": "ABC123",
    "category": { id: 1, name: "Computadoras" },
    "supplier": { id: 2, name: "Dell Inc." },
    "location": { id: 3, name: "Oficina Principal" },
    "assigned_to": { id: 5, username: "juan.doe" },
    "purchase_price": 1500.00,
    "purchase_date": "2025-01-01",
    "warranty_expiry": "2027-01-01",
    "status": "active"
  }
}
```

### Listar Equipos (Con Filtros)
```javascript
GET /public/ajax/equipment.php?action=list
GET /public/ajax/equipment.php?action=list?status=active
GET /public/ajax/equipment.php?action=list?category_id=1&status=active
GET /public/ajax/equipment.php?action=list?location_id=3&assigned_to=5

// Filtros disponibles:
// - status: 'active', 'inactive', 'maintenance'
// - category_id: ID de categoría
// - location_id: ID de ubicación
// - assigned_to: ID de usuario asignado
```

### Buscar Equipos
```javascript
GET /public/ajax/equipment.php?action=search&q=laptop

// Busca en: asset_tag, name, serial_number
// Respuesta: Array de equipos coincidentes
```

### Obtener Estadísticas
```javascript
GET /public/ajax/equipment.php?action=statistics

// Respuesta:
{
  "success": true,
  "data": {
    "total": 150,
    "active": 120,
    "inactive": 25,
    "maintenance": 5,
    "total_value": 450000.00,
    "assigned_count": 85
  }
}
```

### Cambiar Estado
```javascript
POST /public/ajax/equipment.php?action=change_status

{
  id: 42,
  status: "maintenance"  // 'active', 'inactive', 'maintenance'
}
```

### Asignar a Usuario
```javascript
POST /public/ajax/equipment.php?action=assign_to_user

{
  id: 42,
  user_id: 5
}

// user_id puede ser null para desasignar
```

### Eliminar Equipo
```javascript
POST /public/ajax/equipment.php?action=delete

{
  id: 42
}
```

---

## Códigos de Estado HTTP

- **200 OK** - Solicitud exitosa
- **400 Bad Request** - Datos inválidos o faltantes
- **401 Unauthorized** - Sesión expirada
- **403 Forbidden** - Permisos insuficientes
- **404 Not Found** - Recurso no encontrado
- **405 Method Not Allowed** - GET/POST incorrecto
- **500 Internal Server Error** - Error del servidor

---

## Manejo de Errores en Frontend

### Patrón estándar:

```javascript
MyAPI.action(data)
  .done(function(response) {
    if (response.success) {
      toastr.success(response.message);
      // Procesar response.data
    } else {
      // Errores de validación
      if (response.errors && response.errors.length > 0) {
        response.errors.forEach(function(error) {
          toastr.error(error);
        });
      } else {
        toastr.error(response.message);
      }
    }
  })
  .fail(function(xhr, status, error) {
    toastr.error('Error de conexión: ' + error);
  });
```

### Errores comunes:

| Error | Causa | Solución |
|-------|-------|----------|
| "Sesión expirada" | Token/sesión inválida | Login nuevamente |
| "Permiso denegado" | Usuario sin rol necesario | Contactar admin |
| "Campo requerido" | Datos incompletos | Validar formulario |
| "Username already exists" | Username duplicado | Elegir otro |
| "ID requerido" | Falta parámetro ID | Verificar URL |

---

## Ejemplos de Integración Completos

### Crear formulario dinámico

```html
<form id="userForm">
  <input type="text" name="username" placeholder="Username" required>
  <input type="email" name="email" placeholder="Email" required>
  <input type="text" name="firstname" placeholder="Nombre" required>
  <input type="password" name="password" placeholder="Contraseña" required>
  <select name="role">
    <option value="staff">Staff</option>
    <option value="admin">Admin</option>
  </select>
  <button type="submit">Crear Usuario</button>
</form>

<script>
$('#userForm').on('submit', function(e) {
  e.preventDefault();
  
  $.ajax({
    url: '/public/ajax/user.php?action=create',
    type: 'POST',
    data: $(this).serialize(),
    dataType: 'json',
    success: function(response) {
      if (response.success) {
        toastr.success('Usuario creado');
        $('#userForm')[0].reset();
        loadUserList(); // Recargar tabla
      } else {
        toastr.error(response.message);
      }
    }
  });
});
</script>
```

### Tabla con búsqueda en tiempo real

```html
<input type="text" id="searchEquipment" placeholder="Buscar por asset_tag, nombre...">
<table id="equipmentTable" class="table">
  <thead>
    <tr>
      <th>Asset Tag</th>
      <th>Nombre</th>
      <th>Serial</th>
      <th>Categoría</th>
      <th>Estado</th>
      <th>Precio</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody></tbody>
</table>

<script>
$('#searchEquipment').on('keyup', function() {
  var query = $(this).val();
  
  if (query.length < 2) {
    loadAllEquipment();
    return;
  }
  
  $.ajax({
    url: '/public/ajax/equipment.php?action=search&q=' + encodeURIComponent(query),
    type: 'GET',
    dataType: 'json',
    success: function(response) {
      if (response.success) {
        displayEquipmentTable(response.data);
      }
    }
  });
});

function loadAllEquipment() {
  $.ajax({
    url: '/public/ajax/equipment.php?action=list',
    type: 'GET',
    dataType: 'json',
    success: function(response) {
      if (response.success) {
        displayEquipmentTable(response.data);
      }
    }
  });
}

function displayEquipmentTable(equipment) {
  var tbody = $('#equipmentTable tbody').html('');
  
  equipment.forEach(function(eq) {
    tbody.append(
      '<tr>' +
        '<td>' + eq.asset_tag + '</td>' +
        '<td>' + eq.name + '</td>' +
        '<td>' + (eq.serial_number || '-') + '</td>' +
        '<td>' + (eq.category_name || '-') + '</td>' +
        '<td><span class="badge badge-' + (eq.status === 'active' ? 'success' : 'danger') + '">' + eq.status + '</span></td>' +
        '<td>$' + parseFloat(eq.purchase_price).toFixed(2) + '</td>' +
        '<td>' +
          '<button class="btn btn-sm btn-info" onclick="editEquipment(' + eq.id + ')">Editar</button> ' +
          '<button class="btn btn-sm btn-danger" onclick="deleteEquipment(' + eq.id + ')">Eliminar</button>' +
        '</td>' +
      '</tr>'
    );
  });
}

loadAllEquipment();
</script>
```

---

## Notas Importantes

1. **Autenticación**: Todos los endpoints requieren sesión activa
2. **Permisos**: 
   - Crear/Actualizar/Eliminar usuarios: Solo ADMIN
   - Cambiar propia contraseña: Cualquier usuario
   - Cambiar contraseña ajena: Solo ADMIN
3. **Validación Frontend**: Los endpoints validan nuevamente (nunca confíes en validación frontend)
4. **CORS**: Si estás en dominio diferente, configura CORS en .htaccess
5. **Rate Limiting**: No implementado aún (TODO para fase 5)

---

## Próximos Pasos

- [ ] Crear endpoint Customer Controller
- [ ] Crear endpoint Ticket Controller
- [ ] Crear endpoint Department Controller
- [ ] Implementar validación de permisos granulares
- [ ] Agregar logging de acciones
- [ ] Implementar rate limiting
- [ ] Agregar autenticación con tokens JWT

Todas estas mejoras seguirán el mismo patrón establecido en Fase 4.
