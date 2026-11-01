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

$select = 'SELECT c.id, c.user_skill_id, s.name AS skill, c.name,
                  c.schedule, c.start_date, c.end_date
           FROM classes c
           JOIN user_skills s ON s.id = c.user_skill_id';

if ($method === 'GET' && $id === 0) {
    $rows = $pdo->query($select . ' ORDER BY c.id')->fetchAll();
    echo json_encode(['success' => true, 'classes' => $rows]);

} elseif ($method === 'GET') {
    $stmt = $pdo->prepare($select . ' WHERE c.id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) fail(404, 'Class not found');
    echo json_encode(['success' => true, 'class' => $row]);

} elseif ($method === 'POST') {
    $user_skill_id = (int)($data['user_skill_id'] ?? 0);
    $name       = trim($data['name'] ?? '');
    $schedule   = $data['schedule'] ?? null;
    $start_date = $data['start_date'] ?? null;
    $end_date   = $data['end_date'] ?? null;

    if ($user_skill_id === 0 || $name === '') fail(400, 'user_skill_id and name are required');
    if ($start_date && $end_date && $end_date < $start_date) fail(400, 'end_date cannot be before start_date');

    try {
        $stmt = $pdo->prepare('INSERT INTO classes (user_skill_id, name, schedule, start_date, end_date)
                               VALUES (:s, :n, :sc, :sd, :ed) RETURNING id');
        $stmt->execute([':s' => $user_skill_id, ':n' => $name, ':sc' => $schedule,
                        ':sd' => $start_date, ':ed' => $end_date]);
    } catch (PDOException $e) {
        fail(400, 'Invalid user_skill_id or date format');
    }
    http_response_code(201);
    echo json_encode(['success' => true, 'message' => 'Class created', 'class_id' => $stmt->fetchColumn()]);

} elseif ($method === 'PATCH') {
    if ($id === 0) fail(400, 'id is required');
    $name       = isset($data['name']) ? trim($data['name']) : null;
    $schedule   = $data['schedule'] ?? null;
    $start_date = $data['start_date'] ?? null;
    $end_date   = $data['end_date'] ?? null;
    if ($start_date && $end_date && $end_date < $start_date) fail(400, 'end_date cannot be before start_date');

    try {
        $stmt = $pdo->prepare('UPDATE classes
                               SET name = COALESCE(:n, name),
                                   schedule = COALESCE(:sc, schedule),
                                   start_date = COALESCE(:sd, start_date),
                                   end_date = COALESCE(:ed, end_date)
                               WHERE id = :id RETURNING id');
        $stmt->execute([':n' => $name, ':sc' => $schedule, ':sd' => $start_date,
                        ':ed' => $end_date, ':id' => $id]);
    } catch (PDOException $e) {
        fail(400, 'Invalid date format');
    }
    if (!$stmt->fetch()) fail(404, 'Class not found');
    echo json_encode(['success' => true, 'message' => 'Class updated']);

} elseif ($method === 'DELETE') {
    if ($id === 0) fail(400, 'id is required');
    try {
        $stmt = $pdo->prepare('DELETE FROM classes WHERE id = :id RETURNING id');
        $stmt->execute([':id' => $id]);
    } catch (PDOException $e) {
        fail(409, 'Class still has members or attendance records.');
    }
    if (!$stmt->fetch()) fail(404, 'Class not found');
    echo json_encode(['success' => true, 'message' => 'Class deleted']);

} else {
    fail(405, 'Method not allowed');
}