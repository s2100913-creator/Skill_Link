<?php
header('Content-Type: application/json');
require __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Use GET']);
    exit;
}

$sql = 'SELECT s.id, s.name, s.description, s.user_id, u.name AS owner,
               s.category_id, c.name AS category
        FROM user_skills s
        JOIN users u ON u.id = s.user_id
        LEFT JOIN categories c ON c.id = s.category_id
        ORDER BY s.id';
$skills = $pdo->query($sql)->fetchAll();

echo json_encode(['success' => true, 'skills' => $skills]);