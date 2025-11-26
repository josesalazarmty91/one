<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: POST");
require 'db.php';

$data = json_decode(file_get_contents("php://input"));

if(isset($data->username) && isset($data->password)) {
    // Buscar usuario
    $stmt = $pdo->prepare("SELECT id, username, role, password FROM portal_users WHERE username = ?");
    $stmt->execute([$data->username]);
    $user = $stmt->fetch();

    // Validar contraseña (Ajusta esto según cómo las guardes: texto plano o hash)
    // Opción A: Texto plano (SOLO PARA PRUEBAS)
    if ($user && $user['password'] == $data->password) {
    // Opción B: Hash (RECOMENDADO) -> if ($user && password_verify($data->password, $user['password'])) {
        
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Credenciales incorrectas']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Faltan datos']);
}
?>