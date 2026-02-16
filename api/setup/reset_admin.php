<?php
require_once __DIR__ . '/../../config/database.php';

header("Content-Type: application/json");

try {
    $db = getDBConnection();

    $email = 'admin@buildconnect.com';
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Diagnostic info
    $info = [];

    // Check if user exists
    $stmt = $db->prepare("SELECT id, email, role, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $info['status'] = 'found';
        $info['existing_role'] = $user['role'];

        // Update to ensure password and role are correct
        $stmt = $db->prepare("UPDATE users SET password = ?, role = 'admin' WHERE email = ?");
        $stmt->execute([$hash, $email]);
        $info['action'] = 'updated';
    }
    else {
        $info['status'] = 'not_found';
        // Insert
        $stmt = $db->prepare("INSERT INTO users (email, password, role, full_name, phone) VALUES (?, ?, 'admin', 'System Admin', '+254700000000')");
        $stmt->execute([$email, $hash]);
        $info['action'] = 'created';
    }

    // Verify hash immediately after update/create
    $stmt = $db->prepare("SELECT password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $new_user = $stmt->fetch();
    $info['hash_verification'] = password_verify($password, $new_user['password']) ? 'success' : 'failed';

    echo json_encode([
        "message" => "Admin diagnostic complete",
        "diagnostic" => $info,
        "credentials_to_use" => [
            "email" => $email,
            "password" => $password
        ]
    ]);
}
catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
