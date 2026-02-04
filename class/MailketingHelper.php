<?php
/**
 * MailketingHelper - Integrasi API Mailketing untuk Email System
 * Menggantikan sistem SMTP/PHPMailer dengan API Mailketing
 * 
 * @author Arva Digital Media
 * @version 2.0
 * @updated 2025-01-09
 */

class MailketingHelper {
    private $config;
    private $apiEndpoint = 'https://api.mailketing.co.id/api/v1/send';
    private $apiEndpointList = 'https://api.mailketing.co.id/api/v1/viewlist';
    private $apiEndpointAddSub = 'https://api.mailketing.co.id/api/v1/addsubtolist';
    private $apiEndpointBalance = 'https://api.mailketing.co.id/api/v1/ceksaldo';
    
    public function __construct() {
        $this->loadConfig();
    }
    
    /**
     * Load konfigurasi Mailketing dari database settings
     */
    private function loadConfig() {
        global $settings, $db;
        
        // Set konfigurasi default
        $this->config = [
            'api_token' => '277b5a7d945847177b5c67dfe91838ba',
            'from_email' => 'eoagoldacademy@gmail.com',
            'from_name' => 'EPI Gold Academy'
        ];
        
        try {
            // Load konfigurasi Mailketing dari database jika belum ada di global
            if (!isset($settings['mailketing_api_token']) || empty($settings['mailketing_api_token'])) {
                $mailketing_fields = ['mailketing_api_token', 'mailketing_from_email', 'mailketing_from_name'];
                
                foreach ($mailketing_fields as $field) {
                    $value = db_var("SELECT `set_value` FROM `sa_setting` WHERE `set_label`='$field'");
                    if ($value !== false) {
                        $settings[$field] = $value;
                    }
                }
            }
            
            // Update konfigurasi dengan nilai dari database
            $this->config['api_token'] = $settings['mailketing_api_token'] ?? $this->config['api_token'];
            $this->config['from_email'] = $settings['mailketing_from_email'] ?? $this->config['from_email'];
            $this->config['from_name'] = $settings['mailketing_from_name'] ?? $this->config['from_name'];
            
        } catch (Exception $e) {
            error_log("Failed to load Mailketing config: " . $e->getMessage());
        }
    }
    
    /**
     * Fungsi utama untuk mengirim email via API Mailketing
     * 
     * @param string $to Email penerima
     * @param string $subject Subject email
     * @param string $message Isi email (HTML/Text)
     * @param string $from Email pengirim (optional)
     * @param string $fromName Nama pengirim (optional)
     * @param string $attachment URL attachment (optional, max 2MB)
     * @return array Status pengiriman dengan format ['status' => bool, 'message' => string, 'response' => array]
     */
    public function sendEmail($to, $subject, $message, $from = null, $fromName = null, $attachment = null) {
        try {
            // Validasi input
            if (empty($to) || empty($subject) || empty($message)) {
                return [
                    'status' => false, 
                    'message' => 'Parameter email tidak lengkap (to, subject, message wajib diisi)',
                    'response' => null
                ];
            }
            
            // Validasi email format
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                return [
                    'status' => false, 
                    'message' => 'Format email penerima tidak valid: ' . $to,
                    'response' => null
                ];
            }
            
            // Siapkan parameter untuk API
            $params = [
                'api_token' => $this->config['api_token'],
                'from_name' => $fromName ?: $this->config['from_name'],
                'from_email' => $from ?: $this->config['from_email'],
                'recipient' => $to,
                'subject' => $subject,
                'content' => $message
            ];
            
            // Tambahkan attachment jika ada
            if (!empty($attachment)) {
                $params['attach1'] = $attachment;
            }
            
            // Kirim request ke API Mailketing
            $response = $this->sendCurlRequest($this->apiEndpoint, $params);
            
            if ($response === false) {
                $this->logEmail('mailketing', $to, $subject, 'failed', 'Gagal koneksi ke API Mailketing');
                return [
                    'status' => false, 
                    'message' => 'Gagal koneksi ke API Mailketing',
                    'response' => null
                ];
            }
            
            // Parse response JSON
            $responseData = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logEmail('mailketing', $to, $subject, 'failed', 'Response API tidak valid: ' . $response);
                return [
                    'status' => false, 
                    'message' => 'Response API tidak valid',
                    'response' => $response
                ];
            }
            
            // Cek status response
            if (isset($responseData['status']) && $responseData['status'] === 'success') {
                $this->logEmail('mailketing', $to, $subject, 'success', 'Email berhasil dikirim via Mailketing API');
                return [
                    'status' => true, 
                    'message' => 'Email berhasil dikirim via Mailketing: ' . ($responseData['response'] ?? 'Mail Sent'),
                    'response' => $responseData
                ];
            } else {
                $errorMsg = $responseData['response'] ?? 'Unknown error';
                $this->logEmail('mailketing', $to, $subject, 'failed', 'API Error: ' . $errorMsg);
                return [
                    'status' => false, 
                    'message' => 'Mailketing API Error: ' . $errorMsg,
                    'response' => $responseData
                ];
            }
            
        } catch (Exception $e) {
            error_log("MailketingHelper Error: " . $e->getMessage());
            $this->logEmail('mailketing', $to, $subject, 'failed', 'Exception: ' . $e->getMessage());
            return [
                'status' => false, 
                'message' => 'Exception: ' . $e->getMessage(),
                'response' => null
            ];
        }
    }
    
    /**
     * Tambah subscriber ke list
     */
    public function addSubscriber($email, $firstName, $lastName = '', $listId = null) {
        if (empty($this->config['api_token'])) {
            return false;
        }
        
        $params = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'api_token' => $this->config['api_token'],
            'list_id' => $listId ?? '',
            'country' => 'Indonesia'
        ];
        
        $response = $this->sendCurlRequest($this->apiEndpointAddSub, $params);
        $responseData = json_decode($response, true);
        return $responseData && isset($responseData['status']) && $responseData['status'] == 'success';
    }
    
    /**
     * Cek saldo credits
     */
    public function checkCredits() {
        if (empty($this->config['api_token'])) {
            return false;
        }
        
        $params = ['api_token' => $this->config['api_token']];
        $response = $this->sendCurlRequest($this->apiEndpointBalance, $params);
        return json_decode($response, true);
    }
    
    /**
     * Get all lists
     */
    public function getLists() {
        if (empty($this->config['api_token'])) {
            return false;
        }
        
        $params = ['api_token' => $this->config['api_token']];
        $response = $this->sendCurlRequest($this->apiEndpointList, $params);
        return json_decode($response, true);
    }
    
    /**
     * Kirim notifikasi registrasi
     */
    public function sendRegistrationNotification($memberData, $sponsorData = null, $target = 'member') {
        global $settings;
        
        $templateKey = 'mailketing_template_daftar_' . $target;
        $template = $settings[$templateKey] ?? '';
        
        if (empty($template)) {
            return false;
        }
        
        // Replace shortcodes
        $content = $this->replaceShortcodes($template, $memberData, $sponsorData);
        $subject = $this->getSubject('registrasi', $target);
        
        switch ($target) {
            case 'member':
                $to = $memberData['mem_email'];
                break;
            case 'sponsor':
                $to = $sponsorData ? $sponsorData['mem_email'] : '';
                break;
            case 'admin':
                $to = $settings['admin_email'] ?? '';
                break;
            default:
                return false;
        }
        
        if (empty($to)) {
            return false;
        }
        
        return $this->sendEmail($to, $subject, $content);
    }
    
    /**
     * Kirim notifikasi upgrade
     */
    public function sendUpgradeNotification($memberData, $sponsorData = null, $target = 'member') {
        global $settings;
        
        $templateKey = 'mailketing_template_upgrade_' . $target;
        $template = $settings[$templateKey] ?? '';
        
        if (empty($template)) {
            return false;
        }
        
        $content = $this->replaceShortcodes($template, $memberData, $sponsorData);
        $subject = $this->getSubject('upgrade', $target);
        
        switch ($target) {
            case 'member':
                $to = $memberData['mem_email'];
                break;
            case 'sponsor':
                $to = $sponsorData ? $sponsorData['mem_email'] : '';
                break;
            default:
                return false;
        }
        
        if (empty($to)) {
            return false;
        }
        
        return $this->sendEmail($to, $subject, $content);
    }
    
    /**
     * Kirim notifikasi order produk
     */
    public function sendOrderNotification($memberData, $orderData, $sponsorData = null, $target = 'member') {
        global $settings;
        
        $templateKey = 'mailketing_template_order_' . $target;
        $template = $settings[$templateKey] ?? '';
        
        if (empty($template)) {
            return false;
        }
        
        $content = $this->replaceShortcodes($template, $memberData, $sponsorData, $orderData);
        $subject = $this->getSubject('order', $target);
        
        switch ($target) {
            case 'member':
                $to = $memberData['mem_email'];
                break;
            case 'sponsor':
                $to = $sponsorData ? $sponsorData['mem_email'] : '';
                break;
            default:
                return false;
        }
        
        if (empty($to)) {
            return false;
        }
        
        return $this->sendEmail($to, $subject, $content);
    }
    
    /**
     * Kirim notifikasi proses order
     */
    public function sendProcessOrderNotification($memberData, $orderData, $sponsorData = null, $target = 'member') {
        global $settings;
        
        $templateKey = 'mailketing_template_prosesorder_' . $target;
        $template = $settings[$templateKey] ?? '';
        
        if (empty($template)) {
            return false;
        }
        
        $content = $this->replaceShortcodes($template, $memberData, $sponsorData, $orderData);
        $subject = $this->getSubject('prosesorder', $target);
        
        switch ($target) {
            case 'member':
                $to = $memberData['mem_email'];
                break;
            case 'sponsor':
                $to = $sponsorData ? $sponsorData['mem_email'] : '';
                break;
            default:
                return false;
        }
        
        if (empty($to)) {
            return false;
        }
        
        return $this->sendEmail($to, $subject, $content);
    }
    
    /**
     * Kirim notifikasi pencairan komisi
     */
    public function sendCommissionNotification($memberData, $commissionData) {
        global $settings;
        
        $template = $settings['mailketing_template_cair_komisi_member'] ?? '';
        
        if (empty($template)) {
            return false;
        }
        
        $content = $this->replaceShortcodes($template, $memberData, null, null, $commissionData);
        $subject = $this->getSubject('cair_komisi', 'member');
        
        return $this->sendEmail($memberData['mem_email'], $subject, $content);
    }
    
    /**
     * Replace shortcodes dalam template
     */
    public function replaceShortcodes($template, $memberData, $sponsorData = null, $orderData = null, $commissionData = null) {
        // Member shortcodes
        if ($memberData) {
            foreach ($memberData as $key => $value) {
                $shortcode = '[member_' . str_replace('mem_', '', $key) . ']';
                $template = str_replace($shortcode, $value, $template);
            }
        }
        
        // Sponsor shortcodes
        if ($sponsorData) {
            foreach ($sponsorData as $key => $value) {
                $shortcode = '[sponsor_' . str_replace('mem_', '', $key) . ']';
                $template = str_replace($shortcode, $value, $template);
            }
        }
        
        // Order shortcodes
        if ($orderData) {
            $template = str_replace('[idorder]', $orderData['order_id'] ?? '', $template);
            $template = str_replace('[hrgunik]', $orderData['harga_unik'] ?? '', $template);
            $template = str_replace('[hrgproduk]', $orderData['harga_produk'] ?? '', $template);
            $template = str_replace('[namaproduk]', $orderData['nama_produk'] ?? '', $template);
            $template = str_replace('[urlproduk]', $orderData['url_produk'] ?? '', $template);
        }
        
        // Commission shortcodes
        if ($commissionData) {
            $template = str_replace('[komisi]', $commissionData['jumlah'] ?? '', $template);
        }
        
        return $template;
    }
    
    /**
     * Get subject berdasarkan jenis dan target
     */
    private function getSubject($type, $target) {
        global $settings;
        
        $subjectKey = 'mailketing_subject_' . $type . '_' . $target;
        $defaultSubjects = [
            'registrasi_member' => 'Selamat Datang di Simple Aff Plus!',
            'registrasi_sponsor' => 'Member Baru Bergabung',
            'registrasi_admin' => 'Registrasi Member Baru',
            'upgrade_member' => 'Upgrade Berhasil!',
            'upgrade_sponsor' => 'Member Upgrade',
            'order_member' => 'Konfirmasi Order',
            'order_sponsor' => 'Order Baru dari Member',
            'prosesorder_member' => 'Order Sedang Diproses',
            'prosesorder_sponsor' => 'Order Member Diproses',
            'cair_komisi_member' => 'Pencairan Komisi'
        ];
        
        return $settings[$subjectKey] ?? $defaultSubjects[$type . '_' . $target] ?? 'Notifikasi Simple Aff Plus';
    }
    

    
    /**
     * Log aktivitas email
     */
    private function logActivity($email, $subject, $status, $response = '') {
        global $con;
        
        if (!$con) {
            return; // Skip logging jika tidak ada koneksi
        }
        
        $sql = "INSERT INTO `epi_email_log` 
                (`email`, `subject`, `status`, `response`, `sent_at`) 
                VALUES (?, ?, ?, ?, NOW())";
        
        try {
            $stmt = $con->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('ssss', $email, $subject, $status, $response);
                $stmt->execute();
                $stmt->close();
            }
        } catch (Exception $e) {
            // Silent fail untuk logging
            error_log("MailketingHelper logActivity error: " . $e->getMessage());
        }
    }
    
    /**
     * Get email logs
     */
    public function getEmailLogs($limit = 50, $offset = 0) {
        global $con;
        
        if (!$con) {
            return []; // Return empty jika tidak ada koneksi
        }
        
        $sql = "SELECT * FROM `epi_email_log` 
                ORDER BY `sent_at` DESC 
                LIMIT ? OFFSET ?";
        
        try {
            $stmt = $con->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('ii', $limit, $offset);
                $stmt->execute();
                $result = $stmt->get_result();
                $logs = [];
                while ($row = $result->fetch_assoc()) {
                    $logs[] = $row;
                }
                $stmt->close();
                return $logs;
            }
            return [];
        } catch (Exception $e) {
            error_log("MailketingHelper getEmailLogs error: " . $e->getMessage());
            return [];
        }
    }
    

    
    /**
     * Kirim test email
     */
    public function sendTestEmail($email) {
        try {
            // Debug: Log konfigurasi
            error_log("=== MAILKETING TEST EMAIL DEBUG ===");
            error_log("API Token: " . (empty($this->config['api_token']) ? 'KOSONG' : 'ADA (' . strlen($this->config['api_token']) . ' chars)'));
            error_log("From Email: " . $this->config['from_email']);
            error_log("From Name: " . $this->config['from_name']);
            error_log("Target Email: " . $email);
            
            $subject = 'Test Email dari Mailketing - ' . date('d/m/Y H:i');
            $content = '
                <h2>Test Email Berhasil!</h2>
                <p>Selamat! Koneksi Mailketing Anda berfungsi dengan baik.</p>
                <hr>
                <p><strong>Detail Test:</strong></p>
                <ul>
                    <li>Email Tujuan: ' . $email . '</li>
                    <li>Waktu Kirim: ' . date('d/m/Y H:i:s') . '</li>
                    <li>Provider: Mailketing API</li>
                    <li>Status: Berhasil</li>
                </ul>
                <hr>
                <p><small>Email ini dikirim otomatis dari sistem Simple Aff Plus untuk testing koneksi Mailketing.</small></p>
            ';
            
            // Gunakan fungsi sendEmail yang sudah ada
            $result = $this->sendEmail($email, $subject, $content);
            
            if ($result['status']) {
                return [
                    'success' => true,
                    'message' => 'Test email Mailketing berhasil dikirim ke ' . $email,
                    'debug' => $result['response']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Gagal mengirim test email: ' . $result['message'],
                    'debug' => $result['response']
                ];
            }
        } catch (Exception $e) {
            error_log("Exception in sendTestEmail: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Create email template in Mailketing
     */
    public function createTemplate($name, $content) {
        try {
            $data = [
                'name' => $name,
                'content' => $content,
                'type' => 'html'
            ];
            
            $response = $this->makeRequest('POST', '/templates', $data);
            
            return [
                'success' => true,
                'template_id' => $response['id'] ?? null,
                'message' => 'Template berhasil dibuat',
                'data' => $response
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update email template in Mailketing
     */
    public function updateTemplate($templateId, $name, $content) {
        try {
            $data = [
                'name' => $name,
                'content' => $content,
                'type' => 'html'
            ];
            
            $response = $this->makeRequest('PUT', '/templates/' . $templateId, $data);
            
            return [
                'success' => true,
                'message' => 'Template berhasil diupdate',
                'data' => $response
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get template from Mailketing
     */
    public function getTemplate($templateId) {
        try {
            $response = $this->makeRequest('GET', '/templates/' . $templateId);
            
            return [
                'success' => true,
                'data' => $response
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get all templates from Mailketing
     */
    public function getTemplates() {
        try {
            $response = $this->makeRequest('GET', '/templates');
            
            return [
                'success' => true,
                'data' => $response
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    // ===== METHOD WRAPPER UNTUK EMAILSERVICE COMPATIBILITY =====
    
    /**
     * Kirim notifikasi registrasi ke member
     */
    public function sendRegistrationMember($memberData) {
        return [
            'success' => $this->sendRegistrationNotification($memberData, null, 'member'),
            'message' => 'Registration notification sent to member'
        ];
    }
    
    /**
     * Kirim notifikasi registrasi ke sponsor
     */
    public function sendRegistrationSponsor($memberData, $sponsorData) {
        return [
            'success' => $this->sendRegistrationNotification($memberData, $sponsorData, 'sponsor'),
            'message' => 'Registration notification sent to sponsor'
        ];
    }
    
    /**
     * Kirim notifikasi registrasi ke admin
     */
    public function sendRegistrationAdmin($memberData) {
        return [
            'success' => $this->sendRegistrationNotification($memberData, null, 'admin'),
            'message' => 'Registration notification sent to admin'
        ];
    }
    
    /**
     * Kirim notifikasi upgrade ke member
     */
    public function sendUpgradeMember($memberData, $upgradeData) {
        return [
            'success' => $this->sendUpgradeNotification($memberData, null, 'member'),
            'message' => 'Upgrade notification sent to member'
        ];
    }
    
    /**
     * Kirim notifikasi upgrade ke sponsor
     */
    public function sendUpgradeSponsor($memberData, $upgradeData, $sponsorData) {
        return [
            'success' => $this->sendUpgradeNotification($memberData, $sponsorData, 'sponsor'),
            'message' => 'Upgrade notification sent to sponsor'
        ];
    }
    
    /**
     * Kirim notifikasi upgrade ke admin
     */
    public function sendUpgradeAdmin($memberData, $upgradeData) {
        return [
            'success' => $this->sendUpgradeNotification($memberData, null, 'admin'),
            'message' => 'Upgrade notification sent to admin'
        ];
    }
    
    /**
     * Kirim notifikasi order ke member
     */
    public function sendOrderMember($memberData, $orderData) {
        return [
            'success' => $this->sendOrderNotification($memberData, $orderData, null, 'member'),
            'message' => 'Order notification sent to member'
        ];
    }
    
    /**
     * Kirim notifikasi order ke sponsor
     */
    public function sendOrderSponsor($memberData, $orderData, $sponsorData) {
        return [
            'success' => $this->sendOrderNotification($memberData, $orderData, $sponsorData, 'sponsor'),
            'message' => 'Order notification sent to sponsor'
        ];
    }
    
    /**
     * Kirim notifikasi order ke admin
     */
    public function sendOrderAdmin($memberData, $orderData) {
        return [
            'success' => $this->sendOrderNotification($memberData, $orderData, null, 'admin'),
            'message' => 'Order notification sent to admin'
        ];
    }
    
    /**
     * Kirim notifikasi proses order ke member
     */
    public function sendProcessOrderMember($memberData, $orderData) {
        return [
            'success' => $this->sendProcessOrderNotification($memberData, $orderData, null, 'member'),
            'message' => 'Process order notification sent to member'
        ];
    }
    
    /**
     * Kirim notifikasi proses order ke admin
     */
    public function sendProcessOrderAdmin($memberData, $orderData) {
        return [
            'success' => $this->sendProcessOrderNotification($memberData, $orderData, null, 'admin'),
            'message' => 'Process order notification sent to admin'
        ];
    }
    
    /**
     * Kirim notifikasi withdrawal ke member
     */
    public function sendWithdrawalMember($memberData, $withdrawalData) {
        return [
            'success' => $this->sendCommissionNotification($memberData, $withdrawalData),
            'message' => 'Withdrawal notification sent to member'
        ];
    }
    
    /**
     * Kirim notifikasi withdrawal ke admin
     */
    public function sendWithdrawalAdmin($memberData, $withdrawalData) {
        global $settings;
        
        $adminEmail = $settings['admin_email'] ?? '';
        if (empty($adminEmail)) {
            return [
                'success' => false,
                'message' => 'Admin email not configured'
            ];
        }
        
        $subject = 'Withdrawal Request - ' . ($memberData['mem_nama'] ?? 'Unknown Member');
        $content = "
        <h3>Withdrawal Request</h3>
        <p>Member: {$memberData['mem_nama']}</p>
        <p>Email: {$memberData['mem_email']}</p>
        <p>Amount: " . number_format($withdrawalData['amount'] ?? 0) . "</p>
        <p>Date: " . date('Y-m-d H:i:s') . "</p>
        ";
        
        return [
            'success' => $this->sendEmail($adminEmail, $subject, $content),
            'message' => 'Withdrawal notification sent to admin'
        ];
    }
    
    /**
     * Kirim request CURL ke API Mailketing
     * 
     * @param string $url URL endpoint API
     * @param array $params Parameter yang akan dikirim
     * @return string|false Response dari API atau false jika gagal
     */
    private function sendCurlRequest($url, $params) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'MailketingHelper/1.0');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Log untuk debugging
        error_log("Mailketing API Request - URL: $url, HTTP Code: $httpCode");
        if ($curlError) {
            error_log("Mailketing API cURL Error: $curlError");
            return false;
        }
        
        if ($httpCode !== 200) {
            error_log("Mailketing API HTTP Error $httpCode: $response");
        }
        
        return $response;
    }
    
    /**
     * Log aktivitas email ke database
     * 
     * @param string $provider Provider email (mailketing, smtp, etc)
     * @param string $recipient Email penerima
     * @param string $subject Subject email
     * @param string $status Status pengiriman (success, failed)
     * @param string $message Pesan detail
     */
    private function logEmail($provider, $recipient, $subject, $status, $message = '') {
        try {
            // Pastikan tabel log email ada
            $this->createEmailLogTable();
            
            // Escape data untuk keamanan
            $provider = db_escape($provider);
            $recipient = db_escape($recipient);
            $subject = db_escape($subject);
            $status = db_escape($status);
            $message = db_escape($message);
            
            $sql = "
                INSERT INTO epi_email_logs 
                (method, to_email, subject, status, message, created_at) 
                VALUES ('$provider', '$recipient', '$subject', '$status', '$message', NOW())
            ";
            
            db_query($sql);
            
        } catch (Exception $e) {
            error_log("Failed to log email activity: " . $e->getMessage());
        }
    }
    
    /**
     * Buat tabel log email jika belum ada
     */
    private function createEmailLogTable() {
        $sql = "
        CREATE TABLE IF NOT EXISTS epi_email_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            provider VARCHAR(50) NOT NULL DEFAULT 'mailketing',
            recipient VARCHAR(255) NOT NULL,
            subject VARCHAR(500) NOT NULL,
            status ENUM('success', 'failed') NOT NULL,
            message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_recipient (recipient),
            INDEX idx_status (status),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        db_query($sql);
    }
    
    /**
     * Test koneksi ke API Mailketing
     * 
     * @return array Status koneksi
     */
    public function testConnection() {
        try {
            // Debug: Cek konfigurasi
            if (empty($this->config['api_token'])) {
                return [
                    'status' => false,
                    'success' => false,
                    'message' => 'API Token tidak ditemukan. Pastikan konfigurasi sudah disimpan.',
                    'debug' => 'API Token kosong'
                ];
            }
            
            $credits = $this->checkCredits();
            
            // Debug: Log response
            error_log("Mailketing API Response: " . print_r($credits, true));
            
            if ($credits && isset($credits['status']) && ($credits['status'] === true || $credits['status'] == 'success' || $credits['status'] == '1')) {
                return [
                    'status' => true,
                    'success' => true,
                    'message' => 'Koneksi berhasil! Sisa kredit: ' . ($credits['credits'] ?? 'N/A'),
                    'credits' => $credits['credits'] ?? 0,
                    'user_info' => $credits['user_info'] ?? []
                ];
            } else {
                $errorMsg = 'Koneksi gagal atau API token tidak valid';
                if (is_array($credits)) {
                    $errorMsg .= ' - ' . ($credits['message'] ?? $credits['response'] ?? 'Unknown error');
                } elseif (is_string($credits)) {
                    $errorMsg .= ' - ' . $credits;
                }
                
                return [
                    'status' => false,
                    'success' => false,
                    'message' => $errorMsg,
                    'debug' => [
                        'api_token' => substr($this->config['api_token'], 0, 8) . '...',
                        'response' => $credits
                    ]
                ];
            }
            
        } catch (Exception $e) {
            return [
                'status' => false,
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'debug' => $e->getTraceAsString()
            ];
        }
    }
    

    
    /**
     * Get available shortcodes untuk dokumentasi
     * 
     * @return array List shortcode yang tersedia
     */
    public function getAvailableShortcodes() {
        return [
            'member' => [
                '[mem_nama]' => 'Nama member',
                '[mem_email]' => 'Email member', 
                '[mem_hp]' => 'No HP member',
                '[mem_username]' => 'Username member',
                '[mem_id]' => 'ID member'
            ],
            'sponsor' => [
                '[spon_nama]' => 'Nama sponsor',
                '[spon_email]' => 'Email sponsor',
                '[spon_hp]' => 'No HP sponsor'
            ],
            'system' => [
                '[site_name]' => 'Nama website',
                '[site_url]' => 'URL website',
                '[current_date]' => 'Tanggal saat ini',
                '[current_time]' => 'Waktu saat ini',
                '[current_datetime]' => 'Tanggal dan waktu saat ini',
                '[year]' => 'Tahun saat ini'
            ],
            'order' => [
                '[order_id]' => 'ID order',
                '[order_amount]' => 'Jumlah order',
                '[order_status]' => 'Status order',
                '[order_date]' => 'Tanggal order'
            ]
        ];
    }
    
    /**
     * Make HTTP request to Mailketing API
     * 
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $endpoint API endpoint path
     * @param array $data Request data
     * @return array Response data
     * @throws Exception
     */
    private function makeRequest($method, $endpoint, $data = []) {
        // Base URL untuk template management (berbeda dari send email)
        $baseUrl = 'https://api.mailketing.co.id/api/v1';
        $url = $baseUrl . $endpoint;
        
        // Tambahkan API token ke data
        $data['api_token'] = $this->config['api_token'];
        
        $ch = curl_init();
        
        // Set basic curl options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'MailketingHelper/2.0');
        
        // Set method-specific options
        switch (strtoupper($method)) {
            case 'GET':
                if (!empty($data)) {
                    $url .= '?' . http_build_query($data);
                    curl_setopt($ch, CURLOPT_URL, $url);
                }
                break;
                
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/json'
                ]);
                break;
                
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/json'
                ]);
                break;
                
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!empty($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                }
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/json'
                ]);
                break;
                
            default:
                curl_close($ch);
                throw new Exception("Unsupported HTTP method: $method");
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Log untuk debugging
        error_log("Mailketing API $method Request - URL: $url, HTTP Code: $httpCode");
        
        if ($curlError) {
            error_log("Mailketing API cURL Error: $curlError");
            throw new Exception("cURL Error: $curlError");
        }
        
        if ($httpCode < 200 || $httpCode >= 300) {
            error_log("Mailketing API HTTP Error $httpCode: $response");
            throw new Exception("HTTP Error $httpCode: $response");
        }
        
        $responseData = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response: " . json_last_error_msg());
        }
        
        // Cek status response dari Mailketing
        if (isset($responseData['status']) && $responseData['status'] !== 'success') {
            $errorMsg = $responseData['message'] ?? $responseData['response'] ?? 'Unknown API error';
            throw new Exception("Mailketing API Error: $errorMsg");
        }
        
        return $responseData;
    }
}
?>