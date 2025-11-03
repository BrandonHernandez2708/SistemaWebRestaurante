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
            crearSucursal();
            break;
        case 'actualizar':
            actualizarSucursal();
            break;
        case 'eliminar':
            eliminarSucursal();
            break;
    }
}

// ---------------------- FUNCIONES CRUD ----------------------
function crearSucursal() {
    $conn = conectar();
    $direccion = trim($_POST['direccion_sucursal'] ?? '');
    $apertura = $_POST['horario_apertura'] ?? '';
    $cierre = $_POST['hora_cierre'] ?? '';
    $capacidad = (int)($_POST['capacidad_empleados'] ?? 0);
    $telefono = trim($_POST['telefono_sucursal'] ?? '');
    $correo = trim($_POST['correo_sucursal'] ?? '');
    $id_departamento = $_POST['id_departamento'] ?? null;

    // Validaciones
    if ($direccion === '' || $apertura === '' || $cierre === '' || $capacidad <= 0) {
        $_SESSION['mensaje'] = 'Todos los campos obligatorios deben completarse.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Sucursales.php');
        exit();
    }

    if ($correo && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['mensaje'] = 'El correo ingresado no es válido.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Sucursales.php');
        exit();
    }

    if ($telefono && !preg_match('/^[0-9+\-\s]{7,20}$/', $telefono)) {
        $_SESSION['mensaje'] = 'El número de teléfono no es válido.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Sucursales.php');
        exit();
    }

    $sql = "INSERT INTO sucursales (direccion_sucursal, horario_apertura, hora_cierre, capacidad_empleados, telefono_sucursal, correo_sucursal, id_departamento)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssissi', $direccion, $apertura, $cierre, $capacidad, $telefono, $correo, $id_departamento);
    $stmt->execute();

    $success = $stmt->affected_rows > 0;
    if ($success) {
        registrarBitacora(
            $conn,
            'Sucursales',
            'insertar',
            "Sucursal creada (Dirección: $direccion, Apertura: $apertura, Cierre: $cierre, Capacidad: $capacidad, Teléfono: $telefono, Correo: $correo, Departamento: $id_departamento)"
        );
    }
    $_SESSION['mensaje'] = $success ? 'Sucursal creada exitosamente.' : 'Error al crear la sucursal.';
    $_SESSION['tipo_mensaje'] = $success ? 'success' : 'error';

    $stmt->close();
    desconectar($conn);
    header('Location: Sucursales.php');
    exit();
}

function actualizarSucursal() {
    $conn = conectar();
    $id_sucursal = $_POST['id_sucursal'] ?? '';
    $direccion = trim($_POST['direccion_sucursal'] ?? '');
    $apertura = $_POST['horario_apertura'] ?? '';
    $cierre = $_POST['hora_cierre'] ?? '';
    $capacidad = (int)($_POST['capacidad_empleados'] ?? 0);
    $telefono = trim($_POST['telefono_sucursal'] ?? '');
    $correo = trim($_POST['correo_sucursal'] ?? '');
    $id_departamento = $_POST['id_departamento'] ?? null;

    if ($id_sucursal === '' || $direccion === '' || $apertura === '' || $cierre === '' || $capacidad <= 0) {
        $_SESSION['mensaje'] = 'Todos los campos obligatorios deben completarse.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Sucursales.php');
        exit();
    }

    if ($correo && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['mensaje'] = 'El correo ingresado no es válido.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Sucursales.php');
        exit();
    }

    if ($telefono && !preg_match('/^[0-9+\-\s]{7,20}$/', $telefono)) {
        $_SESSION['mensaje'] = 'El número de teléfono no es válido.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Sucursales.php');
        exit();
    }

    $sql = "UPDATE sucursales SET direccion_sucursal=?, horario_apertura=?, hora_cierre=?, capacidad_empleados=?, telefono_sucursal=?, correo_sucursal=?, id_departamento=? WHERE id_sucursal=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssissii', $direccion, $apertura, $cierre, $capacidad, $telefono, $correo, $id_departamento, $id_sucursal);
    $stmt->execute();

    $success = $stmt->affected_rows > 0;
    if ($success) {
        registrarBitacora(
            $conn,
            'Sucursales',
            'Actualizar',
            "Sucursal actualizada (ID: $id_sucursal, Dirección: $direccion, Apertura: $apertura, Cierre: $cierre, Capacidad: $capacidad, Teléfono: $telefono, Correo: $correo, Departamento: $id_departamento)"
        );
    }
    $_SESSION['mensaje'] = $success ? 'Sucursal actualizada exitosamente.' : 'No se realizaron cambios.';
    $_SESSION['tipo_mensaje'] = $success ? 'success' : 'warning';

    $stmt->close();
    desconectar($conn);
    header('Location: Sucursales.php');
    exit();
}

function eliminarSucursal() {
    $conn = conectar();
    $id_sucursal = $_POST['id_sucursal'] ?? '';
    $stmt = $conn->prepare("DELETE FROM sucursales WHERE id_sucursal=?");
    $stmt->bind_param('i', $id_sucursal);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        registrarBitacora(
            $conn,
            'Sucursales',
            'Eliminar',
            "Sucursal eliminada (ID: $id_sucursal)"
        );
        $_SESSION['mensaje'] = 'Sucursal eliminada exitosamente.';
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'Error al eliminar la sucursal.';
        $_SESSION['tipo_mensaje'] = 'error';
    }

    $stmt->close();
    desconectar($conn);
    header('Location: Sucursales.php');
    exit();
}

function obtenerSucursales() {
    $conn = conectar();
    $sql = "SELECT s.*, d.nombre_departamento 
            FROM sucursales s
            LEFT JOIN departamentos d ON s.id_departamento = d.id_departamento
            ORDER BY s.id_sucursal";
    $res = $conn->query($sql);
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    desconectar($conn);
    return $data;
}

// Formatear hora a 12h con am/pm 
function format_time($timeStr) {
    if (empty($timeStr)) return '';
    // Intentar parsear con strtotime; si falla devolver original sin segundos
    $ts = strtotime($timeStr);
    if ($ts === false) {
        $parts = explode(':', $timeStr);
        if (count($parts) >= 2) {
            return ltrim($parts[0], '0') . ':' . $parts[1];
        }
        return $timeStr;
    }
    return date('g:i a', $ts);
}

// ---------------------- MAPEOS ----------------------
$sucursales = obtenerSucursales();
$conn = conectar();
$departamentos = [];
$res = $conn->query("SELECT id_departamento, nombre_departamento FROM departamentos");
while ($row = $res->fetch_assoc()) {
    $departamentos[$row['id_departamento']] = $row['nombre_departamento'];
}
desconectar($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sucursales</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/bootstrap.min.css">
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/diseñoModulos.css">
</head>
<body>
<header class="mb-4">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
        <h1 class="mb-0">Gestión Departamental</h1>
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
        <h2 class="text-primary mb-4">Formulario de Sucursales</h2>

        <form id="form-sucursal" method="post" class="row g-3">
            <input type="hidden" name="operacion" id="operacion" value="crear">
            <input type="hidden" name="id_sucursal" id="id_sucursal">

            <div class="col-md-6">
                <label class="form-label">📌Dirección de la Sucursal:</label>
                <input type="text" class="form-control" name="direccion_sucursal" id="direccion_sucursal"  required placeholder="Ej: 6ta calle 3-37 Zona 1 Mixco">
            </div>

            <div class="col-md-3">
                <label class="form-label">⌚Horario de Apertura:</label>
                <input type="time" class="form-control" name="horario_apertura" id="horario_apertura" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">⌚Hora de Cierre:</label>
                <input type="time" class="form-control" name="hora_cierre" id="hora_cierre" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">📊Capacidad de Empleados:</label>
                <input type="number" class="form-control" name="capacidad_empleados" id="capacidad_empleados" min="1" required placeholder="Ej: 10">
            </div>

            <div class="col-md-3">
                <label class="form-label">📞Teléfono:</label>
                <input type="text" class="form-control" name="telefono_sucursal" id="telefono_sucursal" required placeholder="Ej: 2344-5588">
                <small class="form-text help-text">*8 dígitos</small>
            </div>

            <div class="col-md-3">
                <label class="form-label">📧Correo Electrónico:</label>
                <input type="email" class="form-control" name="correo_sucursal" id="correo_sucursal" required placeholder="Ej: mixcozona1@gmail.com">
                <small class="form-text text-muted help-text">*usuario@gmail.com</small>
            </div>

            <div class="col-md-3">
                <label class="form-label">📍Departamento:</label>
                <select class="form-select" name="id_departamento" id="id_departamento">
                    <option value="">-- Seleccione Departamento --</option>
                    <?php foreach ($departamentos as $id => $nombre): ?>
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
            <h3 class="mb-0">Lista de Sucursales</h3>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Dirección</th>
                        <th>Horario</th>
                        <th>Capacidad</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Departamento</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sucursales as $s): ?>
                        <tr>
                            <td><?= $s['id_sucursal']; ?></td>
                            <td><?= htmlspecialchars($s['direccion_sucursal']); ?></td>
                            <td><?= htmlspecialchars(format_time($s['horario_apertura'])) . ' - ' . htmlspecialchars(format_time($s['hora_cierre'])); ?></td>
                            <td><?= htmlspecialchars($s['capacidad_empleados']); ?></td>
                            <td><?= htmlspecialchars($s['telefono_sucursal']); ?></td>
                            <td><?= htmlspecialchars($s['correo_sucursal']); ?></td>
                            <td><?= htmlspecialchars($s['nombre_departamento'] ?? ''); ?></td>
                            <td class="text-center">
                                <div class="d-flex gap-2 justify-content-center align-items-center">
                                    <button type="button" class="btn btn-primary btn-sm editar-btn"
                                        data-id="<?= $s['id_sucursal']; ?>"
                                        data-direccion="<?= htmlspecialchars($s['direccion_sucursal']); ?>"
                                        data-apertura="<?= $s['horario_apertura']; ?>"
                                        data-cierre="<?= $s['hora_cierre']; ?>"
                                        data-capacidad="<?= $s['capacidad_empleados']; ?>"
                                        data-telefono="<?= htmlspecialchars($s['telefono_sucursal']); ?>"
                                        data-correo="<?= htmlspecialchars($s['correo_sucursal']); ?>"
                                        data-departamento="<?= $s['id_departamento']; ?>">Editar</button>

                                    <form method="post" class="d-inline" data-eliminar="true" style="margin:0;">
                                        <input type="hidden" name="operacion" value="eliminar">
                                        <input type="hidden" name="id_sucursal" value="<?= $s['id_sucursal']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($sucursales)): ?>
                        <tr><td colspan="8" class="text-center">No hay sucursales registradas</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/SistemaWebRestaurante/Departamental/Sucursales.js"></script>
</body>
</html>
