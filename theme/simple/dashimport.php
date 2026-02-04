<?php
$__root = dirname(__DIR__, 2);
if (!isset($weburl)) { @include_once $__root . DIRECTORY_SEPARATOR . 'config.php'; }
if (!function_exists('getsettings')) { @include_once $__root . DIRECTORY_SEPARATOR . 'fungsi.php'; }
if (session_status() === PHP_SESSION_NONE) { @session_start(); }
# path: theme/simple/dashimport.php
# Import Data Member via CSV

// Early handler: kirim CSV murni jika diminta, sebelum output HTML apapun
if (isset($_GET['download_template'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="template_import_member.csv"');
    echo "\xEF\xBB\xBF"; // BOM UTF-8
    echo "sponsor,nama,email,whatsapp,password\n";
    echo ",John Doe,john@example.com,081234567890,password123\n";
    exit;
}
// Early download handlers
/* Handler download_registered dikonsolidasikan di bawah dengan cek role */

/* Handler download_duplicates dikonsolidasikan di bawah dengan cek role dan fallback preview */

// Early handler: ekspor CSV hasil import berdasarkan token
if (isset($_GET['download_imported'])) {
    // Cek peran admin/staff
    $roleTmp = null;
    if (isset($datamember) && isset($datamember['mem_role'])) { $roleTmp = (int)$datamember['mem_role']; }
    elseif (isset($datauser) && isset($datauser['mem_role'])) { $roleTmp = (int)$datauser['mem_role']; }
    elseif (isset($_SESSION['admin_role'])) { $roleTmp = (int)$_SESSION['admin_role']; }
    if ($roleTmp === null || $roleTmp < 5) { header('HTTP/1.1 403 Forbidden'); echo 'Forbidden'; exit; }

    $token = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$_GET['download_imported']);
    $root = dirname(__DIR__, 2);
    $resultFile = $root . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'import_result_' . $token . '.json';
    if (!file_exists($resultFile)) {
        header('Content-Type: text/plain; charset=utf-8');
        http_response_code(404);
        echo 'Data hasil import tidak ditemukan atau telah kadaluarsa.';
        exit;
    }
    $data = json_decode(file_get_contents($resultFile), true);
    $rows = isset($data['imported']) && is_array($data['imported']) ? $data['imported'] : [];

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="imported_members_' . $token . '_' . date('Ymd_His') . '.csv"');
    echo "\xEF\xBB\xBF"; // BOM
    // Kolom: sponsor,nama,email,wa_e164,wa_stored,kodeaff,status,tgldaftar
    $out = fopen('php://output', 'w');
    fputcsv($out, ['sponsor','nama','email','wa_e164','wa_stored','kodeaff','status','tgldaftar']);
    foreach ($rows as $r) {
        // Sanitasi CSV injection
        $line = [
            isset($r['sponsor']) ? preg_replace('/^([=+\-@])/', "'\\$1", $r['sponsor']) : '',
            preg_replace('/^([=+\-@])/', "'\\$1", $r['nama'] ?? ''),
            preg_replace('/^([=+\-@])/', "'\\$1", $r['email'] ?? ''),
            $r['wa_e164'] ?? '',
            $r['wa_stored'] ?? '',
            $r['kodeaff'] ?? '',
            $r['status'] ?? '',
            $r['tgldaftar'] ?? ''
        ];
        fputcsv($out, $line);
    }
    fclose($out);
    exit;
}

// Early handler: Unduh hasil upgrade otomatis (import)
if (isset($_GET['download_upgraded'])) {
    // Cek peran admin/staff
    $roleTmp = null;
    if (isset($datamember) && isset($datamember['mem_role'])) { $roleTmp = (int)$datamember['mem_role']; }
    elseif (isset($datauser) && isset($datauser['mem_role'])) { $roleTmp = (int)$datauser['mem_role']; }
    elseif (isset($_SESSION['admin_role'])) { $roleTmp = (int)$_SESSION['admin_role']; }
    if ($roleTmp === null || $roleTmp < 5) { header('HTTP/1.1 403 Forbidden'); echo 'Forbidden'; exit; }

    $token = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$_GET['download_upgraded']);
    $root = dirname(__DIR__, 2);
    $resultFile = $root . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'import_result_' . $token . '.json';
    if (!file_exists($resultFile)) { header('Content-Type: text/plain; charset=utf-8'); http_response_code(404); echo 'Data hasil import tidak ditemukan atau telah kadaluarsa.'; exit; }
    $data = json_decode(file_get_contents($resultFile), true);
    $rows = isset($data['upgraded']) && is_array($data['upgraded']) ? $data['upgraded'] : [];

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="import_upgraded_' . $token . '_' . date('Ymd_His') . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['nama','email','alasan_upgrade','waktu_upgrade']);
    foreach ($rows as $r) {
        $line = [
            $r['nama'] ?? '',
            $r['email'] ?? '',
            $r['reason'] ?? '',
            $r['upgraded_at'] ?? ''
        ];
        $line = array_map(function($v){ return preg_replace('/^([=+\-@])/', "'\\$1", (string)$v); }, $line);
        fputcsv($out, $line);
    }
    fclose($out);
    exit;
}

// Early handler: Unduh daftar tidak memenuhi syarat upgrade (import)
if (isset($_GET['download_not_eligible'])) {
    // Cek peran admin/staff
    $roleTmp = null;
    if (isset($datamember) && isset($datamember['mem_role'])) { $roleTmp = (int)$datamember['mem_role']; }
    elseif (isset($datauser) && isset($datauser['mem_role'])) { $roleTmp = (int)$datauser['mem_role']; }
    elseif (isset($_SESSION['admin_role'])) { $roleTmp = (int)$_SESSION['admin_role']; }
    if ($roleTmp === null || $roleTmp < 5) { header('HTTP/1.1 403 Forbidden'); echo 'Forbidden'; exit; }

    $token = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$_GET['download_not_eligible']);
    $root = dirname(__DIR__, 2);
    $resultFile = $root . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'import_result_' . $token . '.json';
    if (!file_exists($resultFile)) { header('Content-Type: text/plain; charset=utf-8'); http_response_code(404); echo 'Data hasil import tidak ditemukan atau telah kadaluarsa.'; exit; }
    $data = json_decode(file_get_contents($resultFile), true);
    $rows = isset($data['not_eligible']) && is_array($data['not_eligible']) ? $data['not_eligible'] : [];

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="import_not_eligible_' . $token . '_' . date('Ymd_His') . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['nama','email','alasan','status_saat_ini']);
    foreach ($rows as $r) {
        $line = [
            $r['nama'] ?? '',
            $r['email'] ?? '',
            $r['reason'] ?? '',
            $r['current_status'] ?? ''
        ];
        $line = array_map(function($v){ return preg_replace('/^([=+\-@])/', "'\\$1", (string)$v); }, $line);
        fputcsv($out, $line);
    }
    fclose($out);
    exit;
}

// Unduh data EPIC yang identitas/member tidak ditemukan
if (isset($_GET['download_epic_unmatched'])) {
    // Cek peran admin/staff
    $roleTmp = null;
    if (isset($datamember) && isset($datamember['mem_role'])) { $roleTmp = (int)$datamember['mem_role']; }
    elseif (isset($datauser) && isset($datauser['mem_role'])) { $roleTmp = (int)$datauser['mem_role']; }
    elseif (isset($_SESSION['admin_role'])) { $roleTmp = (int)$_SESSION['admin_role']; }
    if ($roleTmp === null || $roleTmp < 5) { header('HTTP/1.1 403 Forbidden'); echo 'Forbidden'; exit; }

    $token = preg_replace('/[^a-zA-Z0-9_\-]/','', $_GET['download_epic_unmatched']);
    $root = dirname(__DIR__, 2);
    $cacheDir = $root . DIRECTORY_SEPARATOR . 'cache';

    $rows = [];
    $previewFile = $cacheDir . DIRECTORY_SEPARATOR . $token . '.json';
    if (file_exists($previewFile)) {
        $payload = json_decode(file_get_contents($previewFile), true);
        if ($payload && isset($payload['preview_epic']['rows'])) {
            foreach ($payload['preview_epic']['rows'] as $r) {
                if (empty($r['target_mem_id'])) {
                    $notes = array_merge($r['cellErrors']['identitas'] ?? [], $r['cellErrors']['link_sertifikat_epic'] ?? []);
                    $rows[] = [
                        'nama' => $r['nama'] ?? '',
                        'email' => $r['email'] ?? '',
                        'whatsapp' => $r['whatsapp'] ?? '',
                        'id_epic_resmi' => $r['id_epic_resmi'] ?? '',
                        'link_sertifikat_epic' => $r['link_sertifikat_epic'] ?? '',
                        'catatan' => implode('; ', $notes)
                    ];
                }
            }
        }
    } else {
        $resultFile = $cacheDir . DIRECTORY_SEPARATOR . 'update_result_' . $token . '.json';
        if (file_exists($resultFile)) {
            $payload = json_decode(file_get_contents($resultFile), true);
            if (isset($payload['unmatched']) && is_array($payload['unmatched'])) {
                foreach ($payload['unmatched'] as $r) {
                    $rows[] = [
                        'nama' => $r['nama'] ?? '',
                        'email' => $r['email'] ?? '',
                        'whatsapp' => $r['whatsapp'] ?? '',
                        'id_epic_resmi' => $r['id_epic_resmi'] ?? '',
                        'link_sertifikat_epic' => $r['link_sertifikat_epic'] ?? '',
                        'catatan' => $r['catatan'] ?? ''
                    ];
                }
            }
        }
    }

    if (empty($rows)) {
        header('Content-Type: text/plain; charset=utf-8');
        http_response_code(404);
        echo 'Data identitas yang tidak ditemukan tidak tersedia untuk token ini.';
        exit;
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="epic_unmatched_' . $token . '_' . date('Ymd_His') . '.csv"');
    echo "\xEF\xBB\xBF"; // BOM
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Nama Lengkap','Alamat Email','Nomor WhatsApp','ID EPIC Resmi','Link Sertifikat EPIC','Catatan']);
    foreach ($rows as $r) {
        $line = [
            $r['nama'] ?? '',
            $r['email'] ?? '',
            $r['whatsapp'] ?? '',
            $r['id_epic_resmi'] ?? '',
            $r['link_sertifikat_epic'] ?? '',
            $r['catatan'] ?? ''
        ];
        $line = array_map(function($v){ return preg_replace('/^([=+\-@])/', "'\\$1", (string)$v); }, $line);
        fputcsv($out, $line);
    }
    fclose($out);
    exit;
}
// Tambahan early handler: Unduh daftar email terdaftar (global)
if (isset($_GET['download_registered'])) {
    // Cek peran admin/staff
    $roleTmp = null;
    if (isset($datamember) && isset($datamember['mem_role'])) { $roleTmp = (int)$datamember['mem_role']; }
    elseif (isset($datauser) && isset($datauser['mem_role'])) { $roleTmp = (int)$datauser['mem_role']; }
    elseif (isset($_SESSION['admin_role'])) { $roleTmp = (int)$_SESSION['admin_role']; }
    if ($roleTmp === null || $roleTmp < 5) { header('HTTP/1.1 403 Forbidden'); echo 'Forbidden'; exit; }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="registered_emails.csv"');
    echo "\xEF\xBB\xBF"; // BOM
    echo "email,nama,whatsapp,kodeaff,status,tgldaftar\n";
    $rows = db_select("SELECT `mem_email`,`mem_nama`,`mem_whatsapp`,`mem_kodeaff`,`mem_status`,`mem_tgldaftar` FROM `sa_member` ORDER BY `mem_tgldaftar` DESC");
    if (is_array($rows)) {
        foreach ($rows as $r) {
            $line = [
                $r['mem_email'],
                $r['mem_nama'],
                $r['mem_whatsapp'],
                $r['mem_kodeaff'],
                $r['mem_status'],
                $r['mem_tgldaftar']
            ];
            echo implode(',', array_map(function($v){ return '"'.str_replace('"','""',$v).'"'; }, $line))."\n";
        }
    }
    exit;
}

// Ekspor data untuk pengisian EPIC (Nama Lengkap, Alamat Email, No. Whatsapp, Sponsor, ID EPIC Resmi, Link Sertifikat EPIC)
if (isset($_GET['export_epic'])) {
    // Cek peran admin/staff
    $roleTmp = null;
    if (isset($datamember) && isset($datamember['mem_role'])) { $roleTmp = (int)$datamember['mem_role']; }
    elseif (isset($datauser) && isset($datauser['mem_role'])) { $roleTmp = (int)$datauser['mem_role']; }
    elseif (isset($_SESSION['admin_role'])) { $roleTmp = (int)$_SESSION['admin_role']; }
    if ($roleTmp === null || $roleTmp < 5) { header('HTTP/1.1 403 Forbidden'); echo 'Forbidden'; exit; }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="members_epic_export_' . date('Ymd_His') . '.csv"');
    echo "\xEF\xBB\xBF"; // BOM
    $out = fopen('php://output', 'w');
    // Header sesuai permintaan
    fputcsv($out, ['Nama Lengkap','Alamat Email','No. Whatsapp','Sponsor','ID EPIC Resmi','Link Sertifikat EPIC']);

    $sql = "SELECT m.mem_id, m.mem_nama, m.mem_email, m.mem_whatsapp, sp.mem_kodeaff AS sponsor_kodeaff
            FROM sa_member m
            LEFT JOIN sa_sponsor s ON s.sp_mem_id = m.mem_id
            LEFT JOIN sa_member sp ON sp.mem_id = s.sp_sponsor_id
            ORDER BY m.mem_tgldaftar DESC";
    $rows = db_select($sql);
    if (is_array($rows)) {
        foreach ($rows as $r) {
            $line = [
                $r['mem_nama'] ?? '',
                $r['mem_email'] ?? '',
                $r['mem_whatsapp'] ?? '',
                $r['sponsor_kodeaff'] ?? '',
                '', // ID EPIC Resmi awalnya kosong
                ''  // Link Sertifikat EPIC awalnya kosong
            ];
            // Sanitasi nilai yang mungkin memicu formula di Excel
            $line = array_map(function($v){ return preg_replace('/^([=+\-@])/', "'\\$1", (string)$v); }, $line);
            fputcsv($out, $line);
        }
    }
    fclose($out);
    exit;
}

// Template CSV untuk Update EPIC Member (mass update)
if (isset($_GET['download_epic_update_template'])) {
    // Cek peran admin/staff
    $roleTmp = null;
    if (isset($datamember) && isset($datamember['mem_role'])) { $roleTmp = (int)$datamember['mem_role']; }
    elseif (isset($datauser) && isset($datauser['mem_role'])) { $roleTmp = (int)$datauser['mem_role']; }
    elseif (isset($_SESSION['admin_role'])) { $roleTmp = (int)$_SESSION['admin_role']; }
    if ($roleTmp === null || $roleTmp < 5) { header('HTTP/1.1 403 Forbidden'); echo 'Forbidden'; exit; }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="template_update_epic_member.csv"');
    echo "\xEF\xBB\xBF"; // BOM UTF-8
    $out = fopen('php://output', 'w');
    // Kolom tersedia (semua optional, minimal salah satu dari Nama/Email harus diisi pada baris):
    fputcsv($out, ['Nama Lengkap','Alamat Email','Nomor WhatsApp','ID EPIC Resmi','Link Sertifikat EPIC','Status']);
    // Contoh baris: status hanya diisi 'Premium' untuk upgrade
    fputcsv($out, ['John Doe','john@example.com','+6281234567890','EPIC12345','https://example.com/cert/EPIC12345','Premium']);
    fclose($out);
    exit;
}
// Tambahan early handler: Unduh daftar email duplikat berdasarkan token preview/import
if (isset($_GET['download_duplicates'])) {
    // Cek peran admin/staff
    $roleTmp = null;
    if (isset($datamember) && isset($datamember['mem_role'])) { $roleTmp = (int)$datamember['mem_role']; }
    elseif (isset($datauser) && isset($datauser['mem_role'])) { $roleTmp = (int)$datauser['mem_role']; }
    elseif (isset($_SESSION['admin_role'])) { $roleTmp = (int)$_SESSION['admin_role']; }
    if ($roleTmp === null || $roleTmp < 5) { header('HTTP/1.1 403 Forbidden'); echo 'Forbidden'; exit; }
    $token = preg_replace('/[^a-zA-Z0-9_\-]/','', $_GET['download_duplicates']);
    $root = dirname(__DIR__, 2);
    $cacheDir = $root . DIRECTORY_SEPARATOR . 'cache';
    $emails = [];
    // Prioritaskan file hasil import jika ada
    $importFile = $cacheDir . DIRECTORY_SEPARATOR . 'import_result_' . $token . '.json';
    if (file_exists($importFile)) {
        $payload = json_decode(file_get_contents($importFile), true);
        if ($payload && isset($payload['duplicates']) && is_array($payload['duplicates'])) {
            $emails = $payload['duplicates'];
        }
    }
    // Jika tidak ada, fallback ke file preview
    if (empty($emails)) {
        $previewFile = $cacheDir . DIRECTORY_SEPARATOR . $token . '.json';
        if (file_exists($previewFile)) {
            $payload = json_decode(file_get_contents($previewFile), true);
            if ($payload && isset($payload['preview']['rows'])) {
                foreach ($payload['preview']['rows'] as $r) {
                    if (!empty($r['cellErrors']['email'])) {
                        foreach ($r['cellErrors']['email'] as $e) {
                            if (stripos($e,'sudah terdaftar') !== false && !empty($r['email'])) { $emails[] = $r['email']; }
                        }
                    }
                }
            }
        }
    }
    $emails = array_values(array_unique($emails));
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="duplicate_emails.csv"');
    echo "\xEF\xBB\xBF"; // BOM
    echo "email\n";
    foreach ($emails as $em) { echo '"'.str_replace('"','""',$em).'"' . "\n"; }
    exit;
}

// Unduh hasil update EPIC berdasarkan token
if (isset($_GET['download_epic_updated'])) {
    // Cek peran admin/staff
    $roleTmp = null;
    if (isset($datamember) && isset($datamember['mem_role'])) { $roleTmp = (int)$datamember['mem_role']; }
    elseif (isset($datauser) && isset($datauser['mem_role'])) { $roleTmp = (int)$datauser['mem_role']; }
    elseif (isset($_SESSION['admin_role'])) { $roleTmp = (int)$_SESSION['admin_role']; }
    if ($roleTmp === null || $roleTmp < 5) { header('HTTP/1.1 403 Forbidden'); echo 'Forbidden'; exit; }

    $token = preg_replace('/[^a-zA-Z0-9_\-]/','', $_GET['download_epic_updated']);
    $root = dirname(__DIR__, 2);
    $resultFile = $root . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'update_result_' . $token . '.json';
    if (!file_exists($resultFile)) {
        header('Content-Type: text/plain; charset=utf-8');
        http_response_code(404);
        echo 'Data hasil update tidak ditemukan atau telah kadaluarsa.';
        exit;
    }
    $data = json_decode(file_get_contents($resultFile), true);
    $rows = isset($data['updated']) && is_array($data['updated']) ? $data['updated'] : [];

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="epic_updated_' . $token . '_' . date('Ymd_His') . '.csv"');
    echo "\xEF\xBB\xBF"; // BOM
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Nama Lengkap','Alamat Email','Nomor WhatsApp','ID EPIC Resmi','Link Sertifikat EPIC','Status']);
    foreach ($rows as $r) {
        $line = [
            $r['nama'] ?? '',
            $r['email'] ?? '',
            $r['whatsapp'] ?? '',
            $r['id_epic_resmi'] ?? '',
            $r['link_sertifikat_epic'] ?? '',
            $r['statusCsv'] ?? ''
        ];
        $line = array_map(function($v){ return preg_replace('/^([=+\-@])/', "'\\$1", (string)$v); }, $line);
        fputcsv($out, $line);
    }
    fclose($out);
    exit;
}

// Unduh hasil upgrade otomatis dari proses EPIC update
if (isset($_GET['download_epic_upgraded'])) {
    // Cek peran admin/staff
    $roleTmp = null;
    if (isset($datamember) && isset($datamember['mem_role'])) { $roleTmp = (int)$datamember['mem_role']; }
    elseif (isset($datauser) && isset($datauser['mem_role'])) { $roleTmp = (int)$datauser['mem_role']; }
    elseif (isset($_SESSION['admin_role'])) { $roleTmp = (int)$_SESSION['admin_role']; }
    if ($roleTmp === null || $roleTmp < 5) { header('HTTP/1.1 403 Forbidden'); echo 'Forbidden'; exit; }

    $token = preg_replace('/[^a-zA-Z0-9_\-]/','', $_GET['download_epic_upgraded']);
    $root = dirname(__DIR__, 2);
    $resultFile = $root . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'update_result_' . $token . '.json';
    if (!file_exists($resultFile)) { header('Content-Type: text/plain; charset=utf-8'); http_response_code(404); echo 'Data hasil update tidak ditemukan atau telah kadaluarsa.'; exit; }
    $data = json_decode(file_get_contents($resultFile), true);
    $rows = isset($data['epic_upgraded']) && is_array($data['epic_upgraded']) ? $data['epic_upgraded'] : [];

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="epic_upgraded_' . $token . '_' . date('Ymd_His') . '.csv"');
    echo "\xEF\xBB\xBF"; // BOM
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Nama Lengkap','Alamat Email','Alasan Upgrade','Waktu Upgrade']);
    foreach ($rows as $r) {
        $line = [
            $r['nama'] ?? '',
            $r['email'] ?? '',
            $r['reason'] ?? '',
            $r['upgraded_at'] ?? ''
        ];
        $line = array_map(function($v){ return preg_replace('/^([=+\-@])/', "'\\$1", (string)$v); }, $line);
        fputcsv($out, $line);
    }
    fclose($out);
    exit;
}

// Unduh daftar tidak memenuhi syarat upgrade dari proses EPIC update
if (isset($_GET['download_epic_not_eligible'])) {
    // Cek peran admin/staff
    $roleTmp = null;
    if (isset($datamember) && isset($datamember['mem_role'])) { $roleTmp = (int)$datamember['mem_role']; }
    elseif (isset($datauser) && isset($datauser['mem_role'])) { $roleTmp = (int)$datauser['mem_role']; }
    elseif (isset($_SESSION['admin_role'])) { $roleTmp = (int)$_SESSION['admin_role']; }
    if ($roleTmp === null || $roleTmp < 5) { header('HTTP/1.1 403 Forbidden'); echo 'Forbidden'; exit; }

    $token = preg_replace('/[^a-zA-Z0-9_\-]/','', $_GET['download_epic_not_eligible']);
    $root = dirname(__DIR__, 2);
    $resultFile = $root . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'update_result_' . $token . '.json';
    if (!file_exists($resultFile)) { header('Content-Type: text/plain; charset=utf-8'); http_response_code(404); echo 'Data hasil update tidak ditemukan atau telah kadaluarsa.'; exit; }
    $data = json_decode(file_get_contents($resultFile), true);
    $rows = isset($data['epic_not_eligible']) && is_array($data['epic_not_eligible']) ? $data['epic_not_eligible'] : [];

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="epic_not_eligible_' . $token . '_' . date('Ymd_His') . '.csv"');
    echo "\xEF\xBB\xBF"; // BOM
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Nama Lengkap','Alamat Email','Alasan','Status Saat Ini']);
    foreach ($rows as $r) {
        $line = [
            $r['nama'] ?? '',
            $r['email'] ?? '',
            $r['reason'] ?? '',
            $r['current_status'] ?? ''
        ];
        $line = array_map(function($v){ return preg_replace('/^([=+\-@])/', "'\\$1", (string)$v); }, $line);
        fputcsv($out, $line);
    }
    fclose($out);
    exit;
}

$head['pagetitle'] = 'Import Data Member';
$head['description'] = 'Import data member menggunakan file CSV';

// Cek akses admin/staff - gunakan flag, jangan return agar layout tetap utuh
$noAccess = false;
// Tentukan role dari $datamember atau $datauser agar kompatibel dengan route /dashboard/* dan /admin/*
$role = null;
if (isset($datamember) && isset($datamember['mem_role'])) {
    $role = (int)$datamember['mem_role'];
} elseif (isset($datauser) && isset($datauser['mem_role'])) {
    $role = (int)$datauser['mem_role'];
} elseif (isset($_SESSION['admin_role'])) {
    $role = (int)$_SESSION['admin_role'];
}
$noAccess = ($role === null || $role < 5);
// Penentuan $noAccess menggunakan variabel $role yang diambil dari $datamember atau $datauser

// Proses upload CSV
$uploadResult = '';
$epicUploadResult = '';
$importStats = array();
$previewToken = '';
// Tambah CSRF token init
if (!isset($_SESSION['csrf_token'])) {
    try { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); } catch (Exception $e) { $_SESSION['csrf_token'] = md5(uniqid('', true)); }
}

if (!$noAccess && isset($_POST['upload_preview']) && isset($_FILES['csv_file'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $uploadResult = '<div class="alert alert-danger"><i class="fas fa-shield-alt"></i> CSRF token tidak valid. Silakan muat ulang halaman dan coba lagi.</div>';
    } else {
        $preview = parseCSVForPreview($_FILES['csv_file']);
        if (is_array($preview) && isset($preview['rows'])) {
            $previewToken = savePreviewToCache($preview);
            $uploadResult = renderPreviewUI($preview, $previewToken);
        } else {
            // $preview berisi HTML alert error string
            $uploadResult = $preview;
        }
    }
}

if (!$noAccess && isset($_POST['confirm_import']) && isset($_POST['preview_token'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $uploadResult = '<div class="alert alert-danger"><i class="fas fa-shield-alt"></i> CSRF token tidak valid. Proses dibatalkan.</div>';
    } else {
        $uploadResult = performImportFromPreview($_POST['preview_token']);
    }
}

// EPIC update: upload & preview
if (!$noAccess && isset($_POST['upload_epic_preview']) && isset($_FILES['epic_csv'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $epicUploadResult = '<div class="alert alert-danger"><i class="fas fa-shield-alt"></i> CSRF token tidak valid. Silakan muat ulang halaman dan coba lagi.</div>';
    } else {
        $previewEpic = parseCSVForEpicUpdate($_FILES['epic_csv']);
        if (is_array($previewEpic) && isset($previewEpic['rows'])) {
            $epicToken = saveEpicPreviewToCache($previewEpic);
            $epicUploadResult = renderEpicUpdatePreviewUI($previewEpic, $epicToken);
        } else {
            $epicUploadResult = $previewEpic; // berisi alert HTML saat terjadi error
        }
    }
}

// EPIC update: konfirmasi eksekusi
if (!$noAccess && isset($_POST['confirm_epic_update']) && isset($_POST['epic_preview_token'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $epicUploadResult = '<div class="alert alert-danger"><i class="fas fa-shield-alt"></i> CSRF token tidak valid. Proses dibatalkan.</div>';
    } else {
        $epicUploadResult = performEpicUpdateFromPreview($_POST['epic_preview_token']);
    }
}

function processCSVUpload($file) {
    global $importStats;
    
    // Inisialisasi counter untuk mencegah undefined variables
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    $idsponsor = 0;
    
    // Validasi file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Error upload file: ' . $file['error'] . '</div>';
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB max
        return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> File terlalu besar. Maksimal 5MB.</div>';
    }
    
    $fileInfo = pathinfo($file['name']);
    if (strtolower($fileInfo['extension']) !== 'csv') {
        return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> File harus berformat CSV.</div>';
    }
    
    // Baca file CSV
    $csvData = array();
    if (($handle = fopen($file['tmp_name'], 'r')) !== FALSE) {
        $header = fgetcsv($handle); // Baca header
        
        // Validasi header (case-insensitive)
        $requiredColumns = array('sponsor', 'nama', 'email', 'whatsapp', 'password');
        $headerLower = array_map('strtolower', array_map('trim', $header));
        $missingColumns = array();
        foreach ($requiredColumns as $col) {
            if (!in_array(strtolower($col), $headerLower)) {
                $missingColumns[] = $col;
            }
        }
        // Kolom opsional: status (1=Free, 2=Premium)
        $hasStatusColumn = in_array('status', $headerLower, true);
        if (!empty($missingColumns)) {
            fclose($handle);
            return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Kolom wajib tidak ditemukan: ' . implode(', ', $missingColumns) . '</div>';
        }
        // Mapping kolom
        $columnMap = array();
        foreach ($header as $index => $columnName) {
            $cleanName = strtolower(trim($columnName));
            $columnMap[$cleanName] = $index;
        }
        $rowNumber = 1;
        while (($data = fgetcsv($handle)) !== FALSE) {
            $rowNumber++;
            $row = [
                'rowNumber' => $rowNumber,
                'sponsor' => isset($columnMap['sponsor']) ? trim($data[$columnMap['sponsor']]) : '',
                'nama' => isset($columnMap['nama']) ? trim($data[$columnMap['nama']]) : '',
                'email' => isset($columnMap['email']) ? trim($data[$columnMap['email']]) : '',
                'whatsapp' => isset($columnMap['whatsapp']) ? trim($data[$columnMap['whatsapp']]) : '',
                'password' => isset($columnMap['password']) ? trim($data[$columnMap['password']]) : '',
                // status opsional
                'status' => ($hasStatusColumn && isset($columnMap['status'])) ? trim($data[$columnMap['status']]) : ''
            ];
            // Normalisasi status: kosong -> 1, string nama -> mapping, selain 1/2 -> 1
            $statusNorm = 1;
            if ($row['status'] !== '') {
                $val = strtolower($row['status']);
                if ($val === 'premium' || $val === '2') { $statusNorm = 2; }
                elseif ($val === 'free' || $val === '1') { $statusNorm = 1; }
                else { $statusNorm = 1; }
            }
            $row['statusNorm'] = $statusNorm;
            // Validasi
            $cellErrors = [
                'sponsor' => [], 'nama' => [], 'email' => [], 'whatsapp' => [], 'password' => [], 'status' => []
            ];
            if (empty($row['nama'])) $cellErrors['nama'][] = 'Nama kosong';
            if (empty($row['email']) || !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) $cellErrors['email'][] = 'Email tidak valid';
            if (empty($row['whatsapp'])) $cellErrors['whatsapp'][] = 'WhatsApp kosong';
            // Validasi E.164
            $waE164 = waToE164($row['whatsapp']);
            if ($waE164 === null) { $cellErrors['whatsapp'][] = 'WhatsApp tidak sesuai E.164 (+628...)'; }
            // Status opsional: gunakan statusNorm yang telah dihitung sebelumnya (1=Free, 2=Premium)
            if ($hasStatusColumn && !in_array($row['statusNorm'], [1,2], true)) { $cellErrors['status'][] = 'Status tidak valid'; }
            $row['idsponsor'] = $idsponsor;
            // WhatsApp format preview
            $row['whatsappFormatted'] = formatwa($row['whatsapp']);
            // Tambah kolom WA E.164 untuk preview + validasi ringan
            $waE164Preview = waToE164($row['whatsapp']);
            $row['wa_e164'] = $waE164Preview ?? '';
            if ($waE164Preview === null) { $cellErrors['whatsapp'][] = 'WhatsApp tidak sesuai E.164'; }
            // Status opsional: gunakan statusNorm yang telah dihitung sebelumnya (1=Free, 2=Premium)
            if ($hasStatusColumn && !in_array($row['statusNorm'], [1,2], true)) { $cellErrors['status'][] = 'Status tidak valid'; }
            $row['idsponsor'] = $idsponsor;
            // WhatsApp format preview
            $row['whatsappFormatted'] = formatwa($row['whatsapp']);
            $row['wa_e164'] = $waE164; // bisa null jika invalid
            if (!empty($row['email']) && db_exist("SELECT `mem_email` FROM `sa_member` WHERE `mem_email`='" . cek($row['email']) . "'")) {
                $cellErrors['email'][] = 'Email sudah terdaftar';
            }
            
            // Cek sponsor
            $rowErrors = [];
            $idsponsor = 0;
            if (!empty($row['sponsor'])) {
                $sponsorId = db_var("SELECT `mem_id` FROM `sa_member` WHERE `mem_kodeaff`='" . txtonly(strtolower($row['sponsor'])) . "'");
                if (is_numeric($sponsorId)) {
                    $idsponsor = $sponsorId;
                } else {
                    $rowErrors[] = 'Sponsor tidak ditemukan';
                }
            }
            
            if (!empty($rowErrors)) {
                $errorCount++;
                $errors[] = "Baris $rowNumber: " . implode(', ', $rowErrors);
                continue;
            }
            
            // Insert data member (gunakan data dari $row)
            $kodeaff = generateKodeAff($row['nama']);
            $whatsappFormatted = formatwa($row['whatsapp']);
            
            $newuserid = db_insert("INSERT INTO `sa_member` 
                (`mem_nama`, `mem_email`, `mem_password`, `mem_whatsapp`, `mem_kodeaff`, 
                `mem_tgldaftar`, `mem_status`, `mem_role`) 
                VALUES ('" . cek($row['nama']) . "', '" . cek($row['email']) . "', '" . create_hash($row['password']) . "', 
                '" . cek($whatsappFormatted) . "', '" . cek($kodeaff) . "', 
                '" . date('Y-m-d H:i:s') . "', " . (int)$row['statusNorm'] . ", 1)");
            
            if (is_numeric($newuserid)) {
                // Insert sponsor relationship
                if ($idsponsor > 0) {
                    $network = '[' . numonly($idsponsor) . ']' . db_var("SELECT `sp_network` FROM `sa_sponsor` WHERE `sp_mem_id`=" . $idsponsor);
                    db_insert("INSERT INTO `sa_sponsor` (`sp_mem_id`, `sp_sponsor_id`, `sp_network`) VALUES ($newuserid, $idsponsor, '" . $network . "')");
                }
                
                $successCount++;
                
                // Kirim notifikasi (opsional)
                // $customfield['newpass'] = $row['password'];
                // sa_notif('daftar', $newuserid, $customfield);
            } else {
                $errorCount++;
                $errors[] = "Baris $rowNumber: Gagal menyimpan ke database";
            }
        }
        
        fclose($handle);
        
        $importStats = array(
            'success' => $successCount,
            'error' => $errorCount,
            'total' => $successCount + $errorCount
        );
        
        $result = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> <strong>Import Selesai!</strong><br/>';
        $result .= "Total data: {$importStats['total']}<br/>";
        $result .= "Berhasil: {$importStats['success']}<br/>";
        $result .= "Error: {$importStats['error']}</div>";
        
        if (!empty($errors)) {
            $result .= '<div class="alert alert-warning"><strong>Detail Error:</strong><br/>' . implode('<br/>', array_slice($errors, 0, 10));
            if (count($errors) > 10) {
                $result .= '<br/>... dan ' . (count($errors) - 10) . ' error lainnya';
            }
            $result .= '</div>';
        }
        
        return $result;
    } else {
        return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Gagal membaca file CSV.</div>';
    }
}

function generateKodeAff($nama) {
    $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nama));
    $kode = substr($base, 0, 8) . rand(100, 999);
    
    // Pastikan unik
    while (db_exist("SELECT `mem_kodeaff` FROM `sa_member` WHERE `mem_kodeaff`='" . $kode . "'")) {
        $kode = substr($base, 0, 8) . rand(100, 999);
    }
    
    return $kode;
}

showheader($head);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-upload"></i> Import Data Member</h5>
                </div>
                <div class="card-body">
                    
                    <?php if (!empty($uploadResult)) echo $uploadResult; ?>
                    
                    <?php if ($noAccess): ?>
                        <div class="alert alert-danger"><i class="fas fa-lock"></i> Akses ditolak. Hanya admin yang dapat mengakses halaman ini.</div>
                    <?php else: ?>
                    <!-- Form Upload -->
                    <form method="post" enctype="multipart/form-data" class="mb-4">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="csv_file" class="form-label">Pilih File CSV</label>
                                    <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                                    <div class="form-text">Format file: CSV, maksimal 5MB</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" name="upload_preview" class="btn btn-primary d-block">
                                        <i class="fas fa-eye"></i> Upload & Preview
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Panduan Format CSV -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Format CSV Wajib</h6>
                                </div>
                                <div class="card-body">
                                    <p>File CSV harus memiliki kolom-kolom berikut (urutan bebas):</p>
                                    <ul class="list-unstyled">
                                        <li><strong>sponsor</strong> - Kode affiliasi sponsor (opsional)</li>
                                        <li><strong>nama</strong> - Nama lengkap member</li>
                                        <li><strong>email</strong> - Email valid dan unik</li>
                                        <li><strong>whatsapp</strong> - Nomor WhatsApp (format: 08123456789)</li>
                                        <li><strong>password</strong> - Password untuk login</li>
                                        <li><strong>status</strong> - Free Member atau Premium Member</li>
                                    </ul>
                                    
                                    <div class="alert alert-warning">
                                        <small><i class="fas fa-exclamation-triangle"></i> 
                                        <strong>Penting:</strong> Baris pertama harus berisi nama kolom (header)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="fas fa-download"></i> Template CSV</h6>
                                </div>
                                <div class="card-body">
                                    <p>Download template CSV untuk memudahkan import:</p>
                                    <a href="?download_template=1" class="btn btn-success btn-sm">
                                        <i class="fas fa-download"></i> Download Template
                                    </a>
                                    <a href="?download_registered=1" class="btn btn-outline-success btn-sm ms-2">
                                        <i class="fas fa-address-book"></i> Unduh Email Terdaftar
                                    </a>
                                    
                                    <hr>
                                    <p><strong>Contoh format CSV (header + satu baris contoh):</strong></p>
                                    <div class="bg-light p-2 rounded">
                                        <code>
                                            sponsor,nama,email,whatsapp,password,status  <br>
                                            ,John Doe,john@example.com,081234567890,password123,2
                                        </code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Catatan Penting -->
                    <div class="card border-warning mt-3">
                        <div class="card-header bg-warning">
                            <h6 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Catatan Penting</h6>
                        </div>
                        <div class="card-body">
                            <ul class="mb-0">
                                <li>Email yang sudah terdaftar akan dilewati</li>
                                <li>Kode affiliasi akan dibuat otomatis jika tidak ada sponsor</li>
                                <li>Semua member yang diimport akan memiliki status aktif</li>
                                <li>Backup database sebelum melakukan import data besar</li>
                                <li>Notifikasi email registrasi tidak dikirim otomatis saat import</li>
                            </ul>
                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="pt-2">
                        <h5 class="mb-3"><i class="fas fa-certificate"></i> Update EPIC Member</h5>
                        <?php if (!empty($epicUploadResult)) echo $epicUploadResult; ?>
                        <form method="post" enctype="multipart/form-data" class="mb-4">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="epic_csv" class="form-label">Pilih File CSV Update EPIC</label>
                                        <input type="file" class="form-control" id="epic_csv" name="epic_csv" accept=".csv" required>
                                        <div class="form-text">Format file: CSV, maksimal 5MB</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="submit" name="upload_epic_preview" class="btn btn-danger d-block">
                                            <i class="fas fa-eye"></i> Upload & Preview Update EPIC
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="fas fa-info-circle"></i> Format CSV Update EPIC</h6>
                                    </div>
                                    <div class="card-body">
                                        <p>File CSV untuk update EPIC menggunakan header berikut (urutan disarankan):</p>
                                        <ul class="list-unstyled">
                                            <li><strong>Nama Lengkap</strong> atau <strong>Alamat Email</strong> (minimal salah satu untuk identitas)</li>
                                            <li><strong>Nomor WhatsApp</strong> (opsional; format internasional E.164, contoh: +6281234567890)</li>
                                            <li><strong>ID EPIC Resmi</strong> (alfanumerik)</li>
                                            <li><strong>Link Sertifikat EPIC</strong> (URL http/https)</li>
                                            <li><strong>Status</strong> (opsional; isi <em>Premium</em> atau <em>2</em> untuk upgrade Free→Premium)</li>
                                        </ul>
                                        <div class="alert alert-warning">
                                            <small><i class="fas fa-exclamation-triangle"></i>
                                            <strong>Penting:</strong> Minimal salah satu dari kolom EPIC (<em>ID EPIC Resmi</em> atau <em>Link Sertifikat EPIC</em>) harus diisi per baris agar baris tersebut dapat diupdate. Untuk melakukan upgrade Free→Premium melalui menu ini, <strong>Email</strong> dan <strong>ID EPIC Resmi</strong> wajib terisi di baris tersebut.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="fas fa-table"></i> Contoh CSV</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="bg-light p-2 rounded">
                                            <code>
                                                Nama Lengkap,Alamat Email,Nomor WhatsApp,ID EPIC Resmi,Link Sertifikat EPIC,Status<br>
                                                John Doe,john@example.com,+6281234567890,EPIC12345,https://example.com/cert/EPIC12345,Premium
                                            </code>
                                        </div>
                                        <small class="text-muted d-block mt-2">Baris pertama harus berisi nama kolom (header).</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<?php showfooter();
// Tambahan: fungsi parsing untuk preview (tanpa insert)
function parseCSVForPreview($file) {
    // Validasi file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Error upload file: ' . $file['error'] . '</div>';
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> File terlalu besar. Maksimal 5MB.</div>';
    }
    $fileInfo = pathinfo($file['name']);
    if (strtolower($fileInfo['extension']) !== 'csv') {
        return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> File harus berformat CSV.</div>';
    }

    $result = [
        'rows' => [],
        'errors' => [],
        'stats' => ['valid' => 0, 'invalid' => 0, 'total' => 0]
    ];

    if (($handle = fopen($file['tmp_name'], 'r')) !== FALSE) {
        $header = fgetcsv($handle);
        // Strip UTF-8 BOM jika ada pada kolom pertama header
        if (isset($header[0])) { $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]); }
        $requiredColumns = array('sponsor', 'nama', 'email', 'whatsapp', 'password');
        $headerLower = array_map('strtolower', array_map('trim', $header));
        $missingColumns = array();
        foreach ($requiredColumns as $col) {
            if (!in_array(strtolower($col), $headerLower)) {
                $missingColumns[] = $col;
            }
        }
        if (!empty($missingColumns)) {
            fclose($handle);
            return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Kolom wajib tidak ditemukan: ' . implode(', ', $missingColumns) . '</div>';
        }
        // Deteksi kolom opsional 'status'
        $hasStatusColumn = in_array('status', $headerLower, true);
        // Mapping kolom
        $columnMap = array();
        foreach ($header as $index => $columnName) {
            $cleanName = strtolower(trim($columnName));
            $columnMap[$cleanName] = $index;
        }
        $rowNumber = 1;
        while (($data = fgetcsv($handle)) !== FALSE) {
            $rowNumber++;
            $row = [
                'rowNumber' => $rowNumber,
                'sponsor' => isset($columnMap['sponsor']) ? trim($data[$columnMap['sponsor']]) : '',
                'nama' => isset($columnMap['nama']) ? trim($data[$columnMap['nama']]) : '',
                'email' => isset($columnMap['email']) ? trim($data[$columnMap['email']]) : '',
                'whatsapp' => isset($columnMap['whatsapp']) ? trim($data[$columnMap['whatsapp']]) : '',
                'password' => isset($columnMap['password']) ? trim($data[$columnMap['password']]) : '',
                'status' => ($hasStatusColumn && isset($columnMap['status'])) ? trim($data[$columnMap['status']]) : ''
            ];
            // Validasi
            $cellErrors = [
                'sponsor' => [], 'nama' => [], 'email' => [], 'whatsapp' => [], 'password' => []
            ];
            if (empty($row['nama'])) $cellErrors['nama'][] = 'Nama kosong';
            if (empty($row['email']) || !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) $cellErrors['email'][] = 'Email tidak valid';
            if (empty($row['whatsapp'])) $cellErrors['whatsapp'][] = 'WhatsApp kosong';
            if (empty($row['password'])) $cellErrors['password'][] = 'Password kosong';
            if (!empty($row['email']) && db_exist("SELECT `mem_email` FROM `sa_member` WHERE `mem_email`='" . cek($row['email']) . "'")) {
                $cellErrors['email'][] = 'Email sudah terdaftar';
            }
            
            // Normalisasi Status: 1=Free, 2=Premium
            $statusNorm = 1;
            if ($hasStatusColumn && $row['status'] !== '') {
                $val = strtolower(trim($row['status']));
                if ($val === 'premium' || $val === '2') { $statusNorm = 2; }
                elseif ($val === 'free' || $val === '1') { $statusNorm = 1; }
                else { $statusNorm = 1; }
            }
            $row['statusNorm'] = $statusNorm;
            if ($hasStatusColumn && !in_array($statusNorm, [1,2], true)) { $cellErrors['status'][] = 'Status tidak valid'; }
            
            // Sponsor
            $idsponsor = 0;
            if (!empty($row['sponsor'])) {
                $sponsorId = db_var("SELECT `mem_id` FROM `sa_member` WHERE `mem_kodeaff`='" . txtonly(strtolower($row['sponsor'])) . "'");
                if (is_numeric($sponsorId)) { $idsponsor = $sponsorId; }
                else { $cellErrors['sponsor'][] = 'Sponsor tidak ditemukan'; }
            }
            $row['idsponsor'] = $idsponsor;
            // WhatsApp format preview
            $row['whatsappFormatted'] = formatwa($row['whatsapp']);
            // Tambahkan konversi & validasi E.164
            $waE164 = waToE164($row['whatsapp']);
            if ($waE164 === null) { $cellErrors['whatsapp'][] = 'WhatsApp tidak sesuai E.164'; }
            $row['wa_e164'] = $waE164 ?? '';

            $hasErrors = false;
            foreach ($cellErrors as $arr) { if (!empty($arr)) { $hasErrors = true; break; } }
            if ($hasErrors) {
                $result['stats']['invalid']++;
                $result['errors'][] = "Baris {$rowNumber}: " . implode(', ', array_merge($cellErrors['sponsor'], $cellErrors['nama'], $cellErrors['email'], $cellErrors['whatsapp'], $cellErrors['password']));
            } else {
                $result['stats']['valid']++;
            }
            $row['cellErrors'] = $cellErrors;
            $result['rows'][] = $row;
        }
        fclose($handle);
        $result['stats']['total'] = count($result['rows']);
        return $result;
    }
    return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Gagal membaca file CSV.</div>';
}

function savePreviewToCache($preview) {
    $root = dirname(__DIR__, 2);
    $cacheDir = $root . DIRECTORY_SEPARATOR . 'cache';
    if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0777, true); }
    $token = 'import_preview_' . uniqid();
    $payload = [
        'created_at' => date('Y-m-d H:i:s'),
        'user' => isset($_SESSION['sauser']) ? $_SESSION['sauser'] : 'unknown',
        'preview' => $preview
    ];
    file_put_contents($cacheDir . DIRECTORY_SEPARATOR . $token . '.json', json_encode($payload));
    return $token;
}

function renderPreviewUI($preview, $token) {
    $stats = $preview['stats'];
    $rows = $preview['rows'];
    $html = '';
    $html .= '<style>.cell-error{background:#ffe9e9;border:1px solid #f5c2c2}.cell-ok{background:#e9ffe9}.preview-table{font-size:12px}.preview-table th, .preview-table td{padding:6px}</style>';
    $html .= '<div class="alert alert-info"><strong>Preview Data Upload</strong><br>Total: ' . $stats['total'] . ' | Valid: ' . $stats['valid'] . ' | Invalid: ' . $stats['invalid'] . '</div>';
    if (!empty($preview['errors'])) {
        $html .= '<div class="alert alert-warning"><strong>Detail Error:</strong><br/>' . htmlspecialchars(implode('<br/>', array_slice($preview['errors'], 0, 20))) . '</div>';
    }
    // Kebijakan impor
    $html .= '<div class="alert alert-secondary"><i class="fas fa-shield-alt"></i> Kebijakan impor: <ul class="mb-0"><li>Nomor WhatsApp wajib sesuai format E.164 (contoh: +6281234567890). Baris invalid akan ditolak.</li><li>Email yang sudah terdaftar akan otomatis di-skip dan tidak diimpor.</li></ul></div>';
    $html .= '<div class="table-responsive"><table class="table table-bordered preview-table"><thead><tr>';
    $html .= '<th>#</th><th>Sponsor</th><th>Nama</th><th>Email</th><th>WhatsApp</th><th>WA (E.164)</th><th>Password</th><th>Status</th>';
    $html .= '</tr></thead><tbody>';
    $maxRows = min(100, count($rows));
    for ($i=0; $i<$maxRows; $i++) {
        $r = $rows[$i];
        $html .= '<tr>';
        $html .= '<td>' . intval($r['rowNumber']) . '</td>';
        $html .= '<td class="' . (!empty($r['cellErrors']['sponsor']) ? 'cell-error' : 'cell-ok') . '">' . htmlspecialchars($r['sponsor']) . '</td>';
        $html .= '<td class="' . (!empty($r['cellErrors']['nama']) ? 'cell-error' : 'cell-ok') . '">' . htmlspecialchars($r['nama']) . '</td>';
        $html .= '<td class="' . (!empty($r['cellErrors']['email']) ? 'cell-error' : 'cell-ok') . '">' . htmlspecialchars($r['email']) . '</td>';
        $html .= '<td class="' . (!empty($r['cellErrors']['whatsapp']) ? 'cell-error' : 'cell-ok') . '">' . htmlspecialchars($r['whatsappFormatted']) . '</td>';
        $html .= '<td class="' . (!empty($r['cellErrors']['whatsapp']) ? 'cell-error' : 'cell-ok') . '">' . htmlspecialchars($r['wa_e164'] ?? '') . '</td>';
        $html .= '<td class="' . (!empty($r['cellErrors']['password']) ? 'cell-error' : 'cell-ok') . '">' . str_repeat('•', max(8, strlen($r['password']))) . '</td>';
        $html .= '<td class="' . (!empty($r['cellErrors']['status']) ? 'cell-error' : 'cell-ok') . '">' . (($r['statusNorm'] ?? 1) == 2 ? 'Premium' : 'Free') . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table></div>';

    $html .= '<form method="post" class="d-flex gap-2">';
    $html .= '<input type="hidden" name="preview_token" value="' . htmlspecialchars($token) . '">';
    $html .= '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'] ?? '') . '">';
    // Tombol selalu aktif
    $html .= '<button type="submit" name="confirm_import" class="btn btn-success"><i class="fas fa-check"></i> OK, Import ke Sistem</button>';
    $html .= '<a href="' . $_SERVER['PHP_SELF'] . '" class="btn btn-secondary"><i class="fas fa-undo"></i> Batalkan</a>';
    $html .= '</form>';

    // Tautan unduh duplikat untuk follow-up
    $html .= '<div class="mt-2">';
    $html .= '<a class="btn btn-outline-dark btn-sm" href="?download_duplicates=' . htmlspecialchars($token) . '"><i class="fas fa-list"></i> Unduh Email Duplikat (registered)</a>';
    $html .= '</div>';

    return $html;
}

function performImportFromPreview($token) {
    $root = dirname(__DIR__, 2);
    $cacheFile = $root . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . $token . '.json';
    if (!file_exists($cacheFile)) {
        return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Token preview tidak ditemukan atau kadaluarsa.</div>';
    }
    $payload = json_decode(file_get_contents($cacheFile), true);
    if (!$payload || !isset($payload['preview'])) {
        return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Data preview rusak.</div>';
    }
    // Opsi: pastikan pengguna sama
    if (isset($_SESSION['sauser']) && isset($payload['user']) && $_SESSION['sauser'] !== $payload['user']) {
        return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Token tidak valid untuk sesi saat ini.</div>';
    }

    $preview = $payload['preview'];
    $rows = $preview['rows'];
    $successCount = 0; $errorCount = 0; $errors = [];
    $duplicates = []; // hanya yang tidak diupgrade
    $importedRows = [];
    $upgradedRows = [];
    $notEligibleRows = [];

    foreach ($rows as $r) {
        // Re-validate minimal untuk idempotensi: hanya cek field wajib non-empty
        if (empty($r['nama']) || empty($r['email']) || empty($r['whatsapp']) || empty($r['password'])) {
            $errorCount++; $errors[] = 'Baris ' . $r['rowNumber'] . ': data tidak lengkap'; continue;
        }
        // Jika email sudah terdaftar, cek apakah memenuhi syarat upgrade otomatis
        if (db_exist("SELECT `mem_email` FROM `sa_member` WHERE `mem_email`='" . cek($r['email']) . "'")) {
            $existing = db_row("SELECT `mem_id`,`mem_nama`,`mem_email`,`mem_status` FROM `sa_member` WHERE `mem_email`='" . cek($r['email']) . "'");
            if (is_array($existing)) {
                $eligible = false; $reason = '';
                // Syarat MVP: CSV menyatakan status=2 (Premium) dan member saat ini Free (1)
                if (isset($r['statusNorm']) && intval($r['statusNorm']) === 2 && intval($existing['mem_status']) === 1) {
                    $eligible = true; $reason = 'CSV status=2';
                }
                if ($eligible) {
                    $okUp = db_query("UPDATE `sa_member` SET `mem_status`=2, `mem_tglupgrade`='" . date('Y-m-d H:i:s') . "' WHERE `mem_id`=" . intval($existing['mem_id']));
                    if ($okUp !== false) {
                        $upgradedRows[] = [
                            'nama' => $existing['mem_nama'] ?? ($r['nama'] ?? ''),
                            'email' => $existing['mem_email'] ?? ($r['email'] ?? ''),
                            'reason' => $reason,
                            'upgraded_at' => date('Y-m-d H:i:s')
                        ];
                        epiLogUpgrade(intval($existing['mem_id']), $existing['mem_email'], $reason, 'import');
                        // Lanjut ke baris berikutnya, tidak diinsert
                        continue;
                    } else {
                        $errors[] = 'Baris ' . $r['rowNumber'] . ': gagal upgrade otomatis (' . db_error() . ')';
                        // jatuhkan ke kategori tidak eligible agar ada tindak lanjut
                        $notEligibleRows[] = [
                            'nama' => $existing['mem_nama'] ?? ($r['nama'] ?? ''),
                            'email' => $existing['mem_email'] ?? ($r['email'] ?? ''),
                            'reason' => 'Gagal update database',
                            'current_status' => intval($existing['mem_status'])
                        ];
                        $duplicates[] = $r['email'];
                        $errorCount++;
                        continue;
                    }
                } else {
                    // Tidak memenuhi syarat
                    $notEligibleRows[] = [
                        'nama' => $existing['mem_nama'] ?? ($r['nama'] ?? ''),
                        'email' => $existing['mem_email'] ?? ($r['email'] ?? ''),
                        'reason' => 'Tidak memenuhi syarat upgrade (status CSV bukan Premium atau sudah Premium)',
                        'current_status' => intval($existing['mem_status'])
                    ];
                    $duplicates[] = $r['email'];
                    $errorCount++; // kategorikan sebagai skip
                    continue;
                }
            } else {
                // fallback jika SELECT gagal, perlakukan sebagai duplikat biasa
                $duplicates[] = $r['email'];
                $errorCount++;
                continue;
            }
        }
        
        // Validasi & normalisasi WhatsApp
        $waStored = formatwa($r['whatsapp']);
        $waE164 = waToE164($r['whatsapp']);
        if ($waE164 === null) {
            $errorCount++; $errors[] = 'Baris ' . $r['rowNumber'] . ': WhatsApp tidak sesuai E.164';
            continue;
        }
        
        $kodeaff = generateKodeAff($r['nama']);
        $statusToSave = isset($r['statusNorm']) && in_array($r['statusNorm'], [1,2], true) ? $r['statusNorm'] : 1;
        $tglUpgradeSql = ($statusToSave === 2) ? "'" . date('Y-m-d H:i:s') . "'" : "NULL";
        $tgldaftar = date('Y-m-d H:i:s');
        
        $newuserid = db_insert("INSERT INTO `sa_member` (`mem_nama`, `mem_email`, `mem_password`, `mem_whatsapp`, `mem_kodeaff`, `mem_tgldaftar`, `mem_tglupgrade`, `mem_status`, `mem_role`) VALUES ('" . cek($r['nama']) . "', '" . cek($r['email']) . "', '" . create_hash($r['password']) . "', '" . cek($waStored) . "', '" . cek($kodeaff) . "', '" . $tgldaftar . "', " . $tglUpgradeSql . ", " . $statusToSave . ", 1)");
        if (is_numeric($newuserid)) {
            if (!empty($r['idsponsor']) && is_numeric($r['idsponsor'])) {
                $idsponsor = numonly($r['idsponsor']);
                $network = '[' . $idsponsor . ']' . db_var("SELECT `sp_network` FROM `sa_sponsor` WHERE `sp_mem_id`=" . $idsponsor);
                db_insert("INSERT INTO `sa_sponsor` (`sp_mem_id`, `sp_sponsor_id`, `sp_network`) VALUES ($newuserid, $idsponsor, '" . $network . "')");
            }
            $successCount++;
            // Simpan baris berhasil untuk keperluan ekspor hasil import
            $importedRows[] = [
                'sponsor' => $r['sponsor'] ?? '',
                'nama' => $r['nama'],
                'email' => $r['email'],
                'wa_e164' => $waE164,
                'wa_stored' => $waStored,
                'kodeaff' => $kodeaff,
                'status' => $statusToSave,
                'tgldaftar' => $tgldaftar
            ];
        } else {
            $errorCount++; $errors[] = 'Baris ' . $r['rowNumber'] . ': gagal menyimpan ke database';
        }
    }

    // Hapus file cache preview setelah impor
    @unlink($cacheFile);

    $total = $successCount + $errorCount;
    // Simpan hasil import untuk keperluan unduh duplikat
    $resultPayload = [
        'token' => $token,
        'success' => $successCount,
        'error' => $errorCount,
        'total' => $total,
        'duplicates' => array_values(array_unique($duplicates)),
        'imported' => $importedRows,
        'upgraded' => $upgradedRows,
        'not_eligible' => $notEligibleRows,
        'created_at' => date('Y-m-d H:i:s')
    ];
    $resultFile = $root . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'import_result_' . $token . '.json';
    file_put_contents($resultFile, json_encode($resultPayload));

    $msg = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> <strong>Import Selesai!</strong><br/>Total: ' . $total . '<br/>Berhasil diimport: ' . $successCount . '<br/>Diupgrade otomatis: ' . count($upgradedRows) . '<br/>Skip/Duplikat (tidak eligible): ' . count($resultPayload['duplicates']) . '<br/>Error lain: ' . ($errorCount - count($resultPayload['duplicates'])) . '</div>';
    if (!empty($resultPayload['duplicates'])) {
        $msg .= '<div class="alert alert-secondary"><i class="fas fa-list"></i> Terdapat ' . count($resultPayload['duplicates']) . ' email sudah terdaftar. <a class="btn btn-outline-dark btn-sm" href="?download_duplicates=' . htmlspecialchars($token) . '">Unduh daftar email duplikat</a></div>';
    }
    if (!empty($importedRows)) {
        $msg .= '<div class="alert alert-success"><i class="fas fa-download"></i> Anda dapat <a class="btn btn-success btn-sm" href="?download_imported=' . htmlspecialchars($token) . '">Unduh CSV hasil import</a>.</div>';
    }
    if (!empty($upgradedRows)) {
        $msg .= '<div class="alert alert-success"><i class="fas fa-download"></i> ' . count($upgradedRows) . ' member diupgrade otomatis. <a class="btn btn-success btn-sm" href="?download_upgraded=' . htmlspecialchars($token) . '">Unduh CSV upgraded</a>.</div>';
    }
    if (!empty($notEligibleRows)) {
        $msg .= '<div class="alert alert-warning"><i class="fas fa-download"></i> ' . count($notEligibleRows) . ' baris tidak memenuhi syarat upgrade. <a class="btn btn-outline-warning btn-sm" href="?download_not_eligible=' . htmlspecialchars($token) . '">Unduh CSV tidak eligible</a>.</div>';
    }
    if (!empty($errors)) {
        $msg .= '<div class="alert alert-warning"><strong>Detail:</strong><br/>' . htmlspecialchars(implode('<br/>', array_slice($errors, 0, 20))) . '</div>';
    }
    return $msg;
}

function parseCSVForEpicUpdate($file) {
    // Validasi file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Error upload file: ' . $file['error'] . '</div>';
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> File terlalu besar. Maksimal 5MB.</div>';
    }
    $fileInfo = pathinfo($file['name']);
    if (strtolower($fileInfo['extension']) !== 'csv') {
        return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> File harus berformat CSV.</div>';
    }

    $normalize = function($s) {
        $s = (string)$s;
        $s = preg_replace('/^\xEF\xBB\xBF/', '', $s); // strip BOM
        $s = strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9 ]+/', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return $s;
    };

    $result = [
        'rows' => [],
        'errors' => [],
        'stats' => ['total' => 0, 'ready' => 0, 'skip' => 0, 'error' => 0]
    ];

    if (($handle = fopen($file['tmp_name'], 'r')) !== FALSE) {
        $header = fgetcsv($handle);
        if (isset($header[0])) { $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]); }
        $headerNorm = [];
        foreach ($header as $idx => $col) { $headerNorm[$normalize($col)] = $idx; }

        // Kandidat kolom identitas & data update
        $iNama = null; $iEmail = null; $iWa = null; $iIdEpic = null; $iLinkEpic = null; $iStatus = null;
        $getIndex = function($cands) use ($headerNorm) {
            foreach ($cands as $c) { if (isset($headerNorm[$c])) return $headerNorm[$c]; }
            return null;
        };
        $iNama = $getIndex(['nama lengkap','nama']);
        $iEmail = $getIndex(['alamat email','email']);
        $iWa = $getIndex(['no whatsapp','no. whatsapp','nomor whatsapp','whatsapp']);
        $iIdEpic = $getIndex(['id epic resmi','id epic','idepic resmi']);
        $iLinkEpic = $getIndex(['link sertifikat epic','sertifikat epic','epic certificate','link epic']);
        $iStatus = $getIndex(['status']);

        if ($iNama === null && $iEmail === null) {
            fclose($handle);
            return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Kolom identitas tidak ditemukan. Minimal "Nama Lengkap" atau "Alamat Email" harus ada.</div>';
        }
        if ($iIdEpic === null && $iLinkEpic === null) {
            fclose($handle);
            return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Kolom EPIC tidak ditemukan. Minimal "ID EPIC Resmi" atau "Link Sertifikat EPIC" harus ada.</div>';
        }

        $rowNumber = 1;
        while (($data = fgetcsv($handle)) !== FALSE) {
            $rowNumber++;
            $nama = ($iNama !== null && isset($data[$iNama])) ? trim($data[$iNama]) : '';
            $email = ($iEmail !== null && isset($data[$iEmail])) ? trim($data[$iEmail]) : '';
            $whatsapp = ($iWa !== null && isset($data[$iWa])) ? trim($data[$iWa]) : '';
            $idEpic = ($iIdEpic !== null && isset($data[$iIdEpic])) ? trim($data[$iIdEpic]) : '';
            $linkEpic = ($iLinkEpic !== null && isset($data[$iLinkEpic])) ? trim($data[$iLinkEpic]) : '';

            $cellErrors = ['identitas' => [], 'link_sertifikat_epic' => [], 'whatsapp' => [], 'status' => []];
            $status = '';

            if ($idEpic === '' && $linkEpic === '') {
                $status = 'Lewati: kolom EPIC kosong';
                $result['stats']['skip']++;
            }

            // Validasi URL jika diisi
            if ($linkEpic !== '' && !filter_var($linkEpic, FILTER_VALIDATE_URL)) {
                $cellErrors['link_sertifikat_epic'][] = 'URL sertifikat tidak valid';
            }

            // Validasi ID EPIC jika diisi (alfanumerik)
            if ($idEpic !== '' && !preg_match('/^[a-zA-Z0-9]+$/', $idEpic)) {
                $cellErrors['link_sertifikat_epic'][] = 'ID EPIC harus alfanumerik';
            }

            // Cari member target berdasarkan email atau nama
            $memId = null;
            if ($email !== '') {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $cellErrors['identitas'][] = 'Email tidak valid';
                } else {
                    $mid = db_var("SELECT `mem_id` FROM `sa_member` WHERE `mem_email`='" . cek($email) . "'");
                    if (is_numeric($mid)) { $memId = intval($mid); } else { $cellErrors['identitas'][] = 'Email tidak ditemukan'; }
                }
            } else {
                if ($nama !== '') {
                    $list = db_select("SELECT `mem_id` FROM `sa_member` WHERE `mem_nama`='" . cek($nama) . "'");
                    if (is_array($list) && count($list) === 1) { $memId = intval($list[0]['mem_id']); }
                    elseif (is_array($list) && count($list) > 1) { $cellErrors['identitas'][] = 'Nama tidak unik (lebih dari 1 hasil)'; }
                    else { $cellErrors['identitas'][] = 'Nama tidak ditemukan'; }
                } else {
                    $cellErrors['identitas'][] = 'Tidak ada kolom identitas (nama/email)';
                }
            }
            // Fallback berdasarkan WhatsApp jika tersedia dan belum ketemu
            if ($memId === null && $whatsapp !== '') {
                $waStored = formatwa($whatsapp);
                $mid = db_var("SELECT `mem_id` FROM `sa_member` WHERE `mem_whatsapp`='" . cek($waStored) . "'");
                if (is_numeric($mid)) { $memId = intval($mid); }
            }

            // Validasi WhatsApp jika diisi: harus E.164 (+62...) atau 62... (akan dinormalisasi)
            if ($whatsapp !== '') {
                $waE164 = waToE164($whatsapp);
                if ($waE164 === null) { $cellErrors['whatsapp'][] = 'Nomor WhatsApp tidak sesuai format internasional (+62...)'; }
                // Jika sudah tahu memId, cek duplikasi WA ke member lain
                if ($memId !== null) {
                    $waStored = formatwa($whatsapp);
                    $dup = db_var("SELECT `mem_id` FROM `sa_member` WHERE `mem_whatsapp`='" . cek($waStored) . "' AND `mem_id`<>" . intval($memId));
                    if (is_numeric($dup)) { $cellErrors['whatsapp'][] = 'Nomor WhatsApp sudah digunakan oleh member lain'; }
                }
            }

            // Normalisasi Status untuk upgrade: hanya dukung 'Premium' atau '2'
            $statusNorm = null; $statusRaw = '';
            if ($iStatus !== null && isset($data[$iStatus])) {
                $statusRaw = trim($data[$iStatus]);
                $sv = strtolower($statusRaw);
                if ($sv === 'premium' || $sv === '2') {
                    $statusNorm = 2;
                    // Syarat upgrade via CSV: Email dan ID EPIC wajib terisi
                    if ($email === '' || $idEpic === '') {
                        $cellErrors['status'][] = 'Upgrade membutuhkan Email dan ID EPIC Resmi';
                    }
                } elseif ($sv !== '') {
                    $cellErrors['status'][] = 'Status tidak valid (isi Premium atau kosong)';
                }
            }

            if ($status === '') {
                if ($memId && empty($cellErrors['link_sertifikat_epic']) && empty($cellErrors['identitas']) && empty($cellErrors['whatsapp']) && empty($cellErrors['status'])) {
                    $status = 'Siap Update';
                    $result['stats']['ready']++;
                } else {
                    $status = 'Error';
                    $result['stats']['error']++;
                }
            }

            $result['rows'][] = [
                'rowNumber' => $rowNumber,
                'nama' => $nama,
                'email' => $email,
                'whatsapp' => $whatsapp,
                'id_epic_resmi' => $idEpic,
                'link_sertifikat_epic' => $linkEpic,
                'target_mem_id' => $memId,
                'statusNorm' => $statusNorm,
                'statusRaw' => $statusRaw,
                'status' => $status,
                'cellErrors' => $cellErrors
            ];
            $result['stats']['total']++;
        }
        fclose($handle);
        return $result;
    } else {
        return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Gagal membaca file CSV.</div>';
    }
}

function saveEpicPreviewToCache($preview) {
    $root = dirname(__DIR__, 2);
    $cacheDir = $root . DIRECTORY_SEPARATOR . 'cache';
    if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0777, true); }
    $token = 'epic_update_' . uniqid();
    $payload = [
        'created_at' => date('Y-m-d H:i:s'),
        'user' => isset($_SESSION['sauser']) ? $_SESSION['sauser'] : 'unknown',
        'preview_epic' => $preview
    ];
    file_put_contents($cacheDir . DIRECTORY_SEPARATOR . $token . '.json', json_encode($payload));
    return $token;
}

function renderEpicUpdatePreviewUI($preview, $token) {
    $stats = $preview['stats'];
    $rows = $preview['rows'];
    $html = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> <strong>Preview Update EPIC</strong><br/>Total: ' . (int)$stats['total'] . ' | Siap Update: ' . (int)$stats['ready'] . ' | Lewati: ' . (int)$stats['skip'] . ' | Error: ' . (int)$stats['error'] . '</div>';
    $html .= '<form method="post" class="mb-3">';
    $html .= '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'] ?? '') . '">';
    $html .= '<input type="hidden" name="epic_preview_token" value="' . htmlspecialchars($token) . '">';
    $html .= '<button type="submit" name="confirm_epic_update" class="btn btn-danger"><i class="fas fa-check"></i> Konfirmasi Update EPIC</button> ';
    $html .= '<a href="' . $_SERVER['PHP_SELF'] . '" class="btn btn-secondary ms-2"><i class="fas fa-times"></i> Batal</a>';
    $html .= '</form>';
    $html .= '<div class="alert alert-warning"><i class="fas fa-download"></i> Jika ada baris yang identitas member-nya tidak ditemukan, Anda dapat <a class="btn btn-outline-warning btn-sm" href="?download_epic_unmatched=' . htmlspecialchars($token) . '">Unduh CSV Nama Tidak Ditemukan</a> untuk diperbaiki pada update berikutnya.</div>';
    $html .= '<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>#</th><th>Nama Lengkap</th><th>Alamat Email</th><th>Nomor WhatsApp</th><th>ID EPIC Resmi</th><th>Link Sertifikat EPIC</th><th>Status</th><th>Catatan</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $notes = [];
        if (!empty($r['cellErrors']['identitas'])) { $notes = array_merge($notes, $r['cellErrors']['identitas']); }
        if (!empty($r['cellErrors']['link_sertifikat_epic'])) { $notes = array_merge($notes, $r['cellErrors']['link_sertifikat_epic']); }
        if (!empty($r['cellErrors']['whatsapp'])) { $notes = array_merge($notes, $r['cellErrors']['whatsapp']); }
        if (!empty($r['cellErrors']['status'])) { $notes = array_merge($notes, $r['cellErrors']['status']); }
        $html .= '<tr>' .
            '<td>' . (int)($r['rowNumber'] ?? 0) . '</td>' .
            '<td>' . htmlspecialchars($r['nama'] ?? '') . '</td>' .
            '<td>' . htmlspecialchars($r['email'] ?? '') . '</td>' .
            '<td>' . htmlspecialchars($r['whatsapp'] ?? '') . '</td>' .
            '<td>' . htmlspecialchars($r['id_epic_resmi'] ?? '') . '</td>' .
            '<td>' . htmlspecialchars($r['link_sertifikat_epic'] ?? '') . '</td>' .
            '<td>' . htmlspecialchars($r['statusRaw'] ?? '') . '</td>' .
            '<td>' . htmlspecialchars(implode('; ', $notes)) . '</td>' .
        '</tr>';
    }
    $html .= '</tbody></table></div>';
    return $html;
}

function performEpicUpdateFromPreview($token) {
    $root = dirname(__DIR__, 2);
    $cacheDir = $root . DIRECTORY_SEPARATOR . 'cache';
    $token = preg_replace('/[^a-zA-Z0-9_-]/','', (string)$token);
    $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . $token . '.json';
    if (!file_exists($cacheFile)) {
        return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Data preview tidak ditemukan. Silakan ulangi upload.</div>';
    }
    $payload = json_decode(file_get_contents($cacheFile), true);
    if (!$payload || !isset($payload['preview_epic']['rows'])) {
        return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Format cache tidak valid.</div>';
    }
    $rows = $payload['preview_epic']['rows'];
    $success = 0; $skip = 0; $error = 0; $errors = []; $updatedRows = [];
    $epicUpgraded = []; $epicNotEligible = [];
    $unmatchedRows = [];
    foreach ($rows as $ux) {
        if (empty($ux['target_mem_id'])) {
            $notes = array_merge($ux['cellErrors']['identitas'] ?? [], $ux['cellErrors']['link_sertifikat_epic'] ?? []);
            $unmatchedRows[] = [
                'nama' => $ux['nama'] ?? '',
                'email' => $ux['email'] ?? '',
                'whatsapp' => $ux['whatsapp'] ?? '',
                'id_epic_resmi' => $ux['id_epic_resmi'] ?? '',
                'link_sertifikat_epic' => $ux['link_sertifikat_epic'] ?? '',
                'catatan' => implode('; ', $notes),
                'status' => 'not_found'
            ];
        }
    }
    // Dapatkan user updater dari payload untuk audit trail
    $updatedBy = isset($payload['user']) ? $payload['user'] : (isset($_SESSION['sauser']) ? $_SESSION['sauser'] : 'unknown');
    foreach ($rows as $r) {
        if (($r['status'] ?? '') === 'Lewati: kolom EPIC kosong') { $skip++; continue; }
        if (($r['status'] ?? '') === 'Error' || empty($r['target_mem_id'])) {
            $error++;
            $notes = array_merge($r['cellErrors']['identitas'] ?? [], $r['cellErrors']['link_sertifikat_epic'] ?? []);
            $errors[] = 'Baris ' . ($r['rowNumber'] ?? '?') . ': ' . implode('; ', $notes);
            continue;
        }
        $memId = (int)$r['target_mem_id'];
        $member = db_row("SELECT `mem_datalain`,`mem_nama`,`mem_email`,`mem_status`,`mem_whatsapp` FROM `sa_member` WHERE `mem_id`=" . $memId);
        if (!is_array($member)) { $error++; $errors[] = 'Baris ' . ($r['rowNumber'] ?? '?') . ': member tidak ditemukan saat update'; continue; }
        $changes = [];
        $assoc = parseDatalainAssoc($member['mem_datalain'] ?? '');
        $assoc['idepicresmi'] = $r['id_epic_resmi'];
        $assoc['linksertifikatepic'] = $r['link_sertifikat_epic'];
        $newStr = buildDatalainString($assoc);
        $ok = db_query("UPDATE `sa_member` SET `mem_datalain`='" . cek($newStr) . "' WHERE `mem_id`=" . $memId);
        if ($ok === false) { $error++; $errors[] = 'Baris ' . ($r['rowNumber'] ?? '?') . ': gagal update database (' . db_error() . ')'; continue; }
        if (($r['id_epic_resmi'] ?? '') !== '') { $changes['id_epic_resmi'] = ['old' => ($assoc['idepicresmi'] ?? ''), 'new' => $r['id_epic_resmi']]; }
        if (($r['link_sertifikat_epic'] ?? '') !== '') { $changes['link_sertifikat_epic'] = ['old' => ($assoc['linksertifikatepic'] ?? ''), 'new' => $r['link_sertifikat_epic']]; }

        // Update WhatsApp jika diisi dan valid (parse memastikan valid & non-duplicate)
        if (!empty($r['whatsapp'])) {
            $waE164 = waToE164($r['whatsapp']);
            if ($waE164 !== null) {
                $waStored = formatwa($r['whatsapp']);
                // Cek ulang duplikasi untuk idempotensi
                $dup = db_var("SELECT `mem_id` FROM `sa_member` WHERE `mem_whatsapp`='" . cek($waStored) . "' AND `mem_id`<>" . $memId);
                if (!is_numeric($dup)) {
                    $okWa = db_query("UPDATE `sa_member` SET `mem_whatsapp`='" . cek($waStored) . "' WHERE `mem_id`=" . $memId);
                    if ($okWa !== false) {
                        $changes['whatsapp'] = ['old' => ($member['mem_whatsapp'] ?? ''), 'new' => $waStored];
                    } else {
                        $errors[] = 'Baris ' . ($r['rowNumber'] ?? '?') . ': gagal update WhatsApp (' . db_error() . ')';
                    }
                } else {
                    $errors[] = 'Baris ' . ($r['rowNumber'] ?? '?') . ': WhatsApp duplikat pada member lain saat update';
                }
            } else {
                $errors[] = 'Baris ' . ($r['rowNumber'] ?? '?') . ': WhatsApp tidak valid saat update (harus +62...)';
            }
        }
        $success++;
        $finalWa = isset($changes['whatsapp']['new']) ? $changes['whatsapp']['new'] : ($member['mem_whatsapp'] ?? ($r['whatsapp'] ?? ''));
        $updatedRows[] = [
            'nama' => $member['mem_nama'] ?? ($r['nama'] ?? ''),
            'email' => $member['mem_email'] ?? ($r['email'] ?? ''),
            'whatsapp' => $finalWa,
            'id_epic_resmi' => $r['id_epic_resmi'],
            'link_sertifikat_epic' => $r['link_sertifikat_epic'],
            'statusCsv' => ($r['statusRaw'] ?? ''),
            'status' => 'updated'
        ];

        // Auto-upgrade: jika saat ini Free (1) dan memenuhi syarat
        $hasEpicData = !empty($r['id_epic_resmi']) || !empty($r['link_sertifikat_epic']);
        // Upgrade eksplisit via kolom Status: jika diset Premium (syarat: Email & ID EPIC wajib terisi)
        $statusExplicitPremium = (isset($r['statusNorm']) && intval($r['statusNorm']) === 2);
        $upgradeRequiresEmailAndEpicId = (!empty($r['email']) && !empty($r['id_epic_resmi']));
        $canUpgradeByEpicData = ($hasEpicData && $upgradeRequiresEmailAndEpicId);
        $canUpgradeByStatusCsv = ($statusExplicitPremium && $upgradeRequiresEmailAndEpicId);
        if (intval($member['mem_status']) === 1 && ($canUpgradeByEpicData || $canUpgradeByStatusCsv)) {
            $okUp = db_query("UPDATE `sa_member` SET `mem_status`=2, `mem_tglupgrade`='" . date('Y-m-d H:i:s') . "' WHERE `mem_id`=" . $memId);
            if ($okUp !== false) {
                $epicUpgraded[] = [
                    'nama' => $member['mem_nama'] ?? ($r['nama'] ?? ''),
                    'email' => $member['mem_email'] ?? ($r['email'] ?? ''),
                    'reason' => $canUpgradeByEpicData ? 'EPIC data ditambahkan' : 'CSV Status=Premium',
                    'upgraded_at' => date('Y-m-d H:i:s')
                ];
                epiLogUpgrade($memId, ($member['mem_email'] ?? ($r['email'] ?? '')), ($canUpgradeByEpicData ? 'EPIC data ditambahkan' : 'CSV Status=Premium'), 'epic_update');
            } else {
                // Catat sebagai tidak eligible karena gagal update DB
                $epicNotEligible[] = [
                    'nama' => $member['mem_nama'] ?? ($r['nama'] ?? ''),
                    'email' => $member['mem_email'] ?? ($r['email'] ?? ''),
                    'reason' => 'Gagal upgrade otomatis (' . db_error() . ')',
                    'current_status' => intval($member['mem_status'])
                ];
            }
        } elseif (intval($member['mem_status']) !== 1) {
            // Sudah Premium, tidak perlu upgrade
            $epicNotEligible[] = [
                'nama' => $member['mem_nama'] ?? ($r['nama'] ?? ''),
                'email' => $member['mem_email'] ?? ($r['email'] ?? ''),
                'reason' => 'Sudah Premium',
                'current_status' => intval($member['mem_status'])
            ];
        } else {
            // Free tapi tidak memenuhi syarat upgrade -> tidak eligible
            $epicNotEligible[] = [
                'nama' => $member['mem_nama'] ?? ($r['nama'] ?? ''),
                'email' => $member['mem_email'] ?? ($r['email'] ?? ''),
                'reason' => ($statusExplicitPremium ? 'Gagal upgrade: Email & ID EPIC wajib terisi' : 'Tidak memenuhi syarat (Email & ID EPIC wajib terisi)'),
                'current_status' => intval($member['mem_status'])
            ];
        }

        // Audit trail perubahan (field changes)
        if (!empty($changes)) {
            epiLogMemberUpdate($memId, ($member['mem_email'] ?? ($r['email'] ?? '')), $changes, 'epic_update', $updatedBy);
        }
    }
    @unlink($cacheFile);
    $resultPayload = [
        'token' => $token,
        'success' => $success,
        'skip' => $skip,
        'error' => $error,
        'updated' => $updatedRows,
        'unmatched' => $unmatchedRows,
        'epic_upgraded' => $epicUpgraded,
        'epic_not_eligible' => $epicNotEligible,
        'created_at' => date('Y-m-d H:i:s')
    ];
    $resultFile = $cacheDir . DIRECTORY_SEPARATOR . 'update_result_' . $token . '.json';
    file_put_contents($resultFile, json_encode($resultPayload));
    $msg = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> <strong>Update EPIC Selesai!</strong><br/>Total: ' . count($rows) . ' | Berhasil: ' . $success . ' | Diupgrade: ' . count($epicUpgraded) . ' | Tidak Eligible: ' . count($epicNotEligible) . ' | Lewati: ' . $skip . ' | Error: ' . $error . '</div>';
    if (!empty($unmatchedRows)) { $msg .= '<div class="alert alert-warning"><i class="fas fa-download"></i> Ada baris yang identitasnya tidak ditemukan. Anda dapat <a class="btn btn-outline-warning btn-sm" href="?download_epic_unmatched=' . htmlspecialchars($token) . '">Unduh CSV Nama Tidak Ditemukan</a> untuk diperbaiki.</div>'; }
    if (!empty($updatedRows)) { $msg .= '<div class="alert alert-success"><i class="fas fa-download"></i> Anda dapat <a class="btn btn-success btn-sm" href="?download_epic_updated=' . htmlspecialchars($token) . '">Unduh CSV hasil update EPIC</a>.</div>'; }
    if (!empty($epicUpgraded)) { $msg .= '<div class="alert alert-success"><i class="fas fa-arrow-up"></i> ' . count($epicUpgraded) . ' member diupgrade otomatis. <a class="btn btn-success btn-sm" href="?download_epic_upgraded=' . htmlspecialchars($token) . '">Unduh CSV upgraded</a>.</div>'; }
    if (!empty($epicNotEligible)) { $msg .= '<div class="alert alert-secondary"><i class="fas fa-list"></i> ' . count($epicNotEligible) . ' member tidak memenuhi syarat upgrade. <a class="btn btn-outline-dark btn-sm" href="?download_epic_not_eligible=' . htmlspecialchars($token) . '">Unduh CSV tidak eligible</a>.</div>'; }
    if (!empty($errors)) { $msg .= '<div class="alert alert-warning"><strong>Detail:</strong><br/>' . htmlspecialchars(implode('<br/>', array_slice($errors, 0, 50))) . '</div>'; }
    return $msg;
}

function parseDatalainAssoc($str) {
    $assoc = [];
    if (!empty($str)) {
        $exp = explode("][", substr($str,1,-1));
        foreach ($exp as $e) {
            $line = explode("|", $e);
            if (count($line) === 2) { $assoc[$line[0]] = $line[1]; }
        }
    }
    return $assoc;
}

function buildDatalainString($assoc) {
    $out = '';
    foreach ($assoc as $k => $v) {
        $out .= '[' . txtonly(strtolower($k)) . '|' . cek($v) . ']';
    }
    return $out;
}

// Logging upgrade: simpan ke tabel epi_member_upgrade_log jika ada, fallback ke file cache/member-upgrade.log
function epiLogUpgrade($memId, $email, $reason, $source = 'import') {
    $memId = intval($memId);
    $email = cek((string)$email);
    $reason = cek((string)$reason);
    $source = txtonly(strtolower($source));
    $now = date('Y-m-d H:i:s');
    // Coba insert ke DB, tangkap exception agar tidak fatal jika tabel belum ada
    $sql = "INSERT INTO `epi_member_upgrade_log` (`mem_id`,`email`,`reason`,`source`,`performed_at`) VALUES ($memId, '" . $email . "', '" . $reason . "', '" . $source . "', '" . $now . "')";
    $ok = false; $dbErr = '';
    try {
        $res = db_insert($sql);
        if (is_numeric($res)) { $ok = true; }
    } catch (Throwable $e) {
        $dbErr = $e->getMessage();
        $ok = false;
    }
    if (!$ok) {
        // Fallback ke file
        $root = dirname(__DIR__, 2);
        $cacheDir = $root . DIRECTORY_SEPARATOR . 'cache';
        if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0777, true); }
        $line = '[' . $now . '] mem_id=' . $memId . ' email=' . preg_replace('/(.{3}).+(@.+)/', '$1***$2', $email) . ' source=' . $source . ' reason=' . $reason . (empty($dbErr) ? '' : ' (db_err: '.preg_replace('/\s+/', ' ', $dbErr).')') . "\n";
        @file_put_contents($cacheDir . DIRECTORY_SEPARATOR . 'member-upgrade.log', $line, FILE_APPEND);
    }
}

// Audit trail untuk perubahan data member (WhatsApp, EPIC fields, dll.)
function epiLogMemberUpdate($memId, $email, $changesAssoc, $source = 'epic_update', $updatedBy = 'unknown') {
    $memId = intval($memId);
    $email = cek((string)$email);
    $source = txtonly(strtolower($source));
    $updatedBy = cek((string)$updatedBy);
    $now = date('Y-m-d H:i:s');
    $changesJson = cek(json_encode($changesAssoc));
    // Coba insert ke DB, tangkap exception agar tidak fatal jika tabel belum ada
    $sql = "INSERT INTO `epi_member_update_log` (`mem_id`,`email`,`changes`,`source`,`updated_by`,`updated_at`) VALUES ($memId, '" . $email . "', '" . $changesJson . "', '" . $source . "', '" . $updatedBy . "', '" . $now . "')";
    $ok = false; $dbErr = '';
    try {
        $res = db_insert($sql);
        if (is_numeric($res)) { $ok = true; }
    } catch (Throwable $e) {
        $dbErr = $e->getMessage();
        $ok = false;
    }
    if (!$ok) {
        // Fallback ke file
        $root = dirname(__DIR__, 2);
        $cacheDir = $root . DIRECTORY_SEPARATOR . 'cache';
        if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0777, true); }
        $line = '[' . $now . '] mem_id=' . $memId . ' email=' . preg_replace('/(.{3}).+(@.+)/', '$1***$2', $email) . ' source=' . $source . ' updated_by=' . $updatedBy . ' changes=' . $changesJson . (empty($dbErr) ? '' : ' (db_err: '.preg_replace('/\s+/', ' ', $dbErr).')') . "\n";
        @file_put_contents($cacheDir . DIRECTORY_SEPARATOR . 'member-update.log', $line, FILE_APPEND);
    }
}