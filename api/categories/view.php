<?php
header('Content-Type: application/json');
require __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Use GET']);
    exit;
}

$stmt = $pdo->prepare('SELECT id, name FROM categories WHERE id = :id');
$stmt->execute([':id' => (int)($_GET['id'] ?? 0)]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Category not found']);
    exit;
}

echo json_encode(['success' => true, 'category' => $row]);