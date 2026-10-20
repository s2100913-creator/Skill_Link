<?php
header('Content-Type: application/json');
require __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Use DELETE']);
    exit;
}

$id = (int)($_GET['id'] ?? 0);

try {
    $stmt = $pdo->prepare('DELETE FROM user_skills WHERE id = :id RETURNING id');
    $stmt->execute([':id' => $id]);
} catch (PDOException $e) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Skill still has classes. Delete them first.']);
    exit;
}

if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Skill not found']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Skill deleted']);