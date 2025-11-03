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
$empleado_id = $_GET['empleado'] ?? '';
$viaje_id = $_GET['viaje'] ?? '';

// Configurar headers para descarga Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="reporte_accidentes_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Consulta con los mismos filtros
$conn = conectar();
$where = [];
$types = '';
$params = [];

if (!empty($fecha_desde)) { 
    $where[] = "ra.fecha_hora >= ?"; 
    $types .= 's'; 
    $params[] = $fecha_desde . ' 00:00:00'; 
}
if (!empty($fecha_hasta)) { 
    $where[] = "ra.fecha_hora <= ?"; 
    $types .= 's'; 
    $params[] = $fecha_hasta . ' 23:59:59'; 
}
if (!empty($vehiculo_id)) { 
    $where[] = "ve.id_vehiculo = ?"; 
    $types .= 'i'; 
    $params[] = (int)$vehiculo_id; 
}
if (!empty($empleado_id)) { 
    $where[] = "ra.id_empleado = ?"; 
    $types .= 'i'; 
    $params[] = (int)$empleado_id; 
}
if (!empty($viaje_id)) { 
    $where[] = "ra.id_viaje = ?"; 
    $types .= 'i'; 
    $params[] = (int)$viaje_id; 
}

$sql = "
    SELECT 
        ra.id_accidente,
        ra.fecha_hora,
        ra.descripcion_accidente,
        v.id_viaje,
        v.descripcion_viaje,
        v.fecha_hora_salida,
        ve.no_placa,
        ve.marca_vehiculo,
        ve.modelo_vehiculo,
        e.nombre_empleado,
        e.apellido_empleado,
        ep.nombre_empleado as nombre_piloto,
        ep.apellido_empleado as apellido_piloto
    FROM reportes_accidentes ra
    INNER JOIN viajes v ON ra.id_viaje = v.id_viaje
    INNER JOIN vehiculos ve ON v.id_vehiculo = ve.id_vehiculo
    INNER JOIN empleados e ON ra.id_empleado = e.id_empleado
    LEFT JOIN empleados ep ON v.id_empleado_piloto = ep.id_empleado
";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY ra.fecha_hora DESC, ve.no_placa ASC";

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
$accidentes_data = [];
$total_accidentes = 0;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $accidentes_data[] = $row;
        $total_accidentes++;
    }
}

// Obtener información de filtros para mostrar
$filtros_info = [];
if (!empty($fecha_desde)) $filtros_info[] = "Desde: " . date('d/m/Y', strtotime($fecha_desde));
if (!empty($fecha_hasta)) $filtros_info[] = "Hasta: " . date('d/m/Y', strtotime($fecha_hasta));

if (!empty($vehiculo_id)) {
    $sql_veh = "SELECT no_placa, marca_vehiculo, modelo_vehiculo FROM vehiculos WHERE id_vehiculo = ?";
    $stmt_veh = $conn->prepare($sql_veh);
    $stmt_veh->bind_param("i", $vehiculo_id);
    $stmt_veh->execute();
    $result_veh = $stmt_veh->get_result();
    $vehiculo_data = $result_veh->fetch_assoc();
    if ($vehiculo_data) {
        $filtros_info[] = "Vehículo: " . $vehiculo_data['no_placa'] . " - " . $vehiculo_data['marca_vehiculo'] . " " . $vehiculo_data['modelo_vehiculo'];
    }
    $stmt_veh->close();
}

if (!empty($empleado_id)) {
    $sql_emp = "SELECT nombre_empleado, apellido_empleado FROM empleados WHERE id_empleado = ?";
    $stmt_emp = $conn->prepare($sql_emp);
    $stmt_emp->bind_param("i", $empleado_id);
    $stmt_emp->execute();
    $result_emp = $stmt_emp->get_result();
    $empleado_data = $result_emp->fetch_assoc();
    if ($empleado_data) {
        $filtros_info[] = "Empleado: " . $empleado_data['nombre_empleado'] . " " . $empleado_data['apellido_empleado'];
    }
    $stmt_emp->close();
}

if (!empty($viaje_id)) {
    $sql_viaje = "SELECT v.id_viaje, v.descripcion_viaje, ve.no_placa FROM viajes v 
                  INNER JOIN vehiculos ve ON v.id_vehiculo = ve.id_vehiculo 
                  WHERE v.id_viaje = ?";
    $stmt_viaje = $conn->prepare($sql_viaje);
    $stmt_viaje->bind_param("i", $viaje_id);
    $stmt_viaje->execute();
    $result_viaje = $stmt_viaje->get_result();
    $viaje_data = $result_viaje->fetch_assoc();
    if ($viaje_data) {
        $filtros_info[] = "Viaje: #" . $viaje_data['id_viaje'] . " - " . $viaje_data['no_placa'];
    }
    $stmt_viaje->close();
}

// Obtener estadísticas
$vehiculos_unicos = [];
$empleados_unicos = [];
$viajes_unicos = [];

foreach ($accidentes_data as $accidente) {
    $vehiculos_unicos[$accidente['no_placa']] = true;
    $empleados_unicos[$accidente['nombre_empleado'] . ' ' . $accidente['apellido_empleado']] = true;
    $viajes_unicos[$accidente['id_viaje']] = true;
}

$total_vehiculos_unicos = count($vehiculos_unicos);
$total_empleados_unicos = count($empleados_unicos);
$total_viajes_unicos = count($viajes_unicos);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #dc2626; color: white; font-weight: bold; }
        .text-center { text-align: center; }
        .filtros-info { background: #f8fafc; padding: 10px; margin: 10px 0; border-left: 4px solid #dc2626; }
    </style>
</head>
<body>
    <h2>Reporte de Accidentes</h2>
    <p>Generado: <?= date('d/m/Y H:i:s'); ?></p>
    
    <?php if (!empty($filtros_info)): ?>
    <div class="filtros-info">
        <strong>Filtros aplicados:</strong><br>
        <?= implode(' | ', $filtros_info); ?>
    </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha/Hora</th>
                <th>Vehículo</th>
                <th>Marca/Modelo</th>
                <th>Viaje ID</th>
                <th>Descripción Viaje</th>
                <th>Fecha Salida Viaje</th>
                <th>Piloto</th>
                <th>Empleado que Reporta</th>
                <th>Descripción del Accidente</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($accidentes_data as $accidente): ?>
            <tr>
                <td class="text-center"><?= $accidente['id_accidente']; ?></td>
                <td><?= date("d/m/Y H:i", strtotime($accidente['fecha_hora'])); ?></td>
                <td><?= htmlspecialchars($accidente['no_placa']); ?></td>
                <td><?= htmlspecialchars($accidente['marca_vehiculo'] . ' ' . $accidente['modelo_vehiculo']); ?></td>
                <td class="text-center"><?= $accidente['id_viaje']; ?></td>
                <td><?= htmlspecialchars($accidente['descripcion_viaje']); ?></td>
                <td><?= date("d/m/Y H:i", strtotime($accidente['fecha_hora_salida'])); ?></td>
                <td><?= !empty($accidente['nombre_piloto']) ? htmlspecialchars($accidente['nombre_piloto'] . ' ' . $accidente['apellido_piloto']) : 'No asignado'; ?></td>
                <td><?= htmlspecialchars($accidente['nombre_empleado'] . ' ' . $accidente['apellido_empleado']); ?></td>
                <td><?= htmlspecialchars($accidente['descripcion_accidente']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (empty($accidentes_data)): ?>
    <p>No se encontraron accidentes con los filtros aplicados.</p>
    <?php endif; ?>

    <div style="margin-top: 20px; font-size: 12px; color: #666;">
        <p><strong>Resumen Estadístico:</strong></p>
        <p>Total de accidentes reportados: <?= $total_accidentes; ?></p>
        <p>Vehículos únicos involucrados: <?= $total_vehiculos_unicos; ?></p>
        <p>Empleados únicos que reportaron: <?= $total_empleados_unicos; ?></p>
        <p>Viajes únicos afectados: <?= $total_viajes_unicos; ?></p>
        <?php if ($total_accidentes > 0): ?>
        <p>Promedio de accidentes por vehículo: <?= number_format($total_accidentes / $total_vehiculos_unicos, 2); ?></p>
        <p>Promedio de accidentes por empleado: <?= number_format($total_accidentes / $total_empleados_unicos, 2); ?></p>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
// Limpiar recursos
if (isset($stmt)) $stmt->close();
desconectar($conn);
?>