<?php
session_start();
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dpi = trim($_POST['dpi']);
    $nombre = trim($_POST['nombre_empleado']);
    $apellido = trim($_POST['apellido_empleado']);
    $id_departamento = $_POST['id_departamento'];
    $id_puesto = $_POST['id_puesto'];
    $usuario = trim($_POST['usuario']);
    $contrasenia = $_POST['contrasenia'];
    $confirmar = $_POST['confirmar_contrasenia'];

    // 🧩 Validaciones básicas
    if (empty($dpi) || empty($nombre) || empty($apellido) || empty($usuario) || empty($contrasenia)) {
        header("Location: crear_usuario.php?error=Completa todos los campos obligatorios");
        exit();
    }

    if ($contrasenia !== $confirmar) {
        header("Location: crear_usuario.php?error=Las contraseñas no coinciden");
        exit();
    }

    if (strlen($contrasenia) < 6) {
        header("Location: crear_usuario.php?error=La contraseña debe tener al menos 6 caracteres");
        exit();
    }

    try {
        $conn = conectar();

        // 🧠 Verificar si el usuario ya existe
        $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE usuario = ?");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            header("Location: crear_usuario.php?error=El nombre de usuario ya existe");
            exit();
        }
        $stmt->close();

        // 🧩 Insertar empleado (solo columnas existentes)
        $sql_emp = "INSERT INTO empleados (dpi, nombre_empleado, apellido_empleado, id_departamento, id_puesto)
                    VALUES (?, ?, ?, ?, ?)";
        $stmt_emp = $conn->prepare($sql_emp);
        $stmt_emp->bind_param("sssii", $dpi, $nombre, $apellido, $id_departamento, $id_puesto);

        if (!$stmt_emp->execute()) {
            throw new Exception("Error al registrar empleado: " . $stmt_emp->error);
        }

        $id_empleado = $stmt_emp->insert_id;
        $stmt_emp->close();

        // 🔐 Encriptar contraseña y registrar usuario
        $hash = password_hash($contrasenia, PASSWORD_BCRYPT);

        $sql_user = "INSERT INTO usuarios (id_empleado, usuario, contrasenia_hash, activo)
                     VALUES (?, ?, ?, 1)";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param("iss", $id_empleado, $usuario, $hash);

        if (!$stmt_user->execute()) {
            throw new Exception("Error al registrar usuario: " . $stmt_user->error);
        }

        $stmt_user->close();
        desconectar($conn);

        header("Location: crear_usuario.php?success=Cuenta creada exitosamente. Ya puedes iniciar sesión.");
        exit();

    } catch (Exception $e) {
        header("Location: crear_usuario.php?error=" . urlencode("Error: " . $e->getMessage()));
        exit();
    }

} else {
    header("Location: crear_usuario.php");
    exit();
}
?>
