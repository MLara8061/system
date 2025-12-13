# Fase 4: MVC Refactoring - Guía de Uso

## 📚 Estructura

```
app/
├── models/
│   ├── DataStore.php        # Clase base para acceso a datos
│   ├── User.php             # Model de usuarios
│   └── Equipment.php        # Model de equipos
│
├── controllers/
│   └── UserController.php   # Controller de usuarios
│
└── ... (más models/controllers por venir)
```

## 🎯 Conceptos

### DataStore (Clase Base)
Proporciona métodos CRUD genéricos reutilizables:
- `getAll()` - Obtener todos los registros
- `getById()` - Obtener por ID
- `findBy()` - Búsqueda por columna
- `insert()`, `update()`, `delete()` - CRUD básico
- `count()` - Contar registros
- `query()` - Queries personalizados

### Models
Extienden DataStore y agregan lógica específica del dominio:
- `User::validateLogin()` - Validación de login
- `User::changePassword()` - Cambio de contraseña seguro
- `Equipment::getWithRelations()` - Cargar relaciones asociadas
- `Equipment::listWithFilters()` - Listar con filtros

### Controllers
Implementan la lógica de negocio y validación:
- Validan inputs del usuario
- Llaman a Models para obtener/modificar datos
- Retornan respuestas estandarizadas
- Manejo de errores

## 💡 Ejemplos de Uso

### Ejemplo 1: Crear Usuario

```php
require_once ROOT . '/app/controllers/UserController.php';

$userController = new UserController();

$result = $userController->create([
    'username' => 'juan.perez',
    'password' => 'MiContraseña123',
    'firstname' => 'Juan',
    'lastname' => 'Pérez',
    'email' => 'juan@example.com',
    'role' => 'admin'
]);

if ($result['success']) {
    echo "Usuario creado con ID: " . $result['data']['id'];
} else {
    echo "Error: " . $result['message'];
}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Usuario creado exitosamente",
  "data": {
    "id": 42
  }
}
```

### Ejemplo 2: Listar Usuarios

```php
$userController = new UserController();
$result = $userController->list('admin');

if ($result['success']) {
    foreach ($result['data'] as $user) {
        echo $user['firstname'] . " " . $user['lastname'];
    }
}
```

**Respuesta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "username": "admin",
      "firstname": "Administrador",
      "lastname": "Sistema",
      "email": "admin@example.com",
      "role": "admin"
    }
  ],
  "count": 1
}
```

### Ejemplo 3: Buscar Equipos

```php
require_once ROOT . '/app/models/Equipment.php';

$equipmentModel = new Equipment();

// Búsqueda simple
$results = $equipmentModel->search('laptop');

// Búsqueda con filtros
$results = $equipmentModel->listWithFilters([
    'status' => 'active',
    'category_id' => 5,
    'location_id' => 2
]);

// Obtener con relaciones
$equipment = $equipmentModel->getWithRelations(123);
```

### Ejemplo 4: Obtener Estadísticas

```php
$equipmentModel = new Equipment();
$stats = $equipmentModel->getStatistics();

echo "Total equipos: " . $stats['total'];
echo "Activos: " . $stats['active'];
echo "Asignados: " . $stats['assigned'];
echo "Valor total: $" . $stats['total_value'];
```

## 🔄 Patrón de Respuesta Estándar

Todos los Controllers retornan arrays con esta estructura:

```php
[
    'success' => true/false,
    'message' => 'Descripción del resultado',
    'data' => [],     // Opcional - datos del resultado
    'errors' => []    // Opcional - array de errores de validación
]
```

## 🛡️ Características de Seguridad

### 1. Validación de Entrada
- Los Controllers validan todos los inputs
- Trimean strings, castean tipos
- Verifican unicidad de datos

### 2. Prepared Statements
- DataStore usa placeholders (?)
- Previene SQL Injection
- Separación código/datos

### 3. Password Hashing
- `User::changePassword()` usa bcrypt
- Support para legacy MD5 (backward compatibility)
- Nunca se retornan passwords en respuestas

### 4. No Direct DB Access
- Vistas usan Controllers, no Models directo
- AJAX usa Controllers
- Lógica centralizada

## 📋 Métodos Disponibles

### DataStore (Genéricos)
```php
$store = new DataStore('users');
$store->getAll($orderBy, $limit);        // Todos
$store->getById($id);                    // Por ID
$store->findBy($column, $value, $single); // Búsqueda
$store->count($where);                    // Contar
$store->insert($data);                    // Insertar
$store->update($data, $id);               // Actualizar
$store->delete($id);                      // Eliminar
$store->query($sql, $params);             // Personalizado
$store->getConnection();                  // Conexión PDO
```

### User Model
```php
$user = new User();
$user->save($data);                      // Crear/actualizar
$user->getByUsername($username);         // Por username
$user->getByEmail($email);               // Por email
$user->validateLogin($username, $pass);  // Validar login
$user->changePassword($id, $old, $new);  // Cambiar contraseña
$user->getByRole($role);                 // Por role
$user->search($search);                  // Búsqueda
$user->getWithDetails($id);              // Con detalles (sin password)
$user->updateAvatar($id, $avatar);       // Actualizar avatar
```

### UserController
```php
$controller = new UserController();
$controller->create($input);             // Crear
$controller->update($id, $input);        // Actualizar
$controller->delete($id);                // Eliminar
$controller->get($id);                   // Obtener
$controller->list($role);                // Listar
$controller->search($search);            // Buscar
$controller->changePassword($id, $input);// Cambiar contraseña
```

### Equipment Model
```php
$eq = new Equipment();
$eq->save($data);                          // Crear/actualizar
$eq->getWithRelations($id);               // Con relaciones
$eq->listWithFilters($filters);           // Con filtros
$eq->search($search);                     // Búsqueda
$eq->getByCategory($catId);               // Por categoría
$eq->getByLocation($locId);               // Por ubicación
$eq->getAssignedTo($userId);              // Asignados a usuario
$eq->getByStatus($status);                // Por estado
$eq->getStatistics();                     // Estadísticas
$eq->changeStatus($id, $status);          // Cambiar estado
$eq->assignToUser($eqId, $userId);        // Asignar
$eq->moveToLocation($eqId, $locId);       // Mover
```

## 🚀 Cómo Integrar en AJAX

### Antes (Viejo - admin_class.php):
```javascript
// public/ajax/action.php
$crud = new Action();
$crud->save_user(); // Hace de todo
```

### Después (Nuevo - Recomendado):
```php
// public/ajax/action.php - O crear new endpoint
require_once ROOT . '/app/controllers/UserController.php';

$action = $_POST['action'] ?? '';
$controller = new UserController();

switch ($action) {
    case 'create':
        echo json_encode($controller->create($_POST));
        break;
    case 'update':
        echo json_encode($controller->update($_POST['id'], $_POST));
        break;
    case 'delete':
        echo json_encode($controller->delete($_POST['id']));
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Action not found']);
}
```

## 📈 Beneficios

| Antes | Después |
|-------|---------|
| 2600+ líneas en admin_class.php | 300-400 líneas por module |
| Lógica + datos mezclados | Separación clara |
| Difícil de testear | Fácil de testear |
| Hard-coded queries | Flexible query builder |
| Sin validación centralizada | Validación en Controller |
| Bug en un módulo = todo roto | Aislamiento de módulos |

## 🔧 Próximo Paso: Crear Más Models

Para cada módulo principal:
1. Customer (similar a User)
2. Ticket (con relaciones)
3. Department
4. Category
5. Location
6. Supplier

Cada uno seguirá el mismo patrón:
```
Model
├── Hereda de DataStore
├── Métodos específicos del dominio
└── Queries complejas

Controller
├── Validación de inputs
├── Llamadas a Model
└── Respuestas estandarizadas
```

## ✅ Checklist para Nuevo Model

1. Crear `app/models/NuevoModel.php`
   - Extender DataStore
   - Agregar métodos específicos
   - Usar prepared statements

2. Crear `app/controllers/NuevoController.php`
   - CRUD básico (create, update, delete, get, list)
   - Validación de inputs
   - Respuestas estandarizadas

3. Crear `app/helpers/NuevoValidator.php` (opcional)
   - Validaciones reutilizables
   - Rules de negocio

4. Integrar en AJAX/Vistas
   - Importar Controller
   - Llamar métodos
   - Procesar respuestas

---

**Fase 4 Iniciada:** ✅ DataStore, User Model/Controller, Equipment Model creados
**Siguiente:** Customer Model/Controller (similar patrón)
