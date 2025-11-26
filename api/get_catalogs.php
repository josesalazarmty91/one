<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
require 'db.php';

try {
    // 1. Obtener Unidades
    // CORRECCIÓN: Filtramos por 'estado_mantenimiento' que es la columna real en tu BD.
    // Solo traemos las unidades que están 'OK' para que aparezcan en el selector.
    $stmt = $pdo->query("
        SELECT 
            id, 
            unit_number, 
            company_id 
        FROM units 
        WHERE estado_mantenimiento = 'OK' OR estado_mantenimiento IS NULL
    ");
    $units = $stmt->fetchAll();

    // 2. Obtener Operadores
    // Aquí sí usamos 'status' porque la tabla 'operators' lo tiene (1 = Activo)
    $stmt = $pdo->query("SELECT id, name, company_id FROM operators WHERE status = 1");
    $operators = $stmt->fetchAll();

    // 3. Obtener Compañías
    $stmt = $pdo->query("SELECT id, name FROM companies");
    $companies = $stmt->fetchAll();

    echo json_encode([
        'units' => $units,
        'operators' => $operators,
        'companies' => $companies
    ]);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error SQL: ' . $e->getMessage()]);
}
?>