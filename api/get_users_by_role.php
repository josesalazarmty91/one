<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require 'db.php';

// Devuelve lista de usuarios "assigned" para que el moderador elija
$stmt = $pdo->query("SELECT id, username FROM portal_users WHERE role = 'assigned'");
echo json_encode($stmt->fetchAll());
?>