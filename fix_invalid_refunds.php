<?php
require 'config.php';
require 'fungsi.php';
if (!defined('IS_IN_SCRIPT')) { define('IS_IN_SCRIPT', true); }

echo "--- FIX INVALID REFUNDS ---\n";
echo "Mulai perbaikan data transaksi...\n";

// Find all "Refund" / "Pembatalan" income transactions
// Looking for code 2 (Sponsor) or 3 (Contributor) where lap_masuk > 0 and keterangan contains "Pembatalan"
$sql = "SELECT * FROM `sa_laporan` 
        WHERE (`lap_code`=2 OR `lap_code`=3) 
        AND `lap_masuk` > 0 
        AND (`lap_keterangan` LIKE '%Pembatalan Pencairan%' OR `lap_keterangan` LIKE '%Pembatalan Potongan%')";

$rows = db_select($sql);
$count = 0;
$fixed_ids = [];

foreach ($rows as $r) {
    $lid = $r['lap_id'];
    $uid = $r['lap_idsponsor'];
    $pid = $r['payout_id']; 
    $amount = (int)$r['lap_masuk'];
    $date = $r['lap_tanggal'];
    
    // VERIFY AGAIN (Safety Check)
    $foundExpense = false;
    if (!empty($pid) && $pid > 0) {
        $check = db_row("SELECT * FROM `sa_laporan` WHERE `payout_id`=$pid AND `lap_keluar` > 0");
        if ($check) $foundExpense = true;
    } else {
        // Fallback: Look for any expense with "Pencairan" for this user BEFORE this refund
        $check = db_row("SELECT * FROM `sa_laporan` WHERE `lap_idsponsor`=$uid AND `lap_keluar` > 0 AND `lap_keterangan` LIKE '%Pencairan%' AND `lap_tanggal` <= '$date'");
        if ($check) {
            $foundExpense = true; 
        }
    }
    
    if (!$foundExpense) {
        echo "Fixing ID: $lid | User: $uid | Amount: $amount\n";
        
        // EXECUTE FIX
        // Set amount to 0, code to 99 (Void), append log
        $newKet = $r['lap_keterangan'] . " [VOID: INVALID REFUND]";
        // Escape for SQL
        $newKetSafe = cek($newKet);
        
        $upd = db_query("UPDATE `sa_laporan` SET `lap_masuk`=0, `lap_keluar`=0, `lap_code`='X', `lap_keterangan`='$newKetSafe' WHERE `lap_id`=$lid");
        
        if ($upd) {
            $count++;
            $fixed_ids[] = $lid;
            echo "  -> SUCCESS. Voided.\n";
        } else {
            echo "  -> FAILED to update.\n";
        }
    }
}

echo "\n--- SUMMARY ---\n";
echo "Total Transactions Fixed: $count\n";
echo "Fixed IDs: " . implode(', ', $fixed_ids) . "\n";
echo "--- END FIX ---\n";
