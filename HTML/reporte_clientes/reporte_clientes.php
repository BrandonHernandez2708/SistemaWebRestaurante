<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

require_once('../conexion.php');
require_once('reporte_clientes_logic.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reporte de Clientes - Marea Roja</title>

  <!-- Fuentes y Estilos -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../../css/diseñoreportes.css">

  <!-- JS -->
  <script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script defer src="../../javascript/reporte_clientes.js"></script>
</head>
<body>

  <!-- Encabezado -->
  <div class="header-full">
    <div class="header-content">
      <h1 class="header-title">
        <i class="bi bi-people-fill me-2"></i>Reporte de Clientes
      </h1>
      <a href="../menu_empleados_vista.php" class="btn-back"><i class="bi bi-arrow-left"></i>Regresar al Menú</a>
    </div>
  </div>

  <div class="container">
    <!-- Filtros -->
    <div class="card filtros">
      <div class="card-body">
        <h2 class="card-title text-primary mb-4">
          <i class="bi bi-funnel me-2"></i>Filtros de Búsqueda
        </h2>
        <form method="GET" action="">
          <div class="filtro-group">
            <div class="filtro-item w-50">
              <label for="busqueda"><i class="bi bi-search me-1"></i>Buscar cliente:</label>
              <input type="text" id="busqueda" name="busqueda"
                     value="<?= htmlspecialchars($busqueda); ?>"
                     placeholder="Nombre, apellido, NIT, teléfono o correo...">
            </div>
            <div class="filtro-item botones-acciones">
              <button type="submit" class="btn btn-buscar" id="btnBuscar">
                <i class="bi bi-search"></i> Buscar
              </button>
              <button type="button" class="btn btn-limpiar" id="btnLimpiar">
                <i class="bi bi-eraser"></i> Limpiar
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Resumen -->
    <div class="resumen">
      <h2 class="card-title text-primary mb-4">
        <i class="bi bi-graph-up me-2"></i>Resumen General
      </h2>
      <div class="resumen-grid">
        <div class="resumen-item">
          <h3>Total Clientes</h3>
          <div class="valor"><?= $total_clientes; ?></div>
        </div>
      </div>
    </div>

    <!-- Exportar -->
    <div class="export-buttons">
      <a href="?<?= http_build_query(array_merge($_GET, ['exportar_excel' => '1'])); ?>" class="btn-export btn-excel">
        <i class="bi bi-file-earmark-excel"></i> Exportar Excel
      </a>
    </div>

    <!-- Tabla -->
    <div class="tabla-container">
      <div class="card-body">
        <h2 class="card-title text-primary mb-4">
          <i class="bi bi-list-ul me-2"></i>Listado de Clientes
        </h2>

        <?php if ($clientes && count($clientes) > 0): ?>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Nombre Completo</th>
                  <th>NIT</th>
                  <th>Teléfono</th>
                  <th>Correo</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($clientes as $c): ?>
                  <tr>
                    <td><?= $c['id_cliente']; ?></td>
                    <td><?= htmlspecialchars($c['nombre_completo']); ?></td>
                    <td><?= htmlspecialchars($c['nit']); ?></td>
                    <td><?= htmlspecialchars($c['telefono']); ?></td>
                    <td><?= htmlspecialchars($c['correo']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Contador dinámico -->
          <div class="contador-resultados mt-3 text-end">
            <i class="bi bi-people"></i>
            Mostrando <strong><?= $total_clientes; ?></strong> 
            <?= $busqueda ? 'clientes filtrados' : 'clientes registrados'; ?> 
            de un total de <strong><?= $total_general; ?></strong>.
          </div>

        <?php else: ?>
          <div class="no-data">
            <i class="bi bi-inbox"></i>
            <h3>No hay clientes</h3>
            <p>No existen datos que coincidan con los filtros aplicados.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>