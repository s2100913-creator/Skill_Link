<?php
header('Content-Type: application/json');
require __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Use PATCH']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$name = trim($data['name'] ?? '');

if ($name === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'name is required']);
    exit;
}

$stmt = $pdo->prepare('UPDATE categories SET name = :n WHERE id = :id RETURNING id');
$stmt->execute([':n' => $name, ':id' => (int)($_GET['id'] ?? 0)]);

if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Category not found']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Category updated']);