<?php
session_start();
require_once '../conexion.php';
require_once '../funciones_globales.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operacion = $_POST['operacion'] ?? '';
    switch($operacion) {
        case 'crear':
            crearDetalleCompra();
            break;
        case 'actualizar':
            actualizarDetalleCompra();
            break;
        case 'eliminar':
            eliminarDetalleCompra();
            break;
    }
}

function crearDetalleCompra() {
    global $conn;
    $conn = conectar();
    
    $id_compra_ingrediente = intval($_POST['id_compra_ingrediente'] ?? '');
    $id_ingrediente = intval($_POST['id_ingrediente'] ?? '');
    $id_unidad = intval($_POST['id_unidad'] ?? '');
    $cantidad_compra = floatval($_POST['cantidad_compra'] ?? 0);
    $costo_unitario = floatval($_POST['costo_unitario'] ?? 0);
    $costo_total = $cantidad_compra * $costo_unitario;
    
    $sql = "INSERT INTO detalle_compra_ingrediente (id_compra_ingrediente, id_ingrediente, id_unidad, cantidad_compra, costo_unitario, costo_total) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiddd", $id_compra_ingrediente, $id_ingrediente, $id_unidad, $cantidad_compra, $costo_unitario, $costo_total);
    
    if ($stmt->execute()) {
        registrarBitacora(
            $conn,
            "Detalle Compras Inventario Ingredientes",
            "Insertar",
            "Registro #$id_compra_ingrediente insertado (Id Ingrediente: $id_ingrediente, Cantidad: $cantidad_compra, Costo Unitario: $costo_unitario, Total: $costo_total)"
        );
        $_SESSION['mensaje'] = "Detalle de compra registrado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al registrar detalle de compra: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: detalle_compras_ingredientes.php');
    exit();
}

function actualizarDetalleCompra() {
    global $conn;
    $conn = conectar();
    
    $id_compra_ingrediente = intval($_POST['id_compra_ingrediente'] ?? '');
    $id_ingrediente = intval($_POST['id_ingrediente'] ?? '');
    $id_unidad = intval($_POST['id_unidad'] ?? '');
    $cantidad_compra = floatval($_POST['cantidad_compra'] ?? 0);
    $costo_unitario = floatval($_POST['costo_unitario'] ?? 0);
    $costo_total = $cantidad_compra * $costo_unitario;
    
    $sql = "UPDATE detalle_compra_ingrediente 
            SET id_unidad = ?, cantidad_compra = ?, costo_unitario = ?, costo_total = ? 
            WHERE id_compra_ingrediente = ? AND id_ingrediente = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idddii", $id_unidad, $cantidad_compra, $costo_unitario, $costo_total, $id_compra_ingrediente, $id_ingrediente);
    
    if ($stmt->execute()) {
        registrarBitacora(
            $conn,
            "Detalle Compras Inventario Ingredientes",
            "Actualizar",
            "Registro #$id_compra_ingrediente actualizado (Id Ingrediente: $id_ingrediente, Cantidad: $cantidad_compra, Costo Unitario: $costo_unitario, Total: $costo_total)"
        );
        $_SESSION['mensaje'] = "Detalle de compra actualizado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar detalle de compra: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: detalle_compras_ingredientes.php');
    exit();
}

function eliminarDetalleCompra() {
    global $conn;
    $conn = conectar();
    
    $id_compra_ingrediente = intval($_POST['id_compra_ingrediente'] ?? '');
    $id_ingrediente = intval($_POST['id_ingrediente'] ?? '');
    
    $sql = "DELETE FROM detalle_compra_ingrediente WHERE id_compra_ingrediente = ? AND id_ingrediente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_compra_ingrediente, $id_ingrediente);
    
    if ($stmt->execute()) {
        registrarBitacora(
            $conn,
            "Detalle Compras Inventario Ingredientes",
            "Eliminar",
            "Registro #$id_compra_ingrediente eliminado (Id Ingrediente: $id_ingrediente)"
        );
        $_SESSION['mensaje'] = "Detalle de compra eliminado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al eliminar detalle de compra: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: detalle_compras_ingredientes.php');
    exit();
}

function obtenerDetallesCompra() {
    $conn = conectar();
    $sql = "SELECT dci.*, ci.fecha_de_compra, i.nombre_ingrediente, um.unidad, um.abreviatura
            FROM detalle_compra_ingrediente dci
            LEFT JOIN compras_ingrediente ci ON dci.id_compra_ingrediente = ci.id_compra_ingrediente
            LEFT JOIN ingredientes i ON dci.id_ingrediente = i.id_ingrediente
            LEFT JOIN unidades_medida um ON dci.id_unidad = um.id_unidad
            ORDER BY ci.fecha_de_compra DESC, dci.id_compra_ingrediente DESC";
    $resultado = $conn->query($sql);
    $detalles = [];
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $detalles[] = $fila;
        }
    }
    desconectar($conn);
    return $detalles;
}

function obtenerCompras() {
    $conn = conectar();
    $sql = "SELECT id_compra_ingrediente, fecha_de_compra FROM compras_ingrediente ORDER BY fecha_de_compra DESC";
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

function obtenerIngredientes() {
    $conn = conectar();
    $sql = "SELECT i.id_ingrediente, i.nombre_ingrediente, i.id_unidad, um.unidad, um.abreviatura
            FROM ingredientes i
            LEFT JOIN unidades_medida um ON i.id_unidad = um.id_unidad
            ORDER BY i.nombre_ingrediente";
    $resultado = $conn->query($sql);
    $ingredientes = [];
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $ingredientes[] = $fila;
        }
    }
    desconectar($conn);
    return $ingredientes;
}

function obtenerUnidadesMedida() {
    $conn = conectar();
    $sql = "SELECT * FROM unidades_medida ORDER BY unidad";
    $resultado = $conn->query($sql);
    $unidades = [];
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $unidades[] = $fila;
        }
    }
    desconectar($conn);
    return $unidades;
}

$detalles = obtenerDetallesCompra();
$compras = obtenerCompras();
$ingredientes = obtenerIngredientes();
$unidades = obtenerUnidadesMedida();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Compras - Marea Roja</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
    <style>
        body, h1, h2, h3, h4, h5, h6, label, input, button, table, th, td {
            font-family: 'Poppins', Arial, Helvetica, sans-serif !important;
        }
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
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
        }
        .table th {
            background-color: #1e40af;
            color: white;
            font-weight: 600;
        }
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 0.2rem rgba(59,130,246,0.25);
        }
        .monto-alto {
            color: #059669;
            font-weight: bold;
        }
        .info-badge {
            background-color: #dbeafe;
            color: #1e40af;
            font-size: 0.75rem;
            padding: 2px 6px;
            border-radius: 4px;
        }
    </style>
</head>

<body>
<header class="mb-4">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
        <h1 class="mb-0">DETALLE DE COMPRAS DE INGREDIENTES - MAREA ROJA</h1>
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
        <h2 class="card-title text-primary mb-4">DETALLE DE COMPRAS</h2>
        <form id="form-detalle-compra" method="post" class="row g-3">
            <input type="hidden" id="operacion" name="operacion" value="crear">
            <input type="hidden" id="id_compra_ingrediente_original" name="id_compra_ingrediente_original" value="">
            <input type="hidden" id="id_ingrediente_original" name="id_ingrediente_original" value="">
            <div class="col-md-3">
                <label class="form-label fw-semibold" for="id_compra_ingrediente">Compra: *</label>
                <select class="form-control" id="id_compra_ingrediente" name="id_compra_ingrediente" required>
                    <option value="">Seleccione una compra</option>
                    <?php foreach($compras as $compra): ?>
                        <option value="<?php echo $compra['id_compra_ingrediente']; ?>">
                            Compra #<?php echo $compra['id_compra_ingrediente']; ?> - <?php echo $compra['fecha_de_compra']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold" for="id_ingrediente">Ingrediente: *</label>
                <select class="form-control" id="id_ingrediente" name="id_ingrediente" required>
                    <option value="">Seleccione un ingrediente</option>
                    <?php foreach($ingredientes as $ingrediente): ?>
                        <option value="<?php echo $ingrediente['id_ingrediente']; ?>" data-unidad="<?php echo $ingrediente['id_unidad']; ?>">
                            <?php echo htmlspecialchars($ingrediente['nombre_ingrediente']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold" for="id_unidad">Unidad: *</label>
                <select class="form-control" id="id_unidad" name="id_unidad" required>
                    <option value="">Seleccione unidad</option>
                    <?php foreach($unidades as $unidad): ?>
                        <option value="<?php echo $unidad['id_unidad']; ?>">
                            <?php echo htmlspecialchars($unidad['unidad'] . ' (' . $unidad['abreviatura'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold" for="cantidad_compra">Cantidad: *</label>
                <input type="number" class="form-control" id="cantidad_compra" name="cantidad_compra" required placeholder="0.000" step="0.001" min="0">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold" for="costo_unitario">Costo Unitario (Q): *</label>
                <input type="number" class="form-control" id="costo_unitario" name="costo_unitario" required placeholder="0.00" step="0.01" min="0">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Total Calculado:</label>
                <div class="form-control bg-light" id="total-calculado">Q 0.00</div>
            </div>
        </form>

        <div class="d-flex gap-2 mt-4">
            <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
            <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
            <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
            <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
        </div>

        <h2 class="card-title mb-3 mt-5">DETALLES REGISTRADOS</h2>
        
        <div class="table-responsive mt-3">
            <table class="table table-striped table-bordered" id="tabla-detalles">
                <thead class="table-dark">
                    <tr>
                        <th>Compra</th>
                        <th>Ingrediente</th>
                        <th>Cantidad</th>
                        <th>Unidad</th>
                        <th>Costo Unitario</th>
                        <th>Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($detalles as $detalle): ?>
                    <tr>
                        <td>
                            <span class="info-badge">Compra #<?php echo $detalle['id_compra_ingrediente']; ?></span>
                            <br><small><?php echo $detalle['fecha_de_compra']; ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($detalle['nombre_ingrediente']); ?></td>
                        <td><?php echo htmlspecialchars($detalle['cantidad_compra']); ?></td>
                        <td><span class="info-badge"><?php echo $detalle['abreviatura']; ?></span></td>
                        <td>Q <?php echo number_format($detalle['costo_unitario'], 2); ?></td>
                        <td class="monto-alto">Q <?php echo number_format($detalle['costo_total'], 2); ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                    data-compra="<?php echo $detalle['id_compra_ingrediente']; ?>"
                                    data-ingrediente="<?php echo $detalle['id_ingrediente']; ?>"
                                    data-unidad="<?php echo $detalle['id_unidad']; ?>"
                                    data-cantidad="<?php echo $detalle['cantidad_compra']; ?>"
                                    data-costo="<?php echo $detalle['costo_unitario']; ?>">
                                Editar
                            </button>
                            <form method="post" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este detalle de compra?')">
                                <input type="hidden" name="operacion" value="eliminar">
                                <input type="hidden" name="id_compra_ingrediente" value="<?php echo $detalle['id_compra_ingrediente']; ?>">
                                <input type="hidden" name="id_ingrediente" value="<?php echo $detalle['id_ingrediente']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger btn-action">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($detalles)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No hay detalles de compra registrados</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('form-detalle-compra');
        const btnNuevo = document.getElementById('btn-nuevo');
        const btnGuardar = document.getElementById('btn-guardar');
        const btnActualizar = document.getElementById('btn-actualizar');
        const btnCancelar = document.getElementById('btn-cancelar');
        const operacionInput = document.getElementById('operacion');
        const idCompraOriginalInput = document.getElementById('id_compra_ingrediente_original');
        const idIngredienteOriginalInput = document.getElementById('id_ingrediente_original');
        const ingredienteSelect = document.getElementById('id_ingrediente');
        const cantidadInput = document.getElementById('cantidad_compra');
        const costoInput = document.getElementById('costo_unitario');
        const totalCalculado = document.getElementById('total-calculado');

        ingredienteSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const unidadId = selectedOption.getAttribute('data-unidad');
            if (unidadId) {
                document.getElementById('id_unidad').value = unidadId;
            }
            calcularTotal();
        });

        cantidadInput.addEventListener('input', calcularTotal);
        costoInput.addEventListener('input', calcularTotal);

        function calcularTotal() {
            const cantidad = parseFloat(cantidadInput.value) || 0;
            const costo = parseFloat(costoInput.value) || 0;
            const total = cantidad * costo;
            totalCalculado.textContent = `Q ${total.toFixed(2)}`;
        }

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
                const compra = this.getAttribute('data-compra');
                const ingrediente = this.getAttribute('data-ingrediente');
                const unidad = this.getAttribute('data-unidad');
                const cantidad = this.getAttribute('data-cantidad');
                const costo = this.getAttribute('data-costo');
                idCompraOriginalInput.value = compra;
                idIngredienteOriginalInput.value = ingrediente;
                document.getElementById('id_compra_ingrediente').value = compra;
                document.getElementById('id_ingrediente').value = ingrediente;
                document.getElementById('id_unidad').value = unidad;
                document.getElementById('cantidad_compra').value = cantidad;
                document.getElementById('costo_unitario').value = costo;
                calcularTotal();
                mostrarBotonesActualizar();
            });
        });

        function limpiarFormulario() {
            form.reset();
            idCompraOriginalInput.value = '';
            idIngredienteOriginalInput.value = '';
            operacionInput.value = 'crear';
            totalCalculado.textContent = 'Q 0.00';
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
            const compra = document.getElementById('id_compra_ingrediente').value;
            const ingrediente = document.getElementById('id_ingrediente').value;
            const unidad = document.getElementById('id_unidad').value;
            const cantidad = document.getElementById('cantidad_compra').value;
            const costo = document.getElementById('costo_unitario').value;
            if (!compra) {
                alert('La compra es requerida');
                return false;
            }
            if (!ingrediente) {
                alert('El ingrediente es requerido');
                return false;
            }
            if (!unidad) {
                alert('La unidad es requerida');
                return false;
            }
            if (!cantidad || cantidad <= 0) {
                alert('La cantidad debe ser un número positivo');
                return false;
            }
            if (!costo || costo <= 0) {
                alert('El costo unitario debe ser un número positivo');
                return false;
            }
            return true;
        }
    });
</script>
</body>
</html>
