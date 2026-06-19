<?php
require 'fungsi.php';
if (!defined('IS_IN_SCRIPT')) { define('IS_IN_SCRIPT', true); }

echo "--- AUDIT INVALID REFUNDS ---\n";

// Find all "Refund" / "Pembatalan" income transactions
// Looking for code 2 (Sponsor) or 3 (Contributor) where lap_masuk > 0 and keterangan contains "Pembatalan"
$sql = "SELECT * FROM `sa_laporan` 
        WHERE (`lap_code`=2 OR `lap_code`=3) 
        AND `lap_masuk` > 0 
        AND (`lap_keterangan` LIKE '%Pembatalan Pencairan%' OR `lap_keterangan` LIKE '%Pembatalan Potongan%')";

$rows = db_select($sql);
$count = 0;
$affected_users = [];

foreach ($rows as $r) {
    $lid = $r['lap_id'];
    $uid = $r['lap_idsponsor'];
    $pid = $r['payout_id']; // Might be 0 or empty
    $amount = (int)$r['lap_masuk'];
    $date = $r['lap_tanggal'];
    
    // Check if there is a corresponding EXPENSE (Pencairan)
    // If payout_id is set, look for expense with same payout_id
    $foundExpense = false;
    
    if (!empty($pid) && $pid > 0) {
        $check = db_row("SELECT * FROM `sa_laporan` WHERE `payout_id`=$pid AND `lap_keluar` > 0");
        if ($check) $foundExpense = true;
    } else {
        // Fallback: Look for any expense with "Pencairan" for this user BEFORE this refund
        // This is a loose check, but if there are NO pencairan at all, it's definitely invalid.
        $check = db_row("SELECT * FROM `sa_laporan` WHERE `lap_idsponsor`=$uid AND `lap_keluar` > 0 AND `lap_keterangan` LIKE '%Pencairan%' AND `lap_tanggal` <= '$date'");
        if ($check) {
            // Found SOME pencairan. Need manual verification? 
            // For ID 229, we know there was NO pencairan.
            $foundExpense = true; 
        } else {
            // Double check: maybe "Potongan PPh21 Ditahan" (which is income for admin, expense for user?? No, PPh21 expense for user is code 2/3)
            // User expense is "Pencairan Komisi" (Net) + "Potongan PPh21" (Tax)
        }
    }
    
    if (!$foundExpense) {
        $count++;
        if (!isset($affected_users[$uid])) {
            $affected_users[$uid] = ['total_invalid' => 0, 'trans_ids' => []];
        }
        $affected_users[$uid]['total_invalid'] += $amount;
        $affected_users[$uid]['trans_ids'][] = $lid;
        
        echo "INVALID REFUND DETECTED:\n";
        echo "  ID: $lid | User: $uid | Date: $date | Amount: " . number_format($amount) . " | Ket: {$r['lap_keterangan']} | PayoutID: $pid\n";
    }
}

echo "\n--- SUMMARY ---\n";
echo "Total Invalid Transactions: $count\n";
echo "Affected Users: " . count($affected_users) . "\n";
foreach ($affected_users as $uid => $info) {
    echo "  User ID $uid: Total Invalid Income = " . number_format($info['total_invalid']) . " (Trans IDs: " . implode(',', $info['trans_ids']) . ")\n";
}
echo "--- END AUDIT ---\n";
