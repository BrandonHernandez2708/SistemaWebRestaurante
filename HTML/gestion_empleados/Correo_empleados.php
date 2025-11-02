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
            crearCorreo();
            break;
        case 'actualizar':
            actualizarCorreo();
            break;
        case 'eliminar':
            eliminarCorreo();
            break;
    }
}

// ---------------------- FUNCIONES CRUD ----------------------
function crearCorreo() {
    $conn = conectar();
    $id_empleado = $_POST['id_empleado'] ?? '';
    $direccion_correo = trim($_POST['direccion_correo'] ?? '');

    // Validaciones
    if ($id_empleado === '' || $direccion_correo === '') {
        $_SESSION['mensaje'] = 'Todos los campos son requeridos.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Correo_empleados.php');
        exit();
    }

    // Validar formato de correo electrónico
    if (!filter_var($direccion_correo, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['mensaje'] = 'El formato del correo electrónico no es válido.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Correo_empleados.php');
        exit();
    }

    // Insertar correo
    $sql = "INSERT INTO correos_empleado (id_empleado, direccion_correo) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $id_empleado, $direccion_correo);
    $stmt->execute();

    $success = $stmt->affected_rows > 0;
    if ($success) {
        registrarBitacora(
            $conn,
            'Correos de Empleados',
            'insertar',
            "Registro insertado (Empleado ID: $id_empleado, Correo: $direccion_correo)"
        );
    }
    $_SESSION['mensaje'] = $success ? 'Correo registrado exitosamente.' : 'Error al registrar correo.';
    $_SESSION['tipo_mensaje'] = $success ? 'success' : 'error';

    $stmt->close();
    desconectar($conn);
    header('Location: Correo_empleados.php');
    exit();
}

function actualizarCorreo() {
    $conn = conectar();
    $id_correo = $_POST['id_correo'] ?? '';
    $id_empleado = $_POST['id_empleado'] ?? '';
    $direccion_correo = trim($_POST['direccion_correo'] ?? '');

    if ($id_correo === '' || $id_empleado === '' || $direccion_correo === '') {
        $_SESSION['mensaje'] = 'Todos los campos son requeridos.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Correo_empleados.php');
        exit();
    }

    if (!filter_var($direccion_correo, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['mensaje'] = 'El formato del correo electrónico no es válido.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Correo_Empleados.php');
        exit();
    }

    $sql = "UPDATE correos_empleado SET id_empleado=?, direccion_correo=? WHERE id_correo=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isi', $id_empleado, $direccion_correo, $id_correo);
    $stmt->execute();

    $success = $stmt->affected_rows > 0;
    if ($success) {
        registrarBitacora(
            $conn,
            'Correos de Empleados',
            'Actualizar',
            "Registro actualizado (ID Correo: $id_correo, Empleado ID: $id_empleado, Correo: $direccion_correo)"
        );
    }
    $_SESSION['mensaje'] = $success ? 'Correo actualizado exitosamente.' : 'No se realizaron cambios.';
    $_SESSION['tipo_mensaje'] = $success ? 'success' : 'warning';

    $stmt->close();
    desconectar($conn);
    header('Location: Correo_empleados.php');
    exit();
}

function eliminarCorreo() {
    $conn = conectar();
    $id_correo = $_POST['id_correo'] ?? '';
    $sql = "DELETE FROM correos_empleado WHERE id_correo=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_correo);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        registrarBitacora(
            $conn,
            'Correos de Empleados',
            'Eliminar',
            "Registro eliminado (ID Correo: $id_correo)"
        );
        $_SESSION['mensaje'] = 'Correo eliminado exitosamente.';
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'Error al eliminar correo.';
        $_SESSION['tipo_mensaje'] = 'error';
    }

    $stmt->close();
    desconectar($conn);
    header('Location: Correo_empleados.php');
    exit();
}

function obtenerCorreos() {
    $conn = conectar();
    // Ordenar por id_empleado asc y luego por id_correo asc para que al agrupar los empleados aparezcan en orden numérico y sus correos en orden de inserción.
    $sql = "SELECT c.id_correo, c.direccion_correo, c.id_empleado,
             e.nombre_empleado, e.apellido_empleado
         FROM correos_empleado c
         INNER JOIN empleados e ON c.id_empleado = e.id_empleado
         ORDER BY c.id_empleado ASC, c.id_correo ASC";
    $resultado = $conn->query($sql);
    $data = [];
    while ($fila = $resultado->fetch_assoc()) {
        $data[] = $fila;
    }
    desconectar($conn);
    return $data;
}

// ---------------------- MAPEO DE DATOS ----------------------
$correos = obtenerCorreos();
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
    <title>Correos de Empleados</title>
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
        <h2 class="text-primary mb-4">Formulario Correo Empleado</h2>

        <form id="form-correo" method="post" class="row g-3">
            <input type="hidden" name="operacion" id="operacion" value="crear">
            <input type="hidden" name="id_correo" id="id_correo">

            <div class="col-md-4">
                <label class="form-label">👤 Empleado:</label>
                <select class="form-select" name="id_empleado" id="id_empleado" required>
                    <option value="">-- Seleccione empleado --</option>
                    <?php foreach ($empleados_map as $id => $nombre): ?>
                        <option value="<?= $id; ?>"><?= $id . ' - ' . htmlspecialchars($nombre); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">📧 Correo Electrónico:</label>
                <input type="email" class="form-control" name="direccion_correo" id="direccion_correo" required placeholder="Ej: juanperez25@gmail.com">
                <small class="form-text text-muted help-text">*usuario@gmail.com</small>
            
            </div>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>
        </form>

        <div class="d-flex justify-content-between align-items-center mt-5 mb-3">
            <h3 class="mb-0">Lista de Correos</h3>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Empleado</th>
                        <th>Correos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $grupos = [];
                    foreach ($correos as $c) {
                        $idem = $c['id_empleado'];
                        if (!isset($grupos[$idem])) {
                            $grupos[$idem] = [
                                'id_empleado' => $idem,
                                'nombre' => $c['nombre_empleado'] . ' ' . $c['apellido_empleado'],
                                'correos' => []
                            ];
                        }
                        $grupos[$idem]['correos'][] = [
                            'id_correo' => $c['id_correo'],
                            'direccion' => $c['direccion_correo']
                        ];
                    }

                    if (!empty($grupos)):
                        foreach ($grupos as $g):
                    ?>
                        <tr>
                            <td><?= $g['id_empleado']; ?></td>
                            <td><?= htmlspecialchars($g['nombre']); ?></td>
                            <td>
                                <?php foreach ($g['correos'] as $cr): ?>
                                    <div>
                                        <a class="correo-empleado" href="mailto:<?= htmlspecialchars($cr['direccion']); ?>">
                                            <?= htmlspecialchars($cr['direccion']); ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </td>
                            <td class="text-center">
                                <?php
                                $correos_json = htmlspecialchars(json_encode($g['correos']), ENT_QUOTES, 'UTF-8');
                                ?>
                                <button type="button" class="btn btn-primary btn-sm editar-emp-btn"
                                    data-correos='<?= $correos_json; ?>'
                                    data-id_empleado="<?= $g['id_empleado']; ?>">Editar</button>
                                <button type="button" class="btn btn-danger btn-sm eliminar-emp-btn"
                                    data-correos='<?= $correos_json; ?>'
                                    data-id_empleado="<?= $g['id_empleado']; ?>">Eliminar</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center">No hay correos registrados</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/SistemaWebRestaurante/javascript/Correo_Empleados.js"></script>
</body>
</html>
