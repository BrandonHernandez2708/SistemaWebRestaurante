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
            crearEmpleado();
            break;
        case 'actualizar':
            actualizarEmpleado();
            break;
        case 'eliminar':
            eliminarEmpleado();
            break;
    }
}

function crearEmpleado() {
    $conn = conectar();
    $dpi = isset($_POST['dpi']) ? trim($_POST['dpi']) : '';
    $nombre = $_POST['nombre_empleado'] ?? '';
    $apellido = $_POST['apellido_empleado'] ?? '';
    $id_departamento = $_POST['id_departamento'] ?? null;
    $id_puesto = $_POST['id_puesto'] ?? null;

    if ($id_departamento && preg_match('/^\s*(\d+)/', $id_departamento, $m)) $id_departamento = (int)$m[1];
    if ($id_puesto && preg_match('/^\s*(\d+)/', $id_puesto, $m)) $id_puesto = (int)$m[1];

    // Validaciones básicas en servidor
    if ($dpi === '') {
        $_SESSION['mensaje'] = 'El DPI es requerido';
        $_SESSION['tipo_mensaje'] = 'error';
        desconectar($conn);
        header('Location: Empleados.php');
        exit();
    }

    // Normalizar y validar nombres/apellidos
    $nombre = normalize_name($nombre);
    $apellido = normalize_name($apellido);
    if (!is_valid_name($nombre)) {
        $_SESSION['mensaje'] = 'El nombre sólo debe contener letras y espacios';
        $_SESSION['tipo_mensaje'] = 'error';
        desconectar($conn);
        header('Location: Empleados.php');
        exit();
    }
    if (!is_valid_name($apellido)) {
        $_SESSION['mensaje'] = 'El apellido sólo debe contener letras y espacios';
        $_SESSION['tipo_mensaje'] = 'error';
        desconectar($conn);
        header('Location: Empleados.php');
        exit();
    }

    // Normalizar DPI: conservar sólo 13 dígitos
    $dpi_digits = preg_replace('/\D/', '', $dpi);
    if (strlen($dpi_digits) !== 13) {
        $_SESSION['mensaje'] = 'El DPI debe contener 13 dígitos';
        $_SESSION['tipo_mensaje'] = 'error';
        desconectar($conn);
        header('Location: Empleados.php');
        exit();
    }
    $dpi = $dpi_digits;

    

    // Verificar DPI ya esta registrado
    $check = $conn->prepare("SELECT id_empleado FROM empleados WHERE dpi = ? LIMIT 1");
    $check->bind_param('s', $dpi);
    $check->execute();
    $res = $check->get_result();
    if ($res && $res->num_rows > 0) {
        $_SESSION['mensaje'] = 'El DPI ingresado ya está registrado para otro empleado';
        $_SESSION['tipo_mensaje'] = 'error';
        $check->close();
        desconectar($conn);
        header('Location: Empleados.php');
        exit();
    }
    $check->close();

    $sql = "INSERT INTO empleados (dpi, nombre_empleado, apellido_empleado, id_departamento, id_puesto)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssii', $dpi, $nombre, $apellido, $id_departamento, $id_puesto);
    $stmt->execute();

    $success = $stmt->affected_rows > 0;
    $message = $success ? 'Empleado creado exitosamente' : 'Error al crear empleado: ' . $stmt->error;
    $tipo = $success ? 'success' : 'error';
    $new_id = $stmt->insert_id;

    $stmt->close();
    desconectar($conn);

    // Si es una petición AJAX, respondemos JSON y no redirigimos
    if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'tipo' => $tipo,
            'id_empleado' => $new_id,
            'dpi' => format_dpi($dpi),
            'nombre' => $nombre,
            'apellido' => $apellido,
            'id_departamento' => $id_departamento,
            'id_puesto' => $id_puesto
        ]);
        exit();
    }

    $_SESSION['mensaje'] = $message;
    $_SESSION['tipo_mensaje'] = $tipo;
    header('Location: Empleados.php');
    exit();
}

function actualizarEmpleado() {
    $conn = conectar();
    $id_empleado = $_POST['id_empleado'] ?? '';
    $dpi = isset($_POST['dpi']) ? trim($_POST['dpi']) : '';
    $nombre = $_POST['nombre_empleado'] ?? '';
    $apellido = $_POST['apellido_empleado'] ?? '';
    $id_departamento = $_POST['id_departamento'] ?? null;
    $id_puesto = $_POST['id_puesto'] ?? null;

    if ($id_departamento && preg_match('/^\s*(\d+)/', $id_departamento, $m)) $id_departamento = (int)$m[1];
    if ($id_puesto && preg_match('/^\s*(\d+)/', $id_puesto, $m)) $id_puesto = (int)$m[1];
    $id_empleado = (int)$id_empleado;

    // Validación básica
    if ($dpi === '') {
        $_SESSION['mensaje'] = 'El DPI es requerido';
        $_SESSION['tipo_mensaje'] = 'error';
        desconectar($conn);
        header('Location: Empleados.php');
        exit();
    }

    // Normalizar DPI: conservar sólo dígitos
    $dpi_digits = preg_replace('/\D/', '', $dpi);
    if (strlen($dpi_digits) !== 13) {
        $_SESSION['mensaje'] = 'El DPI debe contener 13 dígitos';
        $_SESSION['tipo_mensaje'] = 'error';
        desconectar($conn);
        header('Location: Empleados.php');
        exit();
    }
    $dpi = $dpi_digits;

    // Verificar que el DPI no exista en otro registro
    $check = $conn->prepare("SELECT id_empleado FROM empleados WHERE dpi = ? AND id_empleado != ? LIMIT 1");
    $check->bind_param('si', $dpi, $id_empleado);
    $check->execute();
    $res = $check->get_result();
    if ($res && $res->num_rows > 0) {
        $_SESSION['mensaje'] = 'El DPI ingresado ya está registrado';
        $_SESSION['tipo_mensaje'] = 'error';
        $check->close();
        desconectar($conn);
        header('Location: Empleados.php');
        exit();
    }
    $check->close();

    $sql = "UPDATE empleados SET dpi=?, nombre_empleado=?, apellido_empleado=?, id_departamento=?, id_puesto=? WHERE id_empleado=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssiii', $dpi, $nombre, $apellido, $id_departamento, $id_puesto, $id_empleado);
    $stmt->execute();

    $success = $stmt->affected_rows > 0;
    $message = $success ? 'Empleado actualizado exitosamente' : 'Error al actualizar empleado: ' . $stmt->error;
    $tipo = $success ? 'success' : 'error';

    $stmt->close();
    desconectar($conn);

    if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'tipo' => $tipo,
            'id_empleado' => $id_empleado,
            'dpi' => format_dpi($dpi),
            'nombre' => $nombre,
            'apellido' => $apellido,
            'id_departamento' => $id_departamento,
            'id_puesto' => $id_puesto
        ]);
        exit();
    }

    $_SESSION['mensaje'] = $message;
    $_SESSION['tipo_mensaje'] = $tipo;
    header('Location: Empleados.php');
    exit();
}

function eliminarEmpleado() {
    $conn = conectar();
    $id_empleado = isset($_POST['id_empleado']) ? (int)$_POST['id_empleado'] : 0;

    if ($id_empleado <= 0) {
        $_SESSION['mensaje'] = 'ID de empleado inválido.';
        $_SESSION['tipo_mensaje'] = 'error';
        desconectar($conn);
        header('Location: Empleados.php');
        exit();
    }

    // Hacemos la eliminación en una transacción: primero eliminamos filas dependientes 
    // para evitar errores por restricciones FK, luego eliminamos el empleado.
    $conn->begin_transaction();
    try {
        // Eliminar correos asociados
        $stmtChild = $conn->prepare("DELETE FROM correos_empleado WHERE id_empleado = ?");
        if ($stmtChild) {
            $stmtChild->bind_param('i', $id_empleado);
            $stmtChild->execute();
            $stmtChild->close();
        }

        // Ahora eliminar el empleado
        $stmt = $conn->prepare("DELETE FROM empleados WHERE id_empleado = ?");
        if (!$stmt) {
            throw new Exception('Error preparando sentencia de eliminación: ' . $conn->error);
        }
        $stmt->bind_param('i', $id_empleado);
        $stmt->execute();

        $affected = $stmt->affected_rows;
        $stmt->close();

        $conn->commit();

        if ($affected > 0) {
            $_SESSION['mensaje'] = 'Empleado eliminado exitosamente.';
            $_SESSION['tipo_mensaje'] = 'success';
        } else {
            $_SESSION['mensaje'] = 'No se encontró el empleado o no se pudo eliminar.';
            $_SESSION['tipo_mensaje'] = 'error';
        }
    } catch (Exception $e) {
        // Mensaje de que el empleado no se puede eliminar por dependencias
        try { $conn->rollback(); } catch (Exception $e2) {}
        $_SESSION['mensaje'] = 'No se puede eliminar el empleado porque tiene información relacionada con otros datos.';
        $_SESSION['tipo_mensaje'] = 'warning';
    }

    desconectar($conn);
    header('Location: Empleados.php');
    exit();
}

function obtenerEmpleados() {
    $conn = conectar();
    $sql = "SELECT * FROM empleados ORDER BY id_empleado";
    $resultado = $conn->query($sql);
    $data = [];
    while ($fila = $resultado->fetch_assoc()) {
        $data[] = $fila;
    }
    desconectar($conn);
    return $data;
}

// ---------------------- MAPEOS ----------------------
$empleados = obtenerEmpleados();
$conn = conectar();

// Departamentos
$departamentos_map = [];
$res = $conn->query("SELECT id_departamento, nombre_departamento FROM departamentos");
while ($row = $res->fetch_assoc()) {
    $departamentos_map[$row['id_departamento']] = $row['nombre_departamento'];
}

// Puestos
$puestos_map = [];
$res = $conn->query("SELECT id_puesto, puesto FROM puesto");
while ($row = $res->fetch_assoc()) {
    $puestos_map[$row['id_puesto']] = $row['puesto'];
}
desconectar($conn);

// Formatear DPI para mostrar en la interfaz (4-5-4)
function format_dpi($dpi) {
    $d = preg_replace('/\D/', '', (string)$dpi);
    if (strlen($d) === 13) {
        return substr($d, 0, 4) . ' ' . substr($d, 4, 5) . ' ' . substr($d, 9, 4);
    }
    return $dpi;
}

// Formatear y validar nombres/apellidos en servidor
function normalize_name($s) {
    // eliminar caracteres no permitidos (permitir letras con tildes, Ñ y espacios)
    $s = isset($s) ? (string)$s : '';
    // quitar todo lo que no sea letra o espacio
    $s = preg_replace('/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]/u', '', $s);
    // colapsar espacios múltiples y trim
    $s = preg_replace('/\s+/u', ' ', trim($s));
    if ($s === '') return '';

    // Si el usuario ingresó todo en mayúsculas, respetarlo
    if (function_exists('mb_strtoupper')) {
        $upper = mb_strtoupper($s, 'UTF-8');
        if ($s === $upper) return $s;
    } else {
        if ($s === strtoupper($s)) return $s;
    }

    if (function_exists('mb_convert_case')) {
        return mb_convert_case($s, MB_CASE_TITLE, 'UTF-8');
    }

    // Fallback: capitalizar cada palabra manualmente
    $parts = preg_split('/\s+/u', $s);
    $out = [];
    foreach ($parts as $p) {
        $first = strtoupper(substr($p, 0, 1));
        $rest = strtolower(substr($p, 1));
        $out[] = $first . $rest;
    }
    return implode(' ', $out);
}

function is_valid_name($s) {
    if (!is_string($s)) return false;
    $s = trim($s);
    if ($s === '') return false;
    return preg_match('/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]+$/u', $s) === 1;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empleados</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/bootstrap.min.css">
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/diseñoModulos.css">

</head>
<body>
<header class="mb-4">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
        <h1 class="mb-0">Gestión de Empleados</h1>
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
            // Mensaje generado en servidor — lo mostramos con SweetAlert en el cliente si está disponible
            window.__mensaje = {
                text: <?php echo json_encode($_SESSION['mensaje']); ?>,
                tipo: <?php echo json_encode($_SESSION['tipo_mensaje'] ?? 'error'); ?>
            };
        </script>
        <noscript>
            <div class="alert alert-<?php echo ($_SESSION['tipo_mensaje'] ?? '') === 'success' ? 'success' : 'danger'; ?>">
                <?php echo htmlspecialchars($_SESSION['mensaje']); ?>
            </div>
        </noscript>
        <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
    <?php endif; ?>

    <section class="card shadow p-4">
        <h2 class="card__title text-primary mb-4">Formulario de Empleados</h2>

        <form id="form-empleado" method="post" class="row g-3">
            <input type="hidden" name="operacion" id="operacion" value="crear">
            <input type="hidden" name="id_empleado" id="id_empleado">

            <div class="col-md-3">
                <label class="form-label">🪪 DPI:</label>
                <input type="text" class="form-control" name="dpi" id="dpi" required placeholder="Ej: 1234 56356 2411">
                <small class="form-text text-muted help-text">*13 dígitos numéricos</small>
            </div>

            <div class="col-md-3">
                <label class="form-label">👤 Nombre:</label>
                <input type="text" class="form-control" name="nombre_empleado" id="nombre_empleado" required placeholder="Ej: Juan Alberto">
            </div>

            <div class="col-md-3">
                <label class="form-label">👤 Apellido:</label>
                <input type="text" class="form-control" name="apellido_empleado" id="apellido_empleado" required placeholder="Ej: Pérez López">
            </div>

            <div class="col-md-3">
                <label class="form-label">📍ID Departamento:</label>
                <select class="form-select" name="id_departamento" id="id_departamento" required>
                    <option value="">-- Sin departamento --</option>
                    <?php foreach ($departamentos_map as $dep_id => $dep_name): ?>
                        <option value="<?= $dep_id; ?>"><?= $dep_id . ' - ' . htmlspecialchars($dep_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">💼 ID Puesto:</label>
                <select class="form-select" name="id_puesto" id="id_puesto" required>
                    <option value="">-- Sin puesto --</option>
                    <?php foreach ($puestos_map as $puesto_id => $puesto_nombre): ?>
                        <option value="<?= $puesto_id; ?>"><?= $puesto_id . ' - ' . htmlspecialchars($puesto_nombre); ?></option>
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
            <h3 class="mb-0">Lista de Empleados</h3>
        </div>

        <div id="lista-empleados" class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>DPI</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Departamento</th>
                        <th>Puesto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($empleados as $empleado): ?>
                    <tr>
                        <td><?= $empleado['id_empleado']; ?></td>
                        <td><?= htmlspecialchars(format_dpi($empleado['dpi'])); ?></td>
                        <td><?= htmlspecialchars($empleado['nombre_empleado']); ?></td>
                        <td><?= htmlspecialchars($empleado['apellido_empleado']); ?></td>
                        <td><?= htmlspecialchars($departamentos_map[$empleado['id_departamento']] ?? ''); ?></td>
                        <td><?= htmlspecialchars($puestos_map[$empleado['id_puesto']] ?? ''); ?></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-primary btn-sm editar-btn"
                                data-id="<?= $empleado['id_empleado']; ?>"
                                data-dpi="<?= htmlspecialchars(format_dpi($empleado['dpi'])); ?>"
                                data-nombre="<?= htmlspecialchars($empleado['nombre_empleado']); ?>"
                                data-apellido="<?= htmlspecialchars($empleado['apellido_empleado']); ?>"
                                data-departamento="<?= $empleado['id_departamento']; ?>"
                                data-puesto="<?= $empleado['id_puesto']; ?>">Editar</button>
                            <form method="post" style="display:inline;margin-left:6px;" data-eliminar="true">
                                <input type="hidden" name="operacion" value="eliminar">
                                <input type="hidden" name="id_empleado" value="<?= $empleado['id_empleado']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($empleados)): ?>
                    <tr><td colspan="7" class="text-center">No hay empleados registrados</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/SistemaWebRestaurante/javascript/Empleados.js"></script>
</body>
</html>  