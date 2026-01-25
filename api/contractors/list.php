<?php
require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

try {
    $db = getDBConnection();
    
    // Get query parameters
    $category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : null;
    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : null;
    
    // Build query
    $query = "SELECT c.*, u.full_name, u.email, u.phone 
              FROM contractors c 
              JOIN users u ON c.user_id = u.id 
              WHERE c.status = 'approved'";
    
    $params = [];
    
    if ($category) {
        $query .= " AND c.category = ?";
        $params[] = $category;
    }
    
    if ($search) {
        $query .= " AND (c.business_name LIKE ? OR u.full_name LIKE ? OR c.location LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $query .= " ORDER BY c.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $contractors = $stmt->fetchAll();
    
    // Get portfolio images for each contractor
    foreach ($contractors as &$contractor) {
        $stmt = $db->prepare("SELECT * FROM portfolio WHERE contractor_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$contractor['id']]);
        $contractor['portfolio'] = $stmt->fetchAll();
    }
    
    jsonResponse([
        'contractors' => $contractors,
        'count' => count($contractors)
    ]);
    
} catch (PDOException $e) {
    jsonResponse(['error' => 'Failed to fetch contractors'], 500);
}
