<?php
// Bulletproof CORS
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: " . $origin);
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get requested URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');

// Forward all /api requests to the API router
if (strpos($uri, '/api') === 0) {
    require __DIR__ . '/api/index.php';
    exit();
}

// Static file or root route
if ($uri !== '' && file_exists(__DIR__ . $uri)) {
    return false; // serve as-is
}

// Default to root index.php logic
require __DIR__ . '/index.php';
