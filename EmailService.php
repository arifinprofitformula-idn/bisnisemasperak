<?php
/**
 * Email Service untuk mengirim notifikasi menggunakan Mailketing API
 * Dengan fallback ke SMTP jika Mailketing gagal
 */

require_once 'class.phpmailer.php';
require_once 'class/MailketingHelper.php';

class EmailService {
    private $config;
    private $mailer;
    private $mailketingHelper;
    
    public function __construct() {
        $this->loadConfig();
        $this->initializeMailketing();
        $this->initializeMailer();
    }
    
    /**
     * Load konfigurasi email dari database
     */
    private function loadConfig() {
        global $settings;
        
        // Load konfigurasi SMTP langsung dari database jika belum ada di global
        if (!isset($settings['smtp_server']) || empty($settings['smtp_server'])) {
            // Load dari database
            $smtp_fields = ['smtp_server', 'smtp_port', 'smtp_username', 'smtp_password', 
                           'smtp_secure', 'smtp_auth', 'smtp_from', 'smtp_sender'];
            
            foreach ($smtp_fields as $field) {
                $value = db_var("SELECT `set_value` FROM `sa_setting` WHERE `set_label`='$field'");
                if ($value !== false) {
                    $settings[$field] = $value;
                }
            }
        }
        
        $this->config = [
            // Mailketing config dari database (prioritas utama)
            'mailketing_enabled' => db_var("SELECT set_value FROM sa_setting WHERE set_label = 'mailketing_enabled'") == '1',
            'mailketing_api_token' => db_var("SELECT set_value FROM sa_setting WHERE set_label = 'mailketing_api_token'") ?: '',
            'mailketing_from_email' => db_var("SELECT set_value FROM sa_setting WHERE set_label = 'mailketing_from_email'") ?: 'eoagoldacademy@gmail.com',
            'mailketing_from_name' => db_var("SELECT set_value FROM sa_setting WHERE set_label = 'mailketing_from_name'") ?: 'EOA Gold Academy',
            
            // SMTP config (fallback)
            'smtp_server' => $settings['smtp_server'] ?? '',
            'smtp_port' => $settings['smtp_port'] ?? 587,
            'smtp_username' => $settings['smtp_username'] ?? '',
            'smtp_password' => $settings['smtp_password'] ?? '',
            'smtp_secure' => $settings['smtp_secure'] ?? 'tls',
            'smtp_auth' => $settings['smtp_auth'] ?? 'true',
            'smtp_from' => $settings['smtp_from'] ?? 'eoagoldacademy@gmail.com',
            'smtp_sender' => $settings['smtp_sender'] ?? 'EOA Gold Academy'
        ];
    }
    
    /**
     * Initialize Mailketing Helper
     */
    private function initializeMailketing() {
        try {
            $this->mailketingHelper = new MailketingHelper();
        } catch (Exception $e) {
            error_log("Failed to initialize MailketingHelper: " . $e->getMessage());
            $this->config['mailketing_enabled'] = false;
        }
    }
    
    /**
     * Initialize PHPMailer untuk fallback SMTP
     */
    private function initializeMailer() {
        $this->mailer = new PHPMailer();
        $this->mailer->IsSMTP();
        $this->mailer->IsHTML(true);
        $this->mailer->SMTPDebug = 0;
        $this->mailer->Timeout = 60;
        
        // PHPMailer versi lama menggunakan property berbeda untuk SMTP timeout
        if (property_exists($this->mailer, 'SMTPTimeout')) {
            $this->mailer->SMTPTimeout = 60;
        } elseif (property_exists($this->mailer, 'Timeout')) {
            // Fallback untuk versi lama yang hanya punya Timeout
            $this->mailer->Timeout = 60;
        }
    }
    
    /**
     * Fungsi utama untuk mengirim email via Mailketing API dengan fallback ke SMTP
     * 
     * @param string $to Email penerima
     * @param string $subject Subject email
     * @param string $message Isi email (HTML)
     * @param string $from Email pengirim (optional)
     * @param string $fromName Nama pengirim (optional)
     * @return array Status pengiriman dengan format ['status' => bool, 'message' => string]
     */
    public function sendEmail($to, $subject, $message, $from = null, $fromName = null) {
        // Prioritas 1: Coba kirim via Mailketing API
        if ($this->config['mailketing_enabled'] && $this->mailketingHelper) {
            try {
                $result = $this->mailketingHelper->sendEmail(
                    $to, 
                    $subject, 
                    $message, 
                    $from ?: $this->config['mailketing_from_email'], 
                    $fromName ?: $this->config['mailketing_from_name']
                );
                
                if ($result['status']) {
                    return $result; // Berhasil via Mailketing
                } else {
                    error_log("Mailketing failed, trying SMTP fallback: " . $result['message']);
                }
            } catch (Exception $e) {
                error_log("Mailketing exception, trying SMTP fallback: " . $e->getMessage());
            }
        }
        
        // Prioritas 2: Fallback ke SMTP jika Mailketing gagal
        try {
            $result = $this->sendViaSMTP($to, $subject, $message, $from, $fromName);
            if (is_array($result)) {
                return $result;
            } elseif ($result) {
                $this->logEmail('smtp', $to, $subject, 'success', 'Email sent via SMTP fallback');
                return ['status' => true, 'message' => 'Email berhasil dikirim via SMTP (fallback)'];
            } else {
                return ['status' => false, 'message' => 'Gagal mengirim email via SMTP fallback'];
            }
        } catch (Exception $e) {
            error_log("EmailService Error: " . $e->getMessage());
            $this->logEmail('smtp', $to, $subject, 'failed', 'Exception: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Kirim email via SMTP menggunakan PHPMailer
     */
    private function sendViaSMTP($to, $subject, $message, $from = null, $fromName = null) {
        // Cek konfigurasi SMTP
        if (empty($this->config['smtp_server'])) {
            $this->logEmail('smtp', $to, $subject, 'failed', 'SMTP server tidak dikonfigurasi');
            return false;
        }

        require_once __DIR__ . '/class.phpmailer.php';
        
        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->IsHTML(true);
        $mail->SMTPDebug = 0; // Set ke 2 untuk debug detail
        
        // Set timeout untuk mengatasi "Temporary local problem"
        $mail->Timeout = 60; // Connection timeout 60 detik
        
        // PHPMailer versi lama menggunakan property berbeda untuk SMTP timeout
        if (property_exists($mail, 'SMTPTimeout')) {
            $mail->SMTPTimeout = 60; // SMTP command timeout 60 detik
        } elseif (property_exists($mail, 'Timeout')) {
            // Fallback untuk versi lama yang hanya punya Timeout
            $mail->Timeout = 60;
        }
        
        // Konfigurasi SMTP Authentication
        $mail->SMTPAuth = ($this->config['smtp_auth'] == 'true');
        
        // Konfigurasi SSL/TLS berdasarkan port dan setting (PHPMailer 5.1 compatible)
        if ($this->config['smtp_port'] == 465) {
            // Port 465 menggunakan SSL (SMTPS)
            $mail->SMTPSecure = 'ssl';
        } elseif ($this->config['smtp_port'] == 587) {
            // Port 587 menggunakan TLS (STARTTLS)
            $mail->SMTPSecure = 'tls';
        } else {
            // Port lain, gunakan setting manual
            $secureType = strtolower($this->config['smtp_secure']);
            if ($secureType == 'ssl') {
                $mail->SMTPSecure = 'ssl';
            } elseif ($secureType == 'tls') {
                $mail->SMTPSecure = 'tls';
            }
            // Tidak set SMTPSecure jika tidak ada enkripsi
        }
        
        $mail->Host = $this->config['smtp_server'];
        $mail->Port = $this->config['smtp_port'];
        $mail->Username = $this->config['smtp_username'];
        $mail->Password = $this->config['smtp_password'];
        
        // Set pengirim
        $fromEmail = $from ?: $this->config['smtp_from'];
        $fromName = $fromName ?: $this->config['smtp_sender'];
        $mail->SetFrom($fromEmail, $fromName);
        
        // Set penerima dan konten
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AddAddress($to);
        
        try {
            if (!$mail->Send()) {
                $errorMsg = 'SMTP Error: ' . $mail->ErrorInfo;
                
                // Handle specific timeout errors
                if (strpos($errorMsg, 'Temporary local problem') !== false) {
                    $errorMsg .= ' (Kemungkinan timeout - coba lagi dalam beberapa saat)';
                } elseif (strpos($errorMsg, 'timeout') !== false) {
                    $errorMsg .= ' (Koneksi timeout - server SMTP lambat merespons)';
                }
                
                $this->logEmail('smtp', $to, $subject, 'failed', $errorMsg);
                return [
                    'status' => false,
                    'message' => $errorMsg
                ];
            } else {
                $successMsg = 'Email berhasil dikirim via SMTP ke ' . $to;
                $this->logEmail('smtp', $to, $subject, 'success', $successMsg);
                return [
                    'status' => true,
                    'message' => $successMsg
                ];
            }
        } catch (Exception $e) {
            $errorMsg = 'SMTP Exception: ' . $e->getMessage();
            
            // Handle specific timeout exceptions
            if (strpos($errorMsg, 'timeout') !== false || strpos($errorMsg, 'Temporary local problem') !== false) {
                $errorMsg .= ' (Server SMTP lambat - coba lagi dalam beberapa saat)';
            }
            
            $this->logEmail('smtp', $to, $subject, 'failed', $errorMsg);
            return [
                'status' => false,
                'message' => $errorMsg
            ];
        }
    }
    
    /**
     * Kirim notifikasi registrasi member via SMTP
     */
    public function sendRegistrationNotification($memberData, $sponsorData = null) {
        global $settings;
        $notifications = [];
        
        // Notifikasi ke Member
        if (isset($settings['judul_registrasi_member']) && isset($settings['isi_registrasi_member'])) {
            $subject = $this->replaceShortcodes($settings['judul_registrasi_member'], $memberData);
            $message = $this->replaceShortcodes($settings['isi_registrasi_member'], $memberData);
            $notifications['member'] = $this->sendEmail($memberData['mem_email'], $subject, $message);
        }
        
        // Notifikasi ke Sponsor (jika ada)
        if ($sponsorData && isset($settings['judul_registrasi_sponsor']) && isset($settings['isi_registrasi_sponsor'])) {
            $subject = $this->replaceShortcodes($settings['judul_registrasi_sponsor'], $memberData, null, $sponsorData);
            $message = $this->replaceShortcodes($settings['isi_registrasi_sponsor'], $memberData, null, $sponsorData);
            $notifications['sponsor'] = $this->sendEmail($sponsorData['mem_email'], $subject, $message);
        }
        
        // Notifikasi ke Admin
        if (isset($settings['judul_registrasi_admin']) && isset($settings['isi_registrasi_admin'])) {
            $adminEmail = $settings['smtp_from'] ?? 'admin@localhost';
            $subject = $this->replaceShortcodes($settings['judul_registrasi_admin'], $memberData);
            $message = $this->replaceShortcodes($settings['isi_registrasi_admin'], $memberData);
            $notifications['admin'] = $this->sendEmail($adminEmail, $subject, $message);
        }
        
        return $notifications;
    }
    
    /**
     * Kirim notifikasi upgrade member via SMTP
     */
    public function sendUpgradeNotification($memberData, $upgradeData, $sponsorData = null) {
        global $settings;
        $notifications = [];
        
        // Notifikasi ke Member
        if (isset($settings['judul_upgrade_member']) && isset($settings['isi_upgrade_member'])) {
            $subject = $this->replaceShortcodes($settings['judul_upgrade_member'], $memberData, $upgradeData);
            $message = $this->replaceShortcodes($settings['isi_upgrade_member'], $memberData, $upgradeData);
            $notifications['member'] = $this->sendEmail($memberData['mem_email'], $subject, $message);
        }
        
        // Notifikasi ke Sponsor (jika ada)
        if ($sponsorData && isset($settings['judul_upgrade_sponsor']) && isset($settings['isi_upgrade_sponsor'])) {
            $subject = $this->replaceShortcodes($settings['judul_upgrade_sponsor'], $memberData, $upgradeData, $sponsorData);
            $message = $this->replaceShortcodes($settings['isi_upgrade_sponsor'], $memberData, $upgradeData, $sponsorData);
            $notifications['sponsor'] = $this->sendEmail($sponsorData['mem_email'], $subject, $message);
        }
        
        // Notifikasi ke Admin
        if (isset($settings['judul_upgrade_admin']) && isset($settings['isi_upgrade_admin'])) {
            $adminEmail = $settings['smtp_from'] ?? 'admin@localhost';
            $subject = $this->replaceShortcodes($settings['judul_upgrade_admin'], $memberData, $upgradeData);
            $message = $this->replaceShortcodes($settings['isi_upgrade_admin'], $memberData, $upgradeData);
            $notifications['admin'] = $this->sendEmail($adminEmail, $subject, $message);
        }
        
        return $notifications;
    }
    
    /**
     * Kirim notifikasi order produk via SMTP
     */
    public function sendOrderNotification($memberData, $orderData, $sponsorData = null) {
        global $settings;
        $notifications = [];
        
        // Notifikasi ke Member
        if (isset($settings['judul_order_member']) && isset($settings['isi_order_member'])) {
            $subject = $this->replaceShortcodes($settings['judul_order_member'], $memberData, $orderData);
            $message = $this->replaceShortcodes($settings['isi_order_member'], $memberData, $orderData);
            $notifications['member'] = $this->sendEmail($memberData['mem_email'], $subject, $message, 'order');
        }
        
        // Notifikasi ke Sponsor (jika ada)
        if ($sponsorData && isset($settings['judul_order_sponsor']) && isset($settings['isi_order_sponsor'])) {
            $subject = $this->replaceShortcodes($settings['judul_order_sponsor'], $memberData, $orderData, $sponsorData);
            $message = $this->replaceShortcodes($settings['isi_order_sponsor'], $memberData, $orderData, $sponsorData);
            $notifications['sponsor'] = $this->sendEmail($sponsorData['mem_email'], $subject, $message, 'order');
        }
        
        // Notifikasi ke Admin
        if (isset($settings['judul_order_admin']) && isset($settings['isi_order_admin'])) {
            $adminEmail = $settings['smtp_from'] ?? 'admin@localhost';
            $subject = $this->replaceShortcodes($settings['judul_order_admin'], $memberData, $orderData);
            $message = $this->replaceShortcodes($settings['isi_order_admin'], $memberData, $orderData);
            $notifications['admin'] = $this->sendEmail($adminEmail, $subject, $message, 'order');
        }
        
        return $notifications;
    }
    
    /**
     * Kirim notifikasi proses order via SMTP
     */
    public function sendProcessOrderNotification($memberData, $orderData, $sponsorData = null) {
        global $settings;
        $notifications = [];
        
        // Notifikasi ke Member
        if (isset($settings['judul_prosesorder_member']) && isset($settings['isi_prosesorder_member'])) {
            $subject = $this->replaceShortcodes($settings['judul_prosesorder_member'], $memberData, $orderData);
            $message = $this->replaceShortcodes($settings['isi_prosesorder_member'], $memberData, $orderData);
            $notifications['member'] = $this->sendEmail($memberData['mem_email'], $subject, $message, 'prosesorder');
        }
        
        // Notifikasi ke Admin
        if (isset($settings['judul_prosesorder_admin']) && isset($settings['isi_prosesorder_admin'])) {
            $adminEmail = $settings['smtp_from'] ?? 'admin@localhost';
            $subject = $this->replaceShortcodes($settings['judul_prosesorder_admin'], $memberData, $orderData);
            $message = $this->replaceShortcodes($settings['isi_prosesorder_admin'], $memberData, $orderData);
            $notifications['admin'] = $this->sendEmail($adminEmail, $subject, $message, 'prosesorder');
        }
        
        return $notifications;
    }
    
    /**
     * Kirim notifikasi pencairan komisi via SMTP
     */
    public function sendWithdrawalNotification($memberData, $withdrawalData) {
        global $settings;
        $notifications = [];
        
        // Notifikasi ke Member
        if (isset($settings['judul_cair_komisi_member']) && isset($settings['isi_cair_komisi_member'])) {
            $subject = $this->replaceShortcodes($settings['judul_cair_komisi_member'], $memberData, $withdrawalData);
            $message = $this->replaceShortcodes($settings['isi_cair_komisi_member'], $memberData, $withdrawalData);
            $notifications['member'] = $this->sendEmail($memberData['mem_email'], $subject, $message, 'withdrawal');
        }
        
        // Notifikasi ke Admin
        if (isset($settings['judul_cair_komisi_admin']) && isset($settings['isi_cair_komisi_admin'])) {
            $adminEmail = $settings['smtp_from'] ?? 'admin@localhost';
            $subject = $this->replaceShortcodes($settings['judul_cair_komisi_admin'], $memberData, $withdrawalData);
            $message = $this->replaceShortcodes($settings['isi_cair_komisi_admin'], $memberData, $withdrawalData);
            $notifications['admin'] = $this->sendEmail($adminEmail, $subject, $message, 'withdrawal');
        }
        
        return $notifications;
    }
    
    /**
     * Kirim email reset password
     */
    public function sendPasswordReset($memberData, $resetToken) {
        global $settings, $weburl;
        
        $siteName = $settings['site_name'] ?? $settings['nama_web'] ?? 'Bisnis Emas Perak';
        $siteUrl = $settings['site_url'] ?? $weburl ?? 'http://localhost/forbisnisemasperak';
        
        $subject = "Reset Password - " . $siteName;
        $resetLink = rtrim($siteUrl, '/') . "/sareset.php?token=" . $resetToken;
        
        $message = "
        <h3>Reset Password</h3>
        <p>Halo {$memberData['nama']},</p>
        <p>Anda telah meminta reset password untuk akun Anda.</p>
        <p>Klik link berikut untuk reset password:</p>
        <p><a href='{$resetLink}' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
        <p>Link ini akan expired dalam 24 jam.</p>
        <p>Jika Anda tidak meminta reset password, abaikan email ini.</p>
        <br>
        <p>Terima kasih,<br>" . $siteName . "</p>
        ";
        
        return $this->sendEmail($memberData['email'], $subject, $message);
    }
    
    /**
     * Test koneksi SMTP
     */
    public function testConnection($testEmail = null) {
        global $settings;
        
        if (!$testEmail) {
            $testEmail = 'test@example.com';
        }
        
        $siteName = $settings['site_name'] ?? $settings['nama_web'] ?? 'Bisnis Emas Perak';
        
        $subject = "Test Email SMTP - " . $siteName;
        $message = "
        <h3>Test Email SMTP</h3>
        <p>Ini adalah email test dari sistem " . $siteName . "</p>
        <p>Waktu: " . date('Y-m-d H:i:s') . "</p>
        <p>Server SMTP: " . ($this->config['smtp_server'] ?? 'Tidak dikonfigurasi') . "</p>
        <p>Port: " . ($this->config['smtp_port'] ?? 'Tidak dikonfigurasi') . "</p>
        <p>Status: Email dikirim via SMTP</p>
        ";
        
        return $this->sendEmail($testEmail, $subject, $message);
    }
    
    /**
     * Log aktivitas email
     */
    private function logEmail($provider, $recipient, $subject, $status, $message = '') {
        $recipient = cek($recipient);
        $subject = cek($subject);
        $provider = cek($provider);
        $status = cek($status);
        $message = cek($message);
        $created_at = date('Y-m-d H:i:s');
        
        $query = "INSERT INTO `epi_email_logs` 
                  (`method`, `to_email`, `subject`, `status`, `message`, `created_at`) 
                  VALUES ('$provider', '$recipient', '$subject', '$status', '$message', '$created_at')";
        
        db_query($query);
    }
    
    /**
     * Get statistik email
     */
    public function getEmailStats($days = 7) {
        $dateFrom = date('Y-m-d', strtotime("-{$days} days"));
        
        $stats = [
            'total' => 0,
            'sent' => 0,
            'failed' => 0,
            'test_mode' => 0,
            'by_provider' => []
        ];
        
        $logs = db_select("SELECT provider, status, COUNT(*) as count 
                          FROM epi_email_logs 
                          WHERE created_at >= '{$dateFrom}' 
                          GROUP BY provider, status");
        
        foreach ($logs as $log) {
            $stats['total'] += $log['count'];
            
            if ($log['status'] == 'success') {
                $stats['sent'] += $log['count'];
            } elseif ($log['status'] == 'failed') {
                $stats['failed'] += $log['count'];
            } elseif ($log['status'] == 'test_mode') {
                $stats['test_mode'] += $log['count'];
            }
            
            if (!isset($stats['by_provider'][$log['provider']])) {
                $stats['by_provider'][$log['provider']] = 0;
            }
            $stats['by_provider'][$log['provider']] += $log['count'];
        }
        
        return $stats;
    }
    
    /**
     * Cek saldo kredit Mailketing (tidak digunakan untuk SMTP)
     */
    public function checkMailketingCredits() {
        // Mailketing selalu dinonaktifkan untuk sistem SMTP
        return ['success' => false, 'message' => 'Mailketing tidak aktif - menggunakan SMTP'];
    }
    
    /**
     * Replace shortcodes dalam template email
     */
    private function replaceShortcodes($template, $memberData, $orderData = null, $sponsorData = null) {
        // Replace member data
        if ($memberData) {
            foreach ($memberData as $key => $value) {
                $template = str_replace('[' . $key . ']', $value, $template);
                $template = str_replace('[member_' . str_replace('mem_', '', $key) . ']', $value, $template);
            }
        }
        
        // Replace sponsor data
        if ($sponsorData) {
            foreach ($sponsorData as $key => $value) {
                $template = str_replace('[sponsor_' . str_replace('mem_', '', $key) . ']', $value, $template);
            }
        }
        
        // Replace order data
        if ($orderData) {
            foreach ($orderData as $key => $value) {
                $template = str_replace('[' . $key . ']', $value, $template);
            }
            
            // Shortcode khusus untuk order
            if (isset($orderData['order_id'])) {
                $template = str_replace('[idorder]', $orderData['order_id'], $template);
            }
            if (isset($orderData['order_total'])) {
                $template = str_replace('[hrgunik]', $orderData['order_total'], $template);
                $template = str_replace('[hrgproduk]', $orderData['order_total'], $template);
            }
            if (isset($orderData['product_name'])) {
                $template = str_replace('[namaproduk]', $orderData['product_name'], $template);
            }
            if (isset($orderData['product_url'])) {
                $template = str_replace('[urlproduk]', $orderData['product_url'], $template);
            }
        }
        
        // Replace withdrawal data
        if (isset($orderData['komisi'])) {
            $template = str_replace('[komisi]', $orderData['komisi'], $template);
        }
        
        return $template;
    }
}

/**
 * Fungsi wrapper untuk kompatibilitas dengan kode yang sudah ada
 * Menggantikan smtpmailer()
 */
function sendEmailNotification($to, $subject, $message, $from = null, $fromName = null) {
    $emailService = new EmailService();
    return $emailService->sendEmail($to, $subject, $message, $from, $fromName);
}

/**
 * Fungsi untuk mengirim notifikasi berdasarkan jenis
 */
function sendNotificationByType($type, $memberData, $additionalData = [], $sponsorData = null) {
    $emailService = new EmailService();
    
    switch ($type) {
        case 'registration':
            return $emailService->sendRegistrationNotification($memberData, $sponsorData);
            
        case 'upgrade':
            return $emailService->sendUpgradeNotification($memberData, $additionalData, $sponsorData);
            
        case 'order':
            return $emailService->sendOrderNotification($memberData, $additionalData, $sponsorData);
            
        case 'process_order':
            return $emailService->sendProcessOrderNotification($memberData, $additionalData, $sponsorData);
            
        case 'withdrawal':
            return $emailService->sendWithdrawalNotification($memberData, $additionalData);
            
        case 'password_reset':
            return $emailService->sendPasswordReset($memberData, $additionalData['token']);
            
        default:
            return ['success' => false, 'message' => 'Jenis notifikasi tidak dikenal'];
    }
}
?>