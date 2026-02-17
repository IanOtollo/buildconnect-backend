<?php
// BuildConnect Database Migration
// Use this file to update your database schema in production
// Access via: https://your-railway-url.up.railway.app/migrate-db.php

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain');

try {
    $db = getDBConnection();
    echo "✅ Connected to database.\n\n";

    $migrations = [
        "ALTER TABLE service_requests ADD COLUMN IF NOT EXISTS urgency ENUM('low', 'medium', 'high') DEFAULT 'medium' AFTER location",
        "ALTER TABLE service_requests ADD COLUMN IF NOT EXISTS budget DECIMAL(10, 2) DEFAULT 0.00 AFTER urgency",
        "ALTER TABLE service_requests ADD COLUMN IF NOT EXISTS estimated_duration VARCHAR(100) AFTER budget"
    ];

    foreach ($migrations as $sql) {
        try {
            $db->exec($sql);
            echo "✅ Successfully executed: $sql\n";
        } catch (PDOException $e) {
            // Some MySQL versions don't support ADD COLUMN IF NOT EXISTS
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "ℹ️  Column already exists, skipping: $sql\n";
            } else {
                echo "⚠️  Migration failed: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "\n🎉 Database migration complete!\n";
    echo "Please delete this file after running it.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
