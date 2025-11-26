<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: POST");
require 'db.php';

$data = json_decode(file_get_contents("php://input"));

if(isset($data->id) && isset($data->status)) {
    try {
        // 1. Si hay nota de seguimiento, primero obtenemos el historial actual
        if (!empty($data->note)) {
            $stmtGet = $pdo->prepare("SELECT history FROM incidents WHERE id = ?");
            $stmtGet->execute([$data->id]);
            $currentHistory = $stmtGet->fetchColumn();
            
            $historyArray = $currentHistory ? json_decode($currentHistory) : [];
            if (!is_array($historyArray)) $historyArray = [];
            
            // Agregar nueva nota al inicio con fecha
            $timestamp = date('Y-m-d H:i');
            array_unshift($historyArray, "$timestamp - $data->note");
            
            // Guardar estatus Y historial nuevo
            $sql = "UPDATE incidents SET status = ?, history = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$data->status, json_encode($historyArray), $data->id]);
        } else {
            // 2. Si solo es cambio de estatus sin nota
            $sql = "UPDATE incidents SET status = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$data->status, $data->id]);
        }

        echo json_encode(['success' => true]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>