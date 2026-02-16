<?php
require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Require contractor authentication
$user = requireAuth(['contractor']);

try {
    $db = getDBConnection();

    // Get contractor ID
    $stmt = $db->prepare("SELECT id FROM contractors WHERE user_id = ?");
    $stmt->execute([$user['user_id']]);
    $contractor = $stmt->fetch();

    if (!$contractor) {
        jsonResponse(['error' => 'Contractor profile not found'], 404);
    }

    // Get PENDING requests assigned to this contractor
    $stmt = $db->prepare("
        SELECT sr.*, u.full_name as client_name, u.phone as client_phone, u.email as client_email
        FROM service_requests sr
        JOIN users u ON sr.client_id = u.id
        WHERE sr.contractor_id = ? AND sr.status = 'pending'
        ORDER BY sr.created_at DESC
    ");
    $stmt->execute([$contractor['id']]);
    $requests = $stmt->fetchAll();

    jsonResponse($requests);


}
catch (PDOException $e) {
    jsonResponse(['error' => 'Failed to fetch pending assignments'], 500);
}
