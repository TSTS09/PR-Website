<?php
/**
 * API endpoint to submit summit applications
 * POST /api/submit_application.php
 */

define('BOGF_ACCESS', true);
require_once '../includes/config.php';

// Set CORS headers
corsHeaders();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Rate limiting (simple implementation)
$clientIp = $_SERVER['REMOTE_ADDR'];
$rateLimitKey = "app_submit_$clientIp";

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        jsonResponse(['success' => false, 'message' => 'Invalid JSON data'], 400);
    }
    
    // Validate required fields
    $requiredFields = [
        'first_name' => 'First name',
        'last_name' => 'Last name', 
        'email' => 'Email address',
        'country' => 'Country',
        'city' => 'City',
        'job_title' => 'Job title',
        'industry_sector' => 'Industry sector',
        'years_experience' => 'Years of experience',
        'application_type' => 'Application type',
        'motivation' => 'Motivation',
        'expectations' => 'Expectations',
        'privacy_consent' => 'Privacy consent'
    ];
    
    $errors = [];
    
    foreach ($requiredFields as $field => $label) {
        if (empty($input[$field]) || (is_string($input[$field]) && trim($input[$field]) === '')) {
            $errors[] = "$label is required";
        }
    }
    
    // Validate email format
    if (!empty($input['email']) && !validateEmail($input['email'])) {
        $errors[] = 'Please provide a valid email address';
    }
    
    // Validate text length for critical fields
    if (!empty($input['motivation']) && strlen(trim($input['motivation'])) < 50) {
        $errors[] = 'Please provide at least 50 characters for your motivation';
    }
    
    if (!empty($input['expectations']) && strlen(trim($input['expectations'])) < 50) {
        $errors[] = 'Please provide at least 50 characters for your expectations';
    }
    
    // Check privacy consent
    if (empty($input['privacy_consent']) || $input['privacy_consent'] !== '1') {
        $errors[] = 'You must agree to the Privacy Policy and Terms of Service';
    }
    
    // Return validation errors
    if (!empty($errors)) {
        jsonResponse([
            'success' => false, 
            'message' => 'Please correct the following errors:',
            'errors' => $errors
        ], 422);
    }
    
    // Check for duplicate applications (same email)
    $db = Database::getInstance()->getConnection();
    
    $duplicateCheck = $db->prepare("
        SELECT id, status FROM summit_applications 
        WHERE email = ? AND status NOT IN ('rejected')
        LIMIT 1
    ");
    $duplicateCheck->execute([trim($input['email'])]);
    $existingApplication = $duplicateCheck->fetch();
    
    if ($existingApplication) {
        $statusMessage = $existingApplication['status'] === 'pending' ? 'under review' : $existingApplication['status'];
        jsonResponse([
            'success' => false,
            'message' => "An application with this email address already exists and is currently $statusMessage. Please use a different email address or contact us for assistance."
        ], 409);
    }
    
    // Sanitize input data
    $applicationData = [
        'first_name' => sanitizeInput($input['first_name']),
        'last_name' => sanitizeInput($input['last_name']),
        'email' => strtolower(trim($input['email'])),
        'phone' => !empty($input['phone']) ? sanitizeInput($input['phone']) : null,
        'organization' => !empty($input['organization']) ? sanitizeInput($input['organization']) : null,
        'job_title' => sanitizeInput($input['job_title']),
        'country' => sanitizeInput($input['country']),
        'city' => sanitizeInput($input['city']),
        'industry_sector' => sanitizeInput($input['industry_sector']),
        'years_experience' => (int)$input['years_experience'],
        'application_type' => sanitizeInput($input['application_type']),
        'motivation' => sanitizeInput($input['motivation']),
        'expectations' => sanitizeInput($input['expectations']),
        'contribution' => !empty($input['contribution']) ? sanitizeInput($input['contribution']) : null,
        'dietary_requirements' => !empty($input['dietary_requirements']) ? sanitizeInput($input['dietary_requirements']) : null,
        'accessibility_needs' => !empty($input['accessibility_needs']) ? sanitizeInput($input['accessibility_needs']) : null,
        'how_heard_about' => !empty($input['how_heard_about']) ? sanitizeInput($input['how_heard_about']) : null,
        'marketing_consent' => !empty($input['marketing_consent']) && $input['marketing_consent'] === '1'
    ];
    
    // Insert application into database
    $stmt = $db->prepare("
        INSERT INTO summit_applications (
            first_name, last_name, email, phone, organization, job_title,
            country, city, industry_sector, years_experience, application_type,
            motivation, expectations, contribution, dietary_requirements,
            accessibility_needs, how_heard_about, marketing_consent, status
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending'
        )
    ");
    
    $success = $stmt->execute([
        $applicationData['first_name'],
        $applicationData['last_name'], 
        $applicationData['email'],
        $applicationData['phone'],
        $applicationData['organization'],
        $applicationData['job_title'],
        $applicationData['country'],
        $applicationData['city'],
        $applicationData['industry_sector'],
        $applicationData['years_experience'],
        $applicationData['application_type'],
        $applicationData['motivation'],
        $applicationData['expectations'],
        $applicationData['contribution'],
        $applicationData['dietary_requirements'],
        $applicationData['accessibility_needs'],
        $applicationData['how_heard_about'],
        $applicationData['marketing_consent'] ? 1 : 0
    ]);
    
    if (!$success) {
        throw new Exception('Failed to save application to database');
    }
    
    $applicationId = $db->lastInsertId();
    
    // Add to newsletter if consent given
    if ($applicationData['marketing_consent']) {
        try {
            $newsletterStmt = $db->prepare("
                INSERT IGNORE INTO newsletter_subscribers (email, first_name, last_name, subscription_source)
                VALUES (?, ?, ?, 'summit_application')
            ");
            $newsletterStmt->execute([
                $applicationData['email'],
                $applicationData['first_name'], 
                $applicationData['last_name']
            ]);
        } catch (Exception $e) {
            // Log but don't fail the application
            error_log("Newsletter subscription failed: " . $e->getMessage());
        }
    }
    
    // Send confirmation email (implement based on your email setup)
    try {
        sendApplicationConfirmationEmail($applicationData, $applicationId);
    } catch (Exception $e) {
        // Log but don't fail the application
        error_log("Confirmation email failed: " . $e->getMessage());
    }
    
    // Send admin notification (implement based on your email setup)
    try {
        sendAdminNotificationEmail($applicationData, $applicationId);
    } catch (Exception $e) {
        // Log but don't fail the application
        error_log("Admin notification failed: " . $e->getMessage());
    }
    
    // Success response
    jsonResponse([
        'success' => true,
        'message' => 'Your application has been submitted successfully!',
        'application_id' => $applicationId,
        'data' => [
            'applicant_name' => $applicationData['first_name'] . ' ' . $applicationData['last_name'],
            'email' => $applicationData['email'],
            'application_type' => $applicationData['application_type'],
            'status' => 'pending'
        ]
    ], 201);
    
} catch (Exception $e) {
    error_log("Application submission error: " . $e->getMessage());
    
    jsonResponse([
        'success' => false,
        'message' => DEBUG_MODE ? $e->getMessage() : 'Application submission failed. Please try again later.'
    ], 500);
}

/**
 * Send confirmation email to applicant
 */
function sendApplicationConfirmationEmail($applicationData, $applicationId) {
    if (!defined('SMTP_HOST') || !SMTP_HOST) {
        throw new Exception('Email configuration not set up');
    }
    
    $to = $applicationData['email'];
    $subject = 'Application Received - Business of Ghanaian Fashion Summit 2025';
    
    $body = generateConfirmationEmailBody($applicationData, $applicationId);
    
    // Use your preferred email library (PHPMailer, SwiftMailer, etc.)
    // This is a placeholder for the actual email sending logic
    $headers = [
        'From' => ADMIN_EMAIL,
        'Reply-To' => ADMIN_EMAIL,
        'Content-Type' => 'text/html; charset=UTF-8'
    ];
    
    // For now, just log the email (replace with actual sending)
    error_log("Would send confirmation email to: $to");
    error_log("Subject: $subject");
    
    return true;
}

/**
 * Send admin notification email
 */
function sendAdminNotificationEmail($applicationData, $applicationId) {
    if (!defined('ADMIN_EMAIL') || !ADMIN_EMAIL) {
        return false;
    }
    
    $subject = 'New Summit Application Received - ' . $applicationData['first_name'] . ' ' . $applicationData['last_name'];
    
    $body = "
        <h3>New Summit Application Received</h3>
        <p><strong>Application ID:</strong> $applicationId</p>
        <p><strong>Name:</strong> {$applicationData['first_name']} {$applicationData['last_name']}</p>
        <p><strong>Email:</strong> {$applicationData['email']}</p>
        <p><strong>Organization:</strong> {$applicationData['organization']}</p>
        <p><strong>Application Type:</strong> {$applicationData['application_type']}</p>
        <p><strong>Country:</strong> {$applicationData['country']}</p>
        <p><strong>Industry:</strong> {$applicationData['industry_sector']}</p>
        <p><a href='" . SITE_URL . "/admin/applications.php?view=$applicationId'>Review Application</a></p>
    ";
    
    // For now, just log the email (replace with actual sending)
    error_log("Would send admin notification email");
    
    return true;
}

/**
 * Generate confirmation email body
 */
function generateConfirmationEmailBody($applicationData, $applicationId) {
    $name = $applicationData['first_name'] . ' ' . $applicationData['last_name'];
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Application Confirmation</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #C8102E; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
            .highlight { color: #C8102E; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Business of Ghanaian Fashion</h1>
                <p>Summit 2025 Application Confirmation</p>
            </div>
            
            <div class='content'>
                <h2>Dear $name,</h2>
                
                <p>Thank you for your interest in the Business of Ghanaian Fashion Summit 2025!</p>
                
                <p>We have successfully received your application with the following details:</p>
                
                <ul>
                    <li><strong>Application ID:</strong> <span class='highlight'>$applicationId</span></li>
                    <li><strong>Application Type:</strong> {$applicationData['application_type']}</li>
                    <li><strong>Email:</strong> {$applicationData['email']}</li>
                    <li><strong>Organization:</strong> {$applicationData['organization']}</li>
                    <li><strong>Submission Date:</strong> " . date('F j, Y \a\t g:i A') . "</li>
                </ul>
                
                <h3>What's Next?</h3>
                <ol>
                    <li>Our team will carefully review your application within 2-3 weeks</li>
                    <li>Selected participants will be contacted via email with further instructions</li>
                    <li>If approved, you'll receive payment details to secure your spot</li>
                    <li>Final confirmation and summit materials will be sent after payment</li>
                </ol>
                
                <p><strong>Summit Details:</strong></p>
                <ul>
                    <li><strong>Date:</strong> Thursday, October 16, 2025</li>
                    <li><strong>Venue:</strong> Kempinski Hotel Gold Coast City, Accra</li>
                    <li><strong>Duration:</strong> Full Day Experience</li>
                </ul>
                
                <p>We appreciate your patience during the selection process. Due to limited capacity, we carefully review each application to ensure the right mix of participants for meaningful discussions and networking.</p>
                
                <p>If you have any questions, please don't hesitate to contact us at <a href='mailto:" . ADMIN_EMAIL . "'>" . ADMIN_EMAIL . "</a></p>
                
                <p>Best regards,<br>
                <strong>The BoGF Team</strong><br>
                Fashion Nexus Ghana</p>
            </div>
            
            <div class='footer'>
                <p>&copy; 2025 Business of Ghanaian Fashion. Presented by Fashion Nexus Ghana.</p>
                <p>Building a sustainable future for Ghana's fashion industry.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}
?>