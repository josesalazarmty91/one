<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

require 'db.php';

// Obtener el método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Obtener datos del cuerpo de la petición (para POST, PUT, DELETE)
$input = json_decode(file_get_contents("php://input"), true);

// Si hay parámetros en la URL (ej: ?action=delete&id=5), los usamos también
$action = isset($_GET['action']) ? $_GET['action'] : null; 
// Nota: El 'action' es opcional si usamos métodos HTTP estrictos, pero útil para claridad o servidores limitados.

try {
    switch ($method) {
        case 'GET':
            handleGet($pdo);
            break;
        
        case 'POST':
            // POST se usa típicamente para CREAR, pero a veces se usa para todo.
            // Aquí asumimos CREAR por defecto, o UPDATE/DELETE si se especifica una acción en el body o URL.
            if ($action === 'update' || (isset($input['action']) && $input['action'] === 'update')) {
                handleUpdate($pdo, $input);
            } elseif ($action === 'delete' || (isset($input['action']) && $input['action'] === 'delete')) {
                handleDelete($pdo, $input);
            } else {
                handleCreate($pdo, $input);
            }
            break;

        case 'PUT':
            handleUpdate($pdo, $input);
            break;

        case 'DELETE':
            // A veces DELETE no permite body, así que mejor pasar ID por URL o usar POST con action
            // Si tu servidor soporta body en DELETE:
            handleDelete($pdo, $input);
            break;

        default:
            http_response_code(405); // Method Not Allowed
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// --- FUNCIONES HELPER PARA CADA ACCIÓN ---

function handleGet($pdo) {
    // LISTAR USUARIOS (Equivalente a get_users.php)
    $stmt = $pdo->query("SELECT id, username, role, full_name, created_at FROM users_incident ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
    echo json_encode($users);
}

function handleCreate($pdo, $data) {
    // CREAR USUARIO (Equivalente a create_user.php)
    if (!isset($data['username']) || !isset($data['password']) || !isset($data['role'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios (username, password, role)']);
        return;
    }

    // Verificar duplicados
    $checkStmt = $pdo->prepare("SELECT id FROM users_incident WHERE username = ?");
    $checkStmt->execute([$data['username']]);
    if ($checkStmt->rowCount() > 0) {
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'error' => 'El nombre de usuario ya existe']);
        return;
    }

    $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
    $sql = "INSERT INTO users_incident (username, password, role, full_name) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['username'], $hashedPassword, $data['role'], $data['full_name'] ?? '']);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'message' => 'Usuario creado']);
}

function handleUpdate($pdo, $data) {
    // ACTUALIZAR USUARIO (Equivalente a update_user.php)
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Se requiere ID para actualizar']);
        return;
    }

    $fields = [];
    $params = [];

    if (isset($data['role'])) {
        $fields[] = "role = ?";
        $params[] = $data['role'];
    }
    if (isset($data['full_name'])) {
        $fields[] = "full_name = ?";
        $params[] = $data['full_name'];
    }
    if (isset($data['password']) && !empty($data['password'])) {
        $fields[] = "password = ?";
        $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
    }

    if (empty($fields)) {
        echo json_encode(['success' => true, 'message' => 'Nada que actualizar']);
        return;
    }

    $params[] = $data['id']; // Para el WHERE
    $sql = "UPDATE users_incident SET " . implode(", ", $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['success' => true, 'message' => 'Usuario actualizado']);
}

function handleDelete($pdo, $data) {
    // ELIMINAR USUARIO (Equivalente a delete_user.php)
    // Puede venir el ID en el body ($data['id']) o en la URL ($_GET['id'])
    $id = $data['id'] ?? $_GET['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Se requiere ID para eliminar']);
        return;
    }

    if ($id == 1) {
        http_response_code(403); // Forbidden
        echo json_encode(['success' => false, 'error' => 'No se puede eliminar al administrador principal']);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM users_incident WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode(['success' => true, 'message' => 'Usuario eliminado']);
}
?>