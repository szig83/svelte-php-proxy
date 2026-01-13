<?php
$requestUri = $_SERVER['REQUEST_URI'];
$requestPath = parse_url($requestUri, PHP_URL_PATH);
$requestPath = trim($requestPath, '/');

$routes = [
    'auth/login' => 'endpoints/auth/login.php',
    'menu' => 'endpoints/menu.php',
];

if (isset($routes[$requestPath])) {
    $endpointFile = __DIR__ . '/' . $routes[$requestPath];
    if (file_exists($endpointFile)) {
        require $endpointFile;
    } else {
        http_response_code(404);
        header('Content-type: application/json');
        echo json_encode(['error' => 'Endpoint file not found']);
    }
} else {
    http_response_code(404);
    header('Content-type: application/json');
    echo json_encode(['error' => 'Route not found']);
}
