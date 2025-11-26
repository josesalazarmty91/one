<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require 'db.php';

try {
    // Obtener Categorías
    $stmt = $pdo->query("SELECT * FROM incident_categories WHERE active = 1");
    $categories = $stmt->fetchAll();

    $catalog = [];

    foreach ($categories as $cat) {
        // Obtener sus subcategorías
        $stmtSub = $pdo->prepare("SELECT id, label, icon_emoji as icon FROM incident_subcategories WHERE category_id = ? AND active = 1");
        $stmtSub->execute([$cat['id']]);
        $subs = $stmtSub->fetchAll();

        $catalog[] = [
            'id' => $cat['id'], // Usamos el ID numérico real de la BD
            'label' => $cat['label'],
            'icon_name' => $cat['icon_name'], // Enviamos el nombre para mapearlo en React
            'color' => $cat['color_class'],
            'subcategories' => $subs
        ];
    }

    echo json_encode($catalog);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>