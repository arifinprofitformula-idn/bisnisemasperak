<?php 
if (!defined('IS_IN_SCRIPT')) { define('IS_IN_SCRIPT', true); }

/*
Plugin Name: EPI WhatsApp Login
Plugin Slug: epi-whatsapp-login
Description: Login via WhatsApp OTP untuk SAP (SimpleAff Plus) tanpa mengubah core.
Version: 1.0.0
Author: EPI Team
*/

// Guard agar file tidak dieksekusi dua kali dalam satu request
if (defined('EPI_WHATSAPP_LOGIN_LOADED')) {
  return;
}
define('EPI_WHATSAPP_LOGIN_LOADED', true);

// Pastikan timezone
if (function_exists('date_default_timezone_set')) { date_default_timezone_set('Asia/Jakarta'); }
// Hilangkan session_start di plugin (serahkan ke core affiliatepage.php) untuk hindari Notice duplikasi

// Muat core SAP (config + fungsi DB) bila belum tersedia
// Ini mencegah error "undefined function db_var()" saat plugin dipanggil langsung
if (!function_exists('db_var')) {
  $root = dirname(dirname(__DIR__)); // C:\xampp\htdocs\bep
  $cfg  = $root . DIRECTORY_SEPARATOR . 'config.php';
  $fn   = $root . DIRECTORY_SEPARATOR . 'fungsi.php';
  if (file_exists($cfg)) {
    require_once $cfg;
  }
  if (file_exists($fn)) {
    require_once $fn;
  }
}

// Keamanan dasar
if (!function_exists('epi_mask_phone')) {
  function epi_mask_phone($phone){
    $p = preg_replace('/\D+/', '', (string)$phone);
    if (strlen($p) <= 4) return str_repeat('*', max(strlen($p)-2, 0)) . substr($p, -2);
    return substr($p, 0, 2) . str_repeat('*', max(strlen($p)-4,0)) . substr($p, -2);
  }
}

// Hook menu: inject ke mainmenu + override login + tambah submenu Settings
if (function_exists('add_filter')) {
  add_filter('menu', function($menu){
    // Tambah item publik di mainmenu
    if (!isset($menu['mainmenu'])) { $menu['mainmenu'] = array(); }
    $menu['mainmenu']['whatsapp-login'] = array('Login via WhatsApp', 'plugin/epi-whatsapp-login/whatsapp-login.php');

    // Tambah submenu admin di Settings (gunakan slug unik agar tidak bentrok dengan item publik)
    if (isset($menu['settings']['submenu']) && is_array($menu['settings']['submenu'])) {
      $menu['settings']['submenu']['whatsapp-login-settings'] = array('WhatsApp Login (Settings)', 'plugin/epi-whatsapp-login/dashwhatslogin.php', 9);
    }

    // Override aman untuk slug 'login' di mainmenu agar menampilkan tombol tambahan tanpa ubah core
    if (isset($menu['mainmenu']['login']) && is_array($menu['mainmenu']['login'])) {
      $menu['mainmenu']['login'][1] = 'plugin/epi-whatsapp-login/wrap_login.php';
    }
    return $menu;
  });
}

// Loader file bantuan
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/gateway.php';

// Fungsi instalasi (dipanggil dari halaman Plugin Admin)
function epi_whatsapp_login_install(){
  // Tabel utama OTP
  db_query("CREATE TABLE IF NOT EXISTS `epi_login_otp` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `request_id` VARCHAR(64) NOT NULL,
    `mem_id` INT DEFAULT NULL,
    `phone` VARCHAR(32) NOT NULL,
    `otp_code_hash` VARCHAR(128) NOT NULL,
    `status` ENUM('pending','verified','expired','failed') NOT NULL DEFAULT 'pending',
    `attempts` INT NOT NULL DEFAULT 0,
    `ip_address` VARCHAR(64) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    `expires_at` DATETIME NOT NULL,
    UNIQUE KEY `uniq_request` (`request_id`),
    KEY `idx_phone` (`phone`),
    KEY `idx_status` (`status`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

  // Log OTP
  db_query("CREATE TABLE IF NOT EXISTS `epi_login_otp_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `request_id` VARCHAR(64) NOT NULL,
    `action` VARCHAR(32) NOT NULL,
    `masked_phone` VARCHAR(32) NOT NULL,
    `status` VARCHAR(32) NOT NULL,
    `info` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    KEY `idx_request` (`request_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

  return true;
}

// Fungsi uninstall (rollback tabel)
function epi_whatsapp_login_uninstall(){
  db_query("DROP TABLE IF EXISTS `epi_login_otp_log`");
  db_query("DROP TABLE IF EXISTS `epi_login_otp`");
  return true;
}

// Healthcheck sederhana
function epi_whatsapp_login_health(){
  $ok = db_var("SHOW TABLES LIKE 'epi_login_otp'") && db_var("SHOW TABLES LIKE 'epi_login_otp_log'");
  return !!$ok;
}
// Auto-install guard: jika tabel belum ada, buat sekarang (idempotent)
if (!epi_whatsapp_login_health()) {
  epi_whatsapp_login_install();
}
?>