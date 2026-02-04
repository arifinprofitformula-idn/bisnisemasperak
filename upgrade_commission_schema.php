<?php
// Script to upgrade database schema for commission audit fix
// Run this via browser or command line

require_once 'config.php';
require_once 'fungsi.php';

// Helper for output
function msg($str, $type='info') {
    $color = ($type=='error')?'red':(($type=='success')?'green':'black');
    echo "<div style='color:$color; margin: 5px 0;'>$str</div>\n";
    if ($type=='error') exit;
}

echo "<h2>Starting Database Upgrade...</h2>";

// 1. Convert sa_laporan to InnoDB
$table = 'sa_laporan';
$check = db_row("SHOW TABLE STATUS WHERE Name = '$table'");
if ($check && isset($check['Engine']) && strtolower($check['Engine']) !== 'innodb') {
    msg("Converting $table to InnoDB...");
    db_query("ALTER TABLE `$table` ENGINE=InnoDB");
    msg("Converted $table to InnoDB.", 'success');
} else {
    msg("$table is already InnoDB or could not check.", 'success');
}

// 2. Add payout_id column to sa_laporan
$col = db_row("SHOW COLUMNS FROM `$table` LIKE 'payout_id'");
if (!$col) {
    msg("Adding payout_id column to $table...");
    db_query("ALTER TABLE `$table` ADD `payout_id` INT UNSIGNED NULL DEFAULT NULL AFTER `lap_idorder`");
    db_query("ALTER TABLE `$table` ADD INDEX `idx_payout` (`payout_id`)");
    msg("Added payout_id column.", 'success');
} else {
    msg("Column payout_id already exists.", 'success');
}

// 3. Add reference column to sa_laporan for idempotency
$colRef = db_row("SHOW COLUMNS FROM `$table` LIKE 'lap_reference'");
if (!$colRef) {
    msg("Adding lap_reference column to $table...");
    db_query("ALTER TABLE `$table` ADD `lap_reference` VARCHAR(100) NULL DEFAULT NULL AFTER `lap_app`");
    db_query("ALTER TABLE `$table` ADD UNIQUE INDEX `idx_reference` (`lap_reference`)");
    msg("Added lap_reference column.", 'success');
} else {
    msg("Column lap_reference already exists.", 'success');
}

// 4. Ensure epi_commission_payout is InnoDB
$table2 = 'epi_commission_payout';
$check2 = db_row("SHOW TABLE STATUS WHERE Name = '$table2'");
if ($check2 && isset($check2['Engine']) && strtolower($check2['Engine']) !== 'innodb') {
    msg("Converting $table2 to InnoDB...");
    db_query("ALTER TABLE `$table2` ENGINE=InnoDB");
    msg("Converted $table2 to InnoDB.", 'success');
} else {
    msg("$table2 is already InnoDB.", 'success');
}

echo "<h3>Upgrade Completed.</h3>";
