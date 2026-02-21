<?php
require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Only contractors can log progress updates
$user = requireAuth(['contractor']);

$data = json_decode(file_get_contents('php://input'), true);

$error = validateRequired($data, ['request_id', 'stage']);
if ($error) {
    jsonResponse(['error' => $error], 400);
}

$requestId = intval($data['request_id']);
$stage = sanitizeInput($data['stage']); // 'midpoint' or 'final'
$notes = sanitizeInput($data['notes'] ?? '');

if (!in_array($stage, ['midpoint', 'final'])) {
    jsonResponse(['error' => 'Invalid stage. Must be midpoint or final.'], 400);
}

try {
    $db = getDBConnection();

    // Get contractor id
    $stmt = $db->prepare("SELECT id FROM contractors WHERE user_id = ?");
    $stmt->execute([$user['userId']]);
    $contractor = $stmt->fetch();

    if (!$contractor) {
        jsonResponse(['error' => 'Contractor profile not found'], 404);
    }

    // Get the service request, verify it's assigned to this contractor
    $stmt = $db->prepare("SELECT * FROM service_requests WHERE id = ? AND contractor_id = ?");
    $stmt->execute([$requestId, $contractor['id']]);
    $request = $stmt->fetch();

    if (!$request) {
        jsonResponse(['error' => 'Service request not found or unauthorized'], 404);
    }

    // Validate the correct current status for each stage
    if ($stage === 'midpoint' && $request['status'] !== 'deposit_paid') {
        jsonResponse(['error' => 'Midpoint update can only be submitted after the client has paid the deposit.'], 400);
    }
    if ($stage === 'final' && $request['status'] !== 'balance_paid') {
        jsonResponse(['error' => 'Final completion can only be submitted after the client has paid the balance.'], 400);
    }

    // Set the new status (pending client approval)
    $newStatus = $stage === 'midpoint' ? 'pending_midpoint_approval' : 'pending_final_approval';
    $db->prepare("UPDATE service_requests SET status = ? WHERE id = ?")->execute([$newStatus, $requestId]);

    // Notify the client
    if ($stage === 'midpoint') {
        $title = 'Contractor Reports Halfway Done – Please Confirm';
        $message = "Your contractor has marked the project '{$request['title']}' as 50% complete." .
            ($notes ? " Notes: $notes" : "") .
            " Please review and confirm to release the remaining 80% payment.";
    } else {
        $title = 'Project Marked Complete – Please Confirm';
        $message = "Your contractor has marked '{$request['title']}' as fully completed." .
            ($notes ? " Notes: $notes" : "") .
            " Please review and confirm to release the final payment to the contractor.";
    }

    createNotification($request['client_id'], $title, $message, 'progress_update');

    jsonResponse([
        'message' => "Progress update submitted. Waiting for client confirmation.",
        'new_status' => $newStatus
    ]);

} catch (PDOException $e) {
    jsonResponse(['error' => 'Failed to submit progress update: ' . $e->getMessage()], 500);
}
