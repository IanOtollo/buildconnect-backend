<?php
// BuildConnect Database Migration
// Use this file to update your database schema in production
// Access via: https://your-railway-url.up.railway.app/migrate-db.php

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain');

try {
    $db = getDBConnection();
    echo "✅ Connected to database.\n\n";

    // Get existing columns
    $stmt = $db->query("DESCRIBE service_requests");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $migrations = [
        'urgency' => "ALTER TABLE service_requests ADD COLUMN urgency ENUM('low', 'medium', 'high') DEFAULT 'medium' AFTER location",
        'budget' => "ALTER TABLE service_requests ADD COLUMN budget DECIMAL(10, 2) DEFAULT 0.00 AFTER urgency",
        'estimated_duration' => "ALTER TABLE service_requests ADD COLUMN estimated_duration VARCHAR(100) AFTER budget"
    ];

    foreach ($migrations as $column => $sql) {
        if (in_array($column, $existingColumns)) {
            echo "ℹ️  Column '$column' already exists, skipping.\n";
            continue;
        }

        try {
            $db->exec($sql);
            echo "✅ Successfully added column: $column\n";
        } catch (PDOException $e) {
            echo "⚠️  Migration failed for '$column': " . $e->getMessage() . "\n";
        }
    }

    echo "\n🎉 Database migration complete!\n";
    echo "Please delete this file after running it.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
