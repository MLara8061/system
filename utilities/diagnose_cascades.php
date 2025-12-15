<?php
require_once __DIR__ . '/../config/db.php';

$pdo = get_pdo();

function out(string $text = ''): void {
    echo $text . PHP_EOL;
}

function yesno(bool $b): string {
    return $b ? 'SI' : 'NO';
}

function getDatabaseName(PDO $pdo): string {
    $stmt = $pdo->query('SELECT DATABASE() AS db');
    $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
    return (string)($row['db'] ?? '');
}

function tableExists(PDO $pdo, string $table): bool {
    $db = getDatabaseName($pdo);
    if ($db === '') return false;
    $stmt = $pdo->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = ? AND table_name = ? LIMIT 1');
    $stmt->execute([$db, $table]);
    return (bool)$stmt->fetchColumn();
}

function columnExists(PDO $pdo, string $table, string $column): bool {
    $db = getDatabaseName($pdo);
    if ($db === '') return false;
    $stmt = $pdo->prepare('SELECT 1 FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ? LIMIT 1');
    $stmt->execute([$db, $table, $column]);
    return (bool)$stmt->fetchColumn();
}

function fetchAllSafe(PDO $pdo, string $sql, array $params = []): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return is_array($rows) ? $rows : [];
}

function fetchValueSafe(PDO $pdo, string $sql, array $params = []): int {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)($stmt->fetchColumn() ?? 0);
}

try {
    $db = getDatabaseName($pdo);
    if ($db === '') {
        out('ERROR: No hay base de datos seleccionada (DATABASE() vacío).');
        out('Revisa DB_NAME en config/.env');
        exit(2);
    }

    out('=== Diagnóstico cascadas Departamento → Ubicación → Cargo Responsable ===');
    out('DB: ' . $db);

    $hasDepartments = tableExists($pdo, 'departments');
    $hasLocations = tableExists($pdo, 'locations');
    $hasJobPositions = tableExists($pdo, 'job_positions');
    $hasLocationPositions = tableExists($pdo, 'location_positions');

    $locationsHasDepartmentId = $hasLocations && columnExists($pdo, 'locations', 'department_id');
    $jobPositionsHasLocationId = $hasJobPositions && columnExists($pdo, 'job_positions', 'location_id');
    $jobPositionsHasDepartmentId = $hasJobPositions && columnExists($pdo, 'job_positions', 'department_id');

    out('');
    out('**Estructura detectada**');
    out('- departments: ' . yesno($hasDepartments));
    out('- locations: ' . yesno($hasLocations) . ' | locations.department_id: ' . yesno($locationsHasDepartmentId));
    out('- job_positions: ' . yesno($hasJobPositions) . ' | job_positions.location_id: ' . yesno($jobPositionsHasLocationId) . ' | job_positions.department_id: ' . yesno($jobPositionsHasDepartmentId));
    out('- location_positions: ' . yesno($hasLocationPositions));

    if (!$hasDepartments) {
        out('');
        out('BLOQUEO: No existe la tabla departments, no es posible evaluar la cascada.');
        exit(1);
    }

    $departmentsCount = fetchValueSafe($pdo, 'SELECT COUNT(*) FROM departments');
    out('');
    out('**Conteos**');
    out('- Departamentos: ' . $departmentsCount);

    if ($hasLocations) {
        $locationsCount = fetchValueSafe($pdo, 'SELECT COUNT(*) FROM locations');
        out('- Ubicaciones: ' . $locationsCount);
    } else {
        out('- Ubicaciones: (tabla locations NO existe)');
    }

    if ($hasJobPositions) {
        $positionsCount = fetchValueSafe($pdo, 'SELECT COUNT(*) FROM job_positions');
        out('- Cargos (job_positions): ' . $positionsCount);
    } else {
        out('- Cargos (job_positions): (tabla job_positions NO existe)');
    }

    if ($hasLocationPositions) {
        $lpCount = fetchValueSafe($pdo, 'SELECT COUNT(*) FROM location_positions');
        out('- Asignaciones (location_positions): ' . $lpCount);
    }

    // 1) Departamentos sin ubicaciones
    out('');
    out('**1) Departamentos sin ubicaciones**');
    if (!$hasLocations) {
        out('- No se puede evaluar: falta tabla locations');
    } elseif (!$locationsHasDepartmentId) {
        out('- No se puede evaluar directo: locations.department_id NO existe');
        out('- Acción: tu instalación no relaciona Ubicación con Departamento por columna; la UI mostrará todas las ubicaciones o depende de otra tabla.');
    } else {
        $missing = fetchAllSafe(
            $pdo,
            'SELECT d.id, d.name FROM departments d LEFT JOIN locations l ON l.department_id = d.id WHERE l.id IS NULL ORDER BY d.name ASC LIMIT 50'
        );
        $missingCount = fetchValueSafe(
            $pdo,
            'SELECT COUNT(*) FROM departments d LEFT JOIN locations l ON l.department_id = d.id WHERE l.id IS NULL'
        );
        out('- Total sin ubicaciones: ' . $missingCount);
        foreach ($missing as $row) {
            out('  - ' . (string)$row['id'] . ' | ' . (string)$row['name']);
        }
        if ($missingCount > 50) {
            out('  (mostrando 50)');
        }
    }

    // 2) Ubicaciones sin cargos
    out('');
    out('**2) Ubicaciones sin cargos**');
    if (!$hasLocations) {
        out('- No se puede evaluar: falta tabla locations');
    } elseif (!$hasJobPositions) {
        out('- No se puede evaluar: falta tabla job_positions');
    } else {
        $missingLocations = [];
        $missingCount = null;

        if ($jobPositionsHasLocationId) {
            $missingLocations = fetchAllSafe(
                $pdo,
                'SELECT l.id, l.name FROM locations l LEFT JOIN job_positions j ON j.location_id = l.id WHERE j.id IS NULL ORDER BY l.name ASC LIMIT 50'
            );
            $missingCount = fetchValueSafe(
                $pdo,
                'SELECT COUNT(*) FROM locations l LEFT JOIN job_positions j ON j.location_id = l.id WHERE j.id IS NULL'
            );
            out('- Modo: job_positions.location_id');
        } elseif ($hasLocationPositions) {
            $missingLocations = fetchAllSafe(
                $pdo,
                'SELECT l.id, l.name FROM locations l LEFT JOIN location_positions lp ON lp.location_id = l.id WHERE lp.location_id IS NULL ORDER BY l.name ASC LIMIT 50'
            );
            $missingCount = fetchValueSafe(
                $pdo,
                'SELECT COUNT(*) FROM locations l LEFT JOIN location_positions lp ON lp.location_id = l.id WHERE lp.location_id IS NULL'
            );
            out('- Modo: location_positions (tabla puente)');
        } elseif ($jobPositionsHasDepartmentId && $locationsHasDepartmentId) {
            // cargos por departamento
            $missingLocations = fetchAllSafe(
                $pdo,
                'SELECT l.id, l.name FROM locations l LEFT JOIN job_positions j ON j.department_id = l.department_id WHERE j.id IS NULL ORDER BY l.name ASC LIMIT 50'
            );
            $missingCount = fetchValueSafe(
                $pdo,
                'SELECT COUNT(*) FROM locations l LEFT JOIN job_positions j ON j.department_id = l.department_id WHERE j.id IS NULL'
            );
            out('- Modo: cargos por departamento (job_positions.department_id)');
        } else {
            out('- No se puede evaluar: no hay relación conocida (location_id / location_positions / department_id)');
        }

        if ($missingCount !== null) {
            out('- Total ubicaciones sin cargos: ' . $missingCount);
            foreach ($missingLocations as $row) {
                out('  - ' . (string)$row['id'] . ' | ' . (string)$row['name']);
            }
            if ($missingCount > 50) {
                out('  (mostrando 50)');
            }
        }
    }

    // 3) Señales de datos "raros"
    out('');
    out('**3) Señales rápidas**');
    if ($hasLocations && $locationsHasDepartmentId) {
        $orphanLocations = fetchValueSafe(
            $pdo,
            'SELECT COUNT(*) FROM locations l LEFT JOIN departments d ON d.id = l.department_id WHERE l.department_id IS NOT NULL AND l.department_id <> 0 AND d.id IS NULL'
        );
        out('- Ubicaciones con department_id inválido: ' . $orphanLocations);
    }

    if ($hasJobPositions && $jobPositionsHasLocationId && $hasLocations) {
        $orphanPositions = fetchValueSafe(
            $pdo,
            'SELECT COUNT(*) FROM job_positions j LEFT JOIN locations l ON l.id = j.location_id WHERE j.location_id IS NOT NULL AND j.location_id <> 0 AND l.id IS NULL'
        );
        out('- Cargos con location_id inválido: ' . $orphanPositions);
    }

    out('');
    out('=== Fin diagnóstico ===');
    out('Tip: si los conteos dan 0 o salen muchos "sin ubicaciones/sin cargos", el problema es de datos/asignaciones más que de código.');
} catch (Throwable $e) {
    out('ERROR: ' . $e->getMessage());
    exit(1);
}
