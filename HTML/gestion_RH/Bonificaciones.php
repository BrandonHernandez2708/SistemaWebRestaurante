<?php
// Ernesto David Samayoa Jocol 0901-22-3415
session_start();
require_once '../conexion.php';
require_once '../funciones_globales.php';


// Obtener puesto y sueldo base por empleado
if (isset($_GET['action']) && $_GET['action'] === 'infoEmpleado' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $conn = conectar();
    $sql = "SELECT p.puesto, p.sueldo_base 
            FROM empleados e 
            INNER JOIN puesto p ON e.id_puesto = p.id_puesto
            WHERE e.id_empleado = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc() ?: [];
    desconectar($conn);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit();
}


if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operacion = $_POST['operacion'] ?? '';
    switch ($operacion) {
        case 'crear': crearBonificacion(); break;
        case 'actualizar': actualizarBonificacion(); break;
        case 'eliminar': eliminarBonificacion(); break;
    }
}


function crearBonificacion() {
    $conn = conectar();
    $id_empleado = $_POST['id_empleado'] ?? '';
    $fecha = $_POST['fecha_bonificacion'] ?? '';
    $horas = floatval($_POST['horas_extras'] ?? 0);
    $pago = floatval($_POST['pago_por_hora'] ?? 0.00);

    if ($id_empleado === '' || $fecha === '' || $horas <= 0 || $pago <= 0) {
        $_SESSION['mensaje'] = 'Todos los campos son requeridos y los valores deben ser mayores a 0.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Bonificaciones.php');
        exit();
    }

    // NOTA: 'monto_bonificacion' es una columna generada en la BD; no se debe asignar explícitamente.
    $sql = "INSERT INTO bonificaciones (id_empleado, fecha_bonificacion, horas_extras, pago_por_hora)
        VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isdd', $id_empleado, $fecha, $horas, $pago);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        registrarBitacora(
            $conn,
            'Bonificaciones',
            'insertar',
            "Horas extras registradas (Empleado ID: $id_empleado, Fecha: $fecha, Horas: $horas, PagoHora: $pago)"
        );
        $_SESSION['mensaje'] = 'Horas extras registradas correctamente.';
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'Error al registrar.';
        $_SESSION['tipo_mensaje'] = 'error';
    }

    $stmt->close();
    desconectar($conn);
    header('Location: Bonificaciones.php');
    exit();
}

function actualizarBonificacion() {
    $conn = conectar();
    $id_bonificacion = $_POST['id_bonificacion'] ?? '';
    $id_empleado = $_POST['id_empleado'] ?? '';
    $fecha = $_POST['fecha_bonificacion'] ?? '';
    $horas = floatval($_POST['horas_extras'] ?? 0);
    $pago = floatval($_POST['pago_por_hora'] ?? 0.00);

    if ($id_bonificacion === '' || $id_empleado === '' || $fecha === '' || $horas <= 0 || $pago <= 0) {
        $_SESSION['mensaje'] = 'Debe llenar todos los campos correctamente.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Bonificaciones.php');
        exit();
    }

    // 'monto_bonificacion' es generado; sólo actualizamos las columnas base
    $sql = "UPDATE bonificaciones 
        SET id_empleado=?, fecha_bonificacion=?, horas_extras=?, pago_por_hora=? 
        WHERE id_bonificacion=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isddi', $id_empleado, $fecha, $horas, $pago, $id_bonificacion);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        registrarBitacora(
            $conn,
            'Bonificaciones',
            'Actualizar',
            "Bonificación actualizada (ID: $id_bonificacion, Empleado ID: $id_empleado, Fecha: $fecha, Horas: $horas, PagoHora: $pago)"
        );
        $_SESSION['mensaje'] = 'Registro actualizado correctamente.';
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'No se realizaron cambios.';
        $_SESSION['tipo_mensaje'] = 'info';
    }

    $stmt->close();
    desconectar($conn);
    header('Location: Bonificaciones.php');
    exit();
}

function eliminarBonificacion() {
    $conn = conectar();
    $id_bonificacion = $_POST['id_bonificacion'] ?? '';
    $sql = "DELETE FROM bonificaciones WHERE id_bonificacion=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_bonificacion);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        registrarBitacora(
            $conn,
            'Bonificaciones',
            'Eliminar',
            "Bonificación eliminada (ID: $id_bonificacion)"
        );
        $_SESSION['mensaje'] = 'Registro eliminado correctamente.';
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'Error al eliminar.';
        $_SESSION['tipo_mensaje'] = 'error';
    }

    $stmt->close();
    desconectar($conn);
    header('Location: Bonificaciones.php');
    exit();
}

$conn = conectar();
$empleados_map = [];
$res = $conn->query("SELECT id_empleado, CONCAT(nombre_empleado, ' ', apellido_empleado) AS nombre_completo FROM empleados");
while ($row = $res->fetch_assoc()) $empleados_map[$row['id_empleado']] = $row['nombre_completo'];
desconectar($conn);

$conn = conectar();
$sql = "SELECT b.id_bonificacion, b.id_empleado, CONCAT(e.nombre_empleado, ' ', e.apellido_empleado) AS nombre_empleado,
               b.fecha_bonificacion, b.horas_extras, b.pago_por_hora, b.monto_bonificacion
        FROM bonificaciones b
        INNER JOIN empleados e ON b.id_empleado = e.id_empleado
        ORDER BY b.fecha_bonificacion DESC";
$res = $conn->query($sql);
$bonificaciones = [];
while ($r = $res->fetch_assoc()) $bonificaciones[] = $r;
desconectar($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Bonificaciones (Horas Extras)</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/SistemaWebRestaurante/css/bootstrap.min.css">
<link rel="stylesheet" href="/SistemaWebRestaurante/css/diseñoModulos.css">
</head>
<body>
<header class="mb-4">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
        <h1 class="mb-0">Recursos Humanos RH</h1>
        <ul class="nav nav-pills gap-2 mb-0">
            <li class="nav-item">
                <a href="../menu_empleados.php" class="btn-back" aria-label="Regresar al menú principal">
                    <span class="arrow">←</span><span>Regresar al Menú</span>
                </a>
            </li>
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
<h2 class="text-primary mb-4">Formulario de Bonificaciones (Horas Extras)</h2>

<form id="form-bonificacion" method="post" class="row g-3">
    <input type="hidden" name="operacion" id="operacion" value="crear">
    <input type="hidden" name="id_bonificacion" id="id_bonificacion">

    <div class="col-md-4">
        <label class="form-label">👤Empleado</label>
        <select name="id_empleado" id="id_empleado" class="form-select" required>
            <option value="">-- Seleccionar empleado --</option>
            <?php foreach ($empleados_map as $id => $nombre): ?>
                <option value="<?= $id; ?>"><?= $id . ' - ' . htmlspecialchars($nombre); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">👔Puesto</label>
        <input type="text" class="form-control" id="puesto_empleado" readonly>
    </div>

    <div class="col-md-2">
        <label class="form-label">💰Sueldo Base (Q)</label>
        <input type="text" class="form-control" id="sueldo_base" readonly>
    </div>

    <div class="col-md-2">
        <label class="form-label">📅Fecha</label>
        <input type="date" class="form-control" name="fecha_bonificacion" id="fecha_bonificacion" required>
    </div>

    <div class="col-md-2">
        <label class="form-label">🕒Horas Extras</label>
        <input type="number" class="form-control" name="horas_extras" id="horas_extras" min="0.01" step="0.01" required>
    </div>

    <div class="col-md-2">
        <label class="form-label">💵 Pago por Hora (Q)</label>
        <input type="number" class="form-control" name="pago_por_hora" id="pago_por_hora" step="0.01" min="0.01" required>
    </div>

    <div class="col-md-2">
        <label class="form-label">Total (Q)</label>
        <input type="text" class="form-control" id="monto_bonificacion" readonly>
    </div>

    <div class="d-flex gap-2 mt-3">
        <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
        <button id="btn-guardar" type="submit" class="btn btn-success">Guardar</button>
        <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
        <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
    </div>
</form>

<h3 class="mt-5">Horas Extras Registradas</h3>
<div class="table-responsive mt-3">
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Empleado</th>
                <th>Fecha</th>
                <th>Horas</th>
                <th>Pago por Hora</th>
                <th>Total</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($bonificaciones)): ?>
                <?php foreach ($bonificaciones as $b): ?>
                    <tr>
                        <td><?= $b['id_bonificacion']; ?></td>
                        <td><?= htmlspecialchars($b['nombre_empleado']); ?></td>
                        <td><?= $b['fecha_bonificacion']; ?></td>
                        <td><?= number_format($b['horas_extras'], 2); ?></td>
                        <td>Q <?= number_format($b['pago_por_hora'], 2); ?></td>
                        <td>Q <?= number_format($b['monto_bonificacion'], 2); ?></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-primary btn-sm editar-btn"
                                data-id="<?= $b['id_bonificacion']; ?>"
                                data-empleado="<?= $b['id_empleado']; ?>"
                                data-fecha="<?= $b['fecha_bonificacion']; ?>"
                                data-horas="<?= $b['horas_extras']; ?>"
                                data-pago="<?= $b['pago_por_hora']; ?>"
                                data-total="<?= $b['monto_bonificacion']; ?>">Editar</button>
                            <form method="post" style="display:inline;" data-eliminar="true">
                                <input type="hidden" name="operacion" value="eliminar">
                                <input type="hidden" name="id_bonificacion" value="<?= $b['id_bonificacion']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" class="text-center">No hay registros</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</section>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/SistemaWebRestaurante/gestion_RH/Bonificaciones.js"></script>
</body>
</html>
