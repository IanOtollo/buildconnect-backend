<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/GeminiAI.php';

// Auth check
$user = requireAuth(['client']);

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
    $aiResponse = $ai->getProjectEstimate($description, $location);

    if (is_array($aiResponse) && isset($aiResponse['error'])) {
        jsonResponse($aiResponse, 500);
    }

    // Parse the JSON string from AI
    $estimate = json_decode($aiResponse, true);

    if (!$estimate) {
        error_log("AI_PARSE_ERROR: Failed to parse JSON from Gemini. Raw: " . $aiResponse);
        jsonResponse(['error' => 'AI returned malformed data', 'debug' => $aiResponse], 500);
    }

    jsonResponse([
        'estimate' => $estimate
    ]);


} catch (Exception $e) {
    jsonResponse(['error' => 'AI processing failed: ' . $e->getMessage()], 500);
}
