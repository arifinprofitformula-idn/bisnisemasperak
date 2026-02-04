<?php
if (!db_var("show tables like 'sa_kategori'")) {
	db_query("CREATE TABLE IF NOT EXISTS `sa_kategori` (
		`kat_id` bigint(20) NOT NULL auto_increment,							
		`kat_parent_id` varchar(50) NOT NULL default '',
		`kat_nama` varchar(100) NULL,
		`kat_slug` varchar(100) NULL,
		PRIMARY KEY  (`kat_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");

	db_query("CREATE TABLE IF NOT EXISTS `sa_artikel` (
		`art_id` bigint(20) NOT NULL auto_increment,
		`art_tglpublish` datetime NULL,
		`art_kat_id` varchar(50) NOT NULL default '',
		`art_judul` varchar(100) NULL,
		`art_slug` varchar(100) NULL,
    `art_img` VARCHAR(200) NULL,
		`art_konten` longtext,
		`art_role` varchar(1) NULL,
		`art_product` bigint(20) NOT NULL default 0,
		`art_status` varchar(1) NULL,
		PRIMARY KEY  (`art_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
}

if (!db_var("SHOW COLUMNS FROM `sa_order` LIKE 'order_trx'")) {
	db_query("ALTER TABLE `sa_order` ADD `order_trx` VARCHAR(50) NULL");
}

if (version_compare($settings['ver'], '1.0.7') < 0) {
	db_query("ALTER TABLE `sa_page` CHANGE `page_diskripsi` `page_diskripsi` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL");
	db_query("ALTER TABLE `sa_page` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
	db_query("ALTER TABLE `sa_artikel` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
	db_query("ALTER TABLE `sa_kategori` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
	db_query("ALTER TABLE `sa_setting` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
	db_query("ALTER TABLE `sa_member` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
}

if (version_compare($settings['ver'], '1.0.8') < 0) {
	db_query("ALTER TABLE `sa_page` ADD `pro_img` VARCHAR(50) NULL");
}

if (version_compare($settings['ver'], '1.0.9') < 0) {
	db_query("ALTER TABLE `sa_artikel` ADD `art_writer` bigint(20) NOT NULL default 1");
}

if (version_compare($settings['ver'], '1.1.6') < 0) {
	db_query("ALTER TABLE `sa_laporan` ADD `lap_app` varchar(5) NOT NULL default 'SA'");
	if (!isset($datasetting['theme'])) {
    $datasetting['theme'] = 'simple';
    updatesettings($datasetting);
  } 
}

if (version_compare($settings['ver'], '1.2.7') < 0) {
	db_query("ALTER TABLE `sa_page` ADD `page_fr` TEXT NULL");
}

if (version_compare($settings['ver'], '1.3.2') < 0) {
	db_query("CREATE TABLE IF NOT EXISTS `sa_visitor` (
	    `id_sponsor` INT NOT NULL,
	    `visit_date` DATE NOT NULL,
	    `count` INT DEFAULT 0,
	    PRIMARY KEY (`id_sponsor`, `visit_date`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

	// Nama file TXT yang akan dimigrasi
	$file_name = 'visitor_data.txt';

	// Pastikan fungsi db_query() sudah tersedia.
	// Jika belum, Anda perlu mendefinisikannya di sini atau di file lain yang di-include.
	if (!function_exists('db_query')) {
	    #echo "ERROR: Fungsi db_query() tidak ditemukan. Pastikan fungsi ini sudah didefinisikan sebelum menjalankan skrip ini.<br>";
	    exit;
	}

	#echo "Memulai proses migrasi dari '{$file_name}' ke tabel 'sa_visitor'...<br>";

	// Periksa apakah file ada sebelum membuka
	if (!file_exists($file_name)) {
	    #echo "INFO: File '{$file_name}' tidak ditemukan. Melewati proses migrasi.<br>";
	    // Keluar dari blok if tanpa error
	} else {
	    // Buka file untuk dibaca
	    $file = fopen($file_name, 'r');

	if ($file) {
	    $migrated_rows = 0;
	    $failed_rows = 0;

	    // Baca file baris per baris
	    while (($line = fgets($file)) !== false) {
	        $line = trim($line); // Hapus spasi di awal/akhir baris

	        // Lewati baris kosong
	        if (empty($line)) {
	            continue;
	        }

	        // Pisahkan data berdasarkan koma
	        $line_data = explode(',', $line);

	        // Pastikan ada 3 bagian data: id_sponsor, tanggal, count
	        if (count($line_data) == 3) {
	            $id_sponsor_raw = $line_data[0];
	            $visit_date_raw = $line_data[1]; // Format YYYYMMDD
	            $count_raw = $line_data[2];

	            // Validasi dan konversi tipe data
	            $id_sponsor = (int)$id_sponsor_raw;
	            $count = (int)$count_raw;

	            // Konversi format tanggal dari YYYYMMDD ke YYYY-MM-DD
	            // Contoh: 20250227 menjadi 2025-02-27
	            if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $visit_date_raw, $matches)) {
	                $visit_date = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
	            } else {
	                #echo "SKIP: Format tanggal tidak valid pada baris: '{$line}'<br>";
	                $failed_rows++;
	                continue;
	            }

	            // Buat kueri INSERT
	            // Menggunakan ON DUPLICATE KEY UPDATE untuk menangani kasus jika ada duplikasi
	            // (misalnya, jika Anda menjalankan skrip ini beberapa kali atau ada data yang sama)
	            // Ini akan menambahkan count jika id_sponsor dan visit_date sudah ada.
	            // Jika Anda hanya ingin INSERT baru dan mengabaikan duplikasi, gunakan INSERT IGNORE.
	            // Jika Anda ingin mengganti baris yang ada, gunakan REPLACE INTO.
	            $sql = "INSERT INTO `sa_visitor` (`id_sponsor`, `visit_date`, `count`) 
	                    VALUES ({$id_sponsor}, '{$visit_date}', {$count})
	                    ON DUPLICATE KEY UPDATE `count` = `count` + VALUES(`count`);";
	            
	            // Eksekusi kueri menggunakan fungsi db_query() Anda
	            if (db_query($sql)) {
	                $migrated_rows++;
	            } else {
	                #echo "Gagal memigrasi baris: '{$line}'<br>";
	                $failed_rows++;
	            }
	        } else {
	            #echo "SKIP: Format baris tidak sesuai (diharapkan 3 kolom terpisah koma) pada baris: '{$line}'<br>";
	            $failed_rows++;
	        }
	    }

	    fclose($file);
	    /*
	    echo "<br>Proses migrasi selesai.<br>";
	    echo "Jumlah baris berhasil dimigrasi: {$migrated_rows}<br>";
	    echo "Jumlah baris gagal/dilewati: {$failed_rows}<br>";
		*/
	} else {
	    #echo "ERROR: Tidak dapat membuka file '{$file_name}'. Pastikan file ada dan memiliki izin baca.<br>";
	}
	} // Penutup blok else untuk file_exists
}

// Tambahan kolom untuk fitur Harga Tampil, Akses Gratis, dan Auto-Approve
if (version_compare($settings['ver'], '1.3.3') < 0) {
    // pro_harga_display: harga yang ditampilkan ke user (opsional)
    if (!db_var("SHOW COLUMNS FROM `sa_page` LIKE 'pro_harga_display'")) {
        db_query("ALTER TABLE `sa_page` ADD `pro_harga_display` INT NULL");
    }

    // pro_free_access: flag akses gratis tanpa order
    if (!db_var("SHOW COLUMNS FROM `sa_page` LIKE 'pro_free_access'")) {
        db_query("ALTER TABLE `sa_page` ADD `pro_free_access` TINYINT(1) NOT NULL DEFAULT 0");
    }

    // pro_auto_approve: flag order otomatis di-approve saat dibuat
    if (!db_var("SHOW COLUMNS FROM `sa_page` LIKE 'pro_auto_approve'")) {
        db_query("ALTER TABLE `sa_page` ADD `pro_auto_approve` TINYINT(1) NOT NULL DEFAULT 0");
    }

    // Backfill harga tampil jika belum di-set
    db_query("UPDATE `sa_page` SET `pro_harga_display` = `pro_harga` WHERE `pro_harga_display` IS NULL");
}

if (version_compare($settings['ver'], '1.4.0') < 0) {
    if (!db_var("SHOW COLUMNS FROM `sa_page` LIKE 'meta_headline'")) {
        db_query("ALTER TABLE `sa_page` ADD `meta_headline` VARCHAR(120) NULL");
    }
    if (!db_var("SHOW COLUMNS FROM `sa_page` LIKE 'meta_subheadline'")) {
        db_query("ALTER TABLE `sa_page` ADD `meta_subheadline` VARCHAR(160) NULL");
    }
    if (!db_var("SHOW COLUMNS FROM `sa_page` LIKE 'meta_description'")) {
        db_query("ALTER TABLE `sa_page` ADD `meta_description` VARCHAR(300) NULL");
    }
    if (!db_var("SHOW COLUMNS FROM `sa_page` LIKE 'meta_img'")) {
        db_query("ALTER TABLE `sa_page` ADD `meta_img` VARCHAR(200) NULL");
    }
    if (!db_var("SHOW COLUMNS FROM `sa_page` LIKE 'page_short'")) {
        db_query("ALTER TABLE `sa_page` ADD `page_short` VARCHAR(32) NULL");
    }
    if (!db_var("SHOW TABLES LIKE 'sa_page_meta_history'")) {
        db_query("CREATE TABLE IF NOT EXISTS `sa_page_meta_history` (
            `hist_id` BIGINT(20) NOT NULL AUTO_INCREMENT,
            `page_id` BIGINT(20) NOT NULL,
            `mem_id` BIGINT(20) NULL,
            `old_values` TEXT NULL,
            `new_values` TEXT NULL,
            `changed_at` DATETIME NOT NULL,
            PRIMARY KEY (`hist_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    }
}

// removed: legacy bukti transfer columns

// Ensure order discount-related columns exist
if (!db_var("SHOW COLUMNS FROM `sa_order` LIKE 'order_price_display'")) {
    db_query("ALTER TABLE `sa_order` ADD `order_price_display` INT NULL");
}
if (!db_var("SHOW COLUMNS FROM `sa_order` LIKE 'order_discount'")) {
    db_query("ALTER TABLE `sa_order` ADD `order_discount` INT NULL");
}
if (!db_var("SHOW COLUMNS FROM `sa_order` LIKE 'order_promo_code'")) {
    db_query("ALTER TABLE `sa_order` ADD `order_promo_code` VARCHAR(50) NULL");
}
if (!db_var("SHOW COLUMNS FROM `sa_order` LIKE 'order_important_note'")) {
    db_query("ALTER TABLE `sa_order` ADD `order_important_note` VARCHAR(255) NULL");
}

// Ensure product-category mapping table exists
if (!db_var("SHOW TABLES LIKE 'sa_product_category'")) {
    db_query("CREATE TABLE IF NOT EXISTS `sa_product_category` (
        `page_id` BIGINT(20) NOT NULL,
        `kat_id` BIGINT(20) NOT NULL,
        PRIMARY KEY (`page_id`, `kat_id`),
        KEY `idx_kat` (`kat_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
}
?>
