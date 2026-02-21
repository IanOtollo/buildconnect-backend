<?php
require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Require client authentication to release
$user = requireAuth(['client']);

$data = json_decode(file_get_contents('php://input'), true);

$error = validateRequired($data, ['request_id']);
if ($error) {
    jsonResponse(['error' => $error], 400);
}

$requestId = intval($data['request_id']);

try {
    $db = getDBConnection();

    // Get service request and ensure the user owns it
    $stmt = $db->prepare("SELECT * FROM service_requests WHERE id = ? AND client_id = ?");
    $stmt->execute([$requestId, $user['id']]);
    $request = $stmt->fetch();

    if (!$request) {
        jsonResponse(['error' => 'Service request not found or unauthorized'], 404);
    }

    // Check if it's already completed or cancelled
    if (in_array($request['status'], ['completed', 'cancelled'])) {
        jsonResponse(['error' => 'Request is already completed or cancelled'], 400);
    }

    // Must be at a stage where escrow was funded
    // For simplicity we allow completing from any stage as long as escrow was funded
    // Or we simply check the transaction table.

    // Find the escrow payment
    $escrowStmt = $db->prepare("SELECT amount FROM transactions WHERE service_request_id = ? AND transaction_type = 'escrow_payment' AND status = 'completed'");
    $escrowStmt->execute([$requestId]);
    $escrowTx = $escrowStmt->fetch();

    $escrowAmount = $escrowTx ? floatval($escrowTx['amount']) : 0;

    $db->beginTransaction();

    // Update request status
    $updateStmt = $db->prepare("UPDATE service_requests SET status = 'completed' WHERE id = ?");
    $updateStmt->execute([$requestId]);

    if ($escrowAmount > 0 && $request['contractor_id']) {
        // Find contractor's user_id
        $contractorStmt = $db->prepare("SELECT user_id FROM contractors WHERE id = ?");
        $contractorStmt->execute([$request['contractor_id']]);
        $contractor = $contractorStmt->fetch();

        if ($contractor) {
            $contractorUserId = $contractor['user_id'];

            // Credit contractor wallet
            $balanceStmt = $db->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
            $balanceStmt->execute([$escrowAmount, $contractorUserId]);

            // Create escrow_release transaction
            $releaseStmt = $db->prepare("INSERT INTO transactions (user_id, service_request_id, amount, transaction_type, status, description) VALUES (?, ?, ?, 'escrow_release', 'completed', 'Escrow released for Service Request completion')");
            $releaseStmt->execute([$contractorUserId, $requestId, $escrowAmount]);

            createNotification(
                $contractorUserId,
                "Payment Released",
                "KES " . number_format($escrowAmount, 2) . " has been released to your wallet for completing '{$request['title']}'.",
                "payment"
            );
        }
    }

    createNotification(
        $user['id'],
        "Project Completed",
        "You have marked '{$request['title']}' as complete.",
        "request_status"
    );

    $db->commit();

    jsonResponse([
        'message' => 'Service request completed and escrow funds released successfully.'
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse(['error' => 'Failed to complete project: ' . $e->getMessage()], 500);
}
