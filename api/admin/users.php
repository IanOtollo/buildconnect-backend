<?php
require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

// Require admin authentication
$admin = requireAuth(['admin']);

try {
    $db = getDBConnection();

    if ($method === 'GET') {
        $stmt = $db->query("SELECT id, email, full_name, phone, role, created_at FROM users ORDER BY created_at DESC");
        jsonResponse($stmt->fetchAll());
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        $error = validateRequired($data, ['email', 'password', 'full_name', 'phone', 'role']);
        if ($error) {
            jsonResponse(['error' => $error], 400);
        }

        $email = filter_var(sanitizeInput($data['email']), FILTER_VALIDATE_EMAIL);
        if (!$email)
            jsonResponse(['error' => 'Invalid email'], 400);

        // Check if exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            jsonResponse(['error' => 'Email already registered'], 400);
        }

        $hashed = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO users (email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$email, $hashed, sanitizeInput($data['full_name']), sanitizeInput($data['phone']), $data['role']]);

        jsonResponse(['message' => 'User created successfully', 'id' => $db->lastInsertId()], 201);
    }
} catch (PDOException $e) {
    jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
}
