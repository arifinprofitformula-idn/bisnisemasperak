<?php 
/**
 * Notifikasi Email dengan Mailketing
 * Pengganti sanotif.php yang menggunakan SMTP
 * 
 * @author Arva Digital Media
 * @version 1.0
 */

require_once 'EmailService.php';

// Inisialisasi EmailService
$emailService = new EmailService();

// Ambil data member
$data = db_row("SELECT * FROM `sa_member` 
LEFT JOIN `sa_sponsor` ON `sa_sponsor`.`sp_mem_id` = `sa_member`.`mem_id` 
WHERE `mem_id`=".$iduser);
$datamember = extractdata($data);
$datamember['kodeaff'] = $weburl.$datamember['kodeaff'];

// Ambil data admin
$dataadmin = db_row("SELECT * FROM `sa_member` WHERE `mem_id`=1");
$dataadmin = extractdata($dataadmin);

// Ambil data sponsor jika ada
$datasponsor = null;
if (isset($data['sp_sponsor_id']) && !empty($data['sp_sponsor_id']) && is_numeric($data['sp_sponsor_id'])) {    
    $sponsorData = db_row("SELECT * FROM `sa_member` WHERE `mem_id`=".$data['sp_sponsor_id']);
    if (isset($sponsorData['mem_id'])) {
        $datasponsor = extractdata($sponsorData);
        $datasponsor['kodeaff'] = $weburl.$datasponsor['kodeaff'];
    }
}

// Handle Password baru
if (isset($datalain['newpass']) && $datalain['newpass'] != '') {
    $datamember['password'] = $datalain['newpass'];
}

// Proses data tambahan
if (isset($datalain) && is_array($datalain) && count($datalain) > 0) {
    foreach ($datalain as $key => $value) {
        $datamember[$key] = $value;
    }
}

// Kirim notifikasi berdasarkan event
$notificationResult = [];

switch ($event) {
    case 'registrasi':
        $notificationResult = $emailService->sendRegistrationNotification($datamember, $datasponsor);
        break;
        
    case 'upgrade':
        $upgradeData = [
            'upgrade_type' => $datalain['upgrade_type'] ?? 'premium',
            'upgrade_date' => date('Y-m-d H:i:s'),
            'upgrade_amount' => $datalain['upgrade_amount'] ?? 0
        ];
        $notificationResult = $emailService->sendUpgradeNotification($datamember, $upgradeData, $datasponsor);
        break;
        
    case 'order':
        $orderData = [
            'order_id' => $datalain['order_id'] ?? '',
            'product_name' => $datalain['product_name'] ?? '',
            'order_amount' => $datalain['order_amount'] ?? 0,
            'order_date' => date('Y-m-d H:i:s'),
            'payment_method' => $datalain['payment_method'] ?? ''
        ];
        $notificationResult = $emailService->sendOrderNotification($datamember, $orderData, $datasponsor);
        break;
        
    case 'prosesorder':
        $processData = [
            'order_id' => $datalain['order_id'] ?? '',
            'product_name' => $datalain['product_name'] ?? '',
            'process_status' => $datalain['process_status'] ?? 'processed',
            'process_date' => date('Y-m-d H:i:s'),
            'tracking_number' => $datalain['tracking_number'] ?? ''
        ];
        $notificationResult = $emailService->sendProcessOrderNotification($datamember, $processData, $datasponsor);
        break;
        
    case 'pencairan':
        $withdrawalData = [
            'withdrawal_id' => $datalain['withdrawal_id'] ?? '',
            'withdrawal_amount' => $datalain['withdrawal_amount'] ?? 0,
            'withdrawal_date' => date('Y-m-d H:i:s'),
            'bank_name' => $datalain['bank_name'] ?? '',
            'account_number' => $datalain['account_number'] ?? '',
            'account_name' => $datalain['account_name'] ?? ''
        ];
        $notificationResult = $emailService->sendWithdrawalNotification($datamember, $withdrawalData);
        break;
        
    case 'reset_password':
        $resetToken = $datalain['reset_token'] ?? '';
        $notificationResult = $emailService->sendPasswordReset($datamember, $resetToken);
        break;
        
    default:
        // Fallback ke sistem lama jika event tidak dikenal
        include 'sanotif.php';
        return;
}

// Log hasil notifikasi
if (is_array($notificationResult)) {
    foreach ($notificationResult as $recipient => $result) {
        if ($result['success']) {
            error_log("Mailketing: Email {$event} berhasil dikirim ke {$recipient}");
        } else {
            error_log("Mailketing: Email {$event} gagal dikirim ke {$recipient} - " . $result['message']);
        }
    }
} else {
    if ($notificationResult) {
        error_log("Mailketing: Email {$event} berhasil dikirim");
    } else {
        error_log("Mailketing: Email {$event} gagal dikirim");
    }
}

// Tetap kirim WhatsApp (tidak berubah)
if (!empty($datamember['whatsapp']) && isset($settings['wa_'.$event.'_member']) && !empty($settings['wa_'.$event.'_member'])) {
    kirimwa($datamember['whatsapp'],$settings['wa_'.$event.'_member']);
}

if (!empty($datasponsor['whatsapp']) && isset($settings['wa_'.$event.'_sponsor']) && !empty($settings['wa_'.$event.'_sponsor'])) {
    kirimwa($datasponsor['whatsapp'],$settings['wa_'.$event.'_sponsor']);
}  

if (!empty($dataadmin['whatsapp']) && isset($settings['wa_'.$event.'_admin']) && !empty($settings['wa_'.$event.'_admin'])) {
    kirimwa($dataadmin['whatsapp'],$settings['wa_'.$event.'_admin']);
}

// Tetap kirim data ke WAFUCB (tidak berubah)
if (isset($settings['wafucb_'.$event]) && is_numeric($settings['wafucb_'.$event])) {
    if (isset($settings['wafucb_val_'.$event]) && $settings['wafucb_val_'.$event] == 1) {
        $validate = 1;
    } else {
        $validate = 0;
    }

    if ($datasponsor) {
        $nearray = array_map(function($key) {
            return 'sp' . $key;
        }, array_keys($datasponsor));

        $newsponsor = array_combine($nearray, array_values($datasponsor));
        
        $newdatamember = array_merge($datamember, $newsponsor);
        
        $postdata = http_build_query($newdatamember);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            ]
        ]);
        
        $result = file_get_contents($settings['wafucb_'.$event], false, $context);
    }
}
?>