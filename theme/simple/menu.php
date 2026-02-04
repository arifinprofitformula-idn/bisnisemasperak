<?php
// Pastikan struktur menu tersedia agar aman dipakai di semua konteks
if (!isset($menu) || !is_array($menu)) { $menu = []; }
# Hapus dulu menu materi
if (isset($settings['url_materi'])) {
  unset($menu[$settings['url_materi']]);
}

@require_once dirname(__DIR__,2).'/plugin/epi-role-manager/index.php';
$roleCode = isset($datamember['mem_role']) ? (int)$datamember['mem_role'] : 0;
$permMap = null;
if ($roleCode > 0 && $roleCode < 9 && function_exists('epi_role_permissions_for_member')) {
  $permMap = epi_role_permissions_for_member($datamember);
}
$mandatoryKeys = function_exists('epi_mandatory_menu_keys') ? epi_mandatory_menu_keys() : array('home','profil','logout','orderanda','product');
$canViewMenu = function($menuKey, $minRole = null) use ($roleCode, $permMap, $mandatoryKeys){
  if (in_array($menuKey, $mandatoryKeys, true)) { return true; }
  if (is_array($permMap) && array_key_exists($menuKey, $permMap)) { return (bool)$permMap[$menuKey]; }
  if ($minRole !== null && $minRole !== '') { return $roleCode >= (int)$minRole; }
  return true;
};

if (isset($datamember['mem_role']) && $datamember['mem_role'] >= 5) {
  foreach ($menu as $keymenu => $menuadmin) {
    if (isset($menuadmin['label'])) {      
      if (isset($menuadmin['submenu'])) { 
        $submenu = '';
        foreach ($menuadmin['submenu'] as $key => $value) {          
          if (isset($value[2])) {
            if ($canViewMenu($key, $value[2])) {
              $submenu .= '<li><a class="dropdown-item" href="'.$weburl.'dashboard/'.$key.'">'.$value[0].'</a></li>';
            }
          } else {
            $submenu .= '<li><a class="dropdown-item" href="'.$weburl.'dashboard/'.$key.'">'.$value[0].'</a></li>';
          }
        }
        if ($submenu != '') {
          echo '
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              '.$menuadmin['label'].'
            </a>
            <ul class="dropdown-menu">'.$submenu.'</ul>
          </li>
          ';
        }
      } else {
        if ($keymenu === 'epistore') {
          $isPremium = (isset($datamember['mem_status']) && intval($datamember['mem_status']) === 2);
          if (!$isPremium) { continue; }
        }
        echo '
        <li class="nav-item">
          <a class="nav-link" href="'.$weburl.$keymenu.'">'.$menuadmin['label'].'</a>
        </li>';
      }
    } 
  }
} else {
  foreach ($menu as $keymenu => $menuadmin) {
    if (isset($menuadmin['label'])) {
      if ($keymenu == 'membermenu') {
        $menumember = (isset($menu['membermenu']['submenu']) && is_array($menu['membermenu']['submenu'])) ? $menu['membermenu']['submenu'] : [];

        if (isset($settings['klienoff']) && $settings['klienoff'] == 1) {
          unset($menumember['klien']);
        }
        if (isset($settings['networkoff']) && $settings['networkoff'] == 1) {
          unset($menumember['jaringan']);
        }        
        
        foreach ($menumember as $key => $value) {
          if (isset($value[2])) {
            if ($canViewMenu($key, $value[2])) {
              echo '
              <li class="nav-item">
                <a class="nav-link" href="'.$weburl.'dashboard/'.$key.'">'.$value[0].'</a>
              </li>
              ';
            }
          } else {
            echo '
              <li class="nav-item">
                <a class="nav-link" href="'.$weburl.'dashboard/'.$key.'">'.$value[0].'</a>
              </li>
              ';
          }
        }
      } else {
        if (!isset($menuadmin['submenu'])) {
          if ($keymenu === 'epistore') {
            $isPremium = (isset($datamember['mem_status']) && intval($datamember['mem_status']) === 2);
            if (!$isPremium) { continue; }
          }
          echo '
            <li class="nav-item">
              <a class="nav-link" href="'.$weburl.$keymenu.'">'.$menuadmin['label'].'</a>
            </li>
            ';
        }
      }
    }
  }
}

do_action('nav_menu');
