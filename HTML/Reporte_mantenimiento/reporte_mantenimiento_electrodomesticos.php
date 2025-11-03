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
$electrodomestico_id = $_GET['electrodomestico'] ?? '';
$taller_id = $_GET['taller'] ?? '';

// Cargar electrodomésticos para el filtro
function obtenerElectrodomesticos(): array {
    $c = conectar();
    $sql = "SELECT im.id_mobiliario, im.nombre_mobiliario, tm.descripcion as tipo_mobiliario
            FROM inventario_mobiliario im
            LEFT JOIN tipos_mobiliario tm ON im.id_tipo_mobiliario = tm.id_tipo_mobiliario
            WHERE tm.descripcion LIKE '%electrodoméstico%' OR tm.descripcion LIKE '%electrodomestico%' 
                   OR im.nombre_mobiliario LIKE '%refrigerador%' OR im.nombre_mobiliario LIKE '%cocina%' 
                   OR im.nombre_mobiliario LIKE '%microondas%' OR im.nombre_mobiliario LIKE '%licuadora%'
                   OR im.nombre_mobiliario LIKE '%lavadora%' OR im.nombre_mobiliario LIKE '%secadora%'
                   OR im.nombre_mobiliario LIKE '%horno%' OR im.nombre_mobiliario LIKE '%televisor%'
                   OR im.nombre_mobiliario LIKE '%aire%' OR im.nombre_mobiliario LIKE '%ventilador%'
            ORDER BY im.nombre_mobiliario";
    $rs = $c->query($sql);
    $rows = $rs ? $rs->fetch_all(MYSQLI_ASSOC) : [];
    desconectar($c);
    return $rows;
}

// Cargar talleres para el filtro
function obtenerTalleres(): array {
    $c = conectar();
    $sql = "SELECT id_taller, nombre_taller 
            FROM talleres 
            WHERE (nombre_taller LIKE '%electrodoméstico%' OR nombre_taller LIKE '%electrodomestico%'
                   OR nombre_taller LIKE '%línea blanca%' OR nombre_taller LIKE '%linea blanca%'
                   OR nombre_taller LIKE '%electrónica%' OR nombre_taller LIKE '%electronica%'
                   OR nombre_taller LIKE '%refrigeración%' OR nombre_taller LIKE '%refrigeracion%'
                   OR nombre_taller LIKE '%reparación%' OR nombre_taller LIKE '%reparacion%'
                   OR nombre_taller LIKE '%servicio técnico%' OR nombre_taller LIKE '%tecnico%')
            ORDER BY nombre_taller";
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
    $where[] = "me.id_taller = ?"; 
    $types .= 'i'; 
    $params[] = (int)$taller_id; 
}

$sql = "
    SELECT 
        me.id_mantenimiento_elect,
        me.fecha_mantenimiento,
        me.descripcion_mantenimiento,
        me.costo_mantenimiento_q,
        im.id_mobiliario,
        im.nombre_mobiliario,
        tm.descripcion AS tipo_mobiliario,
        t.id_taller,
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
$qTotal = "SELECT COUNT(*) AS total FROM mantenimiento_electrodomesticos me";
$qCosto = "SELECT COALESCE(SUM(me.costo_mantenimiento_q), 0) AS total FROM mantenimiento_electrodomesticos me";
$qPromedio = "SELECT COALESCE(AVG(me.costo_mantenimiento_q), 0) AS promedio FROM mantenimiento_electrodomesticos me";

if ($where) {
    $cond = " WHERE " . implode(" AND ", $where);
    $qTotal .= $cond;
    $qCosto .= $cond;
    $qPromedio .= $cond;
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
} else {
    $resT = $conn->query($qTotal);
    $resC = $conn->query($qCosto);
    $resP = $conn->query($qPromedio);
}

$total_mantenimientos = ($resT && $resT->num_rows) ? (int)$resT->fetch_assoc()['total'] : 0;
$costo_total = ($resC && $resC->num_rows) ? (float)$resC->fetch_assoc()['total'] : 0.0;
$costo_promedio = ($resP && $resP->num_rows) ? (float)$resP->fetch_assoc()['promedio'] : 0.0;

$electrodomesticos = obtenerElectrodomesticos();
$talleres = obtenerTalleres();

// Cerrar statements si existen
if (isset($stmt)) $stmt->close();
if (isset($stmtT)) $stmtT->close();
if (isset($stmtC)) $stmtC->close();
if (isset($stmtP)) $stmtP->close();
desconectar($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Reporte Mantenimiento Electrodomésticos - Marea Roja</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="../../css/Reportes.css">
</head>
<body>
  <div class="header-full">
    <div class="header-content">
      <h1 class="header-title"><i class="bi bi-tools"></i> Reporte de Mantenimiento de Electrodomésticos</h1>
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
              <label for="electrodomestico">Electrodoméstico</label>
              <select id="electrodomestico" name="electrodomestico">
                <option value="">Todos</option>
                <?php foreach ($electrodomesticos as $electro): ?>
                  <option value="<?= (int)$electro['id_mobiliario']; ?>" <?= ($electrodomestico_id == $electro['id_mobiliario'] ? 'selected' : ''); ?>>
                    <?= htmlspecialchars($electro['nombre_mobiliario']); ?> - <?= htmlspecialchars($electro['tipo_mobiliario']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="filtro-item">
              <label for="taller">Taller</label>
              <select id="taller" name="taller">
                <option value="">Todos</option>
                <option value="interno" <?= ($taller_id == 'interno' ? 'selected' : ''); ?>>Mantenimiento Interno</option>
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
            <a href="reporte_mantenimiento_electrodomesticos.php" class="btn-limpiar"><i class="bi bi-arrow-clockwise"></i> Limpiar</a>
          </div>
        </form>
      </div>
    </div>

    <!-- RESUMEN -->
    <div class="resumen">
      <h2 style="color:#1e40af;"><i class="bi bi-bar-chart-fill"></i> Resumen</h2>
      <div class="resumen-grid">
        <div class="resumen-item"><h3>Total Mantenimientos</h3><div class="valor"><?= number_format($total_mantenimientos); ?></div></div>
        <div class="resumen-item"><h3>Costo Total</h3><div class="valor">Q<?= number_format($costo_total, 2); ?></div></div>
        <div class="resumen-item"><h3>Costo Promedio</h3><div class="valor">Q<?= number_format($costo_promedio, 2); ?></div></div>
      </div>
    </div>

    <!-- EXPORTAR -->
    <div class="export-buttons">
      <a href="exportar_excel_mantenimiento_electrodomesticos.php?<?= http_build_query($_GET); ?>" class="btn-export">
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
                <th>Electrodoméstico</th>
                <th>Tipo</th>
                <th>Descripción</th>
                <th>Taller</th>
                <th class="text-right">Costo (Q)</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($mantenimientos_data as $mantenimiento): ?>
                <tr>
                  <td class="text-center">
                    <span class="badge-electrodomestico">#<?= (int)$mantenimiento['id_mantenimiento_elect']; ?></span>
                  </td>
                  <td><?= date("d/m/Y", strtotime($mantenimiento['fecha_mantenimiento'])); ?></td>
                  <td><strong><?= htmlspecialchars($mantenimiento['nombre_mobiliario']); ?></strong></td>
                  <td><?= htmlspecialchars($mantenimiento['tipo_mobiliario'] ?? 'N/A'); ?></td>
                  <td class="descripcion-cell"><?= htmlspecialchars($mantenimiento['descripcion_mantenimiento']); ?></td>
                  <td>
                    <?php if (!empty($mantenimiento['nombre_taller'])): ?>
                      <?= htmlspecialchars($mantenimiento['nombre_taller']); ?>
                      <?php if (!empty($mantenimiento['telefono_taller'])): ?>
                        <br><small class="text-muted">Tel: <?= htmlspecialchars($mantenimiento['telefono_taller']); ?></small>
                      <?php endif; ?>
                    <?php else: ?>
                      <span class="text-muted">Mantenimiento Interno</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-right fw-bold">Q<?= number_format((float)$mantenimiento['costo_mantenimiento_q'], 2); ?></td>
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
            <i class="bi bi-tools" style="font-size:60px;display:block;margin-bottom:15px;"></i>
            <h3>No se encontraron mantenimientos</h3>
            <p>No hay registros con los filtros aplicados.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>