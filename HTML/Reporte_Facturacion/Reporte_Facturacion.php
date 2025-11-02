<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// RUTA CORREGIDA 
include('../conexion.php');

// Crear conexión usando tu función
$conexion = conectar();

// Inicializar variables de filtro
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';
$tipo_reporte = $_GET['tipo_reporte'] ?? 'facturas'; // 'facturas' o 'ordenes'

// Construir consulta con filtros
$where_conditions = [];
$params = [];
$types = '';

// Filtro por fecha desde
if (!empty($fecha_desde)) {
    if ($tipo_reporte == 'facturas') {
        $where_conditions[] = "f.fecha_emision >= ?";
    } else {
        $where_conditions[] = "o.fecha_orden >= ?";
    }
    $params[] = $fecha_desde;
    $types .= 's';
}

// Filtro por fecha hasta
if (!empty($fecha_hasta)) {
    if ($tipo_reporte == 'facturas') {
        $where_conditions[] = "f.fecha_emision <= ?";
    } else {
        $where_conditions[] = "o.fecha_orden <= ?";
    }
    $params[] = $fecha_hasta;
    $types .= 's';
}

// Consulta base para FACTURAS
if ($tipo_reporte == 'facturas') {
    $query = "
        SELECT 
            f.id_factura,
            f.codigo_serie,
            f.fecha_emision,
            f.nit_cliente,
            f.monto_total_q,
            c.nombre as nombre_cliente,
            c.apellido as apellido_cliente,
            o.id_orden,
            o.id_mesa,
            m.descripcion as descripcion_mesa,
            dc.id_tipo_cobro,
            tc.tipo_cobro,
            dc.monto_detalle_q,
            do.id_plato,
            do.id_bebida,
            do.cantidad,
            do.subtotal,
            p.nombre_plato,
            b.descripcion as nombre_bebida
        FROM facturas f
        LEFT JOIN clientes c ON f.id_cliente = c.id_cliente
        LEFT JOIN orden o ON f.id_orden = o.id_orden
        LEFT JOIN mesas m ON o.id_mesa = m.id_mesa
        LEFT JOIN detalle_cobro dc ON f.id_factura = dc.id_factura
        LEFT JOIN tipos_cobro tc ON dc.id_tipo_cobro = tc.id_tipo_cobro
        LEFT JOIN detalle_orden do ON o.id_orden = do.id_orden
        LEFT JOIN platos p ON do.id_plato = p.id_plato
        LEFT JOIN bebidas b ON do.id_bebida = b.id_bebida
    ";
} 
// Consulta base para ÓRDENES
else {
    $query = "
        SELECT 
            o.id_orden,
            o.fecha_orden,
            o.descripcion as descripcion_orden,
            o.total,
            m.id_mesa,
            m.descripcion as descripcion_mesa,
            m.capacidad_personas,
            do.id_detalle_orden,
            do.id_plato,
            do.id_bebida,
            do.cantidad,
            do.subtotal,
            p.nombre_plato,
            p.precio_unitario as precio_plato,
            b.descripcion as nombre_bebida,
            b.precio_unitario as precio_bebida,
            (SELECT COUNT(*) FROM facturas f WHERE f.id_orden = o.id_orden) as tiene_factura
        FROM orden o
        LEFT JOIN mesas m ON o.id_mesa = m.id_mesa
        LEFT JOIN detalle_orden do ON o.id_orden = do.id_orden
        LEFT JOIN platos p ON do.id_plato = p.id_plato
        LEFT JOIN bebidas b ON do.id_bebida = b.id_bebida
    ";
}

// Agregar condiciones WHERE si existen
if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

if ($tipo_reporte == 'facturas') {
    $query .= " ORDER BY f.fecha_emision DESC, f.id_factura DESC, dc.id_detalle_cobro ASC";
} else {
    $query .= " ORDER BY o.fecha_orden DESC, o.id_orden DESC, do.id_detalle_orden ASC";
}

// Preparar y ejecutar consulta
if (!empty($params)) {
    $stmt = $conexion->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conexion->query($query);
}

// CONSULTAS PARA RESUMEN
if ($tipo_reporte == 'facturas') {
    $query_total = "SELECT COUNT(DISTINCT f.id_factura) as total FROM facturas f";
    $query_monto = "SELECT COALESCE(SUM(f.monto_total_q), 0) as total FROM facturas f";
} else {
    $query_total = "SELECT COUNT(DISTINCT o.id_orden) as total FROM orden o";
    $query_monto = "SELECT COALESCE(SUM(o.total), 0) as total FROM orden o";
}

// Agregar condiciones WHERE si existen para las consultas del resumen
if (!empty($where_conditions)) {
    $query_total .= " WHERE " . implode(" AND ", $where_conditions);
    $query_monto .= " WHERE " . implode(" AND ", $where_conditions);
}

// Ejecutar consultas del resumen con parámetros si existen
if (!empty($params)) {
    // Total registros
    $stmt_total = $conexion->prepare($query_total);
    $stmt_total->bind_param($types, ...$params);
    $stmt_total->execute();
    $total_result = $stmt_total->get_result();
    
    // Monto total
    $stmt_monto = $conexion->prepare($query_monto);
    $stmt_monto->bind_param($types, ...$params);
    $stmt_monto->execute();
    $monto_total_result = $stmt_monto->get_result();
} else {
    $total_result = $conexion->query($query_total);
    $monto_total_result = $conexion->query($query_monto);
}

$total_registros = $total_result->fetch_assoc()['total'] ?? 0;
$monto_total = $monto_total_result->fetch_assoc()['total'] ?? 0;
$promedio = $total_registros > 0 ? $monto_total / $total_registros : 0;

// Cerrar statements si existen
if (isset($stmt_total)) $stmt_total->close();
if (isset($stmt_monto)) $stmt_monto->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de <?php echo $tipo_reporte == 'facturas' ? 'Facturas' : 'Órdenes'; ?> - Marea Roja</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, h1, h2, h3, h4, h5, h6, label, input, button, table, th, td {
            font-family: 'Poppins', Arial, Helvetica, sans-serif !important;
        }

        body {
            background-color: #f8fafc;
            color: #334155;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* BARRA SUPERIOR COMPLETA */
        .header-full {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            color: white;
            padding: 20px 0;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            width: 100%;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
        }

        .nav-link {
            background: rgba(255, 255, 255, 0.2);
            color: white !important;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            margin-bottom: 25px;
            background: white;
        }

        .card-body {
            padding: 25px;
        }

        .filtros {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .filtro-group {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: end;
        }

        .filtro-item {
            flex: 1;
            min-width: 220px;
        }

        .filtro-item label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }

        .filtro-item input, .filtro-item select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .filtro-item input:focus, .filtro-item select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        .btn-buscar {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-buscar:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .btn-limpiar {
            background: #6b7280;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-limpiar:hover {
            background: #4b5563;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            color: white;
        }

        .resumen {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .resumen-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .resumen-item {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: 10px;
            border-left: 4px solid #3b82f6;
            transition: transform 0.3s ease;
        }

        .resumen-item:hover {
            transform: translateY(-2px);
        }

        .resumen-item h3 {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .resumen-item .valor {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
        }

        .tabla-reporte {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            margin-bottom: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            color: white;
        }

        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        tbody tr {
            transition: background-color 0.2s ease;
        }

        tbody tr:hover {
            background-color: #f8fafc;
        }

        .registro-header {
            background-color: #f1f5f9 !important;
            font-weight: 700;
        }

        .registro-header td {
            border-top: 2px solid #cbd5e1;
            font-size: 14px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .no-data {
            text-align: center;
            padding: 50px;
            color: #64748b;
        }

        .no-data i {
            font-size: 64px;
            margin-bottom: 20px;
            display: block;
            color: #cbd5e1;
        }

        .no-data h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #475569;
        }

        .export-buttons {
            margin-bottom: 25px;
            text-align: right;
        }

        .btn-export {
            background: #059669;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-export:hover {
            background: #047857;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            color: white;
        }

        .monto-alto {
            color: #059669;
            font-weight: 700;
        }

        .info-adicional {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }

        .estado-facturada {
            background: #10b981;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .estado-pendiente {
            background: #f59e0b;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .header-title {
                font-size: 22px;
            }
            
            .filtro-group {
                flex-direction: column;
            }
            
            .filtro-item {
                min-width: 100%;
            }
            
            table {
                font-size: 12px;
            }
            
            th, td {
                padding: 10px 8px;
            }
            
            .resumen-grid {
                grid-template-columns: 1fr;
            }

            .export-buttons {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- BARRA SUPERIOR COMPLETA -->
    <div class="header-full">
        <div class="header-content">
            <h1 class="header-title">
                <i class="bi bi-<?php echo $tipo_reporte == 'facturas' ? 'receipt' : 'clipboard-check'; ?> me-2"></i>
                Reporte de <?php echo $tipo_reporte == 'facturas' ? 'Facturas' : 'Órdenes'; ?>
            </h1>
            <a href="../menu_empleados_vista.php" class="nav-link">
                <i class="bi bi-arrow-left me-1"></i>Regresar al Menú
            </a>
        </div>
    </div>

    <div class="container">
        <!-- Filtros -->
        <div class="card">
            <div class="card-body">
                <h2 class="card-title text-primary mb-4">
                    <i class="bi bi-funnel me-2"></i>Filtros de Búsqueda
                </h2>
                <form method="GET" action="">
                    <div class="filtro-group">
                        <div class="filtro-item">
                            <label for="tipo_reporte"><i class="bi bi-filter me-1"></i>Tipo de Reporte:</label>
                            <select id="tipo_reporte" name="tipo_reporte" onchange="this.form.submit()">
                                <option value="facturas" <?php echo $tipo_reporte == 'facturas' ? 'selected' : ''; ?>>Facturas</option>
                                <option value="ordenes" <?php echo $tipo_reporte == 'ordenes' ? 'selected' : ''; ?>>Órdenes</option>
                            </select>
                        </div>
                        <div class="filtro-item">
                            <label for="fecha_desde"><i class="bi bi-calendar-date me-1"></i>Fecha Desde:</label>
                            <input type="date" id="fecha_desde" name="fecha_desde" value="<?php echo htmlspecialchars($fecha_desde); ?>">
                        </div>
                        <div class="filtro-item">
                            <label for="fecha_hasta"><i class="bi bi-calendar-date me-1"></i>Fecha Hasta:</label>
                            <input type="date" id="fecha_hasta" name="fecha_hasta" value="<?php echo htmlspecialchars($fecha_hasta); ?>">
                        </div>
                        <div class="filtro-item">
                            <button type="submit" class="btn-buscar">
                                <i class="bi bi-search me-1"></i>Buscar
                            </button>
                            <a href="Reporte_Facturacion.php" class="btn-limpiar" style="margin-left: 10px;">
                                <i class="bi bi-arrow-clockwise me-1"></i>Limpiar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Resumen -->
        <div class="resumen">
            <h2 class="card-title text-primary mb-4">
                <i class="bi bi-graph-up me-2"></i>Resumen Estadístico
            </h2>
            <div class="resumen-grid">
                <div class="resumen-item">
                    <h3>Total <?php echo $tipo_reporte == 'facturas' ? 'Facturas' : 'Órdenes'; ?></h3>
                    <div class="valor"><?php echo number_format($total_registros); ?></div>
                </div>
                <div class="resumen-item">
                    <h3>Monto Total</h3>
                    <div class="valor">Q<?php echo number_format($monto_total, 2); ?></div>
                </div>
                <div class="resumen-item">
                    <h3>Promedio por <?php echo $tipo_reporte == 'facturas' ? 'Factura' : 'Orden'; ?></h3>
                    <div class="valor">Q<?php echo number_format($promedio, 2); ?></div>
                </div>
            </div>
        </div>

        <!-- Botones de exportación -->
        <div class="export-buttons">
            <a href="exportar_excel_<?php echo $tipo_reporte; ?>.php?<?php echo http_build_query($_GET); ?>" 
               class="btn-export">
                <i class="bi bi-file-earmark-excel"></i>Exportar Excel
            </a>
        </div>

        <!-- Tabla de reporte -->
        <div class="tabla-reporte">
            <div class="card-body">
                <h2 class="card-title text-primary mb-4">
                    <i class="bi bi-list-ul me-2"></i>Detalle de <?php echo $tipo_reporte == 'facturas' ? 'Facturas' : 'Órdenes'; ?>
                </h2>
                <?php if ($result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <?php if ($tipo_reporte == 'facturas'): ?>
                                        <th>ID Factura</th>
                                        <th>Fecha</th>
                                        <th>Cliente</th>
                                        <th>NIT</th>
                                        <th>Orden</th>
                                        <th>Mesa</th>
                                        <th class="text-right">Monto</th>
                                        <th>Método Cobro</th>
                                    <?php else: ?>
                                        <th>ID Orden</th>
                                        <th>Fecha</th>
                                        <th>Mesa</th>
                                        <th>Descripción</th>
                                        <th>Producto</th>
                                        <th class="text-right">Cantidad</th>
                                        <th class="text-right">Subtotal</th>
                                        <th>Estado</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($tipo_reporte == 'facturas') {
                                    $current_factura = null;
                                    while ($row = $result->fetch_assoc()):
                                        if ($current_factura != $row['id_factura']):
                                            $current_factura = $row['id_factura'];
                                ?>
                                <tr class="registro-header">
                                    <td><strong>#<?php echo $row['id_factura']; ?></strong></td>
                                    <td><strong><?php echo date('d/m/Y H:i', strtotime($row['fecha_emision'])); ?></strong></td>
                                    <td colspan="2">
                                        <strong><?php echo htmlspecialchars($row['nombre_cliente'] . ' ' . $row['apellido_cliente']); ?></strong>
                                        <div class="info-adicional">
                                            NIT: <?php echo htmlspecialchars($row['nit_cliente']); ?>
                                        </div>
                                    </td>
                                    <td><strong>Orden #<?php echo $row['id_orden']; ?></strong></td>
                                    <td>Mesa <?php echo $row['id_mesa']; ?></td>
                                    <td class="text-right"><strong class="monto-alto">Q<?php echo number_format($row['monto_total_q'], 2); ?></strong></td>
                                    <td></td>
                                </tr>
                                <?php
                                        endif;
                                ?>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td colspan="3">
                                        <?php if ($row['nombre_plato']): ?>
                                            <?php echo $row['cantidad'] ?>x <?php echo htmlspecialchars($row['nombre_plato']); ?>
                                        <?php elseif ($row['nombre_bebida']): ?>
                                            <?php echo $row['cantidad'] ?>x <?php echo htmlspecialchars($row['nombre_bebida']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td></td>
                                    <td class="text-right">Q<?php echo number_format($row['subtotal'], 2); ?></td>
                                    <td>
                                        <?php if ($row['tipo_cobro']): ?>
                                            <span class="estado-facturada"><?php echo htmlspecialchars($row['tipo_cobro']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                
                                <?php } else { // Reporte de órdenes ?>
                                    <?php
                                    $current_orden = null;
                                    while ($row = $result->fetch_assoc()):
                                        if ($current_orden != $row['id_orden']):
                                            $current_orden = $row['id_orden'];
                                    ?>
                                    <tr class="registro-header">
                                        <td><strong>#<?php echo $row['id_orden']; ?></strong></td>
                                        <td><strong><?php echo date('d/m/Y H:i', strtotime($row['fecha_orden'])); ?></strong></td>
                                        <td>Mesa <?php echo $row['id_mesa']; ?> - <?php echo htmlspecialchars($row['descripcion_mesa']); ?></td>
                                        <td><?php echo htmlspecialchars($row['descripcion_orden'] ?: '-'); ?></td>
                                        <td></td>
                                        <td></td>
                                        <td class="text-right"><strong class="monto-alto">Q<?php echo number_format($row['total'], 2); ?></strong></td>
                                        <td>
                                            <?php if ($row['tiene_factura'] > 0): ?>
                                                <span class="estado-facturada">Facturada</span>
                                            <?php else: ?>
                                                <span class="estado-pendiente">Pendiente</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php
                                        endif;
                                    ?>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td>
                                            <?php if ($row['nombre_plato']): ?>
                                                <?php echo htmlspecialchars($row['nombre_plato']); ?>
                                            <?php elseif ($row['nombre_bebida']): ?>
                                                <?php echo htmlspecialchars($row['nombre_bebida']); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right"><?php echo $row['cantidad']; ?></td>
                                        <td class="text-right">Q<?php echo number_format($row['subtotal'], 2); ?></td>
                                        <td></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="bi bi-inbox"></i>
                        <h3>No se encontraron registros</h3>
                        <p>No hay <?php echo $tipo_reporte == 'facturas' ? 'facturas' : 'órdenes'; ?> registradas en el sistema con los filtros aplicados.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Establecer fechas por defecto (últimos 30 días) si no hay filtros aplicados
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (!urlParams.has('fecha_desde') && !urlParams.has('fecha_hasta')) {
                const fechaHasta = new Date().toISOString().split('T')[0];
                const fechaDesde = new Date();
                fechaDesde.setDate(fechaDesde.getDate() - 30);
                
                document.getElementById('fecha_hasta').value = fechaHasta;
                document.getElementById('fecha_desde').value = fechaDesde.toISOString().split('T')[0];
            }
        }
    </script>
</body>
</html>

<?php
// Cerrar conexión al final
if (isset($conexion)) {
    desconectar($conexion);
}
?>