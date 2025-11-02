<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
  header("Location: ../login.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Marea Roja | Bitácora</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../css/bootstrap.min.css">
  <link rel="stylesheet" href="../../css/diseñoModulos.css">

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script defer src="../../javascript/bitacora.js"></script>
</head>

<body>
<header class="mb-4">
  <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
    <h1 class="mb-0">🧾 Marea Roja | Bitácora de Actividades</h1>
    <ul class="nav nav-pills gap-2 mb-0">
     <a href="../menu_empleados_vista.php" class="btn-back"><i class="bi bi-arrow-left"></i>Regresar al Menú</a>
    </ul>
  </div>
</header>

<main class="container">
  <section class="card shadow p-4 mb-4">
    <h2 class="card__title mb-3">Historial de Acciones</h2>

    <!-- filtros de busqueda -->
    <div class="row g-3 align-items-end mb-3">
      <div class="col-md-3">
        <label for="filtro-usuario" class="form-label">Usuario:</label>
        <input type="text" class="form-control" id="filtro-usuario" placeholder="Nombre de usuario">
      </div>

      <div class="col-md-3">
        <label for="filtro-fecha-inicio" class="form-label">Fecha inicio:</label>
        <input type="date" class="form-control" id="filtro-fecha-inicio">
      </div>

      <div class="col-md-3">
        <label for="filtro-fecha-fin" class="form-label">Fecha fin:</label>
        <input type="date" class="form-control" id="filtro-fecha-fin">
      </div>

      <div class="col-md-3 d-flex gap-2">
        <button class="btn btn-primary flex-fill" id="btn-filtrar">Filtrar</button>
        <button class="btn btn-secondary flex-fill" id="btn-limpiar">Limpiar</button>
      </div>
    </div>

    <!-- tabla -->
    <div class="table-responsive mt-4">
      <table class="table table-striped table-bordered table-hover align-middle text-center" id="tabla-bitacora">
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Operación Realizada</th>
            <th>IP</th>
            <th>PC</th>
            <th>Fecha y Hora</th>
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
