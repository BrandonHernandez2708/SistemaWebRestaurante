<!--Ernesto David Samayoa Jocol 0901-22-3415-->
<?php
session_start();
require_once '../conexion.php';
require_once '../funciones_globales.php';

// Verificar sesión activa
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

$conn = conectar();

// ---------------------- CRUD PRINCIPAL ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operacion = $_POST['operacion'] ?? '';

    if ($operacion === 'crear' || $operacion === 'actualizar') {
        $id_empleado = intval($_POST['id_empleado'] ?? 0);
        $id_sucursal = intval($_POST['id_sucursal'] ?? 0);
        $fecha_asignacion = $_POST['fecha_asignacion'] ?? '';

        if ($id_empleado <= 0 || $id_sucursal <= 0 || empty($fecha_asignacion)) {
            $_SESSION['mensaje'] = 'Todos los campos son obligatorios.';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: Empleados_Sucursal.php');
            exit();
        }

        // Verificar si ya está asignado
        $check = $conn->prepare("SELECT COUNT(*) FROM empleado_sucursal WHERE id_empleado = ?");
        $check->bind_param('i', $id_empleado);
        $check->execute();
        $check->bind_result($total);
        $check->fetch();
        $check->close();

        if ($total > 0 && $operacion === 'crear') {
            $_SESSION['mensaje'] = 'Este empleado ya tiene una sucursal asignada.';
            $_SESSION['tipo_mensaje'] = 'warning';
            header('Location: Empleados_Sucursal.php');
            exit();
        }

        if ($operacion === 'crear') {
            $stmt = $conn->prepare("INSERT INTO empleado_sucursal (id_empleado, id_sucursal, fecha_asignacion) VALUES (?, ?, ?)");
            $stmt->bind_param('iis', $id_empleado, $id_sucursal, $fecha_asignacion);
        } else {
            $id_asignacion = intval($_POST['id_asignacion'] ?? 0);
            $stmt = $conn->prepare("UPDATE empleado_sucursal SET id_sucursal = ?, fecha_asignacion = ? WHERE id_asignacion = ?");
            $stmt->bind_param('isi', $id_sucursal, $fecha_asignacion, $id_asignacion);
        }

        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            $accion = ($operacion === 'crear') ? 'insertar' : 'Actualizar';
            $detalle = ($operacion === 'crear')
                ? "Asignación creada (Empleado ID: $id_empleado, Sucursal ID: $id_sucursal, Fecha: $fecha_asignacion)"
                : "Asignación actualizada (Asignación ID: " . intval($_POST['id_asignacion'] ?? 0) . ", Sucursal ID: $id_sucursal, Fecha: $fecha_asignacion)";
            registrarBitacora($conn, 'Empleado Sucursal', $accion, $detalle);
        }

        $_SESSION['mensaje'] = $ok ? 'Asignación guardada correctamente.' : 'Error al guardar la asignación.';
        $_SESSION['tipo_mensaje'] = $ok ? 'success' : 'error';
        header('Location: Empleados_Sucursal.php');
        exit();
    }

    if ($operacion === 'eliminar') {
        $id_asignacion = intval($_POST['id_asignacion'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM empleado_sucursal WHERE id_asignacion = ?");
        $stmt->bind_param('i', $id_asignacion);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();

        if ($ok) {
            registrarBitacora(
                $conn,
                'Empleado Sucursal',
                'Eliminar',
                "Asignación eliminada (Asignación ID: $id_asignacion)"
            );
        }

        $_SESSION['mensaje'] = $ok ? 'Asignación eliminada correctamente.' : 'Error al eliminar.';
        $_SESSION['tipo_mensaje'] = $ok ? 'success' : 'error';
        header('Location: Empleados_Sucursal.php');
        exit();
    }
}

// ---------------------- CONSULTAS ----------------------
$sql = "
SELECT es.id_asignacion, es.fecha_asignacion, 
e.id_empleado, CONCAT(e.nombre_empleado, ' ', e.apellido_empleado) AS empleado,
s.id_sucursal, s.direccion_sucursal AS sucursal,
p.puesto, d.nombre_departamento AS departamento
FROM empleado_sucursal es
INNER JOIN empleados e ON es.id_empleado = e.id_empleado
INNER JOIN puesto p ON e.id_puesto = p.id_puesto
INNER JOIN sucursales s ON es.id_sucursal = s.id_sucursal
LEFT JOIN departamentos d ON s.id_departamento = d.id_departamento
ORDER BY es.id_asignacion ASC";
$resultado = $conn->query($sql);

$empleados = $conn->query("SELECT id_empleado, CONCAT(nombre_empleado, ' ', apellido_empleado) AS nombre FROM empleados ORDER BY nombre_empleado");
$sucursales = $conn->query("SELECT id_sucursal, direccion_sucursal FROM sucursales ORDER BY direccion_sucursal");

desconectar($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Asignaciones de Empleados a Sucursales</title>
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
    <h2 class="text-primary mb-4">Formulario de Asignación</h2>

    <form id="form-asignacion" method="post" class="row g-3">
        <input type="hidden" name="operacion" id="operacion" value="crear">
        <input type="hidden" name="id_asignacion" id="id_asignacion">

        <div class="col-md-3">
            <label class="form-label">👤 Empleado</label>
            <select class="form-select" name="id_empleado" id="id_empleado" required>
                <option value="">-- Seleccione empleado --</option>
                <?php while ($emp = $empleados->fetch_assoc()): ?>
                    <option value="<?= $emp['id_empleado']; ?>"><?= htmlspecialchars($emp['nombre']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">📌Sucursal</label>
            <select class="form-select" name="id_sucursal" id="id_sucursal" required>
                <option value="">-- Seleccione sucursal --</option>
                <?php while ($s = $sucursales->fetch_assoc()): ?>
                    <option value="<?= $s['id_sucursal']; ?>"><?= htmlspecialchars($s['direccion_sucursal']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">📅 Fecha de Asignación</label>
            <input type="date" class="form-control" name="fecha_asignacion" id="fecha_asignacion" required>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
            <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
            <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
            <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
        </div>
    </form>

    <div class="mt-5">
        <h3 class="text-primary mb-3">Asignaciones Registradas</h3>
        <div class="table-responsive">
            <table class="table table-bordered table-striped text-center">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Empleado</th>
                        <th>Puesto</th>
                        <th>Sucursal</th>
                        <th>Departamento</th>
                        <th>Fecha de Asignación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($resultado->num_rows > 0): ?>
                    <?php while ($fila = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><?= $fila['id_asignacion']; ?></td>
                        <td><?= htmlspecialchars($fila['empleado']); ?></td>
                        <td><?= htmlspecialchars($fila['puesto']); ?></td>
                        <td><?= htmlspecialchars($fila['sucursal']); ?></td>
                        <td><?= htmlspecialchars($fila['departamento'] ?? '—'); ?></td>
                        <?php
                            $fechaIso = $fila['fecha_asignacion'] ?? '';
                            $fechaMostrar = ($fechaIso && $fechaIso !== '0000-00-00')
                                ? date('d/m/Y', strtotime($fechaIso))
                                : '—';
                        ?>
                        <td><?= htmlspecialchars($fechaMostrar); ?></td>
                        <td>
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button"
                                    class="btn btn-sm btn-primary editar-btn"
                                    data-id="<?= $fila['id_asignacion']; ?>"
                                    data-empleado="<?= $fila['id_empleado']; ?>"
                                    data-sucursal="<?= $fila['id_sucursal']; ?>"
                                    data-fecha="<?= $fila['fecha_asignacion']; ?>">
                                    Editar
                                </button>
                                <form method="post" class="m-0" data-eliminar="true">
                                    <input type="hidden" name="operacion" value="eliminar">
                                    <input type="hidden" name="id_asignacion" value="<?= $fila['id_asignacion']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7">No hay asignaciones registradas</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/SistemaWebRestaurante/javascript/Empleados_Sucursal.js"></script>
</body>
</html>
