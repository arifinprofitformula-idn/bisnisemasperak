<?php
// ============================================================================
// EPI Channel - Premium Member Area Access Control
// File: welcome-epi-premium.php
// Hanya untuk member premium (mem_status = 2)
// ============================================================================

// Start session dengan security settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set ke 1 jika menggunakan HTTPS
ini_set('session.use_only_cookies', 1);
session_start();

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Fungsi untuk redirect dengan pesan
function redirectWithMessage($url, $message) {
    $_SESSION['error_message'] = $message;
    header("Location: $url");
    exit();
}

// Fungsi untuk log akses
function logAccess($action, $user_id = null, $status = null) {
    $log_entry = date('Y-m-d H:i:s') . " - " . $_SERVER['REMOTE_ADDR'] . " - $action";
    if ($user_id) $log_entry .= " - User ID: $user_id";
    if ($status) $log_entry .= " - Status: $status";
    $log_entry .= " - UA: " . substr($_SERVER['HTTP_USER_AGENT'], 0, 100) . "\n";
    
    // Log ke file (opsional)
    // file_put_contents('logs/premium_access.log', $log_entry, FILE_APPEND | LOCK_EX);
}

// ============================================================================
// VALIDASI PREMIUM MEMBER & ADMIN ACCESS
// ============================================================================

// 1. Cek apakah user sudah login (Premium Member atau Admin)
$is_logged_in = false;
$is_admin = false;
$is_premium = false;

// Cek login premium member
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $is_logged_in = true;
    if (isset($_SESSION['user_status']) && $_SESSION['user_status'] == 2) {
        $is_premium = true;
    }
}

// Cek login admin (sistem SimpleAff)
if (isset($_SESSION['sauser']) && !empty($_SESSION['sauser'])) {
    // Jika sudah ada session admin yang valid, gunakan itu
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && 
        isset($_SESSION['admin_role']) && $_SESSION['admin_role'] >= 5) {
        $is_logged_in = true;
        $is_admin = true;
    } else {
        // Include config untuk koneksi database
        if (!isset($con)) {
            include 'config.php';
            include 'fungsi.php';
        }
        
        $username = $_SESSION['sauser'];
        
        // Cek koneksi database
        if (isset($con) && $con) {
            $query = "SELECT mem_role, mem_nama, mem_id FROM sa_member WHERE mem_kodeaff = '" . cek($username) . "'";
            $result = mysqli_query($con, $query);
            $admin_data = mysqli_fetch_array($result);
            
            if ($admin_data && $admin_data['mem_role'] >= 5) {
                $is_logged_in = true;
                $is_admin = true;
                
                // Set session variables untuk konsistensi
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_role'] = $admin_data['mem_role'];
                $_SESSION['admin_name'] = $admin_data['mem_nama'];
                $_SESSION['admin_id'] = $admin_data['mem_id'];
            }
        } else {
            // Fallback untuk testing - jika session admin sudah diset manual
            if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] >= 5) {
                $is_logged_in = true;
                $is_admin = true;
                $_SESSION['admin_logged_in'] = true;
            }
        }
    }
}

// Jika tidak ada yang login
if (!$is_logged_in) {
    logAccess('ACCESS_DENIED_NOT_LOGGED_IN');
    redirectWithMessage(
        'https://bisnisemasperak.com/login?redirect=' . urlencode($_SERVER['REQUEST_URI']),
        'Silakan login terlebih dahulu untuk mengakses halaman ini.'
    );
}

// 2. Cek session timeout (30 menit) - hanya untuk premium member
if ($is_premium) {
    $session_timeout = 1800; // 30 menit
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $session_timeout) {
        session_destroy();
        logAccess('SESSION_TIMEOUT', $_SESSION['user_id'] ?? null);
        redirectWithMessage(
            'https://bisnisemasperak.com/login?redirect=' . urlencode($_SERVER['REQUEST_URI']),
            'Session Anda telah berakhir. Silakan login kembali.'
        );
    }
    
    // 3. Validasi User Agent consistency (security) - hanya untuk premium member
    if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_destroy();
        logAccess('SECURITY_VIOLATION_UA_MISMATCH', $_SESSION['user_id'] ?? null);
        redirectWithMessage(
            'https://bisnisemasperak.com/login',
            'Terdeteksi aktivitas mencurigakan. Silakan login kembali.'
        );
    }
}

// 4. VALIDASI AKSES (ADMIN ATAU PREMIUM MEMBER)
if (!$is_admin && !$is_premium) {
    $user_status = $_SESSION['user_status'] ?? 'unknown';
    $user_id = $_SESSION['user_id'] ?? null;
    
    logAccess('ACCESS_DENIED_NOT_AUTHORIZED', $user_id, $user_status);
    
    // Redirect dengan pesan khusus berdasarkan status
    if ($user_status == 1) {
        // Free member
        redirectWithMessage(
            'https://bisnisemasperak.com/login?upgrade=1',
            'Hanya admin atau member premium yang dapat mengakses halaman ini. Silakan upgrade ke premium member.'
        );
    } else {
        // Status tidak dikenal atau belum set
        redirectWithMessage(
            'https://bisnisemasperak.com/login',
            'Hanya admin atau member premium yang dapat mengakses halaman ini. Silakan login dengan akun yang sesuai.'
        );
    }
}

// 5. Update last activity (hanya untuk premium member)
if ($is_premium) {
    $_SESSION['last_activity'] = time();
    
    // Set user agent jika belum ada
    if (!isset($_SESSION['user_agent'])) {
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    }
}

// 6. Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 7. Log successful access
if ($is_admin) {
    logAccess('ADMIN_ACCESS_GRANTED', $_SESSION['admin_id'] ?? null, 'admin_role_' . ($_SESSION['admin_role'] ?? 'unknown'));
} else {
    logAccess('PREMIUM_ACCESS_GRANTED', $_SESSION['user_id'] ?? null, $_SESSION['user_status'] ?? 'unknown');
}

// ============================================================================
// KONTEN HALAMAN PREMIUM
// ============================================================================
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EPI Channel - Premium Member Area</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .header-logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-logo img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid #d4af37;
            object-fit: cover;
        }

        .header-title {
            font-size: 1.5rem;
            font-weight: bold;
            background: linear-gradient(45deg, #d4af37, #b8860b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header-nav {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .nav-item {
            color: #5d4e37;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 25px;
            transition: all 0.3s ease;
            font-weight: 500;
            border: 2px solid transparent;
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

        .premium-badge {
            background: linear-gradient(45deg, #d4af37, #ffd700);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-left: 5px;
        }

        .admin-badge {
            background: linear-gradient(45deg, #dc3545, #ff6b6b);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-left: 5px;
            animation: pulse 2s infinite;
        }

        .super-admin-badge {
            background: linear-gradient(45deg, #6f42c1, #9c27b0);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-left: 5px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }

        .admin-header {
            background: linear-gradient(135deg, #dc3545, #ff6b6b);
            color: white;
            padding: 10px 20px;
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }

        .super-admin-header {
            background: linear-gradient(135deg, #6f42c1, #9c27b0);
            color: white;
            padding: 10px 20px;
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(111, 66, 193, 0.3);
        }

        .logout-btn {
            background: linear-gradient(45deg, #dc3545, #c82333);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-left: 10px;
        }

        .logout-btn:hover {
            background: linear-gradient(45deg, #c82333, #bd2130);
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(220, 53, 69, 0.3);
        }

        /* Mobile Menu */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #d4af37;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 15px;
            }
            
            .header-nav {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .nav-item {
                font-size: 0.9rem;
                padding: 6px 12px;
            }
            
            .header-title {
                font-size: 1.2rem;
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
                <img src="upload/logoweb.png" alt="EPI Channel Logo">
                <div class="header-title">EPI Channel</div>
            </div>
            
            <nav class="header-nav">
                <a href="/forbisnisemasperak/dashboard" class="nav-item">🏠 Home</a>
                <a href="welcome-epi-premium.php" class="nav-item active">🎯 Premium Access</a>
                <a href="#" class="nav-item">📚 Materi</a>
                <a href="#" class="nav-item">💎 Premium</a>
                <a href="#" class="nav-item">📊 Dashboard</a>
            </nav>
            
            <div class="user-info">
                <?php if ($is_admin): ?>
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)); ?></div>
                    <span><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator'); ?></span>
                    <?php if (($_SESSION['admin_role'] ?? 0) >= 9): ?>
                        <span class="super-admin-badge">SUPER ADMIN</span>
                    <?php else: ?>
                        <span class="admin-badge">ADMIN</span>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'P', 0, 1)); ?></div>
                    <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Premium Member'); ?></span>
                    <span class="premium-badge">PREMIUM</span>
                <?php endif; ?>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="welcome-card">
            <?php if ($is_admin): ?>
                <?php if (($_SESSION['admin_role'] ?? 0) >= 9): ?>
                    <div class="super-admin-header">
                        🔧 SUPER ADMIN ACCESS - Level <?php echo $_SESSION['admin_role'] ?? 'Unknown'; ?>
                    </div>
                <?php else: ?>
                    <div class="admin-header">
                        ⚙️ ADMIN ACCESS - Level <?php echo $_SESSION['admin_role'] ?? 'Unknown'; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="welcome-message">
                <?php if ($is_admin): ?>
                    <p class="welcome-text">
                        <strong>🔧 Selamat datang Administrator EPI Channel!</strong><br><br>
                        Anda sedang mengakses halaman premium sebagai <span class="gold-accent">Administrator</span> dengan level akses <?php echo $_SESSION['admin_role'] ?? 'Unknown'; ?>. 
                        Sebagai admin, Anda memiliki akses penuh ke semua fitur dan konten premium untuk keperluan administrasi dan monitoring.
                        <br><br>
                        Gunakan akses ini dengan bijak untuk mengelola sistem EPI - Indonesian Bullion Ecosystem.
                    </p>
                <?php else: ?>
                    <p class="welcome-text">
                        <strong>🌟 Selamat datang Premium Member EPI Channel!</strong><br><br>
                        Terima kasih sudah bergabung dan menjadi bagian dari <span class="gold-accent">Bisnis Emas Perak Indonesia</span>. 
                        Sebagai member premium, Anda mendapatkan akses eksklusif ke semua fitur dan konten premium kami.
                        <br><br>
                        Selamat menempuh perjalanan sukses bisnis emas perak bersama EPI - Indonesian Bullion Ecosystem.
                    </p>
                <?php endif; ?>
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
                <h2 class="section-title">🔥 Akses Premium Eksklusif</h2>
                
                <div class="access-grid">
                    <div class="access-item">
                        <h3 style="color: #25D366; margin-bottom: 15px;">📱 WhatsApp VIP</h3>
                        <p style="margin-bottom: 15px; color: #5d4e37;">Grup WhatsApp Khusus Premium Member</p>
                        <a href="https://chat.whatsapp.com/L3wiMLCtYpeEqT7FDLVsa6" class="access-btn" target="_blank">
                            Akses VIP Group
                        </a>
                    </div>

                    <div class="access-item telegram-special">
                        <h3 style="color: #0088cc; margin-bottom: 15px;">📊 Telegram EPIC Premium</h3>
                        <p style="margin-bottom: 15px; color: #5d4e37;">Channel Premium dengan Analisis Mendalam</p>
                        <div class="qr-container">
                            <img src="img/qrtelegram-epic.jpeg" alt="QR Code Telegram EPIC" class="qr-code">
                            <div class="qr-text">Scan QR Code<br>atau klik tombol</div>
                        </div>
                        <a href="https://t.me/+8q7WNOKfQko1YzFl" class="access-btn" target="_blank" style="background: linear-gradient(45deg, #0088cc, #00aaff);">
                            Akses Premium Channel
                        </a>
                    </div>

                    <div class="access-item">
                        <h3 style="color: #0088cc; margin-bottom: 15px;">🎯 Marketing Kit Premium</h3>
                        <p style="margin-bottom: 15px; color: #5d4e37;">Tools Marketing Eksklusif Premium</p>
                        <a href="https://t.me/+BiucnaE56JhlZTM9" class="access-btn" target="_blank" style="background: linear-gradient(45deg, #0088cc, #00aaff);">
                            Download Kit Premium
                        </a>
                    </div>

                    <div class="access-item">
                        <h3 style="color: #d4af37; margin-bottom: 15px;">🔐 OMS Premium</h3>
                        <p style="margin-bottom: 15px; color: #5d4e37;">Order Management System dengan Fitur Premium</p>
                        <a href="https://epic.emasperak.id/login" class="access-btn" target="_blank">
                            Akses OMS Premium
                        </a>
                    </div>
                </div>
            </div>

            <div class="access-section">
                <h2 class="section-title">🏆 Benefit Premium Member</h2>
                
                <div class="access-grid">
                    <div class="access-item">
                        <h3 style="color: #25D366; margin-bottom: 15px;">📢 WhatsApp Channel Premium</h3>
                        <p style="margin-bottom: 15px; color: #5d4e37;">Update Eksklusif & Analisis Pasar Real-time</p>
                        <a href="https://whatsapp.com/channel/0029VaEOAJP89isezBZJzx2h" class="access-btn" target="_blank" style="background: linear-gradient(45deg, #25D366, #128C7E);">
                            Follow Premium Channel
                        </a>
                    </div>

                    <div class="access-item">
                        <h3 style="color: #E4405F; margin-bottom: 15px;">📸 Instagram Premium</h3>
                        <p style="margin-bottom: 15px; color: #5d4e37;">Konten Eksklusif & Live Trading Session</p>
                        <a href="https://instagram.com/epi.channel" class="access-btn" target="_blank" style="background: linear-gradient(45deg, #E4405F, #C13584);">
                            Follow Premium Content
                        </a>
                    </div>
                </div>
            </div>

            <div class="contact-section">
                <p class="contact-text">
                    💎 Sebagai <span class="gold-accent">Premium Member</span>, Anda mendapatkan prioritas support dari <span class="gold-accent">EPIS Pembina</span> dan akses ke semua fitur eksklusif
                </p>
            </div>

            <div class="footer">
                <p>© 2025 EPI - Indonesian Bullion Ecosystem</p>
                <p style="margin-top: 10px;">🏆 <span class="gold-accent">Premium Member - Sukses Bisnis Emas Perak Bersama EPI</span> 🏆</p>
                <p style="margin-top: 5px; font-size: 0.8rem;">Session: <?php echo date('d/m/Y H:i', $_SESSION['last_activity']); ?> WIB</p>
            </div>
        </div>
    </div>

    <script>
        // Auto refresh session setiap 25 menit
        setTimeout(function() {
            fetch(window.location.href, {
                method: 'HEAD',
                headers: {
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
        }, 1500000); // 25 menit

        // Prevent back button after logout
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>