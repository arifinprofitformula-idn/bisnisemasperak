<?php
/**
 * Script Test untuk Mailketing API Integration
 * Gunakan script ini untuk memverifikasi bahwa integrasi Mailketing berfungsi dengan baik
 */

// Include file yang diperlukan
require_once 'config.php';
require_once 'fungsi.php';
require_once 'class/MailketingHelper.php';
require_once 'EmailService.php';

// Set timezone
date_default_timezone_set('Asia/Jakarta');

echo "<h2>Test Mailketing API Integration</h2>";
echo "<p>Waktu test: " . date('Y-m-d H:i:s') . "</p>";

// Test 1: Koneksi ke API Mailketing
echo "<h3>1. Test Koneksi API Mailketing</h3>";
try {
    $mailketing = new MailketingHelper();
    $connectionTest = $mailketing->testConnection();
    
    if ($connectionTest['status']) {
        echo "<p style='color: green;'>✓ Koneksi berhasil: " . $connectionTest['message'] . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Koneksi gagal: " . $connectionTest['message'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Exception: " . $e->getMessage() . "</p>";
}

// Test 2: Cek Credits
echo "<h3>2. Test Cek Credits</h3>";
try {
    $credits = $mailketing->checkCredits();
    if ($credits['status']) {
        echo "<p style='color: green;'>✓ Credits tersedia: " . $credits['credits'] . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Gagal cek credits: " . $credits['message'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Exception: " . $e->getMessage() . "</p>";
}

// Test 3: Test kirim email via MailketingHelper
echo "<h3>3. Test Kirim Email via MailketingHelper</h3>";
$testEmail = 'eoagoldacademy@gmail.com'; // Email test yang valid
$testSubject = 'Test Email Mailketing - ' . date('Y-m-d H:i:s');
$testMessage = '
<h3>Test Email dari Mailketing API</h3>
<p>Ini adalah email test untuk memverifikasi integrasi Mailketing API.</p>
<p>Waktu kirim: ' . date('Y-m-d H:i:s') . '</p>
<p>Jika Anda menerima email ini, berarti integrasi berhasil!</p>
';

try {
    $result = $mailketing->sendEmail($testEmail, $testSubject, $testMessage);
    
    if ($result['status']) {
        echo "<p style='color: green;'>✓ Email berhasil dikirim: " . $result['message'] . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Email gagal dikirim: " . $result['message'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Exception: " . $e->getMessage() . "</p>";
}

// Test 4: Test kirim email via EmailService (dengan fallback)
echo "<h3>4. Test Kirim Email via EmailService</h3>";
try {
    $emailService = new EmailService();
    $result = $emailService->sendEmail($testEmail, $testSubject . ' (via EmailService)', $testMessage);
    
    if ($result['status']) {
        echo "<p style='color: green;'>✓ Email berhasil dikirim via EmailService: " . $result['message'] . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Email gagal dikirim via EmailService: " . $result['message'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Exception: " . $e->getMessage() . "</p>";
}

// Test 5: Test fungsi smtpmailer (legacy)
echo "<h3>5. Test Fungsi smtpmailer (Legacy)</h3>";
require_once 'fungsi.php';
$settings = getsettings();

try {
    $result = smtpmailer($testEmail, $testSubject . ' (via smtpmailer)', $testMessage);
    
    if ($result['status']) {
        echo "<p style='color: green;'>✓ Email berhasil dikirim via smtpmailer: " . $result['message'] . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Email gagal dikirim via smtpmailer: " . $result['message'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Exception: " . $e->getMessage() . "</p>";
}

// Test 6: Test shortcode replacement
echo "<h3>6. Test Shortcode Replacement</h3>";
$testData = [
    'mem_nama' => 'John Doe',
    'mem_email' => 'john@example.com',
    'mem_hp' => '081234567890'
];

$templateMessage = 'Halo [mem_nama], email Anda adalah [mem_email] dan HP [mem_hp]. Terima kasih!';
$processedMessage = $mailketing->replaceShortcodes($templateMessage, $testData);

echo "<p><strong>Template:</strong> " . htmlspecialchars($templateMessage) . "</p>";
echo "<p><strong>Hasil:</strong> " . htmlspecialchars($processedMessage) . "</p>";

if (strpos($processedMessage, '[') === false) {
    echo "<p style='color: green;'>✓ Shortcode replacement berhasil</p>";
} else {
    echo "<p style='color: orange;'>⚠ Masih ada shortcode yang belum diganti</p>";
}

// Test 7: Cek log email
echo "<h3>7. Cek Log Email Terbaru</h3>";
try {
    $query = "SELECT * FROM epi_email_logs ORDER BY created_at DESC LIMIT 5";
    $result = db_query($query);
    
    if ($result) {
        $hasData = false;
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Method</th><th>To Email</th><th>Subject</th><th>Status</th><th>Message</th><th>Created</th></tr>";
        
        while ($row = mysqli_fetch_assoc($result)) {
            $hasData = true;
            $statusColor = $row['status'] == 'success' ? 'green' : 'red';
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['method']) . "</td>";
            echo "<td>" . htmlspecialchars($row['to_email']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($row['subject'], 0, 50)) . "...</td>";
            echo "<td style='color: $statusColor;'>" . htmlspecialchars($row['status']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($row['message'], 0, 100)) . "...</td>";
            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if (!$hasData) {
            echo "<p>Tidak ada log email ditemukan.</p>";
        }
    } else {
        echo "<p>Error: Tidak dapat mengakses tabel log email.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error mengambil log: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Catatan:</strong></p>";
echo "<ul>";
echo "<li>Ganti \$testEmail dengan email yang valid untuk test pengiriman</li>";
echo "<li>Pastikan API Token Mailketing valid dan memiliki credits</li>";
echo "<li>Cek log error di error_log jika ada masalah</li>";
echo "<li>Test ini akan membuat log di tabel epi_email_logs</li>";
echo "</ul>";
?>