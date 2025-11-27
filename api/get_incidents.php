<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require 'db.php';

$userId = isset($_GET['user_id']) ? $_GET['user_id'] : 0;
$userRole = isset($_GET['role']) ? $_GET['role'] : 'creator';
$filterMode = isset($_GET['filter_mode']) ? $_GET['filter_mode'] : 'default';

try {
    // Consulta con JOINs para obtener nombres legibles
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
            assigned.username as assigned_username,
            creator.username as creator_username,      -- Nombre de usuario del creador
            creator.full_name as creator_fullname      -- Nombre completo del creador
        FROM incidents i
        LEFT JOIN units u ON i.unit_id = u.id
        LEFT JOIN companies c ON i.company_id = c.id
        LEFT JOIN incident_categories cat ON i.category_id = cat.id
        LEFT JOIN incident_subcategories sub ON i.subcategory_id = sub.id
        LEFT JOIN users_incident assigned ON i.assigned_to = assigned.id
        LEFT JOIN users_incident creator ON i.created_by = creator.id -- JOIN para el creador
        WHERE 1=1
    ";

    $params = [];

    // Lógica de filtrado (igual que antes)
    if ($filterMode === 'created_by_me') {
        $sql .= " AND i.created_by = ?";
        $params[] = $userId;
    } 
    elseif ($filterMode === 'assigned_to_me') {
        $sql .= " AND i.assigned_to = ?";
        $params[] = $userId;
    }
    else {
        if ($userRole === 'admin') {
            // Ve todo
        } elseif ($userRole === 'moderator') {
            $sql .= " AND (i.assigned_to IS NULL OR i.created_by = ? OR i.assigned_to = ?)";
            $params[] = $userId;
            $params[] = $userId;
        } elseif ($userRole === 'assigned') {
            $sql .= " AND i.assigned_to = ?";
            $params[] = $userId;
        } else { // creator
            $sql .= " AND i.created_by = ?";
            $params[] = $userId;
        }
    }

    $sql .= " ORDER BY i.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $incidents = $stmt->fetchAll();
    
    foreach ($incidents as &$ticket) {
        $ticket['history'] = !empty($ticket['history']) ? json_decode($ticket['history']) : [];
    }
    
    echo json_encode($incidents);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>