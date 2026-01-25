<?php
require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Require authentication
$user = requireAuth(['client', 'contractor']);

try {
    $db = getDBConnection();
    
    if ($user['role'] === 'client') {
        // Get requests created by this client
        $stmt = $db->prepare("
            SELECT sr.*, c.business_name, u.full_name as contractor_name, u.phone as contractor_phone, u.email as contractor_email
            FROM service_requests sr
            LEFT JOIN contractors c ON sr.contractor_id = c.id
            LEFT JOIN users u ON c.user_id = u.id
            WHERE sr.client_id = ?
            ORDER BY sr.created_at DESC
        ");
        $stmt->execute([$user['user_id']]);
    } else if ($user['role'] === 'contractor') {
        // Get contractor ID
        $stmt = $db->prepare("SELECT id FROM contractors WHERE user_id = ?");
        $stmt->execute([$user['user_id']]);
        $contractor = $stmt->fetch();
        
        if (!$contractor) {
            jsonResponse(['error' => 'Contractor profile not found'], 404);
        }
        
        // Get requests assigned to this contractor
        $stmt = $db->prepare("
            SELECT sr.*, u.full_name as client_name, u.phone as client_phone, u.email as client_email
            FROM service_requests sr
            JOIN users u ON sr.client_id = u.id
            WHERE sr.contractor_id = ?
            ORDER BY sr.created_at DESC
        ");
        $stmt->execute([$contractor['id']]);
    }
    
    $requests = $stmt->fetchAll();
    
    jsonResponse([
        'requests' => $requests,
        'count' => count($requests)
    ]);
    
} catch (PDOException $e) {
    jsonResponse(['error' => 'Failed to fetch service requests'], 500);
}
