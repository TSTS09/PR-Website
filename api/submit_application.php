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

/**
 * Send confirmation email to applicant
 */
function sendApplicationConfirmationEmail($applicationData, $applicationId) {
    try {
        $emailService = new EmailService();
        
        $to = $applicationData['email'];
        $name = $applicationData['first_name'] . ' ' . $applicationData['last_name'];
        $subject = 'Application Received - Business of Ghanaian Fashion Summit 2025';
        
        $emailData = [
            'applicant_name' => $name,
            'application_id' => $applicationId,
            'application_type' => ucfirst(str_replace('_', ' ', $applicationData['application_type'])),
            'summit_date' => 'October 16, 2025',
            'summit_venue' => 'Kempinski Hotel Gold Coast City, Accra'
        ];
        
        $body = generateConfirmationEmailTemplate($emailData);
        
        return $emailService->sendEmail($to, $subject, $body, $name);
        
    } catch (Exception $e) {
        error_log("Failed to send confirmation email: " . $e->getMessage());
        return false;
    }
}

/**
 * Send admin notification email
 */
function sendAdminNotificationEmail($applicationData, $applicationId) {
    try {
        $emailService = new EmailService();
        
        $subject = 'New Summit Application - ' . $applicationData['first_name'] . ' ' . $applicationData['last_name'];
        
        $emailData = [
            'application_id' => $applicationId,
            'applicant_name' => $applicationData['first_name'] . ' ' . $applicationData['last_name'],
            'email' => $applicationData['email'],
            'phone' => $applicationData['phone'],
            'organization' => $applicationData['organization'],
            'job_title' => $applicationData['job_title'],
            'application_type' => ucfirst(str_replace('_', ' ', $applicationData['application_type'])),
            'country' => $applicationData['country'],
            'industry_sector' => $applicationData['industry_sector'],
            'admin_url' => SITE_URL . '/admin/applications.php?view=' . $applicationId
        ];
        
        $body = generateAdminNotificationTemplate($emailData);
        
        return $emailService->sendEmail(ADMIN_EMAIL, $subject, $body);
        
    } catch (Exception $e) {
        error_log("Failed to send admin notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate confirmation email template
 */
function generateConfirmationEmailTemplate($data) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Application Confirmation</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #C8102E; color: white; padding: 30px; text-align: center; }
            .content { padding: 30px; background: #f9f9f9; }
            .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
            .highlight { color: #C8102E; font-weight: bold; }
            .button { 
                display: inline-block; 
                background: #C8102E; 
                color: white; 
                padding: 12px 25px; 
                text-decoration: none; 
                border-radius: 5px;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Fashion Nexus Ghana</h1>
                <h2>Application Confirmed</h2>
            </div>
            
            <div class='content'>
                <h2>Dear {$data['applicant_name']},</h2>
                
                <p>Thank you for your interest in the <strong>Business of Ghanaian Fashion Summit 2025</strong>!</p>
                
                <p><strong>Application Details:</strong></p>
                <ul>
                    <li><strong>Application ID:</strong> <span class='highlight'>#APP{$data['application_id']}</span></li>
                    <li><strong>Application Type:</strong> {$data['application_type']}</li>
                    <li><strong>Status:</strong> Under Review</li>
                    <li><strong>Submitted:</strong> " . date('F j, Y \a\t g:i A') . "</li>
                </ul>
                
                <p><strong>Summit Information:</strong></p>
                <ul>
                    <li><strong>Date:</strong> {$data['summit_date']}</li>
                    <li><strong>Venue:</strong> {$data['summit_venue']}</li>
                    <li><strong>Time:</strong> 8:00 AM - 6:00 PM GMT</li>
                </ul>
                
                <h3>What's Next?</h3>
                <p>Our team will carefully review your application and get back to you within <strong>48 hours</strong>. If approved, you will receive:</p>
                <ul>
                    <li>Official acceptance notification</li>
                    <li>Payment instructions and options</li>
                    <li>Detailed summit agenda and information</li>
                    <li>Networking and preparation materials</li>
                </ul>
                
                <p>If you have any questions, please don't hesitate to contact us at <a href='mailto:" . ADMIN_EMAIL . "'>" . ADMIN_EMAIL . "</a>.</p>
                
                <a href='" . SITE_URL . "/summit.html' class='button'>View Summit Details</a>
                
                <p>We're excited about the possibility of having you join us in shaping the future of Ghana's fashion industry!</p>
                
                <p><strong>Best regards,</strong><br>
                The Fashion Nexus Ghana Team</p>
            </div>
            
            <div class='footer'>
                <p>&copy; 2025 Fashion Nexus Ghana. All rights reserved.</p>
                <p>This is an automated message. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

/**
 * Generate admin notification template
 */
function generateAdminNotificationTemplate($data) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>New Application Notification</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #C8102E; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .details { background: white; padding: 20px; border-radius: 5px; margin: 20px 0; }
            .button { 
                display: inline-block; 
                background: #C8102E; 
                color: white; 
                padding: 12px 25px; 
                text-decoration: none; 
                border-radius: 5px;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>New Summit Application</h1>
                <p>Application ID: #APP{$data['application_id']}</p>
            </div>
            
            <div class='content'>
                <div class='details'>
                    <h3>Applicant Information</h3>
                    <p><strong>Name:</strong> {$data['applicant_name']}</p>
                    <p><strong>Email:</strong> {$data['email']}</p>
                    <p><strong>Phone:</strong> {$data['phone']}</p>
                    <p><strong>Organization:</strong> {$data['organization']}</p>
                    <p><strong>Job Title:</strong> {$data['job_title']}</p>
                    <p><strong>Country:</strong> {$data['country']}</p>
                    <p><strong>Industry:</strong> {$data['industry_sector']}</p>
                    <p><strong>Application Type:</strong> {$data['application_type']}</p>
                </div>
                
                <p><strong>Submitted:</strong> " . date('F j, Y \a\t g:i A') . "</p>
                
                <a href='{$data['admin_url']}' class='button'>Review Application</a>
                
                <p>Please review this application promptly and respond to the applicant within 48 hours.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

?>

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

<?php
/**
 * Enhanced Email Service Class
 * File: includes/email.php
 */

if (!defined('BOGF_ACCESS')) {
    die('Direct access not permitted');
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer (install via Composer: composer require phpmailer/phpmailer)
require_once __DIR__ . '/../vendor/autoload.php';

class EmailService {
    private $mailer;
    
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->configureSMTP();
    }
    
    private function configureSMTP() {
        try {
            if (SMTP_HOST && SMTP_HOST !== 'localhost') {
                // SMTP configuration
                $this->mailer->isSMTP();
                $this->mailer->Host = SMTP_HOST;
                $this->mailer->SMTPAuth = true;
                $this->mailer->Username = SMTP_USERNAME;
                $this->mailer->Password = SMTP_PASSWORD;
                $this->mailer->SMTPSecure = SMTP_ENCRYPTION;
                $this->mailer->Port = SMTP_PORT;
            } else {
                // Use PHP's mail() function
                $this->mailer->isMail();
            }
            
            // Set default sender
            $this->mailer->setFrom(NOREPLY_EMAIL, SITE_NAME);
            $this->mailer->addReplyTo(ADMIN_EMAIL, SITE_NAME);
            
            // Email settings
            $this->mailer->isHTML(true);
            $this->mailer->CharSet = 'UTF-8';
            
        } catch (Exception $e) {
            error_log("Email configuration error: " . $e->getMessage());
            throw new Exception("Email system configuration failed");
        }
    }
    
    public function sendEmail($to, $subject, $body, $recipientName = null) {
        try {
            // Clear any previous recipients
            $this->mailer->clearAddresses();
            
            // Add recipient
            $this->mailer->addAddress($to, $recipientName);
            
            // Set email content
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            
            // Generate plain text version
            $this->mailer->AltBody = $this->htmlToText($body);
            
            // Send email
            $result = $this->mailer->send();
            
            if ($result) {
                error_log("Email sent successfully to: $to");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendBulkEmail($recipients, $subject, $body) {
        $results = [];
        
        foreach ($recipients as $recipient) {
            $email = is_array($recipient) ? $recipient['email'] : $recipient;
            $name = is_array($recipient) ? $recipient['name'] : null;
            
            $results[] = [
                'email' => $email,
                'sent' => $this->sendEmail($email, $subject, $body, $name)
            ];
            
            // Add delay to avoid overwhelming SMTP server
            usleep(100000); // 0.1 second delay
        }
        
        return $results;
    }
    
    private function htmlToText($html) {
        // Simple HTML to text conversion
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
}

?>