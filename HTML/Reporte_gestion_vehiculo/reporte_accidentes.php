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
$empleado_id = $_GET['empleado'] ?? '';
$viaje_id = $_GET['viaje'] ?? '';

// Cargar vehículos para el filtro
function obtenerVehiculos(): array {
    $c = conectar();
    $sql = "SELECT DISTINCT v.id_vehiculo, v.no_placa, v.marca_vehiculo, v.modelo_vehiculo 
            FROM vehiculos v
            INNER JOIN viajes vi ON v.id_vehiculo = vi.id_vehiculo
            INNER JOIN reportes_accidentes ra ON vi.id_viaje = ra.id_viaje
            ORDER BY v.no_placa";
    $rs = $c->query($sql);
    $rows = $rs ? $rs->fetch_all(MYSQLI_ASSOC) : [];
    desconectar($c);
    return $rows;
}

// Cargar empleados para el filtro
function obtenerEmpleados(): array {
    $c = conectar();
    $sql = "SELECT DISTINCT e.id_empleado, e.nombre_empleado, e.apellido_empleado 
            FROM empleados e
            INNER JOIN reportes_accidentes ra ON e.id_empleado = ra.id_empleado
            ORDER BY e.nombre_empleado, e.apellido_empleado";
    $rs = $c->query($sql);
    $rows = $rs ? $rs->fetch_all(MYSQLI_ASSOC) : [];
    desconectar($c);
    return $rows;
}

// Cargar viajes para el filtro
function obtenerViajes(): array {
    $c = conectar();
    $sql = "SELECT DISTINCT v.id_viaje, v.descripcion_viaje, v.fecha_hora_salida, ve.no_placa
            FROM viajes v
            INNER JOIN vehiculos ve ON v.id_vehiculo = ve.id_vehiculo
            INNER JOIN reportes_accidentes ra ON v.id_viaje = ra.id_viaje
            ORDER BY v.fecha_hora_salida DESC";
    $rs = $c->query($sql);
    $rows = $rs ? $rs->fetch_all(MYSQLI_ASSOC) : [];
    desconectar($c);
    return $rows;
}

// Consulta principal con filtros
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
        ve.id_vehiculo,
        ve.no_placa,
        ve.marca_vehiculo,
        ve.modelo_vehiculo,
        e.id_empleado,
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
$accidentes_data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $accidentes_data[] = $row;
    }
}

// Consultas para resumen
$qTotal = "SELECT COUNT(*) AS total FROM reportes_accidentes ra
           INNER JOIN viajes v ON ra.id_viaje = v.id_viaje
           INNER JOIN vehiculos ve ON v.id_vehiculo = ve.id_vehiculo";
$qVehiculos = "SELECT COUNT(DISTINCT ve.id_vehiculo) AS total FROM reportes_accidentes ra
               INNER JOIN viajes v ON ra.id_viaje = v.id_viaje
               INNER JOIN vehiculos ve ON v.id_vehiculo = ve.id_vehiculo";
$qEmpleados = "SELECT COUNT(DISTINCT ra.id_empleado) AS total FROM reportes_accidentes ra";
$qViajes = "SELECT COUNT(DISTINCT ra.id_viaje) AS total FROM reportes_accidentes ra";

if ($where) {
    $cond = " WHERE " . implode(" AND ", $where);
    $qTotal .= $cond;
    $qVehiculos .= $cond;
    $qEmpleados .= $cond;
    $qViajes .= $cond;
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

    $stmtE = $conn->prepare($qEmpleados);
    $stmtE->bind_param($types, ...$params);
    $stmtE->execute();
    $resE = $stmtE->get_result();

    $stmtVi = $conn->prepare($qViajes);
    $stmtVi->bind_param($types, ...$params);
    $stmtVi->execute();
    $resVi = $stmtVi->get_result();
} else {
    $resT = $conn->query($qTotal);
    $resV = $conn->query($qVehiculos);
    $resE = $conn->query($qEmpleados);
    $resVi = $conn->query($qViajes);
}

$total_accidentes = ($resT && $resT->num_rows) ? (int)$resT->fetch_assoc()['total'] : 0;
$total_vehiculos = ($resV && $resV->num_rows) ? (int)$resV->fetch_assoc()['total'] : 0;
$total_empleados = ($resE && $resE->num_rows) ? (int)$resE->fetch_assoc()['total'] : 0;
$total_viajes = ($resVi && $resVi->num_rows) ? (int)$resVi->fetch_assoc()['total'] : 0;

$vehiculos = obtenerVehiculos();
$empleados = obtenerEmpleados();
$viajes = obtenerViajes();

// Cerrar statements si existen
if (isset($stmt)) $stmt->close();
if (isset($stmtT)) $stmtT->close();
if (isset($stmtV)) $stmtV->close();
if (isset($stmtE)) $stmtE->close();
if (isset($stmtVi)) $stmtVi->close();
desconectar($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Reporte de Accidentes - Marea Roja</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="../../css/Reportes.css">
<style>
.badge-accidente {
    background: #dc2626;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
}
.descripcion-cell {
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.vehiculo-info {
    font-weight: bold;
    color: #1e40af;
}
.empleado-info {
    color: #059669;
}
.piloto-info {
    color: #7c3aed;
    font-size: 0.9em;
}
</style>
</head>
<body>
  <div class="header-full">
    <div class="header-content">
      <h1 class="header-title"><i class="bi bi-exclamation-triangle-fill"></i> Reporte de Accidentes</h1>
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
              <label for="empleado">Empleado que Reporta</label>
              <select id="empleado" name="empleado">
                <option value="">Todos</option>
                <?php foreach ($empleados as $empleado): ?>
                  <option value="<?= (int)$empleado['id_empleado']; ?>" <?= ($empleado_id == $empleado['id_empleado'] ? 'selected' : ''); ?>>
                    <?= htmlspecialchars($empleado['nombre_empleado'] . ' ' . $empleado['apellido_empleado']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="filtro-item">
              <label for="viaje">Viaje</label>
              <select id="viaje" name="viaje">
                <option value="">Todos</option>
                <?php foreach ($viajes as $viaje): ?>
                  <option value="<?= (int)$viaje['id_viaje']; ?>" <?= ($viaje_id == $viaje['id_viaje'] ? 'selected' : ''); ?>>
                    Viaje #<?= $viaje['id_viaje']; ?> - <?= htmlspecialchars($viaje['no_placa']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="botones-filtro">
            <button type="submit" class="btn-buscar"><i class="bi bi-search"></i> Buscar</button>
            <a href="reporte_accidentes.php" class="btn-limpiar"><i class="bi bi-arrow-clockwise"></i> Limpiar</a>
          </div>
        </form>
      </div>
    </div>

    <!-- RESUMEN -->
    <div class="resumen">
      <h2 style="color:#1e40af;"><i class="bi bi-bar-chart-fill"></i> Resumen</h2>
      <div class="resumen-grid">
        <div class="resumen-item"><h3>Total Accidentes</h3><div class="valor"><?= number_format($total_accidentes); ?></div></div>
        <div class="resumen-item"><h3>Vehículos Involucrados</h3><div class="valor"><?= number_format($total_vehiculos); ?></div></div>
        <div class="resumen-item"><h3>Empleados que Reportaron</h3><div class="valor"><?= number_format($total_empleados); ?></div></div>
        <div class="resumen-item"><h3>Viajes Afectados</h3><div class="valor"><?= number_format($total_viajes); ?></div></div>
      </div>
    </div>

    <!-- EXPORTAR -->
    <div class="export-buttons">
      <a href="exportar_excel_accidentes.php?<?= http_build_query($_GET); ?>" class="btn-export">
        <i class="bi bi-file-earmark-excel-fill"></i> Exportar Excel
      </a>
    </div>

    <!-- TABLA -->
    <div class="card">
      <div class="card-body">
        <?php if (!empty($accidentes_data)): ?>
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Fecha/Hora</th>
                <th>Vehículo</th>
                <th>Viaje</th>
                <th>Piloto</th>
                <th>Empleado Reporta</th>
                <th>Descripción</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($accidentes_data as $accidente): ?>
                <tr>
                  <td class="text-center">
                    <span class="badge-accidente">#<?= (int)$accidente['id_accidente']; ?></span>
                  </td>
                  <td>
                    <?php 
                    $fecha = new DateTime($accidente['fecha_hora']);
                    echo $fecha->format('d/m/Y H:i');
                    ?>
                  </td>
                  <td>
                    <span class="vehiculo-info"><?= htmlspecialchars($accidente['no_placa']); ?></span><br>
                    <small class="text-muted"><?= htmlspecialchars($accidente['marca_vehiculo']); ?> <?= htmlspecialchars($accidente['modelo_vehiculo']); ?></small>
                  </td>
                  <td>
                    <strong>Viaje #<?= $accidente['id_viaje']; ?></strong><br>
                    <small class="text-muted"><?= htmlspecialchars($accidente['descripcion_viaje']); ?></small><br>
                    <small class="text-muted">Salida: <?= date("d/m/Y H:i", strtotime($accidente['fecha_hora_salida'])); ?></small>
                  </td>
                  <td>
                    <?php if (!empty($accidente['nombre_piloto'])): ?>
                      <span class="piloto-info"><?= htmlspecialchars($accidente['nombre_piloto'] . ' ' . $accidente['apellido_piloto']); ?></span>
                    <?php else: ?>
                      <span class="text-muted">No asignado</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="empleado-info"><?= htmlspecialchars($accidente['nombre_empleado'] . ' ' . $accidente['apellido_empleado']); ?></span>
                  </td>
                  <td class="descripcion-cell" title="<?= htmlspecialchars($accidente['descripcion_accidente']); ?>">
                    <?= htmlspecialchars($accidente['descripcion_accidente']); ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div style="text-align:center;padding:50px;color:#64748b;">
            <i class="bi bi-exclamation-triangle" style="font-size:60px;display:block;margin-bottom:15px;"></i>
            <h3>No se encontraron accidentes</h3>
            <p>No hay registros con los filtros aplicados.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>