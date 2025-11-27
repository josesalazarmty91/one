<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require 'db.php';

$userId = isset($_GET['user_id']) ? $_GET['user_id'] : 0;
$userRole = isset($_GET['role']) ? $_GET['role'] : 'creator';
$filterMode = isset($_GET['filter_mode']) ? $_GET['filter_mode'] : 'default';

try {
    // Consulta con JOINs para obtener nombres legibles
    // COALESCE asegura que si es null, devuelva un valor por defecto
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
            COALESCE(assigned.full_name, assigned.username) as assigned_username,
            COALESCE(creator.full_name, creator.username, 'Desconocido') as creator_username,
            creator.full_name as creator_fullname
        FROM incidents i
        LEFT JOIN units u ON i.unit_id = u.id
        LEFT JOIN companies c ON i.company_id = c.id
        LEFT JOIN incident_categories cat ON i.category_id = cat.id
        LEFT JOIN incident_subcategories sub ON i.subcategory_id = sub.id
        LEFT JOIN users_incident assigned ON i.assigned_to = assigned.id
        LEFT JOIN users_incident creator ON i.created_by = creator.id
        WHERE 1=1
    ";

    $params = [];

    // Lógica de filtrado
    if ($filterMode === 'created_by_me') {
        // Caso: "Mis Reportes" (Lo que yo reporté)
        $sql .= " AND i.created_by = ?";
        $params[] = $userId;
    } 
    elseif ($filterMode === 'assigned_to_me') {
        // Caso: "Mis Tareas" (Lo que me asignaron)
        $sql .= " AND i.assigned_to = ?";
        $params[] = $userId;
    }
    else {
        // Caso Default por Rol
        if ($userRole === 'admin') {
            // Admin ve todo, no se agrega filtro extra
        } elseif ($userRole === 'moderator') {
            // Moderador ve: Sin asignar O creados por él O asignados a él
            $sql .= " AND (i.assigned_to IS NULL OR i.created_by = ? OR i.assigned_to = ?)";
            $params[] = $userId;
            $params[] = $userId;
        } elseif ($userRole === 'assigned') {
            // Técnico ve: Asignados a él (principalmente)
            // Nota: Si entra a 'Mis Reportes', usará el modo 'created_by_me' arriba
            $sql .= " AND i.assigned_to = ?";
            $params[] = $userId;
        } else { // creator
            // Usuario normal solo ve los suyos
            $sql .= " AND i.created_by = ?";
            $params[] = $userId;
        }
    }

    $sql .= " ORDER BY i.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $incidents = $stmt->fetchAll();
    
    // Procesar historial JSON
    foreach ($incidents as &$ticket) {
        $ticket['history'] = !empty($ticket['history']) ? json_decode($ticket['history']) : [];
        
        // Asegurar que los campos críticos no sean null para el frontend
        if (!$ticket['creator_username']) $ticket['creator_username'] = 'Usuario Eliminado';
    }
    
    echo json_encode($incidents);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>