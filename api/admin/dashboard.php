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

    // Get all contractors with documents and structure nested user
    foreach ($pendingContractors as &$contractor) {
        $stmt = $db->prepare("SELECT * FROM contractor_documents WHERE contractor_id = ?");
        $stmt->execute([$contractor['id']]);
        $contractor['documents'] = $stmt->fetchAll();

        // Nest user data to match frontend expectation
        $contractor['user'] = [
            'full_name' => $contractor['full_name'],
            'email' => $contractor['email'],
            'phone' => $contractor['phone']
        ];
    }

    // Get all users for the User Hub
    $stmt = $db->query("SELECT id, email, full_name, phone, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();

    // Get stats
    $stmt = $db->query("SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'client') as total_clients,
        (SELECT COUNT(*) FROM contractors WHERE status = 'approved') as approved_contractors,
        (SELECT COUNT(*) FROM contractors WHERE status = 'pending') as pending_contractors,
        (SELECT COUNT(*) FROM service_requests) as total_requests,
        (SELECT COUNT(*) FROM service_requests WHERE status = 'completed') as completed_requests,
        (SELECT COALESCE(SUM(budget), 0) FROM service_requests) as total_revenue
    ");
    $stats = $stmt->fetch();

    // Generate recent activity (Mocking for now based on actual data)
    $activity = [];

    // Add recent users
    foreach (array_slice($users, 0, 3) as $u) {
        $activity[] = [
            'type' => 'user',
            'title' => 'New ' . ucfirst($u['role']),
            'description' => $u['full_name'] . ' joined the platform',
            'date' => $u['created_at']
        ];
    }

    // Add recent requests
    $stmt = $db->query("SELECT title, created_at FROM service_requests ORDER BY created_at DESC LIMIT 3");
    $recentRequests = $stmt->fetchAll();
    foreach ($recentRequests as $r) {
        $activity[] = [
            'type' => 'request',
            'title' => 'Project Requested',
            'description' => 'New request: ' . $r['title'],
            'date' => $r['created_at']
        ];
    }

    jsonResponse([
        'pending_contractors' => $pendingContractors,
        'stats' => $stats,
        'users' => $users,
        'activity' => $activity
    ]);


} catch (PDOException $e) {
    jsonResponse(['error' => 'Failed to fetch dashboard data'], 500);
}
