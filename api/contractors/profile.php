<?php
require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$contractorId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($contractorId <= 0) {
    jsonResponse(['error' => 'Invalid contractor ID'], 400);
}

try {
    $db = getDBConnection();
    
    // Get contractor details
    $stmt = $db->prepare("
        SELECT c.*, u.full_name, u.email, u.phone 
        FROM contractors c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.id = ? AND c.status = 'approved'
    ");
    $stmt->execute([$contractorId]);
    $contractor = $stmt->fetch();
    
    if (!$contractor) {
        jsonResponse(['error' => 'Contractor not found'], 404);
    }
    
    // Get portfolio
    $stmt = $db->prepare("SELECT * FROM portfolio WHERE contractor_id = ? ORDER BY created_at DESC");
    $stmt->execute([$contractorId]);
    $contractor['portfolio'] = $stmt->fetchAll();
    
    // Get completed requests count
    $stmt = $db->prepare("SELECT COUNT(*) as completed_jobs FROM service_requests WHERE contractor_id = ? AND status = 'completed'");
    $stmt->execute([$contractorId]);
    $contractor['completed_jobs'] = $stmt->fetch()['completed_jobs'];
    
    jsonResponse($contractor);
    
} catch (PDOException $e) {
    jsonResponse(['error' => 'Failed to fetch contractor profile'], 500);
}
