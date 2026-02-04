<?php
/*
Name: EPI EPIStore Pages
Slug: epi-epistore-pages
Description: Menambahkan halaman publik "Daftar EPI Store" dan modul admin "Manage > Daftar EPI Store" via hook/filter tanpa mengubah core. Membuat tabel sa_epistore bila belum ada.
Version: 1.0.0
Author: EPI Team
*/

if (defined('EPI_EPISTORE_PAGES_LOADED')) { return; }
define('EPI_EPISTORE_PAGES_LOADED', true);

if (!function_exists('add_filter')) {
    return;
}

add_filter('menu', function($menu){
    try {
        global $settings;
        $dashfile = 'theme/'.($settings['theme'] ?? 'simple').'/';

        // Public page: /epistore
        $menu['epistore'] = array(
            'label' => 'Daftar EPIS',
            'slug'  => 'epistore',
            'file'  => $dashfile.'saepistore.php'
        );

        // Admin submenu under Manage
        if (!isset($menu['manage'])) { $menu['manage'] = array('label'=>'Manage','slug'=>'#','submenu'=>array()); }
        if (!isset($menu['manage']['submenu']) || !is_array($menu['manage']['submenu'])) { $menu['manage']['submenu'] = array(); }
        $menu['manage']['submenu']['daftar-epi-store'] = array('Daftar EPI Store', $dashfile.'dashstore.php', 5);

        // Settings submenu: Pengaturan Peta
        if (!isset($menu['settings'])) { $menu['settings'] = array('label'=>'Settings','slug'=>'#','submenu'=>array()); }
        if (!isset($menu['settings']['submenu']) || !is_array($menu['settings']['submenu'])) { $menu['settings']['submenu'] = array(); }
        $menu['settings']['submenu']['mapsetting'] = array('Pengaturan Peta', $dashfile.'dashmap.php', 9);
    } catch (\Throwable $e) {
        error_log('epi-epistore-pages: gagal menambahkan menu: '.$e->getMessage());
    }
    return $menu;
}, 10);

// Bootstrap DB table if not exists
try {
    if (!function_exists('db_query')) { @include_once dirname(__DIR__,2) . DIRECTORY_SEPARATOR . 'fungsi.php'; }
    $sql = "CREATE TABLE IF NOT EXISTS `sa_epistore` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `nama_store` VARCHAR(120) NOT NULL,
      `manager_nama` VARCHAR(120) NOT NULL,
      `wa_nomor` VARCHAR(20) NOT NULL,
      `provinsi` VARCHAR(80) DEFAULT NULL,
      `kota` VARCHAR(80) DEFAULT NULL,
      `lat` DECIMAL(10,7) DEFAULT NULL,
      `lng` DECIMAL(10,7) DEFAULT NULL,
      `status` TINYINT(1) NOT NULL DEFAULT 1,
      `created_at` DATETIME NOT NULL,
      `updated_at` DATETIME NOT NULL,
      PRIMARY KEY (`id`),
      KEY `idx_nama_store` (`nama_store`),
      KEY `idx_region` (`provinsi`,`kota`),
      KEY `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    @db_query($sql);
    // Tambah kolom nomor_kode bila belum ada
    try {
        $col = db_row("SHOW COLUMNS FROM `sa_epistore` LIKE 'nomor_kode'");
        if (!$col) {
            @db_query("ALTER TABLE `sa_epistore` ADD `nomor_kode` VARCHAR(20) NOT NULL AFTER `wa_nomor`");
            @db_query("ALTER TABLE `sa_epistore` ADD UNIQUE KEY `idx_nomor_kode` (`nomor_kode`)");
            // Inisialisasi kode untuk baris yang belum memiliki nomor_kode
            $maxNum = intval(db_var("SELECT MAX(CAST(SUBSTRING(`nomor_kode`,5) AS UNSIGNED)) FROM `sa_epistore` WHERE `nomor_kode` REGEXP '^EPIS[0-9]{2}$'"));
            $rowsNoCode = db_select("SELECT `id` FROM `sa_epistore` WHERE (`nomor_kode` IS NULL OR `nomor_kode`='') ORDER BY `id` ASC");
            if (is_array($rowsNoCode)) {
                foreach ($rowsNoCode as $rc) {
                    $maxNum++;
                    $code = 'EPIS'.str_pad($maxNum, 2, '0', STR_PAD_LEFT);
                    @db_query("UPDATE `sa_epistore` SET `nomor_kode`='".cek($code)."' WHERE `id`=".intval($rc['id']));
                }
            }
        }
    } catch (\Throwable $e2) {
        error_log('epi-epistore-pages: alter table nomor_kode error: '.$e2->getMessage());
    }
} catch (\Throwable $e) {
    error_log('epi-epistore-pages: init table error: '.$e->getMessage());
}

?>
