<?php
// API: Export Buyers CSV
// Path: api/export-buyers.php
// Description: Stream CSV of buyers for selected product or all products.
// Columns: Nama pembeli, Email pembeli, Tanggal pembelian, Jumlah produk, Total harga, Metode pembayaran
// Security: Role check (admin/staff mem_role >= 9), prepared statements, server-side processing, masked logging.

// Ensure app context (do NOT redefine IS_IN_SCRIPT to avoid duplicate constant warnings)
$__root = dirname(__DIR__, 1);
@include_once $__root . DIRECTORY_SEPARATOR . 'config.php';
@include_once $__root . DIRECTORY_SEPARATOR . 'fungsi.php';

// Ensure session for auth context
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

// Resolve current user
// Prefer cookie-based login (is_login) used by dashboard, then fallback to session, then $datamember
$currentUser = null;
try {
    if (function_exists('is_login')) {
        $uid = is_login();
        if ($uid && function_exists('getdatamember')) {
            $userRow = getdatamember((int)$uid);
            if (is_array($userRow) && isset($userRow['mem_id'])) { $currentUser = $userRow; }
        }
    }
} catch (Throwable $e) { /* ignore, fallback below */ }
if (!$currentUser && isset($_SESSION['member']) && is_array($_SESSION['member'])) {
    $currentUser = $_SESSION['member'];
}
if (!$currentUser && isset($datamember) && is_array($datamember)) {
    $currentUser = $datamember;
}
// Allow admin/staff (role >=5) to export
if (!$currentUser || (int)($currentUser['mem_role'] ?? 0) < 5) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => 'Forbidden']);
    exit;
}

// Input params
$produkId = isset($_GET['produk']) && is_numeric($_GET['produk']) ? (int)$_GET['produk'] : 0; // 0 = all
$periode = isset($_GET['periode']) ? trim((string)$_GET['periode']) : 'all';

// Map periode to start date (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');
$startDate = '';
switch ($periode) {
    case '7d':
        $startDate = date('Y-m-d 00:00:00', strtotime('-7 days'));
        break;
    case '30d':
        $startDate = date('Y-m-d 00:00:00', strtotime('-30 days'));
        break;
    case 'month':
        $startDate = date('Y-m-01 00:00:00');
        break;
    case 'year':
        $startDate = date('Y-01-01 00:00:00');
        break;
    default:
        $startDate = '';
        break;
}

// Build SQL with prepared statements
$where = 'WHERE o.`order_status` = 1';
$types = '';
$params = [];
if ($produkId > 0) {
    $where .= ' AND o.`order_idproduk` = ?';
    $types .= 'i';
    $params[] = $produkId;
}
if ($startDate !== '') {
    $where .= ' AND o.`order_tglorder` >= ?';
    $types .= 's';
    $params[] = $startDate;
}

$sql = "SELECT 
            m.`mem_nama`, 
            m.`mem_email`, 
            m.`mem_whatsapp`, 
            o.`order_tglorder`, 
            COALESCE(o.`order_harga`, p.`pro_harga`) AS `harga_produk`, 
            o.`order_hargaunik` AS `harga_bayar`, 
            o.`order_discount`
         FROM `sa_order` o
         LEFT JOIN `sa_member` m ON m.`mem_id` = o.`order_idmember`
         LEFT JOIN `sa_page` p ON p.`page_id` = o.`order_idproduk`
         $where
         ORDER BY o.`order_tglorder` DESC";

// Prepare output headers (CSV + filename with timestamp)
// Clean any buffered output (e.g., warnings) before sending headers
if (function_exists('ob_get_level') && ob_get_level() > 0) { @ob_clean(); }
// Hide PHP warnings/notices from leaking into CSV output
@ini_set('display_errors', '0');

$filenameDate = date('Ymd');
$filename = 'data_pembeli_' . $filenameDate . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, max-age=0');

// Open output stream
$out = fopen('php://output', 'w');
// CSV Header (7 kolom sesuai urutan permintaan)
fputcsv($out, [
    'Tanggal Pembelian (Format: DD/MM/YYYY)',
    'Nama Lengkap',
    'Email',
    'Nomor Whatsapp',
    'Harga Produk',
    'Harga yang Dibayar',
    'Diskon'
]);

// Execute query and stream rows
try {
    if (!isset($con) || !$con) { throw new Exception('DB connection missing'); }
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) { throw new Exception('Prepare failed: ' . mysqli_error($con)); }
    if ($types !== '' && count($params) > 0) {
        // Bind dynamic params
        $bind = [$stmt, $types];
        foreach ($params as $p) { $bind[] = $p; }
        // Use ... to unpack, but in PHP < 5.6 not supported; fallback via call_user_func_array
        call_user_func_array('mysqli_stmt_bind_param', refValues($bind));
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $nama = (string)($row['mem_nama'] ?? '');
            $email = (string)($row['mem_email'] ?? '');
            $wa = (string)($row['mem_whatsapp'] ?? '');
            $tgl = (string)($row['order_tglorder'] ?? '');
            $hargaProduk = isset($row['harga_produk']) ? (float)$row['harga_produk'] : 0.0;
            $hargaBayar  = isset($row['harga_bayar']) ? (float)$row['harga_bayar'] : 0.0;
            $diskon = isset($row['order_discount']) ? (float)$row['order_discount'] : 0.0;
            // Format tanggal ke WIB (DD/MM/YYYY)
            $tglFmt = '';
            if ($tgl !== '') {
                $ts = strtotime($tgl);
                $tglFmt = $ts ? date('d/m/Y', $ts) : $tgl;
            }
            // Format currency to Indonesian Rupiah (Rp X.XXX)
            $hargaProdukFmt = 'Rp ' . number_format($hargaProduk, 0, ',', '.');
            $hargaBayarFmt  = 'Rp ' . number_format($hargaBayar, 0, ',', '.');
            $diskonFmt = 'Rp ' . number_format($diskon, 0, ',', '.');
            // Write row in required order
            fputcsv($out, [$tglFmt, $nama, $email, $wa, $hargaProdukFmt, $hargaBayarFmt, $diskonFmt]);
        }
    }
    mysqli_stmt_close($stmt);
} catch (Throwable $e) {
    // Minimal logging with masked parameters
    $logDir = $__root . DIRECTORY_SEPARATOR . 'cache';
    if (!is_dir($logDir)) { @mkdir($logDir, 0777, true); }
    $msg = '[' . date('Y-m-d H:i:s') . '] export-buyers error: ' . preg_replace('/\s+/', ' ', $e->getMessage()) . '\n';
    @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'export-buyers-error.log', $msg, FILE_APPEND);
}

// Flush and close stream
@fflush($out);
@fclose($out);
exit;

// Helper to support call_user_func_array on references for mysqli_stmt_bind_param
function refValues($arr){
    $refs = [];
    foreach($arr as $key => $value){
        $refs[$key] = &$arr[$key];
    }
    return $refs;
}
?>
