<?php
/**
 * Enhanced Application Submission API
 * Business of Ghanaian Fashion Summit 2025
 * 
 * POST /api/submit_application.php
 */

define('BOGF_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/email.php';

// Set CORS headers
corsHeaders();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['message' => 'Method not allowed'], 405);
}

// Rate limiting
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!checkRateLimit($clientIP, 10, 3600)) { // 10 submissions per hour
    jsonResponse([
        'message' => 'Rate limit exceeded. Please wait before submitting again.',
        'retry_after' => 3600
    ], 429);
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    jsonResponse(['message' => 'Invalid or expired CSRF token'], 403);
}

try {
    // Validate and sanitize input data
    $applicationData = validateApplicationData($_POST);
    
    // Check for duplicate submissions (same email within 24 hours)
    if (isDuplicateApplication($applicationData['email'])) {
        jsonResponse([
            'message' => 'You have already submitted an application in the last 24 hours. Please check your email for confirmation.'
        ], 409);
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Insert application
        $applicationId = insertApplication($db, $applicationData);
        
        // Send confirmation email to applicant
        $emailSent = sendApplicationConfirmationEmail($applicationData, $applicationId);
        
        // Send admin notification
        sendAdminNotificationEmail($applicationData, $applicationId);
        
        // Log the submission
        error_log("New application submitted: ID $applicationId, Email: {$applicationData['email']}");
        
        // Commit transaction
        $db->commit();
        
        // Success response
        jsonResponse([
            'message' => 'Application submitted successfully!',
            'application_id' => $applicationId,
            'email_sent' => $emailSent,
            'data' => [
                'applicant_name' => $applicationData['first_name'] . ' ' . $applicationData['last_name'],
                'email' => $applicationData['email'],
                'application_type' => $applicationData['application_type'],
                'status' => 'pending'
            ]
        ], 201);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (ValidationException $e) {
    jsonResponse([
        'message' => 'Validation error',
        'errors' => $e->getErrors()
    ], 400);
    
} catch (Exception $e) {
    error_log("Application submission error: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
    
    jsonResponse([
        'message' => DEBUG_MODE ? $e->getMessage() : 'Application submission failed. Please try again later.'
    ], 500);
}

/**
 * Custom validation exception
 */
class ValidationException extends Exception {
    private $errors = [];
    
    public function __construct($errors) {
        $this->errors = $errors;
        parent::__construct('Validation failed');
    }
    
    public function getErrors() {
        return $this->errors;
    }
}

/**
 * Validate and sanitize application data
 */
function validateApplicationData($data) {
    $errors = [];
    $cleaned = [];
    
    // Required fields
    $requiredFields = [
        'first_name' => 'First name is required',
        'last_name' => 'Last name is required',
        'email' => 'Email address is required',
        'phone' => 'Phone number is required',
        'country' => 'Country is required',
        'organization' => 'Organization is required',
        'job_title' => 'Job title is required',
        'industry_sector' => 'Industry sector is required',
        'years_experience' => 'Years of experience is required',
        'application_type' => 'Application type is required',
        'how_heard_about' => 'Please tell us how you heard about us',
        'motivation' => 'Motivation is required',
        'expectations' => 'Expectations are required'
    ];
    
    // Validate required fields
    foreach ($requiredFields as $field => $message) {
        if (empty($data[$field])) {
            $errors[$field] = $message;
        } else {
            $cleaned[$field] = sanitizeInput($data[$field]);
        }
    }
    
    // Validate email format
    if (!empty($data['email'])) {
        if (!validateEmail($data['email'])) {
            $errors['email'] = 'Please enter a valid email address';
        } else {
            $cleaned['email'] = strtolower(trim($data['email']));
        }
    }
    
    // Validate phone number
    if (!empty($data['phone'])) {
        $phone = formatGhanaianPhone($data['phone']);
        if (strlen(preg_replace('/\D/', '', $phone)) < 10) {
            $errors['phone'] = 'Please enter a valid phone number';
        } else {
            $cleaned['phone'] = $phone;
        }
    }
    
    // Validate name lengths
    if (!empty($data['first_name']) && strlen(trim($data['first_name'])) < 2) {
        $errors['first_name'] = 'First name must be at least 2 characters';
    }
    
    if (!empty($data['last_name']) && strlen(trim($data['last_name'])) < 2) {
        $errors['last_name'] = 'Last name must be at least 2 characters';
    }
    
    // Validate textarea minimum lengths
    $textareaFields = ['motivation', 'expectations'];
    foreach ($textareaFields as $field) {
        if (!empty($data[$field]) && strlen(trim($data[$field])) < 50) {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be at least 50 characters';
        }
    }
    
    // Validate select field values
    $selectValidations = [
        'application_type' => ['attendee', 'student', 'speaker', 'sponsor', 'media'],
        'industry_sector' => [
            'Fashion Design', 'Retail', 'Manufacturing', 'Media & Marketing',
            'Investment & Finance', 'Education', 'Government', 'Non-profit', 'Other'
        ],
        'years_experience' => ['0-2', '3-5', '6-10', '11-15', '16+'],
        'how_heard_about' => [
            'Website', 'Social Media', 'Email Newsletter', 'Word of Mouth',
            'Partner Organization', 'Media Coverage', 'Other'
        ]
    ];
    
    foreach ($selectValidations as $field => $validOptions) {
        if (!empty($data[$field]) && !in_array($data[$field], $validOptions)) {
            $errors[$field] = 'Invalid ' . str_replace('_', ' ', $field) . ' selected';
        }
    }
    
    // Optional fields
    $optionalFields = ['city', 'dietary_requirements', 'accessibility_needs'];
    foreach ($optionalFields as $field) {
        $cleaned[$field] = !empty($data[$field]) ? sanitizeInput($data[$field]) : null;
    }
    
    // Boolean fields
    $cleaned['marketing_consent'] = !empty($data['marketing_consent']) ? 1 : 0;
    
    // Validate privacy consent
    if (empty($data['privacy_consent'])) {
        $errors['privacy_consent'] = 'You must agree to the Privacy Policy and Terms of Service';
    }
    
    if (!empty($errors)) {
        throw new ValidationException($errors);
    }
    
    return $cleaned;
}

/**
 * Check for duplicate applications
 */
function isDuplicateApplication($email) {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM summit_applications 
            WHERE email = ? AND applied_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    } catch (Exception $e) {
        error_log("Error checking duplicate application: " . $e->getMessage());
        return false; // Fail open
    }
}

/**
 * Insert application into database
 */
function insertApplication($db, $applicationData) {
    $stmt = $db->prepare("
        INSERT INTO summit_applications (
            first_name, last_name, email, phone, country, city,
            organization, job_title, industry_sector, years_experience,
            application_type, motivation, expectations, 
            dietary_requirements, accessibility_needs, how_heard_about,
            marketing_consent, status, applied_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW()
        )
    ");
    
    $stmt->execute([
        $applicationData['first_name'],
        $applicationData['last_name'],
        $applicationData['email'],
        $applicationData['phone'],
        $applicationData['country'],
        $applicationData['city'],
        $applicationData['organization'],
        $applicationData['job_title'],
        $applicationData['industry_sector'],
        $applicationData['years_experience'],
        $applicationData['application_type'],
        $applicationData['motivation'],
        $applicationData['expectations'],
        $applicationData['dietary_requirements'],
        $applicationData['accessibility_needs'],
        $applicationData['how_heard_about'],
        $applicationData['marketing_consent']
    ]);
    
    return $db->lastInsertId();
}

?>
