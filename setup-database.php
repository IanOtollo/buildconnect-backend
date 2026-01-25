<?php
// BuildConnect Database Auto-Setup
// Upload this file to Railway and access it once via browser

header('Content-Type: text/plain');

// Database connection
$host = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$dbname = getenv('MYSQLDATABASE') ?: 'railway';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to MySQL successfully!\n\n";
    
    // Read and execute SQL
    $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('client', 'contractor', 'admin') NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

CREATE TABLE IF NOT EXISTS contractors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    business_name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    years_of_experience INT NOT NULL,
    bio TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    rejection_reason TEXT,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_category (category)
);

CREATE TABLE IF NOT EXISTS contractor_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contractor_id INT NOT NULL,
    document_type ENUM('kra_pin', 'business_permit', 'certificate', 'id_copy') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contractor_id) REFERENCES contractors(id) ON DELETE CASCADE,
    INDEX idx_contractor (contractor_id)
);

CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS portfolio (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contractor_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contractor_id) REFERENCES contractors(id) ON DELETE CASCADE,
    INDEX idx_contractor (contractor_id)
);

CREATE TABLE IF NOT EXISTS service_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    contractor_id INT,
    category VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(255) NOT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
    file_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (contractor_id) REFERENCES contractors(id) ON DELETE SET NULL,
    INDEX idx_client (client_id),
    INDEX idx_contractor (contractor_id),
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read)
);

INSERT IGNORE INTO categories (name, description) VALUES
('Plumbing', 'Water systems, pipes, drainage'),
('Electrical', 'Wiring, installations, repairs'),
('Carpentry', 'Wood work, furniture, installations'),
('Painting', 'Interior and exterior painting'),
('Masonry', 'Brickwork, concrete, construction'),
('Roofing', 'Roof installation and repairs'),
('Landscaping', 'Gardens, lawns, outdoor design'),
('HVAC', 'Heating, ventilation, air conditioning');

INSERT IGNORE INTO users (email, password, role, full_name, phone) VALUES
('admin@buildconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Admin', '+254700000000');
SQL;

    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $conn->exec($statement);
                echo "✅ Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                echo "⚠️  Warning: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n🎉 Database setup complete!\n\n";
    
    // Verify tables
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📋 Created tables:\n";
    foreach ($tables as $table) {
        echo "   - $table\n";
    }
    
    echo "\n✅ You can now delete this setup-database.php file!\n";
    echo "🔐 Default admin login:\n";
    echo "   Email: admin@buildconnect.com\n";
    echo "   Password: admin123\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
