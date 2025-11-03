<?php
session_start();
require_once '../conexion.php';

// Verificar acceso
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// Obtener parámetros de filtro
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';
$electrodomestico_id = $_GET['electrodomestico'] ?? '';
$taller_id = $_GET['taller'] ?? '';

// Configurar headers para descarga Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="reporte_mantenimiento_electrodomesticos_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Consulta con los mismos filtros
$conn = conectar();
$where = [];
$types = '';
$params = [];

if (!empty($fecha_desde)) { 
    $where[] = "me.fecha_mantenimiento >= ?"; 
    $types .= 's'; 
    $params[] = $fecha_desde; 
}
if (!empty($fecha_hasta)) { 
    $where[] = "me.fecha_mantenimiento <= ?"; 
    $types .= 's'; 
    $params[] = $fecha_hasta; 
}
if (!empty($electrodomestico_id)) { 
    $where[] = "me.id_mobiliario = ?"; 
    $types .= 'i'; 
    $params[] = (int)$electrodomestico_id; 
}
if (!empty($taller_id)) { 
    if ($taller_id === 'interno') {
        $where[] = "me.id_taller IS NULL"; 
    } else {
        $where[] = "me.id_taller = ?"; 
        $types .= 'i'; 
        $params[] = (int)$taller_id;
    }
}

$sql = "
    SELECT 
        me.id_mantenimiento_elect,
        me.fecha_mantenimiento,
        me.descripcion_mantenimiento,
        me.costo_mantenimiento_q,
        im.nombre_mobiliario,
        tm.descripcion AS tipo_mobiliario,
        t.nombre_taller,
        t.telefono AS telefono_taller
    FROM mantenimiento_electrodomesticos me
    INNER JOIN inventario_mobiliario im ON me.id_mobiliario = im.id_mobiliario
    LEFT JOIN tipos_mobiliario tm ON im.id_tipo_mobiliario = tm.id_tipo_mobiliario
    LEFT JOIN talleres t ON me.id_taller = t.id_taller
";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY me.fecha_mantenimiento DESC, im.nombre_mobiliario ASC";

// Ejecutar consulta
if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// Obtener datos
$mantenimientos_data = [];
$total_costo = 0;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $mantenimientos_data[] = $row;
        $total_costo += (float)$row['costo_mantenimiento_q'];
    }
}

// Consulta para obtener nombre del electrodoméstico si hay filtro
$electrodomestico_nombre = '';
if (!empty($electrodomestico_id)) {
    $sql_electro = "SELECT nombre_mobiliario FROM inventario_mobiliario WHERE id_mobiliario = ?";
    $stmt_electro = $conn->prepare($sql_electro);
    $stmt_electro->bind_param("i", $electrodomestico_id);
    $stmt_electro->execute();
    $result_electro = $stmt_electro->get_result();
    $electrodomestico_nombre = $result_electro->fetch_assoc()['nombre_mobiliario'] ?? '';
    $stmt_electro->close();
}

// Consulta para obtener nombre del taller si hay filtro
$taller_nombre = '';
if (!empty($taller_id) && $taller_id !== 'interno') {
    $sql_taller = "SELECT nombre_taller FROM talleres WHERE id_taller = ?";
    $stmt_taller = $conn->prepare($sql_taller);
    $stmt_taller->bind_param("i", $taller_id);
    $stmt_taller->execute();
    $result_taller = $stmt_taller->get_result();
    $taller_nombre = $result_taller->fetch_assoc()['nombre_taller'] ?? '';
    $stmt_taller->close();
} elseif ($taller_id === 'interno') {
    $taller_nombre = 'Mantenimiento Interno';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #1e40af; color: white; font-weight: bold; }
        .total-row { background-color: #f1f5f9; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <h2>Reporte de Mantenimiento de Electrodomésticos</h2>
    <p>Generado: <?= date('d/m/Y H:i:s'); ?></p>
    
    <?php if (!empty($fecha_desde) || !empty($fecha_hasta) || !empty($electrodomestico_id) || !empty($taller_id)): ?>
    <p><strong>Filtros aplicados:</strong>
        <?php 
        $filtros = [];
        if (!empty($fecha_desde)) $filtros[] = "Desde: " . date('d/m/Y', strtotime($fecha_desde));
        if (!empty($fecha_hasta)) $filtros[] = "Hasta: " . date('d/m/Y', strtotime($fecha_hasta));
        if (!empty($electrodomestico_id)) $filtros[] = "Electrodoméstico: " . $electrodomestico_nombre;
        if (!empty($taller_id)) $filtros[] = "Taller: " . $taller_nombre;
        echo implode(' | ', $filtros);
        ?>
    </p>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Electrodoméstico</th>
                <th>Tipo</th>
                <th>Descripción</th>
                <th>Taller</th>
                <th>Teléfono Taller</th>
                <th>Costo (Q)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($mantenimientos_data as $mantenimiento): ?>
            <tr>
                <td class="text-center"><?= $mantenimiento['id_mantenimiento_elect']; ?></td>
                <td><?= date("d/m/Y", strtotime($mantenimiento['fecha_mantenimiento'])); ?></td>
                <td><?= htmlspecialchars($mantenimiento['nombre_mobiliario']); ?></td>
                <td><?= htmlspecialchars($mantenimiento['tipo_mobiliario'] ?? 'N/A'); ?></td>
                <td><?= htmlspecialchars($mantenimiento['descripcion_mantenimiento']); ?></td>
                <td><?= !empty($mantenimiento['nombre_taller']) ? htmlspecialchars($mantenimiento['nombre_taller']) : 'Mantenimiento Interno'; ?></td>
                <td><?= !empty($mantenimiento['telefono_taller']) ? htmlspecialchars($mantenimiento['telefono_taller']) : 'N/A'; ?></td>
                <td class="text-right">Q<?= number_format((float)$mantenimiento['costo_mantenimiento_q'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
            
            <?php if (!empty($mantenimientos_data)): ?>
            <tr class="total-row">
                <td colspan="7" class="text-right"><strong>TOTAL GENERAL:</strong></td>
                <td class="text-right"><strong>Q<?= number_format($total_costo, 2); ?></strong></td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (empty($mantenimientos_data)): ?>
    <p>No se encontraron mantenimientos con los filtros aplicados.</p>
    <?php endif; ?>

    <div style="margin-top: 20px; font-size: 12px; color: #666;">
        <p><strong>Resumen:</strong></p>
        <p>Total de mantenimientos: <?= count($mantenimientos_data); ?></p>
        <p>Costo total: Q<?= number_format($total_costo, 2); ?></p>
        <p>Costo promedio: Q<?= count($mantenimientos_data) > 0 ? number_format($total_costo / count($mantenimientos_data), 2) : '0.00'; ?></p>
    </div>
</body>
</html>

<?php
// Limpiar recursos
if (isset($stmt)) $stmt->close();
desconectar($conn);
?>