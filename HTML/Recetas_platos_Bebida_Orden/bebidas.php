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
            crearBebida();
            break;
        case 'actualizar':
            actualizarBebida();
            break;
        case 'eliminar':
            eliminarBebida();
            break;
    }
}

function crearBebida() {
    global $conn;
    $conn = conectar();
    
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio_unitario = floatval($_POST['precio_unitario'] ?? 0);
    
    $sql = "INSERT INTO bebidas (descripcion, precio_unitario) 
            VALUES (?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sd", $descripcion, $precio_unitario);
    
    if ($stmt->execute()) {
        registrarBitacora(
            $conn,
            "Bebidas",
            "insertar",
            "Registro insertado (Descripción: $descripcion, Precio unitario: $precio_unitario)"
        );
        $_SESSION['mensaje'] = "Bebida creada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al crear bebida: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: bebidas.php');
    exit();
}

function actualizarBebida() {
    global $conn;
    $conn = conectar();
    
    $id_bebida = intval($_POST['id_bebida'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio_unitario = floatval($_POST['precio_unitario'] ?? 0);
    
    $sql = "UPDATE bebidas SET descripcion = ?, precio_unitario = ? 
            WHERE id_bebida = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdi", $descripcion, $precio_unitario, $id_bebida);
    
    if ($stmt->execute()) {
        registrarBitacora(
            $conn,
            "Bebidas",
            "Actualizar",
            "Registro actualizado (Descripción: $descripcion, Precio unitario: $precio_unitario)"
        );
        $_SESSION['mensaje'] = "Bebida actualizada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar bebida: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: bebidas.php');
    exit();
}

function eliminarBebida() {
    global $conn;
    $conn = conectar();
    
    $id_bebida = intval($_POST['id_bebida'] ?? '');

    // Verificar si la bebida está en uso en órdenes
    $sql_check = "SELECT id_detalle_orden FROM detalle_orden WHERE id_bebida = ? LIMIT 1";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $id_bebida);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $_SESSION['mensaje'] = "No se puede eliminar la bebida porque está siendo usada en órdenes";
        $_SESSION['tipo_mensaje'] = "error";
        $stmt_check->close();
        desconectar($conn);
        header('Location: bebidas.php');
        exit();
    }
    $stmt_check->close();

    // Eliminar registro
    $sql = "DELETE FROM bebidas WHERE id_bebida = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_bebida);

    if ($stmt->execute()) {
        registrarBitacora(
            $conn,
            "Bebidas",
            "Eliminar",
            "Registro eliminado (ID bebida: $id_bebida)"
        );
        $_SESSION['mensaje'] = "Bebida eliminada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al eliminar bebida: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }

    $stmt->close();
    desconectar($conn);
    header('Location: bebidas.php');
    exit();
}

// Obtener todas las bebidas para mostrar en la tabla
function obtenerBebidas() {
    $conn = conectar();
    $sql = "SELECT * FROM bebidas ORDER BY descripcion";
    $resultado = $conn->query($sql);
    $bebidas = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $bebidas[] = $fila;
        }
    }
    
    desconectar($conn);
    return $bebidas;
}

$bebidas = obtenerBebidas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Bebidas - Marea Roja</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
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
        
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }
        
        .badge-precio {
            background-color: #10b981;
            color: white;
        }
    </style>

    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
</head>

<body>
<header class="mb-4">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
        <h1 class="mb-0">GESTIÓN DE BEBIDAS - MAREA ROJA</h1>
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
        <h2 class="card-title text-primary mb-4">FORMULARIO DE BEBIDAS</h2>

        <form id="form-bebida" method="post" class="row g-3">
            <input type="hidden" id="operacion" name="operacion" value="crear">
            <input type="hidden" id="id_bebida" name="id_bebida" value="">
            
            <div class="col-md-6">
                <label class="form-label fw-semibold" for="descripcion">Descripción de la Bebida: *</label>
                <input type="text" class="form-control" id="descripcion" name="descripcion" 
                       required placeholder="Ej. Coca Cola 500ml, Jugo de Naranja Natural, etc." maxlength="120">
            </div>
            
            <div class="col-md-6">
                <label class="form-label fw-semibold" for="precio_unitario">Precio Unitario: *</label>
                <input type="number" class="form-control" id="precio_unitario" name="precio_unitario" 
                       required placeholder="Ej. 2.50" step="0.01" min="0">
            </div>
        </form>

        <div class="d-flex gap-2 mt-4">
            <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
            <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
            <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
            <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
        </div>

        <h2 class="card-title mb-3 mt-5">LISTA DE BEBIDAS</h2>
        
        <div class="table-responsive mt-3">
            <table class="table table-striped table-bordered" id="tabla-bebidas">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Descripción</th>
                        <th>Precio Unitario</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($bebidas as $bebida): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($bebida['id_bebida']); ?></td>
                        <td><?php echo htmlspecialchars($bebida['descripcion']); ?></td>
                        <td>
                            <span class="badge badge-precio">
                                Q<?php echo number_format($bebida['precio_unitario'], 2); ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                    data-id="<?php echo $bebida['id_bebida']; ?>"
                                    data-descripcion="<?php echo htmlspecialchars($bebida['descripcion']); ?>"
                                    data-precio="<?php echo $bebida['precio_unitario']; ?>">
                                Editar
                            </button>
                            <form method="post" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar esta bebida?')">
                                <input type="hidden" name="operacion" value="eliminar">
                                <input type="hidden" name="id_bebida" value="<?php echo $bebida['id_bebida']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger btn-action">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($bebidas)): ?>
                    <tr>
                        <td colspan="4" class="text-center">No hay bebidas registradas</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('form-bebida');
        const btnNuevo = document.getElementById('btn-nuevo');
        const btnGuardar = document.getElementById('btn-guardar');
        const btnActualizar = document.getElementById('btn-actualizar');
        const btnCancelar = document.getElementById('btn-cancelar');
        const operacionInput = document.getElementById('operacion');
        const idBebidaInput = document.getElementById('id_bebida');

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
                const descripcion = this.getAttribute('data-descripcion');
                const precio = this.getAttribute('data-precio');

                idBebidaInput.value = id;
                document.getElementById('descripcion').value = descripcion;
                document.getElementById('precio_unitario').value = precio;

                mostrarBotonesActualizar();
            });
        });

        function limpiarFormulario() {
            form.reset();
            idBebidaInput.value = '';
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
            const descripcion = document.getElementById('descripcion').value.trim();
            const precio = document.getElementById('precio_unitario').value;

            if (!descripcion) {
                alert('La descripción de la bebida es requerida');
                return false;
            }
            if (!precio || precio < 0) {
                alert('El precio unitario debe ser un número positivo');
                return false;
            }

            return true;
        }
    });
</script>
</body>
</html>
