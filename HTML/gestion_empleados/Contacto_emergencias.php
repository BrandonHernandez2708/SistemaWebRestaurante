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
            crearContacto();
            break;
        case 'actualizar':
            actualizarContacto();
            break;
        case 'eliminar':
            eliminarContacto();
            break;
    }
}

// ---------------------- FUNCIONES CRUD ----------------------
function crearContacto() {
    $conn = conectar();
    $nombre = trim($_POST['nombre_contacto'] ?? '');
    $relacion = trim($_POST['relacion'] ?? '');
    $telefono = trim($_POST['numero_telefono'] ?? '');
    $id_empleado = $_POST['id_empleado'] ?? '';

    // Validaciones
    if ($nombre === '' || $id_empleado === '') {
        $_SESSION['mensaje'] = 'Debe ingresar el nombre y seleccionar el empleado.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Contacto_emergencias.php');
        exit();
    }

    $nombre = normalize_name($nombre);
    if (!is_valid_name($nombre) || mb_strlen($nombre) < 2 || mb_strlen($nombre) > 60) {
        $_SESSION['mensaje'] = 'El nombre del contacto sólo debe contener letras y espacios (2-60 caracteres).';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Contacto_emergencias.php');
        exit();
    }

    if ($relacion !== '') {
        $relacion = normalize_name($relacion);
        if (!is_valid_name($relacion) || mb_strlen($relacion) < 2 || mb_strlen($relacion) > 40) {
            $_SESSION['mensaje'] = 'La relación sólo debe contener letras y espacios (2-40 caracteres).';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: Contacto_emergencias.php');
            exit();
        }
    }


    if ($telefono && !preg_match('/^\d{4}-\d{4}$/', $telefono)) {
        $_SESSION['mensaje'] = 'El número de teléfono debe tener formato 0000-0000.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Contacto_emergencias.php');
        exit();
    }

    $sql = "INSERT INTO contacto_emergencia (nombre_contacto, relacion, numero_telefono, id_empleado)
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssi', $nombre, $relacion, $telefono, $id_empleado);
    $stmt->execute();

    $success = $stmt->affected_rows > 0;
    if ($success) {
        registrarBitacora(
            $conn,
            'Contactos de Emergencia',
            'insertar',
            "Registro insertado (Empleado ID: $id_empleado, Nombre: $nombre, Relación: $relacion, Teléfono: $telefono)"
        );
    }
    $_SESSION['mensaje'] = $success ? 'Contacto de emergencia agregado exitosamente.' : 'Error al agregar el contacto.';
    $_SESSION['tipo_mensaje'] = $success ? 'success' : 'error';

    $stmt->close();
    desconectar($conn);
    header('Location: Contacto_emergencias.php');
    exit();
}

function actualizarContacto() {
    $conn = conectar();
    $id_contacto = $_POST['id_contacto'] ?? '';
    $nombre = trim($_POST['nombre_contacto'] ?? '');
    $relacion = trim($_POST['relacion'] ?? '');
    $telefono = trim($_POST['numero_telefono'] ?? '');


    if ($id_contacto === '' || $nombre === '') {
        $_SESSION['mensaje'] = 'Debe ingresar el nombre del contacto.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Contacto_emergencias.php');
        exit();
    }

    // Validar nombre y relación
    $nombre = normalize_name($nombre);
    if (!is_valid_name($nombre) || mb_strlen($nombre) < 2 || mb_strlen($nombre) > 60) {
        $_SESSION['mensaje'] = 'El nombre del contacto sólo debe contener letras y espacios (2-60 caracteres).';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Contacto_emergencias.php');
        exit();
    }

    if ($relacion !== '') {
        $relacion = normalize_name($relacion);
        if (!is_valid_name($relacion) || mb_strlen($relacion) < 2 || mb_strlen($relacion) > 40) {
            $_SESSION['mensaje'] = 'La relación sólo debe contener letras y espacios (2-40 caracteres).';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: Contacto_emergencias.php');
            exit();
        }
    }

    if ($telefono && !preg_match('/^\d{4}-\d{4}$/', $telefono)) {
        $_SESSION['mensaje'] = 'El número de teléfono debe tener formato 0000-0000.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Contacto_emergencias.php');
        exit();
    }

    $sql = "UPDATE contacto_emergencia SET nombre_contacto=?, relacion=?, numero_telefono=? WHERE id_contacto=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssi', $nombre, $relacion, $telefono, $id_contacto);
    $stmt->execute();

    $success = $stmt->affected_rows > 0;
    if ($success) {
        registrarBitacora(
            $conn,
            'Contactos de Emergencia',
            'Actualizar',
            "Registro actualizado (ID Contacto: $id_contacto, Nombre: $nombre, Relación: $relacion, Teléfono: $telefono)"
        );
    }
    $_SESSION['mensaje'] = $success ? 'Contacto de emergencia actualizado correctamente.' : 'No se realizaron cambios.';
    $_SESSION['tipo_mensaje'] = $success ? 'success' : 'warning';

    $stmt->close();
    desconectar($conn);
    header('Location: Contacto_emergencias.php');
    exit();
}

function eliminarContacto() {
    $conn = conectar();
    $id_contacto = $_POST['id_contacto'] ?? '';
    $stmt = $conn->prepare("DELETE FROM contacto_emergencia WHERE id_contacto=?");
    $stmt->bind_param('i', $id_contacto);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        registrarBitacora(
            $conn,
            'Contactos de Emergencia',
            'Eliminar',
            "Registro eliminado (ID Contacto: $id_contacto)"
        );
        $_SESSION['mensaje'] = 'Contacto eliminado exitosamente.';
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'Error al eliminar el contacto.';
        $_SESSION['tipo_mensaje'] = 'error';
    }

    $stmt->close();
    desconectar($conn);
    header('Location: Contacto_emergencias.php');
    exit();
}

function obtenerContactos() {
    $conn = conectar();
    $sql = "SELECT c.id_contacto, c.nombre_contacto, c.relacion, c.numero_telefono,
                   e.id_empleado, e.nombre_empleado, e.apellido_empleado
            FROM contacto_emergencia c
            INNER JOIN empleados e ON c.id_empleado = e.id_empleado
        -- Ordenar por id_empleado y luego por id_contacto para que los grupos
        -- se muestren en orden ascendente por empleado y por inserción del contacto
        ORDER BY c.id_empleado ASC, c.id_contacto ASC";
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
$contactos = obtenerContactos();
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
    <title>Contacto de Emergencias</title>
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
            window.__mensaje = {
                text: <?php echo json_encode($_SESSION['mensaje']); ?>,
                tipo: <?php echo json_encode($_SESSION['tipo_mensaje'] ?? 'error'); ?>
            };
        </script>
        <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
    <?php endif; ?>

    <section class="card shadow p-4">
        <h2 class="text-primary mb-4">Formulario de Contacto de Emergencia</h2>

        <form id="form-contacto" method="post" class="row g-3">
            <input type="hidden" name="operacion" id="operacion" value="crear">
            <input type="hidden" name="id_contacto" id="id_contacto">

            <div class="col-md-4">
                <label class="form-label">👤 Nombre del Contacto:</label>
                <input type="text" class="form-control" name="nombre_contacto" id="nombre_contacto"  required placeholder="Ej: Rubén Luch">
            </div>

            <div class="col-md-3">
                <label class="form-label">👩‍❤️‍👨 Relación:</label>
                <input type="text" class="form-control" name="relacion" id="relacion"  required placeholder="Ej: Espos@, Herman@, Amig@">
            </div>

            <div class="col-md-3">
                <label class="form-label">📞 Número de Teléfono:</label>
                <input type="text" class="form-control" name="numero_telefono" id="numero_telefono"  required placeholder="Ej: 2446-5890">
                <small class="form-text help-text">*8 dígitos</small>
            </div>

            <div class="col-md-4">
                <label class="form-label">Empleado Asociado:</label>
                <select class="form-select" name="id_empleado" id="id_empleado" required>
                    <option value="">-- Seleccione Empleado --</option>
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
            <h3 class="mb-0">Lista de Contactos</h3>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID Empleado</th>
                        <th>Empleado</th>
                        <th>Contacto</th>
                        <th>Teléfono</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    // Agrupar contactos por empleado
                    $grupos = [];
                    foreach ($contactos as $c) {
                        $idem = $c['id_empleado'];
                        if (!isset($grupos[$idem])) {
                            $grupos[$idem] = [
                                'id_empleado' => $idem,
                                'nombre' => $c['nombre_empleado'] . ' ' . $c['apellido_empleado'],
                                'contactos' => []
                            ];
                        }
                        $grupos[$idem]['contactos'][] = [
                            'id_contacto' => $c['id_contacto'],
                            'nombre' => $c['nombre_contacto'],
                            'relacion' => $c['relacion'],
                            'telefono' => $c['numero_telefono']
                        ];
                    }
                ?>
                <?php if (!empty($grupos)): ?>
                    <?php foreach ($grupos as $g): ?>
                        <tr>
                            <td><?= $g['id_empleado']; ?></td>
                            <td><?= htmlspecialchars($g['nombre']); ?></td>
                            <td>
                                <?php foreach ($g['contactos'] as $ct): ?>
                                    <div>
                                        <strong><?= htmlspecialchars($ct['nombre']); ?></strong>
                                        <?php if (!empty($ct['relacion'])): ?>
                                            <span class="text-muted">(<?= htmlspecialchars($ct['relacion']); ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <?php foreach ($g['contactos'] as $ct): ?>
                                    <div>
                                        <?= htmlspecialchars($ct['telefono']); ?>
                                    </div>
                                <?php endforeach; ?>
                            </td>
                            <td class="text-center">
                                <?php $contacts_json = htmlspecialchars(json_encode($g['contactos']), ENT_QUOTES, 'UTF-8'); ?>
                                <button type="button" class="btn btn-primary btn-sm editar-emp-btn"
                                    data-contacts='<?= $contacts_json; ?>'
                                    data-id_empleado="<?= $g['id_empleado']; ?>">Editar</button>
                                <button type="button" class="btn btn-danger btn-sm eliminar-emp-btn"
                                    data-contacts='<?= $contacts_json; ?>'
                                    data-id_empleado="<?= $g['id_empleado']; ?>">Eliminar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center">No hay contactos registrados</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/SistemaWebRestaurante/gestion_empleados/Contacto_Emergencias.js"></script>
</body>
</html>
