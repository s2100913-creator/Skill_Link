<?php
header('Content-Type: application/json');
require __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Use DELETE']);
    exit;
}

try {
    $stmt = $pdo->prepare('DELETE FROM categories WHERE id = :id RETURNING id');
    $stmt->execute([':id' => (int)($_GET['id'] ?? 0)]);
} catch (PDOException $e) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Category is used by skills. Remove those first.']);
    exit;
}

if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Category not found']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Category deleted']);