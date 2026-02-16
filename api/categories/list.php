<?php
require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

try {
    $db = getDBConnection();

    // Get all categories
    $stmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll();

    // Get contractor count for each category and map icons
    $icons = [
        'Plumbing' => 'FiDroplet',
        'Electrical' => 'FiZap',
        'Carpentry' => 'FiHome',
        'Masonry' => 'FiLayers',
        'Painting' => 'FiEdit3',
        'HVAC' => 'FiWind',
        'Roofing' => 'FiArrowUpCircle',
        'Landscaping' => 'FiSun',
        'Interior Design' => 'FiLayout',
        'General Contracting' => 'FiBriefcase'
    ];

    foreach ($categories as &$category) {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM contractors WHERE category = ? AND status = 'approved'");
        $stmt->execute([$category['name']]);
        $category['contractor_count'] = $stmt->fetch()['count'];
        $category['icon'] = $icons[$category['name']] ?? 'FiTool';
    }

    jsonResponse($categories);


}
catch (PDOException $e) {
    jsonResponse(['error' => 'Failed to fetch categories'], 500);
}
