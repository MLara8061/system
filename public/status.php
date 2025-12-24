<?php
// Status sin requerir sesión
?><!DOCTYPE html>
<html>
<head>
    <title>Estado del Sistema</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .ok { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Diagnóstico del Sistema</h1>
    
    <h2>1. Verificar PHP</h2>
    <p>Versión PHP: <span class="ok"><?php echo PHP_VERSION; ?></span></p>
    
    <h2>2. Verificar Archivos Críticos</h2>
    <ul>
        <li>config.php: <span class="<?php echo file_exists(__DIR__ . '/../../config/config.php') ? 'ok' : 'error'; ?>">
            <?php echo file_exists(__DIR__ . '/../../config/config.php') ? '✓ EXISTE' : '✗ FALTA'; ?>
        </span></li>
        
        <li>vendor/autoload.php: <span class="<?php echo file_exists(__DIR__ . '/../../vendor/autoload.php') ? 'ok' : 'error'; ?>">
            <?php echo file_exists(__DIR__ . '/../../vendor/autoload.php') ? '✓ EXISTE' : '✗ FALTA'; ?>
        </span></li>
        
        <li>app/routing.php: <span class="<?php echo file_exists(__DIR__ . '/../../app/routing.php') ? 'ok' : 'error'; ?>">
            <?php echo file_exists(__DIR__ . '/../../app/routing.php') ? '✓ EXISTE' : '✗ FALTA'; ?>
        </span></li>
        
        <li>lib/PhpSpreadsheet-1.29.0: <span class="<?php echo is_dir(__DIR__ . '/../../lib/PhpSpreadsheet-1.29.0') ? 'ok' : 'error'; ?>">
            <?php echo is_dir(__DIR__ . '/../../lib/PhpSpreadsheet-1.29.0') ? '✓ EXISTE' : '✗ FALTA'; ?>
        </span></li>
    </ul>
    
    <h2>3. Intentar cargar PHPSpreadsheet</h2>
    <pre><?php
        try {
            if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
                require_once __DIR__ . '/../../vendor/autoload.php';
            }
            if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                echo "✓ PHPSpreadsheet cargado correctamente\n";
                $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                echo "✓ Instancia creada: " . get_class($ss);
            } else {
                echo "✗ Clase PhpOffice\\PhpSpreadsheet\\Spreadsheet NO encontrada";
            }
        } catch (Exception $e) {
            echo "✗ ERROR: " . $e->getMessage() . "\n";
            echo "  en: " . $e->getFile() . ":" . $e->getLine();
        }
    ?></pre>
    
    <h2>4. Extensions de PHP</h2>
    <ul>
        <li>json: <span class="<?php echo extension_loaded('json') ? 'ok' : 'error'; ?>">
            <?php echo extension_loaded('json') ? '✓' : '✗'; ?>
        </span></li>
        <li>pdo: <span class="<?php echo extension_loaded('pdo') ? 'ok' : 'error'; ?>">
            <?php echo extension_loaded('pdo') ? '✓' : '✗'; ?>
        </span></li>
        <li>zip: <span class="<?php echo extension_loaded('zip') ? 'warning' : 'error'; ?>">
            <?php echo extension_loaded('zip') ? '✓' : '✗'; ?>
        </span></li>
        <li>zlib: <span class="<?php echo extension_loaded('zlib') ? 'ok' : 'warning'; ?>">
            <?php echo extension_loaded('zlib') ? '✓' : '✗'; ?>
        </span></li>
    </ul>
    
    <h2>5. Permisos</h2>
    <ul>
        <li>uploads/: <span class="<?php echo is_writable(__DIR__ . '/../../uploads') ? 'ok' : 'error'; ?>">
            <?php echo is_writable(__DIR__ . '/../../uploads') ? '✓ Writable' : '✗ No writable'; ?>
        </span></li>
        <li>logs/: <span class="<?php echo is_writable(__DIR__ . '/../../logs') ? 'ok' : 'error'; ?>">
            <?php echo is_writable(__DIR__ . '/../../logs') ? '✓ Writable' : '✗ No writable'; ?>
        </span></li>
    </ul>
</body>
</html>
