# Guía de Instalación - Carga Masiva de Equipos

## 🎯 Objetivo
Permitir la carga masiva de equipos desde archivos Excel (.xlsx, .xls)

## 📦 Método 1: Instalación con Composer (Recomendado)

### 1. Instalar Composer

**Opción A: Descarga directa**
1. Ve a: https://getcomposer.org/download/
2. Descarga `Composer-Setup.exe` para Windows
3. Ejecuta el instalador y sigue las instrucciones
4. Reinicia PowerShell

**Opción B: Usando PowerShell (como administrador)**
```powershell
# Descargar el instalador
Invoke-WebRequest -Uri "https://getcomposer.org/Composer-Setup.exe" -OutFile "$env:TEMP\composer-setup.exe"

# Ejecutar instalador
Start-Process -FilePath "$env:TEMP\composer-setup.exe" -Wait
```

### 2. Instalar PHPSpreadsheet

Abre PowerShell en `c:\xampp\htdocs\system` y ejecuta:

```powershell
cd c:\xampp\htdocs\system
composer require phpoffice/phpspreadsheet
```

### 3. Generar la plantilla

```powershell
php crear_plantilla_equipos.php
```

---

## 📦 Método 2: Instalación Manual (Sin Composer)

Si no puedes instalar Composer, sigue estos pasos:

### 1. Descargar PHPSpreadsheet manualmente

```powershell
# Crear directorio vendor si no existe
New-Item -ItemType Directory -Force -Path "c:\xampp\htdocs\system\vendor"

# Descargar PHPSpreadsheet
cd c:\xampp\htdocs\system
Invoke-WebRequest -Uri "https://github.com/PHPOffice/PhpSpreadsheet/archive/refs/heads/master.zip" -OutFile "phpspreadsheet.zip"

# Extraer
Expand-Archive -Path "phpspreadsheet.zip" -DestinationPath "vendor\" -Force

# Renombrar carpeta
Move-Item "vendor\PhpSpreadsheet-master" "vendor\phpoffice-phpspreadsheet" -Force
```

### 2. Crear archivo autoload.php

Crea el archivo `vendor/autoload.php` con este contenido:

```php
<?php
// Autoloader simple para PHPSpreadsheet
spl_autoload_register(function ($class) {
    // Convertir namespace a ruta de archivo
    $prefix = 'PhpOffice\\PhpSpreadsheet\\';
    $base_dir = __DIR__ . '/phpoffice-phpspreadsheet/src/PhpSpreadsheet/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});
?>
```

---

## 📦 Método 3: Usando librería ZIP de PHP (Alternativa Simple)

Si los métodos anteriores no funcionan, puedes usar una alternativa más simple:

### 1. Instalar extensión ZIP de PHP

Edita `c:\xampp\php\php.ini` y descomenta:
```ini
extension=zip
```

Reinicia Apache.

### 2. Usar SimpleXLSX (librería ligera)

Descarga desde: https://github.com/shuchkin/simplexlsx/archive/refs/heads/master.zip

```powershell
cd c:\xampp\htdocs\system
Invoke-WebRequest -Uri "https://github.com/shuchkin/simplexlsx/archive/refs/heads/master.zip" -OutFile "simplexlsx.zip"
Expand-Archive -Path "simplexlsx.zip" -DestinationPath "lib\" -Force
```

### 3. Modifica admin_class.php para usar SimpleXLSX

Reemplaza la función `upload_excel_equipment()` con:

```php
function upload_excel_equipment() {
    require_once 'lib/simplexlsx-master/src/SimpleXLSX.php';
    
    if (!isset($_FILES['excel_file'])) {
        return json_encode(['status' => 0, 'msg' => 'No se recibió ningún archivo']);
    }
    
    $file = $_FILES['excel_file'];
    
    if ( $xlsx = SimpleXLSX::parse($file['tmp_name']) ) {
        $rows = $xlsx->rows();
        
        $success = 0;
        $errors = [];
        $skipped = 0;
        
        // Saltar encabezados (fila 0)
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            
            if (empty($row[0]) || trim($row[0]) == '') {
                $skipped++;
                continue;
            }
            
            $serie = $this->db->real_escape_string(trim($row[0]));
            $name = isset($row[1]) ? $this->db->real_escape_string(trim($row[1])) : '';
            $brand = isset($row[2]) ? $this->db->real_escape_string(trim($row[2])) : '';
            $model = isset($row[3]) ? $this->db->real_escape_string(trim($row[3])) : '';
            $acquisition_type = isset($row[4]) ? $this->db->real_escape_string(trim($row[4])) : '';
            $characteristics = isset($row[5]) ? $this->db->real_escape_string(trim($row[5])) : '';
            $discipline = isset($row[6]) ? $this->db->real_escape_string(trim($row[6])) : '';
            $supplier_name = isset($row[7]) ? trim($row[7]) : '';
            $amount = isset($row[8]) ? intval($row[8]) : 1;
            
            // Buscar proveedor
            $supplier_id = 'NULL';
            if (!empty($supplier_name)) {
                $supplier_query = $this->db->query("SELECT id FROM suppliers WHERE name LIKE '%$supplier_name%' LIMIT 1");
                if ($supplier_query && $supplier_query->num_rows > 0) {
                    $supplier_id = $supplier_query->fetch_assoc()['id'];
                }
            }
            
            // Verificar duplicados
            $check = $this->db->query("SELECT id FROM equipments WHERE serie = '$serie'");
            if ($check && $check->num_rows > 0) {
                $errors[] = "Fila " . ($i + 1) . ": El equipo con serie '$serie' ya existe";
                continue;
            }
            
            // Insertar
            $sql = "INSERT INTO equipments 
                    (serie, name, brand, model, acquisition_type, characteristics, discipline, supplier_id, amount, date_created) 
                    VALUES 
                    ('$serie', '$name', '$brand', '$model', '$acquisition_type', '$characteristics', '$discipline', $supplier_id, $amount, NOW())";
            
            if ($this->db->query($sql)) {
                $equipment_id = $this->db->insert_id;
                
                // Insertar registros relacionados
                $this->db->query("INSERT INTO equipment_reception (equipment_id, state, comments) VALUES ($equipment_id, 'Pendiente', 'Importado desde Excel')");
                $this->db->query("INSERT INTO equipment_delivery (equipment_id, department_id) VALUES ($equipment_id, NULL)");
                $this->db->query("INSERT INTO equipment_safeguard (equipment_id) VALUES ($equipment_id)");
                $this->db->query("INSERT INTO equipment_control_documents (equipment_id) VALUES ($equipment_id)");
                
                $success++;
            } else {
                $errors[] = "Fila " . ($i + 1) . ": " . $this->db->error;
            }
        }
        
        $msg = "Carga completada: $success equipos insertados";
        if ($skipped > 0) $msg .= ", $skipped filas omitidas";
        if (count($errors) > 0) $msg .= ", " . count($errors) . " errores";
        
        return json_encode([
            'status' => 1,
            'msg' => $msg,
            'success' => $success,
            'skipped' => $skipped,
            'errors' => $errors
        ]);
        
    } else {
        return json_encode(['status' => 0, 'msg' => 'Error: ' . SimpleXLSX::parseError()]);
    }
}
```

---

## ✅ Verificar instalación

Ejecuta este script de prueba:

```powershell
php -r "echo 'PHP Version: ' . phpversion() . PHP_EOL;"
php -r "echo 'ZIP Extension: ' . (extension_loaded('zip') ? 'OK' : 'NO INSTALADA') . PHP_EOL;"
```

---

## 🚀 Pasos finales

1. **Agrega el enlace en el menú**

Edita `sidebar.php` y agrega:

```php
<li class="nav-item">
    <a href="index.php?page=upload_equipment" class="nav-link">
        <i class="fas fa-file-upload"></i>
        <p>Carga Masiva Equipos</p>
    </a>
</li>
```

2. **Prueba la funcionalidad**
   - Accede a `http://localhost/system/index.php?page=upload_equipment`
   - Descarga la plantilla
   - Llena con datos de prueba
   - Sube el archivo

---

## 🐛 Solución de problemas

### Error: "Cannot find autoload.php"
- Verifica que ejecutaste `composer require phpoffice/phpspreadsheet`
- O sigue el Método 2 o 3

### Error: "Class PhpOffice\PhpSpreadsheet\IOFactory not found"
- PHPSpreadsheet no está correctamente instalado
- Intenta el Método 3 (SimpleXLSX)

### Error: "ZIP extension not loaded"
- Edita `php.ini` y descomenta `extension=zip`
- Reinicia Apache

---

## 📞 Ayuda adicional

Si ningún método funciona, envíame:
1. Versión de PHP: `php -v`
2. Extensiones cargadas: `php -m`
3. Contenido del directorio: `ls vendor/`
