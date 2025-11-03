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
$taller_id = $_GET['taller'] ?? '';

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

// Cargar talleres para el filtro
function obtenerTalleres(): array {
    $c = conectar();
    $sql = "SELECT id_taller, nombre_taller FROM talleres ORDER BY nombre_taller";
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
        v.id_vehiculo,
        v.no_placa,
        v.marca_vehiculo,
        v.modelo_vehiculo,
        v.estado,
        t.id_taller,
        t.nombre_taller
    FROM mantenimiento_vehiculo mv
    INNER JOIN vehiculos v ON mv.id_vehiculo = v.id_vehiculo
    INNER JOIN talleres t ON mv.id_taller = t.id_taller
";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY mv.fecha_mantenimiento DESC, v.no_placa ASC";

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
$mantenimientos_data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $mantenimientos_data[] = $row;
    }
}

// Consultas para resumen
$qTotal = "SELECT COUNT(*) AS total FROM mantenimiento_vehiculo mv";
$qCosto = "SELECT COALESCE(SUM(mv.costo_mantenimiento), 0) AS total FROM mantenimiento_vehiculo mv";
$qPromedio = "SELECT COALESCE(AVG(mv.costo_mantenimiento), 0) AS promedio FROM mantenimiento_vehiculo mv";
$qVehiculos = "SELECT COUNT(DISTINCT mv.id_vehiculo) AS total FROM mantenimiento_vehiculo mv";

if ($where) {
    $cond = " WHERE " . implode(" AND ", $where);
    $qTotal .= $cond;
    $qCosto .= $cond;
    $qPromedio .= $cond;
    $qVehiculos .= $cond;
}

// Ejecutar consultas de resumen
if ($params) {
    $stmtT = $conn->prepare($qTotal);
    $stmtT->bind_param($types, ...$params);
    $stmtT->execute();
    $resT = $stmtT->get_result();

    $stmtC = $conn->prepare($qCosto);
    $stmtC->bind_param($types, ...$params);
    $stmtC->execute();
    $resC = $stmtC->get_result();

    $stmtP = $conn->prepare($qPromedio);
    $stmtP->bind_param($types, ...$params);
    $stmtP->execute();
    $resP = $stmtP->get_result();

    $stmtV = $conn->prepare($qVehiculos);
    $stmtV->bind_param($types, ...$params);
    $stmtV->execute();
    $resV = $stmtV->get_result();
} else {
    $resT = $conn->query($qTotal);
    $resC = $conn->query($qCosto);
    $resP = $conn->query($qPromedio);
    $resV = $conn->query($qVehiculos);
}

$total_mantenimientos = ($resT && $resT->num_rows) ? (int)$resT->fetch_assoc()['total'] : 0;
$costo_total = ($resC && $resC->num_rows) ? (float)$resC->fetch_assoc()['total'] : 0.0;
$costo_promedio = ($resP && $resP->num_rows) ? (float)$resP->fetch_assoc()['promedio'] : 0.0;
$total_vehiculos = ($resV && $resV->num_rows) ? (int)$resV->fetch_assoc()['total'] : 0;

$vehiculos = obtenerVehiculos();
$talleres = obtenerTalleres();

// Cerrar statements si existen
if (isset($stmt)) $stmt->close();
if (isset($stmtT)) $stmtT->close();
if (isset($stmtC)) $stmtC->close();
if (isset($stmtP)) $stmtP->close();
if (isset($stmtV)) $stmtV->close();
desconectar($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Reporte Mantenimiento de Vehículos - Marea Roja</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="../../css/Reportes.css">
<style>
.badge-mantenimiento {
    background: #dc2626;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
}
.estado-vehiculo {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
}
.estado-en-taller {
    background: #fef3c7;
    color: #d97706;
}
.estado-disponible {
    background: #dcfce7;
    color: #16a34a;
}
.estado-mantenimiento {
    background: #f3e8ff;
    color: #9333ea;
}
.descripcion-cell {
    max-width: 250px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
</head>
<body>
  <div class="header-full">
    <div class="header-content">
      <h1 class="header-title"><i class="bi bi-truck"></i> Reporte de Mantenimiento de Vehículos</h1>
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
              <label for="taller">Taller</label>
              <select id="taller" name="taller">
                <option value="">Todos</option>
                <?php foreach ($talleres as $taller): ?>
                  <option value="<?= (int)$taller['id_taller']; ?>" <?= ($taller_id == $taller['id_taller'] ? 'selected' : ''); ?>>
                    <?= htmlspecialchars($taller['nombre_taller']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="botones-filtro">
            <button type="submit" class="btn-buscar"><i class="bi bi-search"></i> Buscar</button>
            <a href="reporte_mantenimiento_vehiculos.php" class="btn-limpiar"><i class="bi bi-arrow-clockwise"></i> Limpiar</a>
          </div>
        </form>
      </div>
    </div>

    <!-- RESUMEN -->
    <div class="resumen">
      <h2 style="color:#1e40af;"><i class="bi bi-bar-chart-fill"></i> Resumen</h2>
      <div class="resumen-grid">
        <div class="resumen-item"><h3>Total Mantenimientos</h3><div class="valor"><?= number_format($total_mantenimientos); ?></div></div>
        <div class="resumen-item"><h3>Vehículos Atendidos</h3><div class="valor"><?= number_format($total_vehiculos); ?></div></div>
        <div class="resumen-item"><h3>Costo Total</h3><div class="valor">Q<?= number_format($costo_total, 2); ?></div></div>
        <div class="resumen-item"><h3>Costo Promedio</h3><div class="valor">Q<?= number_format($costo_promedio, 2); ?></div></div>
      </div>
    </div>

    <!-- EXPORTAR -->
    <div class="export-buttons">
      <a href="exportar_excel_mantenimiento_vehiculos.php?<?= http_build_query($_GET); ?>" class="btn-export">
        <i class="bi bi-file-earmark-excel-fill"></i> Exportar Excel
      </a>
    </div>

    <!-- TABLA -->
    <div class="card">
      <div class="card-body">
        <?php if (!empty($mantenimientos_data)): ?>
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Vehículo</th>
                <th>Estado</th>
                <th>Taller</th>
                <th>Descripción</th>
                <th class="text-right">Costo (Q)</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($mantenimientos_data as $mantenimiento): ?>
                <tr>
                  <td class="text-center">
                    <span class="badge-mantenimiento">#<?= (int)$mantenimiento['id_mantenimiento']; ?></span>
                  </td>
                  <td><?= date("d/m/Y", strtotime($mantenimiento['fecha_mantenimiento'])); ?></td>
                  <td>
                    <strong><?= htmlspecialchars($mantenimiento['no_placa']); ?></strong><br>
                    <small class="text-muted"><?= htmlspecialchars($mantenimiento['marca_vehiculo']); ?> <?= htmlspecialchars($mantenimiento['modelo_vehiculo']); ?></small>
                  </td>
                  <td class="text-center">
                    <?php 
                    $estado_class = '';
                    switch($mantenimiento['estado']) {
                        case 'EN_TALLER':
                            $estado_class = 'estado-en-taller';
                            $estado_text = 'EN TALLER';
                            break;
                        case 'DISPONIBLE':
                            $estado_class = 'estado-disponible';
                            $estado_text = 'DISPONIBLE';
                            break;
                        case 'MANTENIMIENTO':
                            $estado_class = 'estado-mantenimiento';
                            $estado_text = 'MANTENIMIENTO';
                            break;
                        default:
                            $estado_class = 'estado-disponible';
                            $estado_text = $mantenimiento['estado'];
                    }
                    ?>
                    <span class="estado-vehiculo <?= $estado_class; ?>"><?= $estado_text; ?></span>
                  </td>
                  <td><?= htmlspecialchars($mantenimiento['nombre_taller']); ?></td>
                  <td class="descripcion-cell" title="<?= htmlspecialchars($mantenimiento['descripcion_mantenimiento']); ?>">
                    <?= htmlspecialchars($mantenimiento['descripcion_mantenimiento']); ?>
                  </td>
                  <td class="text-right fw-bold">Q<?= number_format((float)$mantenimiento['costo_mantenimiento'], 2); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr class="total-footer">
                <td colspan="6" class="text-right"><strong>TOTAL GENERAL:</strong></td>
                <td class="text-right"><strong>Q<?= number_format($costo_total, 2); ?></strong></td>
              </tr>
            </tfoot>
          </table>
        <?php else: ?>
          <div style="text-align:center;padding:50px;color:#64748b;">
            <i class="bi bi-truck" style="font-size:60px;display:block;margin-bottom:15px;"></i>
            <h3>No se encontraron mantenimientos</h3>
            <p>No hay registros con los filtros aplicados.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>