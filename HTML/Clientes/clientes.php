<?php
// clientes.php — Vista principal
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Marea Roja | Clientes</title>

  <!-- Fuentes y Frameworks -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../css/bootstrap.min.css">
  <link rel="stylesheet" href="../../css/diseñoModulos.css">

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Script principal -->
  <script defer src="../../javascript/clientes.js"></script>
</head>
<body>

<header class="mb-4">
  <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
    <h1 class="mb-0">👥 Marea Roja | Gestión de Clientes</h1>
        <ul class="nav nav-pills gap-3 mb-0">
      <li class="nav-item">
        <a href="../menu_empleados.php" class="btn-back"><i class="bi bi-arrow-left"></i>Regresar al Menú</a>
      </li>
  </ul>
  </div>
</header>

<main class="container">
  <section class="card shadow p-4 mb-4">
    <h2 class="card__title mb-3">Administrar Clientes</h2>

    <form id="form-clientes" class="row g-3">
      <div class="col-md-2">
        <label for="cliente-id" class="form-label fw-semibold">ID Cliente</label>
        <input type="text" class="form-control" id="cliente-id" name="id_cliente" readonly>
      </div>

      <div class="col-md-5">
        <label for="cliente-nombre" class="form-label fw-semibold">Nombre *</label>
        <input type="text" class="form-control" id="cliente-nombre" name="nombre" placeholder="Nombre del cliente" required>
      </div>

      <div class="col-md-5">
        <label for="cliente-apellido" class="form-label fw-semibold">Apellido</label>
        <input type="text" class="form-control" id="cliente-apellido" name="apellido" placeholder="Apellido del cliente">
      </div>

      <div class="col-md-4">
        <label for="cliente-nit" class="form-label fw-semibold">NIT *</label>
        <input type="text" class="form-control" id="cliente-nit" name="nit" placeholder="Ingrese el NIT" required>
      </div>

      <div class="col-md-4">
        <label for="cliente-telefono" class="form-label fw-semibold">Teléfono</label>
        <input type="text" class="form-control" id="cliente-telefono" name="telefono" placeholder="Ej. 5555-1234">
      </div>

      <div class="col-md-4">
        <label for="cliente-correo" class="form-label fw-semibold">Correo Electrónico</label>
        <input type="email" class="form-control" id="cliente-correo" name="correo" placeholder="correo@ejemplo.com">
      </div>

      <div class="col-12 d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-success px-4">Guardar</button>
        <button type="button" class="btn btn-danger btn-refrescar px-4">Limpiar y Refrescar</button>
      </div>
    </form>

    <!-- Tabla de clientes -->
    <div class="table-responsive mt-4">
      <table class="table table-striped table-bordered table-hover align-middle text-center" id="tabla-clientes">
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>NIT</th>
            <th>Teléfono</th>
            <th>Correo</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </section>
</main>

<footer class="text-center py-3">
  &copy; 2025 Marea Roja - Sistema de Registro Clientes
</footer>

</body>
</html>

