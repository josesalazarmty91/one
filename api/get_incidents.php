<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require 'db.php';

try {
    // Consulta uniendo tablas para obtener nombres en lugar de solo IDs
    $sql = "
        SELECT 
            i.*, 
            u.unit_number as unitNumber, 
            c.name as companyName, 
            o.name as operatorName
        FROM incidents i
        LEFT JOIN units u ON i.unit_id = u.id
        LEFT JOIN companies c ON i.company_id = c.id
        LEFT JOIN operators o ON i.operator_id = o.id
        ORDER BY i.created_at DESC
    ";
    
    $stmt = $pdo->query($sql);
    $incidents = $stmt->fetchAll();
    
    // Decodificar el historial si existe (para que React lo lea como Array)
    foreach ($incidents as &$ticket) {
        if (!empty($ticket['history'])) {
            $decoded = json_decode($ticket['history']);
            // Si es JSON válido lo usamos, si no, lo dejamos como array vacío
            $ticket['history'] = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        } else {
            $ticket['history'] = [];
        }
    }
    
    echo json_encode($incidents);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>