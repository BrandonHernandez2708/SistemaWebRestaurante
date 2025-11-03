<!--Ernesto David Samayoa Jocol 0901-22-3415-->
<?php
session_start();
require_once '../conexion.php';
require_once '../funciones_globales.php';

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
            crearPenalizacion();
            break;
        case 'actualizar':
            actualizarPenalizacion();
            break;
        case 'eliminar':
            eliminarPenalizacion();
            break;
    }
}

// ---------------------- FUNCIONES CRUD ----------------------
function crearPenalizacion() {
    $conn = conectar();
    $id_empleado = $_POST['id_empleado'] ?? '';
    $descripcion = trim($_POST['descripcion_penalizacion'] ?? '');
    $fecha = $_POST['fecha_penalizacion'] ?? '';
    $descuento = $_POST['descuento_penalizacion'] ?? 0.00;

    if ($id_empleado === '' || $descripcion === '' || $fecha === '') {
        $_SESSION['mensaje'] = 'Debe completar todos los campos requeridos.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Penalizacion.php');
        exit();
    }

    if (!is_numeric($descuento) || $descuento < 0) {
        $_SESSION['mensaje'] = 'El descuento debe ser un valor numérico positivo.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Penalizacion.php');
        exit();
    }

    $sql = "INSERT INTO penalizaciones (id_empleado, descripcion_penalizacion, fecha_penalizacion, descuento_penalizacion)
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('issd', $id_empleado, $descripcion, $fecha, $descuento);
    $stmt->execute();

    $success = $stmt->affected_rows > 0;
    if ($success) {
        registrarBitacora(
            $conn,
            'Penalizaciones',
            'insertar',
            "Penalización registrada (Empleado ID: $id_empleado, Fecha: $fecha, Descuento: $descuento)"
        );
    }
    $_SESSION['mensaje'] = $success ? 'Penalización registrada exitosamente.' : 'Error al registrar penalización.';
    $_SESSION['tipo_mensaje'] = $success ? 'success' : 'error';

    $stmt->close();
    desconectar($conn);
    header('Location: Penalizacion.php');
    exit();
}

function actualizarPenalizacion() {
    $conn = conectar();
    $id_penalizacion = $_POST['id_penalizacion'] ?? '';
    $id_empleado = $_POST['id_empleado'] ?? '';
    $descripcion = trim($_POST['descripcion_penalizacion'] ?? '');
    $fecha = $_POST['fecha_penalizacion'] ?? '';
    $descuento = $_POST['descuento_penalizacion'] ?? 0.00;

    if ($id_penalizacion === '' || $id_empleado === '' || $descripcion === '' || $fecha === '') {
        $_SESSION['mensaje'] = 'Debe completar todos los campos requeridos.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Penalizacion.php');
        exit();
    }

    if (!is_numeric($descuento) || $descuento < 0) {
        $_SESSION['mensaje'] = 'El descuento debe ser un valor numérico positivo.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Penalizacion.php');
        exit();
    }

    $sql = "UPDATE penalizaciones 
            SET id_empleado=?, descripcion_penalizacion=?, fecha_penalizacion=?, descuento_penalizacion=?
            WHERE id_penalizacion=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('issdi', $id_empleado, $descripcion, $fecha, $descuento, $id_penalizacion);
    $stmt->execute();

    $success = $stmt->affected_rows > 0;
    if ($success) {
        registrarBitacora(
            $conn,
            'Penalizaciones',
            'Actualizar',
            "Penalización actualizada (ID: $id_penalizacion, Empleado ID: $id_empleado, Fecha: $fecha, Descuento: $descuento)"
        );
    }
    $_SESSION['mensaje'] = $success ? 'Penalización actualizada correctamente.' : 'No se realizaron cambios.';
    $_SESSION['tipo_mensaje'] = $success ? 'success' : 'warning';

    $stmt->close();
    desconectar($conn);
    header('Location: Penalizacion.php');
    exit();
}

function eliminarPenalizacion() {
    $conn = conectar();
    $id_penalizacion = $_POST['id_penalizacion'] ?? '';
    $stmt = $conn->prepare("DELETE FROM penalizaciones WHERE id_penalizacion=?");
    $stmt->bind_param('i', $id_penalizacion);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        registrarBitacora(
            $conn,
            'Penalizaciones',
            'Eliminar',
            "Penalización eliminada (ID: $id_penalizacion)"
        );
        $_SESSION['mensaje'] = 'Penalización eliminada exitosamente.';
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'Error al eliminar penalización.';
        $_SESSION['tipo_mensaje'] = 'error';
    }

    $stmt->close();
    desconectar($conn);
    header('Location: Penalizacion.php');
    exit();
}

function obtenerPenalizaciones() {
    $conn = conectar();
    $sql = "SELECT p.id_penalizacion, p.descripcion_penalizacion, p.fecha_penalizacion, 
                   p.descuento_penalizacion, e.id_empleado, e.nombre_empleado, e.apellido_empleado
            FROM penalizaciones p
            INNER JOIN empleados e ON p.id_empleado = e.id_empleado
            ORDER BY p.fecha_penalizacion DESC";
    $resultado = $conn->query($sql);
    $data = [];
    while ($fila = $resultado->fetch_assoc()) {
        $data[] = $fila;
    }
    desconectar($conn);
    return $data;
}

// ---------------------- MAPEO DE DATOS ----------------------
$penalizaciones = obtenerPenalizaciones();
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
    <title>Penalizaciones</title>
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
        <h2 class="text-primary mb-4">Formulario de Penalizaciones</h2>

        <form id="form-penalizacion" method="post" class="row g-3">
            <input type="hidden" name="operacion" id="operacion" value="crear">
            <input type="hidden" name="id_penalizacion" id="id_penalizacion">

            <div class="col-md-4">
                <label class="form-label"> 👤Empleado</label>
                <select class="form-select" name="id_empleado" id="id_empleado" required>
                    <option value="">-- Seleccione empleado --</option>
                    <?php foreach ($empleados_map as $id => $nombre): ?>
                        <option value="<?= $id; ?>"><?= $id . ' - ' . htmlspecialchars($nombre); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label"> 🗓️Fecha Penalización</label>
                <input type="date" class="form-control" name="fecha_penalizacion" id="fecha_penalizacion" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">💰Descuento (Q)</label>
                <input type="number" step="0.01" min="0" class="form-control" name="descuento_penalizacion" id="descuento_penalizacion" required>
            </div>

            <div class="col-md-12">
                <label class="form-label"> 📝Descripción</label>
                <textarea class="form-control" name="descripcion_penalizacion" id="descripcion_penalizacion" rows="2" required></textarea>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>
        </form>

        <div class="d-flex justify-content-between align-items-center mt-5 mb-3">
            <h3 class="mb-0">Lista de Penalizaciones</h3>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Empleado</th>
                        <th>Fecha</th>
                        <th>Descripción</th>
                        <th>Descuento (Q)</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($penalizaciones as $p): ?>
                        <tr>
                            <td><?= $p['id_penalizacion']; ?></td>
                            <td><?= htmlspecialchars($p['nombre_empleado'] . ' ' . $p['apellido_empleado']); ?></td>
                            <td><?php
                                $fecha = $p['fecha_penalizacion'] ?? '';
                                echo $fecha ? date('d/m/Y', strtotime($fecha)) : '';
                            ?></td>
                            <td><?= htmlspecialchars($p['descripcion_penalizacion']); ?></td>
                            <td><?= 'Q ' . number_format($p['descuento_penalizacion'], 2); ?></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-primary btn-sm editar-btn"
                                    data-id="<?= $p['id_penalizacion']; ?>"
                                    data-empleado="<?= $p['id_empleado']; ?>"
                                    data-fecha="<?= $p['fecha_penalizacion']; ?>"
                                    data-descripcion="<?= htmlspecialchars($p['descripcion_penalizacion']); ?>"
                                    data-descuento="<?= $p['descuento_penalizacion']; ?>">Editar</button>

                                <form method="post" style="display:inline;margin-left:6px;" data-eliminar="true">
                                    <input type="hidden" name="operacion" value="eliminar">
                                    <input type="hidden" name="id_penalizacion" value="<?= $p['id_penalizacion']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($penalizaciones)): ?>
                        <tr><td colspan="6" class="text-center">No hay penalizaciones registradas</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="./Penalizacion.js"></script>
</body>
</html>
