<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

// Simple router
// Assuming URI structure: /api/resource/action
// e.g. /api/auth/login -> resource=auth, action=login

if (isset($uri[2])) {
    $resource = $uri[2];
    $action = $uri[3] ?? null;

    switch ($resource) {
        case 'auth':
            require_once __DIR__ . '/auth/index.php'; // You might need to adjust this based on your auth structure
            break;
        case 'requests':
            require_once __DIR__ . '/requests/index.php';
            break;
        // Add other cases
        default:
            echo json_encode(['message' => 'BuildConnect API Services Running']);
            break;
    }
}
else {
    echo json_encode(['message' => 'BuildConnect API Services Running']);
}
