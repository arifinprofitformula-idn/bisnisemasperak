<?php
header('Content-Type: application/xml');

// Mulai membuat XML
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

// Tambahkan URL halaman statis (jika ada)
// Contoh: halaman beranda
echo '<url>';
echo '<loc>' . $weburl . '</loc>';
echo '<lastmod>' . date('Y-m-d') . '</lastmod>'; // Atau tanggal terakhir modifikasi homepage
echo '<changefreq>daily</changefreq>';
echo '<priority>1.0</priority>';
echo '</url>';

// Sitemap Artikel

// Ambil data artikel dari database
$articles = db_select("SELECT `art_tglpublish`, `art_slug` FROM `sa_artikel` WHERE `art_status`=1");
if (count($articles) > 0) {
    $artikelslug = 'artikel';
    if (isset($settings['url_artikel'])) { $artikelslug = $settings['url_artikel']; }

    foreach ($articles as $article) {
        $article_url = $weburl . $artikelslug.'/' . htmlspecialchars($article['art_slug']); // Sesuaikan struktur URL artikel Anda
        $last_mod = date('Y-m-d', strtotime($article['art_tglpublish'])); // Menggunakan tanggal publish sebagai lastmod
        if (!isset($produkdate)) { $produkdate = $last_mod; }
        echo '<url>';
        echo '<loc>' . $article_url . '</loc>';
        echo '<lastmod>' . $last_mod . '</lastmod>';
        echo '<changefreq>weekly</changefreq>'; // Asumsi artikel tidak terlalu sering berubah setelah publish
        echo '<priority>0.8</priority>'; // Prioritas artikel sedikit di bawah homepage
        echo '</url>';
    }
}

if (!isset($produkdate)) { $produkdate = '2025-06-25'; }

// Sitemap Produk dan Landingpage

$produk = db_select("SELECT * FROM `sa_page` WHERE `pro_status`=1");
if (count($produk) > 0) {
    foreach ($produk as $pro) {
        echo '<url>';
        echo '<loc>' . $weburl.$pro['page_url'] . '</loc>';
        echo '<lastmod>'.$produkdate.'</lastmod>';
        echo '<changefreq>weekly</changefreq>'; // Asumsi artikel tidak terlalu sering berubah setelah publish
        echo '<priority>0.8</priority>'; // Prioritas artikel sedikit di bawah homepage
        echo '</url>';
    }
}

do_action('sitemap');

echo '</urlset>';
?>