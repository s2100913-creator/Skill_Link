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

$select = 'SELECT cu.id, cu.class_id, c.name AS class, c.schedule, c.start_date, c.end_date,
                  s.name AS skill, o.name AS owner,
                  cu.user_id, u.name AS member
           FROM class_users cu
           JOIN classes c ON c.id = cu.class_id
           JOIN user_skills s ON s.id = c.user_skill_id
           JOIN users o ON o.id = s.user_id
           JOIN users u ON u.id = cu.user_id';

if ($method === 'GET' && $id === 0) {
    // optional filters: ?class_id=1 and/or ?user_id=2
    $where = []; $params = [];
    if (!empty($_GET['class_id'])) { $where[] = 'cu.class_id = :c'; $params[':c'] = (int)$_GET['class_id']; }
    if (!empty($_GET['user_id']))  { $where[] = 'cu.user_id = :u';  $params[':u'] = (int)$_GET['user_id']; }
    $sql = $select . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY cu.id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true, 'class_users' => $stmt->fetchAll()]);

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