<?php
// Mulai sesi hanya jika belum aktif untuk mencegah Notice: session_start(): Ignoring session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Deteksi status login untuk mengatur perilaku wajib referral tanpa menghentikan proses sponsor
if (function_exists('is_login')) {
    $logged_in_user_id = is_login();
}
if (isset($settings['iddefault']) && !empty($settings['iddefault'])) {
	$default = explode(',',$settings['iddefault']);
	$jml = count($default);
	if ($jml > 1) {
		$rand = rand(0,$jml-1);
		$idsponsordefault = trim($default[$rand]);
	} else {
		$idsponsordefault = $default[0];
	}
} else {
	# Kalau tidak ada ID Default, maka ID 1 yang jadi default
	$idsponsordefault = 1;
}

if (isset($settings['khususpremium']) && $settings['khususpremium'] == 1) {
	# Affiliasi hanya khusus premium
	$khususpremium = " AND `mem_status` > 1";
} else {
	$khususpremium = "";
}


# Prioritas 1 adalah URL Affiliasi
if (isset($kodeaff) && trim($kodeaff) != '') {
	$ceksp = db_row("SELECT * FROM `sa_member` WHERE `mem_kodeaff`='".txtonly(strtolower($kodeaff))."'".$khususpremium);
	
	if (isset($ceksp['mem_id'])) {
		$idsponsor = $ceksp['mem_id'];
		$datasponsor = getdatamember($idsponsor);
	} 
} 

# Prioritas 2 adalah cookie
if (!isset($idsponsor)) {
	if (isset($_COOKIE["idsponsor"]) && is_numeric($_COOKIE["idsponsor"])) {
		$idsponsor = $_COOKIE["idsponsor"];        
    } else {
        if (isset($settings['wajibaff']) && $settings['wajibaff'] == 1 && empty($logged_in_user_id)) {
            $reqPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            if (stripos($reqPath, '/login') === false) {
                $openfile = 'theme/'.($settings['theme'] ?? 'simple').'/salinkaff.php';
                return;
            } else {
                $idsponsor = $idsponsordefault;
            }
        } else {
            $idsponsor = $idsponsordefault;
        }
    }

	$datasponsor = getdatamember($idsponsor);

	if (!isset($datasponsor['mem_id'])) {
		# Jika ID sudah ada tapi lalu dihapus, maka munculkan sponsor default
		$idsponsor = $idsponsordefault;
		$datasponsor = getdatamember($idsponsor);
	}
}

setcookie("idsponsor", "", strtotime('-30 days'),'/');
setcookie("idsponsor",$idsponsor,strtotime('+30 days'),'/');

// Periksa apakah visitor sudah dihitung untuk idsponsor ini dalam sesi saat ini
if (!isset($_SESSION['visitor_count']) || (isset($_SESSION['visitor_count']) && $_SESSION['visitor_count'] != $idsponsor)) {
    // Tandai visitor ini sudah dihitung untuk idsponsor ini dalam sesi
    $_SESSION['visitor_count'] = $idsponsor;

    // Dapatkan tanggal saat ini dalam format YYYY-MM-DD untuk tabel SQL DATE
    $current_date = date('Y-m-d');

    // Pastikan $idsponsor adalah integer untuk keamanan dan konsistensi database
    $id_sponsor_for_db = (int)$idsponsor;

    // Buat kueri SQL untuk INSERT atau UPDATE
    // Jika kombinasi id_sponsor dan visit_date sudah ada (primary key),
    // maka kolom 'count' akan di-increment (ditambah 1).
    // Jika belum ada, baris baru akan dimasukkan dengan 'count' = 1.
    $sql = "INSERT INTO `sa_visitor` (`id_sponsor`, `visit_date`, `count`) 
            VALUES ({$id_sponsor_for_db}, '{$current_date}', 1)
            ON DUPLICATE KEY UPDATE `count` = `count` + 1;";
    
    // Eksekusi kueri menggunakan fungsi db_query() Anda
    if (db_query($sql)) {
        // Opsional: Log atau tampilkan pesan sukses
        // echo "Visitor count updated successfully for sponsor ID: {$id_sponsor_for_db} on date: {$current_date}<br>";
    } else {
        // Opsional: Log atau tampilkan pesan error jika kueri gagal
        error_log("Gagal memperbarui hitungan visitor untuk sponsor ID: {$id_sponsor_for_db} pada tanggal: {$current_date}");
        // echo "Terjadi kesalahan saat memperbarui hitungan visitor.<br>";
    }
}
?>
