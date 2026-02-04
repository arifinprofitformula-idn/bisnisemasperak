<?php
// Script to reconcile commission data
// Run via CLI or Browser

require_once '../config.php';
require_once '../fungsi.php';

echo "<h2>Commission Reconciliation Report</h2>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr>
    <th>User ID</th>
    <th>Name</th>
    <th>Total Earned (Ledger Masuk)</th>
    <th>Total Deducted (Ledger Keluar)</th>
    <th>Total Payouts (Table Paid Gross)</th>
    <th>Diff (Ledger vs Payouts)</th>
    <th>Current Ledger Balance</th>
    <th>Pending Requests</th>
    <th>Real Withdrawable</th>
    <th>Status</th>
</tr>";

// Get all users who have commission transactions
$users = db_query("SELECT DISTINCT lap_idsponsor FROM sa_laporan WHERE lap_code IN (2,3)");
$count = 0;
$issues = 0;

if (is_array($users)) {
    foreach ($users as $u) {
        $uid = (int)$u['lap_idsponsor'];
        if ($uid == 0) continue;
        
        $member = db_row("SELECT mem_nama FROM sa_member WHERE mem_id=".$uid);
        $name = isset($member['mem_nama']) ? $member['mem_nama'] : 'Unknown';
        
        // 1. Ledger Aggregates
        // lap_code 2 (Sponsor), 3 (Contrib)
        // lap_masuk is earnings.
        // lap_keluar is payouts (debits).
        $ledger = db_row("SELECT SUM(lap_masuk) as earned, SUM(lap_keluar) as deducted FROM sa_laporan WHERE lap_idsponsor=".$uid." AND lap_code IN (2,3)");
        $earned = (int)$ledger['earned'];
        $deducted = (int)$ledger['deducted'];
        $balance = $earned - $deducted;
        
        // 2. Payout Table Aggregates
        // status='paid'
        $payout = db_row("SELECT SUM(gross_amount) as paid_gross FROM epi_commission_payout WHERE receiver_id=".$uid." AND status='paid'");
        $paidGross = (int)$payout['paid_gross'];
        
        // 3. Pending
        $pending = db_row("SELECT SUM(amount) as pending_amount FROM epi_commission_payout WHERE receiver_id=".$uid." AND status IN ('requested','pending','processed')");
        $pendingAmt = (int)$pending['pending_amount'];
        
        // 4. Comparison
        // Ledger Deducted should equal Payout Paid Gross
        $diff = $deducted - $paidGross;
        
        // Real Withdrawable
        $withdrawable = $balance - $pendingAmt;
        
        $status = 'OK';
        $color = 'green';
        
        if ($diff != 0) {
            $status = 'MISMATCH';
            $color = 'red';
            $issues++;
        } elseif ($withdrawable < 0) {
            $status = 'NEGATIVE_AVAIL';
            $color = 'orange';
            $issues++;
        }
        
        if ($status != 'OK') {
            echo "<tr style='color:$color; font-weight:bold;'>";
        } else {
            // Only show issues or first 10 OKs
            if ($count > 10 && $issues == 0) continue; 
            echo "<tr>";
        }
        
        echo "<td>$uid</td>
            <td>$name</td>
            <td>".number_format($earned)."</td>
            <td>".number_format($deducted)."</td>
            <td>".number_format($paidGross)."</td>
            <td>".number_format($diff)."</td>
            <td>".number_format($balance)."</td>
            <td>".number_format($pendingAmt)."</td>
            <td>".number_format($withdrawable)."</td>
            <td>$status</td>
        </tr>";
        
        $count++;
    }
}

echo "</table>";
echo "<p>Total Users Checked: $count. Issues Found: $issues.</p>";
if ($issues == 0) {
    echo "<h3 style='color:green'>ALL CLEAN. No discrepancies found.</h3>";
} else {
    echo "<h3 style='color:red'>WARNING: Discrepancies found! Please investigate users with MISMATCH status.</h3>";
    echo "<p>Fix strategy: Check 'sa_laporan' entries for duplicates or missing entries corresponding to 'epi_commission_payout'.</p>";
}
