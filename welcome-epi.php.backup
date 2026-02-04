<?php
// ============================================================================
// WELCOME EPI - PROTECTED PAGE
// ============================================================================
// Include konfigurasi dan fungsi sistem
require_once 'config.php';
require_once 'fungsi.php';

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Start session untuk kompatibilitas
session_start();

// ============================================================================
// AUTHENTICATION & ACCESS CONTROL
// ============================================================================

// Function untuk logging akses
function logAccess($action, $user_id = null, $details = '') {
    $log_entry = date('Y-m-d H:i:s') . " - " . $action;
    if ($user_id) $log_entry .= " - User ID: " . $user_id;
    if ($details) $log_entry .= " - " . $details;
    $log_entry .= " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
    
    // Log ke file (pastikan folder logs ada dan writable)
    $log_file = 'logs/access_' . date('Y-m') . '.log';
    if (!file_exists('logs')) {
        mkdir('logs', 0755, true);
    }
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Function untuk redirect dengan pesan
function redirectWithMessage($url, $message) {
    $separator = strpos($url, '?') !== false ? '&' : '?';
    header('Location: ' . $url . $separator . 'message=' . urlencode($message));
    exit();
}

// Function untuk mendapatkan login URL yang tepat
function getLoginUrl() {
    global $weburl;
    return $weburl . 'login?redirect=' . urlencode($_SERVER['REQUEST_URI']);
}

// 1. Cek autentikasi menggunakan sistem yang sama dengan dashboard
$user_id = is_login();
if (!$user_id) {
    // User belum login - redirect ke halaman login
    logAccess('ACCESS_DENIED_NOT_LOGGED_IN', null, 'Redirect to login required');
    redirectWithMessage(
        getLoginUrl(),
        'Silakan login terlebih dahulu untuk mengakses halaman member.'
    );
    exit;
}

// User sudah login - ambil data member dari database
$datamember = db_row("SELECT * FROM `sa_member` WHERE `mem_id`=" . intval($user_id));
if (!$datamember) {
    // Data member tidak ditemukan
    logAccess('ACCESS_DENIED_INVALID_USER', $user_id, 'Member data not found');
    redirectWithMessage(
        getLoginUrl(),
        'Data member tidak ditemukan. Silakan login kembali.'
    );
    exit;
}

// Log successful access
logAccess('ACCESS_GRANTED', $user_id, 'Member: ' . $datamember['mem_nama']);

// Generate CSRF token untuk forms jika diperlukan
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get user info for display dari database
$user_name = !empty($datamember['mem_nama']) ? htmlspecialchars($datamember['mem_nama'], ENT_QUOTES, 'UTF-8') : 'Member EPI';
$user_email = !empty($datamember['mem_email']) ? htmlspecialchars($datamember['mem_email'], ENT_QUOTES, 'UTF-8') : '';
$user_initial = strtoupper(substr(strip_tags($user_name), 0, 1));

// Pastikan user_initial tidak kosong
if (empty($user_initial) || !ctype_alpha($user_initial)) {
    $user_initial = 'M'; // Default 'M' untuk Member
}

// Log access for security monitoring
error_log("Login access: User={$user_name}, IP={$_SERVER['REMOTE_ADDR']}, Time=" . date('Y-m-d H:i:s'));

// ============================================================================
// DEBUGGING & ERROR HANDLING (untuk development)
// ============================================================================

// Function untuk debug session (hanya untuk development)
function debugSession() {
    global $user_id, $datamember;
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $is_local = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false);
    
    if ($is_local && isset($_GET['debug']) && $_GET['debug'] === 'session') {
        echo "<!-- DEBUG AUTH INFO:\n";
        echo "User ID: " . ($user_id ?? 'Not set') . "\n";
        echo "Member Name: " . ($datamember['mem_nama'] ?? 'Not set') . "\n";
        echo "Member Email: " . ($datamember['mem_email'] ?? 'Not set') . "\n";
        echo "Member Status: " . ($datamember['mem_status'] ?? 'Not set') . "\n";
        echo "Cookie Auth: " . (isset($_COOKIE['authentication']) ? 'Present' : 'Not set') . "\n";
        echo "-->\n";
    }
}

// ============================================================================
// HTML CONTENT STARTS HERE
// ============================================================================
?>
<?php debugSession(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EPI Channel - Member Area (Protected)</title>
<style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #FFFFFF;
            min-height: 100vh;
        }

        /* Header Menu Member */
        .member-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 3px solid #d4af37;
            box-shadow: 0 5px 20px rgba(212, 175, 55, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 15px 20px;
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            align-items: center;
            gap: 20px;
        }

        .header-logo {
            display: flex;
            align-items: center;
            justify-self: start;
        }

        .header-logo img {
            height: 35px;
            width: auto;
            object-fit: contain;
            max-width: 150px;
        }

        .header-nav {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: center;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            justify-self: end;
            color: #5d4e37;
            font-weight: 500;
        }

        .nav-item {
            color: #5d4e37;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 25px;
            transition: all 0.3s ease;
            font-weight: 500;
            border: 2px solid transparent;
            font-size: 0.95rem;
        }

        .nav-item:hover {
            background: linear-gradient(45deg, #d4af37, #ffd700);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.4);
        }

        .nav-item.active {
            background: linear-gradient(45deg, #d4af37, #b8860b);
            color: white;
            border-color: #b8860b;
        }

        .nav-item.logout-btn {
            background: linear-gradient(45deg, #dc3545, #c82333);
            color: white;
            border-color: #dc3545;
        }

        .nav-item.logout-btn:hover {
            background: linear-gradient(45deg, #c82333, #bd2130);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
        }



        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #5d4e37;
            font-weight: 500;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(45deg, #d4af37, #ffd700);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            flex-direction: column;
            justify-content: space-around;
            width: 30px;
            height: 30px;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0;
            z-index: 1001;
            transition: all 0.3s ease;
        }

        .hamburger-line {
            width: 100%;
            height: 3px;
            background: linear-gradient(45deg, #d4af37, #ffd700);
            border-radius: 2px;
            transition: all 0.3s ease;
            transform-origin: center;
        }

        /* Hamburger Animation States */
        .mobile-menu-toggle.active .hamburger-line:nth-child(1) {
            transform: rotate(45deg) translate(6px, 6px);
        }

        .mobile-menu-toggle.active .hamburger-line:nth-child(2) {
            opacity: 0;
            transform: scale(0);
        }

        .mobile-menu-toggle.active .hamburger-line:nth-child(3) {
            transform: rotate(-45deg) translate(6px, -6px);
        }

        /* Mobile Menu Overlay */
        .mobile-menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .mobile-menu-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Small desktop optimization */
        @media (max-width: 900px) and (min-width: 769px) {
            .header-container {
                max-width: 100%;
                padding: 12px 15px;
                gap: 10px;
                grid-template-columns: auto 1fr auto;
            }
            
            .header-nav {
                gap: 15px;
                justify-content: center;
            }
            
            .nav-item {
                padding: 8px 12px;
                font-size: 0.85rem;
                white-space: nowrap;
            }
        }

        /* Tablet landscape optimization */
        @media (max-width: 1024px) and (min-width: 901px) {
            .header-container {
                max-width: 100%;
                padding: 15px 15px;
                gap: 15px;
            }
            
            .header-nav {
                gap: 20px;
            }
            
            .nav-item {
                padding: 8px 15px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 768px) {
            .header-container {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 15px 20px;
                position: relative;
            }
            
            .header-logo {
                order: 1;
                flex-shrink: 0;
            }
            
            .mobile-menu-toggle {
                display: flex;
                order: 3;
            }
            
            .header-nav {
                position: fixed;
                top: 0;
                right: -100%;
                width: 280px;
                height: 100vh;
                background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
                flex-direction: column;
                justify-content: flex-start;
                align-items: stretch;
                padding: 80px 20px 20px;
                box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
                transition: right 0.3s ease;
                z-index: 1000;
                gap: 15px;
                order: 2;
            }
            
            .header-nav.active {
                right: 0;
            }
            
            .nav-item {
                font-size: 1rem;
                padding: 15px 20px;
                border-radius: 12px;
                text-align: center;
                margin-bottom: 5px;
                border: 2px solid transparent;
                background: rgba(212, 175, 55, 0.05);
                transition: all 0.3s ease;
            }
            
            .nav-item:hover {
                background: linear-gradient(45deg, #d4af37, #ffd700);
                color: white;
                transform: translateX(-5px);
                box-shadow: 5px 5px 15px rgba(212, 175, 55, 0.3);
            }
            
            .nav-item.logout-btn {
                margin-top: 20px;
                background: linear-gradient(45deg, #dc3545, #c82333);
                color: white;
            }
            

            
            .nav-item.logout-btn:hover {
                background: linear-gradient(45deg, #c82333, #bd2130);
                transform: translateX(-5px);
                box-shadow: 5px 5px 15px rgba(220, 53, 69, 0.3);
            }
            
            .user-info {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .header-container {
                padding: 12px 15px;
            }
            
            .header-logo img {
                height: 32px;
                width: auto;
                max-width: 120px;
            }
            
            .mobile-menu-toggle {
                width: 28px;
                height: 28px;
            }
            
            .hamburger-line {
                height: 2.5px;
            }
            
            .header-nav {
                width: 100%;
                right: -100%;
                padding: 70px 15px 20px;
            }
            
            .nav-item {
                font-size: 0.95rem;
                padding: 12px 15px;
                margin-bottom: 8px;
            }
            
            .nav-item.logout-btn {
                margin-top: 15px;
            }
            

        }

        @media (max-width: 360px) {
            .header-container {
                padding: 10px 12px;
            }
            
            .header-logo img {
                height: 28px;
                width: auto;
                max-width: 100px;
            }
            
            .mobile-menu-toggle {
                width: 26px;
                height: 26px;
            }
            
            .nav-item {
                font-size: 0.9rem;
                padding: 10px 12px;
            }
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Floating WhatsApp Button */
        .whatsapp-float {
            position: fixed;
            bottom: 25px;
            right: 25px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 25px rgba(37, 211, 102, 0.4);
            z-index: 1000;
            transition: all 0.3s ease;
            text-decoration: none;
            animation: pulse 2s infinite;
        }

        .whatsapp-float:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 35px rgba(37, 211, 102, 0.6);
            background: linear-gradient(135deg, #128c7e 0%, #25d366 100%);
        }

        .whatsapp-float svg {
            width: 32px;
            height: 32px;
            fill: white;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 8px 25px rgba(37, 211, 102, 0.4);
            }
            50% {
                box-shadow: 0 8px 25px rgba(37, 211, 102, 0.7);
            }
            100% {
                box-shadow: 0 8px 25px rgba(37, 211, 102, 0.4);
            }
        }

        /* Responsive WhatsApp Button */
        @media (max-width: 768px) {
            .whatsapp-float {
                bottom: 20px;
                right: 20px;
                width: 55px;
                height: 55px;
            }
            
            .whatsapp-float svg {
                width: 28px;
                height: 28px;
            }
        }

        @media (max-width: 480px) {
            .whatsapp-float {
                bottom: 15px;
                right: 15px;
                width: 50px;
                height: 50px;
            }
            
            .whatsapp-float svg {
                width: 26px;
                height: 26px;
            }
        }

        .welcome-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(212, 197, 160, 0.3);
            border: 2px solid #d4c5a0;
            backdrop-filter: blur(10px);
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo-text {
            font-size: 2.5rem;
            font-weight: bold;
            background: linear-gradient(45deg, #d4af37, #ffd700, #b8860b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }

        .subtitle {
            font-size: 1.1rem;
            color: #8b7355;
            margin-bottom: 30px;
        }

        .welcome-message {
            background: linear-gradient(135deg, #faf8f3, #f0ead6);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 40px;
            border-left: 5px solid #d4af37;
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.1);
        }

        .welcome-text {
            font-size: 1.1rem;
            text-align: center;
            color: #5d4e37;
            margin-bottom: 20px;
        }

        .access-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: bold;
            color: #8b7355;
            margin-bottom: 25px;
            text-align: center;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(45deg, #d4af37, #ffd700);
            border-radius: 2px;
        }

        .access-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .access-item {
            background: linear-gradient(135deg, #ffffff, #faf8f3);
            padding: 25px;
            border-radius: 15px;
            border: 2px solid #e8dcc0;
            transition: all 0.3s ease;
            text-align: center;
        }

        .access-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(212, 175, 55, 0.2);
            border-color: #d4af37;
        }

        .access-btn {
            display: inline-block;
            background: linear-gradient(45deg, #d4af37, #ffd700);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
            border: none;
            cursor: pointer;
            margin-top: 10px;
        }

        .access-btn:hover {
            background: linear-gradient(45deg, #b8860b, #d4af37);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(212, 175, 55, 0.4);
        }

        .telegram-special {
            background: linear-gradient(135deg, #ffffff, #f0f8ff);
            border: 2px solid #0088cc;
        }

        .telegram-special:hover {
            border-color: #0066aa;
        }

        .qr-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .qr-code {
            max-width: 120px;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .qr-text {
            font-size: 0.9rem;
            color: #0088cc;
            font-weight: 500;
        }

        .contact-section {
            background: linear-gradient(135deg, #f9f6f0, #f0ead6);
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            border: 2px solid #e8dcc0;
            margin-top: 20px;
        }

        .contact-text {
            font-size: 1rem;
            color: #5d4e37;
            font-style: italic;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            color: #8b7355;
            font-size: 0.9rem;
        }

        .gold-accent {
            color: #d4af37;
            font-weight: bold;
        }

        /* Video Section Styles */
        .video-section {
            background: linear-gradient(135deg, #f9f6f0, #f0ead6);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            border: 2px solid #e8dcc0;
            margin: 30px 0;
        }

        .video-container {
            position: relative;
            width: 100%;
            max-width: 800px;
            margin: 20px auto;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.2);
        }

        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 15px;
        }

        .video-title {
            font-size: 1.4rem;
            font-weight: bold;
            color: #5d4e37;
            margin-bottom: 15px;
        }

        .video-description {
            font-size: 1rem;
            color: #8b7355;
            margin-bottom: 20px;
        }

        /* Download Section Styles */
        .download-section {
            background: linear-gradient(135deg, #f9f6f0, #f0ead6);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            border: 2px solid #e8dcc0;
            margin: 30px 0;
        }

        .download-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }

        .download-item {
            background: linear-gradient(135deg, #ffffff, #faf8f3);
            padding: 25px;
            border-radius: 15px;
            border: 2px solid #e8dcc0;
            transition: all 0.3s ease;
            text-align: center;
        }

        .download-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(212, 175, 55, 0.2);
            border-color: #d4af37;
        }

        .download-title {
            font-size: 1.1rem;
            font-weight: bold;
            color: #5d4e37;
            margin-bottom: 10px;
        }

        .download-subtitle {
            font-size: 0.9rem;
            color: #8b7355;
            margin-bottom: 15px;
            font-style: italic;
        }

        .download-btn {
            display: inline-block;
            background: linear-gradient(45deg, #d4af37, #ffd700);
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
            border: none;
            cursor: pointer;
        }

        .download-btn:hover {
            background: linear-gradient(45deg, #b8860b, #d4af37);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(212, 175, 55, 0.4);
        }

        .download-icon {
            font-size: 2rem;
            margin-bottom: 15px;
        }

        /* Enhanced Mobile Responsiveness */
        @media (max-width: 480px) {
            .container {
                padding: 10px;
            }
            
            .welcome-card {
                padding: 20px;
                margin: 10px 0;
            }
            
            .logo-text {
                font-size: 1.8rem;
            }
            
            .subtitle {
                font-size: 1rem;
            }
            
            .welcome-message {
                padding: 20px;
            }
            
            .access-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .access-item {
                padding: 20px;
            }
            
            .access-btn {
                padding: 12px 25px;
                font-size: 0.9rem;
            }
            
            .qr-container {
                flex-direction: column;
                gap: 10px;
            }
            
            .qr-code {
                max-width: 100px;
            }
            
            .video-section {
                padding: 20px;
                margin: 20px 0;
            }
            
            .video-title {
                font-size: 1.2rem;
            }
            
            .video-description {
                font-size: 0.9rem;
            }
            
            .download-section {
                padding: 20px;
                margin: 20px 0;
            }
            
            .download-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .download-item {
                padding: 20px;
            }
            
            .download-btn {
                padding: 10px 20px;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .welcome-card {
                padding: 25px;
            }
            
            .logo-text {
                font-size: 2rem;
            }
            
            .access-grid {
                grid-template-columns: 1fr;
            }
            
            .qr-container {
                flex-direction: column;
                gap: 15px;
            }
        }

        @media (min-width: 769px) and (max-width: 1024px) {
            .access-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .sparkle {
            position: relative;
            overflow: hidden;
        }

        .sparkle::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 215, 0, 0.1), transparent);
            animation: sparkle 3s infinite;
        }

        @keyframes sparkle {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
    </style>
</head>
<body>
    <!-- Header Menu Member -->
    <header class="member-header">
        <div class="header-container">
            <div class="header-logo">
                <img src="upload/logoweb.png" alt="EPI Logo">
            </div>
            
            <!-- Mobile Menu Toggle Button -->
            <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle Menu">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
            
            <nav class="header-nav" id="headerNav">
                <a href="/dashboard" class="nav-item">🏠 Home</a>
                <a href="welcome-epi.php" class="nav-item active">🎯 Digital Access</a>
                <a href="#" class="nav-item">📚 e-Course</a>
                <a href="logout.php" class="nav-item logout-btn">🚪 Logout</a>
            </nav>
        </div>
    </header>

    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

    <div class="container">
            <div class="welcome-message">
                <p class="welcome-text">
                    <strong>Selamat datang kembali, <?php echo $user_name; ?>!</strong><br><br>
                    Terima kasih sudah bergabung dan menjadi bagian dari <span class="gold-accent">Bisnis Emas Perak Indonesia</span>. 
                    Selamat menempuh perjalanan sukses bisnis emas perak bersama EPI - Indonesian Bullion Ecosystem.
                    <br><br>
                    <em>Status: Member Aktif | Akses: Full Digital Content</em>
                </p>
            </div>

            <div class="video-section">
                <h3 class="video-title">🎬 Sambutan dari CEO PT EPI</h3>
                <p class="video-description">Dengarkan langsung sambutan dan visi misi dari CEO PT EPI untuk kesuksesan bisnis emas perak Anda</p>
                <div class="video-container">
                    <iframe 
                        src="https://www.youtube.com/embed/dQw4w9WgXcQ?rel=0&modestbranding=1&showinfo=0&controls=1&autoplay=0" 
                        title="Sambutan CEO PT EPI" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen>
                    </iframe>
                </div>
            </div>

            <div class="access-section">
                <h2 class="section-title">Akses Penting Untuk Anda</h2>
                
                <div class="access-grid">
                    <div class="access-item">
                        <h3 style="color: #25D366; margin-bottom: 15px;">📱 WhatsApp Resmi</h3>
                        <p style="margin-bottom: 15px; color: #5d4e37;">Grup WhatsApp Resmi EPI Channel</p>
                        <a href="https://chat.whatsapp.com/L3wiMLCtYpeEqT7FDLVsa6" class="access-btn" target="_blank">
                            Klik Disini
                        </a>
                    </div>

                    <div class="access-item telegram-special">
                        <h3 style="color: #0088cc; margin-bottom: 15px;">📊 Telegram EPIC</h3>
                        <p style="margin-bottom: 15px; color: #5d4e37;">Channel Telegram Informasi Harga EPIC</p>
                        <div class="qr-container">
                            <img src="img/qrtelegram-epic.jpeg" alt="QR Code Telegram EPIC" class="qr-code">
                            <div class="qr-text">Scan QR Code<br>atau klik tombol</div>
                        </div>
                        <a href="https://t.me/+8q7WNOKfQko1YzFl" class="access-btn" target="_blank" style="background: linear-gradient(45deg, #0088cc, #00aaff);">
                            Klik Disini
                        </a>
                    </div>

                    <div class="access-item">
                        <h3 style="color: #0088cc; margin-bottom: 15px;">🎯 Marketing Kit</h3>
                        <p style="margin-bottom: 15px; color: #5d4e37;">Channel Marketing Kit</p>
                        <a href="https://t.me/+BiucnaE56JhlZTM9" class="access-btn" target="_blank" style="background: linear-gradient(45deg, #0088cc, #00aaff);">
                            Klik Disini
                        </a>
                    </div>

                    <div class="access-item">
                        <h3 style="color: #d4af37; margin-bottom: 15px;">🔐 Akses OMS</h3>
                        <p style="margin-bottom: 15px; color: #5d4e37;">Order Management System</p>
                        <a href="https://epic.emasperak.id/login" class="access-btn" target="_blank">
                            Akses OMS
                        </a>
                    </div>
                </div>
            </div>

            <div class="access-section">
                <h2 class="section-title">Media Sosial Resmi EPI</h2>
                
                <div class="access-grid">
                    <div class="access-item">
                        <h3 style="color: #25D366; margin-bottom: 15px;">📢 WhatsApp Channel</h3>
                        <p style="margin-bottom: 15px; color: #5d4e37;">Channel Resmi EPI untuk Update Terbaru</p>
                        <a href="https://whatsapp.com/channel/0029VaEOAJP89isezBZJzx2h" class="access-btn" target="_blank" style="background: linear-gradient(45deg, #25D366, #128C7E);">
                            Follow Channel
                        </a>
                    </div>

                    <div class="access-item">
                        <h3 style="color: #E4405F; margin-bottom: 15px;">📸 Instagram</h3>
                        <p style="margin-bottom: 15px; color: #5d4e37;">Follow Instagram Resmi EPI</p>
                        <a href="https://instagram.com/epi.channel" class="access-btn" target="_blank" style="background: linear-gradient(45deg, #E4405F, #C13584);">
                            Follow Instagram
                        </a>
                    </div>
                </div>
            </div>

            <div class="access-section">
                <h2 class="section-title">Download Dokumen</h2>
                
                <div class="access-grid">
                    <div class="access-item">
                        <h3 style="color: #d4af37; margin-bottom: 15px;">📚 Panduan Bisnis EPIC</h3>
                        <p style="margin-bottom: 15px; color: #5d4e37;">EPIC Growth Playbook</p>
                        <a href="https://heyzine.com/flip-book/8e699b002d.html" class="access-btn" target="_blank">
                            Download Panduan
                        </a>
                    </div>

                    <div class="access-item">
                        <h3 style="color: #d4af37; margin-bottom: 15px;">⚖️ Kode Etik EPI</h3>
                        <p style="margin-bottom: 15px; color: #5d4e37;">EPI Partner Network Integrity Code</p>
                        <a href="https://heyzine.com/flip-book/5d8c2f803a.html" class="access-btn" target="_blank">
                            Download Kode Etik
                        </a>
                    </div>

                    <div class="access-item">
                        <h3 style="color: #d4af37; margin-bottom: 15px;">🏢 Company Profile</h3>
                        <p style="margin-bottom: 15px; color: #5d4e37;">Company Profile PT EPI</p>
                        <a href="https://heyzine.com/flip-book/577130c234.html" class="access-btn" target="_blank">
                            Download Profile
                        </a>
                    </div>
                </div>
            </div>

            <div class="contact-section">
                <p class="contact-text">
                    💬 Jika ada yang tidak dimengerti silakan hubungi <span class="gold-accent">EPIS Pembina Anda</span>
                </p>
            </div>

            <div class="footer">
                <p>© 2025 EPI - Indonesian Bullion Ecosystem</p>
                <p style="margin-top: 10px;">🏆 <span class="gold-accent">Sukses Bisnis Emas Perak Bersama EPI</span> 🏆</p>
            </div>
        </div>
    </div>

    <script>
        // Mobile Menu Toggle Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const headerNav = document.getElementById('headerNav');
            const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
            const body = document.body;

            // Toggle menu function
            function toggleMobileMenu() {
                const isActive = mobileMenuToggle.classList.contains('active');
                
                if (isActive) {
                    // Close menu
                    mobileMenuToggle.classList.remove('active');
                    headerNav.classList.remove('active');
                    mobileMenuOverlay.classList.remove('active');
                    body.style.overflow = '';
                } else {
                    // Open menu
                    mobileMenuToggle.classList.add('active');
                    headerNav.classList.add('active');
                    mobileMenuOverlay.classList.add('active');
                    body.style.overflow = 'hidden';
                }
            }

            // Event listeners
            mobileMenuToggle.addEventListener('click', toggleMobileMenu);
            mobileMenuOverlay.addEventListener('click', toggleMobileMenu);

            // Close menu when clicking on nav items
            const navItems = headerNav.querySelectorAll('.nav-item');
            navItems.forEach(item => {
                item.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        toggleMobileMenu();
                    }
                });
            });

            // Close menu on window resize if screen becomes larger
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    mobileMenuToggle.classList.remove('active');
                    headerNav.classList.remove('active');
                    mobileMenuOverlay.classList.remove('active');
                    body.style.overflow = '';
                }
            });

            // Prevent menu from staying open on orientation change
            window.addEventListener('orientationchange', function() {
                setTimeout(function() {
                    if (window.innerWidth > 768) {
                        mobileMenuToggle.classList.remove('active');
                        headerNav.classList.remove('active');
                        mobileMenuOverlay.classList.remove('active');
                        body.style.overflow = '';
                    }
                }, 100);
            });
        });
    </script>

    <!-- Floating WhatsApp Button -->
    <a href="https://wa.me/6285176997327?text=Kak%20Arva%2C%20saya%20ingin%20tanya%20sesuatu%20tentang%20EPIC%20Hub%20Lite.%20Nama%20saya%3A%20..." 
       class="whatsapp-float" 
       target="_blank" 
       rel="noopener noreferrer"
       title="Chat dengan Kak Arva di WhatsApp">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.893 3.488"/>
        </svg>
    </a>
</body>
</html>