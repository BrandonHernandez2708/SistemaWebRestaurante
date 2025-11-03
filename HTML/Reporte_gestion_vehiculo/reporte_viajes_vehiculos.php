<?php
session_start();
require_once '../conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// Filtros
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';
$vehiculo_id = $_GET['vehiculo'] ?? '';
$ruta_id = $_GET['ruta'] ?? '';
$piloto_id = $_GET['piloto'] ?? '';
$estado_viaje = $_GET['estado'] ?? '';

// Cargar vehículos para el filtro
function obtenerVehiculos(): array {
    $c = conectar();
    $sql = "SELECT id_vehiculo, no_placa, marca_vehiculo, modelo_vehiculo 
            FROM vehiculos 
            ORDER BY no_placa";
    $rs = $c->query($sql);
    $rows = $rs ? $rs->fetch_all(MYSQLI_ASSOC) : [];
    desconectar($c);
    return $rows;
}

// Cargar rutas para el filtro
function obtenerRutas(): array {
    $c = conectar();
    $sql = "SELECT id_ruta, descripcion_ruta, inicio_ruta, fin_ruta 
            FROM rutas 
            ORDER BY descripcion_ruta";
    $rs = $c->query($sql);
    $rows = $rs ? $rs->fetch_all(MYSQLI_ASSOC) : [];
    desconectar($c);
    return $rows;
}

// Cargar pilotos para el filtro
function obtenerPilotos(): array {
    $c = conectar();
    $sql = "SELECT DISTINCT e.id_empleado, e.nombre_empleado, e.apellido_empleado 
            FROM empleados e
            INNER JOIN viajes v ON e.id_empleado = v.id_empleado_piloto
            ORDER BY e.nombre_empleado, e.apellido_empleado";
    $rs = $c->query($sql);
    $rows = $rs ? $rs->fetch_all(MYSQLI_ASSOC) : [];
    desconectar($c);
    return $rows;
}

// Función para determinar el estado del viaje
function determinarEstadoViaje($fechaSalida, $tiempoAproximado) {
    $ahora = new DateTime();
    $fechaSalidaObj = new DateTime($fechaSalida);
    
    // Si la fecha de salida es en el futuro
    if ($fechaSalidaObj > $ahora) {
        return 'PROGRAMADO';
    }
    
    // Calcular fecha estimada de llegada
    if ($tiempoAproximado) {
        $fechaLlegadaEstimada = clone $fechaSalidaObj;
        $fechaLlegadaEstimada->modify("+{$tiempoAproximado} minutes");
        
        if ($ahora > $fechaLlegadaEstimada) {
            return 'COMPLETADO';
        } else {
            return 'EN_CURSO';
        }
    }
    
    // Si no hay tiempo aproximado, usar lógica simple
    $diferenciaHoras = ($ahora->getTimestamp() - $fechaSalidaObj->getTimestamp()) / 3600;
    
    if ($diferenciaHoras > 24) {
        return 'COMPLETADO';
    } else if ($diferenciaHoras > 0) {
        return 'EN_CURSO';
    } else {
        return 'PROGRAMADO';
    }
}

// Consulta principal con filtros
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
        r.id_ruta,
        r.descripcion_ruta,
        r.inicio_ruta,
        r.fin_ruta,
        ve.id_vehiculo,
        ve.no_placa,
        ve.marca_vehiculo,
        ve.modelo_vehiculo,
        ve.estado as estado_vehiculo,
        ep.id_empleado as id_piloto,
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

// Ejecutar consulta principal
if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// Obtener datos para procesar
$viajes_data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Determinar estado del viaje
        $row['estado_viaje'] = determinarEstadoViaje($row['fecha_hora_salida'], $row['tiempo_aproximado_min']);
        $viajes_data[] = $row;
    }
}

// Filtrar por estado si se especificó
if (!empty($estado_viaje)) {
    $viajes_data = array_filter($viajes_data, function($viaje) use ($estado_viaje) {
        return $viaje['estado_viaje'] === $estado_viaje;
    });
}

// Consultas para resumen
$qTotal = "SELECT COUNT(*) AS total FROM viajes v";
$qVehiculos = "SELECT COUNT(DISTINCT v.id_vehiculo) AS total FROM viajes v";
$qPilotos = "SELECT COUNT(DISTINCT v.id_empleado_piloto) AS total FROM viajes v";
$qRutas = "SELECT COUNT(DISTINCT v.id_ruta) AS total FROM viajes v";

if ($where) {
    $cond = " WHERE " . implode(" AND ", $where);
    $qTotal .= $cond;
    $qVehiculos .= $cond;
    $qPilotos .= $cond;
    $qRutas .= $cond;
}

// Ejecutar consultas de resumen
if ($params) {
    $stmtT = $conn->prepare($qTotal);
    $stmtT->bind_param($types, ...$params);
    $stmtT->execute();
    $resT = $stmtT->get_result();

    $stmtV = $conn->prepare($qVehiculos);
    $stmtV->bind_param($types, ...$params);
    $stmtV->execute();
    $resV = $stmtV->get_result();

    $stmtP = $conn->prepare($qPilotos);
    $stmtP->bind_param($types, ...$params);
    $stmtP->execute();
    $resP = $stmtP->get_result();

    $stmtR = $conn->prepare($qRutas);
    $stmtR->bind_param($types, ...$params);
    $stmtR->execute();
    $resR = $stmtR->get_result();
} else {
    $resT = $conn->query($qTotal);
    $resV = $conn->query($qVehiculos);
    $resP = $conn->query($qPilotos);
    $resR = $conn->query($qRutas);
}

$total_viajes = ($resT && $resT->num_rows) ? (int)$resT->fetch_assoc()['total'] : 0;
$total_vehiculos = ($resV && $resV->num_rows) ? (int)$resV->fetch_assoc()['total'] : 0;
$total_pilotos = ($resP && $resP->num_rows) ? (int)$resP->fetch_assoc()['total'] : 0;
$total_rutas = ($resR && $resR->num_rows) ? (int)$resR->fetch_assoc()['total'] : 0;

$vehiculos = obtenerVehiculos();
$rutas = obtenerRutas();
$pilotos = obtenerPilotos();

// Cerrar statements si existen
if (isset($stmt)) $stmt->close();
if (isset($stmtT)) $stmtT->close();
if (isset($stmtV)) $stmtV->close();
if (isset($stmtP)) $stmtP->close();
if (isset($stmtR)) $stmtR->close();
desconectar($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Reporte de Viajes - Marea Roja</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="../../css/Reportes.css">
<style>
.badge-viaje {
    background: #3b82f6;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
}
.estado-viaje {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
    text-align: center;
}
.estado-programado {
    background: #fef3c7;
    color: #d97706;
}
.estado-en-curso {
    background: #dbeafe;
    color: #1d4ed8;
}
.estado-completado {
    background: #dcfce7;
    color: #16a34a;
}
.vehiculo-info {
    font-weight: bold;
    color: #1e40af;
}
.piloto-info {
    color: #059669;
}
.ruta-info {
    color: #7c3aed;
}
.descripcion-cell {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
</head>
<body>
  <div class="header-full">
    <div class="header-content">
      <h1 class="header-title"><i class="bi bi-geo-alt-fill"></i> Reporte de Viajes de Vehículos</h1>
      <a href="../menu_empleados_vista.php" class="nav-link"><i class="bi bi-arrow-left-circle"></i> Regresar al Menú</a>
    </div>
  </div>

  <div class="container">
    <!-- FILTROS -->
    <div class="card">
      <div class="card-body">
        <h2 style="color:#1e40af;"><i class="bi bi-funnel-fill"></i> Filtros de búsqueda</h2>
        <form method="GET">
          <div class="filtro-group">
            <div class="filtro-item">
              <label for="fecha_desde">Fecha Desde</label>
              <input type="date" id="fecha_desde" name="fecha_desde" value="<?= htmlspecialchars($fecha_desde); ?>">
            </div>
            <div class="filtro-item">
              <label for="fecha_hasta">Fecha Hasta</label>
              <input type="date" id="fecha_hasta" name="fecha_hasta" value="<?= htmlspecialchars($fecha_hasta); ?>">
            </div>
            <div class="filtro-item">
              <label for="vehiculo">Vehículo</label>
              <select id="vehiculo" name="vehiculo">
                <option value="">Todos</option>
                <?php foreach ($vehiculos as $vehiculo): ?>
                  <option value="<?= (int)$vehiculo['id_vehiculo']; ?>" <?= ($vehiculo_id == $vehiculo['id_vehiculo'] ? 'selected' : ''); ?>>
                    <?= htmlspecialchars($vehiculo['no_placa']); ?> - <?= htmlspecialchars($vehiculo['marca_vehiculo']); ?> <?= htmlspecialchars($vehiculo['modelo_vehiculo']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="filtro-item">
              <label for="ruta">Ruta</label>
              <select id="ruta" name="ruta">
                <option value="">Todas</option>
                <?php foreach ($rutas as $ruta): ?>
                  <option value="<?= (int)$ruta['id_ruta']; ?>" <?= ($ruta_id == $ruta['id_ruta'] ? 'selected' : ''); ?>>
                    <?= htmlspecialchars($ruta['descripcion_ruta']); ?> (<?= htmlspecialchars($ruta['inicio_ruta']); ?> - <?= htmlspecialchars($ruta['fin_ruta']); ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="filtro-item">
              <label for="piloto">Piloto</label>
              <select id="piloto" name="piloto">
                <option value="">Todos</option>
                <?php foreach ($pilotos as $piloto): ?>
                  <option value="<?= (int)$piloto['id_empleado']; ?>" <?= ($piloto_id == $piloto['id_empleado'] ? 'selected' : ''); ?>>
                    <?= htmlspecialchars($piloto['nombre_empleado'] . ' ' . $piloto['apellido_empleado']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="filtro-item">
              <label for="estado">Estado Viaje</label>
              <select id="estado" name="estado">
                <option value="">Todos</option>
                <option value="PROGRAMADO" <?= ($estado_viaje == 'PROGRAMADO' ? 'selected' : ''); ?>>Programado</option>
                <option value="EN_CURSO" <?= ($estado_viaje == 'EN_CURSO' ? 'selected' : ''); ?>>En Curso</option>
                <option value="COMPLETADO" <?= ($estado_viaje == 'COMPLETADO' ? 'selected' : ''); ?>>Completado</option>
              </select>
            </div>
          </div>

          <div class="botones-filtro">
            <button type="submit" class="btn-buscar"><i class="bi bi-search"></i> Buscar</button>
            <a href="reporte_viajes_vehiculos.php" class="btn-limpiar"><i class="bi bi-arrow-clockwise"></i> Limpiar</a>
          </div>
        </form>
      </div>
    </div>

    <!-- RESUMEN -->
    <div class="resumen">
      <h2 style="color:#1e40af;"><i class="bi bi-bar-chart-fill"></i> Resumen</h2>
      <div class="resumen-grid">
        <div class="resumen-item"><h3>Total Viajes</h3><div class="valor"><?= number_format(count($viajes_data)); ?></div></div>
        <div class="resumen-item"><h3>Vehículos Utilizados</h3><div class="valor"><?= number_format($total_vehiculos); ?></div></div>
        <div class="resumen-item"><h3>Pilotos Asignados</h3><div class="valor"><?= number_format($total_pilotos); ?></div></div>
        <div class="resumen-item"><h3>Rutas Diferentes</h3><div class="valor"><?= number_format($total_rutas); ?></div></div>
      </div>
    </div>

    <!-- EXPORTAR -->
    <div class="export-buttons">
      <a href="exportar_excel_viajes.php?<?= http_build_query($_GET); ?>" class="btn-export">
        <i class="bi bi-file-earmark-excel-fill"></i> Exportar Excel
      </a>
    </div>

    <!-- TABLA -->
    <div class="card">
      <div class="card-body">
        <?php if (!empty($viajes_data)): ?>
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Fecha/Hora Salida</th>
                <th>Vehículo</th>
                <th>Ruta</th>
                <th>Piloto</th>
                <th>Acompañante</th>
                <th>Tiempo (min)</th>
                <th>Estado</th>
                <th>Descripción</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($viajes_data as $viaje): ?>
                <tr>
                  <td class="text-center">
                    <span class="badge-viaje">#<?= (int)$viaje['id_viaje']; ?></span>
                  </td>
                  <td>
                    <?= date("d/m/Y H:i", strtotime($viaje['fecha_hora_salida'])); ?>
                  </td>
                  <td>
                    <span class="vehiculo-info"><?= htmlspecialchars($viaje['no_placa']); ?></span><br>
                    <small class="text-muted"><?= htmlspecialchars($viaje['marca_vehiculo']); ?> <?= htmlspecialchars($viaje['modelo_vehiculo']); ?></small><br>
                    <small class="text-muted">Estado: <?= htmlspecialchars($viaje['estado_vehiculo']); ?></small>
                  </td>
                  <td>
                    <span class="ruta-info"><?= htmlspecialchars($viaje['descripcion_ruta']); ?></span><br>
                    <small class="text-muted"><?= htmlspecialchars($viaje['inicio_ruta']); ?> → <?= htmlspecialchars($viaje['fin_ruta']); ?></small>
                  </td>
                  <td>
                    <span class="piloto-info"><?= htmlspecialchars($viaje['piloto_nombre'] . ' ' . $viaje['piloto_apellido']); ?></span>
                  </td>
                  <td>
                    <?php if (!empty($viaje['acompanante_nombre'])): ?>
                      <?= htmlspecialchars($viaje['acompanante_nombre'] . ' ' . $viaje['acompanante_apellido']); ?>
                    <?php else: ?>
                      <span class="text-muted">Ninguno</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <?= $viaje['tiempo_aproximado_min'] ? number_format($viaje['tiempo_aproximado_min']) : 'N/A'; ?>
                  </td>
                  <td class="text-center">
                    <?php 
                    $estado_class = '';
                    switch($viaje['estado_viaje']) {
                        case 'PROGRAMADO':
                            $estado_class = 'estado-programado';
                            break;
                        case 'EN_CURSO':
                            $estado_class = 'estado-en-curso';
                            break;
                        case 'COMPLETADO':
                            $estado_class = 'estado-completado';
                            break;
                    }
                    ?>
                    <span class="estado-viaje <?= $estado_class; ?>"><?= $viaje['estado_viaje']; ?></span>
                  </td>
                  <td class="descripcion-cell" title="<?= htmlspecialchars($viaje['descripcion_viaje']); ?>">
                    <?= htmlspecialchars($viaje['descripcion_viaje']); ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div style="text-align:center;padding:50px;color:#64748b;">
            <i class="bi bi-geo-alt" style="font-size:60px;display:block;margin-bottom:15px;"></i>
            <h3>No se encontraron viajes</h3>
            <p>No hay registros con los filtros aplicados.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>