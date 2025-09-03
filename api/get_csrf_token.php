<?php
/**
 * CSRF Token Generation API
 * 
 * GET /api/get_csrf_token.php
 */

define('BOGF_ACCESS', true);
require_once '../includes/config.php';

// Set CORS headers
corsHeaders();

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['message' => 'Method not allowed'], 405);
}

try {
    $token = generateCSRFToken();
    
    jsonResponse([
        'csrf_token' => $token,
        'expires_in' => CSRF_TOKEN_EXPIRE
    ]);
    
} catch (Exception $e) {
    error_log("CSRF token generation error: " . $e->getMessage());
    
    jsonResponse([
        'message' => 'Failed to generate CSRF token'
    ], 500);
}
?>