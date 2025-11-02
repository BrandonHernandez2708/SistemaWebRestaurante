<?php
require_once('../conexion.php');

$conexion = conectar();

$cliente_id = $_GET['cliente'] ?? '';
$estado = $_GET['estado'] ?? '';
$fecha_inicio = $_GET['desde'] ?? '';
$fecha_fin = $_GET['hasta'] ?? '';

$where = [];
$params = [];
$types = '';

if ($cliente_id) { $where[] = "r.id_cliente = ?"; $params[] = $cliente_id; $types .= 'i'; }
if ($estado) { $where[] = "r.estado = ?"; $params[] = $estado; $types .= 's'; }
if ($fecha_inicio && $fecha_fin) { $where[] = "DATE(r.fecha_hora) BETWEEN ? AND ?"; $params[] = $fecha_inicio; $params[] = $fecha_fin; $types .= 'ss'; }

$sql = "SELECT r.id_reservacion, CONCAT(c.nombre, ' ', c.apellido) AS cliente, 
               CONCAT('Mesa #', m.id_mesa) AS mesa, r.cantidad_personas, r.fecha_hora, r.estado
        FROM reservaciones r
        INNER JOIN clientes c ON c.id_cliente = r.id_cliente
        INNER JOIN mesas m ON m.id_mesa = r.id_mesa";

if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY r.fecha_hora DESC";

if ($params) {
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $resultados = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result = $conexion->query($sql);
    $resultados = $result->fetch_all(MYSQLI_ASSOC);
}

$total_reservaciones = count($resultados);
$total_programadas = count(array_filter($resultados, fn($r) => $r['estado'] === 'PROGRAMADA'));
$total_cumplidas = count(array_filter($resultados, fn($r) => $r['estado'] === 'CUMPLIDA'));
$total_canceladas = count(array_filter($resultados, fn($r) => $r['estado'] === 'CANCELADA'));

$clientes = $conexion->query("SELECT id_cliente, CONCAT(nombre, ' ', apellido) AS nombre FROM clientes")->fetch_all(MYSQLI_ASSOC);

if (isset($_GET['exportar_excel'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="reporte_reservaciones_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');

    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Cliente</th><th>Mesa</th><th>Personas</th><th>Fecha y Hora</th><th>Estado</th></tr>";
    foreach ($resultados as $r) {
        echo "<tr>
            <td>{$r['id_reservacion']}</td>
            <td>{$r['cliente']}</td>
            <td>{$r['mesa']}</td>
            <td>{$r['cantidad_personas']}</td>
            <td>{$r['fecha_hora']}</td>
            <td>{$r['estado']}</td>
        </tr>";
    }
    echo "</table>";
    exit;
}
?>
