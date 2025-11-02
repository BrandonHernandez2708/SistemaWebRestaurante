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
            crearPerdida();
            break;
        case 'actualizar':
            actualizarPerdida();
            break;
        case 'eliminar':
            eliminarPerdida();
            break;
    }
}

function crearPerdida() {
    global $conn;
    $conn = conectar();
    
    $id_ingrediente = intval($_POST['id_ingrediente'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $cantidad_unitaria_perdida = floatval($_POST['cantidad_unitaria_perdida'] ?? 0);
    $costo_perdida = floatval($_POST['costo_perdida'] ?? 0);
    
    $sql = "INSERT INTO perdidas_inventario (id_ingrediente, descripcion, cantidad_unitaria_perdida, costo_perdida) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isdd", $id_ingrediente, $descripcion, $cantidad_unitaria_perdida, $costo_perdida);
    
    if ($stmt->execute()) {
        registrarBitacora(
            $conn,
            "Perdida Inventario Ingredientes",
            "insertar",
            "Registro insertado (Id Ingrediente: $id_ingrediente, Descripción: $descripcion, Cantidad: $cantidad_unitaria_perdida, Costo: $costo_perdida)"
        );
        $_SESSION['mensaje'] = "Pérdida registrada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al registrar pérdida: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: Perdida_Ingrediente.php');
    exit();
}

function actualizarPerdida() {
    global $conn;
    $conn = conectar();
    
    $id_perdida = intval($_POST['id_perdida'] ?? '');
    $id_ingrediente = intval($_POST['id_ingrediente'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $cantidad_unitaria_perdida = floatval($_POST['cantidad_unitaria_perdida'] ?? 0);
    $costo_perdida = floatval($_POST['costo_perdida'] ?? 0);
    
    $sql = "UPDATE perdidas_inventario 
            SET id_ingrediente = ?, descripcion = ?, cantidad_unitaria_perdida = ?, costo_perdida = ? 
            WHERE id_perdida = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isddi", $id_ingrediente, $descripcion, $cantidad_unitaria_perdida, $costo_perdida, $id_perdida);
    
    if ($stmt->execute()) {
        registrarBitacora(
            $conn,
            "Perdida Inventario Ingredientes",
            "Actualizar",
            "Registro actualizado (Id Ingrediente: $id_ingrediente, Descripción: $descripcion, Cantidad: $cantidad_unitaria_perdida, Costo: $costo_perdida)"
        );
        $_SESSION['mensaje'] = "Pérdida actualizada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar pérdida: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: Perdida_Ingrediente.php');
    exit();
}

function eliminarPerdida() {
    global $conn;
    $conn = conectar();
    
    $id_perdida = intval($_POST['id_perdida'] ?? '');
    
    $sql = "DELETE FROM perdidas_inventario WHERE id_perdida = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_perdida);
    
    if ($stmt->execute()) {
        registrarBitacora(
            $conn,
            "Perdida Inventario Ingredientes",
            "Eliminar",
            "Registro eliminado (Id Pérdida: $id_perdida)"
        );
        $_SESSION['mensaje'] = "Pérdida eliminada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al eliminar pérdida: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: Perdida_Ingrediente.php');
    exit();
}

function obtenerPerdidas() {
    $conn = conectar();
    $sql = "SELECT p.*, i.nombre_ingrediente 
            FROM perdidas_inventario p 
            LEFT JOIN ingredientes i ON p.id_ingrediente = i.id_ingrediente 
            ORDER BY p.id_perdida DESC";
    $resultado = $conn->query($sql);
    $perdidas = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $perdidas[] = $fila;
        }
    }
    
    desconectar($conn);
    return $perdidas;
}

function obtenerIngredientes() {
    $conn = conectar();
    $sql = "SELECT id_ingrediente, nombre_ingrediente FROM ingredientes ORDER BY nombre_ingrediente";
    $resultado = $conn->query($sql);
    $ingredientes = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $ingredientes[] = $fila;
        }
    }
    
    desconectar($conn);
    return $ingredientes;
}

$perdidas = obtenerPerdidas();
$ingredientes = obtenerIngredientes();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pérdidas de Inventario - Marea Roja</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body, h1, h2, h3, h4, h5, h6, label, input, button, table, th, td {
            font-family: 'Poppins', Arial, Helvetica, sans-serif !important;
        }
        .mensaje {
            padding: 12px;
            margin: 10px 0;
            border-radius: 8px;
            font-weight: 500;
        }
        .mensaje.success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .mensaje.error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .btn-action {
            margin: 1px;
            font-size: 0.875rem;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .table th {
            background-color: #1e40af;
            color: white;
            font-weight: 600;
        }
        .costo-alto {
            color: #dc3545;
            font-weight: bold;
        }
    </style>

    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
</head>

<body>
<header class="mb-4">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
        <h1 class="mb-0">PÉRDIDAS DE INVENTARIO - MAREA ROJA</h1>
        <ul class="nav nav-pills gap-2 mb-0">
            <li class="nav-item"><a href="../menu_empleados.php" class="nav-link">Regresar al Menú</a></li>
        </ul>
    </div>
</header>

<main class="container my-4">
    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="mensaje <?php echo $_SESSION['tipo_mensaje']; ?>">
            <?php 
            echo htmlspecialchars($_SESSION['mensaje']); 
            unset($_SESSION['mensaje']);
            unset($_SESSION['tipo_mensaje']);
            ?>
        </div>
    <?php endif; ?>

    <section class="card shadow p-4">
        <h2 class="card-title text-primary mb-4">REGISTRO DE PÉRDIDAS</h2>

        <form id="form-perdida" method="post" class="row g-3">
            <input type="hidden" id="operacion" name="operacion" value="crear">
            <input type="hidden" id="id_perdida" name="id_perdida" value="">
            
            <div class="col-md-4">
                <label class="form-label fw-semibold" for="id_ingrediente">Ingrediente: *</label>
                <select class="form-control" id="id_ingrediente" name="id_ingrediente" required>
                    <option value="">Seleccione un ingrediente</option>
                    <?php foreach($ingredientes as $ingrediente): ?>
                        <option value="<?php echo $ingrediente['id_ingrediente']; ?>">
                            <?php echo htmlspecialchars($ingrediente['nombre_ingrediente']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-4">
                <label class="form-label fw-semibold" for="descripcion">Descripción: *</label>
                <input type="text" class="form-control" id="descripcion" name="descripcion" required maxlength="200">
            </div>
            
            <div class="col-md-2">
                <label class="form-label fw-semibold" for="cantidad_unitaria_perdida">Cantidad Unitaria: *</label>
                <input type="number" class="form-control" id="cantidad_unitaria_perdida" name="cantidad_unitaria_perdida" required step="0.001" min="0">
            </div>
            
            <div class="col-md-2">
                <label class="form-label fw-semibold" for="costo_perdida">Costo (Q): *</label>
                <input type="number" class="form-control" id="costo_perdida" name="costo_perdida" required step="0.01" min="0">
            </div>
        </form>

        <div class="d-flex gap-2 mt-4">
            <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
            <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
            <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
            <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
        </div>

        <h2 class="card-title mb-3 mt-5">HISTORIAL DE PÉRDIDAS</h2>
        
        <div class="table-responsive mt-3">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Ingrediente</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>Costo (Q)</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($perdidas as $perdida): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($perdida['id_perdida']); ?></td>
                        <td><?php echo htmlspecialchars($perdida['nombre_ingrediente']); ?></td>
                        <td><?php echo htmlspecialchars($perdida['descripcion']); ?></td>
                        <td><?php echo number_format($perdida['cantidad_unitaria_perdida'], 3); ?></td>
                        <td class="<?php echo $perdida['costo_perdida'] > 100 ? 'costo-alto' : ''; ?>">
                            Q <?php echo number_format($perdida['costo_perdida'], 2); ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                    data-id="<?php echo $perdida['id_perdida']; ?>"
                                    data-ingrediente="<?php echo $perdida['id_ingrediente']; ?>"
                                    data-descripcion="<?php echo htmlspecialchars($perdida['descripcion']); ?>"
                                    data-cantidad="<?php echo $perdida['cantidad_unitaria_perdida']; ?>"
                                    data-costo="<?php echo $perdida['costo_perdida']; ?>">
                                Editar
                            </button>
                            <form method="post" style="display:inline;" onsubmit="return confirm('¿Desea eliminar este registro?')">
                                <input type="hidden" name="operacion" value="eliminar">
                                <input type="hidden" name="id_perdida" value="<?php echo $perdida['id_perdida']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger btn-action">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($perdidas)): ?>
                    <tr><td colspan="6" class="text-center">No hay pérdidas registradas</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-perdida');
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idPerdidaInput = document.getElementById('id_perdida');

    btnNuevo.addEventListener('click', () => {
        limpiarFormulario();
        mostrarBotonesGuardar();
    });

    btnGuardar.addEventListener('click', () => {
        if (validarFormulario()) {
            operacionInput.value = 'crear';
            form.submit();
        }
    });

    btnActualizar.addEventListener('click', () => {
        if (validarFormulario()) {
            operacionInput.value = 'actualizar';
            form.submit();
        }
    });

    btnCancelar.addEventListener('click', () => {
        limpiarFormulario();
        mostrarBotonesGuardar();
    });

    document.querySelectorAll('.editar-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            idPerdidaInput.value = btn.dataset.id;
            document.getElementById('id_ingrediente').value = btn.dataset.ingrediente;
            document.getElementById('descripcion').value = btn.dataset.descripcion;
            document.getElementById('cantidad_unitaria_perdida').value = btn.dataset.cantidad;
            document.getElementById('costo_perdida').value = btn.dataset.costo;
            mostrarBotonesActualizar();
        });
    });

    function limpiarFormulario() {
        form.reset();
        idPerdidaInput.value = '';
        operacionInput.value = 'crear';
    }

    function mostrarBotonesGuardar() {
        btnGuardar.style.display = 'inline-block';
        btnActualizar.style.display = 'none';
        btnCancelar.style.display = 'none';
    }

    function mostrarBotonesActualizar() {
        btnGuardar.style.display = 'none';
        btnActualizar.style.display = 'inline-block';
        btnCancelar.style.display = 'inline-block';
    }

    function validarFormulario() {
        const ingrediente = document.getElementById('id_ingrediente').value;
        const descripcion = document.getElementById('descripcion').value.trim();
        const cantidad = document.getElementById('cantidad_unitaria_perdida').value;
        const costo = document.getElementById('costo_perdida').value;

        if (!ingrediente) return alert('El ingrediente es requerido'), false;
        if (!descripcion) return alert('La descripción es requerida'), false;
        if (!cantidad || cantidad <= 0) return alert('La cantidad debe ser positiva'), false;
        if (!costo || costo < 0) return alert('El costo debe ser positivo'), false;

        return true;
    }
});
</script>
</body>
</html>
