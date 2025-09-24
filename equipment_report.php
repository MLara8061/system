<?php
// equipment_report.php
// ─────────────────────────────────────────────
// Versión de prueba SIN base de datos
// Muestra datos de ejemplo mientras se crea la tabla real
// ─────────────────────────────────────────────

// Datos de prueba
$datos_prueba = [
    [
        'equipo_id'      => 'EQ-001',
        'disciplina'     => 'Cardiología',
        'encargado'      => 'Dr. Juan Pérez',
        'marca'          => 'Philips',
        'caracteristicas'=> 'Monitor multiparámetro, 12 canales',
        'inventario'     => 5
    ],
    [
        'equipo_id'      => 'EQ-002',
        'disciplina'     => 'Radiología',
        'encargado'      => 'Ing. Ana Gómez',
        'marca'          => 'Siemens',
        'caracteristicas'=> 'Rayos X digital, 300kV',
        'inventario'     => 2
    ],
    [
        'equipo_id'      => 'EQ-003',
        'disciplina'     => 'Laboratorio',
        'encargado'      => 'Lic. Pedro Hernández',
        'marca'          => 'Abbott',
        'caracteristicas'=> 'Analizador químico, 500 muestras/hora',
        'inventario'     => 3
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Frecuencia Anual - Ejemplo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background: #f9f9f9;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background: #0077b6;
            color: #fff;
        }
        tr:nth-child(even) {
            background: #f2f2f2;
        }
    </style>
</head>
<body>

<h1>Reporte de Frecuencia Anual (Datos de Prueba)</h1>

<table>
    <thead>
        <tr>
            <th>ID Equipo</th>
            <th>Disciplina</th>
            <th>Encargado</th>
            <th>Marca</th>
            <th>Características</th>
            <th>Inventario</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($datos_prueba as $fila): ?>
            <tr>
                <td><?= htmlspecialchars($fila['equipo_id']) ?></td>
                <td><?= htmlspecialchars($fila['disciplina']) ?></td>
                <td><?= htmlspecialchars($fila['encargado']) ?></td>
                <td><?= htmlspecialchars($fila['marca']) ?></td>
                <td><?= htmlspecialchars($fila['caracteristicas']) ?></td>
                <td><?= htmlspecialchars($fila['inventario']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
