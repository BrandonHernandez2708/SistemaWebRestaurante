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
    $where_conditions[] = "o.fecha_orden >= ?";
    $params[] = $fecha_desde;
    $types .= 's';
}

if (!empty($fecha_hasta)) {
    $where_conditions[] = "o.fecha_orden <= ?";
    $params[] = $fecha_hasta;
    $types .= 's';
}

$query = "
    SELECT 
        o.id_orden,
        o.fecha_orden,
        o.descripcion,
        o.total,
        m.id_mesa,
        m.descripcion as mesa,
        p.nombre_plato,
        b.descripcion as nombre_bebida,
        do.cantidad,
        do.subtotal,
        (SELECT COUNT(*) FROM facturas f WHERE f.id_orden = o.id_orden) as tiene_factura
    FROM orden o
    LEFT JOIN mesas m ON o.id_mesa = m.id_mesa
    LEFT JOIN detalle_orden do ON o.id_orden = do.id_orden
    LEFT JOIN platos p ON do.id_plato = p.id_plato
    LEFT JOIN bebidas b ON do.id_bebida = b.id_bebida
";

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$query .= " ORDER BY o.fecha_orden DESC, o.id_orden DESC";

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
header('Content-Disposition: attachment; filename="reporte_ordenes_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Crear contenido Excel
echo "<table border='1'>";
echo "<tr>";
echo "<th>ID Orden</th>";
echo "<th>Fecha</th>";
echo "<th>Mesa</th>";
echo "<th>Descripción</th>";
echo "<th>Producto</th>";
echo "<th>Cantidad</th>";
echo "<th>Subtotal</th>";
echo "<th>Estado</th>";
echo "</tr>";

$current_orden = null;
while ($row = $result->fetch_assoc()) {
    if ($current_orden != $row['id_orden']) {
        $current_orden = $row['id_orden'];
        // Fila principal de la orden
        echo "<tr>";
        echo "<td>" . $row['id_orden'] . "</td>";
        echo "<td>" . $row['fecha_orden'] . "</td>";
        echo "<td>Mesa " . $row['id_mesa'] . " - " . $row['mesa'] . "</td>";
        echo "<td>" . $row['descripcion'] . "</td>";
        echo "<td></td>";
        echo "<td></td>";
        echo "<td>Q" . number_format($row['total'], 2) . "</td>";
        echo "<td>" . ($row['tiene_factura'] > 0 ? 'Facturada' : 'Pendiente') . "</td>";
        echo "</tr>";
    }
    
    // Detalles de la orden
    echo "<tr>";
    echo "<td></td>";
    echo "<td></td>";
    echo "<td></td>";
    echo "<td></td>";
    echo "<td>" . ($row['nombre_plato'] ?: $row['nombre_bebida']) . "</td>";
    echo "<td>" . $row['cantidad'] . "</td>";
    echo "<td>Q" . number_format($row['subtotal'], 2) . "</td>";
    echo "<td></td>";
    echo "</tr>";
}

echo "</table>";

desconectar($conexion);
exit();
?>