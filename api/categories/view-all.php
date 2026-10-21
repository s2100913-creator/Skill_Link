<?php
header('Content-Type: application/json');
require __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Use GET']);
    exit;
}

$rows = $pdo->query('SELECT id, name FROM categories ORDER BY id')->fetchAll();
echo json_encode(['success' => true, 'categories' => $rows]);