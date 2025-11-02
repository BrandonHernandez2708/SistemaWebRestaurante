<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

require_once('../conexion.php');
require_once('reporte_reservaciones_logic.php');

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reporte de Reservaciones - Marea Roja</title>

  <!-- Fuentes y Bootstrap Icons y Alertas dinámicas -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

  <!-- Estilos personalizados -->
  <link rel="stylesheet" href="../../css/diseñoreportes.css">

  <!-- Script principal -->
  <script defer src="../../javascript/reporte_reservaciones.js"></script>
</head>
<body>
  <!-- Barra superior -->
  <div class="header-full">
    <div class="header-content">
      <h1 class="header-title">
        <i class="bi bi-calendar-check me-2"></i>Reporte de Reservaciones
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
            <div class="filtro-item">
              <label for="cliente"><i class="bi bi-person me-1"></i>Cliente:</label>
              <select id="cliente" name="cliente">
                <option value="">Todos los clientes</option>
                <?php foreach ($clientes as $c): ?>
                  <option value="<?= $c['id_cliente']; ?>" <?= ($cliente_id == $c['id_cliente']) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($c['nombre']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="filtro-item">
              <label for="estado"><i class="bi bi-tag me-1"></i>Estado:</label>
              <select id="estado" name="estado">
                <option value="">Todos</option>
                <option value="PROGRAMADA" <?= $estado == 'PROGRAMADA' ? 'selected' : ''; ?>>Programada</option>
                <option value="CUMPLIDA" <?= $estado == 'CUMPLIDA' ? 'selected' : ''; ?>>Cumplida</option>
                <option value="CANCELADA" <?= $estado == 'CANCELADA' ? 'selected' : ''; ?>>Cancelada</option>
              </select>
            </div>
            <div class="filtro-item">
              <label for="desde"><i class="bi bi-calendar-event me-1"></i>Desde:</label>
              <input type="date" id="desde" name="desde" value="<?= htmlspecialchars($fecha_inicio); ?>">
            </div>
            <div class="filtro-item">
              <label for="hasta"><i class="bi bi-calendar-event me-1"></i>Hasta:</label>
              <input type="date" id="hasta" name="hasta" value="<?= htmlspecialchars($fecha_fin); ?>">
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
          <h3>Total Reservaciones</h3>
          <div class="valor"><?= $total_reservaciones; ?></div>
        </div>
        <div class="resumen-item">
          <h3>Programadas</h3>
          <div class="valor text-info"><?= $total_programadas; ?></div>
        </div>
        <div class="resumen-item">
          <h3>Cumplidas</h3>
          <div class="valor text-success"><?= $total_cumplidas; ?></div>
        </div>
        <div class="resumen-item">
          <h3>Canceladas</h3>
          <div class="valor text-danger"><?= $total_canceladas; ?></div>
        </div>
      </div>
    </div>

    <!-- Botones -->
    <div class="export-buttons">
      <a href="?<?= http_build_query(array_merge($_GET, ['exportar_excel' => '1'])); ?>" class="btn-export btn-excel">
        <i class="bi bi-file-earmark-excel"></i>Exportar Excel
      </a>
    </div>

    <!-- Tabla -->
    <div class="tabla-container">
      <div class="card-body">
        <h2 class="card-title text-primary mb-4">
          <i class="bi bi-list-ul me-2"></i>Listado de Reservaciones
        </h2>
        <?php if ($resultados && count($resultados) > 0): ?>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Cliente</th>
                  <th>Mesa</th>
                  <th>Personas</th>
                  <th>Fecha y Hora</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($resultados as $r): ?>
                  <tr>
                    <td><?= $r['id_reservacion']; ?></td>
                    <td><?= htmlspecialchars($r['cliente']); ?></td>
                    <td><?= htmlspecialchars($r['mesa']); ?></td>
                    <td><?= $r['cantidad_personas']; ?></td>
                    <td><?= $r['fecha_hora']; ?></td>
                    <td><span class="badge badge-<?= strtolower($r['estado']); ?>"><?= $r['estado']; ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="no-data">
            <i class="bi bi-inbox"></i>
            <h3>No hay reservaciones</h3>
            <p>No existen datos con los filtros aplicados.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../javascript/reporte_reservaciones.js"></script>
</body>
</html>
