<?php
// mesas.php — Vista principal
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Marea Roja | Mesas</title>

  <!-- Fuentes y Frameworks -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../css/bootstrap.min.css">
  <link rel="stylesheet" href="../../css/diseñoModulos.css">

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Script de funcionalidad -->
  <script defer src="../../javascript/mesas.js"></script>
</head>
<body>

<header class="mb-4">
  <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
    <h1 class="mb-0">🍽️ Marea Roja | Gestión de Mesas</h1>
    <ul class="nav nav-pills gap-3 mb-0">
      <li class="nav-item">
        <a href="../menu_empleados.php" class="btn btn-dorado">Regresar</a>
      </li>
      <li class="nav-item">
        <a href="../Reservaciones/reservaciones.php" class="btn btn-dorado">📅 Reservaciones</a>
      </li>
  </ul>

  </div>
</header>

<main class="container">
  <section class="card shadow p-4 mb-4">
    <h2 class="card__title mb-3">Administrar Mesas</h2>

    <form id="form-mesas" class="row g-3">
      <div class="col-md-2">
        <label for="mesa-id" class="form-label">ID Mesa</label>
        <input type="text" class="form-control" id="mesa-id" name="id_mesa" readonly>
      </div>

      <div class="col-md-4">
        <label for="mesa-descripcion" class="form-label">Descripción</label>
        <input type="text" class="form-control" id="mesa-descripcion" name="descripcion" placeholder="Ej. Mesa junto a la ventana" required>
      </div>

      <div class="col-md-3">
        <label for="mesa-capacidad" class="form-label">Capacidad de Personas</label>
        <input type="number" class="form-control" id="mesa-capacidad" name="capacidad_personas" min="1" required>
      </div>

      <div class="col-md-3">
        <label for="mesa-estado" class="form-label">Estado</label>
        <select class="form-control" id="mesa-estado" name="estado" required>
          <option value="">Seleccionar...</option>
          <option value="DISPONIBLE">Disponible</option>
          <option value="OCUPADA">Ocupada</option>
          <option value="RESERVADA">Reservada</option>
          <option value="FUERA_DE_SERVICIO">Fuera de servicio</option>
        </select>
      </div>

      <div class="col-12 d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-success">Insertar</button>
        <button type="button" class="btn btn-danger">Actualizar</button>
      </div>
    </form>

    <div class="table-responsive mt-4">
      <table class="table table-striped table-bordered table-hover align-middle text-center" id="tabla-mesas">
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>Descripción</th>
            <th>Capacidad</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </section>
</main>

<footer class="text-center py-3">
  &copy; 2025 Marea Roja - Sistema de Reservaciones
</footer>

</body>
</html>
