<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../conexion.php';
$conn = conectar();

// Filtros
$fecha_desde  = $_GET['fecha_desde'] ?? '';
$fecha_hasta  = $_GET['fecha_hasta'] ?? '';
$proveedor_id = $_GET['proveedor'] ?? '';
$id_compra    = $_GET['id_compra'] ?? '';

// Headers Excel
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="reporte_compras_insumos_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// WHERE dinámico
$where = [];
$types = '';
$params = [];

if ($fecha_desde !== '')  { $where[] = "c.fecha_compra >= ?";         $types .= 's'; $params[] = $fecha_desde; }
if ($fecha_hasta !== '')   { $where[] = "c.fecha_compra <= ?";         $types .= 's'; $params[] = $fecha_hasta; }
if ($proveedor_id !== '')  { $where[] = "c.id_proveedor = ?";          $types .= 'i'; $params[] = (int)$proveedor_id; }
if ($id_compra !== '')     { $where[] = "c.id_compra_insumo = ?";      $types .= 'i'; $params[] = (int)$id_compra; }

$sql = "
    SELECT 
        c.id_compra_insumo,
        c.fecha_compra,
        p.nombre_proveedor,
        p.telefono_proveedor,
        p.correo_proveedor,
        c.monto_total AS monto_total_compra,
        i.id_insumo,
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

// Ejecutar consulta principal
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
    $qMonto  .= $cond;
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
$monto_total   = ($resM && $resM->num_rows) ? (float)$resM->fetch_assoc()['total'] : 0.0;
$promedio      = $total_compras > 0 ? ($monto_total / $total_compras) : 0.0;

// Render Excel (HTML)
echo "<html><head><meta charset='UTF-8'>
<style>
table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
th { background-color: #1e40af; color: #fff; padding: 10px; text-align: left; }
td { border: 1px solid #ddd; padding: 8px; }
.title { font-size: 18px; font-weight: bold; margin-bottom: 10px; color: #1e40af; }
.subtitle { font-size: 14px; color: #555; margin-bottom: 12px; }
.compra-header { background-color: #f1f5f9; font-weight: bold; }
.compra-total { background-color: #1e293b; color: #fff; font-weight: bold; }
.text-right { text-align: right; }
.text-center { text-align: center; }
.small { font-size: 12px; color: #666; }
</style>
</head><body>";

echo "<div class='title'>REPORTE DE COMPRAS DE INSUMOS - MAREA ROJA</div>";
echo "<div class='subtitle'>Generado: " . date('d/m/Y H:i:s') . "</div>";

// Filtros aplicados
$filtros = [];
if ($fecha_desde !== '')  $filtros[] = "Desde: " . htmlspecialchars($fecha_desde);
if ($fecha_hasta !== '')  $filtros[] = "Hasta: " . htmlspecialchars($fecha_hasta);
if ($proveedor_id !== '') {
    $provRes = $conn->query("SELECT nombre_proveedor FROM proveedores WHERE id_proveedor = " . (int)$proveedor_id);
    $provName = ($provRes && $provRes->num_rows) ? $provRes->fetch_assoc()['nombre_proveedor'] : '';
    $filtros[] = "Proveedor: " . htmlspecialchars($provName);
}
if ($id_compra !== '')    $filtros[] = "ID Compra: " . (int)$id_compra;

if ($filtros) echo "<div class='subtitle'>Filtros: " . implode(" | ", $filtros) . "</div>";

// Tabla principal
echo "<table border='1'>";
echo "<tr>
        <th>ID Compra</th>
        <th>Fecha</th>
        <th>Proveedor</th>
        <th>Insumo</th>
        <th>Cantidad</th>
        <th>Costo Unitario</th>
        <th>Costo Total</th>
      </tr>";

if ($result && $result->num_rows > 0) {
    $current = null;
    $compra_total   = 0.0;
    $total_general  = 0.0;

    while ($row = $result->fetch_assoc()) {
        $idCompra  = (int)$row['id_compra_insumo'];
        $fecha     = htmlspecialchars($row['fecha_compra']);
        $prov      = htmlspecialchars($row['nombre_proveedor']);
        $tel       = htmlspecialchars($row['telefono_proveedor']);
        $mail      = htmlspecialchars($row['correo_proveedor']);
        $insumo    = htmlspecialchars($row['nombre_insumo']);
        $cant      = (float)$row['cantidad_compra'];
        $cUnit     = (float)$row['costo_unitario'];
        $cTotal    = (float)$row['costo_total'];
        $montoComp = (float)$row['monto_total_compra'];

        if ($current !== $idCompra) {
            if ($current !== null) {
                echo "<tr class='compra-total'>
                        <td colspan='6' class='text-right'><strong>Total Compra:</strong></td>
                        <td class='text-right'><strong>Q" . number_format($compra_total, 2) . "</strong></td>
                      </tr>";
                $total_general += $compra_total;
            }
            $current = $idCompra;
            $compra_total = 0.0;

            echo "<tr class='compra-header'>
                    <td><strong>#{$idCompra}</strong></td>
                    <td><strong>" . date('d/m/Y', strtotime($fecha)) . "</strong></td>
                    <td colspan='2'>
                        <strong>{$prov}</strong><br>
                        <span class='small'>Tel: {$tel} | Email: {$mail}</span>
                    </td>
                    <td></td><td></td>
                    <td class='text-right'><strong>Q" . number_format($montoComp, 2) . "</strong></td>
                 </tr>";
        }

        $compra_total += $cTotal;

        echo "<tr>
                <td></td><td></td><td></td>
                <td>{$insumo}</td>
                <td class='text-right'>" . number_format($cant, 0, '', '') . "</td>
                <td class='text-right'>Q" . number_format($cUnit, 4, '.', '') . "</td>
                <td class='text-right'>Q" . number_format($cTotal, 2, '.', '') . "</td>
              </tr>";
    }

    // Último subtotal y total general
    if ($current !== null) {
        echo "<tr class='compra-total'>
                <td colspan='6' class='text-right'><strong>Total Compra:</strong></td>
                <td class='text-right'><strong>Q" . number_format($compra_total, 2) . "</strong></td>
              </tr>";
        $total_general += $compra_total;
    }

    echo "<tr style='background:#059669;color:#fff;font-weight:bold;'>
            <td colspan='6' class='text-right'><strong>TOTAL GENERAL:</strong></td>
            <td class='text-right'><strong>Q" . number_format($total_general, 2) . "</strong></td>
          </tr>";
} else {
    echo "<tr><td colspan='7' class='text-center'>No se encontraron registros</td></tr>";
}
echo "</table>";

// Resumen estadístico
echo "<br><div class='title'>RESUMEN ESTADÍSTICO</div>";
echo "<table border='1' style='width:50%'>
<tr><th>Concepto</th><th>Valor</th></tr>
<tr><td>Total de Compras</td><td>" . number_format($total_compras) . "</td></tr>
<tr><td>Monto Total</td><td>Q" . number_format($monto_total, 2) . "</td></tr>
<tr><td>Promedio por Compra</td><td>Q" . number_format($promedio, 2) . "</td></tr>
</table>";

echo "</body></html>";

// Cerrar recursos
if (isset($stmt))  $stmt->close();
if (isset($stmtT)) $stmtT->close();
if (isset($stmtM)) $stmtM->close();
desconectar($conn);
exit;
