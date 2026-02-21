<?php
require_once __DIR__ . '/../../config/database.php';

// Mpesa Callback handling
$callbackData = file_get_contents('php://input');
$logFile = __DIR__ . '/../../logs/mpesa_callback.log';

if (!file_exists(__DIR__ . '/../../logs')) {
    mkdir(__DIR__ . '/../../logs', 0777, true);
}

// Log the callback for debugging
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] " . $callbackData . PHP_EOL, FILE_APPEND);

$data = json_decode($callbackData, true);

if (!$data) {
    echo json_encode(["ResultCode" => 1, "ResultDesc" => "Invalid Data"]);
    exit;
}

$db = getDBConnection();

try {
    $stkCallback = $data['Body']['stkCallback'];
    $merchantRequestID = $stkCallback['MerchantRequestID'];
    $checkoutRequestID = $stkCallback['CheckoutRequestID'];
    $resultCode = $stkCallback['ResultCode'];
    $resultDesc = $stkCallback['ResultDesc'];

    // Find the pending transaction
    $stmt = $db->prepare("SELECT * FROM transactions WHERE reference_number = ? AND status = 'pending'");
    $stmt->execute([$checkoutRequestID]);
    $transaction = $stmt->fetch();

    if ($transaction) {
        if ($resultCode == 0) {
            // Success
            $db->beginTransaction();

            // Update transaction status
            $updateStmt = $db->prepare("UPDATE transactions SET status = 'completed', metadata = ? WHERE id = ?");
            $updateStmt->execute([$callbackData, $transaction['id']]);

            if ($transaction['transaction_type'] === 'escrow_payment' && $transaction['service_request_id']) {
                // Update service request status
                $srStmt = $db->prepare("UPDATE service_requests SET status = 'paid_escrow' WHERE id = ?");
                $srStmt->execute([$transaction['service_request_id']]);

                // Create notification
                createNotification(
                    $transaction['user_id'],
                    "Escrow Funded",
                    "Your escrow payment of KES " . number_format($transaction['amount'], 2) . " has been securely deposited for your project.",
                    "payment"
                );
            } else {
                // Update user balance for general deposit
                $balanceStmt = $db->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                $balanceStmt->execute([$transaction['amount'], $transaction['user_id']]);

                // Create notification
                createNotification(
                    $transaction['user_id'],
                    "Deposit Successful",
                    "Your deposit of KES " . number_format($transaction['amount'], 2) . " has been credited to your wallet.",
                    "payment"
                );
            }

            $db->commit();
        } else {
            // Failed
            $updateStmt = $db->prepare("UPDATE transactions SET status = 'failed', description = ? WHERE id = ?");
            $updateStmt->execute([$resultDesc, $transaction['id']]);
        }
    }

    echo json_encode(["ResultCode" => 0, "ResultDesc" => "Accepted"]);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Error: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    echo json_encode(["ResultCode" => 1, "ResultDesc" => "Error occurred"]);
}
