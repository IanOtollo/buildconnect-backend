<?php
require_once __DIR__ . '/../../config/database.php';

header("Content-Type: application/json");

$email = 'admin@buildconnect.com';
$password = 'admin123';

try {
    $db = getDBConnection();

    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    $results = [];
    $results['email_searched'] = $email;
    $results['user_found'] = $user ? true : false;

    if ($user) {
        $results['user_id'] = $user['id'];
        $results['user_role'] = $user['role'];
        $results['stored_hash'] = $user['password'];
        $results['password_verify_simulated'] = password_verify($password, $user['password']);

        // Let's try to hash the password manually here and compare
        $new_hash = password_hash($password, PASSWORD_DEFAULT);
        $results['new_hash_generated'] = $new_hash;
        $results['verify_new_hash_against_itself'] = password_verify($password, $new_hash);
    }

    echo json_encode([
        "message" => "Login simulation results",
        "results" => $results
    ]);
}
catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
