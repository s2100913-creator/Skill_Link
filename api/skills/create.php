<?php
header('Content-Type: application/json');
require __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Use POST']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];

$user_id     = (int)($data['user_id'] ?? 0);
$name        = trim($data['name'] ?? '');
$description = trim($data['description'] ?? '');
$category_id = isset($data['category_id']) ? (int)$data['category_id'] : null;

if ($user_id === 0 || $name === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'user_id and name are required']);
    exit;
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO user_skills (user_id, name, description, category_id)
         VALUES (:u, :n, :d, :c) RETURNING id'
    );
    $stmt->execute([':u' => $user_id, ':n' => $name, ':d' => $description, ':c' => $category_id]);
} catch (PDOException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user_id or category_id']);
    exit;
}

http_response_code(201);
echo json_encode(['success' => true, 'message' => 'Skill created', 'skill_id' => $stmt->fetchColumn()]);