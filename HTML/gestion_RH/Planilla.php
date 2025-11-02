<?php
session_start();
require_once '../conexion.php';

// Verificar sesión
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// -----------------------------------
// GENERAR PLANILLA MENSUAL
// -----------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['operacion']) && $_POST['operacion'] === 'generar') {
    generarPlanilla();
}

// -----------------------------------
// FUNCIONES
// -----------------------------------

function generarPlanilla() {
    $conn = conectar();
    $mes = intval($_POST['mes_planilla'] ?? 0);
    $anio = intval($_POST['anio_planilla'] ?? 0);

    if ($mes <= 0 || $anio <= 0) {
        $_SESSION['mensaje'] = 'Debe seleccionar mes y año válidos.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Planilla.php');
        exit();
    }

    // 1️⃣ Verificar si ya existe planilla para ese período
    $check = $conn->prepare("SELECT COUNT(*) AS total FROM planilla WHERE mes_planilla=? AND anio_planilla=?");
    $check->bind_param('ii', $mes, $anio);
    $check->execute();
    $total = $check->get_result()->fetch_assoc()['total'] ?? 0;
    $check->close();

    if ($total > 0) {
        $_SESSION['mensaje'] = "Ya existe una planilla generada para $mes/$anio.";
        $_SESSION['tipo_mensaje'] = 'warning';
        header('Location: Planilla.php');
        exit();
    }

    // 2️⃣ Insertar cálculos automáticos
    $sql = "
    INSERT INTO planilla (id_empleado, id_puesto, sueldo_base_mes, bonificacion_total, penalizacion_total, igss_descuento, bono_fijo, bono_especial, sueldo_total, mes_planilla, anio_planilla)
    SELECT 
        e.id_empleado,
        p.id_puesto,
        p.sueldo_base AS sueldo_base_mes,
        IFNULL(SUM(b.monto_bonificacion), 0) AS bonificacion_total,
        IFNULL(SUM(pe.descuento_penalizacion), 0) AS penalizacion_total,
        ROUND(p.sueldo_base * 0.0483, 2) AS igss_descuento,
        250.00 AS bono_fijo,
        CASE 
            WHEN ? = 7 THEN p.sueldo_base   -- Bono 14
            WHEN ? = 12 THEN p.sueldo_base  -- Aguinaldo
            ELSE 0.00
        END AS bono_especial,
        ROUND(
            (p.sueldo_base 
             + IFNULL(SUM(b.monto_bonificacion),0)
             + 250.00 
             + CASE WHEN ?=7 OR ?=12 THEN p.sueldo_base ELSE 0 END)
            - (IFNULL(SUM(pe.descuento_penalizacion),0) + (p.sueldo_base * 0.0483)),
        2) AS sueldo_total,
        ? AS mes_planilla,
        ? AS anio_planilla
    FROM empleados e
    INNER JOIN puesto p ON e.id_puesto = p.id_puesto
    LEFT JOIN bonificaciones b 
        ON e.id_empleado = b.id_empleado 
        AND MONTH(b.fecha_bonificacion)=? AND YEAR(b.fecha_bonificacion)=?
    LEFT JOIN penalizaciones pe 
        ON e.id_empleado = pe.id_empleado 
        AND MONTH(pe.fecha_penalizacion)=? AND YEAR(pe.fecha_penalizacion)=?
    GROUP BY e.id_empleado, p.id_puesto, p.sueldo_base;
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iiiiiiiiii', $mes, $mes, $mes, $mes, $mes, $anio, $mes, $anio, $mes, $anio);
    $stmt->execute();

    $filas = $stmt->affected_rows;
    $stmt->close();
    desconectar($conn);

    if ($filas > 0) {
        $_SESSION['mensaje'] = "Planilla generada exitosamente ($filas empleados).";
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = "No se generó la planilla. Verifique los datos.";
        $_SESSION['tipo_mensaje'] = 'error';
    }

    header('Location: Planilla.php');
    exit();
}

// -----------------------------------
// MOSTRAR PLANILLAS EXISTENTES (Resumen histórico)
// -----------------------------------
$conn = conectar();
$sql = "
SELECT 
    mes_planilla,
    anio_planilla,
    DATE(MIN(fecha_generacion)) AS fecha_generacion,
    COUNT(DISTINCT id_empleado) AS total_empleados,
    ROUND(SUM(sueldo_total), 2) AS total_pagar
FROM planilla
GROUP BY mes_planilla, anio_planilla
ORDER BY anio_planilla DESC, mes_planilla DESC;
";
$result = $conn->query($sql);
$planillas = [];
while ($row = $result->fetch_assoc()) {
    $planillas[] = $row;
}
desconectar($conn);

// ----------------------- HTML -----------------------
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Planillas</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/SistemaWebRestaurante/css/bootstrap.min.css">
<link rel="stylesheet" href="/SistemaWebRestaurante/css/diseñoModulos.css">
</head>
<body>
<header class="mb-4">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
        <h1 class="mb-0">Gestión de Planillas</h1>
        <ul class="nav nav-pills gap-2 mb-0">
            <li class="nav-item"><a href="../menu_empleados.php" class="nav-link">Regresar al Menú</a></li>
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
    <h2 class="text-primary mb-3">Generar nueva planilla</h2>
    <form method="post" id="form-planilla" class="row g-3">
        <input type="hidden" name="operacion" value="generar">
        <div class="col-md-4">
            <label class="form-label">Mes</label>
            <select name="mes_planilla" id="mes_planilla" class="form-select" required>
                <option value="">-- Seleccionar mes --</option>
                <?php 
                $meses = [
                    1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
                    7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'
                ];
                foreach ($meses as $num=>$nombre)
                    echo "<option value='$num'>$nombre</option>";
                ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Año</label>
            <input type="number" name="anio_planilla" id="anio_planilla" class="form-control" min="2020" max="2100" required>
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <button type="submit" id="btn-generar" class="btn btn-success w-100">Generar Planilla</button>
        </div>
    </form>
</section>

<section class="card shadow p-4 mt-5">
    <h3 class="text-primary mb-3">Histórico de planillas generadas</h3>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Mes</th>
                    <th>Año</th>
                    <th>Fecha de Generación</th>
                    <th>Total Empleados</th>
                    <th>Total a Pagar</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($planillas)): ?>
                    <?php foreach ($planillas as $pl): ?>
                        <tr>
                            <td><?= $meses[$pl['mes_planilla']]; ?></td>
                            <td><?= $pl['anio_planilla']; ?></td>
                            <td><?= $pl['fecha_generacion']; ?></td>
                            <td><?= $pl['total_empleados']; ?></td>
                            <td>Q <?= number_format($pl['total_pagar'],2); ?></td>
                            <td>
                                <a href="Planilla_Detalle.php?mes=<?= $pl['mes_planilla']; ?>&anio=<?= $pl['anio_planilla']; ?>" 
                                   class="btn btn-primary btn-sm">Ver Detalle</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center">No hay planillas generadas</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

</main>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/SistemaWebRestaurante/javascript/Planilla.js"></script>
</body>
</html>
