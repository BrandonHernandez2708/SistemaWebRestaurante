<?php
session_start();
require_once '../conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// Función para validar y sanitizar datos del vehículo
function validarDatosVehiculo($datos) {
    $errores = [];
    
    // Validar placa
    if (empty($datos['no_placas'])) {
        $errores[] = "La placa del vehículo es requerida";
    } else {
        $placa = trim($datos['no_placas']);
        if (strlen($placa) < 3) {
            $errores[] = "La placa debe tener al menos 3 caracteres";
        }
        if (strlen($placa) > 15) {
            $errores[] = "La placa no puede exceder los 15 caracteres";
        }
        // Validar formato de placa (letras, números, guiones)
        if (!preg_match('/^[A-Z0-9\-]+$/', $placa)) {
            $errores[] = "La placa solo puede contener letras mayúsculas, números y guiones";
        }
    }
    
    // Validar marca
    if (empty($datos['marca'])) {
        $errores[] = "La marca del vehículo es requerida";
    } else {
        $marca = trim($datos['marca']);
        if (strlen($marca) < 2) {
            $errores[] = "La marca debe tener al menos 2 caracteres";
        }
        if (strlen($marca) > 50) {
            $errores[] = "La marca no puede exceder los 50 caracteres";
        }
        // Validar caracteres permitidos en marca
        if (!preg_match('/^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s\-\_\.]+$/', $marca)) {
            $errores[] = "La marca contiene caracteres no permitidos";
        }
    }
    
    // Validar modelo
    if (empty($datos['modelo'])) {
        $errores[] = "El modelo del vehículo es requerido";
    } else {
        $modelo = trim($datos['modelo']);
        if (strlen($modelo) < 1) {
            $errores[] = "El modelo debe tener al menos 1 caracter";
        }
        if (strlen($modelo) > 50) {
            $errores[] = "El modelo no puede exceder los 50 caracteres";
        }
        // Validar caracteres permitidos en modelo
        if (!preg_match('/^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s\-\_\.]+$/', $modelo)) {
            $errores[] = "El modelo contiene caracteres no permitidos";
        }
    }
    
    // Validar año
    if (empty($datos['anio_vehiculo']) || !is_numeric($datos['anio_vehiculo'])) {
        $errores[] = "El año del vehículo es requerido y debe ser un número";
    } else {
        $anio = intval($datos['anio_vehiculo']);
        $anio_actual = date('Y');
        $anio_minimo = 1900;
        $anio_maximo = $anio_actual + 1; // Permitir vehículos del próximo año
        
        if ($anio < $anio_minimo || $anio > $anio_maximo) {
            $errores[] = "El año debe estar entre {$anio_minimo} y {$anio_maximo}";
        }
    }
    
    // Validar estado
    if (empty($datos['estado'])) {
        $errores[] = "El estado del vehículo es requerido";
    } else {
        $estados_permitidos = ['ACTIVO', 'EN_TALLER', 'BAJA'];
        if (!in_array($datos['estado'], $estados_permitidos)) {
            $errores[] = "El estado seleccionado no es válido";
        }
    }
    
    // Validar descripción (opcional)
    if (!empty($datos['descripcion'])) {
        $descripcion = trim($datos['descripcion']);
        if (strlen($descripcion) > 500) {
            $errores[] = "La descripción no puede exceder los 500 caracteres";
        }
        // Validar caracteres en descripción
        if (!preg_match('/^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s\-\_\.\,\;\:\!\?\(\)]+$/', $descripcion)) {
            $errores[] = "La descripción contiene caracteres no permitidos";
        }
    }
    
    // Validar mobiliario (opcional)
    if (!empty($datos['id_mobiliario'])) {
        if (!is_numeric($datos['id_mobiliario']) || $datos['id_mobiliario'] <= 0) {
            $errores[] = "El ID del mobiliario asociado es inválido";
        }
    }
    
    return $errores;
}

// Función para verificar existencia del mobiliario
function verificarMobiliario($id_mobiliario) {
    $conn = conectar();
    $sql = "SELECT id_mobiliario FROM inventario_mobiliario WHERE id_mobiliario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_mobiliario);
    $stmt->execute();
    $result = $stmt->get_result();
    $existe = $result->num_rows > 0;
    $stmt->close();
    desconectar($conn);
    return $existe;
}

// Función para verificar si ya existe un vehículo con la misma placa (evitar duplicados)
function verificarPlacaExistente($placa, $id_vehiculo_excluir = null) {
    $conn = conectar();
    
    if ($id_vehiculo_excluir) {
        $sql = "SELECT id_vehiculo FROM vehiculos WHERE no_placa = ? AND id_vehiculo != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $placa, $id_vehiculo_excluir);
    } else {
        $sql = "SELECT id_vehiculo FROM vehiculos WHERE no_placa = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $placa);
    }
    
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
        case 'crear':
            crearVehiculo();
            break;
        case 'actualizar':
            actualizarVehiculo();
            break;
        case 'eliminar':
            eliminarVehiculo();
            break;
    }
}

function crearVehiculo() {
    global $conn;
    $conn = conectar();
    
    // Validar datos
    $errores = validarDatosVehiculo($_POST);
    
    // Verificar existencia del mobiliario si se proporcionó
    if (empty($errores) && !empty($_POST['id_mobiliario'])) {
        if (!verificarMobiliario($_POST['id_mobiliario'])) {
            $errores[] = "El mobiliario seleccionado no existe";
        }
    }
    
    // Verificar si ya existe un vehículo con la misma placa
    if (empty($errores)) {
        $placa = trim($_POST['no_placas']);
        if (verificarPlacaExistente($placa)) {
            $errores[] = "Ya existe un vehículo con la misma placa";
        }
    }
    
    if (!empty($errores)) {
        $_SESSION['mensaje'] = "Errores de validación:<br>" . implode("<br>", $errores);
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: gestion_vehiculos.php');
        exit();
    }
    
    $no_placa = trim($_POST['no_placas']);
    $marca_vehiculo = trim($_POST['marca']);
    $modelo_vehiculo = trim($_POST['modelo']);
    $anio_vehiculo = intval($_POST['anio_vehiculo']);
    $descripcion = !empty($_POST['descripcion']) ? trim($_POST['descripcion']) : null;
    $estado = $_POST['estado'];
    $id_mobiliario = !empty($_POST['id_mobiliario']) ? intval($_POST['id_mobiliario']) : null;
    
    $sql = "INSERT INTO vehiculos (no_placa, marca_vehiculo, modelo_vehiculo, anio_vehiculo, descripcion, estado, id_mobiliario) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssissi", $no_placa, $marca_vehiculo, $modelo_vehiculo, $anio_vehiculo, $descripcion, $estado, $id_mobiliario);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Vehículo creado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al crear vehículo: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: gestion_vehiculos.php');
    exit();
}

function actualizarVehiculo() {
    global $conn;
    $conn = conectar();
    
    // Validar ID de vehículo
    if (empty($_POST['id_placa']) || !is_numeric($_POST['id_placa']) || $_POST['id_placa'] <= 0) {
        $_SESSION['mensaje'] = "ID de vehículo inválido";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: gestion_vehiculos.php');
        exit();
    }
    
    // Validar datos
    $errores = validarDatosVehiculo($_POST);
    
    // Verificar existencia del mobiliario si se proporcionó
    if (empty($errores) && !empty($_POST['id_mobiliario'])) {
        if (!verificarMobiliario($_POST['id_mobiliario'])) {
            $errores[] = "El mobiliario seleccionado no existe";
        }
    }
    
    // Verificar si ya existe otro vehículo con la misma placa
    if (empty($errores)) {
        $placa = trim($_POST['no_placas']);
        $id_vehiculo = intval($_POST['id_placa']);
        if (verificarPlacaExistente($placa, $id_vehiculo)) {
            $errores[] = "Ya existe otro vehículo con la misma placa";
        }
    }
    
    if (!empty($errores)) {
        $_SESSION['mensaje'] = "Errores de validación:<br>" . implode("<br>", $errores);
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: gestion_vehiculos.php');
        exit();
    }
    
    $id_vehiculo = intval($_POST['id_placa']);
    $no_placa = trim($_POST['no_placas']);
    $marca_vehiculo = trim($_POST['marca']);
    $modelo_vehiculo = trim($_POST['modelo']);
    $anio_vehiculo = intval($_POST['anio_vehiculo']);
    $descripcion = !empty($_POST['descripcion']) ? trim($_POST['descripcion']) : null;
    $estado = $_POST['estado'];
    $id_mobiliario = !empty($_POST['id_mobiliario']) ? intval($_POST['id_mobiliario']) : null;
    
    $sql = "UPDATE vehiculos SET no_placa = ?, marca_vehiculo = ?, modelo_vehiculo = ?, anio_vehiculo = ?, descripcion = ?, estado = ?, id_mobiliario = ? 
            WHERE id_vehiculo = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssissii", $no_placa, $marca_vehiculo, $modelo_vehiculo, $anio_vehiculo, $descripcion, $estado, $id_mobiliario, $id_vehiculo);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Vehículo actualizado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar vehículo: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: gestion_vehiculos.php');
    exit();
}

function eliminarVehiculo() {
    global $conn;
    $conn = conectar();
    
    $id_vehiculo = $_POST['id_placa'] ?? '';
    
    // Validar que el ID no esté vacío
    if (empty($id_vehiculo) || !is_numeric($id_vehiculo) || $id_vehiculo <= 0) {
        $_SESSION['mensaje'] = "Error: No se proporcionó un ID de vehículo válido.";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: gestion_vehiculos.php');
        exit();
    }
    
    $id_vehiculo = intval($id_vehiculo);
    
    try {
        // Primero verificar si el vehículo existe
        $check_vehiculo = $conn->prepare("SELECT id_vehiculo, no_placa FROM vehiculos WHERE id_vehiculo = ?");
        if (!$check_vehiculo) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }
        
        $check_vehiculo->bind_param("i", $id_vehiculo);
        
        if (!$check_vehiculo->execute()) {
            throw new Exception("Error al ejecutar la consulta: " . $check_vehiculo->error);
        }
        
        $result_vehiculo = $check_vehiculo->get_result();
        
        if ($result_vehiculo->num_rows === 0) {
            $_SESSION['mensaje'] = "Error: El vehículo que intenta eliminar no existe en el sistema.";
            $_SESSION['tipo_mensaje'] = "error";
            $check_vehiculo->close();
            desconectar($conn);
            header('Location: gestion_vehiculos.php');
            exit();
        }
        
        $vehiculo = $result_vehiculo->fetch_assoc();
        $placa_vehiculo = $vehiculo['no_placa'];
        $check_vehiculo->close();
        
        // Verificar si el vehículo está siendo usado en viajes
        $check_viajes = $conn->prepare("SELECT COUNT(*) as count FROM viajes WHERE id_vehiculo = ?");
        $check_viajes->bind_param("i", $id_vehiculo);
        $check_viajes->execute();
        $result_viajes = $check_viajes->get_result();
        $row_viajes = $result_viajes->fetch_assoc();
        $check_viajes->close();
        
        if ($row_viajes['count'] > 0) {
            $_SESSION['mensaje'] = "No se puede eliminar el vehículo con placa \"$placa_vehiculo\" porque está siendo utilizado en viajes registrados (" . $row_viajes['count'] . " viajes relacionados).";
            $_SESSION['tipo_mensaje'] = "error";
            desconectar($conn);
            header('Location: gestion_vehiculos.php');
            exit();
        }
        
        // Verificar si existe la tabla mantenimientos y si tiene relación
        $check_tabla_mantenimiento = $conn->query("SHOW TABLES LIKE 'mantenimientos'");
        if ($check_tabla_mantenimiento && $check_tabla_mantenimiento->num_rows > 0) {
            $check_mantenimiento = $conn->prepare("SELECT COUNT(*) as count FROM mantenimientos WHERE id_vehiculo = ?");
            if ($check_mantenimiento) {
                $check_mantenimiento->bind_param("i", $id_vehiculo);
                $check_mantenimiento->execute();
                $result_mantenimiento = $check_mantenimiento->get_result();
                $row_mantenimiento = $result_mantenimiento->fetch_assoc();
                $check_mantenimiento->close();
                
                if ($row_mantenimiento['count'] > 0) {
                    $_SESSION['mensaje'] = "No se puede eliminar el vehículo con placa \"$placa_vehiculo\" porque está siendo utilizado en registros de mantenimiento (" . $row_mantenimiento['count'] . " registros relacionados).";
                    $_SESSION['tipo_mensaje'] = "error";
                    desconectar($conn);
                    header('Location: gestion_vehiculos.php');
                    exit();
                }
            }
        }
        
        // Si no hay referencias, proceder con la eliminación
        $sql = "DELETE FROM vehiculos WHERE id_vehiculo = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta de eliminación: " . $conn->error);
        }
        
        $stmt->bind_param("i", $id_vehiculo);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['mensaje'] = "Vehículo con placa \"$placa_vehiculo\" eliminado exitosamente";
                $_SESSION['tipo_mensaje'] = "success";
            } else {
                $_SESSION['mensaje'] = "No se pudo eliminar el vehículo. Es posible que ya haya sido eliminado o no exista.";
                $_SESSION['tipo_mensaje'] = "error";
            }
        } else {
            $error = $stmt->error;
            if (strpos($error, 'foreign key constraint') !== false) {
                $_SESSION['mensaje'] = "No se puede eliminar el vehículo con placa \"$placa_vehiculo\" porque está siendo utilizado en otros registros del sistema.";
                $_SESSION['tipo_mensaje'] = "error";
            } else {
                $_SESSION['mensaje'] = "Error al eliminar vehículo: " . $error;
                $_SESSION['tipo_mensaje'] = "error";
            }
        }
        
        $stmt->close();
        
    } catch (mysqli_sql_exception $e) {
        // Capturar excepciones específicas de MySQL
        $error_message = $e->getMessage();
        
        if (strpos($error_message, 'foreign key constraint fails') !== false) {
            $_SESSION['mensaje'] = "No se puede eliminar el vehículo porque está siendo utilizado en otros registros del sistema.";
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
    header('Location: gestion_vehiculos.php');
    exit();
}

// Obtener todos los vehículos para mostrar en la tabla
function obtenerVehiculos() {
    $conn = conectar();
    
    $sql = "SELECT v.*, im.nombre_mobiliario 
            FROM vehiculos v 
            LEFT JOIN inventario_mobiliario im ON v.id_mobiliario = im.id_mobiliario 
            ORDER BY v.id_vehiculo";
    
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

// Obtener mobiliario para el select
function obtenerMobiliario() {
    $conn = conectar();
    
    try {
        $sql = "SELECT id_mobiliario, nombre_mobiliario 
                FROM inventario_mobiliario 
                WHERE id_tipo_mobiliario = 1 
                ORDER BY nombre_mobiliario";
        
        $resultado = $conn->query($sql);
        $mobiliario = [];
        
        if ($resultado && $resultado->num_rows > 0) {
            while($fila = $resultado->fetch_assoc()) {
                $mobiliario[] = $fila;
            }
        }
        
        desconectar($conn);
        return $mobiliario;
        
    } catch (Exception $e) {
        desconectar($conn);
        return [];
    }
}

$vehiculos = obtenerVehiculos();
$mobiliarios = obtenerMobiliario();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Vehículos - Marina Roja</title>
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Frameworks y librerías base -->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
</head>
<body>
    <header class="mb-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
            <h1 class="mb-0">Gestión de Vehículos</h1>
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
            <h2 class="card__title text-primary mb-4">FORMULARIO - Vehículos</h2>

            <form id="form-vehiculo" method="post" class="row g-3">
                <input type="hidden" id="operacion" name="operacion" value="crear">
                <input type="hidden" id="id_placa" name="id_placa" value="">
                
                <div class="col-md-3">
                    <label class="form-label" for="no_placas">Placa:</label>
                    <input type="text" class="form-control" id="no_placas" name="no_placas" 
                           maxlength="15" required placeholder="Ej. P812HYN">
                    <div class="form-text">Máximo 15 caracteres (solo letras, números y guiones)</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="marca">Marca:</label>
                    <input type="text" class="form-control" id="marca" name="marca" 
                           maxlength="50" required placeholder="Ej. Ford">
                    <div class="form-text">Mínimo 2 caracteres, máximo 50</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="modelo">Modelo:</label>
                    <input type="text" class="form-control" id="modelo" name="modelo" 
                           maxlength="50" required placeholder="Ej. Ranger">
                    <div class="form-text">Mínimo 1 caracter, máximo 50</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="anio_vehiculo">Año:</label>
                    <input type="number" class="form-control" id="anio_vehiculo" name="anio_vehiculo" 
                           min="1900" max="<?php echo date('Y') + 1; ?>" required placeholder="Ej. 2014">
                    <div class="form-text">Entre 1900 y <?php echo date('Y') + 1; ?></div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="estado">Estado:</label>
                    <select class="form-control" id="estado" name="estado" required>
                        <option value="ACTIVO">ACTIVO</option>
                        <option value="EN_TALLER">EN TALLER</option>
                        <option value="BAJA">BAJA</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="id_mobiliario">Mobiliario Asociado:</label>
                    <select class="form-control" id="id_mobiliario" name="id_mobiliario">
                        <option value="">-- Sin mobiliario --</option>
                        <?php if (!empty($mobiliarios)): ?>
                            <?php foreach($mobiliarios as $mob): ?>
                                <option value="<?php echo $mob['id_mobiliario']; ?>">
                                    <?php echo htmlspecialchars($mob['nombre_mobiliario']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">-- No hay mobiliario en inventario --</option>
                        <?php endif; ?>
                    </select>
                    <?php if (empty($mobiliarios)): ?>
                        <small class="text-muted">No se encontraron registros de mobiliario en el inventario</small>
                    <?php endif; ?>
                </div>
                
                <div class="col-12">
                    <label class="form-label" for="descripcion">Descripción:</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" 
                              rows="2" maxlength="500" 
                              placeholder="Ej. Vehículo para entregas a domicilio, color blanco, etc."></textarea>
                    <div class="form-text">Máximo 500 caracteres</div>
                </div>
            </form>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>

            <h2 class="card__title mb-3 mt-5">Lista de Vehículos</h2>
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered" id="tabla-vehiculos">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Placa</th>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Año</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Mobiliario (Inventario)</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($vehiculos as $vehiculo): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($vehiculo['id_vehiculo'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($vehiculo['no_placa'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($vehiculo['marca_vehiculo'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($vehiculo['modelo_vehiculo'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($vehiculo['anio_vehiculo'] ?? 'N/A'); ?></td>
                            <td class="descripcion-cell" title="<?php echo htmlspecialchars($vehiculo['descripcion'] ?? ''); ?>">
                                <?php 
                                $descripcion = $vehiculo['descripcion'] ?? 'Sin descripción';
                                if (strlen($descripcion) > 50) {
                                    echo htmlspecialchars(substr($descripcion, 0, 50)) . '...';
                                } else {
                                    echo htmlspecialchars($descripcion);
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                $estado = $vehiculo['estado'] ?? 'ACTIVO';
                                $badge_class = '';
                                switch($estado) {
                                    case 'ACTIVO':
                                        $badge_class = 'badge bg-success';
                                        break;
                                    case 'EN_TALLER':
                                        $badge_class = 'badge bg-warning text-dark';
                                        break;
                                    case 'BAJA':
                                        $badge_class = 'badge bg-danger';
                                        break;
                                    default:
                                        $badge_class = 'badge bg-success';
                                }
                                ?>
                                <span class="<?php echo $badge_class; ?>">
                                    <?php echo htmlspecialchars($estado); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($vehiculo['nombre_mobiliario'] ?? 'Ninguno'); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                        data-id="<?php echo $vehiculo['id_vehiculo'] ?? ''; ?>"
                                        data-placas="<?php echo htmlspecialchars($vehiculo['no_placa'] ?? ''); ?>"
                                        data-marca="<?php echo htmlspecialchars($vehiculo['marca_vehiculo'] ?? ''); ?>"
                                        data-modelo="<?php echo htmlspecialchars($vehiculo['modelo_vehiculo'] ?? ''); ?>"
                                        data-anio="<?php echo $vehiculo['anio_vehiculo'] ?? ''; ?>"
                                        data-descripcion="<?php echo htmlspecialchars($vehiculo['descripcion'] ?? ''); ?>"
                                        data-estado="<?php echo $vehiculo['estado'] ?? 'ACTIVO'; ?>"
                                        data-mobiliario="<?php echo $vehiculo['id_mobiliario'] ?? ''; ?>">
                                    Editar
                                </button>
                                <form method="post" style="display:inline;" data-eliminar="true">
                                    <input type="hidden" name="operacion" value="eliminar">
                                    <input type="hidden" name="id_placa" value="<?php echo $vehiculo['id_vehiculo'] ?? ''; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-action">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($vehiculos)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No hay vehículos registrados</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/SistemaWebRestaurante/javascript/gestion_vehiculos.js"></script>
</body>
</html>