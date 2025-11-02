<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

include('../conexion.php');
$conexion = conectar();

// Obtener parámetros de filtro
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';

// Construir consulta con filtros
$where_conditions = [];
$params = [];
$types = '';

if (!empty($fecha_desde)) {
    $where_conditions[] = "f.fecha_emision >= ?";
    $params[] = $fecha_desde;
    $types .= 's';
}

if (!empty($fecha_hasta)) {
    $where_conditions[] = "f.fecha_emision <= ?";
    $params[] = $fecha_hasta;
    $types .= 's';
}

$query = "
    SELECT 
        f.id_factura,
        f.codigo_serie,
        f.fecha_emision,
        f.nit_cliente,
        f.monto_total_q,
        CONCAT(c.nombre, ' ', c.apellido) as cliente,
        o.id_orden,
        o.id_mesa,
        m.descripcion as mesa,
        tc.tipo_cobro,
        dc.monto_detalle_q,
        p.nombre_plato,
        b.descripcion as nombre_bebida,
        do.cantidad,
        do.subtotal
    FROM facturas f
    LEFT JOIN clientes c ON f.id_cliente = c.id_cliente
    LEFT JOIN orden o ON f.id_orden = o.id_orden
    LEFT JOIN mesas m ON o.id_mesa = m.id_mesa
    LEFT JOIN detalle_cobro dc ON f.id_factura = dc.id_factura
    LEFT JOIN tipos_cobro tc ON dc.id_tipo_cobro = tc.id_tipo_cobro
    LEFT JOIN detalle_orden do ON o.id_orden = do.id_orden
    LEFT JOIN platos p ON do.id_plato = p.id_plato
    LEFT JOIN bebidas b ON do.id_bebida = b.id_bebida
";

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$query .= " ORDER BY f.fecha_emision DESC, f.id_factura DESC";

if (!empty($params)) {
    $stmt = $conexion->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conexion->query($query);
}

// Configurar headers para descarga Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="reporte_facturas_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Crear contenido Excel
echo "<table border='1'>";
echo "<tr>";
echo "<th>ID Factura</th>";
echo "<th>Código Serie</th>";
echo "<th>Fecha Emisión</th>";
echo "<th>Cliente</th>";
echo "<th>NIT</th>";
echo "<th>Orden</th>";
echo "<th>Mesa</th>";
echo "<th>Producto</th>";
echo "<th>Cantidad</th>";
echo "<th>Método Cobro</th>";
echo "<th>Monto</th>";
echo "</tr>";

$current_factura = null;
while ($row = $result->fetch_assoc()) {
    if ($current_factura != $row['id_factura']) {
        $current_factura = $row['id_factura'];
        // Fila principal de la factura
        echo "<tr>";
        echo "<td>" . $row['id_factura'] . "</td>";
        echo "<td>" . $row['codigo_serie'] . "</td>";
        echo "<td>" . $row['fecha_emision'] . "</td>";
        echo "<td>" . $row['cliente'] . "</td>";
        echo "<td>" . $row['nit_cliente'] . "</td>";
        echo "<td>" . $row['id_orden'] . "</td>";
        echo "<td>" . $row['mesa'] . "</td>";
        echo "<td></td>";
        echo "<td></td>";
        echo "<td></td>";
        echo "<td>Q" . number_format($row['monto_total_q'], 2) . "</td>";
        echo "</tr>";
    }
    
    // Detalles de la factura
    echo "<tr>";
    echo "<td></td>";
    echo "<td></td>";
    echo "<td></td>";
    echo "<td></td>";
    echo "<td></td>";
    echo "<td></td>";
    echo "<td></td>";
    echo "<td>" . ($row['nombre_plato'] ?: $row['nombre_bebida']) . "</td>";
    echo "<td>" . $row['cantidad'] . "</td>";
    echo "<td>" . $row['tipo_cobro'] . "</td>";
    echo "<td>Q" . number_format($row['subtotal'], 2) . "</td>";
    echo "</tr>";
}

echo "</table>";

desconectar($conexion);
exit();
?>