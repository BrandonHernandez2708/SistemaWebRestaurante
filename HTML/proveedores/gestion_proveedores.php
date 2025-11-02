<?php
session_start();
require_once '../conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// Procesar operaciones CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operacion = $_POST['operacion'] ?? '';
    
    switch($operacion) {
        case 'crear_proveedor':
            crearProveedor();
            break;
        case 'actualizar_proveedor':
            actualizarProveedor();
            break;
        case 'eliminar_proveedor':
            eliminarProveedor();
            break;
    }
}

function crearProveedor() {
    global $conn;
    $conn = conectar();
    
    $nombre_proveedor = $_POST['nombre_proveedor'] ?? '';
    $correo_proveedor = $_POST['correo_proveedor'] ?? '';
    $telefono_proveedor = $_POST['telefono_proveedor'] ?? '';
    
    $sql = "INSERT INTO proveedores (nombre_proveedor, correo_proveedor, telefono_proveedor) 
            VALUES (?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $nombre_proveedor, $correo_proveedor, $telefono_proveedor);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Proveedor creado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al crear proveedor: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: gestion_proveedores.php');
    exit();
}

function actualizarProveedor() {
    global $conn;
    $conn = conectar();
    
    $id_proveedor = $_POST['id_proveedor'] ?? '';
    $nombre_proveedor = $_POST['nombre_proveedor'] ?? '';
    $correo_proveedor = $_POST['correo_proveedor'] ?? '';
    $telefono_proveedor = $_POST['telefono_proveedor'] ?? '';
    
    $sql = "UPDATE proveedores SET nombre_proveedor = ?, correo_proveedor = ?, telefono_proveedor = ? 
            WHERE id_proveedor = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $nombre_proveedor, $correo_proveedor, $telefono_proveedor, $id_proveedor);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Proveedor actualizado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar proveedor: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: gestion_proveedores.php');
    exit();
}

function eliminarProveedor() {
    global $conn;
    $conn = conectar();
    
    $id_proveedor = $_POST['id_proveedor'] ?? '';
    
    // Validar que el ID no esté vacío
    if (empty($id_proveedor)) {
        $_SESSION['mensaje'] = "Error: No se proporcionó un ID de proveedor válido.";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: gestion_proveedores.php');
        exit();
    }
    
    try {
        // Primero verificar si el proveedor existe
        $check_proveedor = $conn->prepare("SELECT id_proveedor FROM proveedores WHERE id_proveedor = ?");
        if (!$check_proveedor) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }
        
        $check_proveedor->bind_param("i", $id_proveedor);
        
        if (!$check_proveedor->execute()) {
            throw new Exception("Error al ejecutar la consulta: " . $check_proveedor->error);
        }
        
        $result_proveedor = $check_proveedor->get_result();
        
        if ($result_proveedor->num_rows === 0) {
            $_SESSION['mensaje'] = "Error: El proveedor que intenta eliminar no existe en el sistema.";
            $_SESSION['tipo_mensaje'] = "error";
            $check_proveedor->close();
            desconectar($conn);
            header('Location: gestion_proveedores.php');
            exit();
        }
        $check_proveedor->close();
        
        // Verificar si existe la tabla compras_mobiliario y si tiene relación con proveedores
        $check_tabla_compras = $conn->query("SHOW TABLES LIKE 'compras_mobiliario'");
        if ($check_tabla_compras && $check_tabla_compras->num_rows > 0) {
            // La tabla existe, verificar si hay columnas que referencien proveedores
            $check_columnas = $conn->query("SHOW COLUMNS FROM compras_mobiliario");
            $tiene_relacion = false;
            $columna_relacion = '';
            
            while ($columna = $check_columnas->fetch_assoc()) {
                if (strpos($columna['Field'], 'proveedor') !== false || 
                    strpos($columna['Field'], 'id_proveedor') !== false) {
                    $tiene_relacion = true;
                    $columna_relacion = $columna['Field'];
                    break;
                }
            }
            
            if ($tiene_relacion && !empty($columna_relacion)) {
                // Verificar si hay registros relacionados
                $check_relacion = $conn->prepare("SELECT COUNT(*) as count FROM compras_mobiliario WHERE {$columna_relacion} = ?");
                if ($check_relacion) {
                    $check_relacion->bind_param("i", $id_proveedor);
                    $check_relacion->execute();
                    $result_relacion = $check_relacion->get_result();
                    $row_relacion = $result_relacion->fetch_assoc();
                    $check_relacion->close();
                    
                    if ($row_relacion['count'] > 0) {
                        $_SESSION['mensaje'] = "No se puede eliminar el proveedor porque está siendo utilizado en compras de mobiliario (" . $row_relacion['count'] . " registros relacionados). Primero debe eliminar o modificar los registros relacionados en las compras.";
                        $_SESSION['tipo_mensaje'] = "error";
                        desconectar($conn);
                        header('Location: gestion_proveedores.php');
                        exit();
                    }
                }
            }
        }
        
        // Si no hay referencias, proceder con la eliminación
        $sql = "DELETE FROM proveedores WHERE id_proveedor = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta de eliminación: " . $conn->error);
        }
        
        $stmt->bind_param("i", $id_proveedor);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['mensaje'] = "Proveedor eliminado exitosamente";
                $_SESSION['tipo_mensaje'] = "success";
            } else {
                $_SESSION['mensaje'] = "No se pudo eliminar el proveedor. Es posible que ya haya sido eliminado o no exista.";
                $_SESSION['tipo_mensaje'] = "error";
            }
        } else {
            $error = $stmt->error;
            if (strpos($error, 'foreign key constraint') !== false) {
                $_SESSION['mensaje'] = "No se puede eliminar el proveedor porque está siendo utilizado en otros registros del sistema. Verifique que no existan registros relacionados en las compras.";
                $_SESSION['tipo_mensaje'] = "error";
            } else {
                $_SESSION['mensaje'] = "Error al eliminar proveedor: " . $error;
                $_SESSION['tipo_mensaje'] = "error";
            }
        }
        
        $stmt->close();
        
    } catch (mysqli_sql_exception $e) {
        // Capturar excepciones específicas de MySQL
        $error_message = $e->getMessage();
        
        if (strpos($error_message, 'foreign key constraint fails') !== false) {
            $_SESSION['mensaje'] = "No se puede eliminar el proveedor porque está siendo utilizado en otros registros del sistema. Verifique que no existan registros relacionados en las compras.";
            $_SESSION['tipo_mensaje'] = "error";
        } else if (strpos($error_message, 'Unknown column') !== false) {
            $_SESSION['mensaje'] = "Error en la consulta a la base de datos. Por favor, contacte al administrador del sistema.";
            $_SESSION['tipo_mensaje'] = "error";
        } else {
            $_SESSION['mensaje'] = "Error de base de datos: " . $error_message;
            $_SESSION['tipo_mensaje'] = "error";
        }
    } catch (Exception $e) {
        // Capturar cualquier otra excepción
        $_SESSION['mensaje'] = "Error inesperado: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    desconectar($conn);
    header('Location: gestion_proveedores.php');
    exit();
}

// Obtener todos los proveedores para mostrar en la tabla
function obtenerProveedores() {
    $conn = conectar();
    
    $sql = "SELECT * FROM proveedores ORDER BY nombre_proveedor";
    $resultado = $conn->query($sql);
    $proveedores = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $proveedores[] = $fila;
        }
    }
    
    desconectar($conn);
    return $proveedores;
}

$proveedores = obtenerProveedores();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Proveedores - Marina Roja</title>
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Frameworks y librerías base -->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
</head>
<body>
    <header class="mb-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
            <h1 class="mb-0">GESTIÓN DE PROVEEDORES</h1>
            <ul class="nav nav-pills gap-2 mb-0">
                <li class="nav-item"><a href="../menu_empleados.php" class="nav-link">Regresar al Menú</a></li>
            </ul>
        </div>
    </header>

    <main class="container my-4">
        <!-- Mostrar mensajes con SweetAlert2 -->
        <?php if (isset($_SESSION['mensaje'])): ?>
            <script>
                window.__mensaje = {
                    text: <?php echo json_encode($_SESSION['mensaje']); ?>,
                    tipo: <?php echo json_encode($_SESSION['tipo_mensaje'] ?? 'error'); ?>
                };
            </script>
            <noscript>
                <div class="alert alert-<?php echo ($_SESSION['tipo_mensaje'] ?? '') === 'success' ? 'success' : 'danger'; ?>">
                    <?php echo htmlspecialchars($_SESSION['mensaje']); ?>
                </div>
            </noscript>
            <?php 
            unset($_SESSION['mensaje']);
            unset($_SESSION['tipo_mensaje']);
            ?>
        <?php endif; ?>

        <section class="card shadow p-4">
            <h2 class="card__title text-primary mb-4">FORMULARIO - REGISTRO DE PROVEEDORES</h2>

            <form id="form-proveedores" method="post" class="row g-3">
                <input type="hidden" id="operacion" name="operacion" value="crear_proveedor">
                <input type="hidden" id="id_proveedor" name="id_proveedor" value="">
                
                <div class="col-md-6">
                    <label class="form-label" for="nombre_proveedor">Nombre del Proveedor:</label>
                    <input type="text" class="form-control" id="nombre_proveedor" name="nombre_proveedor" 
                           required placeholder="Ej: Muebles y Diseños S.A.">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="telefono_proveedor">Teléfono:</label>
                    <input type="text" class="form-control" id="telefono_proveedor" name="telefono_proveedor" 
                           placeholder="Ej: 5555-1234">
                </div>
                
                <div class="col-12">
                    <label class="form-label" for="correo_proveedor">Correo Electrónico:</label>
                    <input type="email" class="form-control" id="correo_proveedor" name="correo_proveedor" 
                           placeholder="Ej: contacto@proveedor.com">
                </div>
            </form>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>

            <h2 class="card__title mb-3 mt-5">LISTA DE PROVEEDORES REGISTRADOS</h2>
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered" id="tabla-proveedores">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Correo Electrónico</th>
                            <th>Teléfono</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($proveedores as $proveedor): ?>
                        <tr>
                            <td>
                                <span class="badge-proveedor">#<?php echo htmlspecialchars($proveedor['id_proveedor']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($proveedor['nombre_proveedor']); ?></td>
                            <td class="email-cell">
                                <?php if (!empty($proveedor['correo_proveedor'])): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($proveedor['correo_proveedor']); ?>">
                                        <?php echo htmlspecialchars($proveedor['correo_proveedor']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">No especificado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($proveedor['telefono_proveedor'])): ?>
                                    <?php echo htmlspecialchars($proveedor['telefono_proveedor']); ?>
                                <?php else: ?>
                                    <span class="text-muted">No especificado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                        data-id="<?php echo $proveedor['id_proveedor']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($proveedor['nombre_proveedor']); ?>"
                                        data-correo="<?php echo htmlspecialchars($proveedor['correo_proveedor'] ?? ''); ?>"
                                        data-telefono="<?php echo htmlspecialchars($proveedor['telefono_proveedor'] ?? ''); ?>">
                                    Editar
                                </button>
                                <form method="post" style="display:inline;" data-eliminar="true">
                                    <input type="hidden" name="operacion" value="eliminar_proveedor">
                                    <input type="hidden" name="id_proveedor" value="<?php echo $proveedor['id_proveedor']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-action">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($proveedores)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No hay proveedores registrados</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/SistemaWebRestaurante/javascript/gestion_proveedores.js"></script>
</body>
</html>