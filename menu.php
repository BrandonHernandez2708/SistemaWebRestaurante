<?php
require_once 'html/conexion.php';
$conn = conectar();
$sql = "SELECT nombre_plato, descripcion, precio_unitario FROM platos ORDER BY nombre_plato";
$resultado = $conn->query($sql);
$platos = [];
if ($resultado && $resultado->num_rows > 0) {
    while($fila = $resultado->fetch_assoc()) {
        $platos[] = $fila;
    }
}
desconectar($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Menú - Marea Roja</title>

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Poppins:300,300i,400,400i,600,600i,700,700i|Satisfy|Comic+Neue:300,300i,400,400i,700,700i" rel="stylesheet">

  <!-- Vendor CSS -->
  <link href="css/animate.min.css" rel="stylesheet">
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="css/boxicons.min.css" rel="stylesheet">
  <link href="css/glightbox.min.css" rel="stylesheet">
  <link href="css/swiper-bundle.min.css" rel="stylesheet">

  <!-- Template CSS -->
  <link href="css/style.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>

<body>

<!-- ======= Header ======= -->
<header id="header" class="d-flex align-items-center">
  <div class="container-fluid container-xl d-flex align-items-center justify-content-between">

    <h1 class="logo me-auto d-flex align-items-center">
      <a href="index.php">
        <img src="image/Logo.png" alt="Marea Roja">
        <span>Marea Roja</span>
      </a>
    </h1>

    <nav id="navbar" class="navbar order-last order-lg-0">
      <ul>
        <li><a class="nav-link" href="index.php#hero">Inicio</a></li>
        <li><a class="nav-link" href="index.php#about">Sobre Nosotros</a></li>
        <li><a class="nav-link active" href="menu.php">Menu</a></li>
        <li><a class="nav-link" href="index.php#gallery">Galería</a></li>
        <li><a class="nav-link" href="html/login.php">Empleados</a></li>
      </ul>
      <i class="bi bi-list mobile-nav-toggle"></i>
    </nav>
  </div>
</header>
<!-- End Header -->

<!-- ======= Sección del Menú ======= -->
<section id="platos" class="menu py-5 mt-5">
  <div class="container">
    <div class="section-title text-center mb-5">
      <h2>Nuestros <span>Platos Destacados</span></h2>
      <p>Sabores únicos del mar y la tierra, preparados con pasión en Marea Roja.</p>
    </div>

    <div class="row" id="catalogo-platos">
      <?php if (!empty($platos)): ?>
        <?php foreach($platos as $index => $plato): 
          $colors = ['#bfdbfe', '#fecaca', '#fde68a', '#bbf7d0', '#a5f3fc', '#fbcfe8'];
          $bgColor = $colors[$index % count($colors)];
        ?>
        <div class="col-lg-3 col-md-4 col-sm-6 mb-4 d-flex">
          <div class="card shadow flex-fill text-center border-0" style="background-color: <?= $bgColor ?>;">
            <div class="card-body">
              <img src="https://placehold.co/300x200/ffffff/333333?text=<?= urlencode($plato['nombre_plato']) ?>" 
                   alt="<?= htmlspecialchars($plato['nombre_plato']) ?>" 
                   class="img-fluid rounded shadow-sm mb-3">
              <h5 class="fw-bold"><?= htmlspecialchars($plato['nombre_plato']) ?></h5>
              <p class="text-muted small"><?= htmlspecialchars($plato['descripcion']) ?></p>
              <span class="badge bg-danger fs-6">Q <?= number_format($plato['precio_unitario'], 2) ?></span>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="text-center text-muted">Aún no hay platos registrados.</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ======= Footer ======= -->
<footer id="footer">
  <div class="container">
    <h3>Marea Roja</h3>
    <p>Nos enorgullecemos de servir los mariscos más frescos y deliciosos de Guatemala.</p>
    <div class="social-links">
      <a href="https://www.facebook.com/marearojaelrancho" class="facebook"><i class="bi bi-facebook"></i></a>
      <a href="https://www.instagram.com/marearojaoficial/" class="instagram"><i class="bi bi-instagram"></i></a>
    </div>
    <div class="copyright"></div>
  </div>
</footer>

<a href="#" class="back-to-top d-flex align-items-center justify-content-center">
  <i class="bi bi-arrow-up-short"></i>
</a>

<!-- JS -->
<script src="javascript/bootstrap.bundle.min.js"></script>
<script src="javascript/glightbox.min.js"></script>
<script src="javascript/isotope.pkgd.min.js"></script>
<script src="javascript/swiper-bundle.min.js"></script>
<script src="javascript/validate.js"></script>
<script src="javascript/main.js"></script>

</body>
</html>
