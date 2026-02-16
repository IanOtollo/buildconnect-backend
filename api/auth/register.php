<?php
require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

// If data is empty, check $_POST (useful for multipart/form-data with file uploads)
if (empty($data)) {
    $data = $_POST;
}

// Validate required fields
$error = validateRequired($data, ['email', 'password', 'full_name', 'phone', 'role']);
if ($error) {
    jsonResponse(['error' => $error], 400);
}

// Validate role
if (!in_array($data['role'], ['client', 'contractor'])) {
    jsonResponse(['error' => 'Invalid role'], 400);
}

// Sanitize inputs
$email = filter_var(sanitizeInput($data['email']), FILTER_VALIDATE_EMAIL);
$password = $data['password'];
$full_name = sanitizeInput($data['full_name']);
$phone = sanitizeInput($data['phone']);
$role = $data['role'];

if (!$email) {
    jsonResponse(['error' => 'Invalid email format'], 400);
}

if (strlen($password) < 6) {
    jsonResponse(['error' => 'Password must be at least 6 characters'], 400);
}

try {
    $db = getDBConnection();

    // Check if email exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'Email already registered'], 400);
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $db->prepare("INSERT INTO users (email, password, role, full_name, phone) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$email, $hashedPassword, $role, $full_name, $phone]);

    $userId = $db->lastInsertId();

    // If contractor, create contractor profile
    if ($role === 'contractor') {
        $businessName = sanitizeInput($data['business_name'] ?? '');
        $category = sanitizeInput($data['category'] ?? '');
        $location = sanitizeInput($data['location'] ?? '');
        $yearsExperience = intval($data['years_of_experience'] ?? 0);
        $bio = sanitizeInput($data['bio'] ?? '');
        $hourlyRate = floatval($data['hourly_rate'] ?? 0);

        if (empty($businessName) || empty($category) || empty($location) || $yearsExperience <= 0) {
            jsonResponse(['error' => 'Business name, category, location, and years of experience are required for contractors'], 400);
        }

        // Insert contractor profile with hourly_rate
        $stmt = $db->prepare("INSERT INTO contractors (user_id, business_name, category, location, years_of_experience, bio, hourly_rate, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$userId, $businessName, $category, $location, $yearsExperience, $bio, $hourlyRate]);

        // Create notification for admin
        $stmt = $db->prepare("SELECT id FROM users WHERE role = 'admin'");
        $stmt->execute();
        $admins = $stmt->fetchAll();

        foreach ($admins as $admin) {
            createNotification(
                $admin['id'],
                'New Contractor Application',
                "$businessName has applied to become a contractor",
                'contractor_application'
            );
        }
    }

    // Generate token
    $token = generateToken($userId, $role);

    jsonResponse([
        'message' => $role === 'contractor'
        ? 'Application submitted successfully. Awaiting admin approval.'
        : 'Registration successful',
        'token' => $token,
        'user' => [
            'id' => $userId,
            'email' => $email,
            'full_name' => $full_name,
            'phone' => $phone,
            'role' => $role
        ]
    ], 201);


}
catch (PDOException $e) {
    jsonResponse(['error' => 'Registration failed: ' . $e->getMessage()], 500);
}
