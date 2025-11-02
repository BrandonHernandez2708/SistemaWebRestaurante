<?php
// chatgpt.php — Vista principal
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Marea Roja | Asistente SQL (GPT-5 mini)</title>

  <!-- Fuentes y Frameworks -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../css/bootstrap.min.css">
  <link rel="stylesheet" href="../../css/diseñoModulos.css">

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Script AJAX -->
  <script defer src="../../javascript/chatgpt.js"></script>
</head>
<body>

<header class="mb-4">
  <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
    <h1 class="mb-0"> Marea Roja | Asistente SQL</h1>
       <ul class="nav nav-pills gap-3 mb-0">
      <li class="nav-item">
        <a href="../menu_empleados.php" class="btn btn-dorado">Regresar</a>
      </li>
  </ul>
  </div>
</header>

<main class="container">
  <section class="card shadow p-4 mb-4">
    <h2 class="card__title mb-3">Consultas SQL con GPT-5 mini</h2>
    <p class="text-muted mb-4">Describe tu consulta y el asistente generará un <b>SELECT SQL</b> basado en la estructura de la base de datos del restaurante.</p>

    <form id="form-chatgpt" class="mb-3" method="POST" action="#">
      <div class="mb-3">
        <label for="consulta" class="form-label fw-semibold">Descripción de la consulta</label>
        <textarea class="form-control" id="consulta" name="consulta" rows="3" placeholder="Ejemplo: mostrar las mesas disponibles hoy..." required></textarea>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">Generar consulta</button>
        <button type="button" class="btn btn-secondary px-4" id="btn-limpiar">Limpiar</button>
      </div>
    </form>

    <div id="resultado" class="mt-4">
      <h5>Resultado:</h5>
      <pre class="border p-3 bg-light" id="sql-generado">Esperando consulta...</pre>
    </div>
  </section>
</main>

<footer class="text-center py-3">
  &copy; 2025 Marea Roja - Asistente SQL
</footer>

</body>
</html>

