<?php
/*
Name: EPI Dashboard Tweaks
Slug: epi-dashboard-tweaks
Description: Sembunyikan menu Komisi di dashboard member dan hanya tampilkan tautan Landing Page yang sudah ada di sistem (tanpa tautan registrasi otomatis dan tanpa tombol copy). Non-invasive: via hook/filter tanpa ubah core/theme.
Version: 1.0.0
Author: EPI Team
URI: https://epi.example/epi-dashboard-tweaks
*/

if (defined('EPI_DASHBOARD_TWEAKS_LOADED')) { return; }
define('EPI_DASHBOARD_TWEAKS_LOADED', true);

if (!function_exists('add_filter')) {
    // Sistem hook belum tersedia, hentikan plugin
    return;
}

// 1) (Optional) Sembunyikan menu 'Komisi' di member dashboard — dikendalikan oleh settings['epi_dashboard_hide_commission']
add_filter('menu', function($menu){
    try {
        global $settings;
        $hide = false;
        if (isset($settings['epi_dashboard_hide_commission'])) {
            $cfg = $settings['epi_dashboard_hide_commission'];
            // settings bisa berupa string JSON atau scalar
            if (is_string($cfg)) {
                // coba decode jika JSON, fallback ke nilai langsung
                $decoded = json_decode($cfg, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $hide = !empty($decoded['data']['enabled']);
                } else {
                    $hide = ($cfg === '1' || strtolower($cfg) === 'true');
                }
            } else {
                $hide = (bool)$cfg;
            }
        }

        if ($hide && isset($menu['membermenu']['submenu']['laporankomisi'])) {
            unset($menu['membermenu']['submenu']['laporankomisi']);
        }
    } catch (\Throwable $e) {
        error_log('epi-dashboard-tweaks: gagal mengelola visibilitas menu Komisi: '.$e->getMessage());
    }
    return $menu;
}, 10);

// 2) Override modul Landing Page agar hanya menampilkan tautan LP yang ada di sistem
add_filter('mod_landingpage', function($originalHtml){
    global $weburl, $datamember;

    // Ambil hanya landing page (bukan produk): gunakan pro_harga IS NULL
    $pages = [];
    try {
        $pages = db_select("SELECT `page_judul`,`page_url` FROM `sa_page` WHERE `pro_status`=1 AND `pro_harga` IS NULL ORDER BY `page_judul`");
    } catch (\Throwable $e) {
        error_log('epi-dashboard-tweaks: query landing pages gagal: '.$e->getMessage());
    }

    $html = '';
    if (is_array($pages) && count($pages) > 0) {
        $html .= '<div class="card mb-3">'
              .  '<div class="card-header">Landing Page</div>'
              .  '<div class="card-body">'
              .  '<ol>';
        foreach ($pages as $p) {
            $title = htmlspecialchars($p['page_judul'] ?? '', ENT_QUOTES, 'UTF-8');
            $url   = $weburl . ($datamember['mem_kodeaff'] ?? '') . '/' . ($p['page_url'] ?? '');
            $html .= '<li>'
                  .  '<a href="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'" target="_blank" rel="noopener" title="Kunjungi">'.$title.'</a>'
                  .  '&nbsp;&nbsp;<a onclick="copyToClipboard(\'' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '\')" style="text-decoration:none;cursor: pointer;" title="Copy to Clipboard">'
                  .  '<i class="fa-regular fa-copy"></i></a>'
                  .  '</li>';
        }
        $html .= '</ol>'
               .  '</div>'
               .  '</div>';
    } else {
        // Empty state bila belum ada landing page di sistem
        $html .= '<div class="card mb-3">'
              .  '<div class="card-header">Landing Page</div>'
              .  '<div class="card-body">'
              .  '<p class="text-muted">Belum ada landing page yang ditambahkan.</p>'
              .  '</div>'
              .  '</div>';
    }

    return $html;
}, 10);
