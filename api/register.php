<?php
header('Content-Type: application/json');
   require __DIR__ . '/../lib/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Use POST']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];

$name         = trim($data['name'] ?? '');
$username     = trim($data['username'] ?? '');
$email        = trim($data['email'] ?? '');
$password     = $data['password'] ?? '';
$phone_number = trim($data['phone_number'] ?? '');

if ($name === '' || $username === '' || $email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'name, username, email and password are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email']);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

// Is the username or email already taken?
$check = $pdo->prepare('SELECT 1 FROM users WHERE username = :u OR email = :e');
$check->execute([':u' => $username, ':e' => $email]);
if ($check->fetch()) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
    exit;
}

// Save the user with a hashed password
$stmt = $pdo->prepare(
    'INSERT INTO users (name, username, password, email, phone_number)
     VALUES (:name, :username, :password, :email, :phone)
     RETURNING id'
);
$stmt->execute([
    ':name'     => $name,
    ':username' => $username,
    ':password' => password_hash($password, PASSWORD_DEFAULT),
    ':email'    => $email,
    ':phone'    => $phone_number,
]);

http_response_code(201);
echo json_encode([
    'success' => true,
    'message' => 'Registered successfully',
    'user_id' => $stmt->fetchColumn(),
]);