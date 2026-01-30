<?php
// Router with configurable CORS and DEV-friendly fallback
$allowedOriginsEnv = getenv('CORS_ALLOWED_ORIGINS') ?: 'http://localhost:3000,https://buildconnect-ke.vercel.app';
$allowedOrigins = array_map('trim', explode(',', $allowedOriginsEnv));
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($origin && in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
} elseif (getenv('DEV') === 'true') {
    // Development: allow any origin for convenience
    header('Access-Control-Allow-Origin: *');
} else {
    // Fallback to first allowed origin
    header('Access-Control-Allow-Origin: ' . $allowedOrigins[0]);
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get the requested URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove trailing slash
$uri = rtrim($uri, '/');

// If file exists, serve it
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Try adding .php extension
$phpFile = __DIR__ . $uri . '.php';
if (file_exists($phpFile)) {
    require $phpFile;
    exit();
}

// Try index.php
if (file_exists(__DIR__ . $uri . '/index.php')) {
    require __DIR__ . $uri . '/index.php';
    exit();
}

// Nothing matched
http_response_code(404);
echo json_encode(['error' => 'Not found']);
