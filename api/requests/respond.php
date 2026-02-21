<?php
require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Require contractor authentication
$user = requireAuth(['contractor']);

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$error = validateRequired($data, ['request_id', 'action']);
if ($error) {
    jsonResponse(['error' => $error], 400);
}

$requestId = intval($data['request_id']);
$action = sanitizeInput($data['action']);

if (!in_array($action, ['accept', 'reject'])) {
    jsonResponse(['error' => 'Invalid action'], 400);
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
    $stmt = $db->prepare("SELECT * FROM service_requests WHERE id = ? AND contractor_id = ?");
    $stmt->execute([$requestId, $contractor['id']]);
    $request = $stmt->fetch();

    if (!$request) {
        jsonResponse(['error' => 'Service request not found'], 404);
    }

    if ($request['status'] !== 'pending') {
        jsonResponse(['error' => 'Request already processed'], 400);
    }

    // Update status
    $newStatus = $action === 'accept' ? 'accepted' : 'rejected';
    $stmt = $db->prepare("UPDATE service_requests SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $requestId]);

    // Send notification to client
    if ($action === 'accept') {
        $depositAmount = $request['budget'] * 0.20;
        $notificationTitle = 'Request Accepted – Pay 20% Deposit to Begin';
        $notificationMessage = "Great news! Your service request '{$request['title']}' has been accepted. " .
            "Please pay a 20% deposit of KES " . number_format($depositAmount, 2) . " to allow work to begin.";
    } else {
        $notificationTitle = 'Request Rejected';
        $notificationMessage = "Your service request '{$request['title']}' was not accepted by the contractor.";
    }

    createNotification(
        $request['client_id'],
        $notificationTitle,
        $notificationMessage,
        'request_response'
    );

    jsonResponse([
        'message' => "Request $action" . "ed successfully"
    ]);

} catch (PDOException $e) {
    jsonResponse(['error' => 'Failed to respond to request'], 500);
}
