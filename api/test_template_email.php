<?php
header('Content-Type: application/json');

if (!defined('IS_IN_SCRIPT')) { 
    define('IS_IN_SCRIPT', true);
    require_once '../config.php';
    require_once '../fungsi.php';
}

// Cek akses admin
session_start();
if (!isset($_SESSION['member']) || $_SESSION['member']['mem_role'] < 5) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit();
}

// Include MailketingHelper
require_once __DIR__ . '/../class/MailketingHelper.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $templateId = $input['template_id'] ?? null;
    $email = $input['email'] ?? null;
    $testData = $input['test_data'] ?? [];
    $content = $input['content'] ?? '';
    $subject = $input['subject'] ?? 'Test Email Template';
    
    if (!$templateId || !$email || !$content) {
        throw new Exception('Template ID, email, dan content harus diisi');
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Format email tidak valid');
    }
    
    // Replace shortcodes with test data
    $processedContent = $content;
    $processedSubject = $subject;
    
    // Default test data
    $defaultData = [
        'MEMBER_NAME' => 'Test User',
        'MEMBER_EMAIL' => $email,
        'MEMBER_USERNAME' => 'testuser',
        'MEMBER_PHONE' => '081234567890',
        'SPONSOR_NAME' => 'Sponsor Test',
        'SPONSOR_EMAIL' => 'sponsor@test.com',
        'SPONSOR_USERNAME' => 'sponsortest',
        'SITE_NAME' => 'Bisnis Emas Perak',
        'SITE_URL' => 'https://bisnisemasperak.com',
        'LOGIN_URL' => 'https://bisnisemasperak.com/login',
        'DASHBOARD_URL' => 'https://bisnisemasperak.com/dashboard',
        'ORDER_ID' => 'ORD-' . date('Ymd') . '-001',
        'PRODUCT_NAME' => 'Paket Premium',
        'AMOUNT' => 'Rp 500.000',
        'ORDER_DATE' => date('d/m/Y H:i'),
        'DATE' => date('d/m/Y'),
        'TIME' => date('H:i:s'),
        'CURRENT_YEAR' => date('Y')
    ];
    
    // Merge with provided test data
    $finalData = array_merge($defaultData, $testData);
    
    // Replace shortcodes in content and subject
    foreach ($finalData as $key => $value) {
        $shortcode = '{{' . $key . '}}';
        $processedContent = str_replace($shortcode, $value, $processedContent);
        $processedSubject = str_replace($shortcode, $value, $processedSubject);
    }
    
    // Initialize Mailketing
    $mailketing = new MailketingHelper();
    
    // Send test email
    $result = $mailketing->sendTestEmail($email, $processedSubject, $processedContent);
    
    if ($result['success']) {
        // Log test email
        $logSql = "INSERT INTO epi_email_logs (email_type, recipient_email, subject, status, sent_at, template_id) 
                   VALUES (?, ?, ?, ?, NOW(), ?)";
        db_query($logSql, ['test_template', $email, $processedSubject, 'sent', $templateId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Test email berhasil dikirim',
            'email' => $email,
            'subject' => $processedSubject,
            'mailketing_response' => $result
        ]);
    } else {
        // Log failed email
        $logSql = "INSERT INTO epi_email_logs (email_type, recipient_email, subject, status, error_message, sent_at, template_id) 
                   VALUES (?, ?, ?, ?, ?, NOW(), ?)";
        db_query($logSql, ['test_template', $email, $processedSubject, 'failed', $result['message'], $templateId]);
        
        throw new Exception('Gagal mengirim email: ' . $result['message']);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>