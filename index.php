<?php
// BuildConnect PHP API
// Main entry point

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// API info endpoint
echo json_encode([
    'name' => 'BuildConnect API',
    'version' => '1.0.0',
    'status' => 'active',
    'endpoints' => [
        'auth' => [
            'POST /api/auth/register' => 'Register new user (client or contractor)',
            'POST /api/auth/login' => 'User login'
        ],
        'contractors' => [
            'GET /api/contractors/list' => 'List all approved contractors',
            'GET /api/contractors/profile' => 'Get contractor profile',
            'POST /api/contractors/upload' => 'Upload contractor documents (auth required)'
        ],
        'admin' => [
            'GET /api/admin/dashboard' => 'Admin dashboard data (admin auth required)',
            'POST /api/admin/verify' => 'Verify contractor application (admin auth required)'
        ],
        'requests' => [
            'POST /api/requests/create' => 'Create service request (client auth required)',
            'GET /api/requests/list' => 'List service requests (auth required)',
            'POST /api/requests/respond' => 'Accept/reject service request (contractor auth required)'
        ],
        'notifications' => [
            'GET /api/notifications/get' => 'Get user notifications (auth required)',
            'POST /api/notifications/get' => 'Mark notifications as read (auth required)'
        ],
        'categories' => [
            'GET /api/categories/list' => 'List all categories'
        ]
    ]
]);
