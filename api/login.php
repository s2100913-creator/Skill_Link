
<?php
header('Content-Type: application/json');

require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Use POST'
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];

$login = trim($data['username'] ?? '');
$password = $data['password'] ?? '';

if ($login === '' || $password === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Username and password are required'
    ]);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT * FROM users WHERE username = :login OR email = :login'
);

$stmt->execute([':login' => $login]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Wrong username or password'
    ]);
    exit;
}

unset($user['password']);

echo json_encode([
    'success' => true,
    'message' => 'Login successful',
    'user' => $user
]);
