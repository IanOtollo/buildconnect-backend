<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/MpesaService.php';

// Auth check - requireAuth is the correct helper
$user = requireAuth(['client']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

$error = validateRequired($data, ['phone', 'amount', 'service_request_id', 'payment_stage']);
if ($error) {
    jsonResponse(['error' => $error], 400);
}

$phone = sanitizeInput($data['phone']);
$amount = floatval($data['amount']);
$serviceRequestId = intval($data['service_request_id']);
$paymentStage = sanitizeInput($data['payment_stage']); // 'deposit' or 'balance'

if (!in_array($paymentStage, ['deposit', 'balance'])) {
    jsonResponse(['error' => 'Invalid payment_stage. Must be deposit or balance.'], 400);
}

$reference = 'BCON_' . strtoupper($paymentStage) . '_' . time();
$description = ($paymentStage === 'deposit' ? '20% Deposit' : '80% Balance') . ' for Request #' . $serviceRequestId;

try {
    $db = getDBConnection();

    // Validate the service request exists and belongs to this client
    $stmt = $db->prepare("SELECT * FROM service_requests WHERE id = ? AND client_id = ?");
    $stmt->execute([$serviceRequestId, $user['userId']]);
    $request = $stmt->fetch();

    if (!$request) {
        jsonResponse(['error' => 'Service request not found or unauthorized'], 404);
    }

    // Validate correct stage transition
    if ($paymentStage === 'deposit' && $request['status'] !== 'accepted') {
        jsonResponse(['error' => 'Deposit can only be made once the contractor has accepted the request.'], 400);
    }
    if ($paymentStage === 'balance' && $request['status'] !== 'midpoint_approved') {
        jsonResponse(['error' => 'Balance can only be paid after the midpoint progress has been approved.'], 400);
    }

    $mpesa = new MpesaService();
    $result = $mpesa->stkPush($phone, $amount, $reference, $description);

    if (isset($result['ResponseCode']) && $result['ResponseCode'] === '0') {
        // Record the pending transaction
        $stmt = $db->prepare("INSERT INTO transactions (user_id, service_request_id, amount, transaction_type, status, reference_number, description) VALUES (?, ?, ?, 'escrow_payment', 'pending', ?, ?)");
        $stmt->execute([$user['userId'], $serviceRequestId, $amount, $result['CheckoutRequestID'], $description]);

        // Immediately advance the request status (in a real system this happens in the M-Pesa callback)
        $newStatus = $paymentStage === 'deposit' ? 'deposit_paid' : 'balance_paid';
        $db->prepare("UPDATE service_requests SET status = ? WHERE id = ?")->execute([$newStatus, $serviceRequestId]);

        // Notify the contractor
        $cStmt = $db->prepare("SELECT user_id FROM contractors WHERE id = ?");
        $cStmt->execute([$request['contractor_id']]);
        $contractor = $cStmt->fetch();
        if ($contractor) {
            $notifTitle = $paymentStage === 'deposit' ? 'Deposit Received – Start Work' : 'Full Balance Paid – Please Finish Up';
            $notifMsg = $paymentStage === 'deposit'
                ? "The client has paid the 20% deposit (KES " . number_format($amount, 2) . ") for '{$request['title']}'. You can now begin work."
                : "The client has paid the remaining 80% balance (KES " . number_format($amount, 2) . ") for '{$request['title']}'. Please complete the project.";
            createNotification($contractor['user_id'], $notifTitle, $notifMsg, 'payment');
        }

        jsonResponse([
            'message' => 'Payment initiated. Please complete the M-Pesa prompt on your phone.',
            'checkout_id' => $result['CheckoutRequestID'],
            'new_status' => $newStatus
        ]);
    } else {
        jsonResponse([
            'error' => 'Mpesa request failed. Please try again.',
            'details' => $result
        ], 500);
    }

} catch (Exception $e) {
    jsonResponse(['error' => 'Payment failed: ' . $e->getMessage()], 500);
}
