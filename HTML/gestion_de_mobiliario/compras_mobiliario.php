<?php
session_start();
require_once '../conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// Función para validar y sanitizar datos
function validarDatosCompra($datos) {
    $errores = [];
    
    // Validar ID proveedor
    if (empty($datos['id_proveedor']) || !is_numeric($datos['id_proveedor']) || $datos['id_proveedor'] <= 0) {
        $errores[] = "El ID del proveedor es inválido";
    }
    
    // Validar fecha
    if (empty($datos['fecha_de_compra'])) {
        $errores[] = "La fecha de compra es requerida";
    } else {
        $fecha = DateTime::createFromFormat('Y-m-d', $datos['fecha_de_compra']);
        if (!$fecha || $fecha->format('Y-m-d') !== $datos['fecha_de_compra']) {
            $errores[] = "El formato de fecha es inválido (YYYY-MM-DD)";
        } else {
            // Validar que la fecha no sea futura
            $hoy = new DateTime();
            if ($fecha > $hoy) {
                $errores[] = "La fecha de compra no puede ser futura";
            }
        }
    }
    
    // Validar monto
    if (!isset($datos['monto_total_compra_q']) || !is_numeric($datos['monto_total_compra_q'])) {
        $errores[] = "El monto total debe ser un número válido";
    } else if ($datos['monto_total_compra_q'] < 0) {
        $errores[] = "El monto total no puede ser negativo";
    } else if ($datos['monto_total_compra_q'] == 0) {
        $errores[] = "El monto total debe ser mayor a cero";
    }
    
    // Validar formato del monto (máximo 2 decimales)
    if (isset($datos['monto_total_compra_q']) && is_numeric($datos['monto_total_compra_q'])) {
        $partes = explode('.', (string)$datos['monto_total_compra_q']);
        if (count($partes) > 1 && strlen($partes[1]) > 2) {
            $errores[] = "El monto total no puede tener más de 2 decimales";
        }
    }
    
    return $errores;
}

// Función para verificar existencia del proveedor
function verificarProveedor($id_proveedor) {
    $conn = conectar();
    $sql = "SELECT id_proveedor FROM proveedores WHERE id_proveedor = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_proveedor);
    $stmt->execute();
    $result = $stmt->get_result();
    $existe = $result->num_rows > 0;
    $stmt->close();
    desconectar($conn);
    return $existe;
}

// Procesar operaciones CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operacion = $_POST['operacion'] ?? '';
    
    switch($operacion) {
        case 'crear_compra':
            crearCompra();
            break;
        case 'actualizar_compra':
            actualizarCompra();
            break;
        case 'eliminar_compra':
            eliminarCompra();
            break;
    }
}

function crearCompra() {
    global $conn;
    $conn = conectar();
    
    // Validar datos
    $errores = validarDatosCompra($_POST);
    
    // Verificar existencia del proveedor
    if (empty($errores)) {
        if (!verificarProveedor($_POST['id_proveedor'])) {
            $errores[] = "El proveedor seleccionado no existe";
        }
    }
    
    if (!empty($errores)) {
        $_SESSION['mensaje'] = "Errores de validación:<br>" . implode("<br>", $errores);
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: compras_mobiliario.php');
        exit();
    }
    
    $id_proveedor = intval($_POST['id_proveedor']);
    $fecha_de_compra = $_POST['fecha_de_compra'];
    $monto_total_compra_q = floatval($_POST['monto_total_compra_q']);
    
    // Redondear a 2 decimales
    $monto_total_compra_q = round($monto_total_compra_q, 2);
    
    $sql = "INSERT INTO compras_mobiliario (id_proveedor, fecha_de_compra, monto_total_compra_q) 
            VALUES (?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isd", $id_proveedor, $fecha_de_compra, $monto_total_compra_q);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Compra registrada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al registrar compra: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: compras_mobiliario.php');
    exit();
}

function actualizarCompra() {
    global $conn;
    $conn = conectar();
    
    // Validar ID de compra
    if (empty($_POST['id_compra_mobiliario']) || !is_numeric($_POST['id_compra_mobiliario']) || $_POST['id_compra_mobiliario'] <= 0) {
        $_SESSION['mensaje'] = "ID de compra inválido";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: compras_mobiliario.php');
        exit();
    }
    
    // Validar datos
    $errores = validarDatosCompra($_POST);
    
    // Verificar existencia del proveedor
    if (empty($errores)) {
        if (!verificarProveedor($_POST['id_proveedor'])) {
            $errores[] = "El proveedor seleccionado no existe";
        }
    }
    
    if (!empty($errores)) {
        $_SESSION['mensaje'] = "Errores de validación:<br>" . implode("<br>", $errores);
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: compras_mobiliario.php');
        exit();
    }
    
    $id_compra_mobiliario = intval($_POST['id_compra_mobiliario']);
    $id_proveedor = intval($_POST['id_proveedor']);
    $fecha_de_compra = $_POST['fecha_de_compra'];
    $monto_total_compra_q = floatval($_POST['monto_total_compra_q']);
    
    // Redondear a 2 decimales
    $monto_total_compra_q = round($monto_total_compra_q, 2);
    
    $sql = "UPDATE compras_mobiliario SET id_proveedor = ?, fecha_de_compra = ?, monto_total_compra_q = ? 
            WHERE id_compra_mobiliario = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isdi", $id_proveedor, $fecha_de_compra, $monto_total_compra_q, $id_compra_mobiliario);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Compra actualizada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar compra: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: compras_mobiliario.php');
    exit();
}

function eliminarCompra() {
    global $conn;
    $conn = conectar();
    
    $id_compra_mobiliario = $_POST['id_compra_mobiliario'] ?? '';
    
    // Validar que el ID no esté vacío
    if (empty($id_compra_mobiliario) || !is_numeric($id_compra_mobiliario) || $id_compra_mobiliario <= 0) {
        $_SESSION['mensaje'] = "Error: No se proporcionó un ID de compra válido.";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: compras_mobiliario.php');
        exit();
    }
    
    $id_compra_mobiliario = intval($id_compra_mobiliario);
    
    try {
        // Primero verificar si la compra existe
        $check_compra = $conn->prepare("SELECT id_compra_mobiliario FROM compras_mobiliario WHERE id_compra_mobiliario = ?");
        if (!$check_compra) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }
        
        $check_compra->bind_param("i", $id_compra_mobiliario);
        
        if (!$check_compra->execute()) {
            throw new Exception("Error al ejecutar la consulta: " . $check_compra->error);
        }
        
        $result_compra = $check_compra->get_result();
        
        if ($result_compra->num_rows === 0) {
            $_SESSION['mensaje'] = "Error: La compra que intenta eliminar no existe en el sistema.";
            $_SESSION['tipo_mensaje'] = "error";
            $check_compra->close();
            desconectar($conn);
            header('Location: compras_mobiliario.php');
            exit();
        }
        $check_compra->close();
        
        // Verificar si existe la tabla inventario_mobiliario y si tiene relación con compras
        $check_tabla_inventario = $conn->query("SHOW TABLES LIKE 'inventario_mobiliario'");
        if ($check_tabla_inventario && $check_tabla_inventario->num_rows > 0) {
            // La tabla existe, verificar si hay columnas que referencien compras_mobiliario
            $check_columnas = $conn->query("SHOW COLUMNS FROM inventario_mobiliario");
            $tiene_relacion = false;
            $columna_relacion = '';
            
            while ($columna = $check_columnas->fetch_assoc()) {
                if (strpos($columna['Field'], 'compra') !== false || 
                    strpos($columna['Field'], 'id_compra') !== false) {
                    $tiene_relacion = true;
                    $columna_relacion = $columna['Field'];
                    break;
                }
            }
            
            if ($tiene_relacion && !empty($columna_relacion)) {
                // Verificar si hay registros relacionados
                $check_relacion = $conn->prepare("SELECT COUNT(*) as count FROM inventario_mobiliario WHERE {$columna_relacion} = ?");
                if ($check_relacion) {
                    $check_relacion->bind_param("i", $id_compra_mobiliario);
                    $check_relacion->execute();
                    $result_relacion = $check_relacion->get_result();
                    $row_relacion = $result_relacion->fetch_assoc();
                    $check_relacion->close();
                    
                    if ($row_relacion['count'] > 0) {
                        $_SESSION['mensaje'] = "No se puede eliminar la compra porque está siendo utilizada en el inventario de mobiliario (" . $row_relacion['count'] . " registros relacionados). Primero debe eliminar o modificar los registros relacionados en el inventario.";
                        $_SESSION['tipo_mensaje'] = "error";
                        desconectar($conn);
                        header('Location: compras_mobiliario.php');
                        exit();
                    }
                }
            }
        }
        
        // Si no hay referencias, proceder con la eliminación
        $sql = "DELETE FROM compras_mobiliario WHERE id_compra_mobiliario = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta de eliminación: " . $conn->error);
        }
        
        $stmt->bind_param("i", $id_compra_mobiliario);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['mensaje'] = "Compra eliminada exitosamente";
                $_SESSION['tipo_mensaje'] = "success";
            } else {
                $_SESSION['mensaje'] = "No se pudo eliminar la compra. Es posible que ya haya sido eliminada o no exista.";
                $_SESSION['tipo_mensaje'] = "error";
            }
        } else {
            $error = $stmt->error;
            if (strpos($error, 'foreign key constraint') !== false) {
                $_SESSION['mensaje'] = "No se puede eliminar la compra porque está siendo utilizada en otros registros del sistema. Verifique que no existan registros relacionados en el inventario.";
                $_SESSION['tipo_mensaje'] = "error";
            } else {
                $_SESSION['mensaje'] = "Error al eliminar compra: " . $error;
                $_SESSION['tipo_mensaje'] = "error";
            }
        }
        
        $stmt->close();
        
    } catch (mysqli_sql_exception $e) {
        // Capturar excepciones específicas de MySQL
        $error_message = $e->getMessage();
        
        if (strpos($error_message, 'foreign key constraint fails') !== false) {
            $_SESSION['mensaje'] = "No se puede eliminar la compra porque está siendo utilizada en otros registros del sistema. Verifique que no existan registros relacionados en el inventario.";
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
    header('Location: compras_mobiliario.php');
    exit();
}

// Obtener todas las compras para mostrar en la tabla
function obtenerCompras() {
    $conn = conectar();
    
    $sql = "SELECT cm.*, p.nombre_proveedor 
            FROM compras_mobiliario cm 
            LEFT JOIN proveedores p ON cm.id_proveedor = p.id_proveedor 
            ORDER BY cm.fecha_de_compra DESC";
    
    $resultado = $conn->query($sql);
    $compras = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $compras[] = $fila;
        }
    }
    
    desconectar($conn);
    return $compras;
}

// Obtener proveedores para el select
function obtenerProveedores() {
    $conn = conectar();
    
    $sql = "SELECT id_proveedor, nombre_proveedor FROM proveedores ORDER BY nombre_proveedor";
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

$compras = obtenerCompras();
$proveedores = obtenerProveedores();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compras de Mobiliario - Marina Roja</title>
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Frameworks y librerías base - RUTAS CORREGIDAS -->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
</head>
<body>
    <header class="mb-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
            <h1 class="mb-0">COMPRAS DE MOBILIARIO</h1>
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
            <h2 class="card__title text-primary mb-4">FORMULARIO - REGISTRO DE COMPRAS DE MOBILIARIO</h2>

            <form id="form-compras" method="post" class="row g-3">
                <input type="hidden" id="operacion" name="operacion" value="crear_compra">
                <input type="hidden" id="id_compra_mobiliario" name="id_compra_mobiliario" value="">
                
                <div class="col-md-4">
                    <label class="form-label" for="id_proveedor">Proveedor:</label>
                    <select class="form-control" id="id_proveedor" name="id_proveedor" required>
                        <option value="">Seleccione un proveedor</option>
                        <?php foreach($proveedores as $proveedor): ?>
                            <option value="<?php echo $proveedor['id_proveedor']; ?>">
                                <?php echo htmlspecialchars($proveedor['nombre_proveedor']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label" for="fecha_de_compra">Fecha de Compra:</label>
                    <input type="date" class="form-control" id="fecha_de_compra" name="fecha_de_compra" required>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label" for="monto_total_compra_q">Monto Total (Q):</label>
                    <input type="number" step="0.01" class="form-control" id="monto_total_compra_q" name="monto_total_compra_q" 
                           min="0.01" required placeholder="0.00">
                </div>
            </form>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>

            <h2 class="card__title mb-3 mt-5">HISTORIAL DE COMPRAS</h2>
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered" id="tabla-compras">
                    <thead class="table-dark">
                        <tr>
                            <th>ID Compra</th>
                            <th>Proveedor</th>
                            <th>Fecha de Compra</th>
                            <th>Monto Total (Q)</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($compras as $compra): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($compra['id_compra_mobiliario']); ?></td>
                            <td><?php echo htmlspecialchars($compra['nombre_proveedor'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($compra['fecha_de_compra']); ?></td>
                            <td class="text-end fw-bold">Q <?php echo number_format($compra['monto_total_compra_q'], 2); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                        data-id="<?php echo $compra['id_compra_mobiliario']; ?>"
                                        data-proveedor="<?php echo $compra['id_proveedor']; ?>"
                                        data-fecha="<?php echo $compra['fecha_de_compra']; ?>"
                                        data-monto="<?php echo $compra['monto_total_compra_q']; ?>">
                                    Editar
                                </button>
                                <form method="post" style="display:inline;" data-eliminar="true">
                                    <input type="hidden" name="operacion" value="eliminar_compra">
                                    <input type="hidden" name="id_compra_mobiliario" value="<?php echo $compra['id_compra_mobiliario']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-action">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($compras)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No hay compras registradas</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/SistemaWebRestaurante/javascript/compras_mobiliario.js"></script>
</body>
</html>