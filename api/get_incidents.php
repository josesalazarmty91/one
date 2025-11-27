<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require 'db.php';

$userId = isset($_GET['user_id']) ? $_GET['user_id'] : 0;
$userRole = isset($_GET['role']) ? $_GET['role'] : 'creator';
$filterMode = isset($_GET['filter_mode']) ? $_GET['filter_mode'] : 'default';

try {
    // Consulta base con JOINs para obtener nombres legibles
    // Usamos COALESCE para evitar nulos en nombres de usuario
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

    // --- LÓGICA DE FILTRADO ESTRICTA ---

    if ($filterMode === 'created_by_me') {
        // MODO: Mis Reportes (Lo que yo creé)
        // Este filtro aplica para TODOS los roles cuando quieren ver su historial personal
        $sql .= " AND i.created_by = ?";
        $params[] = $userId;
    } 
    elseif ($filterMode === 'assigned_to_me') {
        // MODO: Mis Tareas (Lo que me asignaron)
        // Típicamente para Técnicos, pero un Moderador también podría auto-asignarse
        $sql .= " AND i.assigned_to = ?";
        $params[] = $userId;
    }
    else {
        // MODO: Default (Basado en Rol)
        // Si no se especifica un modo de filtro, aplicamos la lógica de negocio por rol
        
        if ($userRole === 'admin') {
            // Admin ve TODO. No se agregan filtros.
        } 
        elseif ($userRole === 'moderator') {
            // Moderador ve:
            // 1. Reportes sin asignar (para asignarlos)
            // 2. Reportes asignados a él mismo
            // 3. Reportes que él mismo creó
            // 4. Opcional: Podría ver todo si así se desea, pero esto mantiene su bandeja limpia
            $sql .= " AND (i.assigned_to IS NULL OR i.assigned_to = ? OR i.created_by = ?)";
            $params[] = $userId;
            $params[] = $userId;
        } 
        elseif ($userRole === 'assigned') {
            // Técnico (vista por defecto): Ve lo que le asignaron
            // Si quisiera ver lo que creó, usaría el modo 'created_by_me'
            $sql .= " AND i.assigned_to = ?";
            $params[] = $userId;
        } 
        else { 
            // Creator / Usuario estándar (vista por defecto): Solo ve lo suyo
            $sql .= " AND i.created_by = ?";
            $params[] = $userId;
        }
    }

    $sql .= " ORDER BY i.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $incidents = $stmt->fetchAll();
    
    // Procesamiento final de datos para el frontend
    foreach ($incidents as &$ticket) {
        $ticket['history'] = !empty($ticket['history']) ? json_decode($ticket['history']) : [];
        
        // Asegurar campos para evitar errores en JS
        if (!$ticket['creator_username']) $ticket['creator_username'] = 'Usuario Eliminado';
    }
    
    echo json_encode($incidents);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener reportes: ' . $e->getMessage()]);
}
?>