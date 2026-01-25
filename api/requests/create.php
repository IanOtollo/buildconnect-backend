<?php
require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Require client authentication
$user = requireAuth(['client']);

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$error = validateRequired($data, ['contractor_id', 'category', 'title', 'description', 'location']);
if ($error) {
    jsonResponse(['error' => $error], 400);
}

$contractorId = intval($data['contractor_id']);
$category = sanitizeInput($data['category']);
$title = sanitizeInput($data['title']);
$description = sanitizeInput($data['description']);
$location = sanitizeInput($data['location']);

try {
    $db = getDBConnection();
    
    // Verify contractor exists and is approved
    $stmt = $db->prepare("SELECT c.*, u.email FROM contractors c JOIN users u ON c.user_id = u.id WHERE c.id = ? AND c.status = 'approved'");
    $stmt->execute([$contractorId]);
    $contractor = $stmt->fetch();
    
    if (!$contractor) {
        jsonResponse(['error' => 'Contractor not found or not approved'], 404);
    }
    
    // Create service request
    $stmt = $db->prepare("INSERT INTO service_requests (client_id, contractor_id, category, title, description, location, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$user['user_id'], $contractorId, $category, $title, $description, $location]);
    
    $requestId = $db->lastInsertId();
    
    // Send notification to contractor
    createNotification(
        $contractor['user_id'],
        'New Service Request',
        "You have received a new service request: $title",
        'service_request'
    );
    
    jsonResponse([
        'message' => 'Service request created successfully',
        'request_id' => $requestId
    ], 201);
    
} catch (PDOException $e) {
    jsonResponse(['error' => 'Failed to create service request'], 500);
}
