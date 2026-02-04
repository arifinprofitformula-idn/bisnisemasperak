<?php
include 'config.php';
include 'fungsi.php';

echo "<h2>Fix Homepage Setting</h2>";

// Cek setting homepage saat ini
$current_homepage = db_var("SELECT set_value FROM sa_setting WHERE set_label = 'homepage'");
echo "<p><strong>Homepage saat ini:</strong> " . ($current_homepage ?? 'Not set') . "</p>";

// Update homepage ke halaman login lokal
$new_homepage = $weburl . "login";
$update_result = db_query("UPDATE sa_setting SET set_value = '$new_homepage' WHERE set_label = 'homepage'");

if ($update_result) {
    echo "<p style='color: green;'><strong>✓ Homepage berhasil diupdate ke:</strong> $new_homepage</p>";
} else {
    echo "<p style='color: red;'><strong>✗ Gagal mengupdate homepage</strong></p>";
}

// Verifikasi perubahan
$updated_homepage = db_var("SELECT set_value FROM sa_setting WHERE set_label = 'homepage'");
echo "<p><strong>Homepage setelah update:</strong> " . ($updated_homepage ?? 'Not set') . "</p>";

echo "<p><a href='$weburl'>← Kembali ke halaman utama</a></p>";
echo "<p><a href='{$weburl}login'>→ Test halaman login</a></p>";
?>