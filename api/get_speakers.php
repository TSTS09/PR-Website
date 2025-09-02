<?php
/**
 * API endpoint to get speakers data
 * GET /api/get_speakers.php
 */

define('BOGF_ACCESS', true);
require_once '../includes/config.php';

// Set CORS headers
corsHeaders();

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get query parameters
    $category = $_GET['category'] ?? 'all';
    $featured_only = isset($_GET['featured']) && $_GET['featured'] === 'true';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    // Build the query
    $whereConditions = ['s.is_active = 1'];
    $params = [];
    
    // Filter by category
    if ($category !== 'all' && !empty($category)) {
        $whereConditions[] = 's.category = ?';
        $params[] = $category;
    }
    
    // Filter by featured
    if ($featured_only) {
        $whereConditions[] = 's.is_featured = 1';
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Count total speakers (for pagination)
    $countQuery = "SELECT COUNT(*) as total FROM speakers s WHERE $whereClause";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $totalCount = $countStmt->fetch()['total'];
    
    // Build main query
    $query = "
        SELECT 
            s.*,
            CONCAT(au.first_name, ' ', au.last_name) as created_by_name
        FROM speakers s
        LEFT JOIN admin_users au ON s.created_by = au.id
        WHERE $whereClause
        ORDER BY s.display_order ASC, s.created_at DESC
    ";
    
    // Add pagination if limit is specified
    if ($limit !== null) {
        $query .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $speakers = $stmt->fetchAll();
    
    // Process speakers data
    $processedSpeakers = array_map(function($speaker) {
        // Ensure image URL is complete
        if ($speaker['image_url'] && !filter_var($speaker['image_url'], FILTER_VALIDATE_URL)) {
            $speaker['image_url'] = SITE_URL . '/' . ltrim($speaker['image_url'], '/');
        }
        
        // Convert boolean fields
        $speaker['is_featured'] = (bool)$speaker['is_featured'];
        $speaker['is_active'] = (bool)$speaker['is_active'];
        
        // Format timestamps
        $speaker['created_at_formatted'] = date('F j, Y', strtotime($speaker['created_at']));
        $speaker['updated_at_formatted'] = date('F j, Y \a\t g:i A', strtotime($speaker['updated_at']));
        
        return $speaker;
    }, $speakers);
    
    // Get category counts for additional info
    $categoryCountQuery = "
        SELECT 
            category,
            COUNT(*) as count
        FROM speakers 
        WHERE is_active = 1 
        GROUP BY category
    ";
    $categoryStmt = $db->prepare($categoryCountQuery);
    $categoryStmt->execute();
    $categoryCounts = $categoryStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Response data
    $response = [
        'success' => true,
        'speakers' => $processedSpeakers,
        'pagination' => [
            'total' => $totalCount,
            'count' => count($processedSpeakers),
            'offset' => $offset,
            'limit' => $limit
        ],
        'category_counts' => $categoryCounts,
        'filters' => [
            'category' => $category,
            'featured_only' => $featured_only
        ]
    ];
    
    jsonResponse($response);
    
} catch (Exception $e) {
    error_log("Error in get_speakers.php: " . $e->getMessage());
    
    jsonResponse([
        'success' => false,
        'message' => DEBUG_MODE ? $e->getMessage() : 'Failed to fetch speakers data'
    ], 500);
}
?>