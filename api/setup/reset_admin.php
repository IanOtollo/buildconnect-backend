<?php
require_once __DIR__ . '/../../config/database.php';

try {
    $db = getDBConnection();

    $email = 'admin@buildconnect.com';
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Check if user exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        // Update
        $stmt = $db->prepare("UPDATE users SET password = ?, role = 'admin' WHERE email = ?");
        $stmt->execute([$hash, $email]);
        echo json_encode(["message" => "Admin password reset successfully to 'admin123'"]);
    }
    else {
        // Insert
        $stmt = $db->prepare("INSERT INTO users (email, password, role, full_name, phone) VALUES (?, ?, 'admin', 'System Admin', '+254700000000')");
        $stmt->execute([$email, $hash]);
        echo json_encode(["message" => "Admin user created successfully with password 'admin123'"]);
    }
}
catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
