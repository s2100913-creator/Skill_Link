<?php
header('Content-Type: application/json');
require __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Use PATCH']);
    exit;
}

$id   = (int)($_GET['id'] ?? 0);
$data = json_decode(file_get_contents('php://input'), true) ?? [];

$name        = isset($data['name']) ? trim($data['name']) : null;
$description = isset($data['description']) ? trim($data['description']) : null;
$category_id = isset($data['category_id']) ? (int)$data['category_id'] : null;

try {
    $stmt = $pdo->prepare(
        'UPDATE user_skills
         SET name = COALESCE(:n, name),
             description = COALESCE(:d, description),
             category_id = COALESCE(:c, category_id)
         WHERE id = :id RETURNING id'
    );
    $stmt->execute([':n' => $name, ':d' => $description, ':c' => $category_id, ':id' => $id]);
} catch (PDOException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid category_id']);
    exit;
}

if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Skill not found']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Skill updated']);