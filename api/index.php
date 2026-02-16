<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = explode('/', trim($uri, '/'));

// Expected parts: [api, resource, id/action, action]
// e.g. api/auth/login
// e.g. api/assignments/123/accept

// Index 0 = api
$resource = $parts[1] ?? '';
$param1 = $parts[2] ?? null;
$param2 = $parts[3] ?? null;

// Global route params to be used by included files
global $routeParams;
$routeParams = [];

switch ($resource) {
    case 'auth':
        if ($param1 === 'login')
            require __DIR__ . '/auth/login.php';
        elseif ($param1 === 'register')
            require __DIR__ . '/auth/register.php';
        elseif ($param1 === 'me') {
            // Check if me.php exists, else implement simple response
            if (file_exists(__DIR__ . '/auth/me.php'))
                require __DIR__ . '/auth/me.php';
            else {
                include_once __DIR__ . '/../config/database.php';
                $user = requireAuth(); // This will validate token
                jsonResponse(['user' => $user]);
            }
        }
        else
            http_response_code(404);
        break;

    case 'categories':
        if (is_numeric($param1)) {
            $_GET['id'] = $param1; // Inject ID for list.php or specific file
            require __DIR__ . '/categories/list.php'; // logic usually handles ID check
        }
        else {
            require __DIR__ . '/categories/list.php';
        }
        break;

    case 'service-requests':
        if ($_SERVER['REQUEST_METHOD'] === 'POST')
            require __DIR__ . '/requests/create.php';
        elseif (is_numeric($param1)) {
            $_GET['id'] = $param1;
            // requests/list.php might not handle single ID. 
            // If not, we might need requests/get.php. 
            // For now pointing to list, assuming it filters or returns all.
            require __DIR__ . '/requests/list.php';
        }
        else
            require __DIR__ . '/requests/list.php';
        break;

    case 'contractors':
        if ($param1 === 'me') {
            // Handle /contractors/me
            include_once __DIR__ . '/../config/database.php';
            $user = requireAuth(['contractor']);
            $db = getDBConnection();
            $stmt = $db->prepare("SELECT id FROM contractors WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);
            $c = $stmt->fetch();
            if ($c) {
                $_GET['id'] = $c['id'];
                require __DIR__ . '/contractors/profile.php';
            }
            else {
                jsonResponse(['error' => 'Contractor profile not found'], 404);
            }
        }
        elseif (is_numeric($param1)) {
            $_GET['id'] = $param1;
            require __DIR__ . '/contractors/profile.php';
        }
        else
            require __DIR__ . '/contractors/list.php';
        break;

    case 'assignments':
        // /assignments/{id}/accept
        if (is_numeric($param1) && ($param2 === 'accept' || $param2 === 'decline')) {
            $routeParams['id'] = $param1;
            $routeParams['action'] = $param2;
            require __DIR__ . '/assignments/update.php';
        }
        elseif ($param1 === 'pending') {
            require __DIR__ . '/assignments/pending.php';
        }
        break;

    case 'ai':
        if ($param1 === 'estimate')
            require __DIR__ . '/ai/estimate.php';
        break;

    case 'payments':
        if ($param1 === 'stkpush')
            require __DIR__ . '/payments/stkpush.php';
        elseif ($param1 === 'callback')
            require __DIR__ . '/payments/callback.php';
        break;

    case 'setup':
        require __DIR__ . '/setup/install.php';
        break;

    default:
        echo json_encode(['message' => 'BuildConnect API Services Running']);
        break;
}
