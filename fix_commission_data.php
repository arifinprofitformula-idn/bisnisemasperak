<?php
// fix_commission_data.php
// Script Perbaikan Data Komisi (Web Version - Debug Mode)
// Upload file ini ke root folder public_html Anda.
// Jalankan via browser: https://bisnisemasperak.com/fix_commission_data.php?key=Rahasia@123

// 1. Paksa Error Reporting Tampil
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. Matikan Output Buffering agar log muncul real-time
if (function_exists('apache_setenv')) { @apache_setenv('no-gzip', 1); }
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
ob_implicit_flush(1);

// Load Config & Fungsi
// Gunakan include_once agar kita bisa cek apakah file ada
if (!file_exists('config.php')) die("Error: config.php tidak ditemukan.");
require_once 'config.php';

if (!file_exists('fungsi.php')) die("Error: fungsi.php tidak ditemukan.");
require_once 'fungsi.php';

// Kunci Pengaman
$SECRET_KEY = 'Rahasia@123';
if (!isset($_GET['key']) || $_GET['key'] !== $SECRET_KEY) {
    header('HTTP/1.0 403 Forbidden');
    die("<h1>403 Forbidden</h1><p>Akses ditolak. Kunci keamanan salah.</p>");
}

// Set time limit
@set_time_limit(300);

echo "<html><head><title>Fix Commission Data (Debug)</title>";
echo "<style>body{font-family:monospace; background:#f4f4f4; padding:20px;} .log{background:#fff; padding:15px; border:1px solid #ddd; overflow-x:auto; line-height:1.5;} .err{color:red; font-weight:bold;} .ok{color:green;}</style>";
echo "</head><body>";
echo "<h2>Arva EPI OSS - Commission Data Fixer (Debug Mode)</h2>";
echo "<div class='log'><pre>";

echo "Memulai Proses Perbaikan Data...\n";
echo "Waktu Server: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("-", 60) . "\n\n";

// --- DIAGNOSA DATABASE ---
echo "<strong>DIAGNOSA KONEKSI DATABASE:</strong>\n";
// Coba tes query sederhana
$testQ = db_select("SELECT 1 as cek");
if ($testQ) {
    echo "<span class='ok'>[OK] Koneksi Database Berhasil.</span>\n\n";
} else {
    echo "<span class='err'>[ERROR] Gagal koneksi database atau fungsi db_select bermasalah.</span>\n";
    echo "Cek error log PHP server Anda.\n";
    die();
}
flush();

// --- FASE 0: PREPARE DB (Tambah Kolom Jika Belum Ada) ---
echo "<strong>FASE 0: Memeriksa Struktur Database</strong>\n";
flush();

// Cek kolom payout_id
$checkCol = db_row("SHOW COLUMNS FROM sa_laporan LIKE 'payout_id'");
if (!$checkCol) {
    echo "-> Kolom `payout_id` belum ada. Menambahkan...\n";
    $add = db_query("ALTER TABLE sa_laporan ADD COLUMN payout_id BIGINT UNSIGNED NULL DEFAULT NULL AFTER lap_code, ADD INDEX (payout_id)");
    if ($add) echo "   [OK] Kolom `payout_id` berhasil ditambahkan.\n";
    else echo "   [ERROR] Gagal menambah kolom `payout_id`.\n";
} else {
    echo "-> Kolom `payout_id` sudah ada.\n";
}

// Cek kolom lap_reference (opsional, tapi dibutuhkan script)
$checkRef = db_row("SHOW COLUMNS FROM sa_laporan LIKE 'lap_reference'");
if (!$checkRef) {
    echo "-> Kolom `lap_reference` belum ada. Menambahkan...\n";
    $addRef = db_query("ALTER TABLE sa_laporan ADD COLUMN lap_reference VARCHAR(100) NULL DEFAULT NULL AFTER lap_keterangan");
    if ($addRef) echo "   [OK] Kolom `lap_reference` berhasil ditambahkan.\n";
    else echo "   [ERROR] Gagal menambah kolom `lap_reference`.\n";
} else {
    echo "-> Kolom `lap_reference` sudah ada.\n";
}
echo "\n";
flush();

// --- FASE 1: LINKING (Smart Claiming) ---
echo "<strong>FASE 1: Menghubungkan Data Payout ke Ledger (Linking)</strong>\n";
flush();

// Cek jumlah data dulu
$countPayouts = (int)db_var("SELECT COUNT(*) FROM epi_commission_payout WHERE status='paid'");
echo "-> Ditemukan $countPayouts data payout dengan status 'paid'.\n";
flush();

$sql = "SELECT id, receiver_id, net_amount, tax_amount, paid_at, created_at FROM epi_commission_payout WHERE status='paid' ORDER BY id ASC";
$payouts = db_select($sql);

if ($payouts === false) {
    echo "<span class='err'>[ERROR] Gagal mengambil data payout. Cek struktur tabel `epi_commission_payout`.</span>\n";
    die();
}

$claimedCount = 0;
$processedCount = 0;

foreach ($payouts as $p) {
    $processedCount++;
    $pid = (int)$p['id'];
    $uid = (int)$p['receiver_id'];
    $net = (int)$p['net_amount'];
    $tax = (int)$p['tax_amount'];
    
    // Progress indicator setiap 10 data
    if ($processedCount % 10 == 0) {
        echo ".";
        flush();
    }
    
    // Gunakan paid_at, fallback ke created_at
    $refDate = $p['paid_at'] ?: $p['created_at'];
    if (!$refDate) $refDate = date('Y-m-d H:i:s');

    // 1. Claim NET AMOUNT
    if ($net > 0) {
        if (web_claim_ledger($uid, $net, $pid, $refDate, 'NET')) $claimedCount++;
    }

    // 2. Claim TAX AMOUNT
    if ($tax > 0) {
        if (web_claim_ledger($uid, $tax, $pid, $refDate, 'TAX')) $claimedCount++;
    }
}

echo "\n-> Total Entri Berhasil Di-link: $claimedCount\n\n";
flush();

// --- FASE 2: CLEANUP (Hapus Duplikat) ---
echo "<strong>FASE 2: Membersihkan Duplikat / Orphan Data</strong>\n";
flush();

// Cari entri debit (kode 2/3) yang tidak punya payout_id TAPI punya deskripsi sistem pencairan
$sqlOrphans = "SELECT * FROM sa_laporan 
               WHERE lap_code IN (2,3) 
               AND lap_keluar > 0 
               AND payout_id IS NULL 
               AND (lap_keterangan LIKE '%Pencairan Komisi%' OR lap_keterangan LIKE '%Potongan PPh21%')";

$orphans = db_select($sqlOrphans);

if ($orphans === false) {
     echo "<span class='err'>[ERROR] Gagal mengambil data orphans.</span>\n";
} else {
    $deletedCount = 0;
    if (count($orphans) > 0) {
        foreach ($orphans as $o) {
            // Eksekusi Hapus
            $del = db_query("DELETE FROM sa_laporan WHERE lap_id=" . $o['lap_id']);
            if ($del) {
                $deletedCount++;
                echo "   [DELETE] ID: " . $o['lap_id'] . " | User: " . $o['lap_idsponsor'] . " | Rp " . number_format($o['lap_keluar']) . "\n";
            } else {
                 echo "   <span class='err'>[FAIL DELETE] ID: " . $o['lap_id'] . "</span>\n";
            }
            flush();
        }
    } else {
        echo "-> Tidak ditemukan data orphan/duplikat.\n";
    }
}

echo "\n" . str_repeat("-", 60) . "\n";
echo "<strong>HASIL AKHIR:</strong>\n";
echo "1. Linking Payout : $claimedCount entri.\n";
echo "2. Hapus Duplikat : " . ($deletedCount ?? 0) . " entri.\n";
echo str_repeat("-", 60) . "\n";
echo "\nPERBAIKAN SELESAI.\n";
echo "<strong>PENTING: Segera hapus file ini dari server setelah selesai!</strong>";

echo "</pre></div></body></html>";

// --- FUNGSI BANTUAN ---
function web_claim_ledger($uid, $amount, $pid, $refDate, $type) {
    // Cari kandidat entri ledger yang cocok
    $desc = ($type == 'NET') ? 'Pencairan Komisi' : 'Potongan PPh21';
    
    $candidates = db_select("SELECT * FROM sa_laporan 
        WHERE lap_idsponsor=$uid 
        AND lap_keluar=$amount 
        AND lap_code IN (2,3) 
        AND payout_id IS NULL 
        AND lap_keterangan LIKE '%$desc%'");

    if (empty($candidates)) return false;

    // Cari yang tanggalnya paling dekat
    $bestMatch = null;
    $minDiff = 999999999;

    foreach ($candidates as $c) {
        $diff = abs(strtotime($c['lap_tanggal']) - strtotime($refDate));
        if ($diff < $minDiff) {
            $minDiff = $diff;
            $bestMatch = $c;
        }
    }

    if ($bestMatch) {
        $lid = $bestMatch['lap_id'];
        $ref = "PAYOUT-$pid";
        if ($type == 'TAX') $ref = "TAX-$pid";
        
        // Update link
        $upd = db_query("UPDATE sa_laporan SET payout_id=$pid, lap_reference='$ref' WHERE lap_id=$lid");
        return $upd;
    }
    return false;
}
?>
