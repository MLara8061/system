# Carga Masiva de Equipos desde Excel

## 📋 Requisitos

1. **Instalar PHPSpreadsheet**
   ```bash
   composer require phpoffice/phpspreadsheet
   ```

   Si no tienes Composer instalado, descárgalo desde: https://getcomposer.org/

## 🚀 Instalación

### Paso 1: Instalar la librería

Abre PowerShell en la carpeta `c:\xampp\htdocs\system` y ejecuta:

```powershell
composer require phpoffice/phpspreadsheet
```

### Paso 2: Generar la plantilla de Excel

Ejecuta el script para crear la plantilla:

```powershell
php crear_plantilla_equipos.php
```

Esto creará el archivo `assets/templates/plantilla_equipos.xlsx`

### Paso 3: Agregar enlace en el menú

Edita tu archivo `sidebar.php` o el archivo de navegación y agrega:

```php
<li class="nav-item">
    <a href="index.php?page=upload_equipment" class="nav-link">
        <i class="fas fa-file-upload"></i>
        <p>Carga Masiva Equipos</p>
    </a>
</li>
```

## 📝 Formato del archivo Excel

El archivo Excel debe tener las siguientes columnas:

| Columna | Campo | Descripción | Requerido |
|---------|-------|-------------|-----------|
| A | Serie | Número de serie único | **Sí** |
| B | Nombre | Nombre del equipo | No |
| C | Marca | Marca del equipo | No |
| D | Modelo | Modelo del equipo | No |
| E | Tipo de Adquisición | Compra, Donación, Comodato, etc. | No |
| F | Características | Descripción técnica | No |
| G | Disciplina | Área o disciplina | No |
| H | Proveedor | Nombre del proveedor (debe existir) | No |
| I | Cantidad | Cantidad de equipos | No |

## ✅ Ejemplo de datos

```
Serie          | Nombre              | Marca    | Modelo   | Tipo de Adquisición | ...
EQ-2024-001    | Microscopio Óptico  | Olympus  | CX23     | Compra             | ...
EQ-2024-002    | Centrifuga          | Eppendorf| 5424R    | Donación           | ...
```

## 🔧 Uso

1. Descarga la plantilla desde el sistema
2. Llena los datos de tus equipos
3. **Elimina las filas de ejemplo** antes de cargar
4. Sube el archivo en "Carga Masiva de Equipos"
5. El sistema validará y cargará los datos

## ⚠️ Consideraciones importantes

- La columna **Serie** es obligatoria y debe ser única
- Si el proveedor no existe, el campo quedará vacío
- Los equipos duplicados (misma serie) serán rechazados
- Se crearán automáticamente los registros relacionados (recepción, entrega, etc.)

## 🐛 Solución de problemas

### Error: "PHPSpreadsheet no está instalado"
- Ejecuta: `composer require phpoffice/phpspreadsheet`

### Error: "No se recibió ningún archivo"
- Verifica que el formulario tenga `enctype="multipart/form-data"`
- Revisa los permisos del directorio `uploads/`

### Error: "El equipo con serie XXX ya existe"
- Verifica que no haya series duplicadas en tu archivo Excel
- Revisa en la base de datos si el equipo ya está registrado

## 📁 Archivos modificados/creados

- ✅ `ajax.php` - Agregada acción `upload_excel_equipment`
- ✅ `admin_class.php` - Agregado método `upload_excel_equipment()`
- ✅ `upload_equipment.php` - Nueva interfaz de carga
- ✅ `crear_plantilla_equipos.php` - Script para generar plantilla
- ✅ `assets/templates/` - Directorio para plantillas

## 📞 Soporte

Si encuentras algún problema, revisa los logs de error de PHP:
- Apache: `c:\xampp\apache\logs\error.log`
- PHP: `c:\xampp\php\logs\php_error_log.txt`
