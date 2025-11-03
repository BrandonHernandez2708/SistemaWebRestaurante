<?php
session_start();
require_once '../conexion.php';

// Verificar login
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

/* ===========================
   VALIDACIONES / UTILIDADES
   =========================== */

// Valida datos base del detalle
function validarDatosDetalleCompra($datos) {
    $errores = [];

    // id_compra_mobiliario
    if (empty($datos['id_compra_mobiliario']) || !is_numeric($datos['id_compra_mobiliario']) || $datos['id_compra_mobiliario'] <= 0) {
        $errores[] = "El ID de la compra es inválido";
    }

    // id_mobiliario
    if (empty($datos['id_mobiliario']) || !is_numeric($datos['id_mobiliario']) || $datos['id_mobiliario'] <= 0) {
        $errores[] = "El ID del mobiliario es inválido";
    }

    // cantidad
    if (!isset($datos['cantidad_de_compra']) || !is_numeric($datos['cantidad_de_compra'])) {
        $errores[] = "La cantidad debe ser un número válido";
    } else if ($datos['cantidad_de_compra'] <= 0) {
        $errores[] = "La cantidad debe ser mayor a cero";
    } else if ($datos['cantidad_de_compra'] > 10000) {
        $errores[] = "La cantidad no puede ser mayor a 10,000 unidades";
    }

    // costo_unitario
    if (!isset($datos['costo_unitario']) || !is_numeric($datos['costo_unitario'])) {
        $errores[] = "El costo unitario debe ser un número válido";
    } else if ($datos['costo_unitario'] <= 0) {
        $errores[] = "El costo unitario debe ser mayor a cero";
    } else {
        // Máximo 2 decimales
        $partes = explode('.', (string)$datos['costo_unitario']);
        if (count($partes) > 1 && strlen($partes[1]) > 2) {
            $errores[] = "El costo unitario no puede tener más de 2 decimales";
        }
        if ($datos['costo_unitario'] > 100000) {
            $errores[] = "El costo unitario no puede ser mayor a Q 100,000.00";
        }
    }

    return $errores;
}

function verificarCompra($id_compra_mobiliario) {
    $conn = conectar();
    $sql = "SELECT id_compra_mobiliario, monto_total_compra_q FROM compras_mobiliario WHERE id_compra_mobiliario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_compra_mobiliario);
    $stmt->execute();
    $res = $stmt->get_result();
    $fila = $res->fetch_assoc();
    $stmt->close();
    desconectar($conn);
    return $fila ?: null;
}

function verificarMobiliario($id_mobiliario) {
    $conn = conectar();
    $sql = "SELECT id_mobiliario FROM inventario_mobiliario WHERE id_mobiliario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_mobiliario);
    $stmt->execute();
    $res = $stmt->get_result();
    $existe = $res->num_rows > 0;
    $stmt->close();
    desconectar($conn);
    return $existe;
}

function verificarDetalleExistente($id_compra_mobiliario, $id_mobiliario) {
    $conn = conectar();
    $sql = "SELECT 1 FROM detalle_compra_mobiliario WHERE id_compra_mobiliario = ? AND id_mobiliario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_compra_mobiliario, $id_mobiliario);
    $stmt->execute();
    $res = $stmt->get_result();
    $existe = $res->num_rows > 0;
    $stmt->close();
    desconectar($conn);
    return $existe;
}

/**
 * Obtiene la suma acumulada de los montos de detalle de una compra.
 * Si se envían $id_mobiliario_excluir y $id_compra_excluir, excluye esa línea (útil para UPDATE).
 */
function obtenerAcumuladoDetalles($id_compra_mobiliario, $id_mobiliario_excluir = null) {
    $conn = conectar();
    if ($id_mobiliario_excluir === null) {
        $sql = "SELECT COALESCE(SUM(monto_total_de_mobiliario), 0) AS total
                FROM detalle_compra_mobiliario
                WHERE id_compra_mobiliario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_compra_mobiliario);
    } else {
        $sql = "SELECT COALESCE(SUM(monto_total_de_mobiliario), 0) AS total
                FROM detalle_compra_mobiliario
                WHERE id_compra_mobiliario = ? AND id_mobiliario <> ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id_compra_mobiliario, $id_mobiliario_excluir);
    }
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    desconectar($conn);

    return floatval($res['total'] ?? 0);
}

/**
 * Obtiene el total de la línea original (para UPDATE).
 */
function obtenerMontoLinea($id_compra_mobiliario, $id_mobiliario) {
    $conn = conectar();
    $sql = "SELECT cantidad_de_compra, costo_unitario, monto_total_de_mobiliario
            FROM detalle_compra_mobiliario
            WHERE id_compra_mobiliario = ? AND id_mobiliario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_compra_mobiliario, $id_mobiliario);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    desconectar($conn);
    return $res ?: null;
}

/* ===========================
   CONTROLADOR CRUD DETALLE
   =========================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operacion = $_POST['operacion'] ?? '';
    switch ($operacion) {
        case 'crear_detalle':
            crearDetalleCompra();
            break;
        case 'actualizar_detalle':
            actualizarDetalleCompra();
            break;
        case 'eliminar_detalle':
            eliminarDetalleCompra();
            break;
    }
}

function crearDetalleCompra() {
    global $conn;
    $conn = conectar();

    $errores = validarDatosDetalleCompra($_POST);

    $id_compra_mobiliario = intval($_POST['id_compra_mobiliario'] ?? 0);
    $id_mobiliario        = intval($_POST['id_mobiliario'] ?? 0);
    $cantidad             = intval($_POST['cantidad_de_compra'] ?? 0);
    $costo_unitario       = round(floatval($_POST['costo_unitario'] ?? 0), 2);
    $monto_linea          = round($cantidad * $costo_unitario, 2);

    // Ver existencias de compra y mobiliario
    $compra = verificarCompra($id_compra_mobiliario);
    if (!$compra) $errores[] = "La compra seleccionada no existe";

    if (!verificarMobiliario($id_mobiliario)) {
        $errores[] = "El mobiliario seleccionado no existe";
    }

    // Rechazar duplicado (misma compra y mismo mobiliario)
    if (verificarDetalleExistente($id_compra_mobiliario, $id_mobiliario)) {
        $errores[] = "Ya existe un detalle para esta compra y mobiliario";
    }

    // Reglas contables (solo si existe la compra)
    if ($compra) {
        $monto_total_compra = round(floatval($compra['monto_total_compra_q']), 2);

        // 1) Costo unitario no puede exceder total de la compra
        if ($costo_unitario > $monto_total_compra) {
            $errores[] = "El costo unitario (Q " . number_format($costo_unitario,2) . ") supera el total de la compra (Q " . number_format($monto_total_compra,2) . ").";
        }

        // 2) Total de la línea no puede exceder total de la compra
        if ($monto_linea > $monto_total_compra) {
            $errores[] = "El total de la línea (Q " . number_format($monto_linea,2) . ") excede el total de la compra (Q " . number_format($monto_total_compra,2) . ").";
        }

        // 3) Suma acumulada de líneas + esta línea no puede exceder total compra
        $acumulado = obtenerAcumuladoDetalles($id_compra_mobiliario, null);
        if (($acumulado + $monto_linea) > $monto_total_compra + 0.0001) {
            $errores[] = "El acumulado de detalles (Q " . number_format($acumulado,2) . ") + esta línea (Q " . number_format($monto_linea,2) . ") excede el total de la compra (Q " . number_format($monto_total_compra,2) . ").";
        }
    }

    if (!empty($errores)) {
        $_SESSION['mensaje'] = "Errores de validación:<br>" . implode("<br>", $errores);
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: detalle_compras_mobiliario.php');
        exit();
    }

    $sql = "INSERT INTO detalle_compra_mobiliario
            (id_compra_mobiliario, id_mobiliario, cantidad_de_compra, costo_unitario, monto_total_de_mobiliario)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiidd", $id_compra_mobiliario, $id_mobiliario, $cantidad, $costo_unitario, $monto_linea);

    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Detalle de compra registrado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al registrar detalle de compra: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }

    $stmt->close();
    desconectar($conn);
    header('Location: detalle_compras_mobiliario.php');
    exit();
}

function actualizarDetalleCompra() {
    global $conn;
    $conn = conectar();

    // IDs originales (clave compuesta actual)
    $id_compra_orig = intval($_POST['id_compra_mobiliario_original'] ?? 0);
    $id_mob_orig    = intval($_POST['id_mobiliario_original'] ?? 0);

    if ($id_compra_orig <= 0 || $id_mob_orig <= 0) {
        $_SESSION['mensaje'] = "IDs de detalle de compra inválidos";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: detalle_compras_mobiliario.php');
        exit();
    }

    $errores = validarDatosDetalleCompra($_POST);

    $id_compra_new = intval($_POST['id_compra_mobiliario'] ?? 0);
    $id_mob_new    = intval($_POST['id_mobiliario'] ?? 0);
    $cantidad      = intval($_POST['cantidad_de_compra'] ?? 0);
    $costo_unit    = round(floatval($_POST['costo_unitario'] ?? 0), 2);
    $monto_linea   = round($cantidad * $costo_unit, 2);

    // Validar compra destino y mobiliario destino
    $compra = verificarCompra($id_compra_new);
    if (!$compra) $errores[] = "La compra seleccionada no existe";
    if (!verificarMobiliario($id_mob_new)) {
        $errores[] = "El mobiliario seleccionado no existe";
    }

    // Si cambian las llaves, validar que no dupliquen otro registro
    $cambianLlaves = ($id_compra_new !== $id_compra_orig) || ($id_mob_new !== $id_mob_orig);
    if ($cambianLlaves && verificarDetalleExistente($id_compra_new, $id_mob_new)) {
        $errores[] = "Ya existe un detalle para la compra y mobiliario seleccionados";
    }

    // Reglas contables
    if ($compra) {
        $monto_total_compra = round(floatval($compra['monto_total_compra_q']), 2);

        // 1) Costo unitario vs total compra
        if ($costo_unit > $monto_total_compra) {
            $errores[] = "El costo unitario (Q " . number_format($costo_unit,2) . ") supera el total de la compra (Q " . number_format($monto_total_compra,2) . ").";
        }

        // 2) Total línea vs total compra
        if ($monto_linea > $monto_total_compra) {
            $errores[] = "El total de la línea (Q " . number_format($monto_linea,2) . ") excede el total de la compra (Q " . number_format($monto_total_compra,2) . ").";
        }

        /**
         * 3) Acumulado:
         * - Si actualizamos dentro de la MISMA compra y MISMO mobiliario:
         *   acumulado_sin_esta = SUM(todas las líneas de la compra) - monto_linea_original
         * - Si cambian llaves o cambian de compra:
         *   acumulado_sin_esta = SUM(todas las líneas de la compra destino)  (no restamos original porque ya no está en esa compra)
         */
        if ($id_compra_new === $id_compra_orig && $id_mob_new === $id_mob_orig) {
            // Misma línea, restamos su valor anterior del acumulado
            $linea_original = obtenerMontoLinea($id_compra_orig, $id_mob_orig);
            $monto_original = round(floatval($linea_original['monto_total_de_mobiliario'] ?? 0), 2);
            $acumulado_total = obtenerAcumuladoDetalles($id_compra_new, null);
            $acumulado_sin_esta = round($acumulado_total - $monto_original, 2);
        } else {
            // Cambió la llave o la compra; el acumulado a validar es el de la compra destino sin excluir nada
            $acumulado_sin_esta = obtenerAcumuladoDetalles($id_compra_new, null);
        }

        if (($acumulado_sin_esta + $monto_linea) > $monto_total_compra + 0.0001) {
            $_SESSION['debug'] = [
                'acumulado_sin_esta' => $acumulado_sin_esta,
                'monto_linea' => $monto_linea,
                'monto_total_compra' => $monto_total_compra,
            ];
            $errores[] = "El acumulado de detalles (Q " . number_format($acumulado_sin_esta,2) . ") + esta línea (Q " . number_format($monto_linea,2) . ") excede el total de la compra (Q " . number_format($monto_total_compra,2) . ").";
        }
    }

    if (!empty($errores)) {
        $_SESSION['mensaje'] = "Errores de validación:<br>" . implode("<br>", $errores);
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: detalle_compras_mobiliario.php');
        exit();
    }

    // UPDATE clave compuesta
    $sql = "UPDATE detalle_compra_mobiliario
            SET id_compra_mobiliario = ?, id_mobiliario = ?, cantidad_de_compra = ?, costo_unitario = ?, monto_total_de_mobiliario = ?
            WHERE id_compra_mobiliario = ? AND id_mobiliario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiddii",
        $id_compra_new, $id_mob_new, $cantidad, $costo_unit, $monto_linea,
        $id_compra_orig, $id_mob_orig
    );

    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Detalle de compra actualizado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar detalle de compra: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }

    $stmt->close();
    desconectar($conn);
    header('Location: detalle_compras_mobiliario.php');
    exit();
}

function eliminarDetalleCompra() {
    global $conn;
    $conn = conectar();

    $id_compra_mobiliario = $_POST['id_compra_mobiliario'] ?? '';
    $id_mobiliario        = $_POST['id_mobiliario'] ?? '';

    if (empty($id_compra_mobiliario) || !is_numeric($id_compra_mobiliario) || $id_compra_mobiliario <= 0 ||
        empty($id_mobiliario) || !is_numeric($id_mobiliario) || $id_mobiliario <= 0) {
        $_SESSION['mensaje'] = "Error: No se proporcionaron IDs válidos para eliminar el detalle.";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: detalle_compras_mobiliario.php');
        exit();
    }

    $id_compra_mobiliario = intval($id_compra_mobiliario);
    $id_mobiliario        = intval($id_mobiliario);

    try {
        $check = $conn->prepare("SELECT 1 FROM detalle_compra_mobiliario WHERE id_compra_mobiliario = ? AND id_mobiliario = ?");
        if (!$check) throw new Exception("Error al preparar la consulta: " . $conn->error);
        $check->bind_param("ii", $id_compra_mobiliario, $id_mobiliario);
        if (!$check->execute()) throw new Exception("Error al ejecutar la consulta: " . $check->error);
        $res = $check->get_result();
        if ($res->num_rows === 0) {
            $_SESSION['mensaje'] = "Error: El detalle de compra que intenta eliminar no existe en el sistema.";
            $_SESSION['tipo_mensaje'] = "error";
            $check->close();
            desconectar($conn);
            header('Location: detalle_compras_mobiliario.php');
            exit();
        }
        $check->close();

        $sql = "DELETE FROM detalle_compra_mobiliario WHERE id_compra_mobiliario = ? AND id_mobiliario = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Error al preparar la consulta de eliminación: " . $conn->error);

        $stmt->bind_param("ii", $id_compra_mobiliario, $id_mobiliario);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['mensaje'] = "Detalle de compra eliminado exitosamente";
                $_SESSION['tipo_mensaje'] = "success";
            } else {
                $_SESSION['mensaje'] = "No se pudo eliminar el detalle de compra. Es posible que ya haya sido eliminado o no exista.";
                $_SESSION['tipo_mensaje'] = "error";
            }
        } else {
            $error = $stmt->error;
            if (strpos($error, 'foreign key constraint') !== false) {
                $_SESSION['mensaje'] = "No se puede eliminar el detalle de compra porque está siendo utilizado en otros registros del sistema.";
                $_SESSION['tipo_mensaje'] = "error";
            } else {
                $_SESSION['mensaje'] = "Error al eliminar detalle de compra: " . $error;
                $_SESSION['tipo_mensaje'] = "error";
            }
        }
        $stmt->close();

    } catch (mysqli_sql_exception $e) {
        $msg = $e->getMessage();
        if (strpos($msg, 'foreign key constraint fails') !== false) {
            $_SESSION['mensaje'] = "No se puede eliminar el detalle de compra porque está siendo utilizado en otros registros del sistema.";
        } else if (strpos($msg, 'Unknown column') !== false) {
            $_SESSION['mensaje'] = "Error en la consulta a la base de datos. Por favor, contacte al administrador del sistema.";
        } else {
            $_SESSION['mensaje'] = "Error de base de datos: " . $msg;
        }
        $_SESSION['tipo_mensaje'] = "error";
    } catch (Exception $e) {
        $_SESSION['mensaje'] = "Error inesperado: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "error";
    }

    desconectar($conn);
    header('Location: detalle_compras_mobiliario.php');
    exit();
}

/* ===========================
   QUERIES PARA LISTADOS
   =========================== */

function obtenerDetallesCompra() {
    $conn = conectar();
    $sql = "SELECT dcm.*,
                   cm.fecha_de_compra,
                   p.nombre_proveedor,
                   im.nombre_mobiliario,
                   tm.descripcion AS tipo_mobiliario
            FROM detalle_compra_mobiliario dcm
            LEFT JOIN compras_mobiliario cm ON dcm.id_compra_mobiliario = cm.id_compra_mobiliario
            LEFT JOIN proveedores p ON cm.id_proveedor = p.id_proveedor
            LEFT JOIN inventario_mobiliario im ON dcm.id_mobiliario = im.id_mobiliario
            LEFT JOIN tipos_mobiliario tm ON im.id_tipo_mobiliario = tm.id_tipo_mobiliario
            ORDER BY cm.fecha_de_compra DESC, dcm.id_compra_mobiliario";
    $res = $conn->query($sql);
    $datos = [];
    if ($res && $res->num_rows > 0) while ($f = $res->fetch_assoc()) $datos[] = $f;
    desconectar($conn);
    return $datos;
}

function obtenerCompras() {
    $conn = conectar();
    $sql = "SELECT cm.id_compra_mobiliario, cm.fecha_de_compra, p.nombre_proveedor, cm.monto_total_compra_q
            FROM compras_mobiliario cm
            LEFT JOIN proveedores p ON cm.id_proveedor = p.id_proveedor
            ORDER BY cm.fecha_de_compra DESC";
    $res = $conn->query($sql);
    $datos = [];
    if ($res && $res->num_rows > 0) while ($f = $res->fetch_assoc()) $datos[] = $f;
    desconectar($conn);
    return $datos;
}

function obtenerMobiliario() {
    $conn = conectar();
    $sql = "SELECT im.id_mobiliario, im.nombre_mobiliario, tm.descripcion AS tipo_mobiliario
            FROM inventario_mobiliario im
            LEFT JOIN tipos_mobiliario tm ON im.id_tipo_mobiliario = tm.id_tipo_mobiliario
            ORDER BY im.nombre_mobiliario";
    $res = $conn->query($sql);
    $datos = [];
    if ($res && $res->num_rows > 0) while ($f = $res->fetch_assoc()) $datos[] = $f;
    desconectar($conn);
    return $datos;
}

$detalles     = obtenerDetallesCompra();
$compras      = obtenerCompras();
$mobiliarios  = obtenerMobiliario();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Compras de Mobiliario - Marina Roja</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
</head>
<body>
<header class="mb-4">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
        <h1 class="mb-0">DETALLE DE COMPRAS DE MOBILIARIO</h1>
        <ul class="nav nav-pills gap-2 mb-0">
            <li class="nav-item"><a href="../menu_empleados.php" class="nav-link">Regresar al Menú</a></li>
        </ul>
    </div>
</header>

<main class="container my-4">
    <!-- Mensajes (SweetAlert2) -->
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
        <h2 class="card__title text-primary mb-4">FORMULARIO - DETALLE DE COMPRAS DE MOBILIARIO</h2>

        <form id="form-detalle" method="post" class="row g-3" novalidate>
            <input type="hidden" id="operacion" name="operacion" value="crear_detalle">
            <input type="hidden" id="id_compra_mobiliario_original" name="id_compra_mobiliario_original" value="">
            <input type="hidden" id="id_mobiliario_original" name="id_mobiliario_original" value="">

            <div class="col-md-6">
                <label class="form-label" for="id_compra_mobiliario">Compra:</label>
                <select class="form-control" id="id_compra_mobiliario" name="id_compra_mobiliario" required>
                    <option value="" data-total="0">Seleccione una compra</option>
                    <?php foreach($compras as $compra): ?>
                        <option value="<?php echo $compra['id_compra_mobiliario']; ?>"
                                data-total="<?php echo number_format($compra['monto_total_compra_q'], 2, '.', ''); ?>">
                            Compra #<?php echo $compra['id_compra_mobiliario']; ?> —
                            <?php echo htmlspecialchars($compra['nombre_proveedor']); ?> —
                            <?php echo htmlspecialchars($compra['fecha_de_compra']); ?> —
                            Total Q <?php echo number_format($compra['monto_total_compra_q'], 2); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="id_mobiliario">Mobiliario:</label>
                <select class="form-control" id="id_mobiliario" name="id_mobiliario" required>
                    <option value="">Seleccione mobiliario</option>
                    <?php foreach($mobiliarios as $mob): ?>
                        <option value="<?php echo $mob['id_mobiliario']; ?>">
                            <?php echo htmlspecialchars($mob['nombre_mobiliario']); ?> —
                            <?php echo htmlspecialchars($mob['tipo_mobiliario']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label" for="cantidad_de_compra">Cantidad:</label>
                <input type="number" class="form-control" id="cantidad_de_compra" name="cantidad_de_compra"
                       min="1" max="10000" required value="1">
            </div>

            <div class="col-md-3">
                <label class="form-label" for="costo_unitario">Costo Unitario (Q):</label>
                <input type="number" step="0.01" class="form-control" id="costo_unitario" name="costo_unitario"
                       min="0.01" max="100000" required placeholder="0.00">
            </div>

            <div class="col-md-6">
                <label class="form-label">Monto Total (Q):</label>
                <input type="text" class="form-control" id="monto_total_display" readonly
                       style="background-color:#e9ecef;font-weight:bold;" value="Q 0.00">
                <small id="ayuda-total" class="text-muted d-block mt-1">
                    El costo unitario y el total de línea no pueden exceder el total de la compra. El acumulado de detalles tampoco puede superar ese total.
                </small>
            </div>
        </form>

        <div class="d-flex gap-2 mt-4">
            <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
            <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
            <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
            <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
        </div>

        <h2 class="card__title mb-3 mt-5">DETALLES DE COMPRAS REGISTRADAS</h2>
        <div class="table-responsive mt-3">
            <table class="table table-striped table-bordered" id="tabla-detalles">
                <thead class="table-dark">
                <tr>
                    <th>Compra ID</th>
                    <th>Proveedor</th>
                    <th>Fecha</th>
                    <th>Mobiliario</th>
                    <th>Tipo</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-end">Costo Unitario</th>
                    <th class="text-end">Monto Total</th>
                    <th>Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($detalles as $detalle): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($detalle['id_compra_mobiliario']); ?></td>
                        <td><?php echo htmlspecialchars($detalle['nombre_proveedor'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($detalle['fecha_de_compra']); ?></td>
                        <td><?php echo htmlspecialchars($detalle['nombre_mobiliario'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($detalle['tipo_mobiliario'] ?? 'N/A'); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($detalle['cantidad_de_compra']); ?></td>
                        <td class="text-end fw-bold">Q <?php echo number_format($detalle['costo_unitario'], 2); ?></td>
                        <td class="text-end fw-bold">Q <?php echo number_format($detalle['monto_total_de_mobiliario'], 2); ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary btn-action editar-btn"
                                    data-compra="<?php echo $detalle['id_compra_mobiliario']; ?>"
                                    data-mobiliario="<?php echo $detalle['id_mobiliario']; ?>"
                                    data-cantidad="<?php echo $detalle['cantidad_de_compra']; ?>"
                                    data-costo="<?php echo $detalle['costo_unitario']; ?>">
                                Editar
                            </button>
                            <form method="post" style="display:inline;" data-eliminar="true">
                                <input type="hidden" name="operacion" value="eliminar_detalle">
                                <input type="hidden" name="id_compra_mobiliario" value="<?php echo $detalle['id_compra_mobiliario']; ?>">
                                <input type="hidden" name="id_mobiliario" value="<?php echo $detalle['id_mobiliario']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger btn-action">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($detalles)): ?>
                    <tr>
                        <td colspan="9" class="text-center">No hay detalles de compra registrados</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/SistemaWebRestaurante/javascript/detalle_compras_mobiliario.js"></script>

<script>
// Mostrar SweetAlert si hay mensaje de sesión
if (window.__mensaje) {
    Swal.fire({
        icon: (window.__mensaje.tipo === 'success') ? 'success' : 'error',
        title: (window.__mensaje.tipo === 'success') ? 'Éxito' : 'Aviso',
        html: window.__mensaje.text
    });
}
</script>
</body>
</html>
