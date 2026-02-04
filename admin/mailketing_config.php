<?php
// Konfigurasi Mailketing API
// File: admin/mailketing_config.php

// Include file konfigurasi utama
require_once(dirname(__DIR__) . '/config.php');
require_once(dirname(__DIR__) . '/fungsi.php');
require_once(dirname(__DIR__) . '/class/MailketingHelper.php');

// Redirect ke dashboard jika tidak login atau bukan admin
if (!defined('IS_IN_SCRIPT')) { 
    define('IS_IN_SCRIPT', true);
}

// Cek login dan role admin
if (!isset($datamember) || $datamember['mem_role'] < 9) {
    header('Location: ' . $weburl . 'dashboard/login');
    exit();
}

$message = '';
$messageType = '';

// Proses form submit
if ($_POST) {
    $mailketing_enabled = isset($_POST['mailketing_enabled']) ? '1' : '0';
    $mailketing_api_token = trim($_POST['mailketing_api_token']);
    $mailketing_from_email = trim($_POST['mailketing_from_email']);
    $mailketing_from_name = trim($_POST['mailketing_from_name']);
    $mailketing_list_id = trim($_POST['mailketing_list_id']);
    
    try {
        // Update atau insert konfigurasi
        $configs = [
            'mailketing_enabled' => $mailketing_enabled,
            'mailketing_api_token' => $mailketing_api_token,
            'mailketing_from_email' => $mailketing_from_email,
            'mailketing_from_name' => $mailketing_from_name,
            'mailketing_list_id' => $mailketing_list_id
        ];
        
        foreach ($configs as $label => $value) {
            $query = "INSERT INTO sa_setting (set_label, set_value) VALUES ('$label', '$value') 
                     ON DUPLICATE KEY UPDATE set_value = '$value'";
            db_query($query);
        }
        
        // Test koneksi jika enabled
        if ($mailketing_enabled == '1' && !empty($mailketing_api_token)) {
            $mailketing = new MailketingHelper();
            $testResult = $mailketing->testConnection();
            
            if ($testResult['success']) {
                $message = "Konfigurasi berhasil disimpan! Koneksi API berhasil. Credits tersisa: " . $testResult['credits'];
                $messageType = 'success';
            } else {
                $message = "Konfigurasi disimpan, tapi koneksi API gagal: " . $testResult['message'];
                $messageType = 'warning';
            }
        } else {
            $message = "Konfigurasi berhasil disimpan!";
            $messageType = 'success';
        }
        
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Load konfigurasi saat ini
$current_config = [
    'mailketing_enabled' => db_var("SELECT set_value FROM sa_setting WHERE set_label = 'mailketing_enabled'") ?: '0',
    'mailketing_api_token' => db_var("SELECT set_value FROM sa_setting WHERE set_label = 'mailketing_api_token'") ?: '',
    'mailketing_from_email' => db_var("SELECT set_value FROM sa_setting WHERE set_label = 'mailketing_from_email'") ?: '',
    'mailketing_from_name' => db_var("SELECT set_value FROM sa_setting WHERE set_label = 'mailketing_from_name'") ?: '',
    'mailketing_list_id' => db_var("SELECT set_value FROM sa_setting WHERE set_label = 'mailketing_list_id'") ?: ''
];

// Setup header untuk admin panel
$head['pagetitle'] = 'Konfigurasi Mailketing API';
$head['scripthead'] = '
<style>
    .config-card { margin-bottom: 20px; }
    .status-success { color: #28a745; }
    .status-error { color: #dc3545; }
    .api-info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    .log-table { max-height: 400px; overflow-y: auto; }
    .mailketing-header { 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 30px;
    }
</style>';

showheader($head);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="mailketing-header">
                <h1><i class="fas fa-envelope"></i> Konfigurasi Mailketing API</h1>
                <p class="mb-0">Kelola integrasi email marketing dengan Mailketing.co.id</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType == 'error' ? 'danger' : $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card config-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Tentang Mailketing Integration</h5>
                </div>
                <div class="card-body">
                    <p>Mailketing adalah layanan email marketing yang terintegrasi dengan sistem ini untuk:</p>
                    <ul>
                        <li>Mengirim email reset password</li>
                        <li>Notifikasi pendaftaran member baru</li>
                        <li>Email konfirmasi dan pemberitahuan sistem</li>
                        <li>Fallback otomatis ke SMTP jika Mailketing gagal</li>
                    </ul>
                    <div class="alert alert-info mb-0">
                        <strong>Catatan:</strong> Pastikan domain pengirim sudah ditambahkan di dashboard Mailketing Anda.
                    </div>
                </div>
            </div>

            <div class="card config-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cog"></i> Pengaturan API</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="mailketing_enabled" name="mailketing_enabled" value="1" 
                                       <?php echo $current_config['mailketing_enabled'] == '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="mailketing_enabled">
                                    <strong>Aktifkan Mailketing API</strong>
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="mailketing_api_token" class="form-label">API Token Mailketing:</label>
                            <input type="text" class="form-control" id="mailketing_api_token" name="mailketing_api_token" 
                                   value="<?php echo htmlspecialchars($current_config['mailketing_api_token']); ?>"
                                   placeholder="Masukkan API token dari dashboard Mailketing">
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="mailketing_from_email" class="form-label">Email Pengirim:</label>
                                    <input type="email" class="form-control" id="mailketing_from_email" name="mailketing_from_email" 
                                           value="<?php echo htmlspecialchars($current_config['mailketing_from_email']); ?>"
                                           placeholder="noreply@domain.com">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="mailketing_from_name" class="form-label">Nama Pengirim:</label>
                                    <input type="text" class="form-control" id="mailketing_from_name" name="mailketing_from_name" 
                                           value="<?php echo htmlspecialchars($current_config['mailketing_from_name']); ?>"
                                           placeholder="Nama Website/Perusahaan">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="mailketing_list_id" class="form-label">List ID (Opsional):</label>
                            <input type="text" class="form-control" id="mailketing_list_id" name="mailketing_list_id" 
                                   value="<?php echo htmlspecialchars($current_config['mailketing_list_id']); ?>"
                                   placeholder="ID list untuk menyimpan kontak">
                            <div class="form-text">Kosongkan jika tidak menggunakan list tertentu</div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Konfigurasi
                            </button>
                            <button type="button" class="btn btn-success" onclick="testConnection()">
                                <i class="fas fa-plug"></i> Test Koneksi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function testConnection() {
        const token = document.getElementById('mailketing_api_token').value;
        if (!token) {
            alert('Masukkan API Token terlebih dahulu');
            return;
        }
        
        // Redirect ke halaman test
         window.open('<?php echo $weburl; ?>test_mailketing.php', '_blank');
    }
</script>

<?php showfooter(); ?>