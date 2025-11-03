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
$proveedor_id = $_GET['proveedor'] ?? '';

// Configurar headers para descarga Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="reporte_compras_mobiliario_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Consulta con los mismos filtros
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

// Generar Excel
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #1e40af; color: white; font-weight: bold; }
        .compra-header { background-color: #e2e8f0; font-weight: bold; }
        .total-row { background-color: #f1f5f9; font-weight: bold; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h2>Reporte de Compras de Mobiliario</h2>
    <p>Generado: <?= date('d/m/Y H:i:s'); ?></p>
    
    <?php if (!empty($fecha_desde) || !empty($fecha_hasta) || !empty($proveedor_id)): ?>
    <p><strong>Filtros aplicados:</strong>
        <?php 
        $filtros = [];
        if (!empty($fecha_desde)) $filtros[] = "Desde: " . date('d/m/Y', strtotime($fecha_desde));
        if (!empty($fecha_hasta)) $filtros[] = "Hasta: " . date('d/m/Y', strtotime($fecha_hasta));
        if (!empty($proveedor_id)) {
            // Obtener nombre del proveedor
            $sql_prov = "SELECT nombre_proveedor FROM proveedores WHERE id_proveedor = ?";
            $stmt_prov = $conn->prepare($sql_prov);
            $stmt_prov->bind_param("i", $proveedor_id);
            $stmt_prov->execute();
            $result_prov = $stmt_prov->get_result();
            $proveedor_nombre = $result_prov->fetch_assoc()['nombre_proveedor'] ?? 'Desconocido';
            $filtros[] = "Proveedor: " . $proveedor_nombre;
            $stmt_prov->close();
        }
        echo implode(' | ', $filtros);
        ?>
    </p>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID Compra</th>
                <th>Fecha</th>
                <th>Proveedor</th>
                <th>Mobiliario</th>
                <th>Tipo</th>
                <th>Cantidad</th>
                <th>Costo Unitario (Q)</th>
                <th>Costo Total (Q)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $gran_total = 0;
            foreach ($compras_data as $compra_id => $compra): 
                $gran_total += (float)$compra['monto_total_compra'];
            ?>
                <tr class="compra-header">
                    <td>#<?= $compra_id; ?></td>
                    <td><?= date("d/m/Y", strtotime($compra['fecha'])); ?></td>
                    <td colspan="3"><?= htmlspecialchars($compra['proveedor']); ?></td>
                    <td colspan="3" class="text-right">Total Compra: Q<?= number_format((float)$compra['monto_total_compra'], 2); ?></td>
                </tr>
                
                <?php foreach ($compra['detalles'] as $detalle): ?>
                <tr>
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
                
            <?php endforeach; ?>
            
            <?php if (!empty($compras_data)): ?>
            <tr class="total-row">
                <td colspan="7" class="text-right"><strong>TOTAL GENERAL:</strong></td>
                <td class="text-right"><strong>Q<?= number_format($gran_total, 2); ?></strong></td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (empty($compras_data)): ?>
    <p>No se encontraron compras con los filtros aplicados.</p>
    <?php endif; ?>
</body>
</html>

<?php
// Limpiar recursos
if (isset($stmt)) $stmt->close();
desconectar($conn);
?>