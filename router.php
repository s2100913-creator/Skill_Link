<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$routes = [
    '#^/api/authentication/login/?$#'     => 'login.php',
    '#^/api/authentication/register/?$#'  => 'register.php',
    '#^/api/authentication/logout/?$#'    => 'logout.php',
    '#^/api/users(?:/(\d+))?/?$#'         => 'users.php',
    '#^/api/categories(?:/(\d+))?/?$#'    => 'categories.php',
    '#^/api/skills(?:/(\d+))?/?$#'        => 'skills.php',
    '#^/api/classes(?:/(\d+))?/?$#'       => 'classes.php',
    '#^/api/class-users(?:/(\d+))?/?$#'   => 'class_users.php',
    '#^/api/class-attendances(?:/(\d+))?/?$#' => 'class_attendances.php',
];

foreach ($routes as $pattern => $file) {
    if (preg_match($pattern, $path, $m)) {
        if (!empty($m[1])) {
            $_GET['id'] = $m[1];   // /api/users/3 -> ?id=3
        }
        require __DIR__ . '/api/' . $file;
        return true;
    }
}

// Old style URLs such as /api/login.php keep working
if (preg_match('#^/api/([a-z_]+\.php)$#', $path, $m) && is_file(__DIR__ . '/api/' . $m[1])) {
    require __DIR__ . '/api/' . $m[1];
    return true;
}

http_response_code(404);
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Not found']);