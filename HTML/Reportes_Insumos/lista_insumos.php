<?php
session_start();
require_once '../conexion.php';

// proteger acceso
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// filtros
$q = $_GET['q'] ?? '';
$stock_min = $_GET['stock_min'] ?? '';
$stock_max = $_GET['stock_max'] ?? '';

function obtenerInsumosFiltrados(string $q, $stock_min, $stock_max): array {
    $conn = conectar();
    $where = [];
    $types = '';
    $params = [];

    if ($q !== '') {
        $where[] = "(i.insumo LIKE ? OR i.descripcion LIKE ?)";
        $types .= 'ss';
        $like = "%{$q}%";
        $params[] = $like;
        $params[] = $like;
    }
    if ($stock_min !== '' && is_numeric($stock_min)) {
        $where[] = "i.stock >= ?";
        $types .= 'i';
        $params[] = (int)$stock_min;
    }
    if ($stock_max !== '' && is_numeric($stock_max)) {
        $where[] = "i.stock <= ?";
        $types .= 'i';
        $params[] = (int)$stock_max;
    }

    $sql = "SELECT i.id_insumo, i.insumo, i.descripcion, i.stock
            FROM inventario_insumos i";
    if ($where) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY i.insumo ASC";

    if ($params) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $res = $conn->query($sql);
    }

    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    if (isset($stmt)) $stmt->close();
    desconectar($conn);
    return $rows;
}

$insumos = obtenerInsumosFiltrados($q, $stock_min, $stock_max);
$total_items = count($insumos);
$total_stock = 0;
foreach ($insumos as $r) $total_stock += (int)$r['stock'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Listado de Insumos - Marea Roja</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/Reportes.css">
</head>
<body>
    <div class="header-full">
        <div class="header-content">
            <h1 class="header-title">Listado de Insumos</h1>
            <a href="../menu_empleados_vista.php" class="nav-link">Regresar al Menú</a>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title text-primary mb-4">Filtros de Búsqueda</h2>
                <form method="GET" action="">
                    <div class="filtro-group">
                        <div class="filtro-item">
                            <label for="q">Buscar</label>
                            <input type="search" id="q" name="q" placeholder="Nombre o descripción..." value="<?= htmlspecialchars($q); ?>">
                        </div>
                        <div class="filtro-item">
                            <label for="stock_min">Stock mínimo</label>
                            <input type="number" id="stock_min" name="stock_min" value="<?= htmlspecialchars($stock_min); ?>">
                        </div>
                        <div class="filtro-item">
                            <label for="stock_max">Stock máximo</label>
                            <input type="number" id="stock_max" name="stock_max" value="<?= htmlspecialchars($stock_max); ?>">
                        </div>
                        <div class="filtro-item" style="min-width:auto;">
                            <button type="submit" class="btn-buscar">Buscar</button>
                            <a href="lista_insumos.php" class="btn-buscar btn-limpiar">Limpiar</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="resumen">
            <h2 class="card-title text-primary mb-4">Resumen</h2>
            <div class="resumen-grid">
                <div class="resumen-item">
                    <h3>Total de Insumos</h3>
                    <div class="valor"><?= number_format($total_items); ?></div>
                </div>
                <div class="resumen-item">
                    <h3>Stock Acumulado</h3>
                    <div class="valor"><?= number_format($total_stock, 0, '', ''); ?></div>
                </div>
            </div>
        </div>

        <div class="export-buttons">
            <a href="exportar_excel_insumos.php?<?= http_build_query($_GET); ?>" class="btn-export">Exportar Excel</a>
        </div>

        <div class="tabla">
            <div class="card-body">
                <?php if ($insumos): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:100px;">ID</th>
                                <th>Insumo</th>
                                <th>Descripción</th>
                                <th class="text-right" style="width:160px;">Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($insumos as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['id_insumo']); ?></td>
                                <td><?= htmlspecialchars($r['insumo']); ?></td>
                                <td><?= htmlspecialchars($r['descripcion'] ?: 'Sin descripción'); ?></td>
                                <td class="text-right"><?= number_format((int)$r['stock'], 0, '', ''); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="no-data">
                    <h3>No se encontraron insumos</h3>
                    <p>Intenta ajustar los filtros de búsqueda.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
