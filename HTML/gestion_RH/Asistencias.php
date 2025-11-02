<!--Ernesto David Samayoa Jocol 0901-22-3415-->
<?php
session_start();
require_once '../conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// ---------------------- CRUD PRINCIPAL ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operacion = $_POST['operacion'] ?? '';
    switch ($operacion) {
        case 'crear':
            crearAsistencia();
            break;
        case 'actualizar':
            actualizarAsistencia();
            break;
        case 'eliminar':
            eliminarAsistencia();
            break;
    }
}

// ---------------------- FUNCIONES CRUD ----------------------
function crearAsistencia() {
    $conn = conectar();
    $fecha = $_POST['fecha_asistencia'] ?? '';
    $entrada = $_POST['hora_entrada'] ?? '';
    $salida = $_POST['hora_salida'] ?? '';
    $id_empleado = $_POST['id_empleado'] ?? '';

    if ($fecha === '' || $id_empleado === '') {
        $_SESSION['mensaje'] = 'Debe ingresar la fecha y seleccionar un empleado.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Asistencias.php');
        exit();
    }

    $sql = "INSERT INTO asistencia (fecha_asistencia, hora_entrada, hora_salida, id_empleado)
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssi', $fecha, $entrada, $salida, $id_empleado);
    $stmt->execute();

    $success = $stmt->affected_rows > 0;
    $_SESSION['mensaje'] = $success ? 'Asistencia registrada exitosamente.' : 'Error al registrar la asistencia.';
    $_SESSION['tipo_mensaje'] = $success ? 'success' : 'error';

    $stmt->close();
    desconectar($conn);
    header('Location: Asistencias.php');
    exit();
}

function actualizarAsistencia() {
    $conn = conectar();
    $id_asistencia = $_POST['id_asistencia'] ?? '';
    $fecha = $_POST['fecha_asistencia'] ?? '';
    $entrada = $_POST['hora_entrada'] ?? '';
    $salida = $_POST['hora_salida'] ?? '';
    $id_empleado = $_POST['id_empleado'] ?? '';

    if ($id_asistencia === '' || $fecha === '' || $id_empleado === '') {
        $_SESSION['mensaje'] = 'Debe ingresar la fecha y seleccionar un empleado.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Asistencias.php');
        exit();
    }

    $sql = "UPDATE asistencia SET fecha_asistencia=?, hora_entrada=?, hora_salida=?, id_empleado=? WHERE id_asistencia=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssii', $fecha, $entrada, $salida, $id_empleado, $id_asistencia);
    $stmt->execute();

    $success = $stmt->affected_rows > 0;
    $_SESSION['mensaje'] = $success ? 'Asistencia actualizada correctamente.' : 'No se realizaron cambios.';
    $_SESSION['tipo_mensaje'] = $success ? 'success' : 'warning';

    $stmt->close();
    desconectar($conn);
    header('Location: Asistencias.php');
    exit();
}

function eliminarAsistencia() {
    $conn = conectar();
    $id_asistencia = $_POST['id_asistencia'] ?? '';
    $stmt = $conn->prepare("DELETE FROM asistencia WHERE id_asistencia=?");
    $stmt->bind_param('i', $id_asistencia);
    $stmt->execute();

    $_SESSION['mensaje'] = $stmt->affected_rows > 0 ? 'Asistencia eliminada exitosamente.' : 'Error al eliminar asistencia.';
    $_SESSION['tipo_mensaje'] = $stmt->affected_rows > 0 ? 'success' : 'error';

    $stmt->close();
    desconectar($conn);
    header('Location: Asistencias.php');
    exit();
}

function obtenerAsistencias() {
    $conn = conectar();
    $sql = "SELECT a.id_asistencia, a.fecha_asistencia, a.hora_entrada, a.hora_salida,
                   e.id_empleado, e.nombre_empleado, e.apellido_empleado
            FROM asistencia a
            INNER JOIN empleados e ON a.id_empleado = e.id_empleado
            ORDER BY a.id_asistencia ASC";
    $resultado = $conn->query($sql);
    $data = [];
    while ($fila = $resultado->fetch_assoc()) {
        $data[] = $fila;
    }
    desconectar($conn);
    return $data;
}

// Formatear hora a 12h con am/pm 
function format_time($timeStr) {
    if (empty($timeStr)) return '';
    $ts = strtotime($timeStr);
    if ($ts === false) {
        $parts = explode(':', $timeStr);
        if (count($parts) >= 2) {
            // mantén ceros a la izquierda en la hora y minutos
            $hour = ltrim($parts[0], '0');
            if ($hour === '') $hour = '0';
            return $hour . ':' . $parts[1];
        }
        return $timeStr;
    }
    return date('g:i a', $ts);
}

// Formatear fecha a d/m/Y 
function format_date($dateStr) {
    if (empty($dateStr)) return '';
    $ts = strtotime($dateStr);
    if ($ts === false) return $dateStr;
    return date('d/m/Y', $ts);
}

// ---------------------- MAPEO DE DATOS ----------------------
$asistencias = obtenerAsistencias();
$conn = conectar();
$empleados_map = [];
$res = $conn->query("SELECT id_empleado, CONCAT(nombre_empleado, ' ', apellido_empleado) AS nombre_completo FROM empleados");
while ($row = $res->fetch_assoc()) {
    $empleados_map[$row['id_empleado']] = $row['nombre_completo'];
}
desconectar($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asistencias</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
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
                tipo: <?php echo json_encode($_SESSION['tipo_mensaje'] ?? 'error'); ?>
            };
        </script>
        <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
    <?php endif; ?>

    <section class="card shadow p-4">
        <h2 class="text-primary mb-4">Formulario de Asistencias</h2>

        <form id="form-asistencia" method="post" class="row g-3">
            <input type="hidden" name="operacion" id="operacion" value="crear">
            <input type="hidden" name="id_asistencia" id="id_asistencia">

            <div class="col-md-3">
                <label class="form-label"> 🗓️Fecha de Asistencia</label>
                <input type="date" class="form-control" name="fecha_asistencia" id="fecha_asistencia" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">⏰Hora de Entrada</label>
                <input type="time" class="form-control" name="hora_entrada" id="hora_entrada">
            </div>

            <div class="col-md-3">
                <label class="form-label">⏰Hora de Salida</label>
                <input type="time" class="form-control" name="hora_salida" id="hora_salida">
            </div>

            <div class="col-md-3">
                <label class="form-label"> 👤Empleado</label>
                <select class="form-select" name="id_empleado" id="id_empleado" required>
                    <option value="">-- Seleccione empleado --</option>
                    <?php foreach ($empleados_map as $id => $nombre): ?>
                        <option value="<?= $id; ?>"><?= $id . ' - ' . htmlspecialchars($nombre); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>
        </form>

        <div class="d-flex justify-content-between align-items-center mt-5 mb-3">
            <h3 class="mb-0">Lista de Asistencias</h3>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Hora Entrada</th>
                        <th>Hora Salida</th>
                        <th>Empleado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($asistencias as $a): ?>
                        <tr>
                            <td><?= $a['id_asistencia']; ?></td>
                            <td><?= htmlspecialchars(format_date($a['fecha_asistencia'])); ?></td>
                            <td><?= htmlspecialchars(format_time($a['hora_entrada'])); ?></td>
                            <td><?= htmlspecialchars(format_time($a['hora_salida'])); ?></td>
                            <td><?= htmlspecialchars($a['nombre_empleado'] . ' ' . $a['apellido_empleado']); ?></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-primary btn-sm editar-btn"
                                    data-id="<?= $a['id_asistencia']; ?>"
                                    data-fecha="<?= $a['fecha_asistencia']; ?>"
                                    data-entrada="<?= $a['hora_entrada']; ?>"
                                    data-salida="<?= $a['hora_salida']; ?>"
                                    data-empleado="<?= $a['id_empleado']; ?>">Editar</button>

                                <form method="post" style="display:inline;margin-left:6px;" data-eliminar="true">
                                    <input type="hidden" name="operacion" value="eliminar">
                                    <input type="hidden" name="id_asistencia" value="<?= $a['id_asistencia']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($asistencias)): ?>
                        <tr><td colspan="6" class="text-center">No hay registros de asistencia</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/SistemaWebRestaurante/javascript/Asistencias.js"></script>
</body>
</html>
