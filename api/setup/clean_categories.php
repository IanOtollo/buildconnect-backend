<?php
require_once __DIR__ . '/../../config/database.php';

try {
    $db = getDBConnection();

    // 1. Define Standard Categories
    $standards = [
        ['name' => 'Plumbing', 'description' => 'Piping, fixtures, and water systems'],
        ['name' => 'Electrical', 'description' => 'Wiring, lighting, and power systems'],
        ['name' => 'Carpentry', 'description' => 'Woodwork, roofing, and furniture'],
        ['name' => 'Masonry', 'description' => 'Brickwork, stone, and concrete'],
        ['name' => 'Painting', 'description' => 'Interior and exterior finishes'],
        ['name' => 'HVAC', 'description' => 'Heating, ventilation, and air conditioning'],
        ['name' => 'Roofing', 'description' => 'Roof installation and repair'],
        ['name' => 'Landscaping', 'description' => 'Garden design and maintenance'],
        ['name' => 'Interior Design', 'description' => 'Space planning and aesthetics'],
        ['name' => 'General Contracting', 'description' => 'Full project management']
    ];

    $db->beginTransaction();

    // 2. Clear current categories table
    $db->exec("DELETE FROM categories");

    // 3. Insert standards
    $stmt = $db->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
    foreach ($standards as $s) {
        $stmt->execute([$s['name'], $s['description']]);
    }

    // 4. Migrate existing contractors to standards (Simple fuzzy matching)
    $contractors = $db->query("SELECT id, category FROM contractors")->fetchAll();

    foreach ($contractors as $c) {
        $oldCat = strtolower($c['category']);
        $newCat = 'General Contracting'; // Default

        if (strpos($oldCat, 'plum') !== false)
            $newCat = 'Plumbing';
        elseif (strpos($oldCat, 'elect') !== false)
            $newCat = 'Electrical';
        elseif (strpos($oldCat, 'carp') !== false)
            $newCat = 'Carpentry';
        elseif (strpos($oldCat, 'mason') !== false || strpos($oldCat, 'build') !== false)
            $newCat = 'Masonry';
        elseif (strpos($oldCat, 'paint') !== false)
            $newCat = 'Painting';
        elseif (strpos($oldCat, 'roof') !== false)
            $newCat = 'Roofing';
        elseif (strpos($oldCat, 'land') !== false)
            $newCat = 'Landscaping';
        elseif (strpos($oldCat, 'inter') !== false || strpos($oldCat, 'design') !== false)
            $newCat = 'Interior Design';

        $update = $db->prepare("UPDATE contractors SET category = ? WHERE id = ?");
        $update->execute([$newCat, $c['id']]);
    }

    $db->commit();
    echo "Successfully standardized categories and migrated " . count($contractors) . " contractors.";

}
catch (Exception $e) {
    if ($db)
        $db->rollBack();
    echo "Error: " . $e->getMessage();
}
