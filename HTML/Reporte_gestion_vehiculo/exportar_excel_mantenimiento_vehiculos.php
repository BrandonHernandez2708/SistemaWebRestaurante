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
$vehiculo_id = $_GET['vehiculo'] ?? '';
$taller_id = $_GET['taller'] ?? '';

// Configurar headers para descarga Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="reporte_mantenimiento_vehiculos_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Consulta con los mismos filtros
$conn = conectar();
$where = [];
$types = '';
$params = [];

if (!empty($fecha_desde)) { 
    $where[] = "mv.fecha_mantenimiento >= ?"; 
    $types .= 's'; 
    $params[] = $fecha_desde; 
}
if (!empty($fecha_hasta)) { 
    $where[] = "mv.fecha_mantenimiento <= ?"; 
    $types .= 's'; 
    $params[] = $fecha_hasta; 
}
if (!empty($vehiculo_id)) { 
    $where[] = "mv.id_vehiculo = ?"; 
    $types .= 'i'; 
    $params[] = (int)$vehiculo_id; 
}
if (!empty($taller_id)) { 
    $where[] = "mv.id_taller = ?"; 
    $types .= 'i'; 
    $params[] = (int)$taller_id; 
}

$sql = "
    SELECT 
        mv.id_mantenimiento,
        mv.fecha_mantenimiento,
        mv.descripcion_mantenimiento,
        mv.costo_mantenimiento,
        v.no_placa,
        v.marca_vehiculo,
        v.modelo_vehiculo,
        v.estado,
        t.nombre_taller
    FROM mantenimiento_vehiculo mv
    INNER JOIN vehiculos v ON mv.id_vehiculo = v.id_vehiculo
    INNER JOIN talleres t ON mv.id_taller = t.id_taller
";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY mv.fecha_mantenimiento DESC, v.no_placa ASC";

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
$total_mantenimientos = 0;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $mantenimientos_data[] = $row;
        $total_costo += (float)$row['costo_mantenimiento'];
        $total_mantenimientos++;
    }
}

// Consulta para obtener nombre del vehículo si hay filtro
$vehiculo_info = '';
if (!empty($vehiculo_id)) {
    $sql_veh = "SELECT no_placa, marca_vehiculo, modelo_vehiculo FROM vehiculos WHERE id_vehiculo = ?";
    $stmt_veh = $conn->prepare($sql_veh);
    $stmt_veh->bind_param("i", $vehiculo_id);
    $stmt_veh->execute();
    $result_veh = $stmt_veh->get_result();
    $vehiculo_data = $result_veh->fetch_assoc();
    if ($vehiculo_data) {
        $vehiculo_info = $vehiculo_data['no_placa'] . ' - ' . $vehiculo_data['marca_vehiculo'] . ' ' . $vehiculo_data['modelo_vehiculo'];
    }
    $stmt_veh->close();
}

// Consulta para obtener nombre del taller si hay filtro
$taller_nombre = '';
if (!empty($taller_id)) {
    $sql_taller = "SELECT nombre_taller FROM talleres WHERE id_taller = ?";
    $stmt_taller = $conn->prepare($sql_taller);
    $stmt_taller->bind_param("i", $taller_id);
    $stmt_taller->execute();
    $result_taller = $stmt_taller->get_result();
    $taller_data = $result_taller->fetch_assoc();
    if ($taller_data) {
        $taller_nombre = $taller_data['nombre_taller'];
    }
    $stmt_taller->close();
}

// Obtener vehículos únicos para estadísticas
$vehiculos_unicos = [];
foreach ($mantenimientos_data as $mantenimiento) {
    $vehiculos_unicos[$mantenimiento['no_placa']] = true;
}
$total_vehiculos_unicos = count($vehiculos_unicos);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #dc2626; color: white; font-weight: bold; }
        .total-row { background-color: #f1f5f9; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .estado-cell { 
            padding: 4px 8px; 
            border-radius: 4px; 
            font-size: 11px; 
            font-weight: bold; 
            text-align: center;
        }
        .estado-en-taller { background: #fef3c7; color: #d97706; }
        .estado-disponible { background: #dcfce7; color: #16a34a; }
        .estado-mantenimiento { background: #f3e8ff; color: #9333ea; }
    </style>
</head>
<body>
    <h2>Reporte de Mantenimiento de Vehículos</h2>
    <p>Generado: <?= date('d/m/Y H:i:s'); ?></p>
    
    <?php if (!empty($fecha_desde) || !empty($fecha_hasta) || !empty($vehiculo_id) || !empty($taller_id)): ?>
    <p><strong>Filtros aplicados:</strong>
        <?php 
        $filtros = [];
        if (!empty($fecha_desde)) $filtros[] = "Desde: " . date('d/m/Y', strtotime($fecha_desde));
        if (!empty($fecha_hasta)) $filtros[] = "Hasta: " . date('d/m/Y', strtotime($fecha_hasta));
        if (!empty($vehiculo_id)) $filtros[] = "Vehículo: " . $vehiculo_info;
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
                <th>Placa</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Estado</th>
                <th>Taller</th>
                <th>Descripción</th>
                <th>Costo (Q)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($mantenimientos_data as $mantenimiento): ?>
            <tr>
                <td class="text-center"><?= $mantenimiento['id_mantenimiento']; ?></td>
                <td><?= date("d/m/Y", strtotime($mantenimiento['fecha_mantenimiento'])); ?></td>
                <td><?= htmlspecialchars($mantenimiento['no_placa']); ?></td>
                <td><?= htmlspecialchars($mantenimiento['marca_vehiculo']); ?></td>
                <td><?= htmlspecialchars($mantenimiento['modelo_vehiculo']); ?></td>
                <td class="estado-cell 
                    <?php 
                    switch($mantenimiento['estado']) {
                        case 'EN_TALLER': echo 'estado-en-taller'; break;
                        case 'DISPONIBLE': echo 'estado-disponible'; break;
                        case 'MANTENIMIENTO': echo 'estado-mantenimiento'; break;
                        default: echo 'estado-disponible';
                    }
                    ?>">
                    <?= $mantenimiento['estado']; ?>
                </td>
                <td><?= htmlspecialchars($mantenimiento['nombre_taller']); ?></td>
                <td><?= htmlspecialchars($mantenimiento['descripcion_mantenimiento']); ?></td>
                <td class="text-right">Q<?= number_format((float)$mantenimiento['costo_mantenimiento'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
            
            <?php if (!empty($mantenimientos_data)): ?>
            <tr class="total-row">
                <td colspan="8" class="text-right"><strong>TOTAL GENERAL:</strong></td>
                <td class="text-right"><strong>Q<?= number_format($total_costo, 2); ?></strong></td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (empty($mantenimientos_data)): ?>
    <p>No se encontraron mantenimientos con los filtros aplicados.</p>
    <?php endif; ?>

    <div style="margin-top: 20px; font-size: 12px; color: #666;">
        <p><strong>Resumen Estadístico:</strong></p>
        <p>Total de mantenimientos: <?= $total_mantenimientos; ?></p>
        <p>Vehículos únicos atendidos: <?= $total_vehiculos_unicos; ?></p>
        <p>Costo total: Q<?= number_format($total_costo, 2); ?></p>
        <p>Costo promedio por mantenimiento: Q<?= $total_mantenimientos > 0 ? number_format($total_costo / $total_mantenimientos, 2) : '0.00'; ?></p>
        <p>Costo promedio por vehículo: Q<?= $total_vehiculos_unicos > 0 ? number_format($total_costo / $total_vehiculos_unicos, 2) : '0.00'; ?></p>
    </div>
</body>
</html>

<?php
// Limpiar recursos
if (isset($stmt)) $stmt->close();
desconectar($conn);
?>