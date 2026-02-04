<?php
/*
Name: EPI PWA Auth Sync
Slug: epi-pwa-auth
Description: Sinkronkan status autentikasi (login/logout) dengan Service Worker tanpa mengubah core/theme.
Version: 1.0.0
Author: EPI Team
URI: https://epi.example/epi-pwa-auth
*/

// Tujuan: Sinkronkan status autentikasi (login/logout) dengan Service Worker tanpa mengubah core/theme.

// Guard agar file tidak dieksekusi dua kali dalam satu request
if (defined('EPI_PWA_AUTH_LOADED')) { return; }
define('EPI_PWA_AUTH_LOADED', true);

if (!function_exists('add_action')) {
    // Lingkungan belum siap, keluar aman.
    return;
}

function epiPwaAuthInject() {
    // Gunakan hook nav_menu (tersedia di theme/simple/menu.php) agar script dieksekusi di header semua halaman.
    // Keamanan: tidak menampilkan data sensitif; hanya flag status login/logout.
    // Observabilitas: requestId ringan untuk korelasi lokal (tidak dikirim ke server).
    
    global $weburl, $slug;

    $isLoggedIn = is_login() ? 1 : 0;
    $isLogout   = (isset($slug[1]) && $slug[1] === 'logout') ? 1 : 0;
    $requestId  = substr(md5(session_id() . uniqid('', true)), 0, 8);

    // Cetak script inline minimal, non-intrusif
    $requestIdEsc = htmlspecialchars($requestId, ENT_QUOTES);
    echo <<<HTML
<script>
(function(){
  var isLoggedIn = {$isLoggedIn}, isLogout = {$isLogout}, reqId = '{$requestIdEsc}';

  // Registrasi Service Worker secara global (silent failure)
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function(){
      navigator.serviceWorker.register('/sw.js').catch(function(){ /* no-op */ });
    });
  }

  function postToSW(type, payload){
    try {
      if (navigator.serviceWorker && navigator.serviceWorker.controller) {
        navigator.serviceWorker.controller.postMessage({ type: type, payload: payload || {}, requestId: reqId });
      }
    } catch(e) { /* no-op */ }
  }

  document.addEventListener('DOMContentLoaded', function(){
    try {
      // Kirim sinyal logout saat halaman /logout dibuka
      if (isLogout) {
        postToSW('LOGOUT');
      } else if (isLoggedIn) {
        // Kirim AUTH_CHANGED sekali per load untuk purge cache user lama
        if (!sessionStorage.getItem('epi_auth_changed_sent')) {
          postToSW('AUTH_CHANGED', { loggedIn: true });
          sessionStorage.setItem('epi_auth_changed_sent', '1');
        }
      }

      // Intersep klik tautan logout untuk kirim sinyal segera sebelum redirect
      var logoutLinks = document.querySelectorAll('a[href*="logout"]');
      logoutLinks.forEach(function(a){
        a.addEventListener('click', function(){ postToSW('LOGOUT'); }, { once: true });
      });
    } catch(e) { /* no-op */ }
  });
})();
</script>
HTML;
}

// Prioritas rendah agar tidak mengganggu elemen menu; cukup menyisipkan script di header
add_action('nav_menu', 'epiPwaAuthInject', 9);