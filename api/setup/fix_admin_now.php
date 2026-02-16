<?php
require_once __DIR__ . '/../../config/database.php';

header("Content-Type: application/json");

try {
    $db = getDBConnection();

    $email = 'admin@buildconnect.com';
    $password = 'admin123';
    // $hash = password_hash($password, PASSWORD_DEFAULT); // Bypassing as requested
    $hash = $password;

    // 1. Delete existing if any to be absolutely sure
    $stmt = $db->prepare("DELETE FROM users WHERE email = ?");
    $stmt->execute([$email]);

    // 2. Insert fresh
    $stmt = $db->prepare("INSERT INTO users (email, password, role, full_name, phone) VALUES (?, ?, 'admin', 'System Admin', '+254700000000')");
    $stmt->execute([$email, $hash]);

    echo json_encode([
        "success" => true,
        "message" => "Admin account RECREATED successfully.",
        "details" => [
            "email" => $email,
            "password" => "admin123",
            "role" => "admin"
        ]
    ]);
}
catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
