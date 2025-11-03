<?php
session_start();
require_once '../conexion.php';

// proteger acceso
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// Filtros
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';
$proveedor_id = $_GET['proveedor'] ?? '';

// Cargar proveedores
function obtenerProveedores(): array {
    $c = conectar();
    $rs = $c->query("SELECT id_proveedor, nombre_proveedor FROM proveedores ORDER BY nombre_proveedor");
    $rows = $rs ? $rs->fetch_all(MYSQLI_ASSOC) : [];
    desconectar($c);
    return $rows;
}

// Consulta principal con filtros usando JOINS
$conn = conectar();
$where = [];
$types = '';
$params = [];

if (!empty($fecha_desde)) { 
    $where[] = "cm.fecha_de_compra >= ?"; 
    $types .= 's'; 
    $params[] = $fecha_desde; 
}
if (!empty($fecha_hasta)) { 
    $where[] = "cm.fecha_de_compra <= ?"; 
    $types .= 's'; 
    $params[] = $fecha_hasta; 
}
if (!empty($proveedor_id)) { 
    $where[] = "cm.id_proveedor = ?"; 
    $types .= 'i'; 
    $params[] = (int)$proveedor_id; 
}

$sql = "
    SELECT 
        cm.id_compra_mobiliario,
        cm.fecha_de_compra,
        cm.monto_total_compra_q AS monto_total_compra,
        p.nombre_proveedor,
        dcm.id_mobiliario,
        im.nombre_mobiliario,
        tm.descripcion AS tipo_mobiliario,
        dcm.cantidad_de_compra,
        dcm.costo_unitario,
        dcm.monto_total_de_mobiliario AS costo_total
    FROM compras_mobiliario cm
    INNER JOIN proveedores p ON p.id_proveedor = cm.id_proveedor
    INNER JOIN detalle_compra_mobiliario dcm ON dcm.id_compra_mobiliario = cm.id_compra_mobiliario
    INNER JOIN inventario_mobiliario im ON im.id_mobiliario = dcm.id_mobiliario
    LEFT JOIN tipos_mobiliario tm ON tm.id_tipo_mobiliario = im.id_tipo_mobiliario
";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY cm.id_compra_mobiliario DESC, cm.fecha_de_compra DESC, im.nombre_mobiliario ASC";

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
$compras_data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $compra_id = $row['id_compra_mobiliario'];
        if (!isset($compras_data[$compra_id])) {
            $compras_data[$compra_id] = [
                'fecha' => $row['fecha_de_compra'],
                'proveedor' => $row['nombre_proveedor'],
                'monto_total_compra' => $row['monto_total_compra'],
                'detalles' => []
            ];
        }
        $compras_data[$compra_id]['detalles'][] = $row;
    }
}

// Consultas para resumen
$qTotal = "SELECT COUNT(DISTINCT cm.id_compra_mobiliario) AS total FROM compras_mobiliario cm";
$qMonto = "SELECT COALESCE(SUM(cm.monto_total_compra_q), 0) AS total FROM compras_mobiliario cm";

if ($where) {
    $cond = " WHERE " . implode(" AND ", $where);
    $qTotal .= $cond;
    $qMonto .= $cond;
}

// Ejecutar consultas de resumen
if ($params) {
    $stmtT = $conn->prepare($qTotal);
    $stmtT->bind_param($types, ...$params);
    $stmtT->execute();
    $resT = $stmtT->get_result();

    $stmtM = $conn->prepare($qMonto);
    $stmtM->bind_param($types, ...$params);
    $stmtM->execute();
    $resM = $stmtM->get_result();
} else {
    $resT = $conn->query($qTotal);
    $resM = $conn->query($qMonto);
}

$total_compras = ($resT && $resT->num_rows) ? (int)$resT->fetch_assoc()['total'] : 0;
$monto_total = ($resM && $resM->num_rows) ? (float)$resM->fetch_assoc()['total'] : 0.0;
$promedio = $total_compras > 0 ? ($monto_total / $total_compras) : 0.0;

$proveedores = obtenerProveedores();

// Cerrar statements si existen
if (isset($stmt)) $stmt->close();
if (isset($stmtT)) $stmtT->close();
if (isset($stmtM)) $stmtM->close();
desconectar($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Reporte Compras de Mobiliario - Marea Roja</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="../../css/Reportes.css">
</head>
<body>
  <div class="header-full">
    <div class="header-content">
      <h1 class="header-title"><i class="bi bi-chair-fill"></i> Reporte de Compras de Mobiliario</h1>
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
              <label for="proveedor">Proveedor</label>
              <select id="proveedor" name="proveedor">
                <option value="">Todos</option>
                <?php foreach ($proveedores as $prov): ?>
                  <option value="<?= (int)$prov['id_proveedor']; ?>" <?= ($proveedor_id == $prov['id_proveedor'] ? 'selected' : ''); ?>>
                    <?= htmlspecialchars($prov['nombre_proveedor']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="botones-filtro">
            <button type="submit" class="btn-buscar"><i class="bi bi-search"></i> Buscar</button>
            <a href="reporte_compras_mobiliario.php" class="btn-limpiar"><i class="bi bi-arrow-clockwise"></i> Limpiar</a>
          </div>
        </form>
      </div>
    </div>

    <!-- RESUMEN -->
    <div class="resumen">
      <h2 style="color:#1e40af;"><i class="bi bi-bar-chart-fill"></i> Resumen</h2>
      <div class="resumen-grid">
        <div class="resumen-item"><h3>Total Compras</h3><div class="valor"><?= number_format($total_compras); ?></div></div>
        <div class="resumen-item"><h3>Monto Total</h3><div class="valor">Q<?= number_format($monto_total, 2); ?></div></div>
        <div class="resumen-item"><h3>Promedio por Compra</h3><div class="valor">Q<?= number_format($promedio, 2); ?></div></div>
      </div>
    </div>

    <!-- EXPORTAR -->
    <div class="export-buttons">
      <a href="exportar_excel_compras_mobiliario.php?<?= http_build_query($_GET); ?>" class="btn-export">
        <i class="bi bi-file-earmark-excel-fill"></i> Exportar Excel
      </a>
    </div>

    <!-- TABLA -->
    <div class="card">
      <div class="card-body">
        <?php if (!empty($compras_data)): ?>
          <table>
            <thead>
              <tr>
                <th>ID Compra</th>
                <th>Fecha</th>
                <th>Proveedor</th>
                <th>Mobiliario</th>
                <th>Tipo</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Costo Unitario</th>
                <th class="text-right">Costo Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($compras_data as $compra_id => $compra): ?>
                <?php 
                $compra_detalle_total = 0;
                foreach ($compra['detalles'] as $detalle) {
                    $compra_detalle_total += (float)$detalle['costo_total'];
                }
                ?>
                <tr class="compra-header">
                  <td><strong>#<?= (int)$compra_id; ?></strong></td>
                  <td><strong><?= date("d/m/Y", strtotime($compra['fecha'])); ?></strong></td>
                  <td colspan="2"><strong><?= htmlspecialchars($compra['proveedor']); ?></strong></td>
                  <td colspan="4" class="text-right">
                    <strong class="monto-alto">Total Compra: Q<?= number_format((float)$compra['monto_total_compra'], 2); ?></strong>
                  </td>
                </tr>
                
                <?php foreach ($compra['detalles'] as $detalle): ?>
                <tr class="detalle-row">
                  <td></td>
                  <td></td>
                  <td></td>
                  <td><?= htmlspecialchars($detalle['nombre_mobiliario']); ?></td>
                  <td><?= htmlspecialchars($detalle['tipo_mobiliario'] ?? 'N/A'); ?></td>
                  <td class="text-right"><?= number_format((float)$detalle['cantidad_de_compra'], 0); ?></td>
                  <td class="text-right">Q<?= number_format((float)$detalle['costo_unitario'], 2); ?></td>
                  <td class="text-right">Q<?= number_format((float)$detalle['costo_total'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
                
                <tr class="separator">
                  <td colspan="8"></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div style="text-align:center;padding:50px;color:#64748b;">
            <i class="bi bi-inbox" style="font-size:60px;display:block;margin-bottom:15px;"></i>
            <h3>No se encontraron compras</h3>
            <p>No hay registros con los filtros aplicados.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>