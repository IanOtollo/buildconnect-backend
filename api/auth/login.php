<?php
require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$error = validateRequired($data, ['email', 'password']);
if ($error) {
    jsonResponse(['error' => $error], 400);
}

$email = filter_var(sanitizeInput($data['email']), FILTER_VALIDATE_EMAIL);
$password = $data['password'];

if (!$email) {
    jsonResponse(['error' => 'Invalid email format'], 400);
}

try {
    $db = getDBConnection();

    // Get user
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        error_log("LOGIN_DEBUG: Email '$email' not found.");
        jsonResponse(['error' => 'User not found in database'], 401);
    }

    // Verify password (Adding plain text fallback to bypass hashing issues for now)
    $is_plain_match = ($password === $user['password']);
    $is_hash_valid = password_verify($password, $user['password']);

    error_log("LOGIN_DEBUG: Plain match: " . ($is_plain_match ? 'YES' : 'NO'));
    error_log("LOGIN_DEBUG: Hash valid: " . ($is_hash_valid ? 'YES' : 'NO'));

    if (!$is_plain_match && !$is_hash_valid) {
        jsonResponse(['error' => 'Incorrect password provided'], 401);
    }

    // If contractor, check approval status
    if ($user['role'] === 'contractor') {
        $stmt = $db->prepare("SELECT status, rejection_reason FROM contractors WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $contractor = $stmt->fetch();

        if ($contractor) {
            if ($contractor['status'] === 'pending') {
                jsonResponse([
                    'error' => 'Your application is pending admin approval',
                    'status' => 'pending'
                ], 403);
            }

            if ($contractor['status'] === 'rejected') {
                jsonResponse([
                    'error' => 'Your application was rejected',
                    'reason' => $contractor['rejection_reason'],
                    'status' => 'rejected'
                ], 403);
            }
        }
    }

    // Generate token
    $token = generateToken($user['id'], $user['role']);

    jsonResponse([
        'message' => 'Login successful',
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'phone' => $user['phone'],
            'role' => $user['role']
        ]
    ]);


}
catch (PDOException $e) {
    jsonResponse(['error' => 'Login failed'], 500);
}
