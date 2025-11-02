<?php
// reservaciones.php vista principal
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marea Roja | Reservaciones</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Frameworks y librerías -->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/animate.min.css">
    <link rel="stylesheet" href="../../css/boxicons.min.css">
    <link rel="stylesheet" href="../../css/swiper-bundle.min.css">
    <link rel="stylesheet" href="../../css/glightbox.min.css">
    

    <!-- css global -->
    <link rel="stylesheet" href="../../css/diseñoModulos.css">

    <!-- Archivo JavaScript para manejos del CRUD y otro para alertas interactivas -->
    <script defer src="../../javascript/Reservaciones.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
</head>

<body>
    <header class="mb-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
            <h1 class="mb-0">🍽️ Marea Roja | Reservaciones</h1>
            
            <ul class="nav nav-pills gap-3 mb-0">
                <li class="nav-item">
                    <a href="../menu_empleados.php" class="btn btn-dorado">Regresar</a>
                </li>
                <li class="nav-item">
                    <a href="../Mesas/mesas.php" class="btn btn-dorado">📅 Mesas</a>
                </li>
            </ul>
        </div>
    </header>

<main class="container">
    <!-- CRUD Reservaciones -->
    <section class="card shadow mb-4 p-4">
        <h2 class="card__title mb-3">Gestión de Reservaciones</h2>

        <form id="form-reservaciones" class="row g-3">

            <!-- id informativo -->
            <div class="col-md-2">
                <label class="form-label" for="res-id">ID Reservación</label>
                <input type="text" class="form-control" id="res-id" name="id_reservacion" readonly>
            </div>

            <!-- cliente -->
            <div class="col-md-3">
                <label class="form-label" for="res-cliente">Cliente:</label>
                <input type="text" class="form-control" id="res-cliente" name="id_cliente" placeholder="Seleccionar cliente">
            </div>

            <!-- mesas -->
            <div class="col-md-2">
                <label class="form-label" for="res-mesa">Mesa:</label>
                <input type="text" class="form-control" id="res-mesa" name="id_mesa" placeholder="Seleccionar mesa">
            </div>

            <!-- personas -->
            <div class="col-md-2">
                <label class="form-label" for="res-personas">Cantidad Personas:</label>
                <input type="number" class="form-control" id="res-personas" name="cantidad_personas" min="1" required>
            </div>

            <!-- hora y fecha -->
            <div class="col-md-3">
                <label class="form-label" for="res-fecha">Fecha y Hora:</label>
                <input type="datetime-local" class="form-control" id="res-fecha" name="fecha_hora" required>
            </div>

            <!-- estado reservacion -->
            <div class="col-md-2">
                <label class="form-label" for="res-estado">Estado:</label>
                <select class="form-control" id="res-estado" name="estado" required>
                    <option value="">Seleccionar...</option>
                    <option value="PROGRAMADA">Programada</option>
                    <option value="CANCELADA">Cancelada</option>
                    <option value="CUMPLIDA">CUMPLIDA</option>
                </select>
            </div>

            <!-- botones -->
            <div class="col-12 d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-success">Insertar</button>
                <button type="button" class="btn btn-danger">Limpiar y Refrescar</button>
            </div>
        </form>

        <!-- tabla -->
        <div class="table-responsive mt-4">
            <table class="table table-striped table-bordered table-hover align-middle text-center" id="tabla-reservaciones">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Mesa</th>
                        <th>Personas</th>
                        <th>Fecha y Hora</th>
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
