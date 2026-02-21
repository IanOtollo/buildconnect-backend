<?php
require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Only clients can confirm or decline progress updates
$user = requireAuth(['client']);

$data = json_decode(file_get_contents('php://input'), true);

$error = validateRequired($data, ['request_id', 'stage', 'action']);
if ($error) {
    jsonResponse(['error' => $error], 400);
}

$requestId = intval($data['request_id']);
$stage = sanitizeInput($data['stage']);   // 'midpoint' or 'final'
$action = sanitizeInput($data['action']); // 'approve' or 'decline'
$reason = sanitizeInput($data['reason'] ?? '');

if (!in_array($stage, ['midpoint', 'final'])) {
    jsonResponse(['error' => 'Invalid stage. Must be midpoint or final.'], 400);
}
if (!in_array($action, ['approve', 'decline'])) {
    jsonResponse(['error' => 'Invalid action. Must be approve or decline.'], 400);
}
if ($action === 'decline' && empty($reason)) {
    jsonResponse(['error' => 'A reason is required when declining a progress update.'], 400);
}

try {
    $db = getDBConnection();

    // Get the service request and ensure the client owns it
    $stmt = $db->prepare("SELECT * FROM service_requests WHERE id = ? AND client_id = ?");
    $stmt->execute([$requestId, $user['userId']]);
    $request = $stmt->fetch();

    if (!$request) {
        jsonResponse(['error' => 'Service request not found or unauthorized'], 404);
    }

    // Validate current status matches the stage being confirmed
    $expectedStatus = $stage === 'midpoint' ? 'pending_midpoint_approval' : 'pending_final_approval';
    if ($request['status'] !== $expectedStatus) {
        jsonResponse(['error' => "Cannot confirm '$stage' at the current request stage: {$request['status']}"], 400);
    }

    // Get contractor user_id for notifications
    $cStmt = $db->prepare("SELECT user_id FROM contractors WHERE id = ?");
    $cStmt->execute([$request['contractor_id']]);
    $contractor = $cStmt->fetch();
    $contractorUserId = $contractor ? $contractor['user_id'] : null;

    if ($action === 'approve') {
        if ($stage === 'midpoint') {
            // Midpoint approved: advance to midpoint_approved, client must now pay the 80% balance
            $db->prepare("UPDATE service_requests SET status = 'midpoint_approved' WHERE id = ?")->execute([$requestId]);

            // Notify client to pay balance
            $balanceAmount = $request['budget'] * 0.80;
            createNotification(
                $user['userId'],
                'Midpoint Confirmed – Pay Remaining 80%',
                "You confirmed the midpoint for '{$request['title']}'. Please pay the remaining 80% balance of KES " .
                number_format($balanceAmount, 2) . " to let the contractor finish.",
                'payment'
            );

            // Notify contractor
            if ($contractorUserId) {
                createNotification(
                    $contractorUserId,
                    'Midpoint Approved – Await Balance Payment',
                    "The client has approved your midpoint update for '{$request['title']}'. They will now pay the remaining 80% balance.",
                    'progress_update'
                );
            }

            jsonResponse([
                'message' => 'Midpoint approved. The client will now pay the remaining 80% balance.',
                'new_status' => 'midpoint_approved'
            ]);

        } else {
            // Final completion approved: release all escrowed funds to contractor
            $db->beginTransaction();

            $db->prepare("UPDATE service_requests SET status = 'completed' WHERE id = ?")->execute([$requestId]);

            // Sum up all escrow payments for this request
            $escrowStmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE service_request_id = ? AND transaction_type = 'escrow_payment' AND status = 'pending'");
            $escrowStmt->execute([$requestId]);
            $escrow = $escrowStmt->fetch();
            $totalEscrow = floatval($escrow['total']);

            if ($totalEscrow > 0 && $contractorUserId) {
                // Mark all escrow transactions as completed
                $db->prepare("UPDATE transactions SET status = 'completed' WHERE service_request_id = ? AND transaction_type = 'escrow_payment'")->execute([$requestId]);

                // Credit contractor wallet (uses wallet_balance column; add if it doesn't exist in older schemas)
                $db->prepare("UPDATE users SET wallet_balance = COALESCE(wallet_balance, 0) + ? WHERE id = ?")->execute([$totalEscrow, $contractorUserId]);

                // Record the escrow release
                $db->prepare("INSERT INTO transactions (user_id, service_request_id, amount, transaction_type, status, description) VALUES (?, ?, ?, 'escrow_release', 'completed', 'Full escrow released on project completion')")
                    ->execute([$contractorUserId, $requestId, $totalEscrow]);

                createNotification(
                    $contractorUserId,
                    'Payment Released – Project Complete!',
                    "KES " . number_format($totalEscrow, 2) . " has been released to your wallet for completing '{$request['title']}'. Congratulations!",
                    'payment'
                );
            }

            // Notify client
            createNotification(
                $user['userId'],
                'Project Completed',
                "You have confirmed the completion of '{$request['title']}'. The funds have been released to the contractor.",
                'request_status'
            );

            $db->commit();

            jsonResponse([
                'message' => 'Project confirmed as complete. Funds have been released to the contractor.',
                'new_status' => 'completed',
                'released_amount' => $totalEscrow
            ]);
        }

    } else {
        // DECLINE – revert to previous active stage and notify contractor
        $revertStatus = $stage === 'midpoint' ? 'deposit_paid' : 'balance_paid';
        $db->prepare("UPDATE service_requests SET status = ? WHERE id = ?")->execute([$revertStatus, $requestId]);

        if ($contractorUserId) {
            $stageLabel = $stage === 'midpoint' ? 'midpoint' : 'final completion';
            createNotification(
                $contractorUserId,
                "Progress Update Declined",
                "The client has declined your $stageLabel update for '{$request['title']}'. Reason: $reason. Please address the concerns and resubmit.",
                'progress_update'
            );
        }

        jsonResponse([
            'message' => 'Progress update declined. The contractor has been notified.',
            'new_status' => $revertStatus
        ]);
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse(['error' => 'Failed to process confirmation: ' . $e->getMessage()], 500);
}
