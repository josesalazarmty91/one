<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: POST");
require 'db.php';

$data = json_decode(file_get_contents("php://input"));

if(isset($data->username) && isset($data->password)) {
    try {
        // 1. Buscar al usuario por su nombre
        $stmt = $pdo->prepare("SELECT id, username, role, password, full_name FROM users_incident WHERE username = ?");
        $stmt->execute([$data->username]);
        $user = $stmt->fetch();

        // 2. Verificar la contraseña usando el hash seguro
        if ($user && password_verify($data->password, $user['password'])) {
            // ¡Login exitoso!
            // Devolvemos los datos del usuario (menos la contraseña)
            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'full_name' => $user['full_name']
                ]
            ]);
        } else {
            // Usuario no encontrado o contraseña incorrecta
            echo json_encode(['success' => false, 'error' => 'Usuario o contraseña incorrectos']);
        }
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error de servidor']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Faltan datos']);
}
?>