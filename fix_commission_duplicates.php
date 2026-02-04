<?php
require_once 'config.php';
require_once 'fungsi.php';

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.");
}

echo "Fixing Commission Duplicates (Smart Claiming Method)...\n";
echo str_repeat("-", 80) . "\n";

// Phase 1: Claim valid entries
echo "Phase 1: Linking Payouts to Ledger Entries...\n";

// Get all PAID payouts
$sql = "SELECT id, receiver_id, net_amount, tax_amount, paid_at, created_at FROM epi_commission_payout WHERE status='paid' ORDER BY id ASC";
$payouts = db_select($sql);

$claimedCount = 0;

foreach ($payouts as $p) {
    $pid = (int)$p['id'];
    $uid = (int)$p['receiver_id'];
    $net = (int)$p['net_amount'];
    $tax = (int)$p['tax_amount'];
    
    // Use paid_at, fallback to created_at
    $refDate = $p['paid_at'] ?: $p['created_at'];
    if (!$refDate) $refDate = date('Y-m-d H:i:s'); // Fallback to now (unlikely)

    // 1. Claim NET AMOUNT
    if ($net > 0) {
        claim_ledger_entry($uid, $net, $pid, $refDate, 'NET');
    }

    // 2. Claim TAX AMOUNT
    if ($tax > 0) {
        claim_ledger_entry($uid, $tax, $pid, $refDate, 'TAX');
    }
}

echo "Total Entries Linked/Claimed: $claimedCount\n\n";


// Phase 2: Cleanup Orphans
echo "Phase 2: Removing Duplicate/Orphan Ledger Entries...\n";

// Find unclaimed debit entries that look like automated commission withdrawals
$sql = "SELECT * FROM sa_laporan 
        WHERE lap_code IN (2,3) 
        AND lap_keluar > 0 
        AND payout_id IS NULL 
        AND (lap_keterangan LIKE '%Pencairan Komisi%' OR lap_keterangan LIKE '%Potongan PPh21%')";

$orphans = db_select($sql);
$deletedCount = 0;

if (count($orphans) > 0) {
    foreach ($orphans as $o) {
        echo "Deleting Orphan: ID " . $o['lap_id'] . " | User " . $o['lap_idsponsor'] . " | Amount " . number_format($o['lap_keluar']) . " | Date " . $o['lap_tanggal'] . "\n";
        
        // Safety check: Don't delete if it doesn't look like a system generated entry?
        // The query filters by specific string description used by system.
        
        db_query("DELETE FROM sa_laporan WHERE lap_id=" . $o['lap_id']);
        $deletedCount++;
    }
} else {
    echo "No orphan entries found.\n";
}

echo str_repeat("-", 80) . "\n";
echo "Total Duplicates Deleted: $deletedCount\n";


// --- Helper Function ---

function claim_ledger_entry($uid, $amount, $pid, $refDate, $type) {
    global $claimedCount;
    
    // Find candidate: 
    // - Same User
    // - Same Amount (Debit)
    // - Code 2 or 3
    // - Payout ID is NULL (Unclaimed)
    // - Description matches system default
    $desc = ($type == 'NET') ? 'Pencairan Komisi' : 'Potongan PPh21';
    
    $candidates = db_select("SELECT * FROM sa_laporan 
        WHERE lap_idsponsor=$uid 
        AND lap_keluar=$amount 
        AND lap_code IN (2,3) 
        AND payout_id IS NULL 
        AND lap_keterangan LIKE '%$desc%'");

    if (empty($candidates)) return;

    // Find closest by date
    $bestMatch = null;
    $minDiff = 999999999;

    foreach ($candidates as $c) {
        $diff = abs(strtotime($c['lap_tanggal']) - strtotime($refDate));
        if ($diff < $minDiff) {
            $minDiff = $diff;
            $bestMatch = $c;
        }
    }

    // If matches found, claim the best one
    if ($bestMatch) {
        $lid = $bestMatch['lap_id'];
        $ref = "PAYOUT-$pid";
        if ($type == 'TAX') $ref = "TAX-$pid"; // Or link to same? usually reference tracks payout ID.
        
        // Update
        db_query("UPDATE sa_laporan SET payout_id=$pid, lap_reference='$ref' WHERE lap_id=$lid");
        // echo "  Linked Payout #$pid ($type) to Ledger #$lid (Diff: {$minDiff}s)\n";
        $claimedCount++;
    }
}
