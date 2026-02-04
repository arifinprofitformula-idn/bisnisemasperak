<?php
/**
 * API Endpoint untuk Landing Pages
 * Mengambil data landing page untuk ditampilkan di welcome-epi.php
 */

// Set header untuk JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include konfigurasi dan fungsi
require_once '../config.php';
require_once '../fungsi.php';

try {
    // Validasi method
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        exit;
    }

    // Cek autentikasi member untuk mendapatkan mem_kodeaff
    $user_id = is_login();
    if (!$user_id) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required'
        ]);
        exit;
    }

    // Ambil data member yang sedang login
    $datamember = db_row("SELECT mem_kodeaff FROM sa_member WHERE mem_id=" . intval($user_id));
    if (!$datamember) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Member data not found'
        ]);
        exit;
    }

    // Ambil data landing pages yang aktif (pro_harga IS NULL = landing page, bukan produk)
    $landingPages = db_select("
        SELECT 
            page_id,
            page_judul,
            page_url,
            page_diskripsi,
            page_iframe,
            pro_img,
            pro_status
        FROM sa_page 
        WHERE pro_harga IS NULL 
        AND pro_status = '1' 
        ORDER BY page_judul ASC
    ");

    // Format data untuk response
    $formattedPages = [];
    foreach ($landingPages as $page) {
        $formattedPages[] = [
            'id' => (int)$page['page_id'],
            'title' => $page['page_judul'],
            'url' => $page['page_url'],
            'description' => $page['page_diskripsi'],
            'iframe_url' => $page['page_iframe'],
            'image' => $page['pro_img'] ? 'upload/' . $page['pro_img'] : null,
            'status' => $page['pro_status'],
            'full_url' => $weburl . $datamember['mem_kodeaff'] . '/' . $page['page_url']
        ];
    }

    // Hitung statistik
    $stats = [
        'total_pages' => count($formattedPages),
        'active_pages' => count(array_filter($formattedPages, function($page) {
            return $page['status'] === '1';
        })),
        'last_updated' => date('Y-m-d H:i:s')
    ];

    // Response sukses
    echo json_encode([
        'success' => true,
        'data' => $formattedPages,
        'stats' => $stats,
        'timestamp' => time()
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    // Log error (jika diperlukan)
    error_log("Landing Pages API Error: " . $e->getMessage());
    
    // Response error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
}
?>