<?php
session_start();
require_once '../conexion.php';
require_once '../funciones_globales.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// Procesar operaciones CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operacion = $_POST['operacion'] ?? '';
    
    switch($operacion) {
        case 'crear':
            crearReceta();
            break;
        case 'actualizar':
            actualizarReceta();
            break;
        case 'eliminar':
            eliminarReceta();
            break;
    }
}

function crearReceta() {
    $conn = conectar();
    
    $id_plato = intval($_POST['id_plato'] ?? 0);
    $id_ingrediente = intval($_POST['id_ingrediente'] ?? 0);
    $id_unidad = intval($_POST['id_unidad'] ?? 0);
    $cantidad = floatval($_POST['cantidad_por_plato'] ?? 0);

    $sql = "INSERT INTO receta (id_plato, id_ingrediente, id_unidad, cantidad_por_plato) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiid", $id_plato, $id_ingrediente, $id_unidad, $cantidad);

    if ($stmt->execute()) {
         registrarBitacora(
        $conn,
        "Recetas",
        "insertar",
        "Registro #$id_plato insertado (Id_ingrediente: $id_ingrediente, unidad de medida : $id_unidad,cantidad: $cantidad)");
        $_SESSION['mensaje'] = "Receta creada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al crear receta: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }

    $stmt->close();
    desconectar($conn);
    header('Location: recetas.php');
    exit();
}

function actualizarReceta() {
    $conn = conectar();
    
    $id_registro_receta = intval($_POST['id_registro_receta'] ?? 0);
    $id_plato = intval($_POST['id_plato'] ?? 0);
    $id_ingrediente = intval($_POST['id_ingrediente'] ?? 0);
    $id_unidad = intval($_POST['id_unidad'] ?? 0);
    $cantidad = floatval($_POST['cantidad_por_plato'] ?? 0);

    $sql = "UPDATE receta 
            SET id_plato = ?, id_ingrediente = ?, id_unidad = ?, cantidad_por_plato = ? 
            WHERE id_registro_receta = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiidi", $id_plato, $id_ingrediente, $id_unidad, $cantidad, $id_registro_receta);

    if ($stmt->execute()) {
        registrarBitacora(
        $conn,
        "Recetas",
        "Actualizar",
        "Registro #$id_plato Actualizado (Id_ingrediente: $id_ingrediente, unidad de medida : $id_unidad,cantidad: $cantidad)");
        $_SESSION['mensaje'] = "Receta actualizada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar receta: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }

    $stmt->close();
    desconectar($conn);
    header('Location: recetas.php');
    exit();
}

function eliminarReceta() {
    $conn = conectar();
    $id_registro_receta = intval($_POST['id_registro_receta'] ?? 0);

    $sql = "DELETE FROM receta WHERE id_registro_receta = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_registro_receta);

    if ($stmt->execute()) {
              registrarBitacora(
        $conn,
        "Recetas",
        "Eliminar",
        "Registro #$id_registro_receta Eliminado");
        $_SESSION['mensaje'] = "Receta eliminada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al eliminar receta: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }

    $stmt->close();
    desconectar($conn);
    header('Location: recetas.php');
    exit();
}

function obtenerRecetas() {
    $conn = conectar();
    $sql = "SELECT r.*, p.nombre_plato, i.nombre_ingrediente, um.unidad, um.abreviatura 
            FROM receta r 
            LEFT JOIN platos p ON r.id_plato = p.id_plato 
            LEFT JOIN ingredientes i ON r.id_ingrediente = i.id_ingrediente 
            LEFT JOIN unidades_medida um ON r.id_unidad = um.id_unidad 
            ORDER BY p.nombre_plato, i.nombre_ingrediente";
    $res = $conn->query($sql);
    $recetas = [];
    if ($res && $res->num_rows > 0) {
        while($fila = $res->fetch_assoc()) {
            $recetas[] = $fila;
        }
    }
    desconectar($conn);
    return $recetas;
}

function obtenerPlatos() {
    $conn = conectar();
    $sql = "SELECT * FROM platos ORDER BY nombre_plato";
    $res = $conn->query($sql);
    $datos = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    desconectar($conn);
    return $datos;
}

function obtenerIngredientes() {
    $conn = conectar();
    $sql = "SELECT * FROM ingredientes ORDER BY nombre_ingrediente";
    $res = $conn->query($sql);
    $datos = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    desconectar($conn);
    return $datos;
}

function obtenerUnidadesMedida() {
    $conn = conectar();
    $sql = "SELECT * FROM unidades_medida ORDER BY unidad";
    $res = $conn->query($sql);
    $datos = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    desconectar($conn);
    return $datos;
}

$recetas = obtenerRecetas();
$platos = obtenerPlatos();
$ingredientes = obtenerIngredientes();
$unidades = obtenerUnidadesMedida();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Recetas - Marea Roja</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
    <style>
        body, input, select { font-family: 'Poppins', sans-serif; }
        .mensaje { padding:10px; border-radius:5px; margin:10px 0; }
        .mensaje.success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
        .mensaje.error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
        .table th { background:#0d6efd; color:white; }
    </style>
</head>
<body>
<header class="mb-4">
    <div class="container d-flex justify-content-between align-items-center py-3">
        <h1 class="mb-0">GESTIÓN DE RECETAS - MAREA ROJA</h1>
        <a href="../menu_empleados.php" class="btn btn-outline-dark">Regresar</a>
    </div>
</header>

<main class="container my-4">
    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="mensaje <?= $_SESSION['tipo_mensaje']; ?>">
            <?= htmlspecialchars($_SESSION['mensaje']); ?>
        </div>
        <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
    <?php endif; ?>

    <section class="card shadow p-4">
        <h2 class="mb-3 text-primary"><i class="bi bi-journal-text me-2"></i>Formulario de Recetas</h2>

        <form id="form-receta" method="post" class="row g-3">
            <input type="hidden" name="operacion" id="operacion" value="crear">
            <input type="hidden" name="id_registro_receta" id="id_registro_receta">

            <div class="col-md-3">
                <label class="form-label">Plato *</label>
                <select class="form-control" id="id_plato" name="id_plato" required>
                    <option value="">Seleccione...</option>
                    <?php foreach($platos as $p): ?>
                        <option value="<?= $p['id_plato'] ?>"><?= htmlspecialchars($p['nombre_plato']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Ingrediente *</label>
                <select class="form-control" id="id_ingrediente" name="id_ingrediente" required>
                    <option value="">Seleccione...</option>
                    <?php foreach($ingredientes as $i): ?>
                        <option value="<?= $i['id_ingrediente'] ?>"><?= htmlspecialchars($i['nombre_ingrediente']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Unidad *</label>
                <select class="form-control" id="id_unidad" name="id_unidad" required>
                    <option value="">Seleccione...</option>
                    <?php foreach($unidades as $u): ?>
                        <option value="<?= $u['id_unidad'] ?>"><?= htmlspecialchars($u['unidad'].' ('.$u['abreviatura'].')') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Cantidad por Plato *</label>
                <input type="number" step="0.001" min="0.001" class="form-control" id="cantidad_por_plato" name="cantidad_por_plato" required placeholder="Ej: 200 (gramos)">
            </div>
        </form>

        <div class="mt-3">
            <button id="btn-nuevo" class="btn btn-secondary">Nuevo</button>
            <button id="btn-guardar" class="btn btn-success">Guardar</button>
            <button id="btn-actualizar" class="btn btn-warning d-none">Actualizar</button>
            <button id="btn-cancelar" class="btn btn-danger d-none">Cancelar</button>
        </div>

        <h2 class="mt-5 mb-3 text-primary"><i class="bi bi-list-ul me-2"></i>Listado de Recetas</h2>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>ID</th><th>Plato</th><th>Ingrediente</th><th>Unidad</th><th>Cantidad</th><th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recetas): foreach($recetas as $r): ?>
                    <tr>
                        <td><?= $r['id_registro_receta'] ?></td>
                        <td><?= htmlspecialchars($r['nombre_plato']) ?></td>
                        <td><?= htmlspecialchars($r['nombre_ingrediente']) ?></td>
                        <td><?= htmlspecialchars($r['unidad'].' ('.$r['abreviatura'].')') ?></td>
                        <td><?= htmlspecialchars($r['cantidad_por_plato']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary editar-btn"
                                data-id="<?= $r['id_registro_receta'] ?>"
                                data-plato="<?= $r['id_plato'] ?>"
                                data-ingrediente="<?= $r['id_ingrediente'] ?>"
                                data-unidad="<?= $r['id_unidad'] ?>"
                                data-cantidad="<?= $r['cantidad_por_plato'] ?>">Editar</button>
                            <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar esta receta?')">
                                <input type="hidden" name="operacion" value="eliminar">
                                <input type="hidden" name="id_registro_receta" value="<?= $r['id_registro_receta'] ?>">
                                <button class="btn btn-sm btn-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="6" class="text-center">No hay recetas registradas</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-receta');
    const op = document.getElementById('operacion');
    const id = document.getElementById('id_registro_receta');
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');

    btnNuevo.onclick = () => limpiar();
    btnGuardar.onclick = () => { op.value='crear'; form.submit(); };
    btnActualizar.onclick = () => { op.value='actualizar'; form.submit(); };
    btnCancelar.onclick = () => limpiar();

    document.querySelectorAll('.editar-btn').forEach(b=>{
        b.onclick=()=>{
            id.value=b.dataset.id;
            document.getElementById('id_plato').value=b.dataset.plato;
            document.getElementById('id_ingrediente').value=b.dataset.ingrediente;
            document.getElementById('id_unidad').value=b.dataset.unidad;
            document.getElementById('cantidad_por_plato').value=b.dataset.cantidad;
            btnGuardar.classList.add('d-none');
            btnActualizar.classList.remove('d-none');
            btnCancelar.classList.remove('d-none');
        };
    });

    function limpiar(){
        form.reset();
        id.value='';
        op.value='crear';
        btnGuardar.classList.remove('d-none');
        btnActualizar.classList.add('d-none');
        btnCancelar.classList.add('d-none');
    }
});
</script>
</body>
</html>
