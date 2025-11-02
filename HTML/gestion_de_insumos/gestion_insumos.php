<?php
session_start();
require_once '../conexion.php';
require_once __DIR__ . '/../funciones_globales.php';
// --- Verificar sesión ---
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// --- Procesar operaciones CRUD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operacion = $_POST['operacion'] ?? '';

    match ($operacion) {
        'crear' => crearInsumo(),
        'actualizar' => actualizarInsumo(),
        'eliminar' => eliminarInsumo(),
        default => null
    };
}

// --- Funciones CRUD ---
function crearInsumo(): void {
    $conn = conectar();

    $insumo = trim($_POST['insumo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $stock = (int)($_POST['stock'] ?? 0);

    $sql = "INSERT INTO inventario_insumos (insumo, descripcion, stock) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $insumo, $descripcion, $stock);

    // callback para bitácora
    $onSuccess = function(mysqli $cx) use ($insumo, $descripcion, $stock) {
        $idNew = $cx->insert_id;
        registrarBitacora($cx, "inventario_insumos", "insertar", "ID $idNew - '$insumo' creado (Stock $stock)");
    };

    ejecutarConsulta($conn, $stmt, "Insumo creado exitosamente", "Error al crear insumo", $onSuccess);
}

function actualizarInsumo(): void {
    $conn = conectar();
    $id = (int)($_POST['id_insumo'] ?? 0);
    $insumo = trim($_POST['insumo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $stock = (int)($_POST['stock'] ?? 0);

    $sql = "UPDATE inventario_insumos SET insumo = ?, descripcion = ?, stock = ? WHERE id_insumo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $insumo, $descripcion, $stock, $id);

    // callback para bitácora
    $onSuccess = function(mysqli $cx) use ($id, $insumo, $stock) {
        registrarBitacora($cx, "inventario_insumos", "actualizar", "ID $id - '$insumo' (Stock $stock)");
    };

    ejecutarConsulta($conn, $stmt, "Insumo actualizado exitosamente", "Error al actualizar insumo", $onSuccess);
}

function eliminarInsumo(): void {
    $conn = conectar();
    $id = (int)($_POST['id_insumo'] ?? 0);

    // leer datos antes de eliminar (para registrar detalle)
    $nombrePrev = '';
    $stockPrev = 0;
    if ($id > 0) {
        if ($sel = $conn->prepare("SELECT insumo, stock FROM inventario_insumos WHERE id_insumo = ?")) {
            $sel->bind_param("i", $id);
            $sel->execute();
            $res = $sel->get_result();
            if ($res && $row = $res->fetch_assoc()) {
                $nombrePrev = (string)$row['insumo'];
                $stockPrev = (int)$row['stock'];
            }
            $sel->close();
        }
    }

    $sql = "DELETE FROM inventario_insumos WHERE id_insumo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    // callback para bitácora
    $onSuccess = function(mysqli $cx) use ($id, $nombrePrev, $stockPrev) {
        $detalle = $nombrePrev !== '' ? "ID $id - '$nombrePrev' (Stock $stockPrev)" : "ID $id";
        registrarBitacora($cx, "inventario_insumos", "eliminar", $detalle);
    };

    ejecutarConsulta($conn, $stmt, "Insumo eliminado exitosamente", "Error al eliminar insumo", $onSuccess);
}

// ejecutarConsulta ahora recibe un callback opcional $onSuccess
function ejecutarConsulta(mysqli $conn, mysqli_stmt $stmt, string $msgExito, string $msgError, ?callable $onSuccess = null): void {
    try {
        if ($stmt->execute()) {
            // ejecutar callback de éxito antes de cerrar/redirigir
            if (is_callable($onSuccess)) {
                try { $onSuccess($conn); } catch (\Throwable $t) { /* no detener flujo */ }
            }
            $_SESSION['mensaje'] = $msgExito;
            $_SESSION['tipo_mensaje'] = "success";
        } else {
            $_SESSION['mensaje'] = "$msgError: " . $stmt->error;
            $_SESSION['tipo_mensaje'] = "error";
        }
    } catch (mysqli_sql_exception $e) {
        if ((int)$e->getCode() === 1451) {
            $_SESSION['mensaje'] = "No se puede eliminar: el registro está vinculado en detalle_compra_insumo.";
            $_SESSION['tipo_mensaje'] = "error";
        } else {
            $_SESSION['mensaje'] = "$msgError: " . $e->getMessage();
            $_SESSION['tipo_mensaje'] = "error";
        }
    } finally {
        try { if ($stmt) $stmt->close(); } catch (\Throwable $ignore) {}
        try { desconectar($conn); } catch (\Throwable $ignore) {}
        header('Location: gestion_insumos.php');
        exit();
    }
}

function obtenerInsumos(): array {
    $conn = conectar();
    $sql = "SELECT id_insumo, insumo, descripcion, stock FROM inventario_insumos ORDER BY insumo ASC";
    $res = $conn->query($sql);
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    desconectar($conn);
    return $rows;
}

$insumos = obtenerInsumos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Gestión de Insumos - Marea Roja</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
    <style>
        body, input, button, table { font-family: 'Poppins', Arial, sans-serif; }
        .descripcion-cell { max-width: 240px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .mensaje { padding:10px; margin:10px 0; border-radius:5px; }
        .mensaje.success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
        .mensaje.error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
        .table-responsive { max-height:420px; overflow-y:auto; }
    </style>
</head>
<body>
<header class="mb-4">
    <div class="container d-flex justify-content-between align-items-center py-3">
        <h1 class="mb-0">GESTIÓN DE INSUMOS</h1>
        <a href="../menu_empleados.php" class="btn-back"><span class="arrow">←</span> Regresar</a>
    </div>
</header>

<main class="container my-4">
    <?php if (isset($_SESSION['mensaje'])): ?>
        <script>
            // Pasar mensaje de PHP a JS para que SweetAlert lo muestre
            window.__mensaje = {
                tipo: <?= json_encode($_SESSION['tipo_mensaje'] ?? 'info'); ?>,
                text: <?= json_encode($_SESSION['mensaje'] ?? ''); ?>
            };
        </script>
        <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
    <?php endif; ?>

    <section class="card shadow p-4">
        <h2 class="card__title mb-3">Formulario de Insumos</h2>

        <form id="form-insumos" method="post" class="row g-3">
            <input type="hidden" name="operacion" id="operacion" value="crear">
            <input type="hidden" name="id_insumo" id="id_insumo" value="">

            <div class="col-md-4">
                <label for="insumo" class="form-label required">Insumo</label>
                <input type="text" id="insumo" name="insumo" class="form-control" placeholder="Ej. Foco 9W" required>
            </div>

            <div class="col-md-4">
                <label for="descripcion" class="form-label">Descripción</label>
                <input type="text" id="descripcion" name="descripcion" class="form-control" placeholder="Descripción corta">
            </div>

            <div class="col-md-4">
                <label for="stock" class="form-label required">Stock</label>
                <input type="number" id="stock" name="stock" class="form-control" value="0" min="0" required>
            </div>
        </form>

        <div class="d-flex gap-2 mt-4">
            <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
            <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
            <button id="btn-actualizar" type="button" class="btn btn-warning d-none">Actualizar</button>
            <button id="btn-cancelar" type="button" class="btn btn-danger d-none">Cancelar</button>
            <div class="ms-auto text-muted align-self-center">Fila activa: <span id="fila-activa">ninguna</span></div>
        </div>

        <h2 class="card__title mb-3 mt-5">Inventario de Insumos</h2>
        <div class="table-responsive mt-3">
            <table class="table table-striped table-bordered" id="tabla-insumos">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Insumo</th>
                        <th>Descripción</th>
                        <th>Stock</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($insumos): ?>
                        <?php foreach($insumos as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['id_insumo']); ?></td>
                            <td><?= htmlspecialchars($r['insumo']); ?></td>
                            <td class="descripcion-cell" title="<?= htmlspecialchars($r['descripcion']); ?>">
                                <?= htmlspecialchars($r['descripcion'] ?: 'Sin descripción'); ?>
                            </td>
                            <td><?= (int)$r['stock']; ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary editar-btn"
                                    data-id="<?= $r['id_insumo']; ?>"
                                    data-insumo="<?= htmlspecialchars($r['insumo']); ?>"
                                    data-descripcion="<?= htmlspecialchars($r['descripcion']); ?>"
                                    data-stock="<?= (int)$r['stock']; ?>">
                                    Editar
                                </button>
                                <!-- usar data-eliminar para que el JS gestione la confirmación con SweetAlert -->
                                <form method="post" class="d-inline" data-eliminar="true">
                                    <input type="hidden" name="operacion" value="eliminar">
                                    <input type="hidden" name="id_insumo" value="<?= $r['id_insumo']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center">No hay insumos registrados</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<!-- Incluir SweetAlert2 y el JS de gestión -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../javascript/gestion_insumos.js"></script>
</body>
</html>
