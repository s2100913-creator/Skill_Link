<?php
header('Content-Type: application/json');
require __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Use POST']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$name = trim($data['name'] ?? '');

if ($name === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'name is required']);
    exit;
}

$stmt = $pdo->prepare('INSERT INTO categories (name) VALUES (:n) RETURNING id');
$stmt->execute([':n' => $name]);

http_response_code(201);
echo json_encode(['success' => true, 'message' => 'Category created', 'category_id' => $stmt->fetchColumn()]);