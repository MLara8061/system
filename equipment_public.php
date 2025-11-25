<?php
// Vista pública de equipos - sin requerir autenticación
// Activar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar que config.php existe
if (!file_exists(__DIR__ . '/config/config.php')) {
    die("Error: No se encuentra config/config.php");
}

require_once __DIR__ . '/config/config.php';

// Verificar conexión
if (!isset($conn) || !$conn) {
    die("Error: No hay conexión a la base de datos");
}

// === VALIDAR ID ===
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger text-center'>ID inválido</div>";
    exit;
}
$equipment_id = (int)$_GET['id'];

// === CONSULTA PRINCIPAL ===
$qry = $conn->query("SELECT e.*, s.empresa as supplier_name FROM equipments e LEFT JOIN suppliers s ON e.supplier_id = s.id WHERE e.id = $equipment_id");
if ($qry->num_rows == 0) {
    echo "<div class='alert alert-warning text-center'>Equipo no encontrado</div>";
    exit;
}
$eq = $qry->fetch_assoc();

// === INFORMACIÓN BÁSICA ===
$reception = $delivery = [];
$qry = $conn->query("SELECT * FROM equipment_reception WHERE equipment_id = $equipment_id");
if ($qry->num_rows > 0) $reception = $qry->fetch_assoc();

$qry = $conn->query("SELECT * FROM equipment_delivery WHERE equipment_id = $equipment_id");
if ($qry->num_rows > 0) $delivery = $qry->fetch_assoc();

// === NOMBRES ADICIONALES ===
$dept = $conn->query("SELECT name FROM departments WHERE id = " . ($delivery['department_id'] ?? 0))->fetch_assoc()['name'] ?? 'N/A';
$loc = $conn->query("SELECT name FROM equipment_locations WHERE id = " . ($delivery['location_id'] ?? 0))->fetch_assoc()['name'] ?? 'N/A';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($eq['name']); ?> - Consulta Pública</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .card { border-radius: 20px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .info-label { font-weight: 600; color: #6c757d; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-value { font-size: 1.1rem; color: #212529; font-weight: 500; }
        .badge-inv { font-size: 1.5rem; padding: 0.6em 1.2em; }
        .header-gradient { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; }
        .public-badge { position: absolute; top: 20px; right: 20px; }
    </style>
</head>
<body>
<div class="container" style="max-width: 900px;">
    <div class="card">
        <div class="header-gradient position-relative">
            <span class="badge bg-light text-dark public-badge">
                <i class="fas fa-eye"></i> Consulta Pública
            </span>
            <h2 class="mb-3">
                <i class="fas fa-desktop me-2"></i>
                <?php echo htmlspecialchars($eq['name']); ?>
            </h2>
            <h4 class="mb-0">
                <span class="badge bg-white text-primary badge-inv">
                    #<?= $eq['number_inventory'] ?>
                </span>
            </h4>
        </div>

        <div class="card-body p-4">
            <!-- IMAGEN -->
            <?php if (!empty($eq['image'])): ?>
            <div class="text-center mb-4">
                <img src="<?= $eq['image'] ?>" class="img-fluid rounded shadow" 
                     style="max-height: 300px; object-fit: contain;">
            </div>
            <?php endif; ?>

            <!-- INFORMACIÓN BÁSICA -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="info-label">Marca</div>
                    <div class="info-value"><?= htmlspecialchars($eq['brand']) ?></div>
                </div>
                <div class="col-md-6">
                    <div class="info-label">Modelo</div>
                    <div class="info-value"><?= htmlspecialchars($eq['model']) ?></div>
                </div>
                <div class="col-md-6">
                    <div class="info-label">Serie</div>
                    <div class="info-value"><?= htmlspecialchars($eq['serie']) ?></div>
                </div>
                <div class="col-md-6">
                    <div class="info-label">Proveedor</div>
                    <div class="info-value"><?= htmlspecialchars($eq['supplier_name'] ?? 'N/A') ?></div>
                </div>
            </div>

            <hr class="my-4">

            <!-- UBICACIÓN Y RESPONSABLE -->
            <h5 class="mb-3 text-primary">
                <i class="fas fa-map-marker-alt"></i> Ubicación
            </h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="info-label">Departamento</div>
                    <div class="info-value"><?= htmlspecialchars($dept) ?></div>
                </div>
                <div class="col-md-6">
                    <div class="info-label">Ubicación</div>
                    <div class="info-value"><?= htmlspecialchars($loc) ?></div>
                </div>
                <div class="col-md-6">
                    <div class="info-label">Responsable</div>
                    <div class="info-value"><?= htmlspecialchars($delivery['responsible_name'] ?? 'N/A') ?></div>
                </div>
                <div class="col-md-6">
                    <div class="info-label">Fecha de Adquisición</div>
                    <div class="info-value">
                        <?= isset($reception['date_reception']) ? date('d/m/Y', strtotime($reception['date_reception'])) : 'N/A' ?>
                    </div>
                </div>
            </div>

            <!-- CARACTERÍSTICAS -->
            <?php if (!empty($eq['characteristics'])): ?>
            <hr class="my-4">
            <h5 class="mb-3 text-primary">
                <i class="fas fa-cogs"></i> Características Técnicas
            </h5>
            <p class="text-muted"><?= nl2br(htmlspecialchars($eq['characteristics'])) ?></p>
            <?php endif; ?>

            <!-- FOOTER -->
            <div class="text-center mt-4 pt-4 border-top">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Para más información, contacte al departamento correspondiente
                </small>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
