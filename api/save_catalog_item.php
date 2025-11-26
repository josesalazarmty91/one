<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: POST");
require 'db.php';

$data = json_decode(file_get_contents("php://input"));

try {
    if ($data->type === 'category') {
        $sql = "INSERT INTO incident_categories (label, icon_name, color_class) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data->label, $data->icon, 'bg-blue-50 text-[#0b4d9a] border-[#0b4d9a]']); // Color por defecto
    } elseif ($data->type === 'subcategory') {
        $sql = "INSERT INTO incident_subcategories (category_id, label, icon_emoji) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data->parentId, $data->label, $data->icon]);
    }
    
    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>