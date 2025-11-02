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
$id_compra = $_GET['id_compra'] ?? '';

// Cargar proveedores
function obtenerProveedores(): array {
    $c = conectar();
    $rs = $c->query("SELECT id_proveedor, nombre_proveedor FROM proveedores ORDER BY nombre_proveedor");
    $rows = $rs ? $rs->fetch_all(MYSQLI_ASSOC) : [];
    desconectar($c);
    return $rows;
}

// Consulta principal con filtros
$conn = conectar();
$where = [];
$types = '';
$params = [];

if (!empty($fecha_desde)) { $where[] = "c.fecha_compra >= ?"; $types .= 's'; $params[] = $fecha_desde; }
if (!empty($fecha_hasta)) { $where[] = "c.fecha_compra <= ?"; $types .= 's'; $params[] = $fecha_hasta; }
if (!empty($proveedor_id)) { $where[] = "c.id_proveedor = ?"; $types .= 'i'; $params[] = (int)$proveedor_id; }
if (!empty($id_compra)) { $where[] = "c.id_compra_insumo = ?"; $types .= 'i'; $params[] = (int)$id_compra; }

$sql = "
    SELECT 
        c.id_compra_insumo,
        c.fecha_compra,
        c.monto_total AS monto_total_compra,
        p.nombre_proveedor,
        d.id_insumo,
        i.insumo AS nombre_insumo,
        d.cantidad_compra,
        d.costo_unitario,
        d.costo_total
    FROM compras_insumos c
    INNER JOIN proveedores p ON p.id_proveedor = c.id_proveedor
    INNER JOIN detalle_compra_insumo d ON d.id_compra_insumo = c.id_compra_insumo
    INNER JOIN inventario_insumos i ON i.id_insumo = d.id_insumo
";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY c.fecha_compra DESC, c.id_compra_insumo DESC, i.insumo ASC";

if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// Resumen
$qTotal = "SELECT COUNT(*) AS total FROM compras_insumos c";
$qMonto = "SELECT SUM(c.monto_total) AS total FROM compras_insumos c";
if ($where) {
    $cond = " WHERE " . implode(" AND ", $where);
    $qTotal .= $cond;
    $qMonto .= $cond;
}
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Detalle Compras de Insumos - Marea Roja</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="../../css/Reportes.css">
</head>
<body>
  <div class="header-full">
    <div class="header-content">
      <h1 class="header-title"><i class="bi bi-basket2-fill"></i> Detalle de Compras de Insumos</h1>
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
            <div class="filtro-item">
              <label for="id_compra">ID Compra</label>
              <input type="number" id="id_compra" name="id_compra" placeholder="Ej. 15" value="<?= htmlspecialchars($id_compra); ?>">
            </div>
          </div>

          <div class="botones-filtro">
            <button type="submit" class="btn-buscar"><i class="bi bi-search"></i> Buscar</button>
            <a href="detalle_compras_insumos.php" class="btn-limpiar"><i class="bi bi-arrow-clockwise"></i> Limpiar</a>
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
      <a href="exportar_excel_compras_insumos.php?<?= http_build_query($_GET); ?>" class="btn-export">
        <i class="bi bi-file-earmark-excel-fill"></i> Exportar Excel
      </a>
    </div>

    <!-- TABLA -->
    <div class="card">
      <div class="card-body">
        <?php if ($result && $result->num_rows > 0): ?>
          <table>
            <thead>
              <tr>
                <th>ID Compra</th>
                <th>Fecha</th>
                <th>Proveedor</th>
                <th>Insumo</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Costo Unitario</th>
                <th class="text-right">Costo Total</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $current_compra = null;
              $compra_total = 0.0;
              while ($row = $result->fetch_assoc()):
                if ($current_compra != $row['id_compra_insumo']):
                  if ($current_compra !== null): ?>
                    <tr class="compra-total">
                      <td colspan="6" class="text-right">Total Compra:</td>
                      <td class="text-right">Q<?= number_format($compra_total, 2); ?></td>
                    </tr>
                  <?php endif;
                  $current_compra = $row['id_compra_insumo'];
                  $compra_total = 0.0; ?>
                  <tr class="compra-header">
                    <td>#<?= (int)$row['id_compra_insumo']; ?></td>
                    <td><?= date("d/m/Y", strtotime($row['fecha_compra'])); ?></td>
                    <td colspan="3"><strong><?= htmlspecialchars($row['nombre_proveedor']); ?></strong></td>
                    <td colspan="2" class="text-right"><strong class="monto-alto">Q<?= number_format((float)$row['monto_total_compra'], 2); ?></strong></td>
                  </tr>
                <?php endif;
                $compra_total += (float)$row['costo_total']; ?>
                <tr>
                  <td></td><td></td><td></td>
                  <td><?= htmlspecialchars($row['nombre_insumo']); ?></td>
                  <td class="text-right"><?= number_format((float)$row['cantidad_compra'], 0, '', ''); ?></td>
                  <td class="text-right">Q<?= number_format((float)$row['costo_unitario'], 4, '.', ''); ?></td>
                  <td class="text-right">Q<?= number_format((float)$row['costo_total'], 2, '.', ''); ?></td>
                </tr>
              <?php endwhile; ?>
              <tr class="compra-total"><td colspan="6" class="text-right">Total Compra:</td><td class="text-right">Q<?= number_format($compra_total, 2); ?></td></tr>
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

<?php
if (isset($stmt)) $stmt->close();
if (isset($stmtT)) $stmtT->close();
if (isset($stmtM)) $stmtM->close();
desconectar($conn);
?>
</body>
</html>
