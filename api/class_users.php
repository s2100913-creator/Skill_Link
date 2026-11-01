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

$select = 'SELECT cu.id, cu.class_id, c.name AS class, cu.user_id, u.name AS member
           FROM class_users cu
           JOIN classes c ON c.id = cu.class_id
           JOIN users u ON u.id = cu.user_id';

if ($method === 'GET' && $id === 0) {
    // optional filter: class_users.php?class_id=1
    $class_id = (int)($_GET['class_id'] ?? 0);
    if ($class_id) {
        $stmt = $pdo->prepare($select . ' WHERE cu.class_id = :c ORDER BY cu.id');
        $stmt->execute([':c' => $class_id]);
        $rows = $stmt->fetchAll();
    } else {
        $rows = $pdo->query($select . ' ORDER BY cu.id')->fetchAll();
    }
    echo json_encode(['success' => true, 'class_users' => $rows]);

} elseif ($method === 'GET') {
    $stmt = $pdo->prepare($select . ' WHERE cu.id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) fail(404, 'Record not found');
    echo json_encode(['success' => true, 'class_user' => $row]);

} elseif ($method === 'POST') {
    $class_id = (int)($data['class_id'] ?? 0);
    $user_id  = (int)($data['user_id'] ?? 0);
    if ($class_id === 0 || $user_id === 0) fail(400, 'class_id and user_id are required');

    $check = $pdo->prepare('SELECT 1 FROM class_users WHERE class_id = :c AND user_id = :u');
    $check->execute([':c' => $class_id, ':u' => $user_id]);
    if ($check->fetch()) fail(409, 'User already joined this class');

    try {
        $stmt = $pdo->prepare('INSERT INTO class_users (class_id, user_id) VALUES (:c, :u) RETURNING id');
        $stmt->execute([':c' => $class_id, ':u' => $user_id]);
    } catch (PDOException $e) {
        fail(400, 'Invalid class_id or user_id');
    }
    http_response_code(201);
    echo json_encode(['success' => true, 'message' => 'Joined class', 'class_user_id' => $stmt->fetchColumn()]);

} elseif ($method === 'DELETE') {
    if ($id === 0) fail(400, 'id is required');
    $stmt = $pdo->prepare('DELETE FROM class_users WHERE id = :id RETURNING id');
    $stmt->execute([':id' => $id]);
    if (!$stmt->fetch()) fail(404, 'Record not found');
    echo json_encode(['success' => true, 'message' => 'Left class']);

} else {
    fail(405, 'Method not allowed');
}