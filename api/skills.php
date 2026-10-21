<?php
header('Content-Type: application/json');
require __DIR__ . '/../lib/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = (int)($_GET['id'] ?? 0);
$data = json_decode(file_get_contents('php://input'), true) ?? [];

function fail($code, $msg) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

$select = 'SELECT s.id, s.name, s.description, s.user_id, u.name AS owner,
                  s.category_id, c.name AS category
           FROM user_skills s
           JOIN users u ON u.id = s.user_id
           LEFT JOIN categories c ON c.id = s.category_id';

if ($method === 'GET' && $id === 0) {
    $rows = $pdo->query($select . ' ORDER BY s.id')->fetchAll();
    echo json_encode(['success' => true, 'skills' => $rows]);

} elseif ($method === 'GET') {
    $stmt = $pdo->prepare($select . ' WHERE s.id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) fail(404, 'Skill not found');
    echo json_encode(['success' => true, 'skill' => $row]);

} elseif ($method === 'POST') {
    $user_id = (int)($data['user_id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $category_id = isset($data['category_id']) ? (int)$data['category_id'] : null;
    if ($user_id === 0 || $name === '') fail(400, 'user_id and name are required');
    try {
        $stmt = $pdo->prepare('INSERT INTO user_skills (user_id, name, description, category_id)
                               VALUES (:u, :n, :d, :c) RETURNING id');
        $stmt->execute([':u' => $user_id, ':n' => $name, ':d' => $description, ':c' => $category_id]);
    } catch (PDOException $e) {
        fail(400, 'Invalid user_id or category_id');
    }
    http_response_code(201);
    echo json_encode(['success' => true, 'message' => 'Skill created', 'skill_id' => $stmt->fetchColumn()]);

} elseif ($method === 'PATCH') {
    $name = isset($data['name']) ? trim($data['name']) : null;
    $description = isset($data['description']) ? trim($data['description']) : null;
    $category_id = isset($data['category_id']) ? (int)$data['category_id'] : null;
    try {
        $stmt = $pdo->prepare('UPDATE user_skills
                               SET name = COALESCE(:n, name),
                                   description = COALESCE(:d, description),
                                   category_id = COALESCE(:c, category_id)
                               WHERE id = :id RETURNING id');
        $stmt->execute([':n' => $name, ':d' => $description, ':c' => $category_id, ':id' => $id]);
    } catch (PDOException $e) {
        fail(400, 'Invalid category_id');
    }
    if (!$stmt->fetch()) fail(404, 'Skill not found');
    echo json_encode(['success' => true, 'message' => 'Skill updated']);

} elseif ($method === 'DELETE') {
    try {
        $stmt = $pdo->prepare('DELETE FROM user_skills WHERE id = :id RETURNING id');
        $stmt->execute([':id' => $id]);
    } catch (PDOException $e) {
        fail(409, 'Skill still has classes. Delete them first.');
    }
    if (!$stmt->fetch()) fail(404, 'Skill not found');
    echo json_encode(['success' => true, 'message' => 'Skill deleted']);

} else {
    fail(405, 'Method not allowed');
}