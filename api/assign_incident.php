<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: POST");
require 'db.php';

$data = json_decode(file_get_contents("php://input"));

if(isset($data->incident_id) && isset($data->assigned_to)) {
    try {
        $sql = "UPDATE incidents SET assigned_to = ?, status = 'en_proceso' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data->assigned_to, $data->incident_id]);
        echo json_encode(['success' => true]);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>