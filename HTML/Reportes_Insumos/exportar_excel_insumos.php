<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}
require_once '../conexion.php';
$conn = conectar();

// filtros GET
$q = $_GET['q'] ?? '';
$stock_min = $_GET['stock_min'] ?? '';
$stock_max = $_GET['stock_max'] ?? '';

// headers Excel
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="lista_insumos_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// WHERE
$where = [];
$types = '';
$params = [];

if ($q !== '') {
    $where[] = "(i.insumo LIKE ? OR i.descripcion LIKE ?)";
    $types .= 'ss';
    $like = "%{$q}%";
    $params[] = $like;
    $params[] = $like;
}
if ($stock_min !== '' && is_numeric($stock_min)) {
    $where[] = "i.stock >= ?";
    $types .= 'i';
    $params[] = (int)$stock_min;
}
if ($stock_max !== '' && is_numeric($stock_max)) {
    $where[] = "i.stock <= ?";
    $types .= 'i';
    $params[] = (int)$stock_max;
}

$sql = "SELECT i.id_insumo, i.insumo, i.descripcion, i.stock
        FROM inventario_insumos i";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY i.insumo ASC";

// ejecutar
if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query($sql);
}

// render Excel
echo "<html><head><meta charset='UTF-8'>
<style>
table{border-collapse:collapse;width:100%;font-family:Arial,sans-serif}
th{background:#1e40af;color:#fff;padding:8px;text-align:left}
td{border:1px solid #ddd;padding:8px}
.title{font-size:18px;font-weight:bold;color:#1e40af;margin:0 0 8px}
.subtitle{font-size:13px;color:#444;margin:0 0 12px}
.text-right{text-align:right}
</style>
</head><body>";

echo "<div class='title'>LISTA DE INSUMOS - MAREA ROJA</div>";
echo "<div class='subtitle'>Generado: " . date('d/m/Y H:i:s') . "</div>";

// filtros
$filtros = [];
if ($q !== '') $filtros[] = "Búsqueda: " . htmlspecialchars($q);
if ($stock_min !== '' && is_numeric($stock_min)) $filtros[] = "Stock ≥ " . (int)$stock_min;
if ($stock_max !== '' && is_numeric($stock_max)) $filtros[] = "Stock ≤ " . (int)$stock_max;
if ($filtros) echo "<div class='subtitle'>Filtros: " . implode(" | ", $filtros) . "</div>";

// tabla
echo "<table border='1'>";
echo "<tr><th style='width:90px'>ID</th><th>Insumo</th><th>Descripción</th><th style='width:140px' class='text-right'>Stock</th></tr>";

$total_items = 0;
$total_stock = 0;

if ($res && $res->num_rows > 0) {
    while ($r = $res->fetch_assoc()) {
        $total_items++;
        $total_stock += (int)$r['stock'];
        echo "<tr>
                <td>" . htmlspecialchars($r['id_insumo']) . "</td>
                <td>" . htmlspecialchars($r['insumo']) . "</td>
                <td>" . htmlspecialchars($r['descripcion'] ?: 'Sin descripción') . "</td>
                <td class='text-right'>" . number_format((int)$r['stock'], 0, '', '') . "</td>
             </tr>";
    }
} else {
    echo "<tr><td colspan='4' class='text-center'>No se encontraron insumos</td></tr>";
}

// resumen
echo "<tr style='background:#f1f5f9;font-weight:bold'>
        <td colspan='3' class='text-right'>Total de Insumos:</td>
        <td class='text-right'>" . number_format($total_items) . "</td>
      </tr>";
echo "<tr style='background:#e2e8f0;font-weight:bold'>
        <td colspan='3' class='text-right'>Stock Acumulado:</td>
        <td class='text-right'>" . number_format($total_stock, 0, '', '') . "</td>
      </tr>";
echo "</table>";

echo "</body></html>";

// limpiar
if (isset($stmt)) $stmt->close();
desconectar($conn);
exit;
