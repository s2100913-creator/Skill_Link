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

// Never select the password column
$cols = 'id, name, username, email, phone_number, is_admin';

if ($method === 'GET' && $id === 0) {
    $rows = $pdo->query("SELECT $cols FROM users ORDER BY id")->fetchAll();
    echo json_encode(['success' => true, 'users' => $rows]);

} elseif ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT $cols FROM users WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) fail(404, 'User not found');
    echo json_encode(['success' => true, 'user' => $row]);

} elseif ($method === 'PATCH') {
    if ($id === 0) fail(400, 'id is required');
    $name     = isset($data['name']) ? trim($data['name']) : null;
    $username = isset($data['username']) ? trim($data['username']) : null;
    $email    = isset($data['email']) ? trim($data['email']) : null;
    $phone    = isset($data['phone_number']) ? trim($data['phone_number']) : null;
    $password = null;

    if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) fail(400, 'Invalid email');
    if (isset($data['password'])) {
        if (strlen($data['password']) < 6) fail(400, 'Password must be at least 6 characters');
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
    }

    try {
        $stmt = $pdo->prepare('UPDATE users
                               SET name = COALESCE(:n, name),
                                   username = COALESCE(:u, username),
                                   email = COALESCE(:e, email),
                                   phone_number = COALESCE(:p, phone_number),
                                   password = COALESCE(:pw, password)
                               WHERE id = :id RETURNING id');
        $stmt->execute([':n' => $name, ':u' => $username, ':e' => $email,
                        ':p' => $phone, ':pw' => $password, ':id' => $id]);
    } catch (PDOException $e) {
        fail(409, 'Username or email already exists');
    }
    if (!$stmt->fetch()) fail(404, 'User not found');
    echo json_encode(['success' => true, 'message' => 'User updated']);

} elseif ($method === 'DELETE') {
    if ($id === 0) fail(400, 'id is required');
    try {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id RETURNING id');
        $stmt->execute([':id' => $id]);
    } catch (PDOException $e) {
        fail(409, 'User still has skills, classes or attendance records.');
    }
    if (!$stmt->fetch()) fail(404, 'User not found');
    echo json_encode(['success' => true, 'message' => 'User deleted']);

} else {
    fail(405, 'Use Register (register.php) to create users');
}