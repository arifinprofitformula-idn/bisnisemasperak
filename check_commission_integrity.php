<?php
require_once 'config.php';
require_once 'fungsi.php';

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.");
}

echo "Checking Commission Integrity...\n";
echo str_repeat("-", 80) . "\n";
echo sprintf("%-6s | %-12s | %-12s | %-12s | %-12s | %-12s | %-12s\n", "ID", "Earned", "LedgerOut", "PaidTable", "Pending", "Diff(L-P)", "Status");
echo str_repeat("-", 80) . "\n";

// Get all users who have activity
$sql = "SELECT DISTINCT lap_idsponsor FROM sa_laporan WHERE lap_code IN (2,3)
        UNION
        SELECT DISTINCT receiver_id FROM epi_commission_payout";
$users = db_select($sql);

$countErrors = 0;

foreach ($users as $u) {
    $uid = $u['lap_idsponsor'];
    if (!$uid) continue;

    // 1. Get Ledger Info (Sponsor + Contrib combined for simplicity, or separate?)
    // User report implies total balance. Let's check Sponsor (Code 2) specifically as per example implies usually sponsor.
    // Actually, let's do both summed up to be safe, or just Code 2.
    // Let's analyze Code 2 (Sponsor) first.
    
    $ledger = db_row("SELECT 
        COALESCE(SUM(CASE WHEN lap_masuk > 0 THEN lap_masuk ELSE 0 END),0) as earned,
        COALESCE(SUM(CASE WHEN lap_keluar > 0 THEN lap_keluar ELSE 0 END),0) as deducted
        FROM sa_laporan WHERE lap_idsponsor=$uid AND lap_code=2");
    
    $payout = db_row("SELECT 
        COALESCE(SUM(CASE WHEN status='paid' THEN amount ELSE 0 END),0) as paid,
        COALESCE(SUM(CASE WHEN status IN ('requested','pending','processed') THEN amount ELSE 0 END),0) as pending
        FROM epi_commission_payout WHERE receiver_id=$uid AND type='sponsor'");

    $earned = (int)$ledger['earned'];
    $deducted = (int)$ledger['deducted'];
    $paid = (int)$payout['paid'];
    $pending = (int)$payout['pending'];

    $diff = $deducted - $paid;

    if ($diff != 0) {
        $status = ($diff > 0) ? "DOUBLE DEDUCT" : "MISSING LEDGER";
        if ($deducted == 2 * $paid && $paid > 0) {
            $status = "EXACT DOUBLE";
        }
        $countErrors++;
        echo sprintf("%-6d | %-12s | %-12s | %-12s | %-12s | %-12s | %s\n", 
            $uid, 
            number_format($earned), 
            number_format($deducted), 
            number_format($paid), 
            number_format($pending), 
            number_format($diff),
            $status
        );
    }
}

echo str_repeat("-", 80) . "\n";
echo "Total Errors Found: $countErrors\n";
