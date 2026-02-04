<?php
// Automated Test Script for Commission Flow
// WARNING: Run this on STAGING/DEV only. It creates dummy data.

require_once 'config.php';
require_once 'fungsi.php';

if (!function_exists('db_insert_id')) {
    function db_insert_id() {
        global $con;
        return mysqli_insert_id($con);
    }
}

function assert_true($cond, $msg) {
    if ($cond) {
        echo "<div style='color:green'>[PASS] $msg</div>";
    } else {
        echo "<div style='color:red'>[FAIL] $msg</div>";
    }
}

echo "<h2>Commission Flow Test</h2>";

// 1. Setup Dummy User
$testEmail = 'test_comm_'.time().'@example.com';
db_query("INSERT INTO sa_member (mem_nama, mem_email, mem_status, mem_role) VALUES ('Test User', '$testEmail', '1', '1')");
$uid = db_insert_id();
echo "Created Test User ID: $uid<br>";

// 2. Add Commission
$commAmt = 100000;
$now = date('Y-m-d H:i:s');
$ref = 'TEST-COMM-'.time();
db_query("INSERT INTO sa_laporan (lap_idsponsor, lap_tanggal, lap_masuk, lap_keluar, lap_code, lap_keterangan, lap_reference) VALUES ($uid, '$now', $commAmt, 0, 2, 'Test Commission', '$ref')");
echo "Added Commission: $commAmt<br>";

// 3. Request Payout
$reqAmt = 50000;
db_query("INSERT INTO epi_commission_payout (receiver_id, type, amount, status, created_at) VALUES ($uid, 'sponsor', $reqAmt, 'requested', '$now')");
$payoutId = db_insert_id();
echo "Requested Payout: $reqAmt (ID: $payoutId)<br>";

/*
// 5. Simulate BUG: Duplicate Ledger Entry
echo "<h3>Simulating Bug (Double Deduction)...</h3>";
$pph = 2.5; 
$taxAmt = (int)round($reqAmt * ($pph/100.0));
$netAmt = $reqAmt - $taxAmt;
$dupRef = "PAYOUT-$payoutId-DUP";
db_query("INSERT INTO `sa_laporan` (`lap_idmember`,`lap_idsponsor`,`lap_tanggal`,`lap_masuk`,`lap_keluar`,`lap_code`,`lap_keterangan`,`lap_reference`,`payout_id`) VALUES (0,$uid,'$now',0,$netAmt,2,'Pencairan Komisi #$payoutId (DUPLICATE)','$dupRef',$payoutId)");
echo "Inserted Duplicate Ledger Entry ($netAmt).<br>";
*/

$balRow = db_row("SELECT SUM(lap_masuk)-SUM(lap_keluar) as bal FROM sa_laporan WHERE lap_idsponsor=$uid AND lap_code IN (2,3)");
$bal = (int)$balRow['bal'];
$pendRow = db_row("SELECT SUM(amount) as pend FROM epi_commission_payout WHERE receiver_id=$uid AND status IN ('requested','pending','processed')");
$pend = (int)$pendRow['pend'];

assert_true($bal == 100000, "Balance should be 100,000");
assert_true($pend == 50000, "Pending should be 50,000");
assert_true(($bal - $pend) == 50000, "Withdrawable should be 50,000");

// 5. Simulate Admin Approval (Payout Processing)
// We replicate the logic from dashbayar.php here
echo "<h3>Processing Payout...</h3>";

db_query("START TRANSACTION");
$pph = 2.5; // Example tax
$taxAmt = (int)round($reqAmt * ($pph/100.0));
$netAmt = $reqAmt - $taxAmt;

// Update Payout
db_query("UPDATE epi_commission_payout SET status='paid', paid_at='$now', gross_amount=$reqAmt, tax_percent=$pph, tax_amount=$taxAmt, net_amount=$netAmt WHERE id=$payoutId");

// Insert Ledger
db_query("INSERT INTO sa_laporan (lap_idsponsor, lap_tanggal, lap_masuk, lap_keluar, lap_code, lap_keterangan, lap_reference, payout_id) VALUES ($uid, '$now', 0, $netAmt, 2, 'Payout #$payoutId', 'PAYOUT-$payoutId', $payoutId)");
if ($taxAmt > 0) {
    db_query("INSERT INTO sa_laporan (lap_idsponsor, lap_tanggal, lap_masuk, lap_keluar, lap_code, lap_keterangan, lap_reference, payout_id) VALUES ($uid, '$now', 0, $taxAmt, 2, 'Tax #$payoutId', 'TAX-$payoutId', $payoutId)");
}
db_query("COMMIT");

// 6. Verify Post-Payout State
$balRow2 = db_row("SELECT SUM(lap_masuk)-SUM(lap_keluar) as bal FROM sa_laporan WHERE lap_idsponsor=$uid AND lap_code IN (2,3)");
$bal2 = (int)$balRow2['bal'];
$pendRow2 = db_row("SELECT SUM(amount) as pend FROM epi_commission_payout WHERE receiver_id=$uid AND status IN ('requested','pending','processed')");
$pend2 = (int)$pendRow2['pend'];

// Expected:
// Earned: 100,000
// Deducted: 50,000 (Net + Tax)
// Balance: 50,000
// Pending: 0
echo "New Balance: $bal2 (Expected 50000)<br>";
echo "New Pending: $pend2 (Expected 0)<br>";

assert_true($bal2 == 50000, "Balance should be 50,000");
assert_true($pend2 == 0, "Pending should be 0");

// 7. Reconcile Check
$paidRow = db_row("SELECT SUM(gross_amount) as paid FROM epi_commission_payout WHERE receiver_id=$uid AND status='paid'");
$paid = (int)$paidRow['paid'];
$deductedRow = db_row("SELECT SUM(lap_keluar) as ded FROM sa_laporan WHERE lap_idsponsor=$uid AND lap_code IN (2,3)");
$deducted = (int)$deductedRow['ded'];

assert_true($paid == $deducted, "Paid ($paid) should equal Deducted ($deducted)");

echo "<h3>Cleanup...</h3>";
// Clean up test data
db_query("DELETE FROM sa_laporan WHERE lap_idsponsor=$uid");
db_query("DELETE FROM epi_commission_payout WHERE receiver_id=$uid");
db_query("DELETE FROM sa_member WHERE mem_id=$uid");
echo "Done.";
