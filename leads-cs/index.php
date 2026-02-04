<?php
// Guard halaman publik: wajib input referral inline (tanpa redirect) saat Wajib Link Affiliasi aktif
// Berlaku untuk pengunjung NON-LOGIN tanpa cookie sponsor
require_once __DIR__ . '/../fungsi.php';
$settings = getsettings();
// AJAX validation (instan tanpa reload) untuk email & WhatsApp
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_validate']) && $_POST['ajax_validate'] === '1') {
    header('Content-Type: application/json; charset=UTF-8');
    $errors = [];
    // Validasi email
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    if (empty($email) || !validemail($email)) {
        $errors['email'] = 'Email tidak valid. Mohon periksa kembali.';
    } else {
        if (db_exist("SELECT `mem_email` FROM `sa_member` WHERE `mem_email`='".cek($email)."'")) {
            $errors['email'] = 'Email sudah digunakan. Silakan gunakan email lain atau login bila ini akun Anda. <a href="login" class="text-link fw-semibold">Login sekarang</a>';
        }
    }
    // Validasi WhatsApp (opsional, bila ada)
    $wa = isset($_POST['whatsapp']) ? trim($_POST['whatsapp']) : '';
    if (!empty($wa)) {
        $formatted_wa = formatwa($wa);
        if (empty($formatted_wa)) {
            $errors['whatsapp'] = 'Nomor WhatsApp tidak valid. Gunakan format 08123456789.';
        } else {
            if (db_exist("SELECT `mem_whatsapp` FROM `sa_member` WHERE `mem_whatsapp`='".cek($formatted_wa)."'")) {
                $errors['whatsapp'] = 'Nomor WhatsApp sudah terdaftar. Silakan gunakan nomor lain atau login bila ini akun Anda.';
            }
        }
    }

    echo json_encode([
        'ok' => empty($errors),
        'errors' => $errors,
    ]);
    exit();
}
$loggedInUserId = is_login();
if (!$loggedInUserId) {
    $wajibAff = isset($settings['wajibaff']) ? (int)$settings['wajibaff'] : 0;
    $hasSponsorCookie = isset($_COOKIE['idsponsor']) && is_numeric($_COOKIE['idsponsor']);
    if ($wajibAff === 1 && !$hasSponsorCookie) {
        // Tampilkan form referral inline di halaman ini
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kodeaff'])) {
            $kodeaff_input = trim($_POST['kodeaff']);
            if (substr($kodeaff_input, 0, 4) === 'http') {
                // Jika input URL penuh, hilangkan prefix domain
                $kodeaff = str_replace($weburl, '', $kodeaff_input);
                if (substr($kodeaff, 0, 4) === 'http') {
                    $newweb = str_replace('https://', 'http://', $weburl);
                    $kodeaff = str_replace($newweb, '', $kodeaff_input);
                }
                $kodeaff = txtonly($kodeaff);
            } else {
                $kodeaff = txtonly($kodeaff_input);
            }

            // Validasi sponsor (perhatikan Khusus Premium bila aktif)
            $setkhususpremium = getsettings('khususpremium');
            $khususpremium = ($setkhususpremium == 1) ? " AND `mem_status` > 1" : "";
            $datasponsor = db_row("SELECT * FROM `sa_member` WHERE `mem_kodeaff`='" . strtolower($kodeaff) . "'" . $khususpremium);

            if (isset($datasponsor['mem_id'])) {
                // Set cookie sponsor 30 hari
                setcookie("idsponsor", "", strtotime('-30 days'), '/');
                setcookie("idsponsor", $datasponsor['mem_id'], strtotime('+30 days'), '/');

                // Tracking visitor per hari
                if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
                if (!isset($_SESSION['visitor_count']) || $_SESSION['visitor_count'] != $datasponsor['mem_id']) {
                    $_SESSION['visitor_count'] = $datasponsor['mem_id'];
                    $current_date = date('Y-m-d');
                    $id_sponsor_for_db = (int)$datasponsor['mem_id'];
                    $sql = "INSERT INTO `sa_visitor` (`id_sponsor`, `visit_date`, `count`) VALUES (" . $id_sponsor_for_db . ", '" . $current_date . "', 1) ON DUPLICATE KEY UPDATE `count` = `count` + 1;";
                    db_query($sql);
                }

                // Reload halaman untuk membuka akses konten
                header('Location: ' . ($_SERVER['REQUEST_URI'] ?? rtrim($weburl, '/') . '/leads-cs/'));
                exit();
            } else {
                $error = 'Maaf, URL tidak valid atau sponsor anda belum melakukan upgrade';
            }
        }

        // HTML Form gating inline
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Masukkan Kode Referral</title>
            <link href="/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
            <style>
                :root {
                    --cs-black: #0B0B0B;
                    --cs-dark: #1A1A1A;
                    --cs-text: #1F2937;
                    --cs-muted: #6B7280;
                    --cs-border: #E5E7EB;
                    --cs-focus: #D4AF37;
                    --cs-gold-1: #D4AF37;
                    --cs-gold-2: #F4D03F;
                }

                body {
                    background: linear-gradient(135deg, var(--cs-black) 0%, var(--cs-dark) 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0;
                    font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    padding: 24px;
                }

                /* Pastikan card berada tepat di tengah halaman */
                .container {
                    width: 100%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 8px;
                }

                .card {
                    max-width: 720px;
                    width: 100%;
                    border-radius: 18px;
                    background: #ffffff;
                    border: 1px solid var(--cs-border);
                    box-shadow: 0 22px 56px rgba(11, 11, 11, 0.35);
                }

                .brand { text-align: center; margin-bottom: 12px; }
                .brand img { max-height: 64px; filter: drop-shadow(0 6px 16px rgba(212, 175, 55, 0.28)); }
                .title { font-weight: 800; color: var(--cs-text); }
                .subtitle { color: var(--cs-muted); }

                .input-group-text {
                    background: #FAFAFA;
                    border: 1px solid var(--cs-border);
                    border-right: 0;
                    color: var(--cs-muted);
                    font-weight: 500;
                    border-top-left-radius: 12px;
                    border-bottom-left-radius: 12px;
                }

                .form-control {
                    border: 1px solid var(--cs-border);
                    border-left: 0;
                    border-top-right-radius: 12px;
                    border-bottom-right-radius: 12px;
                    padding: 12px 14px;
                    font-size: 16px;
                }

                .form-control:focus {
                    border-color: var(--cs-focus);
                    box-shadow: 0 0 0 0.25rem rgba(212, 175, 55, 0.35);
                    outline: none;
                }

                .btn-warning {
                    background: linear-gradient(135deg, var(--cs-gold-1) 0%, var(--cs-gold-2) 100%);
                    border: none;
                    color: #1F2937;
                    border-radius: 12px;
                    padding: 12px 16px;
                    font-weight: 700;
                    letter-spacing: 0.2px;
                    box-shadow: 0 10px 24px rgba(212, 175, 55, 0.32);
                }

                .btn-warning:hover { filter: brightness(1.03); }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="card p-4">
                    <div class="brand">
                        <img src="<?= htmlspecialchars($weburl, ENT_QUOTES, 'UTF-8'); ?>upload/logo-webb.jpg" alt="Logo" style="max-height:64px;" />
                    </div>
                    <h1 class="h4 title text-center mb-2">Masukkan Kode Referral</h1>
                    <p class="text-center subtitle mb-3">Silakan isi kode referral sponsor Anda untuk melanjutkan</p>
                    <?php if (!empty($error)) { ?>
                        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php } ?>
                    <form method="post" action="">
                        <label class="form-label fw-semibold" style="color: #000000">URL Affiliasi</label>
                        <div class="input-group mb-3">
                            <span class="input-group-text"><?= htmlspecialchars($weburl, ENT_QUOTES, 'UTF-8'); ?></span>
                            <input type="text" class="form-control" name="kodeaff" placeholder="Masukkan Kode Refferal Anda" required />
                        </div>
                        <button type="submit" class="btn btn-warning w-100">Submit Kode</button>
                    </form>
                </div>
            </div>
            <script src="/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
        </body>
        </html>
        <?php
        exit();
    }
}
?>
<?php
// Setup sponsor & proses registrasi agar identik dengan theme/simple/saregister.php
// Ambil idsponsor dari cookie jika ada, default ke 1
if (!isset($idsponsor)) {
    if (isset($_COOKIE['idsponsor']) && is_numeric($_COOKIE['idsponsor'])) {
        $idsponsor = (int)$_COOKIE['idsponsor'];
    } else {
        $idsponsor = 1;
    }
}

// Data sponsor (untuk box sponsor dan integrasi pixel jika diperlukan)
$datasponsor = db_row("SELECT * FROM `sa_member` WHERE `mem_id`=".$idsponsor);
if (function_exists('extractdata')) { $datasponsor = extractdata($datasponsor); }

// Proses submit form registrasi identik dengan saregister.php
if (isset($_POST['nama']) && !empty($_POST['nama']) && isset($_POST['email']) && validemail($_POST['email'])) {
    // reCAPTCHA (jika kunci tersedia)
    if (isset($settings['recap_secret']) && !empty($settings['recap_secret'])) {
        $secretKey = $settings['recap_secret'];
        $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
        $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'secret' => $secretKey,
            'response' => $recaptchaResponse,
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($response, true);
        if ($result && isset($result['success']) && $result['success']) {
            $formok = 1;
        } else {
            $error = 'Verifikasi reCAPTCHA gagal';
        }
    } else {
        $formok = 1;
    }

    if (isset($formok) && $formok == 1) {
        // Cek duplikasi email
        if (db_exist("SELECT `mem_email` FROM `sa_member` WHERE `mem_email`='".cek($_POST['email'])."'")) {
            $error = 'Email sudah ada yang menggunakan';
        }

        // Cek duplikasi WhatsApp
        if (!isset($error) && isset($_POST['whatsapp']) && !empty($_POST['whatsapp'])) {
            $formatted_wa = formatwa($_POST['whatsapp']);
            if (!empty($formatted_wa)) {
                if (db_exist("SELECT `mem_whatsapp` FROM `sa_member` WHERE `mem_whatsapp`='".cek($formatted_wa)."'")) {
                    $error = 'Nomor WhatsApp ini sudah terdaftar. Silakan gunakan nomor lain atau login jika ini adalah akun Anda.';
                }
            }
        }

        // Validasi field wajib dari form builder
        $req = db_select("SELECT * FROM `sa_form` WHERE `ff_registrasi`=1 AND `ff_required`=1");
        if (is_array($req) && count($req) > 0) {
            foreach ($req as $rq) {
                if (!isset($_POST[$rq['ff_field']]) || empty($_POST[$rq['ff_field']])) {
                    $error = $rq['ff_label'].' wajib diisi';
                } else {
                    if ($rq['ff_field'] == 'whatsapp') {
                        if (empty(formatwa($_POST['whatsapp']))) {
                            $error = $rq['ff_label'].' wajib diisi dg format 08123456789';
                        }
                    }
                }
            }
        }

        if (!isset($error)) {
            // Sponsor dari input (jika ada) override cookie
            if (isset($_POST['sponsor']) && !empty($_POST['sponsor'])) {
                $sponsor = db_var("SELECT `mem_id` FROM `sa_member` WHERE `mem_kodeaff`='".txtonly(strtolower($_POST['sponsor']))."'");
                if (is_numeric($sponsor)) { $idsponsor = (int)$sponsor; }
            }

            $defaultkey = array('nama','email','password','whatsapp','kodeaff');
            $datalain = '';
            unset($kodeaff);
            foreach ($_POST as $key => $value) {
                if (in_array($key, $defaultkey)) {
                    ${$key} = cek($value);
                } else {
                    $datalain .= '['.txtonly(strtolower($key)).'|'.cek($value).']';
                }
            }

            // Upload gambar (jika ada), identik dengan saregister
            if (isset($_FILES) && count($_FILES) > 0) {
                $max_size = 1024000;
                $whitelist_type = array('image/jpeg', 'image/jpg', 'image/png','image/gif');
                $pic_dir = str_replace('saregister_gold_silver.php','upload',__FILE__);
                $memberid = 'XXX'.rand(1000,9999).'XXX';
                if (!file_exists($pic_dir)) { mkdir($pic_dir); }
                foreach($_FILES as $field => $files) {
                    $filename = $memberid.'_'.$field;
                    $target_file = $pic_dir.'/'.$filename;
                    $uploadOk = 1;
                    $imageFileType = strtolower(pathinfo($files["name"],PATHINFO_EXTENSION));
                    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
                        $txterror = "Maaf, hanya support JPG, JPEG, PNG & GIF saja.";
                        $uploadOk = 0;
                    }
                    if (!in_array($files["type"], $whitelist_type)) {
                        $txterror = "Maaf, hanya support JPG, JPEG, PNG & GIF saja.";
                        $uploadOk = 0;
                    }
                    if ($files["size"] > $max_size) {
                        $txterror = 'Maaf, gambar terlalu besar. Max. 1Mb';
                        $uploadOk = 0;
                    }
                    if ($uploadOk == 1) {
                        $file = $files["tmp_name"];
                        $target_file = $target_file.'.'.$imageFileType;
                        if (class_exists('Imagick')) {
                            $img = new Imagick();
                            $img->readImage($file);
                            $width = $img->getImageWidth();
                            if ($width > 800) { $width = 800; }
                            $img->setimagebackgroundcolor('white');
                            $img->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
                            $img->setImageCompression(Imagick::COMPRESSION_JPEG);
                            $img->setImageCompressionQuality(80);
                            $img->resizeImage($width,800,Imagick::FILTER_CATROM,1,TRUE);
                            $img->stripImage();
                            $img->writeImage($target_file);
                        } else {
                            // Fallback: langsung pindah file jika Imagick tidak tersedia
                            move_uploaded_file($file, $target_file);
                        }
                        $datalain .= '['.txtonly(strtolower($field)).'|'.$filename.'.'.$imageFileType.']';
                    }
                }
            }

            // Password default bila kosong
            if (!isset($password) || empty($password)) { $password = randomword(); } else { $password = $_POST['password']; }
            if (!isset($kodeaff)) { $kodeaff = $nama; }
            $kodeaff = cekkodeaff(txtonly(strtolower($kodeaff)));
            if (isset($whatsapp)) { $whatsapp = formatwa($whatsapp); } else { $whatsapp = ''; }

            $newuserid = db_insert("INSERT INTO `sa_member` (
                `mem_nama`,`mem_email`,`mem_password`,`mem_whatsapp`,`mem_kodeaff`,
                `mem_datalain`,`mem_tgldaftar`,`mem_status`,`mem_role`)
                VALUES ('".$nama."','".$email."','".create_hash($password)."',
                '".$whatsapp."','".$kodeaff."','".$datalain."','".date('Y-m-d H:i:s')."',
                1,1)");

            if (is_numeric($newuserid)) {
                $network = '['.numonly($idsponsor).']'.db_var("SELECT `sp_network` FROM `sa_sponsor` WHERE `sp_mem_id`=".$idsponsor);
                $cek = db_insert("INSERT INTO `sa_sponsor` (`sp_mem_id`,`sp_sponsor_id`,`sp_network`) VALUES ($newuserid,$idsponsor,'".$network."')");
                if (isset($memberid)) {
                    $datalain = str_replace($memberid,$newuserid,$datalain);
                    db_query("UPDATE `sa_member` SET `mem_datalain`='".$datalain."' WHERE `mem_id`=".$newuserid);
                    $files = glob($pic_dir . '/'.$memberid.'*');
                    foreach ($files as $file) { $newName = str_replace($memberid, $newuserid, $file); rename($file, $newName); }
                }
                // Kirim notif
                $customfield['newpass'] = $password;
                if (function_exists('sa_notif')) { sa_notif('daftar',$newuserid,$customfield); }
            } else {
                $error = db_error();
            }

            if (isset($cek)) {
                if ($cek === false) {
                    $error = db_error();
                } else {
                    // Override khusus halaman ini: abaikan setting dashboard dan paksa redirect ke URL absolut
                    $overrideUrl = 'http://localhost/bep/sukses-lead/';
                    header('Location: ' . $overrideUrl);
                    exit;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coming Soon — Lead Magnet</title>
    <meta name="description" content="Landing page lead magnet elegan bertema gelap dengan countdown dan form pendaftaran terhubung affiliasi.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tambahan untuk form registrasi identik dengan saregister -->
    <link href="<?= $weburl;?>bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?=$weburl;?>fontawesome/css/fontawesome.min.css" rel="stylesheet" />
    <link href="<?=$weburl;?>fontawesome/css/regular.min.css" rel="stylesheet" />
    <link href="<?=$weburl;?>fontawesome/css/solid.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Card form dengan background mencolok untuk section lead magnet */
        .section.section-form {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
        }
        .section-form .register-container {
            /* Efek 3D + transparan 60% (≈ 40% opacity) dengan sentuhan emas */
            position: relative;
            background: linear-gradient(180deg, rgba(255,255,255,0.40) 0%, rgba(255,255,255,0.30) 100%);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(212, 175, 55, 0.35);
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.45), 0 8px 24px rgba(212, 175, 55, 0.28);
            border-radius: 18px;
            padding: 24px;
            max-width: 720px;
            margin: 0 auto;
            transform: perspective(1000px) translateZ(6px);
            transition: transform 240ms ease, box-shadow 240ms ease, backdrop-filter 240ms ease;
        }
        .section-form .register-container::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            pointer-events: none;
            background: linear-gradient(180deg, rgba(255,255,255,0.25), rgba(255,255,255,0.05));
            opacity: 0.4;
        }
        .section-form .register-container:hover {
            transform: perspective(1000px) translateZ(8px);
            box-shadow: 0 28px 60px rgba(0,0,0,0.5), 0 10px 26px rgba(212,175,55,0.32);
        }
        /* Form control agar tetap terbaca di background transparan */
        .section-form .register-container .form-control {
            background: rgba(255,255,255,0.35);
            color: #111;
            border: 1px solid rgba(212, 175, 55, 0.35);
        }
        .section-form .register-container .form-control::placeholder { color: rgba(17,17,17,0.65); }
        .section-form .welcome-title {
            color: #1A1A1A;
            font-weight: 800;
            letter-spacing: 0.3px;
        }
        .section-form .btn-register {
            background: linear-gradient(135deg, #D4AF37 0%, #F4D03F 100%);
            color: #0B0B0B;
            border: none;
            box-shadow: 0 12px 28px rgba(212, 175, 55, 0.35);
        }
        /* Sembunyikan elemen terkait password dari tampilan form lead magnet */
        #registrasi input[type="password"],
        #registrasi [name="password"],
        #registrasi [name="repassword"],
        #registrasi #togglePassword {
            display: none !important;
        }
        /* Sembunyikan badge reCAPTCHA (iframe biasanya di kanan bawah) */
        .grecaptcha-badge {
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }

        /* Label wajib hitam, lebih kecil & terpusat */
        .section-form .register-container label,
        .section-form .register-container .form-label,
        .section-form .register-container .input-label {
            color: #000000 !important;
            font-size: 0.92rem;
            text-align: center;
            display: block;
        }
        /* Sponsor box: terpusat & teks hitam */
        .section-form .register-container .sponsor-box {
            text-align: center;
            color: #000000;
            font-size: 0.92rem;
        }
        /* Login hint */
        .section-form .register-container .login-hint {
            color: #000000;
            font-size: 0.92rem;
        }

        /* Fitur dalam bentuk card yang rapi & minimalis */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
            max-width: 960px;
            margin: 0 auto;
        }
        .feature-card {
            background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.03));
            border: 1px solid rgba(212,175,55,0.18);
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.35);
            padding: 16px;
            text-align: center;
        }
        .feature-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px; height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, #B88A1F, #D4AF37, #F2D168);
            margin-bottom: 10px;
            box-shadow: 0 8px 18px rgba(212,175,55,0.25);
        }
        .feature-title { font-weight: 700; margin-bottom: 6px; }
        .feature-text { color: rgba(248,248,248,0.85); font-size: 0.95rem; }

        /* Footer agar tidak terlalu banyak space */
        .footer { padding: 16px 16px 24px !important; }
        .footer-glow::before { width: 280px; height: 80px; bottom: -18px; }
    </style>

    <!-- Open Graph -->
    <meta property="og:title" content="Coming Soon — Lead Magnet">
    <meta property="og:description" content="Landing page lead magnet elegan bertema gelap dengan countdown dan form pendaftaran terhubung affiliasi.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="">
    <meta property="og:image" content="data:image/webp;base64,UklGRkoAAABXRUJQVlA4WAoAAAAvAAAAEwAAEwAAQUxQSCIAAAABAAAcJQAAP8AAABQAAAAAAA=">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Coming Soon — Lead Magnet">
    <meta name="twitter:description" content="Landing page lead magnet elegan bertema gelap dengan countdown dan form pendaftaran terhubung affiliasi.">
    <meta name="twitter:image" content="data:image/webp;base64,UklGRkoAAABXRUJQVlA4WAoAAAAvAAAAEwAAEwAAQUxQSCIAAAABAAAcJQAAP8AAABQAAAAAAA=">

    <!-- Canonical (will be set to current URL via JS) -->
    <link rel="canonical" href="">

    <!-- Favicon placeholder (inline SVG as data URI) -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0' x2='1' y1='0' y2='1'%3E%3Cstop offset='0' stop-color='%23D4AF37'/%3E%3Cstop offset='1' stop-color='%23F8F8F8'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='32' height='32' rx='8' fill='%230B0B0B'/%3E%3Ccircle cx='16' cy='16' r='10' fill='url(%23g)'/%3E%3C/svg%3E">
</head>
<body>
    <!-- Komponen background animasi khusus form registrasi (mengikuti saregister) -->
    <div class="animated-background"></div>
    <header class="site-header" role="banner">
        <div class="logo" aria-label="Logo Coming Soon">
            <!-- Inline SVG logo placeholder -->
            <svg width="160" height="40" viewBox="0 0 160 40" aria-hidden="true">
                <defs>
                    <linearGradient id="lg" x1="0" x2="1">
                        <stop offset="0%" stop-color="#D4AF37" />
                        <stop offset="100%" stop-color="#F8F8F8" />
                    </linearGradient>
                </defs>
                <text x="0" y="28" font-family="'Playfair Display', serif" font-size="26" fill="url(#lg)" font-weight="700">Coming Soon</text>
            </svg>
        </div>
    </header>

    <main id="main" class="main" role="main">
        <!-- HERO -->
        <section id="hero" class="section hero" aria-label="Hero" data-animate>
            <div class="hero-bg" aria-hidden="true"></div>
            <div class="content">
                <h1 class="title">Momen yang Akan Mengubah Segalanya</h1>
                <p class="subtitle">“Sebuah Cahaya Baru Akan Lahir”</p>
                <p class="lead">
                    Di setiap zaman, ada momen yang menandai perubahan besar.
                    Kali ini, Anda akan menjadi saksi lahirnya sesuatu yang lebih dari sekadar brand —
                    Sebuah gerakan yang menggabungkan kemurnian emas 999.9, keberkahan wakaf, dan jaminan keaslian e-warrant.
                </p>
                <p class="lead">🌙 Sebuah kemilau abadi yang akan dikenang selamanya.</p>
                <div class="countdown" aria-live="polite" aria-label="Countdown ke 25 Oktober 2025 10:00 WIB">
                    <div class="countdown-label">
                        <!-- Inline SVG clock icon -->
                        <svg width="18" height="18" viewBox="0 0 24 24" role="img" aria-label="Icon Jam" class="icon">
                            <circle cx="12" cy="12" r="10" stroke="#D4AF37" stroke-width="2" fill="none"/>
                            <line x1="12" y1="12" x2="12" y2="6" stroke="#D4AF37" stroke-width="2"/>
                            <line x1="12" y1="12" x2="16" y2="12" stroke="#D4AF37" stroke-width="2"/>
                        </svg>
                        ⏰ Countdown ke 25 Oktober 2025 10:00 WIB
                    </div>
                    <div class="countdown-grid" id="countdown">
                        <div class="cd-box"><span class="cd-value" id="cd-days">0</span><span class="cd-label">hari</span></div>
                        <div class="cd-box"><span class="cd-value" id="cd-hours">0</span><span class="cd-label">jam</span></div>
                        <div class="cd-box"><span class="cd-value" id="cd-mins">0</span><span class="cd-label">menit</span></div>
                        <div class="cd-box"><span class="cd-value" id="cd-secs">0</span><span class="cd-label">detik</span></div>
                    </div>
                    <p class="cd-done" id="cd-done" hidden>Hari ini dimulai</p>
                </div>
                <div class="hero-actions">
                    <a href="#lead-form" class="btn btn-primary" id="cta-scroll" aria-label="Scroll ke form pendaftaran">Daftar Sekarang</a>
                    <button class="btn btn-outline" id="open-teaser" aria-haspopup="dialog" aria-controls="teaser-modal">
                        <!-- Inline SVG video icon -->
                        <svg width="18" height="18" viewBox="0 0 24 24" role="img" aria-label="Icon Video" class="icon">
                            <rect x="3" y="6" width="14" height="12" rx="2" ry="2" fill="none" stroke="#D4AF37" stroke-width="2"></rect>
                            <polygon points="12,12 18,9 18,15" fill="#D4AF37"></polygon>
                        </svg>
                        Lihat Teaser
                    </button>
                </div>
            </div>
        </section>

        <!-- FEATURES - ditampilkan sebagai card sederhana & terorganisir -->
        <section class="section" aria-label="Fitur Utama" data-animate>
            <h2 class="section-title">Fitur Utama</h2>
            <div class="features-grid">
                <article class="feature-card" aria-label="Emas 999.9 Original">
                    <div class="feature-icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" role="img" aria-label="Icon Bintang">
                            <polygon points="12,2 15,9 22,9 17,14 19,22 12,18 5,22 7,14 2,9 9,9" fill="#0B0B0B" />
                        </svg>
                    </div>
                    <h3 class="feature-title">Emas 999.9 Original</h3>
                    <p class="feature-text">Kemurnian tinggi, kualitas terjamin, dan nilai yang bertahan.</p>
                </article>
                <article class="feature-card" aria-label="Wakaf & Keberkahan">
                    <div class="feature-icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" role="img" aria-label="Icon Hati">
                            <path d="M12 21s-7-4.35-9.33-7.33C1.1 11.28 2.15 8.5 5 8.5c2.3 0 3.5 1.68 4 2.5.5-.82 1.7-2.5 4-2.5 2.85 0 3.9 2.78 2.33 5.17C19 16.65 12 21 12 21z" fill="#0B0B0B"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">Wakaf & Keberkahan</h3>
                    <p class="feature-text">Nilai yang melampaui aset—membawa manfaat jangka panjang.</p>
                </article>
                <article class="feature-card" aria-label="E‑Warrant Digital">
                    <div class="feature-icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" role="img" aria-label="Icon Perisai">
                            <path d="M12 2l7 4v6c0 5-3.5 9-7 10-3.5-1-7-5-7-10V6l7-4z" fill="#0B0B0B"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">E‑Warrant Digital</h3>
                    <p class="feature-text">Jaminan keaslian berbasis digital untuk ketenangan Anda.</p>
                </article>
                <article class="feature-card" aria-label="Harga Soft Launch Terbaik">
                    <div class="feature-icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" role="img" aria-label="Icon Tag">
                            <path d="M21 10l-9 11-8-8 11-9 6 6z" fill="#0B0B0B"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">Harga Soft Launch Terbaik</h3>
                    <p class="feature-text">Dapatkan info & harga paling menguntungkan saat peluncuran.</p>
                </article>
            </div>
        </section>

        <!-- SECTION 2 -->
        <section class="section" aria-label="Sebuah Awal dari Sejarah Baru" data-animate>
            <div class="visual visual-gold" aria-hidden="true">
                <!-- Placeholder image as inline SVG -->
                <svg viewBox="0 0 600 360" width="100%" height="auto" role="img" aria-label="Placeholder Foto Emas">
                    <defs>
                        <linearGradient id="goldGrad" x1="0" x2="1" y1="0" y2="1">
                            <stop offset="0%" stop-color="#0B0B0B"/>
                            <stop offset="50%" stop-color="#1a1a1a"/>
                            <stop offset="100%" stop-color="#D4AF37"/>
                        </linearGradient>
                    </defs>
                    <rect width="600" height="360" fill="url(#goldGrad)"/>
                    <rect x="140" y="90" width="320" height="180" rx="12" fill="#D4AF37" opacity="0.85"/>
                </svg>
            </div>
            <h2 class="section-title">Sebuah Awal dari Sejarah Baru</h2>
            <blockquote class="section-quote">Setiap kilau memiliki kisah.<br>Namun tidak semua membawa makna sedalam ini.</blockquote>
            <p class="section-text">
                Untuk pertama kalinya, kemurnian emas hadir
                bersama nilai keberkahan wakaf dan jaminan digital e-warrant
                yang menjadikan kepemilikan bukan sekadar aset —
                tapi warisan bernilai abadi.
            </p>
        </section>

        <!-- SECTION 3 -->
        <section class="section" aria-label="Desain yang Tak Lekang oleh Waktu" data-animate>
            <div class="visual visual-spotlight" aria-hidden="true">
                <svg viewBox="0 0 600 360" width="100%" height="auto" role="img" aria-label="Placeholder Ilustrasi Spotlight">
                    <defs>
                        <radialGradient id="spot" cx="50%" cy="40%" r="60%">
                            <stop offset="0%" stop-color="#F8F8F8"/>
                            <stop offset="60%" stop-color="#D4AF37"/>
                            <stop offset="100%" stop-color="#0B0B0B"/>
                        </radialGradient>
                    </defs>
                    <rect width="600" height="360" fill="#0B0B0B"/>
                    <circle cx="300" cy="140" r="160" fill="url(#spot)" opacity="0.35"/>
                    <rect x="170" y="110" width="260" height="140" rx="16" fill="#D4AF37" opacity="0.9"/>
                </svg>
            </div>
            <h2 class="section-title">Desain yang Tak Lekang oleh Waktu</h2>
            <p class="section-text">
                > Dibangun dengan filosofi ketulusan dan keindahan,
                setiap detailnya diciptakan dengan cita rasa tinggi —
                memadukan desain timeless, kemurnian sejati, dan tujuan luhur.
            </p>
            <p class="section-text">
                Inilah harmoni antara keindahan dunia dan nilai abadi yang tak tergantikan.
            </p>
        </section>

        <!-- SECTION 4 - FORM -->
        <section id="lead-form" class="section section-form" aria-label="Form Registrasi Free Member" data-animate>
            <div class="register-container" role="region" aria-labelledby="form-title">
                <div class="text-center mb-4">
                    <h1 class="welcome-title" id="form-title">Dapatkan Informasi & Harga Terbaik Saat Soft Launching</h1>
                </div>

                <?php
                // Tampilkan notifikasi hasil proses (error/sukses)
                if (isset($error) && !empty($error)) {
                    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">'
                        .'<strong>Error!</strong> '.htmlspecialchars($error, ENT_QUOTES, 'UTF-8')
                        .'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
                        .'</div>';
                }
                if (isset($successmsg) && !empty($successmsg)) {
                    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">'
                        .'<strong>Ok!</strong> '.($successmsg)
                        .'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
                        .'</div>';
                }
                ?>
                <!-- Placeholder alert untuk pesan peringatan instan (AJAX) tanpa reload -->
                <div id="inline-alert" class="alert alert-danger d-none" role="alert"></div>

                <form action="" method="post" id="registrasi" onsubmit="document.getElementById('formsubmit').disabled=true;document.getElementById('formsubmit').value='Tunggu sebentar...';" enctype="multipart/form-data">
                    <?php echo form_builder('register'); ?>
                    <!-- Hidden password agar validasi server tetap terpenuhi meski field password tidak ditampilkan -->
                    <input type="hidden" name="password" id="password-hidden" />
                    <input type="hidden" name="repassword" id="repassword-hidden" />
                    <?php if (isset($settings['recap_site']) && !empty($settings['recap_site'])): ?>
                        <button type="button" class="g-recaptcha btn btn-register w-100 mb-2" data-sitekey="<?= $settings['recap_site']; ?>" id="formsubmit" data-callback="onSubmit" data-action="submit"> BERGABUNG SEKARANG </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-register w-100 mb-2" id="formsubmit"> BERGABUNG SEKARANG </button>
                    <?php endif; ?>

                    <?php 
                    if (isset($datasponsor['nama'])) {
                        echo '<div class="sponsor-box">';
                        if (isset($settings['boxsponsor']) && !empty($settings['boxsponsor'])) {
                            $isibox = $settings['boxsponsor'];
                            foreach ($datasponsor as $key => $value) {
                                $isibox = str_replace('['.$key.']', ($value??=''), $isibox);
                            }
                            echo $isibox;
                        } else {
                            echo '✨ Sponsor: '.$datasponsor['nama'].' ✨';
                        }
                        echo '</div>';
                    }
                    ?>
                </form>
            </div>
        </section>

        <!-- SECTION 5 -->
        <section class="section" aria-label="Acara Launching Hybrid" data-animate>
            <div class="visual visual-hybrid" aria-hidden="true">
                <svg viewBox="0 0 600 360" width="100%" height="auto" role="img" aria-label="Placeholder Suasana Megah">
                    <defs>
                        <linearGradient id="hy" x1="0" x2="1" y1="0" y2="1">
                            <stop offset="0%" stop-color="#0B0B0B"/>
                            <stop offset="100%" stop-color="#1a1a1a"/>
                        </linearGradient>
                    </defs>
                    <rect width="600" height="360" fill="url(#hy)"/>
                    <rect x="80" y="80" width="200" height="150" fill="#D4AF37" opacity="0.25"/>
                    <rect x="320" y="70" width="200" height="160" fill="#D4AF37" opacity="0.15"/>
                    <rect x="160" y="240" width="280" height="20" fill="#D4AF37" opacity="0.2"/>
                </svg>
            </div>
            <h2 class="section-title">Acara Launching Hybrid</h2>
            <p class="section-text">
                > 📅 25 Oktober 2025<br>
                🕒 Pukul 10.00 WIB – Hybrid Event (Offline & Online)
            </p>
            <p class="section-text">
                Saksikan momen bersejarah ini, baik langsung di lokasi
                maupun dari mana pun Anda berada.
            </p>
            <p class="section-text">
                Karena sejarah besar tak hanya disaksikan —
                ia dihayati bersama.
            </p>
        </section>

        <!-- FOOTER -->
        <footer class="footer" role="contentinfo" data-animate>
            <div class="footer-glow" aria-hidden="true"></div>
            <p class="footer-quote">
                ✨ “Ada banyak momen berharga dalam hidup,
                tapi hanya sedikit yang menjadi sejarah abadi.”
            </p>
            <p class="footer-sub">
                🌟 25 Oktober 2025 — Awal dari Sebuah Era Baru.
            </p>
            <p class="footer-tags">#MenjadiSaksiSejarah #TheGoldenMovement #25Okt2025</p>
        </footer>
    </main>

    <!-- Modal Teaser -->
    <div class="modal" id="teaser-modal" role="dialog" aria-modal="true" aria-labelledby="teaser-title" aria-hidden="true" data-video-url="">
        <div class="modal-backdrop" id="modal-backdrop" tabindex="-1"></div>
        <div class="modal-content" role="document">
            <button class="modal-close" id="modal-close" aria-label="Tutup Teaser" title="Tutup">
                <svg width="18" height="18" viewBox="0 0 24 24" role="img" aria-label="Close icon">
                    <line x1="5" y1="5" x2="19" y2="19" stroke="#F8F8F8" stroke-width="2"></line>
                    <line x1="19" y1="5" x2="5" y2="19" stroke="#F8F8F8" stroke-width="2"></line>
                </svg>
            </button>
            <h3 id="teaser-title">Lihat Teaser</h3>
            <div class="modal-body" id="teaser-body">
                <!-- Video will be injected if available -->
                <div class="teaser-fallback" id="teaser-fallback">Teaser belum tersedia.</div>
            </div>
        </div>
    </div>

    <script src="<?= $weburl;?>bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script>
    // Set canonical to current URL
    (function(){
        var link = document.querySelector('link[rel=canonical]');
        if (link) {
            link.setAttribute('href', location.origin + location.pathname);
        }
    })();

    // Helper: Smooth scroll
    function smoothScrollTo(targetId) {
        var el = document.getElementById(targetId);
        if (!el) return;
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    document.getElementById('cta-scroll').addEventListener('click', function(e){
        e.preventDefault();
        smoothScrollTo('lead-form');
    });

    // Animate on view
    (function() {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view');
                }
            });
        }, { threshold: 0.15 });
        document.querySelectorAll('[data-animate]').forEach(function(sec){ observer.observe(sec); });
    })();

    // Countdown to 25 Oct 2025 10:00 WIB (UTC+7 => 03:00:00Z)
    (function(){
        var target = new Date('2025-10-25T03:00:00.000Z').getTime();
        var daysEl = document.getElementById('cd-days');
        var hoursEl = document.getElementById('cd-hours');
        var minsEl = document.getElementById('cd-mins');
        var secsEl = document.getElementById('cd-secs');
        var doneEl = document.getElementById('cd-done');
        var gridEl = document.getElementById('countdown');

        function update() {
            var now = Date.now();
            var diff = target - now;
            if (diff <= 0) {
                daysEl.textContent = '0';
                hoursEl.textContent = '0';
                minsEl.textContent = '0';
                secsEl.textContent = '0';
                gridEl.setAttribute('hidden', '');
                doneEl.removeAttribute('hidden');
                clearInterval(tid);
                return;
            }
            var d = Math.floor(diff / (1000*60*60*24));
            var h = Math.floor((diff % (1000*60*60*24)) / (1000*60*60));
            var m = Math.floor((diff % (1000*60*60)) / (1000*60));
            var s = Math.floor((diff % (1000*60)) / 1000);
            daysEl.textContent = d;
            hoursEl.textContent = h;
            minsEl.textContent = m;
            secsEl.textContent = s;
        }
        update();
        var tid = setInterval(update, 1000);
    })();

    // Modal logic
    (function(){
        var openBtn = document.getElementById('open-teaser');
        var modal = document.getElementById('teaser-modal');
        var backdrop = document.getElementById('modal-backdrop');
        var closeBtn = document.getElementById('modal-close');
        var body = document.body;
        var teaserBody = document.getElementById('teaser-body');

        function openModal(){
            modal.setAttribute('aria-hidden','false');
            modal.classList.add('open');
            body.style.overflow = 'hidden';
            // Inject video if available
            var url = modal.getAttribute('data-video-url');
            teaserBody.innerHTML = '';
            if (url && url.trim() !== '') {
                var iframe = document.createElement('iframe');
                iframe.src = url;
                iframe.title = 'Teaser Video';
                iframe.width = '560';
                iframe.height = '315';
                iframe.setAttribute('allow','accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
                iframe.setAttribute('allowfullscreen','');
                iframe.loading = 'lazy';
                teaserBody.appendChild(iframe);
            } else {
                var fallback = document.createElement('div');
                fallback.className = 'teaser-fallback';
                fallback.textContent = 'Teaser belum tersedia.';
                teaserBody.appendChild(fallback);
            }
            closeBtn.focus();
        }
        function closeModal(){
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden','true');
            body.style.overflow = '';
        }
        openBtn.addEventListener('click', openModal);
        closeBtn.addEventListener('click', closeModal);
        backdrop.addEventListener('click', closeModal);
        document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeModal(); });
    })();

    // Toggle password visibility & reCAPTCHA callback (mengikuti saregister)
    function togglePassword() {
        var passwordInput = document.getElementById("password");
        var toggleBtn = document.getElementById("togglePassword");
        if (!passwordInput || !toggleBtn) return;
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            toggleBtn.innerHTML = '<i class="fas fa-eye-slash text-secondary"></i>';
        } else {
            passwordInput.type = "password";
            toggleBtn.innerHTML = '<i class="fas fa-eye text-secondary"></i>';
        }
    }
    function onSubmit(token) { var f = document.getElementById("registrasi"); if (f) f.submit(); }
    
    // Lead magnet adjustments + AJAX validation duplikasi email/WhatsApp tanpa reload
    document.addEventListener('DOMContentLoaded', function(){
        var form = document.getElementById('registrasi');
        var btn = document.getElementById('formsubmit');
        var inlineAlert = document.getElementById('inline-alert');
        if (!form) return;

        function genPass(len){
            var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789@#$%';
            var out = '';
            for (var i=0;i<len;i++){ out += chars[Math.floor(Math.random()*chars.length)]; }
            return out;
        }
        var pw = genPass(12);
        var ph = document.getElementById('password-hidden');
        var rh = document.getElementById('repassword-hidden');
        if (ph) ph.value = pw;
        if (rh) rh.value = pw;

        // Hapus field password yang terlihat (jika ada) untuk tampilan lead magnet
        var candidates = form.querySelectorAll('input[type="password"], [name="password"], [name="repassword"], #password, #togglePassword');
        candidates.forEach(function(el){
            var group = el.closest('.mb-3, .form-group, .input-group, .row');
            if (group) { group.remove(); } else { el.remove(); }
        });

        function getValue(name){
            var el = form.querySelector('[name="'+name+'"]');
            return el ? (el.value||'').trim() : '';
        }
        function clearInline(){ if (inlineAlert) { inlineAlert.classList.add('d-none'); inlineAlert.innerHTML=''; } }
        function showInline(html){ if (inlineAlert) { inlineAlert.innerHTML = html; inlineAlert.classList.remove('d-none'); inlineAlert.scrollIntoView({behavior:'smooth', block:'center'}); } }
        function ajaxValidate(){
            var email = getValue('email');
            var whatsapp = getValue('whatsapp');
            var body = new URLSearchParams({ ajax_validate: '1', email: email, whatsapp: whatsapp });
            return fetch(location.href, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString() })
                .then(function(r){ return r.json(); });
        }

        if (btn) {
            // Intersep klik tombol untuk validasi instan tanpa reload
            btn.addEventListener('click', function(e){
                e.preventDefault();
                e.stopImmediatePropagation();
                clearInline();
                ajaxValidate().then(function(res){
                    if (!res || !res.ok) {
                        var emailErr = res && res.errors && !!res.errors.email;
                        var waErr = res && res.errors && !!res.errors.whatsapp;
                        var html = '';
                        function hasLoginLink(msg){ return (msg||'').indexOf('href="login"') !== -1; }
                        if (emailErr && waErr) {
                            html = '<strong>Perhatian!</strong> Email dan Nomor Whatsapp Anda sudah digunakan. Silakan gunakan data lainnya atau login bila ini akun Anda. <a href="login" class="text-link fw-semibold">Login sekarang</a>';
                        } else if (emailErr) {
                            var em = res.errors.email || 'Email sudah digunakan. Silakan gunakan email lain atau login bila ini akun Anda. ';
                            html = '<strong>Perhatian!</strong> ' + em + (hasLoginLink(em) ? '' : ' <a href="login" class="text-link fw-semibold">Login sekarang</a>');
                        } else if (waErr) {
                            var wm = res.errors.whatsapp || 'Nomor WhatsApp sudah terdaftar. Silakan gunakan nomor lain atau login bila ini akun Anda. ';
                            html = '<strong>Perhatian!</strong> ' + wm + (hasLoginLink(wm) ? '' : ' <a href="login" class="text-link fw-semibold">Login sekarang</a>');
                        } else {
                            html = '<strong>Perhatian!</strong> Terjadi kesalahan. Silakan periksa kembali data Anda.';
                        }
                        showInline(html);
                        // Fokuskan ke field pertama yang error
                        if (emailErr) { var eField = form.querySelector('[name="email"]'); if (eField) eField.focus(); }
                        else if (waErr) { var wField = form.querySelector('[name="whatsapp"]'); if (wField) wField.focus(); }
                        return;
                    }
                    // Lolos validasi AJAX => lanjut reCAPTCHA atau submit
                    try {
                        if (btn.classList.contains('g-recaptcha') && typeof grecaptcha !== 'undefined' && grecaptcha.execute) {
                            grecaptcha.execute(); // akan memanggil onSubmit --> form.submit();
                        } else {
                            form.submit();
                        }
                    } catch(_err) {
                        form.submit();
                    }
                }).catch(function(){
                    // Jika AJAX gagal, tetap izinkan submit normal agar validasi server berjalan
                    try {
                        if (btn.classList.contains('g-recaptcha') && typeof grecaptcha !== 'undefined' && grecaptcha.execute) {
                            grecaptcha.execute();
                        } else {
                            form.submit();
                        }
                    } catch(_err2) {
                        form.submit();
                    }
                });
            }, true); // capture true supaya handler ini jalan duluan
        }
    });

    // Sembunyikan iframe yang menempel di kanan bawah (misal badge reCAPTCHA)
    window.addEventListener('load', function(){
        var badge = document.querySelector('.grecaptcha-badge');
        if (badge) {
            badge.style.opacity = '0';
            badge.style.pointerEvents = 'none';
            badge.style.position = 'fixed';
            badge.style.bottom = '-9999px';
        }
        var iframes = document.querySelectorAll('iframe');
        var vw = window.innerWidth, vh = window.innerHeight;
        iframes.forEach(function(ifr){
            var rect = ifr.getBoundingClientRect();
            if ((vw - rect.right) < 120 && (vh - rect.bottom) < 120) {
                ifr.style.display = 'none';
            }
        });
    });
    </script>
</body>
</html>