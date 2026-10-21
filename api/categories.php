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

if ($method === 'GET' && $id === 0) {
    $rows = $pdo->query('SELECT id, name FROM categories ORDER BY id')->fetchAll();
    echo json_encode(['success' => true, 'categories' => $rows]);

} elseif ($method === 'GET') {
    $stmt = $pdo->prepare('SELECT id, name FROM categories WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) fail(404, 'Category not found');
    echo json_encode(['success' => true, 'category' => $row]);

} elseif ($method === 'POST') {
    $name = trim($data['name'] ?? '');
    if ($name === '') fail(400, 'name is required');
    $stmt = $pdo->prepare('INSERT INTO categories (name) VALUES (:n) RETURNING id');
    $stmt->execute([':n' => $name]);
    http_response_code(201);
    echo json_encode(['success' => true, 'message' => 'Category created', 'category_id' => $stmt->fetchColumn()]);

} elseif ($method === 'PATCH') {
    $name = trim($data['name'] ?? '');
    if ($name === '') fail(400, 'name is required');
    $stmt = $pdo->prepare('UPDATE categories SET name = :n WHERE id = :id RETURNING id');
    $stmt->execute([':n' => $name, ':id' => $id]);
    if (!$stmt->fetch()) fail(404, 'Category not found');
    echo json_encode(['success' => true, 'message' => 'Category updated']);

} elseif ($method === 'DELETE') {
    try {
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = :id RETURNING id');
        $stmt->execute([':id' => $id]);
    } catch (PDOException $e) {
        fail(409, 'Category is used by skills. Remove those first.');
    }
    if (!$stmt->fetch()) fail(404, 'Category not found');
    echo json_encode(['success' => true, 'message' => 'Category deleted']);

} else {
    fail(405, 'Method not allowed');
}