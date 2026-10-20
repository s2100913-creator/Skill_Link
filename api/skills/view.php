<?php
header('Content-Type: application/json');
require __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Use GET']);
    exit;
}

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT s.id, s.name, s.description, s.user_id, u.name AS owner,
            s.category_id, c.name AS category
     FROM user_skills s
     JOIN users u ON u.id = s.user_id
     LEFT JOIN categories c ON c.id = s.category_id
     WHERE s.id = :id'
);
$stmt->execute([':id' => $id]);
$skill = $stmt->fetch();

if (!$skill) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Skill not found']);
    exit;
}

echo json_encode(['success' => true, 'skill' => $skill]);