<?php
require_once('../conexion.php');
$conexion = conectar();

$busqueda = trim($_GET['busqueda'] ?? '');

// --- FILTRO AVANZADO ---
$where = [];
$params = [];
$types = '';

if ($busqueda !== '') {
    $where[] = "(
        c.nombre LIKE ? OR 
        c.apellido LIKE ? OR 
        CONCAT(c.nombre, ' ', c.apellido) LIKE ? OR
        c.nit LIKE ? OR 
        c.telefono LIKE ? OR 
        c.correo LIKE ?
    )";

    $texto = "%$busqueda%";
    $params = [$texto, $texto, $texto, $texto, $texto, $texto];
    $types = str_repeat('s', 6);
}

$sql = "SELECT c.id_cliente,
               CONCAT(c.nombre, ' ', COALESCE(c.apellido, '')) AS nombre_completo,
               c.nit, c.telefono, c.correo
        FROM clientes c";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY c.nombre ASC";

if ($params) {
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $clientes = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result = $conexion->query($sql);
    $clientes = $result->fetch_all(MYSQLI_ASSOC);
}

$total_clientes = count($clientes);
$total_general = $conexion->query("SELECT COUNT(*) AS total FROM clientes")->fetch_assoc()['total'];

// --- EXPORTAR A EXCEL ---
if (isset($_GET['exportar_excel'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="reporte_clientes_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');

    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nombre Completo</th><th>NIT</th><th>Teléfono</th><th>Correo</th></tr>";
    foreach ($clientes as $c) {
        echo "<tr>
            <td>{$c['id_cliente']}</td>
            <td>{$c['nombre_completo']}</td>
            <td>{$c['nit']}</td>
            <td>{$c['telefono']}</td>
            <td>{$c['correo']}</td>
        </tr>";
    }
    echo "</table>";
    exit;
}
?>
