<?php
/*
Name: EPI Data Komisi
Slug: epi-data-komisi
Description: Menambahkan submenu Manage > Data Komisi (pereferral & kontributor) via hook/filter tanpa mengubah core.
Version: 1.0.0
Author: EPI Team
*/

if (!defined('IS_IN_SCRIPT')) { die(); exit(); }

if (defined('EPI_DATA_KOMISI_LOADED')) { return; }
define('EPI_DATA_KOMISI_LOADED', true);

if (!function_exists('add_filter')) { return; }

add_filter('menu', function($menu){
    try {
        global $settings;
        $dashfile = 'theme/'.($settings['theme'] ?? 'simple').'/';

        if (!isset($menu['manage'])) {
            $menu['manage'] = array('label' => 'Manage', 'slug' => '#', 'submenu' => array());
        }
        if (!isset($menu['manage']['submenu']) || !is_array($menu['manage']['submenu'])) {
            $menu['manage']['submenu'] = array();
        }

        $item = array('Data Komisi', $dashfile.'dashdatakomisi.php', 5);
        $submenu = $menu['manage']['submenu'];
        if (isset($submenu['datakomisi'])) { unset($submenu['datakomisi']); }
        $newSubmenu = array();
        $inserted = false;
        foreach ($submenu as $key => $val) {
            if (!$inserted && $key === 'bayar') {
                $newSubmenu['datakomisi'] = $item;
                $inserted = true;
            }
            $newSubmenu[$key] = $val;
        }
        if (!$inserted) {
            $newSubmenu['datakomisi'] = $item;
        }
        $menu['manage']['submenu'] = $newSubmenu;
    } catch (Throwable $e) {
        error_log('epi-data-komisi: gagal menambahkan menu: '.$e->getMessage());
    }
    return $menu;
}, 10);

