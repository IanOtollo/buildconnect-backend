<?php
require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Require admin authentication
$user = requireAuth(['admin']);

try {
    $db = getDBConnection();
    
    // Get pending contractors
    $stmt = $db->prepare("
        SELECT c.*, u.full_name, u.email, u.phone 
        FROM contractors c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.status = 'pending' 
        ORDER BY c.created_at DESC
    ");
    $stmt->execute();
    $pendingContractors = $stmt->fetchAll();
    
    // Get all contractors with documents
    foreach ($pendingContractors as &$contractor) {
        $stmt = $db->prepare("SELECT * FROM contractor_documents WHERE contractor_id = ?");
        $stmt->execute([$contractor['id']]);
        $contractor['documents'] = $stmt->fetchAll();
    }
    
    // Get stats
    $stmt = $db->query("SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'client') as total_clients,
        (SELECT COUNT(*) FROM contractors WHERE status = 'approved') as approved_contractors,
        (SELECT COUNT(*) FROM contractors WHERE status = 'pending') as pending_contractors,
        (SELECT COUNT(*) FROM service_requests) as total_requests,
        (SELECT COUNT(*) FROM service_requests WHERE status = 'completed') as completed_requests
    ");
    $stats = $stmt->fetch();
    
    jsonResponse([
        'pending_contractors' => $pendingContractors,
        'stats' => $stats
    ]);
    
} catch (PDOException $e) {
    jsonResponse(['error' => 'Failed to fetch dashboard data'], 500);
}
