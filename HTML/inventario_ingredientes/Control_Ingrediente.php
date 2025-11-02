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
            crearControl();
            break;
        case 'actualizar':
            actualizarControl();
            break;
        case 'eliminar':
            eliminarControl();
            break;
    }
}

function crearControl() {
    global $conn;
    $conn = conectar();
    
    $id_ingrediente = intval($_POST['id_ingrediente'] ?? '');
    $estado = $_POST['estado'] ?? 'OK';
    $fecha_entrada = $_POST['fecha_entrada'] ?? '';
    $fecha_caducidad = $_POST['fecha_caducidad'] ?? null;
    
    $sql = "INSERT INTO control_ingredientes (id_ingrediente, estado, fecha_entrada, fecha_caducidad) 
            VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $id_ingrediente, $estado, $fecha_entrada, $fecha_caducidad);
    
    if ($stmt->execute()) {
        registrarBitacora(
            $conn,
            "Control Inventario Ingredientes",
            "insertar",
            "Registro insertado (Id Ingrediente: $id_ingrediente, Estado: $estado, Fecha Entrada: $fecha_entrada, Fecha Caducidad: $fecha_caducidad)"
        );
        $_SESSION['mensaje'] = "Control de ingrediente creado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al crear control: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: Control_Ingrediente.php');
    exit();
}

function actualizarControl() {
    global $conn;
    $conn = conectar();
    
    $id_control = intval($_POST['id_control'] ?? '');
    $id_ingrediente = intval($_POST['id_ingrediente'] ?? '');
    $estado = $_POST['estado'] ?? 'OK';
    $fecha_entrada = $_POST['fecha_entrada'] ?? '';
    $fecha_caducidad = $_POST['fecha_caducidad'] ?? null;
    
    $sql = "UPDATE control_ingredientes 
            SET id_ingrediente = ?, estado = ?, fecha_entrada = ?, fecha_caducidad = ? 
            WHERE id_control = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssi", $id_ingrediente, $estado, $fecha_entrada, $fecha_caducidad, $id_control);
    
    if ($stmt->execute()) {
        registrarBitacora(
            $conn,
            "Control Inventario Ingredientes",
            "Actualizar",
            "Registro actualizado (Id Ingrediente: $id_ingrediente, Estado: $estado, Fecha Entrada: $fecha_entrada, Fecha Caducidad: $fecha_caducidad)"
        );
        $_SESSION['mensaje'] = "Control de ingrediente actualizado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar control: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: Control_Ingrediente.php');
    exit();
}

function eliminarControl() {
    global $conn;
    $conn = conectar();
    
    $id_control = intval($_POST['id_control'] ?? '');
    
    $sql = "DELETE FROM control_ingredientes WHERE id_control = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_control);
    
    if ($stmt->execute()) {
        registrarBitacora(
            $conn,
            "Control Inventario Ingredientes",
            "Eliminar",
            "Registro eliminado (Id Control: $id_control)"
        );
        $_SESSION['mensaje'] = "Control de ingrediente eliminado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al eliminar control: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: Control_Ingrediente.php');
    exit();
}

function obtenerControles() {
    $conn = conectar();
    $sql = "SELECT ci.*, i.nombre_ingrediente 
            FROM control_ingredientes ci 
            LEFT JOIN ingredientes i ON ci.id_ingrediente = i.id_ingrediente 
            ORDER BY ci.fecha_entrada DESC";
    $resultado = $conn->query($sql);
    $controles = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $controles[] = $fila;
        }
    }
    
    desconectar($conn);
    return $controles;
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

$controles = obtenerControles();
$ingredientes = obtenerIngredientes();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Ingredientes - Marea Roja</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
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
        
        .badge-estado {
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 6px;
        }
        
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }
    </style>

    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
</head>

<body>
<header class="mb-4">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
        <h1 class="mb-0">CONTROL DE INGREDIENTES - MAREA ROJA</h1>
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
        <h2 class="card-title text-primary mb-4">CONTROL DE INGREDIENTES</h2>

        <form id="form-control" method="post" class="row g-3">
            <input type="hidden" id="operacion" name="operacion" value="crear">
            <input type="hidden" id="id_control" name="id_control" value="">
            
            <div class="col-md-3">
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
            
            <div class="col-md-3">
                <label class="form-label fw-semibold" for="estado">Estado: *</label>
                <select class="form-control" id="estado" name="estado" required>
                    <option value="OK">OK</option>
                    <option value="POR_VENCER">POR VENCER</option>
                    <option value="VENCIDO">VENCIDO</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label fw-semibold" for="fecha_entrada">Fecha de Entrada: *</label>
                <input type="date" class="form-control" id="fecha_entrada" name="fecha_entrada" required>
            </div>
            
            <div class="col-md-3">
                <label class="form-label fw-semibold" for="fecha_caducidad">Fecha de Caducidad:</label>
                <input type="date" class="form-control" id="fecha_caducidad" name="fecha_caducidad">
            </div>
        </form>

        <div class="d-flex gap-2 mt-4">
            <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
            <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
            <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
            <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
        </div>

        <h2 class="card-title mb-3 mt-5">LISTA DE CONTROLES</h2>
        
        <div class="table-responsive mt-3">
            <table class="table table-striped table-bordered" id="tabla-controles">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Ingrediente</th>
                        <th>Estado</th>
                        <th>Fecha Entrada</th>
                        <th>Fecha Caducidad</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($controles as $control): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($control['id_control']); ?></td>
                        <td><?php echo htmlspecialchars($control['nombre_ingrediente']); ?></td>
                        <td>
                            <span class="badge badge-estado 
                                <?php 
                                    echo $control['estado'] === 'OK' ? 'bg-success' : 
                                        ($control['estado'] === 'POR_VENCER' ? 'bg-warning' : 'bg-danger'); 
                                ?>">
                                <?php echo htmlspecialchars($control['estado']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($control['fecha_entrada']); ?></td>
                        <td><?php echo htmlspecialchars($control['fecha_caducidad'] ?? 'N/A'); ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                    data-id="<?php echo $control['id_control']; ?>"
                                    data-ingrediente="<?php echo $control['id_ingrediente']; ?>"
                                    data-estado="<?php echo $control['estado']; ?>"
                                    data-entrada="<?php echo $control['fecha_entrada']; ?>"
                                    data-caducidad="<?php echo $control['fecha_caducidad'] ?? ''; ?>">
                                Editar
                            </button>
                            <form method="post" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este control?')">
                                <input type="hidden" name="operacion" value="eliminar">
                                <input type="hidden" name="id_control" value="<?php echo $control['id_control']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger btn-action">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($controles)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No hay controles registrados</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('form-control');
        const btnNuevo = document.getElementById('btn-nuevo');
        const btnGuardar = document.getElementById('btn-guardar');
        const btnActualizar = document.getElementById('btn-actualizar');
        const btnCancelar = document.getElementById('btn-cancelar');
        const operacionInput = document.getElementById('operacion');
        const idControlInput = document.getElementById('id_control');

        document.getElementById('fecha_entrada').valueAsDate = new Date();

        btnNuevo.addEventListener('click', function() {
            limpiarFormulario();
            mostrarBotonesGuardar();
        });

        btnGuardar.addEventListener('click', function() {
            if (validarFormulario()) {
                operacionInput.value = 'crear';
                form.submit();
            }
        });

        btnActualizar.addEventListener('click', function() {
            if (validarFormulario()) {
                operacionInput.value = 'actualizar';
                form.submit();
            }
        });

        btnCancelar.addEventListener('click', function() {
            limpiarFormulario();
            mostrarBotonesGuardar();
        });

        document.querySelectorAll('.editar-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const ingrediente = this.getAttribute('data-ingrediente');
                const estado = this.getAttribute('data-estado');
                const entrada = this.getAttribute('data-entrada');
                const caducidad = this.getAttribute('data-caducidad');

                idControlInput.value = id;
                document.getElementById('id_ingrediente').value = ingrediente;
                document.getElementById('estado').value = estado;
                document.getElementById('fecha_entrada').value = entrada;
                document.getElementById('fecha_caducidad').value = caducidad;

                mostrarBotonesActualizar();
            });
        });

        function limpiarFormulario() {
            form.reset();
            idControlInput.value = '';
            operacionInput.value = 'crear';
            document.getElementById('fecha_entrada').valueAsDate = new Date();
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
            const estado = document.getElementById('estado').value;
            const entrada = document.getElementById('fecha_entrada').value;

            if (!ingrediente) {
                alert('El ingrediente es requerido');
                return false;
            }
            if (!estado) {
                alert('El estado es requerido');
                return false;
            }
            if (!entrada) {
                alert('La fecha de entrada es requerida');
                return false;
            }

            return true;
        }
    });
</script>
</body>
</html>
