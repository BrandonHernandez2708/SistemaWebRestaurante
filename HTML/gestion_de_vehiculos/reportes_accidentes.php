<?php
session_start();
require_once '../conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// Función para validar y sanitizar datos del accidente
function validarDatosAccidente($datos) {
    $errores = [];
    
    // Validar viaje
    if (empty($datos['id_viaje']) || !is_numeric($datos['id_viaje']) || $datos['id_viaje'] <= 0) {
        $errores[] = "El viaje relacionado es inválido";
    }
    
    // Validar empleado
    if (empty($datos['id_empleado']) || !is_numeric($datos['id_empleado']) || $datos['id_empleado'] <= 0) {
        $errores[] = "El empleado que reporta es inválido";
    }
    
    // Validar descripción del accidente
    if (empty($datos['descripcion_accidente'])) {
        $errores[] = "La descripción del accidente es requerida";
    } else {
        $descripcion = trim($datos['descripcion_accidente']);
        if (strlen($descripcion) < 50) {
            $errores[] = "La descripción debe tener al menos 50 caracteres";
        }
        if (strlen($descripcion) > 2000) {
            $errores[] = "La descripción no puede exceder los 2000 caracteres";
        }
        // Validar caracteres en descripción
        if (!preg_match('/^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s\-\_\.\,\;\:\!\?\(\)\#\$\&\+\=\/\@\"\'\n\r]+$/', $descripcion)) {
            $errores[] = "La descripción contiene caracteres no permitidos";
        }
    }
    
    // Validar fecha y hora
    if (empty($datos['fecha_hora'])) {
        $errores[] = "La fecha y hora del accidente son requeridas";
    } else {
        $fecha_hora = $datos['fecha_hora'];
        // Validar formato datetime-local (YYYY-MM-DDTHH:MM)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $fecha_hora)) {
            $errores[] = "El formato de fecha y hora es inválido (YYYY-MM-DDTHH:MM)";
        } else {
            // Convertir a formato MySQL
            $fecha_mysql = str_replace('T', ' ', $fecha_hora) . ':00';
            $fecha_obj = DateTime::createFromFormat('Y-m-d H:i:s', $fecha_mysql);
            
            if (!$fecha_obj || $fecha_obj->format('Y-m-d H:i:s') !== $fecha_mysql) {
                $errores[] = "La fecha y hora proporcionadas son inválidas";
            } else {
                // Validar que la fecha no sea futura
                $ahora = new DateTime();
                if ($fecha_obj > $ahora) {
                    $errores[] = "La fecha y hora del accidente no pueden ser en el futuro";
                }
                
                // Validar que la fecha no sea muy antigua (máximo 1 año atrás)
                $fecha_limite = new DateTime();
                $fecha_limite->modify('-1 year');
                if ($fecha_obj < $fecha_limite) {
                    $errores[] = "La fecha del accidente no puede ser mayor a 1 año atrás";
                }
            }
        }
    }
    
    return $errores;
}

// Funciones de validación de existencia
function validarViaje($id_viaje) {
    $conn = conectar();
    $sql = "SELECT id_viaje FROM viajes WHERE id_viaje = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_viaje);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    desconectar($conn);
    return $exists;
}

function validarEmpleado($id_empleado) {
    $conn = conectar();
    $sql = "SELECT id_empleado FROM empleados WHERE id_empleado = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_empleado);
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
        case 'crear_accidente':
            crearAccidente();
            break;
        case 'actualizar_accidente':
            actualizarAccidente();
            break;
        case 'eliminar_accidente':
            eliminarAccidente();
            break;
    }
}

function crearAccidente() {
    global $conn;
    $conn = conectar();
    
    // Validar datos
    $errores = validarDatosAccidente($_POST);
    
    // Verificar existencia del viaje
    if (empty($errores)) {
        if (!validarViaje($_POST['id_viaje'])) {
            $errores[] = "El viaje seleccionado no existe";
        }
    }
    
    // Verificar existencia del empleado
    if (empty($errores)) {
        if (!validarEmpleado($_POST['id_empleado'])) {
            $errores[] = "El empleado seleccionado no existe";
        }
    }
    
    if (!empty($errores)) {
        $_SESSION['mensaje'] = "Errores de validación:<br>" . implode("<br>", $errores);
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: reportes_accidentes.php');
        exit();
    }
    
    $id_viaje = intval($_POST['id_viaje']);
    $id_empleado = intval($_POST['id_empleado']);
    $descripcion_accidente = trim($_POST['descripcion_accidente']);
    $fecha_hora = str_replace('T', ' ', $_POST['fecha_hora']) . ':00';
    
    $sql = "INSERT INTO reportes_accidentes (id_viaje, id_empleado, descripcion_accidente, fecha_hora) 
            VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiss", $id_viaje, $id_empleado, $descripcion_accidente, $fecha_hora);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Reporte de accidente registrado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al registrar accidente: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: reportes_accidentes.php');
    exit();
}

function actualizarAccidente() {
    global $conn;
    $conn = conectar();
    
    // Validar ID de accidente
    if (empty($_POST['id_accidente']) || !is_numeric($_POST['id_accidente']) || $_POST['id_accidente'] <= 0) {
        $_SESSION['mensaje'] = "ID de accidente inválido";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: reportes_accidentes.php');
        exit();
    }
    
    // Validar datos
    $errores = validarDatosAccidente($_POST);
    
    // Verificar existencia del viaje
    if (empty($errores)) {
        if (!validarViaje($_POST['id_viaje'])) {
            $errores[] = "El viaje seleccionado no existe";
        }
    }
    
    // Verificar existencia del empleado
    if (empty($errores)) {
        if (!validarEmpleado($_POST['id_empleado'])) {
            $errores[] = "El empleado seleccionado no existe";
        }
    }
    
    if (!empty($errores)) {
        $_SESSION['mensaje'] = "Errores de validación:<br>" . implode("<br>", $errores);
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: reportes_accidentes.php');
        exit();
    }
    
    $id_accidente = intval($_POST['id_accidente']);
    $id_viaje = intval($_POST['id_viaje']);
    $id_empleado = intval($_POST['id_empleado']);
    $descripcion_accidente = trim($_POST['descripcion_accidente']);
    $fecha_hora = str_replace('T', ' ', $_POST['fecha_hora']) . ':00';
    
    $sql = "UPDATE reportes_accidentes SET id_viaje = ?, id_empleado = ?, descripcion_accidente = ?, fecha_hora = ? 
            WHERE id_accidente = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissi", $id_viaje, $id_empleado, $descripcion_accidente, $fecha_hora, $id_accidente);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Reporte de accidente actualizado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar accidente: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: reportes_accidentes.php');
    exit();
}

function eliminarAccidente() {
    global $conn;
    $conn = conectar();
    
    $id_accidente = $_POST['id_accidente'] ?? '';
    
    // Validar que el ID no esté vacío
    if (empty($id_accidente) || !is_numeric($id_accidente) || $id_accidente <= 0) {
        $_SESSION['mensaje'] = "Error: No se proporcionó un ID de accidente válido.";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: reportes_accidentes.php');
        exit();
    }
    
    $id_accidente = intval($id_accidente);
    
    try {
        // Primero verificar si el accidente existe
        $check_accidente = $conn->prepare("SELECT id_accidente FROM reportes_accidentes WHERE id_accidente = ?");
        if (!$check_accidente) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }
        
        $check_accidente->bind_param("i", $id_accidente);
        
        if (!$check_accidente->execute()) {
            throw new Exception("Error al ejecutar la consulta: " . $check_accidente->error);
        }
        
        $result_accidente = $check_accidente->get_result();
        
        if ($result_accidente->num_rows === 0) {
            $_SESSION['mensaje'] = "Error: El reporte de accidente que intenta eliminar no existe en el sistema.";
            $_SESSION['tipo_mensaje'] = "error";
            $check_accidente->close();
            desconectar($conn);
            header('Location: reportes_accidentes.php');
            exit();
        }
        $check_accidente->close();
        
        // Verificar si existe la tabla seguimiento_accidentes y si tiene relación
        $check_tabla_seguimiento = $conn->query("SHOW TABLES LIKE 'seguimiento_accidentes'");
        if ($check_tabla_seguimiento && $check_tabla_seguimiento->num_rows > 0) {
            // Verificar si hay registros relacionados
            $check_relacion = $conn->prepare("SELECT COUNT(*) as count FROM seguimiento_accidentes WHERE id_accidente = ?");
            if ($check_relacion) {
                $check_relacion->bind_param("i", $id_accidente);
                $check_relacion->execute();
                $result_relacion = $check_relacion->get_result();
                $row_relacion = $result_relacion->fetch_assoc();
                $check_relacion->close();
                
                if ($row_relacion['count'] > 0) {
                    $_SESSION['mensaje'] = "No se puede eliminar el reporte de accidente porque tiene seguimientos registrados (" . $row_relacion['count'] . " registros relacionados).";
                    $_SESSION['tipo_mensaje'] = "error";
                    desconectar($conn);
                    header('Location: reportes_accidentes.php');
                    exit();
                }
            }
        }
        
        // Si no hay referencias, proceder con la eliminación
        $sql = "DELETE FROM reportes_accidentes WHERE id_accidente = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta de eliminación: " . $conn->error);
        }
        
        $stmt->bind_param("i", $id_accidente);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['mensaje'] = "Reporte de accidente eliminado exitosamente";
                $_SESSION['tipo_mensaje'] = "success";
            } else {
                $_SESSION['mensaje'] = "No se pudo eliminar el reporte de accidente. Es posible que ya haya sido eliminado o no exista.";
                $_SESSION['tipo_mensaje'] = "error";
            }
        } else {
            $error = $stmt->error;
            if (strpos($error, 'foreign key constraint') !== false) {
                $_SESSION['mensaje'] = "No se puede eliminar el reporte de accidente porque está siendo utilizado en otros registros del sistema.";
                $_SESSION['tipo_mensaje'] = "error";
            } else {
                $_SESSION['mensaje'] = "Error al eliminar accidente: " . $error;
                $_SESSION['tipo_mensaje'] = "error";
            }
        }
        
        $stmt->close();
        
    } catch (mysqli_sql_exception $e) {
        // Capturar excepciones específicas de MySQL
        $error_message = $e->getMessage();
        
        if (strpos($error_message, 'foreign key constraint fails') !== false) {
            $_SESSION['mensaje'] = "No se puede eliminar el reporte de accidente porque está siendo utilizado en otros registros del sistema.";
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
    header('Location: reportes_accidentes.php');
    exit();
}

// Obtener todos los accidentes para mostrar en la tabla
function obtenerAccidentes() {
    $conn = conectar();
    
    $sql = "SELECT ra.*, 
                   v.descripcion_viaje,
                   v.fecha_hora_salida,
                   ve.no_placa,
                   e.nombre_empleado,
                   e.apellido_empleado
            FROM reportes_accidentes ra
            LEFT JOIN viajes v ON ra.id_viaje = v.id_viaje
            LEFT JOIN vehiculos ve ON v.id_vehiculo = ve.id_vehiculo
            LEFT JOIN empleados e ON ra.id_empleado = e.id_empleado
            ORDER BY ra.fecha_hora DESC";
    
    $resultado = $conn->query($sql);
    $accidentes = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $accidentes[] = $fila;
        }
    }
    
    desconectar($conn);
    return $accidentes;
}

// Obtener viajes para el select
function obtenerViajes() {
    $conn = conectar();
    
    $sql = "SELECT v.id_viaje, v.descripcion_viaje, v.fecha_hora_salida, ve.no_placa, 
                   ep.nombre_empleado as nombre_piloto, ep.apellido_empleado as apellido_piloto
            FROM viajes v
            LEFT JOIN vehiculos ve ON v.id_vehiculo = ve.id_vehiculo
            LEFT JOIN empleados ep ON v.id_empleado_piloto = ep.id_empleado
            ORDER BY v.fecha_hora_salida DESC";
    
    $resultado = $conn->query($sql);
    $viajes = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $viajes[] = $fila;
        }
    }
    
    desconectar($conn);
    return $viajes;
}

// Obtener empleados para el select
function obtenerEmpleados() {
    $conn = conectar();
    
    $sql = "SELECT id_empleado, nombre_empleado, apellido_empleado 
            FROM empleados 
            ORDER BY nombre_empleado, apellido_empleado";
    
    $resultado = $conn->query($sql);
    $empleados = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $empleados[] = $fila;
        }
    }
    
    desconectar($conn);
    return $empleados;
}

$accidentes = obtenerAccidentes();
$viajes = obtenerViajes();
$empleados = obtenerEmpleados();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Accidentes - Marina Roja</title>
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Frameworks y librerías base -->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
</head>
<body>
    <header class="mb-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
            <h1 class="mb-0">REPORTES DE ACCIDENTES</h1>
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
            <h2 class="card__title text-primary mb-4">FORMULARIO - REPORTE DE ACCIDENTES</h2>

            <div class="alert alert-warning">
                <h5 class="alert-heading">⚠️ Importante</h5>
                <p class="mb-0">Este formulario es para reportar accidentes ocurridos durante los viajes. 
                Asegúrese de proporcionar una descripción detallada del incidente.</p>
            </div>

            <form id="form-accidentes" method="post" class="row g-3">
                <input type="hidden" id="operacion" name="operacion" value="crear_accidente">
                <input type="hidden" id="id_accidente" name="id_accidente" value="">
                
                <div class="col-md-6">
                    <label class="form-label" for="id_viaje">Viaje Relacionado:</label>
                    <select class="form-control" id="id_viaje" name="id_viaje" required>
                        <option value="">Seleccione un viaje</option>
                        <?php foreach($viajes as $viaje): ?>
                            <option value="<?php echo $viaje['id_viaje']; ?>">
                                Viaje #<?php echo $viaje['id_viaje']; ?> - 
                                <?php echo htmlspecialchars($viaje['no_placa'] ?? 'Sin placa'); ?> - 
                                <?php echo htmlspecialchars($viaje['descripcion_viaje'] ?? 'Sin descripción'); ?> -
                                <?php echo htmlspecialchars($viaje['fecha_hora_salida']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="id_empleado">Empleado que Reporta:</label>
                    <select class="form-control" id="id_empleado" name="id_empleado" required>
                        <option value="">Seleccione un empleado</option>
                        <?php foreach($empleados as $empleado): ?>
                            <option value="<?php echo $empleado['id_empleado']; ?>">
                                <?php echo htmlspecialchars($empleado['nombre_empleado'] . ' ' . $empleado['apellido_empleado']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-12">
                    <label class="form-label" for="descripcion_accidente">Descripción del Accidente:</label>
                    <textarea class="form-control" id="descripcion_accidente" name="descripcion_accidente" 
                              rows="4" maxlength="2000" required 
                              placeholder="Describa detalladamente el accidente ocurrido, incluyendo lugar, hora, daños, personas involucradas, etc."></textarea>
                    <div class="form-text">Mínimo 50 caracteres, máximo 2000 caracteres. Sea lo más descriptivo posible.</div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="fecha_hora">Fecha y Hora del Accidente:</label>
                    <input type="datetime-local" class="form-control" id="fecha_hora" name="fecha_hora" 
                           max="<?php echo date('Y-m-d\TH:i'); ?>" required>
                    <div class="form-text">No puede ser una fecha futura</div>
                </div>
            </form>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>

            <h2 class="card__title mb-3 mt-5">HISTORIAL DE ACCIDENTES REPORTADOS</h2>
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered" id="tabla-accidentes">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Viaje</th>
                            <th>Vehículo</th>
                            <th>Empleado</th>
                            <th>Descripción</th>
                            <th>Fecha/Hora</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($accidentes as $accidente): ?>
                        <tr>
                            <td>
                                <span class="badge bg-danger">#<?php echo htmlspecialchars($accidente['id_accidente']); ?></span>
                            </td>
                            <td>
                                Viaje #<?php echo htmlspecialchars($accidente['id_viaje']); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($accidente['descripcion_viaje'] ?? 'N/A'); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($accidente['no_placa'] ?? 'N/A'); ?></td>
                            <td>
                                <?php echo htmlspecialchars($accidente['nombre_empleado'] . ' ' . $accidente['apellido_empleado']); ?>
                            </td>
                            <td class="descripcion-cell" title="<?php echo htmlspecialchars($accidente['descripcion_accidente']); ?>">
                                <?php 
                                $descripcion = $accidente['descripcion_accidente'];
                                if (strlen($descripcion) > 100) {
                                    echo htmlspecialchars(substr($descripcion, 0, 100)) . '...';
                                } else {
                                    echo htmlspecialchars($descripcion);
                                }
                                ?>
                            </td>
                            <td class="fecha-cell">
                                <?php 
                                $fecha = new DateTime($accidente['fecha_hora']);
                                echo $fecha->format('d/m/Y H:i');
                                ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                        data-id="<?php echo $accidente['id_accidente']; ?>"
                                        data-viaje="<?php echo $accidente['id_viaje']; ?>"
                                        data-empleado="<?php echo $accidente['id_empleado']; ?>"
                                        data-descripcion="<?php echo htmlspecialchars($accidente['descripcion_accidente']); ?>"
                                        data-fecha="<?php echo str_replace(' ', 'T', substr($accidente['fecha_hora'], 0, 16)); ?>">
                                    Editar
                                </button>
                                <form method="post" style="display:inline;" data-eliminar="true">
                                    <input type="hidden" name="operacion" value="eliminar_accidente">
                                    <input type="hidden" name="id_accidente" value="<?php echo $accidente['id_accidente']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-action">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($accidentes)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No hay accidentes reportados</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/SistemaWebRestaurante/javascript/reportes_accidentes.js"></script>
</body>
</html>