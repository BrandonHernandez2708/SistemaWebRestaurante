<?php
session_start();

// ✅ Ruta robusta a conexion.php (sube 1 nivel desde /HTML/gestion_de_insumos → /HTML)
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../funciones_globales.php';

// --- Verificar sesión ---
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

/** Cargar proveedores e insumos para selects */
function obtenerProveedores(): array {
    $conn = conectar();
    $sql = "SELECT id_proveedor, nombre_proveedor AS nombre 
            FROM proveedores 
            ORDER BY nombre_proveedor";
    $res = $conn->query($sql);
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    desconectar($conn);
    return $rows;
}

function obtenerInsumos(): array {
    $conn = conectar();
    $sql = "SELECT id_insumo, insumo, descripcion, stock FROM inventario_insumos ORDER BY insumo";
    $res = $conn->query($sql);
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    desconectar($conn);
    return $rows;
}

// --- Procesar POST: crear compra con detalles ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'guardar_compra') {
    // Habilitar excepciones en mysqli
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = conectar();
    try {
        $id_proveedor = (int)($_POST['id_proveedor'] ?? 0);
        $fecha_compra = $_POST['fecha_compra'] ?? date('Y-m-d');
        $insumos      = $_POST['insumo_id'] ?? [];
        $cantidades   = $_POST['cantidad'] ?? [];
        $precios      = $_POST['precio_unitario'] ?? [];

        // Validaciones básicas
        if ($id_proveedor <= 0) {
            throw new Exception('Proveedor inválido.');
        }
        if (!is_array($insumos) || count($insumos) === 0) {
            throw new Exception('Agrega al menos un insumo a la compra.');
        }

        // Calcular total y normalizar detalles
        $monto_total = 0.0;
        $detalles = [];
        $n = count($insumos);
        for ($i = 0; $i < $n; $i++) {
            $id_ins = (int)($insumos[$i] ?? 0);
            // cantidad solo enteros
            $cantRaw = $cantidades[$i] ?? 0;
            $cant    = (int)preg_replace('/[^\d\-]/', '', (string)$cantRaw);
            $precio = (float)str_replace(',', '.', $precios[$i] ?? 0);

            if ($id_ins <= 0 || $cant <= 0 || $precio < 0) {
                throw new Exception('Detalle inválido en la línea ' . ($i + 1));
            }
            $sub = round($cant * $precio, 2);
            $monto_total += $sub;

            $detalles[] = [
                'id_insumo'       => $id_ins,
                'cantidad'        => $cant, // entero
                'precio_unitario' => $precio,
                'subtotal'        => $sub,
            ];
        }
        $monto_total = round($monto_total, 2);

        // Transacción
        $conn->begin_transaction();

        // Insertar compra
        $stmt = $conn->prepare("INSERT INTO compras_insumos (id_proveedor, fecha_compra, monto_total) VALUES (?, ?, ?)");
        // tipos: i (int), s (string), d (double)
        $stmt->bind_param("isd", $id_proveedor, $fecha_compra, $monto_total);
        $stmt->execute();
        $id_compra = $conn->insert_id;
        // Registrar en bitácora tras éxito del execute
        registrarBitacora($conn, "compras_insumos", "insertar", "Compra de insumos (ID: $id_compra) creada");

        $stmt->close();

        // Insertar detalles (un solo prepare, sin recrearlo en el loop)
        $stmtDet = $conn->prepare("
            INSERT INTO detalle_compra_insumo
                (id_compra_insumo, id_insumo, cantidad_compra, costo_unitario, costo_total)
            VALUES (?, ?, ?, ?, ?)
        ");
        // tipos: i (id_compra) , i (id_insumo), i (cantidad entera), d (precio), d (subtotal)
        foreach ($detalles as $d) {
            $stmtDet->bind_param(
                "iiidd",
                $id_compra,
                $d['id_insumo'],
                $d['cantidad'],
                $d['precio_unitario'],
                $d['subtotal']
            );
            $stmtDet->execute();
        }
        $stmtDet->close();

        // Actualizar stock
        $stmtUpd = $conn->prepare("UPDATE inventario_insumos SET stock = stock + ? WHERE id_insumo = ?");
        foreach ($detalles as $d) {
            // cantidad entera suma a stock
            $stmtUpd->bind_param("ii", $d['cantidad'], $d['id_insumo']);
            $stmtUpd->execute();
        }
        $stmtUpd->close();

        $conn->commit();

        $_SESSION['mensaje'] = "Compra registrada correctamente (ID: {$id_compra}).";
        $_SESSION['tipo_mensaje'] = "success";
    } catch (mysqli_sql_exception $me) {
        try { $conn->rollback(); } catch (\Throwable $ignore) {}
        // 1451 = restricción FK en DELETE/UPDATE, 1452 = FK en INSERT
        $code = (int)$me->getCode();
        if ($code === 1451 || $code === 1452) {
            $_SESSION['mensaje'] = "No se puede completar la operación por restricciones de integridad referencial (FK).";
        } else {
            $_SESSION['mensaje'] = "Error en la operación: " . $me->getMessage();
        }
        $_SESSION['tipo_mensaje'] = "error";
    } catch (Exception $e) {
        try { $conn->rollback(); } catch (\Throwable $ignore) {}
        $_SESSION['mensaje'] = $e->getMessage();
        $_SESSION['tipo_mensaje'] = "error";
    } finally {
        desconectar($conn);
        header('Location: compra_insumos.php');
        exit();
    }
}

$proveedores = obtenerProveedores();
$insumos = obtenerInsumos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Compra de Insumos - Marea Roja</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
    <style>
        body { font-family: 'Poppins', Arial, sans-serif; }
        .table-fixed { table-layout: fixed; }
        .w-150 { width: 150px; }
        .w-100 { width: 100px; }
        .required::after { content:" *"; color:#dc3545; }
    </style>
</head>
<body>
<header class="mb-4">
    <div class="container d-flex justify-content-between align-items-center py-3">
        <h1 class="mb-0">COMPRA - INSUMOS</h1>
        <a href="../menu_empleados.php" class="btn-back"><span class="arrow">←</span> Regresar</a>
    </div>
</header>

<main class="container my-4">
    <?php if (isset($_SESSION['mensaje'])): ?>
        <script>
        window.__mensaje = {
            tipo: <?= json_encode($_SESSION['tipo_mensaje'] ?? 'info'); ?>,
            text: <?= json_encode($_SESSION['mensaje'] ?? ''); ?>
        };
        </script>
        <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
    <?php endif; ?>

    <section class="card shadow p-4">
        <h2 class="card__title mb-3">Registrar Compra de Insumos</h2>

        <form id="form-compra" method="post" action="compra_insumos.php">
            <input type="hidden" name="accion" value="guardar_compra">
           
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="id_proveedor" class="form-label required">Proveedor</label>
                    <select id="id_proveedor" name="id_proveedor" class="form-select" required>
                        <option value="">-- Seleccione proveedor --</option>
                        <?php foreach($proveedores as $p): ?>
                            <option value="<?= (int)$p['id_proveedor']; ?>">
                                <?= htmlspecialchars($p['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="fecha_compra" class="form-label required">Fecha</label>
                    <input type="date" id="fecha_compra" name="fecha_compra" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                </div>
            </div>

            <hr class="my-3">

            <h5>Líneas de Compra</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-fixed" id="tabla-lineas">
                    <thead class="table-dark">
                        <tr>
                            <th class="w-150">Insumo</th>
                            <th class="w-100">Cantidad</th>
                            <th class="w-150">Precio Unitario</th>
                            <th class="w-150">Subtotal</th>
                            <th class="w-100">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- filas agregadas por JS -->
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total</strong></td>
                            <td><strong id="total">0.00</strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-3">
                <button id="btn-agregar-linea" type="button" class="btn btn-sm btn-secondary">Agregar línea</button>
                <button id="btn-guardar-compra" type="submit" class="btn btn-success">Guardar Compra</button>
            </div>
        </form>
    </section>
</main>

<!-- SweetAlert2 y JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
window.__DATA_COMPRA = {
    insumos: <?= json_encode($insumos, JSON_UNESCAPED_UNICODE); ?>
};
</script>
<script src="../../javascript/compra_insumos.js"></script>

<script>
// Mostrar mensajes (si los hay) con SweetAlert
if (window.__mensaje && window.__mensaje.text) {
    const tipo = window.__mensaje.tipo || 'info';
    Swal.fire({
        icon: (tipo === 'success' ? 'success' : (tipo === 'error' ? 'error' : 'info')),
        title: tipo === 'success' ? 'Éxito' : (tipo === 'error' ? 'Error' : 'Aviso'),
        text: window.__mensaje.text
    });
}
</script>
</body>
</html>
