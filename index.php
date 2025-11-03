<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta content="width=device-width, initial-scale=1.0" name="viewport" />

  <title>Restaurante Marea Roja</title>
  <meta content="" name="description" />
  <meta content="" name="keywords" />

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon" />
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon" />

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Poppins:300,300i,400,400i,600,600i,700,700i|Satisfy|Comic+Neue:300,300i,400,400i,700,700i" rel="stylesheet" />

  <!-- Vendor CSS Files -->
  <link href="css/animate.min.css" rel="stylesheet" />
  <link href="css/bootstrap.min.css" rel="stylesheet" />
  <link href="css/boxicons.min.css" rel="stylesheet" />
  <link href="css/glightbox.min.css" rel="stylesheet" />
  <link href="css/swiper-bundle.min.css" rel="stylesheet" />

  <!-- Template Main CSS File -->
  <link href="css/style.css" rel="stylesheet" />

  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" />
</head>

<body>
  <!-- ======= Header ======= -->
  <header id="header" class="d-flex align-items-center">
    <div class="container-fluid container-xl d-flex align-items-center justify-content-between">
      <h1 class="logo me-auto d-flex align-items-center">
        <a href="index.php">
          <img src="image/Logo.png" alt="Marea Roja" />
          <span>Marea Roja</span>
        </a>
      </h1>

      <nav id="navbar" class="navbar order-last order-lg-0">
        <ul>
          <li><a class="nav-link scrollto active" href="#hero">Inicio</a></li>
          <li><a class="nav-link scrollto" href="#about">Sobre Nosotros</a></li>
          <li><a class="nav-link scrollto" href="menu.php">Menu</a></li>
          <li><a class="nav-link scrollto" href="#gallery">Galería</a></li>
          <li><a class="nav-link scrollto" href="html/login.php">Empleados</a></li>
        </ul>
        <i class="bi bi-list mobile-nav-toggle"></i>
      </nav>
    </div>
  </header>
  <!-- End Header -->

  <!-- ======= Hero Section ======= -->
  <section id="hero">
    <div class="hero-container">
      <div id="heroCarousel" data-bs-interval="5000" class="carousel slide carousel-fade" data-bs-ride="carousel">
        <ol class="carousel-indicators" id="hero-carousel-indicators">
          <li data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true"></li>
          <li data-bs-target="#heroCarousel" data-bs-slide-to="1"></li>
          <li data-bs-target="#heroCarousel" data-bs-slide-to="2"></li>
        </ol>

        <div class="carousel-inner" role="listbox">
          <!-- Slide 1 -->
          <div class="carousel-item" style="background-image: url('image/slide-1.jpg');">
            <div class="carousel-container">
              <div class="carousel-content">
                <h2 class="animate__animated animate__fadeInDown"></h2>
                <p class="animate__animated animate__fadeInUp"></p>
              </div>
            </div>
          </div>

          <!-- Slide 2 -->
          <div class="carousel-item" style="background-image: url('image/slide-2.jpg');">
            <div class="carousel-container">
              <div class="carousel-content">
                <h2 class="animate__animated animate__fadeInDown">Ceviche de Camarón</h2>
                <p class="animate__animated animate__fadeInUp">Elaborado con los camarones más frescos de primera calidad.</p>
                <a href="menu.php" class="btn-menu animate__animated animate__fadeInUp scrollto">Ver Menú</a>
              </div>
            </div>
          </div>

          <!-- Slide 3 -->
          <div class="carousel-item active" style="background-image: url('image/slide-3.jpg');">
            <div class="carousel-container">
              <div class="carousel-content">
                <h2 class="animate__animated animate__fadeInDown">Mariscos, pastas, carnes y más.</h2>
                <p class="animate__animated animate__fadeInUp">Aquí tenemos para todos los gustos.</p>
                <a href="menu.php" class="btn-menu animate__animated animate__fadeInUp scrollto">Ver Menú</a>
              </div>
            </div>
          </div>
        </div>

        <a class="carousel-control-prev" href="#heroCarousel" role="button" data-bs-slide="prev">
          <span class="carousel-control-prev-icon bi bi-chevron-left" aria-hidden="true"></span>
        </a>
        <a class="carousel-control-next" href="#heroCarousel" role="button" data-bs-slide="next">
          <span class="carousel-control-next-icon bi bi-chevron-right" aria-hidden="true"></span>
        </a>
      </div>
    </div>
  </section>
  <!-- End Hero -->

  <main id="main">
    <!-- ======= About Section ======= -->
    <section id="about" class="about">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-5 align-items-stretch video-box" style="background-image: url('image/about.jpg');">
            <a href="https://www.youtube.com/watch?v=da0g6FHeqX0" class="venobox play-btn mb-4" data-vbtype="video" data-autoplay="true"></a>
          </div>

          <div class="col-lg-7 d-flex flex-column justify-content-center align-items-stretch">
            <div class="content">
              <h3>Somos el restaurante de mariscos <strong>más grande de Guatemala</strong></h3>
              <p>
                Contamos con más de 20 años de experiencia deleitando a guatemaltecos y extranjeros con los sabores más frescos y productos de la más alta calidad traídos directamente del mar.
              </p>
              <p class="fst-italic">Búscanos en nuestras dos sucursales:</p>
              <ul>
                <li><i class="bi bi-check-all"></i> Marea Roja El Rancho km 82.5 Ruta al Atlántico.</li>
                <li><i class="bi bi-check-all"></i> Marea Roja Santa Cruz en el km Río Hondo, Zacapa.</li>
              </ul>
              <p>
                Al llegar al lugar te recibirá una plaza temática con diferentes opciones de comida y como principal atracción Marea Roja, un rancho con aproximadamente 20 metros de altura con un ambiente cálido y acogedor. La decoración marina te transportará a las profundidades del mar, creando una experiencia única.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- End About Section -->
<!-- ======= Gallery Section ======= -->
<section id="gallery" class="gallery py-5">
  <div class="container-fluid px-4">
    <div class="section-title text-center mb-5">
      <h2 class="fw-bold" style="font-family: 'Satisfy', cursive;">Galería de nuestro Restaurante</h2>
    </div>

    <div class="row g-3">
      <div class="col-lg-3 col-md-4 col-sm-6">
        <div class="gallery-item shadow-sm">
          <a href="image/gallery-1.jpg" class="gallery-lightbox">
            <img src="image/gallery-1.jpg" alt="" class="img-fluid rounded" />
          </a>
        </div>
      </div>

      <div class="col-lg-3 col-md-4 col-sm-6">
        <div class="gallery-item shadow-sm">
          <a href="image/gallery-2.jpg" class="gallery-lightbox">
            <img src="image/gallery-2.jpg" alt="" class="img-fluid rounded" />
          </a>
        </div>
      </div>

      <div class="col-lg-3 col-md-4 col-sm-6">
        <div class="gallery-item shadow-sm">
          <a href="image/gallery-3.jpg" class="gallery-lightbox">
            <img src="image/gallery-3.jpg" alt="" class="img-fluid rounded" />
          </a>
        </div>
      </div>

      <div class="col-lg-3 col-md-4 col-sm-6">
        <div class="gallery-item shadow-sm">
          <a href="image/gallery-4.jpg" class="gallery-lightbox">
            <img src="image/gallery-4.jpg" alt="" class="img-fluid rounded" />
          </a>
        </div>
      </div>

      <div class="col-lg-3 col-md-4 col-sm-6">
        <div class="gallery-item shadow-sm">
          <a href="image/gallery-5.jpg" class="gallery-lightbox">
            <img src="image/gallery-5.jpg" alt="" class="img-fluid rounded" />
          </a>
        </div>
      </div>

      <div class="col-lg-3 col-md-4 col-sm-6">
        <div class="gallery-item shadow-sm">
          <a href="image/gallery-6.jpg" class="gallery-lightbox">
            <img src="image/gallery-6.jpg" alt="" class="img-fluid rounded" />
          </a>
        </div>
      </div>

      <div class="col-lg-3 col-md-4 col-sm-6">
        <div class="gallery-item shadow-sm">
          <a href="image/gallery-7.jpg" class="gallery-lightbox">
            <img src="image/gallery-7.jpg" alt="" class="img-fluid rounded" />
          </a>
        </div>
      </div>

      <div class="col-lg-3 col-md-4 col-sm-6">
        <div class="gallery-item shadow-sm">
          <a href="image/gallery-8.jpg" class="gallery-lightbox">
            <img src="image/gallery-8.jpg" alt="" class="img-fluid rounded" />
          </a>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- End Gallery Section -->

<!-- ======= Contact Section ======= -->
<section id="contact" class="contact py-5" style="background-color: #f8f9fa;">
  <div class="container">
    <div class="section-title text-center mb-4">
      <h2 class="fw-bold" style="font-family: 'Satisfy', cursive;">Encuéntranos en Maps</h2>
      <p style="font-size: 1.1rem;">Visítanos y vive una experiencia única en Marea Roja</p>
    </div>
  </div>

  <div class="map-container mx-auto mb-5" style="max-width: 90%; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
    <iframe
      class="map-frame"
      src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d4584.950854259969!2d-90.02006782475465!3d14.910956241676525!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8589fdbe0583cd07%3A0xb471e21319dbe1b1!2sMarea%20Roja%20El%20Rancho%20Oficial!5e0!3m2!1ses!2sgt!4v1696356838367!5m2!1ses!2sgt"
      width="100%"
      height="450"
      style="border:0;"
      allowfullscreen=""
      loading="lazy"
      referrerpolicy="no-referrer-when-downgrade">
    </iframe>
  </div>

  <div class="container">
    <div class="row text-center gy-4 justify-content-center">
      <div class="col-md-3">
        <i class="bi bi-geo-alt-fill text-danger fs-2"></i>
        <h5 class="fw-bold mt-2">Ubicación</h5>
        <p>Km 81.5 ruta al Atlántico<br>El Rancho, El Progreso</p>
      </div>

      <div class="col-md-3">
        <i class="bi bi-clock-fill text-danger fs-2"></i>
        <h5 class="fw-bold mt-2">Horarios de atención</h5>
        <p>Lunes a Domingo:<br>9:00 AM - 5:00 PM</p>
      </div>

      <div class="col-md-3">
        <i class="bi bi-envelope-fill text-danger fs-2"></i>
        <h5 class="fw-bold mt-2">Email</h5>
        <p>info@restaurantemarearoja.com</p>
      </div>

      <div class="col-md-3">
        <i class="bi bi-telephone-fill text-danger fs-2"></i>
        <h5 class="fw-bold mt-2">Teléfono</h5>
        <p>+502 3081-6909 14</p>
      </div>
    </div>
  </div>
</section>
<!-- End Contact Section -->



  <!-- ======= Footer ======= -->
  <footer id="footer">
    <div class="container">
      <h3>Marea Roja</h3>
      <p>En MAREA ROJA, nos enorgullecemos de servir los mariscos más frescos y deliciosos de Guatemala.</p>
      <div class="social-links">
        <a href="https://www.facebook.com/marearojaelrancho" class="facebook"><i class="bi bi-facebook"></i></a>
        <a href="https://www.instagram.com/marearojaoficial/" class="instagram"><i class="bi bi-instagram"></i></a>
      </div>
      <div class="copyright">
        © 2025 <strong><span>Marea Roja</span></strong>. Todos los derechos reservados.
      </div>
      <div class="credits">
        <!-- Créditos opcionales -->
      </div>
    </div>
  </footer>
  <!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center active">
    <i class="bi bi-arrow-up-short"></i>
  </a>

  <!-- Vendor JS Files -->
  <script src="javascript/bootstrap.bundle.min.js"></script>
  <script src="javascript/glightbox.min.js"></script>
  <script src="javascript/isotope.pkgd.min.js"></script>
  <script src="javascript/swiper-bundle.min.js"></script>
  <script src="javascript/validate.js"></script>

  <!-- Template Main JS File -->
  <script src="javascript/main.js"></script>
</body>
</html>
