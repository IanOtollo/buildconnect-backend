<?php
require_once __DIR__ . '/../../config/database.php';

$db = getDBConnection();

try {
    // Add wallet_balance to users if it doesn't exist
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS wallet_balance DECIMAL(15, 2) DEFAULT 0.00 AFTER phone");
    echo "✅ Added wallet_balance column to users table (if it didn't exist).\n";

    // Create transactions table
    $db->exec("CREATE TABLE IF NOT EXISTS transactions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        amount DECIMAL(15, 2) NOT NULL,
        transaction_type ENUM('deposit', 'withdrawal', 'escrow_lock', 'escrow_release', 'payment', 'refund') NOT NULL,
        status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
        reference_number VARCHAR(100) UNIQUE,
        description TEXT,
        metadata JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_type (user_id, transaction_type),
        INDEX idx_reference (reference_number),
        INDEX idx_status (status)
    )");
    echo "✅ Created transactions table (if it didn't exist).\n";

    jsonResponse(['message' => 'Database updated successfully']);
}
catch (PDOException $e) {
    jsonResponse(['error' => 'Database update failed: ' . $e->getMessage()], 500);
}
