<?php
if (!defined('IS_IN_SCRIPT')) { define('IS_IN_SCRIPT', true); }
// Wrapper non-invasif untuk halaman login: ambil output asli lalu sisipkan tombol

global $settings, $weburl;
$themeLogin = 'theme/'.($settings['theme'] ?? 'simple').'/salogin.php';
if (!file_exists($themeLogin)) { $themeLogin = 'theme/simple/salogin.php'; }

ob_start();
include($themeLogin);
$html = ob_get_clean();

// Inject CSS Font Awesome Brands jika belum ada (untuk ikon WhatsApp)
if (strpos($html, 'fontawesome/css/brands') === false) {
    $html = preg_replace('/(<\/head>)/i', '<link href="'.$weburl.'fontawesome/css/brands.min.css" rel="stylesheet" />$1', $html, 1);
}

$btn = '<div class="divider"><span>or</span></div><a href="'.$weburl.'whatsapp-login" class="btn btn-success w-100"><i class="fab fa-whatsapp"></i> Login via WhatsApp</a>';

// Sisipkan sebelum penutup form
$html = preg_replace('/(<\/form>)/i', $btn.'$1', $html, 1);

echo $html;
?>