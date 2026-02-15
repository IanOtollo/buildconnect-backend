<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/GeminiAI.php';

// Auth check
$user = authenticate();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

$error = validateRequired($data, ['description', 'location']);
if ($error) {
    jsonResponse(['error' => $error], 400);
}

$description = sanitizeInput($data['description']);
$location = sanitizeInput($data['location']);

try {
    $ai = new GeminiAI();
    $estimate = $ai->getProjectEstimate($description, $location);

    if (isset($estimate['error'])) {
        jsonResponse($estimate, 500);
    }

    jsonResponse([
        'estimate' => $estimate
    ]);


}
catch (Exception $e) {
    jsonResponse(['error' => 'AI processing failed: ' . $e->getMessage()], 500);
}
