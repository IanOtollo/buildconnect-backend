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
$reference = 'BuildConnect_' . time();
$description = 'Payment for BuildConnect service';

try {
    $mpesa = new MpesaService();
    $result = $mpesa->stkPush($phone, $amount, $reference, $description);

    if (isset($result['ResponseCode']) && $result['ResponseCode'] === '0') {
        $db = getDBConnection();
        $stmt = $db->prepare("INSERT INTO transactions (user_id, amount, transaction_type, status, reference_number, description) VALUES (?, ?, 'deposit', 'pending', ?, 'M-Pesa Wallet Deposit')");
        $stmt->execute([$user['id'], $amount, $result['CheckoutRequestID']]);

        jsonResponse([
            'message' => 'STK Push initiated successfully. Please check your phone.',
            'checkout_id' => $result['CheckoutRequestID']
        ]);
    }
    else {
        jsonResponse([
            'error' => 'Mpesa request failed',
            'details' => $result
        ], 500);
    }


}
catch (Exception $e) {
    jsonResponse(['error' => 'Mpesa integration failed: ' . $e->getMessage()], 500);
}
