<?php
session_start();
require_once 'conexion.php';

// Inicializar arrays
$departamentos = [];
$puestos = [];
$error = '';

// Verificar si las tablas necesarias existen y tienen datos
try {
    $conexion = conectar();
    
    // Obtener departamentos
    $sql_deptos = "SELECT id_departamento, nombre_departamento FROM departamentos ORDER BY nombre_departamento";
    $result_deptos = $conexion->query($sql_deptos);
    if ($result_deptos && $result_deptos->num_rows > 0) {
        $departamentos = $result_deptos->fetch_all(MYSQLI_ASSOC);
    } else {
        $error = "No hay departamentos registrados. Contacta al administrador.";
    }
    
    // Obtener puestos
    $sql_puestos = "SELECT id_puesto, puesto FROM puesto ORDER BY puesto";
    $result_puestos = $conexion->query($sql_puestos);
    if ($result_puestos && $result_puestos->num_rows > 0) {
        $puestos = $result_puestos->fetch_all(MYSQLI_ASSOC);
    } else {
        $error = "No hay puestos registrados. Contacta al administrador.";
    }
    
    desconectar($conexion);
    
} catch (Exception $e) {
    $error = "Error al conectar con la base de datos: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuario</title>
    <link rel="stylesheet" href="../css/login_empleado.css">
    <style>
        .form-section {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .section-title {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.2em;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
        }
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        .form-group {
            flex: 1;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border: 1px solid #c3e6cb;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group small {
            color: #7f8c8d;
            font-size: 0.8em;
        }
        .btn:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box" style="max-width: 600px;">
            <h1>Crear Nueva Cuenta</h1>
            
            <!-- Mostrar mensajes -->
            <?php if (isset($_GET['error'])): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($error) && !empty($departamentos) && !empty($puestos)): ?>
            <form action="procesar_registro.php" method="post">
                <!-- Información Personal -->
                <div class="form-section">
                    <h3 class="section-title">Información Personal</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="dpi">DPI *</label>
                            <input type="text" id="dpi" name="dpi" placeholder="Ingresa tu DPI" maxlength="20" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre_empleado">Nombre *</label>
                            <input type="text" id="nombre_empleado" name="nombre_empleado" placeholder="Ingresa tu nombre" required>
                        </div>
                        <div class="form-group">
                            <label for="apellido_empleado">Apellido *</label>
                            <input type="text" id="apellido_empleado" name="apellido_empleado" placeholder="Ingresa tu apellido" required>
                        </div>
                    </div>
                </div>

                <!-- Información Laboral -->
                <div class="form-section">
                    <h3 class="section-title">Información Laboral</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="id_departamento">Departamento *</label>
                            <select id="id_departamento" name="id_departamento" required>
                                <option value="">Selecciona un departamento</option>
                                <?php foreach ($departamentos as $depto): ?>
                                    <option value="<?php echo htmlspecialchars($depto['id_departamento']); ?>">
                                        <?php echo htmlspecialchars($depto['nombre_departamento']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="id_puesto">Puesto *</label>
                            <select id="id_puesto" name="id_puesto" required>
                                <option value="">Selecciona un puesto</option>
                                <?php foreach ($puestos as $puesto): ?>
                                    <option value="<?php echo htmlspecialchars($puesto['id_puesto']); ?>">
                                        <?php echo htmlspecialchars($puesto['puesto']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Credenciales -->
                <div class="form-section">
                    <h3 class="section-title">Credenciales de Acceso</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="usuario">Usuario *</label>
                            <input type="text" id="usuario" name="usuario" placeholder="Crea tu nombre de usuario" required>
                            <small>Este será tu nombre de usuario para iniciar sesión</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contrasenia">Contraseña *</label>
                            <input type="password" id="contrasenia" name="contrasenia" placeholder="Crea tu contraseña" required>
                            <small>Mínimo 6 caracteres</small>
                        </div>
                        <div class="form-group">
                            <label for="confirmar_contrasenia">Confirmar Contraseña *</label>
                            <input type="password" id="confirmar_contrasenia" name="confirmar_contrasenia" placeholder="Repite tu contraseña" required>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn">Crear Cuenta</button>
                
                <div class="create-user-section">
                    <p>¿Ya tienes cuenta?</p>
                    <a href="login.php" class="btn-create">Iniciar Sesión</a>
                </div>
            </form>
            <?php else: ?>
                <div class="error-message">
                    No se puede mostrar el formulario. Verifica que:
                    <ul>
                        <li>La tabla 'departamentos' tenga datos</li>
                        <li>La tabla 'puesto' tenga datos</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Validación de contraseñas
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const password = document.getElementById('contrasenia').value;
            const confirmPassword = document.getElementById('confirmar_contrasenia').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Las contraseñas no coinciden. Por favor, verifica.');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 6 caracteres.');
                return false;
            }
        });
    </script>
</body>
</html>
