<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require 'db.php';

// Recibir ID y Rol del usuario que solicita
$userId = isset($_GET['user_id']) ? $_GET['user_id'] : 0;
$userRole = isset($_GET['role']) ? $_GET['role'] : 'creator';

try {
    $sql = "
        SELECT 
            i.*, 
            u.unit_number, 
            c.name as companyName,
            cat.label as category_label,
            cat.icon_name as category_icon,
            cat.color_class as category_color,
            sub.label as subcategory_label,
            sub.icon_emoji as subcategory_icon,
            assigned.username as assigned_username
        FROM incidents i
        LEFT JOIN units u ON i.unit_id = u.id
        LEFT JOIN companies c ON i.company_id = c.id
        LEFT JOIN incident_categories cat ON i.category_id = cat.id
        LEFT JOIN incident_subcategories sub ON i.subcategory_id = sub.id
        LEFT JOIN portal_users assigned ON i.assigned_to = assigned.id
        WHERE 1=1
    ";

    // FILTROS SEGÚN ROL
    if ($userRole === 'admin') {
        // Ve todo, no agregamos filtro WHERE
    } 
    elseif ($userRole === 'moderator') {
        // Ve los sin asignar (para asignarlos) Y los que él creó o le asignaron (si aplica)
        $sql .= " AND (i.assigned_to IS NULL OR i.created_by = ? OR i.assigned_to = ?)";
    } 
    elseif ($userRole === 'assigned') {
        // Ve los asignados a él Y los que él creó
        $sql .= " AND (i.assigned_to = ? OR i.created_by = ?)";
    } 
    else { // 'creator' (default)
        // Solo ve los suyos
        $sql .= " AND i.created_by = ?";
    }

    $sql .= " ORDER BY i.created_at DESC";

    $stmt = $pdo->prepare($sql);

    // Bindear parámetros según el rol
    if ($userRole === 'admin') {
        $stmt->execute();
    } elseif ($userRole === 'moderator') {
        $stmt->execute([$userId, $userId]);
    } elseif ($userRole === 'assigned') {
        $stmt->execute([$userId, $userId]);
    } else {
        $stmt->execute([$userId]);
    }
    
    $incidents = $stmt->fetchAll();
    
    // Decodificar historial... (igual que antes)
    foreach ($incidents as &$ticket) {
        $ticket['history'] = !empty($ticket['history']) ? json_decode($ticket['history']) : [];
    }
    
    echo json_encode($incidents);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>