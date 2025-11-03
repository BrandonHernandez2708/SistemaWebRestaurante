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
            crearTelefono();
            break;
        case 'actualizar':
            actualizarTelefono();
            break;
        case 'eliminar':
            eliminarTelefono();
            break;
    }
}

// ---------------------- FUNCIONES CRUD ----------------------
function crearTelefono() {
    $conn = conectar();
    $id_empleado = $_POST['id_empleado'] ?? '';
    $numero_telefono = trim($_POST['numero_telefono'] ?? '');

    // Validaciones
    if ($id_empleado === '' || $numero_telefono === '') {
        $_SESSION['mensaje'] = 'Todos los campos son requeridos.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Telefono_Empleados.php');
        exit();
    }

    // Validar formato de número telefónico (solo dígitos, y - espacios permitidos)
    if (!preg_match('/^[0-9+\-\s]{7,20}$/', $numero_telefono)) {
        $_SESSION['mensaje'] = 'El número de teléfono no es válido.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Telefono_Empleados.php');
        exit();
    }

    // Insertar teléfono
    $sql = "INSERT INTO telefono_empleados (id_empleado, numero_telefono) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $id_empleado, $numero_telefono);
    $stmt->execute();

    $success = $stmt->affected_rows > 0;
    if ($success) {
        registrarBitacora(
            $conn,
            'Teléfonos de Empleados',
            'insertar',
            "Registro insertado (Empleado ID: $id_empleado, Teléfono: $numero_telefono)"
        );
    }
    $message = $success ? 'Teléfono registrado exitosamente.' : 'Error al registrar teléfono.';
    $tipo = $success ? 'success' : 'error';

    $stmt->close();
    desconectar($conn);

    $_SESSION['mensaje'] = $message;
    $_SESSION['tipo_mensaje'] = $tipo;
    header('Location: Telefono_Empleados.php');
    exit();
}

function actualizarTelefono() {
    $conn = conectar();
    $id_telefono = $_POST['id_telefono'] ?? '';
    $id_empleado = $_POST['id_empleado'] ?? '';
    $numero_telefono = trim($_POST['numero_telefono'] ?? '');

    if ($id_telefono === '' || $id_empleado === '' || $numero_telefono === '') {
        $_SESSION['mensaje'] = 'Todos los campos son requeridos.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Telefono_Empleados.php');
        exit();
    }

    if (!preg_match('/^[0-9+\-\s]{7,20}$/', $numero_telefono)) {
        $_SESSION['mensaje'] = 'El número de teléfono no es válido.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Telefono_Empleados.php');
        exit();
    }

    $sql = "UPDATE telefono_empleados SET id_empleado=?, numero_telefono=? WHERE id_telefono=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isi', $id_empleado, $numero_telefono, $id_telefono);
    $stmt->execute();

    $success = $stmt->affected_rows > 0;
    if ($success) {
        registrarBitacora(
            $conn,
            'Teléfonos de Empleados',
            'Actualizar',
            "Registro actualizado (ID Teléfono: $id_telefono, Empleado ID: $id_empleado, Teléfono: $numero_telefono)"
        );
    }
    $message = $success ? 'Teléfono actualizado exitosamente.' : 'No se realizaron cambios.';
    $tipo = $success ? 'success' : 'warning';

    $stmt->close();
    desconectar($conn);

    $_SESSION['mensaje'] = $message;
    $_SESSION['tipo_mensaje'] = $tipo;
    header('Location: Telefono_Empleados.php');
    exit();
}

function eliminarTelefono() {
    $conn = conectar();
    $id_telefono = $_POST['id_telefono'] ?? '';
    $sql = "DELETE FROM telefono_empleados WHERE id_telefono=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_telefono);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        registrarBitacora(
            $conn,
            'Teléfonos de Empleados',
            'Eliminar',
            "Registro eliminado (ID Teléfono: $id_telefono)"
        );
        $_SESSION['mensaje'] = 'Teléfono eliminado exitosamente.';
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'Error al eliminar teléfono.';
        $_SESSION['tipo_mensaje'] = 'error';
    }

    $stmt->close();
    desconectar($conn);
    header('Location: Telefono_Empleados.php');
    exit();
}

function obtenerTelefonos() {
    $conn = conectar();
    // Ordenar primero por id_empleado (asc) y luego por id_telefono para mantener el orden lógico por empleado conforme se agregan los registros.
    $sql = "SELECT t.id_telefono, t.numero_telefono, t.id_empleado, 
             e.nombre_empleado, e.apellido_empleado
         FROM telefono_empleados t
         INNER JOIN empleados e ON t.id_empleado = e.id_empleado
         ORDER BY t.id_empleado ASC, t.id_telefono ASC";
    $resultado = $conn->query($sql);
    $data = [];
    while ($fila = $resultado->fetch_assoc()) {
        $data[] = $fila;
    }
    desconectar($conn);
    return $data;
}

// ---------------------- MAPEO DE DATOS ----------------------
$telefonos = obtenerTelefonos();
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
    <title>Teléfonos de Empleados</title>
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
                    <span class="arrow">←</span>
                    <span>Regresar al Menú</span>
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
        <h2 class="text-primary mb-4">Formulario Teléfono de Empleados</h2>

        <form id="form-telefono" method="post" class="row g-3">
            <input type="hidden" name="operacion" id="operacion" value="crear">
            <input type="hidden" name="id_telefono" id="id_telefono">

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
                <label class="form-label">📞 Número de teléfono:</label>
                <input type="text" class="form-control" name="numero_telefono" id="numero_telefono" required placeholder="Ej: 1234-5678">
                <small class="form-text text-muted help-text">*8 dígitos</small>

            </div>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="submit" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>
        </form>

        <div class="d-flex justify-content-between align-items-center mt-5 mb-3">
            <h3 class="mb-0">Lista de Teléfonos</h3>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Empleado</th>
                        <th>Número</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Agrupar teléfonos por empleado para mostrar todos los números en una sola fila por empleado
                    $grupos = [];
                    foreach ($telefonos as $t) {
                        $idem = $t['id_empleado'];
                        if (!isset($grupos[$idem])) {
                            $grupos[$idem] = [
                                'id_empleado' => $idem,
                                'nombre' => $t['nombre_empleado'] . ' ' . $t['apellido_empleado'],
                                'telefonos' => []
                            ];
                        }
                        $grupos[$idem]['telefonos'][] = [
                            'id_telefono' => $t['id_telefono'],
                            'numero' => $t['numero_telefono']
                        ];
                    }

                    if (!empty($grupos)):
                        foreach ($grupos as $g):
                    ?>
                        <tr>
                            <td><?= $g['id_empleado']; ?></td>
                            <td><?= htmlspecialchars($g['nombre']); ?></td>
                            <td>
                                <?php foreach ($g['telefonos'] as $p): ?>
                                    <div><?= htmlspecialchars($p['numero']); ?></div>
                                <?php endforeach; ?>
                            </td>
                            <td class="text-center">
                                <?php
                                    // Preparar JSON con los teléfonos para el atributo data-phones
                                    $phones_json = htmlspecialchars(json_encode($g['telefonos']), ENT_QUOTES, 'UTF-8');
                                ?>
                                <button type="button" class="btn btn-primary btn-sm editar-emp-btn"
                                    data-phones='<?= $phones_json; ?>'
                                    data-id_empleado="<?= $g['id_empleado']; ?>">Editar</button>
                                <button type="button" class="btn btn-danger btn-sm eliminar-emp-btn"
                                    data-phones='<?= $phones_json; ?>'
                                    data-id_empleado="<?= $g['id_empleado']; ?>">Eliminar</button>
                            </td>
                        </tr>
                        <?php
                        endforeach;
                    else:
                        ?>
                        <tr><td colspan="4" class="text-center">No hay teléfonos registrados</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="./Telefono_Empleados.js"></script>
</body>
</html>
