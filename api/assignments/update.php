<?php
require_once __DIR__ . '/../../config/database.php';

// This script handles /assignments/{id}/{action} logic
// It mimics requests/respond.php but takes parameters from the router

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Require contractor authentication
$user = requireAuth(['contractor']);

// IDs passed from router (needs to be injected into global scope or passed differently,
// but for now, we assume index.php sets $_REQUEST or global variables)
// Let's rely on global variables set by index.php for simplicity in this PHP version
global $routeParams;
$requestId = $routeParams['id'] ?? 0;
$action = $routeParams['action'] ?? '';

// Map 'accept'/'decline' to 'accept'/'reject' needed by logic
if ($action === 'decline')
    $action = 'reject';

if (!$requestId || !in_array($action, ['accept', 'reject'])) {
    jsonResponse(['error' => 'Invalid request parameters'], 400);
}

try {
    $db = getDBConnection();

    // Get contractor ID
    $stmt = $db->prepare("SELECT id FROM contractors WHERE user_id = ?");
    $stmt->execute([$user['user_id']]);
    $contractor = $stmt->fetch();

    if (!$contractor) {
        jsonResponse(['error' => 'Contractor profile not found'], 404);
    }

    // Get service request
    // Note: Verification logic might differ. 
    // Usually assignments implies a relationship exists or is being established.
    // If 'respond.php' logic checks `contractor_id`, it means the request is already assigned to them?
    // Or maybe it's checking if they applied?
    // Let's assume the request has `contractor_id` set to this contractor if it was assigned to them.

    $stmt = $db->prepare("SELECT * FROM service_requests WHERE id = ? AND contractor_id = ?");
    $stmt->execute([$requestId, $contractor['id']]);
    $request = $stmt->fetch();

    if (!$request) {
        jsonResponse(['error' => 'Service request not found or not assigned to you'], 404);
    }

    if ($request['status'] !== 'pending') {
        jsonResponse(['error' => 'Request already processed'], 400);
    }

    // Update status
    $newStatus = $action === 'accept' ? 'accepted' : 'rejected';
    $stmt = $db->prepare("UPDATE service_requests SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $requestId]);

    // Send notification to client
    $notificationTitle = $action === 'accept' ? 'Request Accepted' : 'Request Rejected';
    $notificationMessage = $action === 'accept'
        ? "Your service request '{$request['title']}' has been accepted by the contractor."
        : "Your service request '{$request['title']}' has been declined by the contractor.";

    createNotification(
        $request['client_id'],
        $notificationTitle,
        $notificationMessage,
        'request_response'
    );

    jsonResponse([
        'message' => "Assignment " . ($action === 'accept' ? 'accepted' : 'declined') . " successfully"
    ]);


}
catch (PDOException $e) {
    jsonResponse(['error' => 'Failed to process assignment: ' . $e->getMessage()], 500);
}
