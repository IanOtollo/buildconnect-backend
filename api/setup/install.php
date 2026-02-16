<?php
ob_start();
require_once __DIR__ . '/../../config/database.php';

function runSetup()
{
    try {
        $db = getDBConnection();
        $schemaPath = __DIR__ . '/../../database/schema.sql';

        if (!file_exists($schemaPath)) {
            jsonResponse(['error' => 'Schema file not found'], 500);
        }

        $sql = file_get_contents($schemaPath);

        // Execute schema.sql (split by semicolon to execute individual statements if needed, 
        // but PDO::exec sometimes handles multiple. Best to split.)
        // Simple split logic:
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $stmt) {
            if (!empty($stmt)) {
                $db->exec($stmt);
            }
        }

        echo "✅ Base schema executed.\n";

        // Now apply updates from update-database.php logic
        // Wallet balance
        try {
            $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS wallet_balance DECIMAL(15, 2) DEFAULT 0.00 AFTER phone");
            echo "✅ Wallet balance column checked/added.\n";
        }
        catch (Exception $e) {
            echo "ℹ️ Wallet balance column might already exist.\n";
        }

        // Transactions table
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
        echo "✅ Transactions table checked/created.\n";

        jsonResponse(['message' => 'Database installed and updated successfully']);

    }
    catch (PDOException $e) {
        jsonResponse(['error' => 'Setup failed: ' . $e->getMessage()], 500);
    }
}

runSetup();
