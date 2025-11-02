<?php
session_start();
require_once '../conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// Función para validar y sanitizar datos del mantenimiento
function validarDatosMantenimiento($datos) {
    $errores = [];
    
    // Validar vehículo
    if (empty($datos['id_vehiculo']) || !is_numeric($datos['id_vehiculo']) || $datos['id_vehiculo'] <= 0) {
        $errores[] = "El vehículo seleccionado es inválido";
    }
    
    // Validar taller
    if (empty($datos['id_taller']) || !is_numeric($datos['id_taller']) || $datos['id_taller'] <= 0) {
        $errores[] = "El taller seleccionado es inválido";
    }
    
    // Validar fecha de mantenimiento
    if (empty($datos['fecha_mantenimiento'])) {
        $errores[] = "La fecha de mantenimiento es requerida";
    } else {
        $fecha = DateTime::createFromFormat('Y-m-d', $datos['fecha_mantenimiento']);
        if (!$fecha || $fecha->format('Y-m-d') !== $datos['fecha_mantenimiento']) {
            $errores[] = "El formato de fecha es inválido (YYYY-MM-DD)";
        } else {
            // Validar que la fecha no sea futura
            $hoy = new DateTime();
            if ($fecha > $hoy) {
                $errores[] = "La fecha de mantenimiento no puede ser futura";
            }
            
            // Validar que la fecha no sea muy antigua (máximo 10 años atrás)
            $fecha_limite = new DateTime();
            $fecha_limite->modify('-10 years');
            if ($fecha < $fecha_limite) {
                $errores[] = "La fecha de mantenimiento no puede ser mayor a 10 años atrás";
            }
        }
    }
    
    // Validar costo
    if (!isset($datos['costo_mantenimiento']) || !is_numeric($datos['costo_mantenimiento'])) {
        $errores[] = "El costo debe ser un número válido";
    } else if ($datos['costo_mantenimiento'] < 0) {
        $errores[] = "El costo no puede ser negativo";
    } else if ($datos['costo_mantenimiento'] == 0) {
        $errores[] = "El costo debe ser mayor a cero";
    } else if ($datos['costo_mantenimiento'] > 1000000) {
        $errores[] = "El costo no puede ser mayor a Q 1,000,000.00";
    }
    
    // Validar formato del costo (máximo 2 decimales)
    if (isset($datos['costo_mantenimiento']) && is_numeric($datos['costo_mantenimiento'])) {
        $partes = explode('.', (string)$datos['costo_mantenimiento']);
        if (count($partes) > 1 && strlen($partes[1]) > 2) {
            $errores[] = "El costo no puede tener más de 2 decimales";
        }
    }
    
    // Validar descripción
    if (empty($datos['descripcion_mantenimiento'])) {
        $errores[] = "La descripción del mantenimiento es requerida";
    } else {
        $descripcion = trim($datos['descripcion_mantenimiento']);
        if (strlen($descripcion) < 5) {
            $errores[] = "La descripción debe tener al menos 5 caracteres";
        }
        if (strlen($descripcion) > 500) {
            $errores[] = "La descripción no puede exceder los 500 caracteres";
        }
        // Validar caracteres en descripción
        if (!preg_match('/^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s\-\_\.\,\;\:\!\?\(\)\#\$\&\+\=\/\@]+$/', $descripcion)) {
            $errores[] = "La descripción contiene caracteres no permitidos";
        }
    }
    
    return $errores;
}

// Funciones de validación de existencia
function validarVehiculo($id_vehiculo) {
    $conn = conectar();
    $sql = "SELECT id_vehiculo, estado FROM vehiculos WHERE id_vehiculo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_vehiculo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        desconectar($conn);
        return false;
    }
    
    $vehiculo = $result->fetch_assoc();
    $stmt->close();
    desconectar($conn);
    
    // Verificar que el vehículo esté en taller
    return $vehiculo['estado'] === 'EN_TALLER';
}

function validarTaller($id_taller) {
    $conn = conectar();
    $sql = "SELECT id_taller FROM talleres WHERE id_taller = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_taller);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    desconectar($conn);
    return $exists;
}

// Procesar operaciones CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operacion = $_POST['operacion'] ?? '';
    
    switch($operacion) {
        case 'crear':
            crearMantenimiento();
            break;
        case 'actualizar':
            actualizarMantenimiento();
            break;
        case 'eliminar':
            eliminarMantenimiento();
            break;
    }
}

function crearMantenimiento() {
    global $conn;
    $conn = conectar();
    
    // Validar datos
    $errores = validarDatosMantenimiento($_POST);
    
    // Verificar existencia del vehículo y que esté en taller
    if (empty($errores)) {
        if (!validarVehiculo($_POST['id_vehiculo'])) {
            $errores[] = "El vehículo seleccionado no existe o no está en taller";
        }
    }
    
    // Verificar existencia del taller
    if (empty($errores)) {
        if (!validarTaller($_POST['id_taller'])) {
            $errores[] = "El taller seleccionado no existe";
        }
    }
    
    if (!empty($errores)) {
        $_SESSION['mensaje'] = "Errores de validación:<br>" . implode("<br>", $errores);
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: mantenimiento_vehiculos.php');
        exit();
    }
    
    $id_vehiculo = intval($_POST['id_vehiculo']);
    $id_taller = intval($_POST['id_taller']);
    $descripcion_mantenimiento = trim($_POST['descripcion_mantenimiento']);
    $fecha_mantenimiento = $_POST['fecha_mantenimiento'];
    $costo_mantenimiento = floatval($_POST['costo_mantenimiento']);
    
    // Redondear costo a 2 decimales
    $costo_mantenimiento = round($costo_mantenimiento, 2);
    
    $sql = "INSERT INTO mantenimiento_vehiculo (id_vehiculo, id_taller, descripcion_mantenimiento, fecha_mantenimiento, costo_mantenimiento) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissd", $id_vehiculo, $id_taller, $descripcion_mantenimiento, $fecha_mantenimiento, $costo_mantenimiento);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Mantenimiento creado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al crear mantenimiento: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: mantenimiento_vehiculos.php');
    exit();
}

function actualizarMantenimiento() {
    global $conn;
    $conn = conectar();
    
    // Validar ID de mantenimiento
    if (empty($_POST['id_mantenimiento']) || !is_numeric($_POST['id_mantenimiento']) || $_POST['id_mantenimiento'] <= 0) {
        $_SESSION['mensaje'] = "ID de mantenimiento inválido";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: mantenimiento_vehiculos.php');
        exit();
    }
    
    // Validar datos
    $errores = validarDatosMantenimiento($_POST);
    
    // Verificar existencia del vehículo y que esté en taller
    if (empty($errores)) {
        if (!validarVehiculo($_POST['id_vehiculo'])) {
            $errores[] = "El vehículo seleccionado no existe o no está en taller";
        }
    }
    
    // Verificar existencia del taller
    if (empty($errores)) {
        if (!validarTaller($_POST['id_taller'])) {
            $errores[] = "El taller seleccionado no existe";
        }
    }
    
    if (!empty($errores)) {
        $_SESSION['mensaje'] = "Errores de validación:<br>" . implode("<br>", $errores);
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: mantenimiento_vehiculos.php');
        exit();
    }
    
    $id_mantenimiento = intval($_POST['id_mantenimiento']);
    $id_vehiculo = intval($_POST['id_vehiculo']);
    $id_taller = intval($_POST['id_taller']);
    $descripcion_mantenimiento = trim($_POST['descripcion_mantenimiento']);
    $fecha_mantenimiento = $_POST['fecha_mantenimiento'];
    $costo_mantenimiento = floatval($_POST['costo_mantenimiento']);
    
    // Redondear costo a 2 decimales
    $costo_mantenimiento = round($costo_mantenimiento, 2);
    
    $sql = "UPDATE mantenimiento_vehiculo 
            SET id_vehiculo = ?, id_taller = ?, descripcion_mantenimiento = ?, 
                fecha_mantenimiento = ?, costo_mantenimiento = ? 
            WHERE id_mantenimiento = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissdi", $id_vehiculo, $id_taller, $descripcion_mantenimiento, $fecha_mantenimiento, $costo_mantenimiento, $id_mantenimiento);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Mantenimiento actualizado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar mantenimiento: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: mantenimiento_vehiculos.php');
    exit();
}

function eliminarMantenimiento() {
    global $conn;
    $conn = conectar();
    
    $id_mantenimiento = $_POST['id_mantenimiento'] ?? '';
    
    // Validar que el ID no esté vacío
    if (empty($id_mantenimiento) || !is_numeric($id_mantenimiento) || $id_mantenimiento <= 0) {
        $_SESSION['mensaje'] = "Error: No se proporcionó un ID de mantenimiento válido.";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: mantenimiento_vehiculos.php');
        exit();
    }
    
    $id_mantenimiento = intval($id_mantenimiento);
    
    try {
        // Primero verificar si el mantenimiento existe
        $check_mantenimiento = $conn->prepare("SELECT id_mantenimiento FROM mantenimiento_vehiculo WHERE id_mantenimiento = ?");
        if (!$check_mantenimiento) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }
        
        $check_mantenimiento->bind_param("i", $id_mantenimiento);
        
        if (!$check_mantenimiento->execute()) {
            throw new Exception("Error al ejecutar la consulta: " . $check_mantenimiento->error);
        }
        
        $result_mantenimiento = $check_mantenimiento->get_result();
        
        if ($result_mantenimiento->num_rows === 0) {
            $_SESSION['mensaje'] = "Error: El mantenimiento que intenta eliminar no existe en el sistema.";
            $_SESSION['tipo_mensaje'] = "error";
            $check_mantenimiento->close();
            desconectar($conn);
            header('Location: mantenimiento_vehiculos.php');
            exit();
        }
        $check_mantenimiento->close();
        
        // Verificar si existe la tabla facturas y si tiene relación
        $check_tabla_facturas = $conn->query("SHOW TABLES LIKE 'facturas'");
        if ($check_tabla_facturas && $check_tabla_facturas->num_rows > 0) {
            // Verificar si hay registros relacionados
            $check_relacion = $conn->prepare("SELECT COUNT(*) as count FROM facturas WHERE id_mantenimiento = ?");
            if ($check_relacion) {
                $check_relacion->bind_param("i", $id_mantenimiento);
                $check_relacion->execute();
                $result_relacion = $check_relacion->get_result();
                $row_relacion = $result_relacion->fetch_assoc();
                $check_relacion->close();
                
                if ($row_relacion['count'] > 0) {
                    $_SESSION['mensaje'] = "No se puede eliminar el mantenimiento porque está siendo utilizado en facturas registradas (" . $row_relacion['count'] . " facturas relacionadas).";
                    $_SESSION['tipo_mensaje'] = "error";
                    desconectar($conn);
                    header('Location: mantenimiento_vehiculos.php');
                    exit();
                }
            }
        }
        
        // Si no hay referencias, proceder con la eliminación
        $sql = "DELETE FROM mantenimiento_vehiculo WHERE id_mantenimiento = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta de eliminación: " . $conn->error);
        }
        
        $stmt->bind_param("i", $id_mantenimiento);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['mensaje'] = "Mantenimiento eliminado exitosamente";
                $_SESSION['tipo_mensaje'] = "success";
            } else {
                $_SESSION['mensaje'] = "No se pudo eliminar el mantenimiento. Es posible que ya haya sido eliminado o no exista.";
                $_SESSION['tipo_mensaje'] = "error";
            }
        } else {
            $error = $stmt->error;
            if (strpos($error, 'foreign key constraint') !== false) {
                $_SESSION['mensaje'] = "No se puede eliminar el mantenimiento porque está siendo utilizado en otros registros del sistema.";
                $_SESSION['tipo_mensaje'] = "error";
            } else {
                $_SESSION['mensaje'] = "Error al eliminar mantenimiento: " . $error;
                $_SESSION['tipo_mensaje'] = "error";
            }
        }
        
        $stmt->close();
        
    } catch (mysqli_sql_exception $e) {
        // Capturar excepciones específicas de MySQL
        $error_message = $e->getMessage();
        
        if (strpos($error_message, 'foreign key constraint fails') !== false) {
            $_SESSION['mensaje'] = "No se puede eliminar el mantenimiento porque está siendo utilizado en otros registros del sistema.";
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
    header('Location: mantenimiento_vehiculos.php');
    exit();
}

// Obtener datos para los selectores - Solo vehículos en taller
function obtenerVehiculos() {
    $conn = conectar();
    $sql = "SELECT id_vehiculo, no_placa, marca_vehiculo, modelo_vehiculo 
            FROM vehiculos 
            WHERE estado = 'EN_TALLER' 
            ORDER BY no_placa";
    $resultado = $conn->query($sql);
    $vehiculos = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $vehiculos[] = $fila;
        }
    }
    
    desconectar($conn);
    return $vehiculos;
}

function obtenerTalleres() {
    $conn = conectar();
    $sql = "SELECT id_taller, nombre_taller FROM talleres ORDER BY nombre_taller";
    $resultado = $conn->query($sql);
    $talleres = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $talleres[] = $fila;
        }
    }
    
    desconectar($conn);
    return $talleres;
}

// Obtener todos los mantenimientos para mostrar en la tabla
function obtenerMantenimientos() {
    $conn = conectar();
    $sql = "SELECT mv.*, v.no_placa, v.marca_vehiculo, v.modelo_vehiculo, t.nombre_taller 
            FROM mantenimiento_vehiculo mv
            LEFT JOIN vehiculos v ON mv.id_vehiculo = v.id_vehiculo
            LEFT JOIN talleres t ON mv.id_taller = t.id_taller
            ORDER BY mv.fecha_mantenimiento DESC";
    $resultado = $conn->query($sql);
    $mantenimientos = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $mantenimientos[] = $fila;
        }
    }
    
    desconectar($conn);
    return $mantenimientos;
}

$vehiculos = obtenerVehiculos();
$talleres = obtenerTalleres();
$mantenimientos = obtenerMantenimientos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenimiento de Vehículos - Marina Roja</title>
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Frameworks y librerías base -->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
</head>
<body>
    <header class="mb-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
            <h1 class="mb-0">Mantenimiento de Vehículos</h1>
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
            <h2 class="card__title text-primary mb-4">FORMULARIO - Mantenimiento de Vehículos</h2>

            <!-- Mensaje si no hay vehículos en taller -->
            <?php if (empty($vehiculos)): ?>
                <div class="alert alert-warning">
                    <strong>⚠️ No hay vehículos en taller</strong>
                    <p class="mb-0">Para registrar un mantenimiento, primero debe cambiar el estado de un vehículo a "EN TALLER" en el módulo de Gestión de Vehículos.</p>
                </div>
            <?php endif; ?>

            <form id="form-mantenimiento" method="post" class="row g-3" <?php echo empty($vehiculos) ? 'style="opacity: 0.5; pointer-events: none;"' : ''; ?>>
                <input type="hidden" id="operacion" name="operacion" value="crear">
                <input type="hidden" id="id_mantenimiento" name="id_mantenimiento" value="">
                
                <div class="col-md-4">
                    <label class="form-label" for="id_vehiculo">Vehículo:</label>
                    <select class="form-control" id="id_vehiculo" name="id_vehiculo" required <?php echo empty($vehiculos) ? 'disabled' : ''; ?>>
                        <option value="">Seleccione un vehículo</option>
                        <?php foreach($vehiculos as $vehiculo): ?>
                            <option value="<?php echo $vehiculo['id_vehiculo']; ?>">
                                <?php echo htmlspecialchars($vehiculo['no_placa'] . ' - ' . $vehiculo['marca_vehiculo'] . ' ' . $vehiculo['modelo_vehiculo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($vehiculos)): ?>
                        <small class="text-danger">No hay vehículos disponibles en taller</small>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label" for="id_taller">Taller:</label>
                    <select class="form-control" id="id_taller" name="id_taller" required <?php echo empty($vehiculos) ? 'disabled' : ''; ?>>
                        <option value="">Seleccione un taller</option>
                        <?php foreach($talleres as $taller): ?>
                            <option value="<?php echo $taller['id_taller']; ?>">
                                <?php echo htmlspecialchars($taller['nombre_taller']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label" for="fecha_mantenimiento">Fecha de Mantenimiento:</label>
                    <input type="date" class="form-control" id="fecha_mantenimiento" name="fecha_mantenimiento" 
                           max="<?php echo date('Y-m-d'); ?>" required <?php echo empty($vehiculos) ? 'disabled' : ''; ?>>
                    <div class="form-text">No puede ser una fecha futura</div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="costo_mantenimiento">Costo (Q):</label>
                    <input type="number" class="form-control" id="costo_mantenimiento" name="costo_mantenimiento" 
                           step="0.01" min="0.01" max="1000000" required placeholder="Ej. 1250.00" <?php echo empty($vehiculos) ? 'disabled' : ''; ?>>
                    <div class="form-text">Mínimo Q 0.01, máximo Q 1,000,000.00</div>
                </div>
                
                <div class="col-12">
                    <label class="form-label" for="descripcion_mantenimiento">Descripción del Mantenimiento:</label>
                    <textarea class="form-control" id="descripcion_mantenimiento" name="descripcion_mantenimiento" 
                              rows="3" maxlength="500" required 
                              placeholder="Ej. Cambio de aceite, reparación de frenos, etc." <?php echo empty($vehiculos) ? 'disabled' : ''; ?>></textarea>
                    <div class="form-text">Mínimo 5 caracteres, máximo 500 caracteres</div>
                </div>
            </form>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary" <?php echo empty($vehiculos) ? 'disabled' : ''; ?>>Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success" <?php echo empty($vehiculos) ? 'disabled' : ''; ?>>Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>

            <h2 class="card__title mb-3 mt-5">Historial de Mantenimientos</h2>
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered" id="tabla-mantenimientos">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Vehículo</th>
                            <th>Taller</th>
                            <th>Fecha</th>
                            <th>Costo (Q)</th>
                            <th>Descripción</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($mantenimientos as $mantenimiento): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($mantenimiento['id_mantenimiento']); ?></td>
                            <td><?php echo htmlspecialchars($mantenimiento['no_placa'] . ' - ' . $mantenimiento['marca_vehiculo']); ?></td>
                            <td><?php echo htmlspecialchars($mantenimiento['nombre_taller'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($mantenimiento['fecha_mantenimiento']); ?></td>
                            <td class="text-end fw-bold">Q <?php echo number_format($mantenimiento['costo_mantenimiento'], 2); ?></td>
                            <td class="descripcion-cell" title="<?php echo htmlspecialchars($mantenimiento['descripcion_mantenimiento']); ?>">
                                <?php 
                                $descripcion = $mantenimiento['descripcion_mantenimiento'];
                                if (strlen($descripcion) > 50) {
                                    echo htmlspecialchars(substr($descripcion, 0, 50)) . '...';
                                } else {
                                    echo htmlspecialchars($descripcion);
                                }
                                ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                        data-id="<?php echo $mantenimiento['id_mantenimiento']; ?>"
                                        data-vehiculo="<?php echo $mantenimiento['id_vehiculo']; ?>"
                                        data-taller="<?php echo $mantenimiento['id_taller']; ?>"
                                        data-fecha="<?php echo $mantenimiento['fecha_mantenimiento']; ?>"
                                        data-costo="<?php echo $mantenimiento['costo_mantenimiento']; ?>"
                                        data-descripcion="<?php echo htmlspecialchars($mantenimiento['descripcion_mantenimiento']); ?>">
                                    Editar
                                </button>
                                <form method="post" style="display:inline;" data-eliminar="true">
                                    <input type="hidden" name="operacion" value="eliminar">
                                    <input type="hidden" name="id_mantenimiento" value="<?php echo $mantenimiento['id_mantenimiento']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-action">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($mantenimientos)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No hay mantenimientos registrados</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/SistemaWebRestaurante/javascript/mantenimiento_vehiculos.js"></script>
</body>
</html>