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
            crearPuesto();
            break;
        case 'actualizar':
            actualizarPuesto();
            break;
        case 'eliminar':
            eliminarPuesto();
            break;
    }
}

// ---------------------- FUNCIONES CRUD ----------------------
function crearPuesto() {
    $conn = conectar();
    $puesto = trim($_POST['puesto'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $sueldo = $_POST['sueldo_base'] ?? '';

    // Validaciones
    if ($puesto === '' || $descripcion === '' || $sueldo === '') {
        $_SESSION['mensaje'] = 'Todos los campos son obligatorios.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Puestos.php');
        exit();
    }

    // Normalizar nombre del puesto
    $puesto = normalize_name($puesto);
    if (!is_valid_name($puesto)) {
        $_SESSION['mensaje'] = 'El nombre del puesto sólo debe contener letras y espacios.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Puestos.php');
        exit();
    }

    if (!is_numeric($sueldo) || $sueldo < 0) {
        $_SESSION['mensaje'] = 'El sueldo base debe ser un número positivo.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Puestos.php');
        exit();
    }

    $sql = "INSERT INTO puesto (puesto, descripcion, sueldo_base) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssd', $puesto, $descripcion, $sueldo);
    $stmt->execute();

    $success = $stmt->affected_rows > 0;
    if ($success) {
        registrarBitacora(
            $conn,
            'Puestos',
            'insertar',
            "Puesto creado (Nombre: $puesto, Sueldo: $sueldo)"
        );
    }
    $_SESSION['mensaje'] = $success ? 'Puesto creado exitosamente.' : 'Error al crear el puesto.';
    $_SESSION['tipo_mensaje'] = $success ? 'success' : 'error';

    $stmt->close();
    desconectar($conn);
    header('Location: Puestos.php');
    exit();
}

function actualizarPuesto() {
    $conn = conectar();
    $id_puesto = $_POST['id_puesto'] ?? '';
    $puesto = trim($_POST['puesto'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $sueldo = $_POST['sueldo_base'] ?? '';

    if ($id_puesto === '' || $puesto === '' || $descripcion === '' || $sueldo === '') {
        $_SESSION['mensaje'] = 'Todos los campos son obligatorios.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Puestos.php');
        exit();
    }

    $puesto = normalize_name($puesto);
    if (!is_valid_name($puesto)) {
        $_SESSION['mensaje'] = 'El nombre del puesto sólo debe contener letras y espacios.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Puestos.php');
        exit();
    }

    if (!is_numeric($sueldo) || $sueldo < 0) {
        $_SESSION['mensaje'] = 'El sueldo base debe ser un número positivo.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Puestos.php');
        exit();
    }

    $sql = "UPDATE puesto SET puesto=?, descripcion=?, sueldo_base=? WHERE id_puesto=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssdi', $puesto, $descripcion, $sueldo, $id_puesto);
    $stmt->execute();

    $success = $stmt->affected_rows > 0;
    if ($success) {
        registrarBitacora(
            $conn,
            'Puestos',
            'Actualizar',
            "Puesto actualizado (ID: $id_puesto, Nombre: $puesto, Sueldo: $sueldo)"
        );
    }
    $_SESSION['mensaje'] = $success ? 'Puesto actualizado correctamente.' : 'No se realizaron cambios.';
    $_SESSION['tipo_mensaje'] = $success ? 'success' : 'warning';

    $stmt->close();
    desconectar($conn);
    header('Location: Puestos.php');
    exit();
}

function eliminarPuesto() {
    $conn = conectar();
    $id_puesto = $_POST['id_puesto'] ?? '';
    $stmt = $conn->prepare("DELETE FROM puesto WHERE id_puesto=?");
    $stmt->bind_param('i', $id_puesto);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        registrarBitacora(
            $conn,
            'Puestos',
            'Eliminar',
            "Puesto eliminado (ID: $id_puesto)"
        );
        $_SESSION['mensaje'] = 'Puesto eliminado exitosamente.';
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'Error al eliminar el puesto.';
        $_SESSION['tipo_mensaje'] = 'error';
    }

    $stmt->close();
    desconectar($conn);
    header('Location: Puestos.php');
    exit();
}

function obtenerPuestos() {
    $conn = conectar();
    $sql = "SELECT * FROM puesto ORDER BY id_puesto";
    $resultado = $conn->query($sql);
    $data = [];
    while ($fila = $resultado->fetch_assoc()) {
        $data[] = $fila;
    }
    desconectar($conn);
    return $data;
}

function normalize_name($s) {
    $s = isset($s) ? (string)$s : '';
    // permitir letras latinas con tildes, Ñ y espacios
    $s = preg_replace('/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]/u', '', $s);
    $s = preg_replace('/\s+/u', ' ', trim($s));
    if ($s === '') return '';
    if (function_exists('mb_strtoupper')) {
        $upper = mb_strtoupper($s, 'UTF-8');
        if ($s === $upper) return $s;
    } else {
        if ($s === strtoupper($s)) return $s;
    }
    if (function_exists('mb_convert_case')) {
        return mb_convert_case($s, MB_CASE_TITLE, 'UTF-8');
    }
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

// ---------------------- MAPEO DE DATOS ----------------------
$puestos = obtenerPuestos();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Puestos</title>
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
        <h2 class="text-primary mb-4">Formulario de Puestos</h2>

        <form id="form-puesto" method="post" class="row g-3">
            <input type="hidden" name="operacion" id="operacion" value="crear">
            <input type="hidden" name="id_puesto" id="id_puesto">

            <div class="col-md-4">
                <label class="form-label">🧑‍💼Nombre del Puesto:</label>
                <input type="text" class="form-control" name="puesto" id="puesto" required placeholder="Ej: Gerente de General">
            </div>

            <div class="col-md-4">
                <label class="form-label"> 📝Descripción:</label>
                <input type="text" class="form-control" name="descripcion" id="descripcion" required placeholder="Ej: Supervisa todas las operaciones del restaurante..">
            </div>

            <div class="col-md-3">
                <label class="form-label">💰Sueldo Base:</label>
                <input type="number" class="form-control" name="sueldo_base" id="sueldo_base" required placeholder="Ej: 5,000.00">
            </div>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>
        </form>

        <div class="d-flex justify-content-between align-items-center mt-5 mb-3">
            <h3 class="mb-0">Lista de Puestos</h3>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Puesto</th>
                        <th>Descripción</th>
                        <th>Sueldo Base (Q)</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($puestos as $p): ?>
                        <tr>
                            <td><?= $p['id_puesto']; ?></td>
                            <td><?= htmlspecialchars($p['puesto']); ?></td>
                            <td><?= htmlspecialchars($p['descripcion']); ?></td>
                            <td><?= 'Q ' . number_format($p['sueldo_base'], 2); ?></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-primary btn-sm editar-btn"
                                    data-id="<?= $p['id_puesto']; ?>"
                                    data-puesto="<?= htmlspecialchars($p['puesto']); ?>"
                                    data-descripcion="<?= htmlspecialchars($p['descripcion']); ?>"
                                    data-sueldo="<?= $p['sueldo_base']; ?>">Editar</button>

                                <form method="post" style="display:inline;margin-left:6px;" data-eliminar="true">
                                    <input type="hidden" name="operacion" value="eliminar">
                                    <input type="hidden" name="id_puesto" value="<?= $p['id_puesto']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($puestos)): ?>
                        <tr><td colspan="5" class="text-center">No hay puestos registrados</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/SistemaWebRestaurante/gestion_RH/Puestos.js"></script>
</body>
</html>
