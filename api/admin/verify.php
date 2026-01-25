<?php
require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Require admin authentication
$user = requireAuth(['admin']);

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$error = validateRequired($data, ['contractor_id', 'action']);
if ($error) {
    jsonResponse(['error' => $error], 400);
}

$contractorId = intval($data['contractor_id']);
$action = sanitizeInput($data['action']);

if (!in_array($action, ['approve', 'reject'])) {
    jsonResponse(['error' => 'Invalid action'], 400);
}

try {
    $db = getDBConnection();
    
    // Get contractor details
    $stmt = $db->prepare("SELECT c.*, u.full_name, u.email FROM contractors c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
    $stmt->execute([$contractorId]);
    $contractor = $stmt->fetch();
    
    if (!$contractor) {
        jsonResponse(['error' => 'Contractor not found'], 404);
    }
    
    if ($contractor['status'] !== 'pending') {
        jsonResponse(['error' => 'Contractor already processed'], 400);
    }
    
    // Update status
    if ($action === 'approve') {
        $stmt = $db->prepare("UPDATE contractors SET status = 'approved' WHERE id = ?");
        $stmt->execute([$contractorId]);
        
        // Send notification to contractor
        createNotification(
            $contractor['user_id'],
            'Application Approved',
            'Congratulations! Your contractor application has been approved. You can now start receiving service requests.',
            'approval'
        );
        
        $message = 'Contractor approved successfully';
    } else {
        $rejectionReason = sanitizeInput($data['reason'] ?? 'Application did not meet requirements');
        
        $stmt = $db->prepare("UPDATE contractors SET status = 'rejected', rejection_reason = ? WHERE id = ?");
        $stmt->execute([$rejectionReason, $contractorId]);
        
        // Send notification to contractor
        createNotification(
            $contractor['user_id'],
            'Application Rejected',
            "Your application was rejected. Reason: $rejectionReason",
            'rejection'
        );
        
        $message = 'Contractor rejected';
    }
    
    jsonResponse(['message' => $message]);
    
} catch (PDOException $e) {
    jsonResponse(['error' => 'Verification failed'], 500);
}
