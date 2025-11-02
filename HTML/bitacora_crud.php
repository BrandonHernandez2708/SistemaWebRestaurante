<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once("conexion.php");
require_once("funciones_globales.php");
if (session_status() === PHP_SESSION_NONE) session_start();

$conexion = conectar();
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
header('Content-Type: application/json; charset=utf-8');

switch ($accion) {
    // Listar usuarios para el combo
    case 'usuarios':
        $resultado = $conexion->query("SELECT id_usuario, usuario FROM usuarios ORDER BY usuario ASC");
        $data = [];
        while ($fila = $resultado->fetch_assoc()) $data[] = $fila;
        echo json_encode(['status' => 'ok', 'data' => $data]);
        break;

    // Listar bitácora con filtros
    case 'listar':
        $usuario = $_POST['usuario'] ?? '';
        $fecha_inicio = $_POST['fecha_inicio'] ?? '';
        $fecha_fin = $_POST['fecha_fin'] ?? '';

        $filtros = [];
        if (!empty($usuario)) $filtros[] = "u.usuario LIKE '%" . $conexion->real_escape_string($usuario) . "%'";
        if (!empty($fecha_inicio) && !empty($fecha_fin))
            $filtros[] = "DATE(b.fecha_hora_accion) BETWEEN '" . $conexion->real_escape_string($fecha_inicio) . "' 
                          AND '" . $conexion->real_escape_string($fecha_fin) . "'";
        elseif (!empty($fecha_inicio))
            $filtros[] = "DATE(b.fecha_hora_accion) >= '" . $conexion->real_escape_string($fecha_inicio) . "'";
        elseif (!empty($fecha_fin))
            $filtros[] = "DATE(b.fecha_hora_accion) <= '" . $conexion->real_escape_string($fecha_fin) . "'";

        $where = count($filtros) ? "WHERE " . implode(" AND ", $filtros) : "";

        $sql = "SELECT 
                    b.id_bitacora,
                    COALESCE(u.usuario, 'Desconocido') AS usuario,
                    b.ip, b.pc, b.operacion_realizada, b.fecha_hora_accion
                FROM bitacora b
                LEFT JOIN usuarios u ON b.id_usuario = u.id_usuario
                $where
                ORDER BY b.id_bitacora DESC";

        $resultado = $conexion->query($sql);
        $data = [];
        while ($fila = $resultado->fetch_assoc()) $data[] = $fila;
        echo json_encode(['status' => 'ok', 'data' => $data]);
        break;

    default:
        echo json_encode(['status' => 'error', 'msg' => 'Acción no válida']);
        break;
}

desconectar($conexion);
