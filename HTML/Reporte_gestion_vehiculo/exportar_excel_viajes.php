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
$ruta_id = $_GET['ruta'] ?? '';
$piloto_id = $_GET['piloto'] ?? '';
$estado_viaje = $_GET['estado'] ?? '';

// Configurar headers para descarga Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="reporte_viajes_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Función para determinar el estado del viaje
function determinarEstadoViaje($fechaSalida, $tiempoAproximado) {
    $ahora = new DateTime();
    $fechaSalidaObj = new DateTime($fechaSalida);
    
    if ($fechaSalidaObj > $ahora) {
        return 'PROGRAMADO';
    }
    
    if ($tiempoAproximado) {
        $fechaLlegadaEstimada = clone $fechaSalidaObj;
        $fechaLlegadaEstimada->modify("+{$tiempoAproximado} minutes");
        
        if ($ahora > $fechaLlegadaEstimada) {
            return 'COMPLETADO';
        } else {
            return 'EN_CURSO';
        }
    }
    
    $diferenciaHoras = ($ahora->getTimestamp() - $fechaSalidaObj->getTimestamp()) / 3600;
    
    if ($diferenciaHoras > 24) {
        return 'COMPLETADO';
    } else if ($diferenciaHoras > 0) {
        return 'EN_CURSO';
    } else {
        return 'PROGRAMADO';
    }
}

// Consulta con los mismos filtros
$conn = conectar();
$where = [];
$types = '';
$params = [];

if (!empty($fecha_desde)) { 
    $where[] = "v.fecha_hora_salida >= ?"; 
    $types .= 's'; 
    $params[] = $fecha_desde . ' 00:00:00'; 
}
if (!empty($fecha_hasta)) { 
    $where[] = "v.fecha_hora_salida <= ?"; 
    $types .= 's'; 
    $params[] = $fecha_hasta . ' 23:59:59'; 
}
if (!empty($vehiculo_id)) { 
    $where[] = "v.id_vehiculo = ?"; 
    $types .= 'i'; 
    $params[] = (int)$vehiculo_id; 
}
if (!empty($ruta_id)) { 
    $where[] = "v.id_ruta = ?"; 
    $types .= 'i'; 
    $params[] = (int)$ruta_id; 
}
if (!empty($piloto_id)) { 
    $where[] = "v.id_empleado_piloto = ?"; 
    $types .= 'i'; 
    $params[] = (int)$piloto_id; 
}

$sql = "
    SELECT 
        v.id_viaje,
        v.fecha_hora_salida,
        v.tiempo_aproximado_min,
        v.descripcion_viaje,
        r.descripcion_ruta,
        r.inicio_ruta,
        r.fin_ruta,
        ve.no_placa,
        ve.marca_vehiculo,
        ve.modelo_vehiculo,
        ve.estado as estado_vehiculo,
        ep.nombre_empleado as piloto_nombre,
        ep.apellido_empleado as piloto_apellido,
        ea.nombre_empleado as acompanante_nombre,
        ea.apellido_empleado as acompanante_apellido
    FROM viajes v
    INNER JOIN rutas r ON v.id_ruta = r.id_ruta
    INNER JOIN vehiculos ve ON v.id_vehiculo = ve.id_vehiculo
    INNER JOIN empleados ep ON v.id_empleado_piloto = ep.id_empleado
    LEFT JOIN empleados ea ON v.id_empleado_acompanante = ea.id_empleado
";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY v.fecha_hora_salida DESC, ve.no_placa ASC";

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
$viajes_data = [];
$total_viajes = 0;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['estado_viaje'] = determinarEstadoViaje($row['fecha_hora_salida'], $row['tiempo_aproximado_min']);
        
        // Filtrar por estado si se especificó
        if (empty($estado_viaje) || $row['estado_viaje'] === $estado_viaje) {
            $viajes_data[] = $row;
            $total_viajes++;
        }
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

if (!empty($ruta_id)) {
    $sql_ruta = "SELECT descripcion_ruta, inicio_ruta, fin_ruta FROM rutas WHERE id_ruta = ?";
    $stmt_ruta = $conn->prepare($sql_ruta);
    $stmt_ruta->bind_param("i", $ruta_id);
    $stmt_ruta->execute();
    $result_ruta = $stmt_ruta->get_result();
    $ruta_data = $result_ruta->fetch_assoc();
    if ($ruta_data) {
        $filtros_info[] = "Ruta: " . $ruta_data['descripcion_ruta'] . " (" . $ruta_data['inicio_ruta'] . " - " . $ruta_data['fin_ruta'] . ")";
    }
    $stmt_ruta->close();
}

if (!empty($piloto_id)) {
    $sql_piloto = "SELECT nombre_empleado, apellido_empleado FROM empleados WHERE id_empleado = ?";
    $stmt_piloto = $conn->prepare($sql_piloto);
    $stmt_piloto->bind_param("i", $piloto_id);
    $stmt_piloto->execute();
    $result_piloto = $stmt_piloto->get_result();
    $piloto_data = $result_piloto->fetch_assoc();
    if ($piloto_data) {
        $filtros_info[] = "Piloto: " . $piloto_data['nombre_empleado'] . " " . $piloto_data['apellido_empleado'];
    }
    $stmt_piloto->close();
}

if (!empty($estado_viaje)) {
    $filtros_info[] = "Estado: " . $estado_viaje;
}

// Obtener estadísticas
$vehiculos_unicos = [];
$pilotos_unicos = [];
$rutas_unicas = [];
$estados_count = [
    'PROGRAMADO' => 0,
    'EN_CURSO' => 0,
    'COMPLETADO' => 0
];

foreach ($viajes_data as $viaje) {
    $vehiculos_unicos[$viaje['no_placa']] = true;
    $pilotos_unicos[$viaje['piloto_nombre'] . ' ' . $viaje['piloto_apellido']] = true;
    $rutas_unicas[$viaje['descripcion_ruta']] = true;
    $estados_count[$viaje['estado_viaje']]++;
}

$total_vehiculos_unicos = count($vehiculos_unicos);
$total_pilotos_unicos = count($pilotos_unicos);
$total_rutas_unicas = count($rutas_unicas);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #3b82f6; color: white; font-weight: bold; }
        .text-center { text-align: center; }
        .filtros-info { background: #f8fafc; padding: 10px; margin: 10px 0; border-left: 4px solid #3b82f6; }
        .estado-cell { 
            padding: 4px 8px; 
            border-radius: 4px; 
            font-size: 11px; 
            font-weight: bold; 
            text-align: center;
        }
        .estado-programado { background: #fef3c7; color: #d97706; }
        .estado-en-curso { background: #dbeafe; color: #1d4ed8; }
        .estado-completado { background: #dcfce7; color: #16a34a; }
    </style>
</head>
<body>
    <h2>Reporte de Viajes de Vehículos</h2>
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
                <th>ID Viaje</th>
                <th>Fecha/Hora Salida</th>
                <th>Vehículo</th>
                <th>Marca/Modelo</th>
                <th>Estado Vehículo</th>
                <th>Ruta</th>
                <th>Inicio Ruta</th>
                <th>Fin Ruta</th>
                <th>Piloto</th>
                <th>Acompañante</th>
                <th>Tiempo (min)</th>
                <th>Estado Viaje</th>
                <th>Descripción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($viajes_data as $viaje): ?>
            <tr>
                <td class="text-center"><?= $viaje['id_viaje']; ?></td>
                <td><?= date("d/m/Y H:i", strtotime($viaje['fecha_hora_salida'])); ?></td>
                <td><?= htmlspecialchars($viaje['no_placa']); ?></td>
                <td><?= htmlspecialchars($viaje['marca_vehiculo'] . ' ' . $viaje['modelo_vehiculo']); ?></td>
                <td><?= htmlspecialchars($viaje['estado_vehiculo']); ?></td>
                <td><?= htmlspecialchars($viaje['descripcion_ruta']); ?></td>
                <td><?= htmlspecialchars($viaje['inicio_ruta']); ?></td>
                <td><?= htmlspecialchars($viaje['fin_ruta']); ?></td>
                <td><?= htmlspecialchars($viaje['piloto_nombre'] . ' ' . $viaje['piloto_apellido']); ?></td>
                <td><?= !empty($viaje['acompanante_nombre']) ? htmlspecialchars($viaje['acompanante_nombre'] . ' ' . $viaje['acompanante_apellido']) : 'Ninguno'; ?></td>
                <td class="text-center"><?= $viaje['tiempo_aproximado_min'] ? number_format($viaje['tiempo_aproximado_min']) : 'N/A'; ?></td>
                <td class="estado-cell 
                    <?php 
                    switch($viaje['estado_viaje']) {
                        case 'PROGRAMADO': echo 'estado-programado'; break;
                        case 'EN_CURSO': echo 'estado-en-curso'; break;
                        case 'COMPLETADO': echo 'estado-completado'; break;
                    }
                    ?>">
                    <?= $viaje['estado_viaje']; ?>
                </td>
                <td><?= htmlspecialchars($viaje['descripcion_viaje']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (empty($viajes_data)): ?>
    <p>No se encontraron viajes con los filtros aplicados.</p>
    <?php endif; ?>

    <div style="margin-top: 20px; font-size: 12px; color: #666;">
        <p><strong>Resumen Estadístico:</strong></p>
        <p>Total de viajes: <?= $total_viajes; ?></p>
        <p>Vehículos únicos utilizados: <?= $total_vehiculos_unicos; ?></p>
        <p>Pilotos únicos asignados: <?= $total_pilotos_unicos; ?></p>
        <p>Rutas únicas recorridas: <?= $total_rutas_unicas; ?></p>
        <p><strong>Distribución por estado:</strong></p>
        <p>Programados: <?= $estados_count['PROGRAMADO']; ?> (<?= $total_viajes > 0 ? number_format(($estados_count['PROGRAMADO'] / $total_viajes) * 100, 1) : '0'; ?>%)</p>
        <p>En curso: <?= $estados_count['EN_CURSO']; ?> (<?= $total_viajes > 0 ? number_format(($estados_count['EN_CURSO'] / $total_viajes) * 100, 1) : '0'; ?>%)</p>
        <p>Completados: <?= $estados_count['COMPLETADO']; ?> (<?= $total_viajes > 0 ? number_format(($estados_count['COMPLETADO'] / $total_viajes) * 100, 1) : '0'; ?>%)</p>
    </div>
</body>
</html>

<?php
// Limpiar recursos
if (isset($stmt)) $stmt->close();
desconectar($conn);
?>