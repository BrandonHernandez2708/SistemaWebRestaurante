<?php
session_start();
require_once '../conexion.php';

// Verificar sesión
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// ------------------------------
// Validar mes y año recibidos
// ------------------------------
$mes = isset($_GET['mes']) ? intval($_GET['mes']) : 0;
$anio = isset($_GET['anio']) ? intval($_GET['anio']) : 0;

if ($mes <= 0 || $anio <= 0) {
    $_SESSION['mensaje'] = 'Debe seleccionar un período válido.';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: Planilla.php');
    exit();
}

// ------------------------------
// Consulta detalle de planilla
// ------------------------------
$conn = conectar();

$sql = "
SELECT 
    e.id_empleado,
    CONCAT(e.nombre_empleado, ' ', e.apellido_empleado) AS nombre_completo,
    p.puesto,
    p.sueldo_base,
    pl.bonificacion_total,
    pl.penalizacion_total,
    pl.igss_descuento,
    pl.bono_fijo,
    pl.bono_especial,
    pl.sueldo_total
FROM planilla pl
INNER JOIN empleados e ON pl.id_empleado = e.id_empleado
INNER JOIN puesto p ON e.id_puesto = p.id_puesto
WHERE pl.mes_planilla = ? AND pl.anio_planilla = ?
ORDER BY e.apellido_empleado, e.nombre_empleado;
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $mes, $anio);
$stmt->execute();
$res = $stmt->get_result();

$detalles = [];
$total_general = 0;
while ($row = $res->fetch_assoc()) {
    $detalles[] = $row;
    $total_general += $row['sueldo_total'];
}

$stmt->close();
desconectar($conn);

// Mapeo de nombre del mes
$meses = [
    1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
    7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'
];
$nombreMes = $meses[$mes] ?? 'Mes desconocido';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Detalle de Planilla <?= "$nombreMes $anio"; ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/SistemaWebRestaurante/css/bootstrap.min.css">
<link rel="stylesheet" href="/SistemaWebRestaurante/css/diseñoModulos.css">
</head>
<body>
<header class="mb-4">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
        <h1 class="mb-0">Planilla de <?= $nombreMes . ' ' . $anio; ?></h1>
        <ul class="nav nav-pills gap-2 mb-0">
            <li class="nav-item"><a href="Planilla.php" class="nav-link">⬅ Regresar</a></li>
        </ul>
    </div>
</header>

<main class="container my-4">
<?php if (isset($_SESSION['mensaje'])): ?>
<script>
window.__mensaje = {
    text: <?php echo json_encode($_SESSION['mensaje']); ?>,
    tipo: <?php echo json_encode($_SESSION['tipo_mensaje']); ?>
};
</script>
<?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
<?php endif; ?>

<section class="card shadow p-4">
    <h2 class="text-primary mb-4">Detalle por empleado</h2>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Empleado</th>
                    <th>Puesto</th>
                    <th>Sueldo Base</th>
                    <th>Bono Fijo</th>
                    <th>Bono Extra</th>
                    <th>Bonificaciones</th>
                    <th>Penalizaciones</th>
                    <th>IGSS</th>
                    <th>Sueldo Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($detalles)): ?>
                    <?php foreach ($detalles as $fila): ?>
                        <tr>
                            <td><?= $fila['id_empleado']; ?></td>
                            <td><?= htmlspecialchars($fila['nombre_completo']); ?></td>
                            <td><?= htmlspecialchars($fila['puesto']); ?></td>
                            <td>Q <?= number_format($fila['sueldo_base'], 2); ?></td>
                            <td>Q <?= number_format($fila['bono_fijo'], 2); ?></td>
                            <td>Q <?= number_format($fila['bono_especial'], 2); ?></td>
                            <td>Q <?= number_format($fila['bonificacion_total'], 2); ?></td>
                            <td>Q <?= number_format($fila['penalizacion_total'], 2); ?></td>
                            <td>Q <?= number_format($fila['igss_descuento'], 2); ?></td>
                            <td><strong>Q <?= number_format($fila['sueldo_total'], 2); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="10" class="text-center">No hay registros para este período.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="9" class="text-end">Total General:</th>
                    <th><strong>Q <?= number_format($total_general, 2); ?></strong></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="text-end mt-3">
        <a href="Reporte_Planilla.php?mes=<?= $mes; ?>&anio=<?= $anio; ?>" target="_blank" class="btn btn-outline-primary">
            📄 Generar PDF
        </a>
    </div>
</section>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/SistemaWebRestaurante/javascript/Planilla_Detalle.js"></script>
</body>
</html>
