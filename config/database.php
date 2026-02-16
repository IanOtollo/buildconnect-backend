<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

/**
 * DB Connection helper
 */
function getDBConnection()
{
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $db = $_ENV['DB_NAME'] ?? 'buildconnect';
    $user = $_ENV['DB_USER'] ?? 'root';
    $pass = $_ENV['DB_PASS'] ?? $_ENV['DB_PASSWORD'] ?? '';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    }
    catch (\PDOException $e) {
        jsonResponse(['error' => 'Database connection failed: ' . $e->getMessage()], 500);
    }
}

/**
 * JSON Response helper
 */
function jsonResponse($data, $code = 200)
{
    header('Content-Type: application/json');
    http_response_code($code);
    echo json_encode($data);
    exit;
}

/**
 * Sanitize input
 */
function sanitizeInput($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Validate required fields
 */
function validateRequired($data, $fields)
{
    foreach ($fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            return "Field '$field' is required.";
        }
    }
    return null;
}

/**
 * Token generation
 */
function generateToken($userId, $role)
{
    $secret = $_ENV['JWT_SECRET'] ?? 'default_secret_key_change_me';
    $payload = [
        'iss' => 'buildconnect',
        'aud' => 'buildconnect',
        'iat' => time(),
        'nbf' => time(),
        'exp' => time() + (24 * 60 * 60), // 24 hours
        'data' => [
            'userId' => $userId,
            'role' => $role
        ]
    ];

    return JWT::encode($payload, $secret, 'HS256');
}

/**
 * Create notification helper
 */
function createNotification($userId, $title, $message, $type)
{
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $title, $message, $type]);
    }
    catch (\Exception $e) {
    // Log error or ignore
    }
}

/**
 * Auth Middleware
 */
/**
 * Auth Middleware
 */
function requireAuth($allowedRoles = [])
{
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';

    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
        $secret = $_ENV['JWT_SECRET'] ?? 'default_secret_key_change_me';

        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            $user = (array)$decoded->data;

            // Check role if specified
            if (!empty($allowedRoles)) {
                if (!in_array($user['role'], $allowedRoles)) {
                    jsonResponse(['error' => 'Forbidden: Insufficient permissions'], 403);
                }
            }

            return $user;

        }
        catch (\Exception $e) {
            jsonResponse(['error' => 'Unauthorized: Invalid token'], 401);
        }
    }

    jsonResponse(['error' => 'Unauthorized: Token missing'], 401);
}
