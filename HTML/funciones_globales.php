<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function registrarBitacora($conexion, $tabla, $accion, $detalle = '')
{
    try {
        $id_usuario = $_SESSION['id_usuario'] ?? 0;

        //obtener IP real o pública
        $ip = obtenerIpCliente();

        //nombre de PC
        $pc = gethostname();

        //hora local 
        date_default_timezone_set('America/Guatemala');
        $fecha_hora = date('Y-m-d H:i:s');

        //descripción de la acción
        $operacion = ucfirst($accion) . " en tabla '$tabla'";
        if ($detalle) {
            $operacion .= " = " . $detalle;
        }

        //iInsertar registro
        $stmt = $conexion->prepare("
            INSERT INTO bitacora (id_usuario, ip, pc, operacion_realizada, fecha_hora_accion)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issss", $id_usuario, $ip, $pc, $operacion, $fecha_hora);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error al registrar bitácora: " . $e->getMessage());
    }
}

  //Obtiene la IP real o pública del cliente, incluso en entorno local.
function obtenerIpCliente()
{
    //1 Revisar cabeceras posibles (si viene detrás de proxy o router)
    $keys = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];

    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $ip;
            }
        }
    }

    //si sigue siendo local (::1 o 127.0.0.1), obtener IP pública real
    $localIps = ['::1', '127.0.0.1'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';

    if (in_array($ip, $localIps)) {
        $publicIp = obtenerIpPublica();
        if ($publicIp) return $publicIp;
        return '127.0.0.1';
    }

    // 3️⃣ Último recurso
    return $ip ?: '127.0.0.1';
}


 //Usa una API externa para obtener la IP pública real (si hay internet).
function obtenerIpPublica()
{
    $apis = [
        'https://api.ipify.org',
        'https://ifconfig.me/ip',
        'https://checkip.amazonaws.com'
    ];

    foreach ($apis as $url) {
        $publicIp = @file_get_contents($url);
        if ($publicIp && filter_var(trim($publicIp), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return trim($publicIp);
        }
    }

    return null;
}
?>
