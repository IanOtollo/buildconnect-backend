<?php
require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
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
    
    $contractorId = $contractor['id'];
    
    // Check if files were uploaded
    if (empty($_FILES)) {
        jsonResponse(['error' => 'No files uploaded'], 400);
    }
    
    $uploadedFiles = [];
    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    
    // Create uploads directory if it doesn't exist
    $uploadDir = UPLOAD_DIR . 'documents/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    foreach ($_FILES as $documentType => $file) {
        // Validate file type
        if (!in_array($file['type'], $allowedTypes)) {
            jsonResponse(['error' => "Invalid file type for $documentType"], 400);
        }
        
        // Validate file size
        if ($file['size'] > MAX_FILE_SIZE) {
            jsonResponse(['error' => "File size too large for $documentType"], 400);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $contractorId . '_' . $documentType . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            jsonResponse(['error' => "Failed to upload $documentType"], 500);
        }
        
        // Save to database
        $stmt = $db->prepare("INSERT INTO contractor_documents (contractor_id, document_type, file_path) VALUES (?, ?, ?)");
        $stmt->execute([$contractorId, $documentType, 'uploads/documents/' . $filename]);
        
        $uploadedFiles[$documentType] = 'uploads/documents/' . $filename;
    }
    
    jsonResponse([
        'message' => 'Documents uploaded successfully',
        'files' => $uploadedFiles
    ], 201);
    
} catch (PDOException $e) {
    jsonResponse(['error' => 'Upload failed'], 500);
}
