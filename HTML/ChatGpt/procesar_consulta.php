<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../../config/openai.php';

// Leer entrada JSON desde fetch
$input = json_decode(file_get_contents('php://input'), true);
$consulta = trim($input['consulta'] ?? '');

if (empty($consulta)) {
    echo json_encode(["status" => "error", "message" => "No se recibió ninguna consulta."]);
    exit;
}

// Cargar estructura de la base de datos
$contextoBD = file_get_contents('../../BD/marea_roja_db_structure.sql');

// 👇 Aquí llamamos directamente a la función que ya devuelve JSON
echo consultarChatGPT($consulta, $contextoBD);
