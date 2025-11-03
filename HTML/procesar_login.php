<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'conexion.php';
require_once 'funciones_globales.php';

function validarLogin($usuario, $contrasenia, $conn) {
    // Buscar el usuario por nombre de usuario
    $sql = "SELECT id_usuario, id_empleado, usuario, contrasenia_hash, activo 
            FROM usuarios 
            WHERE usuario = ? 
            LIMIT 1";

    if (!($stmt = $conn->prepare($sql))) {
        return false;
    }

    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows !== 1) {
        $stmt->close();
        return false;
    }

    $usuario_data = $resultado->fetch_assoc();

    // El campo activo siempre debe ser 1
    if (empty($usuario_data['activo']) || $usuario_data['activo'] != 1) {
        $stmt->close();
        return false;
    }

    $hash = $usuario_data['contrasenia_hash'];
    $password_ok = false;

    // Verificar hash seguro
    if (!empty($hash) && password_verify($contrasenia, $hash)) {
        $password_ok = true;
    } else {
        // Fallback para contraseñas antiguas
        if (!empty($hash) && (
            hash_equals($hash, $contrasenia) ||
            hash_equals($hash, md5($contrasenia)) ||
            hash_equals($hash, sha1($contrasenia)) ||
            hash_equals($hash, hash('sha256', $contrasenia))
        )) {
            $password_ok = true;
        }

        // Si fue un formato viejo, actualizar el hash
        if ($password_ok) {
            $newHash = password_hash($contrasenia, PASSWORD_DEFAULT);
            $updSql = "UPDATE usuarios SET contrasenia_hash = ? WHERE id_usuario = ?";
            if ($updStmt = $conn->prepare($updSql)) {
                $updStmt->bind_param("si", $newHash, $usuario_data['id_usuario']);
                $updStmt->execute();
                $updStmt->close();
            }
        }
    }

    if (!$password_ok) {
        $stmt->close();
        return false;
    }

    // Obtener nombre completo del empleado
    $id_empleado = $usuario_data['id_empleado'];
    $nombre_completo = '';
    if (!empty($id_empleado)) {
        // ✅ Campos corregidos según tu tabla empleados
        $sql2 = "SELECT nombre_empleado, apellido_empleado 
                 FROM empleados 
                 WHERE id_empleado = ? 
                 LIMIT 1";
        if ($stmt2 = $conn->prepare($sql2)) {
            $stmt2->bind_param("i", $id_empleado);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            if ($res2 && $res2->num_rows === 1) {
                $emp = $res2->fetch_assoc();
                $nombre_completo = trim($emp['nombre_empleado'] . ' ' . $emp['apellido_empleado']);
            }
            $stmt2->close();
        }
    }

    // Guardar id usuario, empleado y nombre usuario
    session_regenerate_id(true);
    $_SESSION['id_usuario'] = $usuario_data['id_usuario'];
    $_SESSION['id_empleado'] = $id_empleado;
    $_SESSION['usuario'] = $usuario_data['usuario'];
    if (!empty($nombre_completo)) {
        $_SESSION['nombre'] = $nombre_completo;
    }

    $stmt->close();
    return true;
}


// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario']);
    $contrasenia = $_POST['clave'];
    $conn = conectar();

    if (validarLogin($usuario, $contrasenia, $conn)) {
        if (isset($_SESSION['id_usuario'])) {
            registrarBitacora($conn, 'usuarios', 'login', 'Inicio de sesión exitoso');
        }
        header('Location: menu_empleados.php');
        exit();
    } else {
        registrarBitacora($conn, 'usuarios', 'login', "Intento fallido de inicio con usuario: $usuario");
        $error = "Usuario o contraseña incorrectos";
        header('Location: login.php?error=' . urlencode($error));
        exit();
    }

    desconectar($conn);
} else {
    header('Location: login.php');
    exit();
}
?>
