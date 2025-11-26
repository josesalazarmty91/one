<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require 'db.php';

// Obtener datos del cuerpo de la petición
$json_input = file_get_contents("php://input");
$data = json_decode($json_input);

// LOG DE DEPURACIÓN (Opcional: guarda lo que llega en un archivo para ver si llegan datos)
// file_put_contents('debug_log.txt', $json_input . PHP_EOL, FILE_APPEND);

// Validar que se recibieron los datos mínimos necesarios
if(
    isset($data->unit_id) && 
    isset($data->company_id) && 
    isset($data->category_id) && 
    isset($data->subcategory_id)
) {
    try {
        // Preparamos la consulta SQL
        // Asegúrate que los nombres de las columnas coinciden EXACTAMENTE con tu tabla `incidents`
        $sql = "INSERT INTO incidents (
            unit_id, 
            operator_id, 
            company_id, 
            category_id, 
            subcategory_id, 
            priority, 
            notes, 
            status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'abierto', NOW())";

        $stmt = $pdo->prepare($sql);
        
        // Ejecutar la consulta
        $stmt->execute([
            $data->unit_id, 
            $data->operator_id ?? 0, // Si no hay operador, poner 0 o NULL
            $data->company_id, 
            $data->category_id,
            $data->subcategory_id,
            $data->priority ?? 'normal',
            $data->notes ?? ''
        ]);

        // Éxito
        echo json_encode([
            'success' => true, 
            'id' => $pdo->lastInsertId(),
            'message' => 'Reporte guardado correctamente'
        ]);

    } catch(PDOException $e) {
        // Error de Base de Datos
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => 'Error SQL: ' . $e->getMessage(),
            'sql_state' => $e->errorInfo
        ]);
    }
} else {
    // Error de Datos Faltantes
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => 'Datos incompletos. Se requiere unit_id, company_id, category_id y subcategory_id.',
        'received_data' => $data // Devuelve lo que recibió para que veas qué falta
    ]);
}
?>