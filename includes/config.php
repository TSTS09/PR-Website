<?php
/**
 * Enhanced Configuration File for Fashion Summit Website
 * Business of Ghanaian Fashion (BoGF) - 2025
 */

// Prevent direct access
if (!defined('BOGF_ACCESS')) {
    die('Direct access not permitted');
}

// Environment Configuration
define('ENVIRONMENT', getenv('ENVIRONMENT') ?: 'development'); // development, staging, production
define('DEBUG_MODE', ENVIRONMENT === 'development');
define('VERSION', '2.0.0');

// Site Configuration
define('SITE_NAME', 'Business of Ghanaian Fashion');
define('SITE_URL', getenv('SITE_URL') ?: 'https://fashionnexusghana.com');
define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: 'info@fashionnexusghana.com');
define('CONTACT_EMAIL', 'info@fashionnexusghana.com');
define('CONTACT_PHONE', '+233 24 123 4567');
define('CONTACT_WHATSAPP', '+233 24 123 4567');

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'bogf_website');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Security Configuration
define('SESSION_TIMEOUT', 3600 * 2); // 2 hours
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes
define('CSRF_TOKEN_EXPIRE', 3600); // 1 hour
define('API_RATE_LIMIT', 100); // requests per hour per IP

// File Upload Configuration
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Application Settings
define('SUMMIT_DATE', '2025-10-16');
define('SUMMIT_VENUE', 'Kempinski Hotel Gold Coast City, Accra');
define('MAX_ATTENDEES', 300);
define('REGISTRATION_OPEN', true);
define('EARLY_BIRD_DEADLINE', '2025-08-15');

// Pricing (in USD)
define('REGULAR_PRICE', 150);
define('EARLY_BIRD_PRICE', 120);
define('STUDENT_PRICE', 75);

// Error Reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Timezone
date_default_timezone_set('Africa/Accra');

// Enhanced Database Singleton Class
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true, // Connection pooling
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Set timezone for this connection
            $this->connection->exec("SET time_zone = '+00:00'");
            
        } catch (PDOException $e) {
            $this->logError("Database Connection Failed: " . $e->getMessage());
            
            if (DEBUG_MODE) {
                die('Database Connection Failed: ' . $e->getMessage());
            } else {
                die('Database connection failed. Please try again later.');
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
    
    private function logError($message) {
        error_log("[" . date('Y-m-d H:i:s') . "] DATABASE ERROR: " . $message . PHP_EOL, 3, __DIR__ . '/../logs/database.log');
    }
    
    // Prevent cloning and unserialization
    private function __clone() {}
    public function __wakeup() {}
}

// Enhanced Utility Functions
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        startSecureSession();
    }
    
    $token = generateToken();
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_token_time'] = time();
    
    return $token;
}

function validateCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        startSecureSession();
    }
    
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Check if token has expired
    if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_EXPIRE) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Enhanced logging function
function logAdminActivity($adminId, $action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            INSERT INTO admin_activity_log 
            (admin_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $adminId,
            $action,
            $tableName,
            $recordId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to log admin activity: " . $e->getMessage());
        return false;
    }
}

// Enhanced JSON response function
function jsonResponse($data, $statusCode = 200, $headers = []) {
    // Security headers
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    
    // Additional headers
    foreach ($headers as $key => $value) {
        header("$key: $value");
    }
    
    http_response_code($statusCode);
    
    $response = [
        'success' => $statusCode >= 200 && $statusCode < 300,
        'timestamp' => date('c'),
        'data' => $data
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// CORS headers function
function corsHeaders() {
    $allowedOrigins = DEBUG_MODE ? ['*'] : [SITE_URL];
    
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
        header("Access-Control-Allow-Origin: " . ($origin ?: '*'));
    }
    
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token");
    header("Access-Control-Allow-Credentials: true");
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// Enhanced session management
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', !DEBUG_MODE);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        session_start();
        
        // Regenerate session ID on login
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
        }
    }
}

function isAdminLoggedIn() {
    startSecureSession();
    
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['login_time'])) {
        return false;
    }
    
    // Check session timeout
    if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
        destroyAdminSession();
        return false;
    }
    
    // Update login time
    $_SESSION['login_time'] = time();
    
    return true;
}

function destroyAdminSession() {
    startSecureSession();
    
    // Clear all session data
    $_SESSION = [];
    
    // Delete session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        if (isAjaxRequest()) {
            jsonResponse(['message' => 'Authentication required'], 401);
        } else {
            header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    }
}

function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function getCurrentAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

function getCurrentAdmin() {
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    static $admin = null;
    
    if ($admin === null) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT id, username, email, first_name, last_name, role, is_active, last_login 
                FROM admin_users 
                WHERE id = ? AND is_active = 1
            ");
            $stmt->execute([getCurrentAdminId()]);
            $admin = $stmt->fetch();
        } catch (Exception $e) {
            error_log("Failed to get current admin: " . $e->getMessage());
            return null;
        }
    }
    
    return $admin;
}

// Rate limiting function
function checkRateLimit($identifier, $maxRequests = API_RATE_LIMIT, $timeWindow = 3600) {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Clean old entries
        $db->prepare("DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)")
           ->execute([$timeWindow]);
        
        // Count current requests
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM rate_limits WHERE identifier = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)");
        $stmt->execute([$identifier, $timeWindow]);
        $count = $stmt->fetch()['count'];
        
        if ($count >= $maxRequests) {
            return false;
        }
        
        // Record this request
        $db->prepare("INSERT INTO rate_limits (identifier, created_at) VALUES (?, NOW())")
           ->execute([$identifier]);
        
        return true;
    } catch (Exception $e) {
        error_log("Rate limiting error: " . $e->getMessage());
        return true; // Fail open
    }
}

// Application-specific functions
function isRegistrationOpen() {
    return REGISTRATION_OPEN && new DateTime() < new DateTime(EARLY_BIRD_DEADLINE);
}

function getCurrentPrice() {
    $now = new DateTime();
    $deadline = new DateTime(EARLY_BIRD_DEADLINE);
    
    return $now <= $deadline ? EARLY_BIRD_PRICE : REGULAR_PRICE;
}

function formatGhanaianPhone($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/\D/', '', $phone);
    
    // Handle different formats
    if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
        // Convert 0XXXXXXXXX to +233XXXXXXXXX
        return '+233' . substr($phone, 1);
    } elseif (strlen($phone) === 9) {
        // Add +233 prefix
        return '+233' . $phone;
    } elseif (strlen($phone) === 12 && substr($phone, 0, 3) === '233') {
        // Add + prefix
        return '+' . $phone;
    }
    
    return $phone;
}

// Auto-create required directories
$requiredDirs = [
    __DIR__ . '/../logs',
    __DIR__ . '/../uploads',
    __DIR__ . '/../uploads/speakers',
    __DIR__ . '/../temp'
];

foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Initialize error log files
$logFiles = [
    __DIR__ . '/../logs/error.log',
    __DIR__ . '/../logs/database.log',
    __DIR__ . '/../logs/security.log'
];

foreach ($logFiles as $logFile) {
    if (!file_exists($logFile)) {
        touch($logFile);
        chmod($logFile, 0644);
    }
}

?>