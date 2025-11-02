<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marea Roja | Ventas</title>
    <link rel="stylesheet" href="../css/diseñoModulos.css">
    <link rel="stylesheet" href="../css/diseñoMenuEmpleados.css">
</head>
<body id="body-empleados">
    <?php
    session_start();
    // Verificar si el usuario está logueado
    if (!isset($_SESSION['id_usuario'])) {
        header('Location: login.php');
        exit();
    }
    ?>
    
    <header>
        <h1>
            <img src="../image/Logo.png" width="60" height="60" alt="Marea Roja"> 
            Marea Roja
        </h1>
        <div class="user-info">
            Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre'] ?? 'No disponible'); ?>
        </div>
    </header>
    
    <div class="sidebar-empleados">
        <ul>
            <li>
                <a href="#" class="submenu-toggle"><span>📅</span> Reservaciones</a>
                <ul class="submenu">
                    <li><a href="#" class="consulta-only" data-href="../HTML/Mesas/mesas.php">Mesas</a></li>
                    <li><a href="#" class="consulta-only" data-href="../HTML/Reservaciones/reservaciones.php">Nueva Reservación</a></li>
                </ul>
            </li>

            <li>
                <a href="#" class="submenu-toggle"><span>👥</span> Gestión de Empleados</a>
                <ul class="submenu">
                    <li><a href="#" class="consulta-only" data-href="gestion_empleados/Empleados.php">Empleados</a></li>
                    <li><a href="#" class="consulta-only" data-href="gestion_empleados/Telefono_empleados.php">Teléfonos</a></li>
                    <li><a href="#" class="consulta-only" data-href="gestion_empleados/Correo_empleados.php">Correos</a></li>
                    <li><a href="#" class="consulta-only" data-href="gestion_empleados/Contactos_emergencias.php">Contactos de Emergencias</a></li>

                </ul>
            </li>

            <li>
                <a href="#" class="submenu-toggle"><span>📍</span> Gestión Departamental</a>
                <ul class="submenu">
                    <li><a href="Reportes_Empleados/Empleados_Sucursal.php">Asignacion de Sucursales a empleados</a></li>
                </ul>
            </li>

            <li>
                <a href="#" class="submenu-toggle"><span>🪑</span> Gestión de Mobiliario</a>
                <ul class="submenu">
                    <li><a href="#" class="consulta-only" data-href="../HTML/gestion_de_mobiliario/gestion_mobiliario.php">Control del Mobiliario</a></li>
                    <li><a href="#" class="consulta-only" data-href="../HTML/gestion_de_mobiliario/compras_mobiliario.php">Gestion de Compras</a></li>
                    <li><a href="#" class="consulta-only" data-href="../HTML/gestion_de_mobiliario/detalle_compras_mobiliario.php">detalle de Compras</a></li>
                </ul>
            </li>

            <li>
                <a href="#" class="submenu-toggle"><span>🛠️</span> mantenimiento de Mobiliario</a>
                <ul class="submenu">
                    <li><a href="#" class="consulta-only" data-href="../HTML/mantenimiento_de_Mobiliario/mantenimiento_muebles.php">mantenimiento de muebles</a></li>
                    <li><a href="#" class="consulta-only" data-href="../HTML/mantenimiento_de_Mobiliario/mantenimiento_electrodomesticos.php">mantenimiento de electrónica</a></li>
                    
                </ul>
            </li>

            <li>
                <a href="#" class="submenu-toggle"><span>🚗</span> Gestión de Vehiculos</a>
                <ul class="submenu">
                    <li><a href="#" class="consulta-only" data-href="../HTML/gestion_de_vehiculos/gestion_vehiculos.php">Gestion de Vehiculos</a></li>
                    <li><a href="#" class="consulta-only" data-href="../HTML/gestion_de_vehiculos/mantenimiento_vehiculos.php">mantenimiento</a></li>
                    <li><a href="#" class="consulta-only" data-href="../HTML/gestion_de_vehiculos/viajes_vehiculos.php">Viajes</a></li>
                    <li><a href="#" class="consulta-only" data-href="../HTML/gestion_de_vehiculos/rutas_vehiculos.php">Rutas</a></li>
                    <li><a href="#" class="consulta-only" data-href="../HTML/gestion_de_vehiculos/reportes_accidentes.php">Accidentes</a></li>
                </ul>
            </li>

            <li>
                <a href="#" class="submenu-toggle"><span>⚙️</span> Taller de vehiculos</a>
                <ul class="submenu">
                    <li><a href="#" class="consulta-only" data-href="../HTML/taller_de_vehiculos/taller_vehiculos.php">Taller</a></li>
                </ul>
            </li>
            
             <li>
                <a href="#" class="submenu-toggle"><span>🍺🍽️</span> Platos Y Bebidas</a>
                <ul class="submenu">
                    <li><a href="../HTML/Reporte_Receta_Bebidas_Platos/Consulta_Plato/Consultas_Platos.php">Reporte de Bebidas,Platos y Recetas</a></li>
                </ul>
            </li>

            <li>
                <a href="#" class="submenu-toggle"><span>🦞</span> Inventario Ingredientes</a>
                <ul class="submenu">
                     <li><a href="../HTML/Reporte_Inventario_ingredientes/Reporte_Control_Ingredientes.php">Reporte de Control Ingredientes</a></li>
                     <li><a href="../HTML/Reporte_Inventario_ingredientes/Reporte_Bodega_Ingredientes.php">Reporte de Inventario Ingredientes</a></li>
                </ul>
            </li>
            
             <li>
                <a href="#" class="submenu-toggle"><span>🦀🛒</span> Compra de Ingredientes</a>
                <ul class="submenu">
                    <li><a href="../HTML/Reporte_Inventario_ingredientes/Reporte_Compras_Ingrediente.php">Reporte de Compras Ingredientes</a></li>
                </ul>
            </li>

            <li>
                <a href="#" class="submenu-toggle"><span>👤</span> Proveedores</a>
                <ul class="submenu">
                    <li><a href="#" class="consulta-only" data-href="../HTML/proveedores/gestion_proveedores.php">Gestion de Proveedores</a></li>
 
                </ul>
            </li>

  <li>
    <a href="#" class="submenu-toggle"><span>💰</span> Facturaciones</a>
    <ul class="submenu">
       
        <li><a href="Reporte_Facturacion/Reporte_Facturacion.php">Reportes de Facturación</a></li>
    </ul>
</li>
       

                <li>
                <a href="#" class="submenu-toggle"><span>📦</span> Insumos</a>
                <ul class="submenu">
                    <li>
                    <a href="Reportes_Insumos/lista_insumos.php">Lista de Insumos</a>
                    <a href="Reportes_Insumos/detalle_compras_insumos.php">Detalle de Compras de Insumos</a>
                    </li>
                </ul>
                </li>
            <li><a href="login.php"><span>🚪</span> Cerrar Sesión</a></li>
        </ul>
    </div>

    <div class="container">
        <div class="consulta-notice">Nota: Esta vista es solo para consultar datos. Las acciones y redirecciones están deshabilitadas aquí.</div>
        <h2>🏢 Panel de Control - Empleados</h2>
        <a class="btn-reportes" href="menu_empleados.php">Ver Mantenimeintos</a>
        <div id="tabla-reservaciones">
            <h3>¡Bienvenido al Sistema de Gestión Marea Roja!</h3>
            <p>Selecciona una opción del menú lateral para comenzar a gestionar el restaurante</p>
            <p><strong>Usuario:</strong> <?php echo htmlspecialchars($_SESSION['usuario'] ?? 'No disponible'); ?></p>
        </div>
    </div>

    <footer>
        &copy; 2025 Marea Roja - Sistema de Gestión
    </footer>
    <script src="../javascript/submenu.js"></script>
</body>
</html>