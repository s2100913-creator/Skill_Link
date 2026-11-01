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

// PDO turns PHP false into an empty string, so send 'true'/'false' text
function to_bool($v) {
    if ($v === null) return null;
    return filter_var($v, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
}

$select = 'SELECT a.id, a.class_id, c.name AS class, a.user_id, u.name AS student,
                  a.has_attended, a.datetime
           FROM class_attendances a
           JOIN classes c ON c.id = a.class_id
           JOIN users u ON u.id = a.user_id';

if ($method === 'GET' && $id === 0) {
    // optional filters: ?class_id=1  and/or  ?user_id=2
    $where = []; $params = [];
    if (!empty($_GET['class_id'])) { $where[] = 'a.class_id = :c'; $params[':c'] = (int)$_GET['class_id']; }
    if (!empty($_GET['user_id']))  { $where[] = 'a.user_id = :u';  $params[':u'] = (int)$_GET['user_id']; }
    $sql = $select . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY a.id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true, 'attendances' => $stmt->fetchAll()]);

} elseif ($method === 'GET') {
    $stmt = $pdo->prepare($select . ' WHERE a.id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) fail(404, 'Attendance not found');
    echo json_encode(['success' => true, 'attendance' => $row]);

} elseif ($method === 'POST') {
    $class_id = (int)($data['class_id'] ?? 0);
    $user_id  = (int)($data['user_id'] ?? 0);
    $has_attended = to_bool($data['has_attended'] ?? true);
    $datetime = $data['datetime'] ?? date('Y-m-d H:i:s');
    if ($class_id === 0 || $user_id === 0) fail(400, 'class_id and user_id are required');

    // only members of the class can have attendance
    $member = $pdo->prepare('SELECT 1 FROM class_users WHERE class_id = :c AND user_id = :u');
    $member->execute([':c' => $class_id, ':u' => $user_id]);
    if (!$member->fetch()) fail(400, 'User has not joined this class');

    try {
        $stmt = $pdo->prepare('INSERT INTO class_attendances (class_id, user_id, has_attended, datetime)
                               VALUES (:c, :u, CAST(:h AS BOOLEAN), :d) RETURNING id');
        $stmt->execute([':c' => $class_id, ':u' => $user_id, ':h' => $has_attended, ':d' => $datetime]);
    } catch (PDOException $e) {
        fail(400, 'Invalid data or datetime format');
    }
    http_response_code(201);
    echo json_encode(['success' => true, 'message' => 'Attendance recorded', 'attendance_id' => $stmt->fetchColumn()]);

} elseif ($method === 'PATCH') {
    if ($id === 0) fail(400, 'id is required');
    $has_attended = array_key_exists('has_attended', $data) ? to_bool($data['has_attended']) : null;
    $datetime = $data['datetime'] ?? null;
    try {
        $stmt = $pdo->prepare('UPDATE class_attendances
                               SET has_attended = COALESCE(CAST(:h AS BOOLEAN), has_attended),
                                   datetime = COALESCE(CAST(:d AS TIMESTAMP), datetime)
                               WHERE id = :id RETURNING id');
        $stmt->execute([':h' => $has_attended, ':d' => $datetime, ':id' => $id]);
    } catch (PDOException $e) {
        fail(400, 'Invalid datetime format');
    }
    if (!$stmt->fetch()) fail(404, 'Attendance not found');
    echo json_encode(['success' => true, 'message' => 'Attendance updated']);

} elseif ($method === 'DELETE') {
    if ($id === 0) fail(400, 'id is required');
    $stmt = $pdo->prepare('DELETE FROM class_attendances WHERE id = :id RETURNING id');
    $stmt->execute([':id' => $id]);
    if (!$stmt->fetch()) fail(404, 'Attendance not found');
    echo json_encode(['success' => true, 'message' => 'Attendance deleted']);

} else {
    fail(405, 'Method not allowed');
}