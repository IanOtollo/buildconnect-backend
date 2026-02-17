<?php
require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Require client authentication
$user = requireAuth(['client']);

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields (Removed contractor_id from required)
$error = validateRequired($data, ['category', 'title', 'description', 'location']);
if ($error) {
    jsonResponse(['error' => $error], 400);
}

$contractorId = isset($data['contractor_id']) ? intval($data['contractor_id']) : null;
$category = sanitizeInput($data['category']);
$title = sanitizeInput($data['title']);
$description = sanitizeInput($data['description']);
$location = sanitizeInput($data['location']);

// New fields
$budget = isset($data['budget']) ? floatval($data['budget']) : 0.00;
$duration = isset($data['estimated_duration']) ? sanitizeInput($data['estimated_duration']) : '';
$urgency = isset($data['urgency']) ? sanitizeInput($data['urgency']) : 'medium';

try {
    $db = getDBConnection();

    // Create service request with new fields
    $stmt = $db->prepare("INSERT INTO service_requests (client_id, contractor_id, category, title, description, location, budget, estimated_duration, urgency, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$user['user_id'], $contractorId, $category, $title, $description, $location, $budget, $duration, $urgency]);

    $requestId = $db->lastInsertId();

    // Send notification to contractor if assigned
    if ($contractorId) {
        $stmt = $db->prepare("SELECT user_id FROM contractors WHERE id = ?");
        $stmt->execute([$contractorId]);
        $contractor = $stmt->fetch();

        if ($contractor) {
            createNotification(
                $contractor['user_id'],
                'New Service Request',
                "You have received a new service request: $title",
                'service_request'
            );
        }
    }

    jsonResponse([
        'message' => 'Service request created successfully',
        'request_id' => $requestId
    ], 201);

} catch (PDOException $e) {
    jsonResponse(['error' => 'Failed to create service request: ' . $e->getMessage()], 500);
}
