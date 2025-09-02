<?php
/**
 * API endpoint to submit contact messages
 * POST /api/submit_contact.php
 */

define('BOGF_ACCESS', true);
require_once '../includes/config.php';

// Set CORS headers
corsHeaders();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        jsonResponse(['success' => false, 'message' => 'Invalid JSON data'], 400);
    }
    
    // Validate required fields
    $requiredFields = [
        'name' => 'Name',
        'email' => 'Email address',
        'subject' => 'Subject',
        'message' => 'Message'
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
    
    // Validate message length
    if (!empty($input['message']) && strlen(trim($input['message'])) < 10) {
        $errors[] = 'Please provide a more detailed message (at least 10 characters)';
    }
    
    // Return validation errors
    if (!empty($errors)) {
        jsonResponse([
            'success' => false, 
            'message' => 'Please correct the following errors:',
            'errors' => $errors
        ], 422);
    }
    
    // Rate limiting - prevent spam (simple implementation)
    $clientIp = $_SERVER['REMOTE_ADDR'];
    $rateLimitKey = "contact_submit_$clientIp";
    
    // Check if this IP has submitted recently (within 5 minutes)
    // Note: In production, you might use Redis or a more sophisticated rate limiting system
    $lastSubmissionFile = sys_get_temp_dir() . "/$rateLimitKey";
    if (file_exists($lastSubmissionFile)) {
        $lastSubmission = (int)file_get_contents($lastSubmissionFile);
        if (time() - $lastSubmission < 300) { // 5 minutes
            jsonResponse([
                'success' => false,
                'message' => 'Please wait a few minutes before sending another message.'
            ], 429);
        }
    }
    
    // Sanitize input data
    $contactData = [
        'name' => sanitizeInput($input['name']),
        'email' => strtolower(trim($input['email'])),
        'organization' => !empty($input['organization']) ? sanitizeInput($input['organization']) : null,
        'subject' => sanitizeInput($input['subject']),
        'message' => sanitizeInput($input['message'])
    ];
    
    // Insert message into database
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        INSERT INTO contact_messages (
            name, email, organization, subject, message, status
        ) VALUES (
            ?, ?, ?, ?, ?, 'new'
        )
    ");
    
    $success = $stmt->execute([
        $contactData['name'],
        $contactData['email'],
        $contactData['organization'],
        $contactData['subject'],
        $contactData['message']
    ]);
    
    if (!$success) {
        throw new Exception('Failed to save message to database');
    }
    
    $messageId = $db->lastInsertId();
    
    // Send confirmation email to the sender
    try {
        sendContactConfirmationEmail($contactData, $messageId);
    } catch (Exception $e) {
        // Log but don't fail the contact submission
        error_log("Confirmation email failed: " . $e->getMessage());
    }
    
    // Send notification email to admin
    try {
        sendContactAdminNotification($contactData, $messageId);
    } catch (Exception $e) {
        // Log but don't fail the contact submission
        error_log("Admin notification failed: " . $e->getMessage());
    }
    
    // Update rate limiting
    file_put_contents($lastSubmissionFile, time());
    
    // Success response
    jsonResponse([
        'success' => true,
        'message' => 'Your message has been sent successfully! We will get back to you within 24-48 hours.',
        'message_id' => $messageId,
        'data' => [
            'name' => $contactData['name'],
            'email' => $contactData['email'],
            'subject' => $contactData['subject']
        ]
    ], 201);
    
} catch (Exception $e) {
    error_log("Contact submission error: " . $e->getMessage());
    
    jsonResponse([
        'success' => false,
        'message' => DEBUG_MODE ? $e->getMessage() : 'Message submission failed. Please try again later.'
    ], 500);
}

/**
 * Send confirmation email to the person who submitted the contact form
 */
function sendContactConfirmationEmail($contactData, $messageId) {
    if (!defined('SMTP_HOST') || !SMTP_HOST) {
        throw new Exception('Email configuration not set up');
    }
    
    $to = $contactData['email'];
    $subject = 'Message Received - Fashion Nexus Ghana';
    
    $body = generateContactConfirmationEmailBody($contactData, $messageId);
    
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
function sendContactAdminNotification($contactData, $messageId) {
    if (!defined('ADMIN_EMAIL') || !ADMIN_EMAIL) {
        return false;
    }
    
    $subject = 'New Contact Message - ' . $contactData['subject'];
    
    $body = "
        <h3>New Contact Message Received</h3>
        <p><strong>Message ID:</strong> $messageId</p>
        <p><strong>From:</strong> {$contactData['name']} ({$contactData['email']})</p>
        <p><strong>Organization:</strong> " . ($contactData['organization'] ?: 'Not specified') . "</p>
        <p><strong>Subject:</strong> {$contactData['subject']}</p>
        <p><strong>Message:</strong></p>
        <div style='background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 10px 0;'>
            " . nl2br(htmlspecialchars($contactData['message'])) . "
        </div>
        <p><a href='" . SITE_URL . "/admin/messages.php?view=$messageId'>View in Admin Panel</a></p>
    ";
    
    // For now, just log the email (replace with actual sending)
    error_log("Would send admin notification email");
    
    return true;
}

/**
 * Generate confirmation email body
 */
function generateContactConfirmationEmailBody($contactData, $messageId) {
    $name = $contactData['name'];
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Message Confirmation</title>
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
                <h1>Fashion Nexus Ghana</h1>
                <p>Message Received</p>
            </div>
            
            <div class='content'>
                <h2>Dear $name,</h2>
                
                <p>Thank you for contacting Fashion Nexus Ghana! We have successfully received your message.</p>
                
                <p><strong>Message Details:</strong></p>
                <ul>
                    <li><strong>Reference ID:</strong> <span class='highlight'>#$messageId</span></li>
                    <li><strong>Subject:</strong> {$contactData['subject']}</li>
                    <li><strong>Date:</strong> " . date('F j, Y \a\t g:i A') . "</li>
                </ul>
                
                <p>Our team will review your message and respond within 24-48 hours during business hours (Monday-Friday, 9 AM - 5 PM GMT).</p>
                
                <p>For urgent inquiries, you can also reach us at:</p>
                <ul>
                    <li><strong>General:</strong> info@fashionnexusghana.com</li>
                    <li><strong>Summit:</strong> summit@fashionnexusghana.com</li>
                    <li><strong>Partnerships:</strong> partnerships@fashionnexusghana.com</li>
                </ul>
                
                <p>Thank you for your interest in Ghana's fashion industry and our work at Fashion Nexus Ghana.</p>
                
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