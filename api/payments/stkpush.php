<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/MpesaService.php';

// Auth check
$user = authenticate();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

$error = validateRequired($data, ['phone', 'amount']);
if ($error) {
    jsonResponse(['error' => $error], 400);
}

$phone = sanitizeInput($data['phone']);
$amount = floatval($data['amount']);
$serviceRequestId = isset($data['service_request_id']) ? intval($data['service_request_id']) : null;
$reference = 'BuildConnect_' . time();
$description = $serviceRequestId ? 'Escrow Payment for Request #' . $serviceRequestId : 'Payment for BuildConnect service';

try {
    $mpesa = new MpesaService();
    $result = $mpesa->stkPush($phone, $amount, $reference, $description);

    if (isset($result['ResponseCode']) && $result['ResponseCode'] === '0') {
        $db = getDBConnection();
        $transactionType = $serviceRequestId ? 'escrow_payment' : 'deposit';
        $stmt = $db->prepare("INSERT INTO transactions (user_id, service_request_id, amount, transaction_type, status, reference_number, description) VALUES (?, ?, ?, ?, 'pending', ?, ?)");
        $stmt->execute([$user['id'], $serviceRequestId, $amount, $transactionType, $result['CheckoutRequestID'], $description]);

        jsonResponse([
            'message' => 'STK Push initiated successfully. Please check your phone.',
            'checkout_id' => $result['CheckoutRequestID']
        ]);
    } else {
        jsonResponse([
            'error' => 'Mpesa request failed',
            'details' => $result
        ], 500);
    }


} catch (Exception $e) {
    jsonResponse(['error' => 'Mpesa integration failed: ' . $e->getMessage()], 500);
}
