# Utilidades del Sistema

Esta carpeta contiene herramientas de instalación, diagnóstico y testing que no forman parte del sistema principal.

## 📦 Instalación de dependencias

- `install_composer_packages.php` - Instalador principal de Composer con PHPSpreadsheet
- `install_composer_simple.php` - Instalador simplificado de Composer
- `install_phpspreadsheet.php` - Instalador manual de PHPSpreadsheet (obsoleto)

## 🔍 Diagnóstico

- `check_phpspreadsheet.php` - Verificar instalación de PHPSpreadsheet
- `debug_phpspreadsheet.php` - Diagnóstico detallado de PHPSpreadsheet
- `debug_users.php` - Depuración de usuarios del sistema
- `diagnostic_users.php` - Diagnóstico de tabla de usuarios

## 🔧 Correcciones

- `fix_phpspreadsheet_structure.php` - Corregir estructura de PHPSpreadsheet
- `fix_phpspreadsheet_location.php` - Mover PHPSpreadsheet a ubicación correcta

## 🧪 Testing

- `test_qr.php` - Prueba de generación de códigos QR
- `test_save_user.php` - Prueba de guardado de usuarios
- `test_upload_access.php` - Prueba de permisos de subida

## 🔐 Seguridad

- `generate_passwords.php` - Generar contraseñas para usuarios

---

**NOTA:** Estos archivos son de uso administrativo y no deben ser ejecutados por usuarios finales.

**ADVERTENCIA:** Algunos de estos archivos pueden estar obsoletos después de la instalación exitosa del sistema.

## 🗑️ Archivos que puedes eliminar después de instalación exitosa:

Después de verificar que PHPSpreadsheet funciona correctamente, puedes eliminar:
- `install_phpspreadsheet.php`
- `check_phpspreadsheet.php`
- `fix_phpspreadsheet_structure.php`
- `fix_phpspreadsheet_location.php`
- `debug_phpspreadsheet.php`
- `install_composer_simple.php`

Mantén solo:
- `install_composer_packages.php` (por si necesitas reinstalar)
- Los archivos de testing que aún uses
- `generate_passwords.php` (útil para administración)
