<?php
session_start();
require_once '../conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// Función para validar y sanitizar datos de la ruta
function validarDatosRuta($datos) {
    $errores = [];
    
    // Validar descripción de la ruta
    if (empty($datos['descripcion_ruta'])) {
        $errores[] = "La descripción de la ruta es requerida";
    } else {
        $descripcion = trim($datos['descripcion_ruta']);
        if (strlen($descripcion) < 3) {
            $errores[] = "La descripción debe tener al menos 3 caracteres";
        }
        if (strlen($descripcion) > 200) {
            $errores[] = "La descripción no puede exceder los 200 caracteres";
        }
        // Validar caracteres en descripción
        if (!preg_match('/^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s\-\_\.\,\;\:\!\?\(\)\#\&]+$/', $descripcion)) {
            $errores[] = "La descripción contiene caracteres no permitidos";
        }
    }
    
    // Validar punto de inicio (opcional)
    if (!empty($datos['inicio_ruta'])) {
        $inicio = trim($datos['inicio_ruta']);
        if (strlen($inicio) > 100) {
            $errores[] = "El punto de inicio no puede exceder los 100 caracteres";
        }
        // Validar caracteres en inicio
        if (!preg_match('/^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s\-\_\.\,\;\:\!\?\(\)\#\&]+$/', $inicio)) {
            $errores[] = "El punto de inicio contiene caracteres no permitidos";
        }
    }
    
    // Validar punto final (opcional)
    if (!empty($datos['fin_ruta'])) {
        $fin = trim($datos['fin_ruta']);
        if (strlen($fin) > 100) {
            $errores[] = "El punto final no puede exceder los 100 caracteres";
        }
        // Validar caracteres en fin
        if (!preg_match('/^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s\-\_\.\,\;\:\!\?\(\)\#\&]+$/', $fin)) {
            $errores[] = "El punto final contiene caracteres no permitidos";
        }
    }
    
    // Validar gasolina aproximada (opcional)
    if (!empty($datos['gasolina_aproximada'])) {
        if (!is_numeric($datos['gasolina_aproximada'])) {
            $errores[] = "La gasolina aproximada debe ser un número válido";
        } else if ($datos['gasolina_aproximada'] < 0) {
            $errores[] = "La gasolina aproximada no puede ser negativa";
        } else if ($datos['gasolina_aproximada'] > 1000) {
            $errores[] = "La gasolina aproximada no puede ser mayor a 1000 litros";
        }
        
        // Validar formato de la gasolina (máximo 2 decimales)
        if (isset($datos['gasolina_aproximada']) && is_numeric($datos['gasolina_aproximada'])) {
            $partes = explode('.', (string)$datos['gasolina_aproximada']);
            if (count($partes) > 1 && strlen($partes[1]) > 2) {
                $errores[] = "La gasolina aproximada no puede tener más de 2 decimales";
            }
        }
    }
    
    return $errores;
}

// Función para verificar si ya existe una ruta con la misma descripción (evitar duplicados)
function verificarRutaExistente($descripcion_ruta, $id_ruta_excluir = null) {
    $conn = conectar();
    
    if ($id_ruta_excluir) {
        $sql = "SELECT id_ruta FROM rutas WHERE descripcion_ruta = ? AND id_ruta != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $descripcion_ruta, $id_ruta_excluir);
    } else {
        $sql = "SELECT id_ruta FROM rutas WHERE descripcion_ruta = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $descripcion_ruta);
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
        case 'crear_ruta':
            crearRuta();
            break;
        case 'actualizar_ruta':
            actualizarRuta();
            break;
        case 'eliminar_ruta':
            eliminarRuta();
            break;
    }
}

function crearRuta() {
    global $conn;
    $conn = conectar();
    
    // Validar datos
    $errores = validarDatosRuta($_POST);
    
    // Verificar si ya existe una ruta con la misma descripción
    if (empty($errores)) {
        $descripcion_ruta = trim($_POST['descripcion_ruta']);
        if (verificarRutaExistente($descripcion_ruta)) {
            $errores[] = "Ya existe una ruta con la misma descripción";
        }
    }
    
    if (!empty($errores)) {
        $_SESSION['mensaje'] = "Errores de validación:<br>" . implode("<br>", $errores);
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: rutas_vehiculos.php');
        exit();
    }
    
    $descripcion_ruta = trim($_POST['descripcion_ruta']);
    $inicio_ruta = !empty($_POST['inicio_ruta']) ? trim($_POST['inicio_ruta']) : null;
    $fin_ruta = !empty($_POST['fin_ruta']) ? trim($_POST['fin_ruta']) : null;
    $gasolina_aproximada = !empty($_POST['gasolina_aproximada']) ? floatval($_POST['gasolina_aproximada']) : null;
    
    // Redondear gasolina a 2 decimales si se proporcionó
    if ($gasolina_aproximada !== null) {
        $gasolina_aproximada = round($gasolina_aproximada, 2);
    }
    
    $sql = "INSERT INTO rutas (descripcion_ruta, inicio_ruta, fin_ruta, gasolina_aproximada) 
            VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssd", $descripcion_ruta, $inicio_ruta, $fin_ruta, $gasolina_aproximada);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Ruta creada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al crear ruta: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: rutas_vehiculos.php');
    exit();
}

function actualizarRuta() {
    global $conn;
    $conn = conectar();
    
    // Validar ID de ruta
    if (empty($_POST['id_ruta']) || !is_numeric($_POST['id_ruta']) || $_POST['id_ruta'] <= 0) {
        $_SESSION['mensaje'] = "ID de ruta inválido";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: rutas_vehiculos.php');
        exit();
    }
    
    // Validar datos
    $errores = validarDatosRuta($_POST);
    
    // Verificar si ya existe otra ruta con la misma descripción
    if (empty($errores)) {
        $descripcion_ruta = trim($_POST['descripcion_ruta']);
        $id_ruta = intval($_POST['id_ruta']);
        if (verificarRutaExistente($descripcion_ruta, $id_ruta)) {
            $errores[] = "Ya existe otra ruta con la misma descripción";
        }
    }
    
    if (!empty($errores)) {
        $_SESSION['mensaje'] = "Errores de validación:<br>" . implode("<br>", $errores);
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: rutas_vehiculos.php');
        exit();
    }
    
    $id_ruta = intval($_POST['id_ruta']);
    $descripcion_ruta = trim($_POST['descripcion_ruta']);
    $inicio_ruta = !empty($_POST['inicio_ruta']) ? trim($_POST['inicio_ruta']) : null;
    $fin_ruta = !empty($_POST['fin_ruta']) ? trim($_POST['fin_ruta']) : null;
    $gasolina_aproximada = !empty($_POST['gasolina_aproximada']) ? floatval($_POST['gasolina_aproximada']) : null;
    
    // Redondear gasolina a 2 decimales si se proporcionó
    if ($gasolina_aproximada !== null) {
        $gasolina_aproximada = round($gasolina_aproximada, 2);
    }
    
    $sql = "UPDATE rutas SET descripcion_ruta = ?, inicio_ruta = ?, fin_ruta = ?, gasolina_aproximada = ? 
            WHERE id_ruta = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdi", $descripcion_ruta, $inicio_ruta, $fin_ruta, $gasolina_aproximada, $id_ruta);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Ruta actualizada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar ruta: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: rutas_vehiculos.php');
    exit();
}

function eliminarRuta() {
    global $conn;
    $conn = conectar();
    
    $id_ruta = $_POST['id_ruta'] ?? '';
    
    // Validar que el ID no esté vacío
    if (empty($id_ruta) || !is_numeric($id_ruta) || $id_ruta <= 0) {
        $_SESSION['mensaje'] = "Error: No se proporcionó un ID de ruta válido.";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: rutas_vehiculos.php');
        exit();
    }
    
    $id_ruta = intval($id_ruta);
    
    try {
        // Primero verificar si la ruta existe
        $check_ruta = $conn->prepare("SELECT id_ruta, descripcion_ruta FROM rutas WHERE id_ruta = ?");
        if (!$check_ruta) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }
        
        $check_ruta->bind_param("i", $id_ruta);
        
        if (!$check_ruta->execute()) {
            throw new Exception("Error al ejecutar la consulta: " . $check_ruta->error);
        }
        
        $result_ruta = $check_ruta->get_result();
        
        if ($result_ruta->num_rows === 0) {
            $_SESSION['mensaje'] = "Error: La ruta que intenta eliminar no existe en el sistema.";
            $_SESSION['tipo_mensaje'] = "error";
            $check_ruta->close();
            desconectar($conn);
            header('Location: rutas_vehiculos.php');
            exit();
        }
        
        $ruta = $result_ruta->fetch_assoc();
        $descripcion_ruta = $ruta['descripcion_ruta'];
        $check_ruta->close();
        
        // Verificar si la ruta está siendo usada en la tabla viajes
        $check_viajes = $conn->prepare("SELECT COUNT(*) as count FROM viajes WHERE id_ruta = ?");
        $check_viajes->bind_param("i", $id_ruta);
        $check_viajes->execute();
        $result_viajes = $check_viajes->get_result();
        $row_viajes = $result_viajes->fetch_assoc();
        $check_viajes->close();
        
        if ($row_viajes['count'] > 0) {
            $_SESSION['mensaje'] = "No se puede eliminar la ruta \"$descripcion_ruta\" porque está siendo utilizada en viajes registrados (" . $row_viajes['count'] . " viajes relacionados).";
            $_SESSION['tipo_mensaje'] = "error";
            desconectar($conn);
            header('Location: rutas_vehiculos.php');
            exit();
        }
        
        // Si no hay referencias, proceder con la eliminación
        $sql = "DELETE FROM rutas WHERE id_ruta = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta de eliminación: " . $conn->error);
        }
        
        $stmt->bind_param("i", $id_ruta);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['mensaje'] = "Ruta \"$descripcion_ruta\" eliminada exitosamente";
                $_SESSION['tipo_mensaje'] = "success";
            } else {
                $_SESSION['mensaje'] = "No se pudo eliminar la ruta. Es posible que ya haya sido eliminada o no exista.";
                $_SESSION['tipo_mensaje'] = "error";
            }
        } else {
            $error = $stmt->error;
            if (strpos($error, 'foreign key constraint') !== false) {
                $_SESSION['mensaje'] = "No se puede eliminar la ruta \"$descripcion_ruta\" porque está siendo utilizada en otros registros del sistema.";
                $_SESSION['tipo_mensaje'] = "error";
            } else {
                $_SESSION['mensaje'] = "Error al eliminar ruta: " . $error;
                $_SESSION['tipo_mensaje'] = "error";
            }
        }
        
        $stmt->close();
        
    } catch (mysqli_sql_exception $e) {
        // Capturar excepciones específicas de MySQL
        $error_message = $e->getMessage();
        
        if (strpos($error_message, 'foreign key constraint fails') !== false) {
            $_SESSION['mensaje'] = "No se puede eliminar la ruta \"$descripcion_ruta\" porque está siendo utilizada en otros registros del sistema.";
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
    header('Location: rutas_vehiculos.php');
    exit();
}

// Obtener todas las rutas para mostrar en la tabla
function obtenerRutas() {
    $conn = conectar();
    
    $sql = "SELECT * FROM rutas ORDER BY descripcion_ruta";
    $resultado = $conn->query($sql);
    $rutas = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $rutas[] = $fila;
        }
    }
    
    desconectar($conn);
    return $rutas;
}

$rutas = obtenerRutas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Rutas - Marina Roja</title>
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Frameworks y librerías base -->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
</head>
<body>
    <header class="mb-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
            <h1 class="mb-0">GESTIÓN DE RUTAS DE VEHÍCULOS</h1>
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
            <h2 class="card__title text-primary mb-4">FORMULARIO - REGISTRO DE RUTAS</h2>

            <form id="form-rutas" method="post" class="row g-3">
                <input type="hidden" id="operacion" name="operacion" value="crear_ruta">
                <input type="hidden" id="id_ruta" name="id_ruta" value="">
                
                <div class="col-12">
                    <label class="form-label" for="descripcion_ruta">Descripción de la Ruta:</label>
                    <input type="text" class="form-control" id="descripcion_ruta" name="descripcion_ruta" 
                           maxlength="200" required placeholder="Ej. Ruta de entregas zona centro">
                    <div class="form-text">Mínimo 3 caracteres, máximo 200 caracteres</div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label" for="inicio_ruta">Punto de Inicio:</label>
                    <input type="text" class="form-control" id="inicio_ruta" name="inicio_ruta" 
                           maxlength="100" placeholder="Ej. Bodega principal">
                    <div class="form-text">Máximo 100 caracteres</div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label" for="fin_ruta">Punto Final:</label>
                    <input type="text" class="form-control" id="fin_ruta" name="fin_ruta" 
                           maxlength="100" placeholder="Ej. Última entrega">
                    <div class="form-text">Máximo 100 caracteres</div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label" for="gasolina_aproximada">Gasolina Aproximada (Litros):</label>
                    <input type="number" step="0.01" class="form-control" id="gasolina_aproximada" name="gasolina_aproximada" 
                           min="0" max="1000" placeholder="Ej. 15.50">
                    <div class="form-text">Máximo 1000 litros</div>
                </div>
            </form>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>

            <h2 class="card__title mb-3 mt-5">RUTAS REGISTRADAS</h2>
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered" id="tabla-rutas">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Descripción</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th>Gasolina (L)</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($rutas as $ruta): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ruta['id_ruta']); ?></td>
                            <td class="descripcion-cell" title="<?php echo htmlspecialchars($ruta['descripcion_ruta']); ?>">
                                <?php echo htmlspecialchars($ruta['descripcion_ruta']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($ruta['inicio_ruta'] ?? 'No especificado'); ?></td>
                            <td><?php echo htmlspecialchars($ruta['fin_ruta'] ?? 'No especificado'); ?></td>
                            <td class="text-end fw-bold">
                                <?php echo $ruta['gasolina_aproximada'] ? number_format($ruta['gasolina_aproximada'], 2) . ' L' : 'No especificado'; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                        data-id="<?php echo $ruta['id_ruta']; ?>"
                                        data-descripcion="<?php echo htmlspecialchars($ruta['descripcion_ruta']); ?>"
                                        data-inicio="<?php echo htmlspecialchars($ruta['inicio_ruta'] ?? ''); ?>"
                                        data-fin="<?php echo htmlspecialchars($ruta['fin_ruta'] ?? ''); ?>"
                                        data-gasolina="<?php echo $ruta['gasolina_aproximada']; ?>">
                                    Editar
                                </button>
                                <form method="post" style="display:inline;" data-eliminar="true">
                                    <input type="hidden" name="operacion" value="eliminar_ruta">
                                    <input type="hidden" name="id_ruta" value="<?php echo $ruta['id_ruta']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-action">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($rutas)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No hay rutas registradas</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/SistemaWebRestaurante/javascript/rutas_vehiculos.js"></script>
</body>
</html>