<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($datamember['mem_role'] < 5) { die(); exit(); }
$head['pagetitle']='Manage Produk';
showheader($head);

if (isset($_POST['urlpage']) && !empty($_POST['urlpage']) && isset($_POST['judulpage']) && !empty($_POST['judulpage'])) {
    if (isset($_FILES['thumb'])) {

    // Accept up to 5MB input; optimize output to ≤500KB
    $max_input_size = 5242880; // 5MB
    $target_max_bytes = 512000; // ≈500KB
    $files = $_FILES['thumb'];
    $whitelist_ext = array('jpeg','jpg','png','gif');
    $whitelist_type = array('image/jpeg', 'image/jpg', 'image/png','image/gif');
    $pic_dir = caripath('theme').'/upload';
    
    if( ! file_exists( $pic_dir ) ) { mkdir( $pic_dir ); }
    
    $gambar = $editgambar = '';

    if (isset($files['name']) && !empty($files['name'])) {
      $filename = txtonly(strtolower($_POST['judulpage']));
      $base_target = $pic_dir.'/'.$filename; // tanpa ekstensi
      $uploadOk = 1;
      $imageFileType = strtolower(pathinfo($files["name"],PATHINFO_EXTENSION));
      if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        $txterror = "Maaf, hanya support JPG, JPEG, PNG & GIF saja.";
        $uploadOk = 0;
      }
      //Check that the file is of the right type
      if (!in_array($files["type"], $whitelist_type)) {
        $txterror = "Maaf, hanya support JPG, JPEG, PNG & GIF saja.";
        $uploadOk = 0;
      }
      // Check input file size (allow up to 5MB)
      if ($files["size"] > $max_input_size) {
        $txterror = 'Maaf, file terlalu besar. Max. 5MB';
        $uploadOk = 0;
      }
      if ($uploadOk == 1) {
        $file = $files["tmp_name"];
        $target_ext = $imageFileType; // ekstensinya dapat berubah jika konversi
        $target_file = $base_target.'.'.$target_ext;

        if (class_exists('Imagick')) {
            // Metode dengan Imagick: resize max 1600px, optimasi kualitas untuk target ≤500KB
            $maxDim = 1600;
            $quality = 85; // initial quality
            $minQuality = 50;
            $attempts = 0;
            $okWrite = false;

            // Fungsi tulis dengan parameter saat ini
            $writeOptimized = function($srcFile, $outFile, $ext, $maxDim, $quality){
              $class = 'Imagick';
              $im = new $class();
              $im->readImage($srcFile);
              // Flatten transparansi ke putih agar ukuran lebih kecil saat JPEG
              $im->setImageBackgroundColor('white');
              $mergeConst = defined('Imagick::LAYERMETHOD_FLATTEN') ? constant('Imagick::LAYERMETHOD_FLATTEN') : 1;
              $im->mergeImageLayers($mergeConst);
              // Resize dengan batas maksimal
              $filterConst = defined('Imagick::FILTER_CATROM') ? constant('Imagick::FILTER_CATROM') : 1;
              $im->resizeImage($maxDim, $maxDim, $filterConst, 1, true);
              $im->stripImage();
              if ($ext == 'jpg' || $ext == 'jpeg') {
                $jpegConst = defined('Imagick::COMPRESSION_JPEG') ? constant('Imagick::COMPRESSION_JPEG') : 1;
                $im->setImageCompression($jpegConst);
                $im->setImageCompressionQuality($quality);
                $im->setImageFormat('jpeg');
              } elseif ($ext == 'png') {
                $im->setImageFormat('png');
                $zipConst = defined('Imagick::COMPRESSION_ZIP') ? constant('Imagick::COMPRESSION_ZIP') : 1;
                $im->setImageCompression($zipConst);
                $im->setImageCompressionQuality(9); // level kompresi
              } else {
                $im->setImageFormat($ext);
              }
              $im->writeImage($outFile);
              $im->clear();
              $im->destroy();
              return file_exists($outFile) ? filesize($outFile) : 0;
            };

            do {
              // Tulis file sesuai parameter saat ini
              $finalSize = $writeOptimized($file, $target_file, $target_ext, $maxDim, $quality);
              if ($finalSize > 0 && $finalSize <= $target_max_bytes) { $okWrite = true; break; }

              // Jika PNG/GIF masih besar, coba konversi ke JPEG untuk penghematan
              if (($target_ext == 'png' || $target_ext == 'gif') && $finalSize > $target_max_bytes) {
                $target_ext = 'jpg';
                $target_file = $base_target.'.jpg';
                // Tulis ulang sebagai JPEG
                $finalSize = $writeOptimized($file, $target_file, $target_ext, $maxDim, $quality);
                if ($finalSize > 0 && $finalSize <= $target_max_bytes) { $okWrite = true; break; }
              }

              // Kurangi kualitas terlebih dahulu, lalu dimensi
              if ($quality > $minQuality) {
                $quality -= 10;
              } else {
                $maxDim = max(800, (int)($maxDim * 0.85));
                $quality = 85; // reset kualitas ketika menurunkan dimensi
              }
              $attempts++;
            } while ($attempts < 8);

            if (!$okWrite) {
              // Gagal mencapai target ≤500KB
              if (file_exists($target_file)) { @unlink($target_file); }
              $txterror = 'Maaf, gambar terlalu besar setelah optimasi. Gunakan JPEG resolusi lebih kecil (≤500KB).';
              $uploadOk = 0;
            }
        } else {
            // Metode alternatif tanpa Imagick
            // Jika fungsi GD tersedia, gunakan GD untuk resize dan optimasi kualitas JPEG
            if (function_exists('imagecreatefromstring') && function_exists('imagecreatetruecolor') && function_exists('imagecopyresampled')) {
                $img = imagecreatefromstring(file_get_contents($file));
                if ($img === false) {
                    // Jika gagal membuat image dari string, fallback copy tanpa resize
                    if (!move_uploaded_file($file, $target_file)) {
                        $txterror = 'Gagal menyimpan gambar.';
                        $uploadOk = 0;
                    }
                } else {
                    $width = imagesx($img);
                    $height = imagesy($img);
                    $maxDim = 1600;
                    $ratio = $width / $height;
                    if ($width >= $height) {
                      $new_width = $maxDim;
                      $new_height = (int)round($maxDim / $ratio);
                    } else {
                      $new_height = $maxDim;
                      $new_width = (int)round($maxDim * $ratio);
                    }

                    // Resize image
                    $tmp_img = imagecreatetruecolor($new_width, $new_height);
                    imagecopyresampled($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

                    $okWrite = false;
                    $quality = 85;
                    $minQuality = 50;
                    $attempts = 0;

                    do {
                      if ($target_ext == 'jpg' || $target_ext == 'jpeg') {
                        imagejpeg($tmp_img, $target_file, $quality);
                      } elseif ($target_ext == 'png') {
                        imagepng($tmp_img, $target_file, 6);
                      } elseif ($target_ext == 'gif') {
                        imagegif($tmp_img, $target_file);
                      }
                      $finalSize = file_exists($target_file) ? filesize($target_file) : 0;
                      if ($finalSize > 0 && $finalSize <= $target_max_bytes) { $okWrite = true; break; }

                      // Jika PNG/GIF masih besar, konversi ke JPEG dengan latar putih
                      if (($target_ext == 'png' || $target_ext == 'gif') && $finalSize > $target_max_bytes) {
                        $target_ext = 'jpg';
                        $target_file = $base_target.'.jpg';
                        imagejpeg($tmp_img, $target_file, $quality);
                        $finalSize = file_exists($target_file) ? filesize($target_file) : 0;
                        if ($finalSize > 0 && $finalSize <= $target_max_bytes) { $okWrite = true; break; }
                      }

                      if ($quality > $minQuality) {
                        $quality -= 10;
                      } else {
                        // Turunkan dimensi jika tetap besar
                        $maxDim = max(800, (int)($maxDim * 0.85));
                        if ($width >= $height) {
                          $new_width = $maxDim;
                          $new_height = (int)round($maxDim / $ratio);
                        } else {
                          $new_height = $maxDim;
                          $new_width = (int)round($maxDim * $ratio);
                        }
                        imagedestroy($tmp_img);
                        $tmp_img = imagecreatetruecolor($new_width, $new_height);
                        imagecopyresampled($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                        $quality = 85;
                      }
                      $attempts++;
                    } while ($attempts < 8);

                    if (!$okWrite) {
                      if (file_exists($target_file)) { @unlink($target_file); }
                      $txterror = 'Maaf, gambar terlalu besar setelah optimasi. Gunakan JPEG resolusi lebih kecil (≤500KB).';
                      $uploadOk = 0;
                    }

                    imagedestroy($img);
                    imagedestroy($tmp_img);
                }
            } else {
                // GD tidak tersedia, simpan file apa adanya tanpa resize
                if (!move_uploaded_file($file, $target_file)) {
                    $txterror = 'Gagal menyimpan gambar. Modul GD tidak tersedia.';
                    $uploadOk = 0;
                }
                // Verifikasi ukuran akhir ≤500KB
                if ($uploadOk == 1) {
                  $finalSize = file_exists($target_file) ? filesize($target_file) : 0;
                  if ($finalSize <= 0 || $finalSize > $target_max_bytes) {
                    if (file_exists($target_file)) { @unlink($target_file); }
                    $txterror = 'Maaf, gambar terlalu besar. Pastikan ukuran akhir ≤500KB.';
                    $uploadOk = 0;
                  }
                }
            }
        }
            
        if ($uploadOk == 1) {
            $gambar = $filename.'.'.$target_ext;
            $editgambar = ",`pro_img`='".$gambar."'";
        } else {
          echo '
          <div class="alert alert-danger alert-dismissible fade show" id="peringatan">
            <strong>Error!</strong> '.$txterror.'
            <button type="button" class="btn-close" id="tutup"></button>
          </div>';
        }
      }
    }
  }

	// Komisi Pereferral: satu level (Level-1) dengan opsi persentase atau nilai tetap
	$komisiType = (isset($_POST['komisi_type']) && in_array($_POST['komisi_type'], array('percent','fixed'))) ? $_POST['komisi_type'] : 'fixed';
	$premiumVal = isset($_POST['komisi']['premium'][1]) ? numonly($_POST['komisi']['premium'][1]) : 0;
	$freeVal    = isset($_POST['komisi']['free'][1]) ? numonly($_POST['komisi']['free'][1]) : 0;

	// Validasi input komisi
	$validationMsg = '';
	if ($komisiType === 'percent') {
		if ($premiumVal < 0 || $premiumVal > 100) { $validationMsg .= 'Persentase komisi Premium harus di antara 0-100%. '; $premiumVal = max(0, min(100, $premiumVal)); }
		if ($freeVal < 0 || $freeVal > 100) { $validationMsg .= 'Persentase komisi Free harus di antara 0-100%. '; $freeVal = max(0, min(100, $freeVal)); }
	} else { // fixed rupiah
		if ($premiumVal < 0) { $validationMsg .= 'Nilai komisi Premium (Rp) tidak boleh negatif. '; $premiumVal = max(0, $premiumVal); }
		if ($freeVal < 0) { $validationMsg .= 'Nilai komisi Free (Rp) tidak boleh negatif. '; $freeVal = max(0, $freeVal); }
	}

	// Simpan struktur komisi dengan tipe
	$komisi = array(
		'type' => $komisiType, // 'percent' atau 'fixed'
		'premium' => array( 1 => $premiumVal ),
		'free'    => array( 1 => $freeVal )
	);
	$dbkomisi = serialize($komisi);

	if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
		# Edit Page
		$cek = db_query("UPDATE `sa_page` SET 
			`page_judul` = '".cek($_POST['judulpage'])."',
			`page_diskripsi` = '".cek($_POST['diskripsipage'])."',
			`page_url` = '".cekurlpage($_POST['urlpage'],$_GET['edit'])."',
			`page_iframe` = '".cek($_POST['iframe'])."',
			`page_method`= ".cek($_POST['metodelp']).", 			
			`pro_harga` = ".numonly($_POST['harga']).",
			`pro_harga_display` = ".(isset($_POST['harga_tampil']) && $_POST['harga_tampil'] !== '' ? numonly($_POST['harga_tampil']) : numonly($_POST['harga'])).",
			`pro_auto_approve` = 0,
			`pro_komisi` = '".$dbkomisi."',
			`pro_file` = '".cek($_POST['namafile'])."',
			`page_fr` = '".serialize($_POST['fr'])."'
			".$editgambar."
			WHERE `page_id`=".$_GET['edit']);
	} else {
		# Simpan di database
		$cek = db_query("INSERT INTO `sa_page` (`page_judul`,`page_diskripsi`,`page_url`,`page_iframe`,`page_method`,`pro_harga`,`pro_harga_display`,`pro_free_access`,`pro_auto_approve`,`pro_komisi`,`pro_file`,`pro_img`,`page_fr`) VALUES 
			('".cek($_POST['judulpage'])."','".cek($_POST['diskripsipage'])."','".cekurlpage($_POST['urlpage'])."','".cek($_POST['iframe'])."',".cek($_POST['metodelp']).",	".numonly($_POST['harga']).", ".(isset($_POST['harga_tampil']) && $_POST['harga_tampil'] !== '' ? numonly($_POST['harga_tampil']) : numonly($_POST['harga'])).", ".(isset($_POST['free_access']) ? 1 : 0).", ".(isset($_POST['auto_approve']) ? 1 : 0).",'".$dbkomisi."','".cek($_POST['namafile'])."','".$gambar."','".serialize($_POST['fr'])."')");
	}


	if ($cek === false) {
		echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
			<strong>Error!</strong> '.db_error().'
			<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	} else {
	    $pid = (isset($_GET['edit']) && is_numeric($_GET['edit'])) ? (int)$_GET['edit'] : 0;
	    if ($pid <= 0) { $pid = (int)db_var("SELECT `page_id` FROM `sa_page` WHERE `page_url`='".cek($_POST['urlpage'])."' LIMIT 1"); }
    $cats = isset($_POST['category_ids']) ? array_filter(array_map('intval', preg_split('/[,\s]+/', $_POST['category_ids']))) : array();
    if ($pid > 0) { set_product_categories($pid, $cats); }
    // Simpan/validasi Multi Kontributor (Premium saja)
    $contribIdsCsv = isset($_POST['contrib_ids']) ? trim($_POST['contrib_ids']) : '';
    // Default (global) Komisi Kontributor
    $contribTypeDefault = (isset($_POST['contrib_type']) && !is_array($_POST['contrib_type']) && in_array($_POST['contrib_type'], array('percent','fixed'))) ? $_POST['contrib_type'] : 'fixed';
    $contribValueDefault = (isset($_POST['contrib_value']) && !is_array($_POST['contrib_value'])) ? numonly($_POST['contrib_value']) : 0;
    // Per-kontributor override
    $contribTypesArr = (isset($_POST['contrib_type']) && is_array($_POST['contrib_type'])) ? $_POST['contrib_type'] : array();
    $contribValuesArr = (isset($_POST['contrib_value']) && is_array($_POST['contrib_value'])) ? $_POST['contrib_value'] : array();
    $warnContrib = '';
    if ($contribTypeDefault === 'percent') {
      if ($contribValueDefault < 0 || $contribValueDefault > 100) { $warnContrib .= 'Persentase komisi Kontributor (default) harus di antara 0-100%. '; $contribValueDefault = max(0, min(100, $contribValueDefault)); }
    } else {
      if ($contribValueDefault < 0) { $warnContrib .= 'Nilai komisi Kontributor (default, Rp) tidak boleh negatif. '; $contribValueDefault = max(0, $contribValueDefault); }
    }

    if (!empty($contribIdsCsv) && $pid > 0) {
      $ids = array_unique(array_filter(array_map('intval', explode(',', $contribIdsCsv))));
      $validIds = array();
      foreach ($ids as $cid) {
        if ($cid > 0) {
          $st = db_row("SELECT `mem_status`,`mem_nama` FROM `sa_member` WHERE `mem_id`=".$cid);
          if ($st && (int)$st['mem_status'] >= 2) { $validIds[] = $cid; }
          else { $warnContrib .= 'Member ID '.$cid.' bukan Premium dan diabaikan. '; }
        }
      }
      // Audit: data sebelumnya
      $prev = db_select("SELECT `member_id` FROM `epi_product_contrib` WHERE `page_id`=".$pid);
      $prevCsv = '';
      if (is_array($prev) && count($prev)>0) { $prevCsv = implode(',', array_map(function($r){ return (int)$r['member_id']; }, $prev)); }
      // Hapus semua kontributor lama untuk page ini, lalu insert baru (replace-set)
      db_query("DELETE FROM `epi_product_contrib` WHERE `page_id`=".$pid);

      // Deteksi kolom yang tersedia pada tabel epi_product_contrib
      $pcCols = db_select("SHOW COLUMNS FROM `epi_product_contrib`");
      $pcColNames = array();
      if (is_array($pcCols)) { foreach ($pcCols as $c) { if (isset($c['Field'])) { $pcColNames[] = $c['Field']; } } }
      foreach ($validIds as $cid) {
        // Tentukan type/value per kontributor (override jika tersedia, jika tidak gunakan default)
        $type = (isset($contribTypesArr[$cid]) && in_array($contribTypesArr[$cid], array('percent','fixed'))) ? $contribTypesArr[$cid] : $contribTypeDefault;
        $valRaw = isset($contribValuesArr[$cid]) ? $contribValuesArr[$cid] : $contribValueDefault;
        $val = numonly($valRaw);
        if ($type === 'percent') {
          if ($val < 0 || $val > 100) { $warnContrib .= 'Persentase kontributor ID '.$cid.' disesuaikan ke 0-100. '; $val = max(0, min(100, $val)); }
        } else {
          if ($val < 0) { $warnContrib .= 'Nilai kontributor ID '.$cid.' tidak boleh negatif. Diset = 0. '; $val = max(0, $val); }
        }
        // Build INSERT dinamis sesuai kolom yang tersedia
        $fields = array('page_id','member_id','type','value');
        $values = array($pid, $cid, "'".$type."'", $val);
        if (in_array('created_at',$pcColNames)) { $fields[]='created_at'; $values[]="'".date('Y-m-d H:i:s')."'"; }
        if (in_array('updated_at',$pcColNames)) { $fields[]='updated_at'; $values[]="'".date('Y-m-d H:i:s')."'"; }
        $ok2 = db_query("INSERT INTO `epi_product_contrib` (`".implode('`,`',$fields)."`) VALUES (".implode(',',$values).")");
        if ($ok2 === false) { $warnContrib .= 'Gagal menyimpan kontributor ID '.$cid.': '.db_error().' '; }
      }
      // Audit log: cek keberadaan tabel/kolom untuk mencegah error fatal
      $actor = isset($iduser) ? (int)$iduser : 0;
      $newCsv = implode(',', $validIds);
      $hasTable = db_select("SHOW TABLES LIKE 'epi_contrib_audit'");
      if (is_array($hasTable) && count($hasTable) > 0) {
        // Ambil seluruh kolom tabel audit
        $audCols = db_select("SHOW COLUMNS FROM `epi_contrib_audit`");
        $audColNames = array(); if (is_array($audCols)) { foreach ($audCols as $c) { if (isset($c['Field'])) { $audColNames[] = $c['Field']; } } }
        // Siapkan field/value sesuai kolom yang ada
        $af = array(); $av = array();
        if (in_array('page_id',$audColNames))  { $af[]='page_id';  $av[]=$pid; }
        if (in_array('actor_id',$audColNames)) { $af[]='actor_id'; $av[]=$actor; }
        if (in_array('action',$audColNames))   { $af[]='action';   $av[]="'replace'"; }
        if (in_array('prev_data',$audColNames)) { $af[]='prev_data'; $av[]="'".addslashes($prevCsv)."'"; }
        if (in_array('new_data',$audColNames))  { $af[]='new_data';  $av[]="'".addslashes($newCsv)."'"; }
        if (in_array('created_at',$audColNames)) { $af[]='created_at'; $av[]="'".date('Y-m-d H:i:s')."'"; }
        if (count($af) > 0) {
          db_query("INSERT INTO `epi_contrib_audit` (`".implode('`,`',$af)."`) VALUES (".implode(',',$av).")");
        }
      }
    }
	    // Tampilkan peringatan validasi jika ada
	    if (!empty($validationMsg)) {
	      echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
	        <strong>Validasi:</strong> '.$validationMsg.' Nilai telah disesuaikan otomatis.
	        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
	      </div>';
	    }

	    // Migrate tables for Product Benefits if not exists
	    db_query("CREATE TABLE IF NOT EXISTS `epi_product_benefit` (
	      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	      `page_id` INT UNSIGNED NOT NULL,
	      `label` VARCHAR(255) NOT NULL,
	      `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
	      `is_active` TINYINT(1) NOT NULL DEFAULT 1,
	      `created_at` DATETIME NULL,
	      `updated_at` DATETIME NULL,
	      PRIMARY KEY (`id`),
	      KEY `idx_page` (`page_id`)
	    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
	    db_query("CREATE TABLE IF NOT EXISTS `epi_product_benefit_settings` (
	      `page_id` INT UNSIGNED NOT NULL,
	      `show_benefit` TINYINT(1) NOT NULL DEFAULT 0,
	      `updated_at` DATETIME NULL,
	      PRIMARY KEY (`page_id`)
	    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

	    // Save benefit settings and items
	    if ($pid > 0) {
	      $show = (isset($_POST['benefit_show']) ? 1 : 0);
	      $now = date('Y-m-d H:i:s');
	      // Upsert settings
	      $exists = db_var("SELECT COUNT(*) FROM `epi_product_benefit_settings` WHERE `page_id`=".$pid);
	      if ((int)$exists > 0) {
	        db_query("UPDATE `epi_product_benefit_settings` SET `show_benefit`=".$show.", `updated_at`='".$now."' WHERE `page_id`=".$pid);
	      } else {
	        db_query("INSERT INTO `epi_product_benefit_settings` (`page_id`,`show_benefit`,`updated_at`) VALUES (".$pid.",".$show.",'".$now."')");
	      }
	      // Replace items
	      db_query("DELETE FROM `epi_product_benefit` WHERE `page_id`=".$pid);
	      $labels = isset($_POST['benefit_items']) ? $_POST['benefit_items'] : array();
	      $sorts  = isset($_POST['benefit_sort']) ? $_POST['benefit_sort'] : array();
	      $actives = isset($_POST['benefit_active_state']) ? $_POST['benefit_active_state'] : array();
	      if (is_array($labels) && count($labels) > 0) {
	        $n = count($labels);
	        for ($i=0; $i<$n; $i++) {
	          $label = trim(strip_tags($labels[$i]));
	          if ($label === '') { continue; }
	          if (mb_strlen($label) > 160) { $label = mb_substr($label, 0, 160); }
	          $ord = isset($sorts[$i]) ? (int)$sorts[$i] : 0;
	          $act = (isset($actives[$i]) && (int)$actives[$i] === 1) ? 1 : 0;
	          db_query("INSERT INTO `epi_product_benefit` (`page_id`,`label`,`sort_order`,`is_active`,`created_at`,`updated_at`) VALUES (".$pid.", '".cek($label)."', ".$ord.", ".$act.", '".$now."', '".$now."')");
	        }
	      }
	    }
	    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
	      <strong>Ok!</strong> Produk telah disimpan.
	      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
	    </div>';
	    if (!empty($warnContrib)) {
      echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>Kontributor:</strong> '.htmlspecialchars($warnContrib).'
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>';
    }
	}
}

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
	$page = db_row("SELECT * FROM `sa_page` WHERE `page_id`=".$_GET['edit']);
}
$pidInit = (isset($_GET['edit']) && is_numeric($_GET['edit'])) ? (int)$_GET['edit'] : 0;
$contribInit = array();
$contribIdsCsvInit = '';
if ($pidInit > 0) {
	$tmp = db_select("SELECT c.`member_id`, c.`type`, c.`value`, m.`mem_nama`, m.`mem_email`, m.`mem_whatsapp` FROM `epi_product_contrib` c LEFT JOIN `sa_member` m ON m.`mem_id`=c.`member_id` WHERE c.`page_id`=".$pidInit);
	if (is_array($tmp) && count($tmp)>0) {
		$contribInit = $tmp;
		$contribIdsCsvInit = implode(',', array_map(function($r){ return (int)$r['member_id']; }, $tmp));
	}
}
?>

<form action="" method="post" enctype="multipart/form-data">
<a name="form"></a>
<div class="card">
  <div class="card-header">
     Tambah Produk
  </div>
  <div class="card-body">
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Nama Produk</label>
	    <div class="col-sm-10">
	      <input type="text" class="form-control" name="judulpage" value="<?= $page['page_judul'] ??= '';?>" required>
	    </div>
	  </div>
  <div class="mb-3 row">
    <label class="col-sm-2 col-form-label">Harga Produk</label>
    <div class="col-sm-10">
      <input type="number" class="form-control" name="harga" value="<?= $page['pro_harga'] ??= '';?>" required>
    </div>
  </div>
  <div class="mb-3 row">
    <label class="col-sm-2 col-form-label">Harga Promo</label>
    <div class="col-sm-10">
      <input type="number" class="form-control" name="harga_tampil" value="<?= (isset($page['pro_harga_display']) && $page['pro_harga_display'] !== '') ? $page['pro_harga_display'] : ($page['pro_harga'] ?? ''); ?>">
      <small class="form-text text-muted">Opsional. Harga yang akan digunakan pada proses pembayaran</small>
    </div>
  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Nama File / URL Akses</label>
	    <div class="col-sm-10">
	      <input type="text" class="form-control" name="namafile" value="<?= $page['pro_file'] ??= '';?>" required>
	    </div>
	  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Diskripsi Produk</label>
	    <div class="col-sm-10">
	      <textarea class="form-control" rows="3" name="diskripsipage"><?= $page['page_diskripsi'] ??= '';?></textarea>
	    </div>
	  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">URL Produk</label>
	    <div class="col-sm-10">
	      <div class="input-group">
			    <span class="input-group-text" id="basic-addon3"><?= $weburl.$datamember['mem_kodeaff'];?>/</span>
			    <input type="text" class="form-control" name="urlpage" value="<?= $page['page_url'] ??= '';?>" required>
			  </div>
	    </div>
	  </div>	  
  <div class="mb-3 row">
    <label class="col-sm-2 col-form-label">URL Sales Page</label>
    <div class="col-sm-10">
      <input type="text" class="form-control" name="iframe" value="<?= $page['page_iframe'] ??= 'https://';?>" required>
    </div>
  </div>
  <!-- Section Kontributor (setelah URL Sales Page) -->
  <div class="mb-3 row">
    <label class="col-sm-2 col-form-label">Kontributor</label>
    <div class="col-sm-10">
      <div class="position-relative">
        <div class="input-group">
          <span class="input-group-text">Cari</span>
          <input type="text" class="form-control" id="contribSearch" placeholder="Ketik min. 4 karakter untuk cari kontributor Premium (nama/email/WA)" autocomplete="off">
          <button class="btn btn-outline-secondary" type="button" id="clearContrib">Clear</button>
        </div>
        <div id="contribResult" class="dropdown-menu w-100 shadow-sm" role="listbox" aria-expanded="false" style="max-height: 260px; overflow:auto;"></div>
      </div>
      <small class="form-text text-muted">Pilih satu atau beberapa kontributor. Hanya member Premium yang dapat dipilih.</small>
      <div class="mt-2 d-flex flex-wrap" id="contribSelected">
        <?php if (!empty($contribInit)) { foreach ($contribInit as $ci) { $labelBase = ($ci['mem_nama']?:'').( ($ci['mem_email']||$ci['mem_whatsapp']) ? ' ('.($ci['mem_email']?:$ci['mem_whatsapp']).')' : '' ); $summary = ($ci['type']==='percent') ? ( (int)$ci['value'].'%' ) : ('Rp '.number_format((int)$ci['value']) ); $label = htmlspecialchars($labelBase.' • '.$summary); echo '<span class="badge bg-primary me-2 mb-2" data-id="'.(int)$ci['member_id'].'" data-label="'.$label.'">'.$label.'<button type="button" class="btn btn-sm btn-light ms-1">×</button><button type="button" class="btn btn-sm btn-outline-secondary ms-1" data-edit-id="'.(int)$ci['member_id'].'">Edit</button></span>'; } } ?>
      </div>
      <div class="mt-2" id="contribConfig">
        <?php if (!empty($contribInit)) { foreach ($contribInit as $ci) { $label = htmlspecialchars(($ci['mem_nama']?:'').( ($ci['mem_email']||$ci['mem_whatsapp']) ? ' ('.($ci['mem_email']?:$ci['mem_whatsapp']).')' : '' )); $cid=(int)$ci['member_id']; $t=$ci['type']; $v=(int)$ci['value']; echo '<div class="row g-2 align-items-center mb-2" data-config-id="'.$cid.'"><div class="col-12 col-md-5"><input type="text" class="form-control" value="'.$label.'" disabled></div><div class="col-6 col-md-3"><select class="form-select" name="contrib_type['.$cid.']" title="Jenis"><option value="percent"'.($t==='percent'?' selected':'').'>Persentase (%)</option><option value="fixed"'.($t==='fixed'?' selected':'').'>Nilai Tetap (Rp)</option></select></div><div class="col-6 col-md-3"><input type="number" class="form-control" name="contrib_value['.$cid.']" min="0" placeholder="0" value="'.$v.'" title="Nilai"></div></div>'; } } ?>
      </div>
      <input type="hidden" name="contrib_ids" id="contribIds" value="<?= htmlspecialchars($contribIdsCsvInit) ?>">
    </div>
  </div>
  <!-- Kategori Produk dihapus sesuai permintaan -->
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Metode</label>
	    <div class="col-sm-5">
	    	<?php
	    	$metode = array(
	    		1 => 'Gunakan iFrame',
	    		2 => 'Inject URL',
	    		3 => 'Redirect URL'
	    	);

	    	$metode = apply_filter('page_metode_lp',$metode);

	    	echo '<select name="metodelp" id="metodelp" class="form-select">';
	    	foreach ($metode as $key => $value) {
	    		echo '<option value="'.$key.'"';
	    		if (isset($page['page_method']) && $page['page_method'] == $key) {
	    			echo ' selected';
	    		}
	    		echo '>'.$value.'</option>';
	    	}
	    	echo '</select>';
	    	?>					    	
	    </div>
	  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Find and Replace</label>
	    <div class="col-sm-10">
	    	<?php 
	    	if (isset($page['page_fr']) && !empty($page['page_fr'])) {
	    		$fr = unserialize($page['page_fr']);
	    	}
	    	for ($i=1; $i <= 5; $i++) :?>
	    	<div class="input-group">
	      	<input type="text" class="form-control" placeholder="find" name="fr[<?= $i;?>][find]" value="<?= $fr[$i]['find'] ??= '';?>">
	      	<input type="text" class="form-control" placeholder="replace" name="fr[<?= $i;?>][replace]" value="<?= $fr[$i]['replace'] ??= '';?>">
	      </div>
	    	<?php endfor; ?>
	      <small class="form-text text-muted">Ubah text landing page (hanya berlaku untuk metode Inject URL)</small>
	    </div>
	  </div>  
	  <div class="mb-3 row">
      <label class="col-sm-2 col-form-label">Thumbnail</label>
      <div class="col-sm-10">
        <input type="file" class="form-control" name="thumb" >
        <small class="form-text text-muted">Rekomendasi ukuran: 200 x 200 pixel</small>
        <div class="mt-2" id="previewthumb">
          <?php 
          if (isset($page['pro_img']) && $page['pro_img'] != '') {
            echo '<img src="'.$weburl.'upload/'.$page['pro_img'].'?id='.rand(100,999).'" class="img-fluid img-thumbnail" style="max-width: 200px">';
          }
          ?>
        </div>
      </div>
    </div>
    <!-- Opsi Akses (alert) dihapus sesuai permintaan -->
	  <?php 
	  if (isset($page['pro_komisi']) && !empty($page['pro_komisi'])) {
	  	$komisi = unserialize($page['pro_komisi']);
	  }
	  ?>
  <!-- Komisi Pereferral (Level 1) dengan pilihan tipe -->
  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Komisi Pereferral</label>
	    <div class="col-sm-10">
	      <div class="row g-2 align-items-center">
        <div class="col-12 col-md-4">
          <div class="input-group">
            <span class="input-group-text">Jenis</span>
            <?php $komisiTypeView = isset($komisi['type']) ? $komisi['type'] : 'fixed'; ?>
            <select class="form-select" name="komisi_type" id="komisiTypeSelect" title="Pilih jenis komisinya nilai tetap (Rp) atau Persentase (%)">
              <option value="percent" <?= ($komisiTypeView==='percent'?'selected':'') ?>>Persentase (%)</option>
              <option value="fixed" <?= ($komisiTypeView==='fixed'?'selected':'') ?>>Nilai Tetap (Rp)</option>
            </select>
          </div>
          <small class="form-text text-muted">Pilih jenis komisinya nilai tetap (Rp) atau Persentase (%).</small>
        </div>
	        <div class="col-12 col-md-4">
	          <div class="input-group">
	            <span class="input-group-text">Premium</span>
	            <input type="number" class="form-control" name="komisi[premium][1]" id="komisiPremium" value="<?= isset($komisi['premium'][1]) ? htmlspecialchars($komisi['premium'][1]) : '';?>" placeholder="0" min="0" <?= ($komisiTypeView==='percent'?'max="100"':'')?> aria-describedby="helpKomisiPremium">
	          </div>
	          <small id="helpKomisiPremium" class="form-text text-muted">Isi angka persentase (0-100) atau rupiah ≥ 0 sesuai jenis komisi.</small>
	        </div>
	        <div class="col-12 col-md-4">
	          <div class="input-group">
	            <span class="input-group-text">Free</span>
	            <input type="number" class="form-control" name="komisi[free][1]" id="komisiFree" value="<?= isset($komisi['free'][1]) ? htmlspecialchars($komisi['free'][1]) : '';?>" placeholder="0" min="0" <?= ($komisiTypeView==='percent'?'max="100"':'')?> aria-describedby="helpKomisiFree">
	          </div>
	          <small id="helpKomisiFree" class="form-text text-muted">Isi angka persentase (0-100) atau rupiah ≥ 0 sesuai jenis komisi.</small>
	        </div>
      </div>
  </div>
  </div>
  <!-- Komisi Kontributor (tipe dan nilai, berlaku untuk semua kontributor terpilih) -->

  <?php 
    $benefitShow = 0; $benefitItems = array();
    db_query("CREATE TABLE IF NOT EXISTS `epi_product_benefit` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `page_id` INT UNSIGNED NOT NULL,
      `label` VARCHAR(255) NOT NULL,
      `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
      `is_active` TINYINT(1) NOT NULL DEFAULT 1,
      `created_at` DATETIME NULL,
      `updated_at` DATETIME NULL,
      PRIMARY KEY (`id`),
      KEY `idx_page` (`page_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    db_query("CREATE TABLE IF NOT EXISTS `epi_product_benefit_settings` (
      `page_id` INT UNSIGNED NOT NULL,
      `show_benefit` TINYINT(1) NOT NULL DEFAULT 0,
      `updated_at` DATETIME NULL,
      PRIMARY KEY (`page_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    if ($pidInit > 0) {
      $benefitShow = (int)db_var("SELECT `show_benefit` FROM `epi_product_benefit_settings` WHERE `page_id`=".$pidInit);
      $rows = db_select("SELECT `id`,`label`,`sort_order`,`is_active` FROM `epi_product_benefit` WHERE `page_id`=".$pidInit." ORDER BY `sort_order` ASC, `id` ASC");
      if (is_array($rows)) { $benefitItems = $rows; }
    }
  ?>
  <div class="mb-3 row">
    <label class="col-sm-2 col-form-label">Informasi Benefit</label>
    <div class="col-sm-10">
      <div class="form-check form-switch mb-2">
        <input class="form-check-input" type="checkbox" name="benefit_show" id="benefitShow" <?= ($benefitShow===1? 'checked':'') ?>>
        <label class="form-check-label" for="benefitShow">Tampilkan bagian ini di halaman order</label>
      </div>
      <div id="benefitEditor" class="border rounded p-2">
        <div class="text-end mb-2">
          <button type="button" class="btn btn-sm btn-outline-primary text-nowrap" id="addBenefit"><i class="fa-solid fa-plus"></i> Tambah</button>
        </div>
        <div id="benefitList">
          <?php if (!empty($benefitItems)) { foreach ($benefitItems as $bi) { ?>
            <div class="row g-2 align-items-center mb-2">
              <div class="col-12 col-md-8">
                <input type="text" name="benefit_items[]" class="form-control" value="<?= htmlspecialchars($bi['label']) ?>" maxlength="160" placeholder="Contoh: Akses EPIC Hub + materi EPI Academy" required>
              </div>
              <div class="col-6 col-md-2">
                <input type="number" name="benefit_sort[]" class="form-control" value="<?= (int)$bi['sort_order'] ?>" min="0" title="Urutan">
              </div>
              <div class="col-6 col-md-2 d-flex align-items-center gap-2">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" value="1" <?= ((int)$bi['is_active']===1?'checked':'') ?> data-active-sync>
                  <label class="form-check-label">Aktif</label>
                </div>
                <input type="hidden" name="benefit_active_state[]" value="<?= ((int)$bi['is_active']===1?'1':'0') ?>">
                <button type="button" class="btn btn-sm btn-outline-danger text-nowrap removeBenefit" aria-label="Hapus"><i class="fa-solid fa-trash-can"></i></button>
              </div>
            </div>
          <?php } } ?>
        </div>
      </div>
      
    </div>
  </div>

  <input type="submit" class="btn btn-success" name="" value=" SIMPAN ">
  </div>
</div>
</form>
<script>
// Desktop alignment: pastikan select sejajar dengan input-group-text
(function(){
  // Tidak perlu JS untuk alignment karena Bootstrap 5 sudah mengatur tinggi elemen dalam input-group.
  // Tooltip: gunakan attribute title pada select, sudah ditambahkan.
})();

// Minimal search kontributor Premium
(function(){
  const elSearch = document.getElementById('contribSearch');
  const elRes = document.getElementById('contribResult');
  const elIds = document.getElementById('contribIds');
  const elSelected = document.getElementById('contribSelected');
  const elConfig = document.getElementById('contribConfig');
  const btnClear = document.getElementById('clearContrib');
  let lastController = null;
  let selectedIds = [];
  const initLabels = {};
  document.querySelectorAll('#contribSelected [data-id]').forEach(function(el){ initLabels[parseInt(el.getAttribute('data-id'),10)] = el.getAttribute('data-label')||el.textContent; });
  let contribLabels = initLabels;

  function renderSelected(){
    elSelected.innerHTML = '';
    selectedIds.forEach(function(id){
      const chip = document.createElement('span');
      chip.className = 'badge bg-primary me-2 mb-2';
      chip.textContent = (contribLabels[id]||('#'+id));
      chip.setAttribute('data-id', id);
      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'btn btn-sm btn-light ms-1';
      remove.textContent = '×';
      remove.addEventListener('click', function(){
        selectedIds = selectedIds.filter(function(x){ return x !== id; });
        elIds.value = selectedIds.join(',');
        renderSelected();
        // Hapus konfigurasi komisi untuk kontributor ini
        var cfg = document.querySelector('[data-config-id="'+id+'"]');
        if (cfg) { cfg.remove(); }
      });
      chip.appendChild(remove);
      elSelected.appendChild(chip);
    });
  }

  function addSelected(id, label){
    id = parseInt(id,10);
    if(!id || selectedIds.indexOf(id) !== -1){ return; }
    selectedIds.push(id);
    elIds.value = selectedIds.join(',');
    renderSelected();
    elSearch.value = label || '';
    elRes.innerHTML = '';
    if(label){ contribLabels[id] = label; }
    // Tambahkan konfigurasi komisi per kontributor
    if (elConfig) {
      const row = document.createElement('div');
      row.className = 'row g-2 align-items-center mb-2';
      row.setAttribute('data-config-id', id);
      row.innerHTML = `
        <div class="col-12 col-md-5">
          <input type="text" class="form-control" value="${label||('#'+id)}" disabled>
        </div>
        <div class="col-6 col-md-3">
          <select class="form-select" name="contrib_type[${id}]" title="Jenis komisi untuk kontributor ini">
            <option value="percent">Persentase (%)</option>
            <option value="fixed" selected>Nilai Tetap (Rp)</option>
          </select>
        </div>
        <div class="col-6 col-md-3">
          <input type="number" class="form-control" name="contrib_value[${id}]" min="0" placeholder="0" title="Nilai komisi untuk kontributor ini">
        </div>
      `;
      elConfig.appendChild(row);
    }
  }

  function renderResults(items){
    const hasItems = Array.isArray(items) && items.length>0;
    if(!hasItems){ elRes.classList.add('show'); elRes.setAttribute('aria-expanded','true'); elRes.innerHTML = '<span class="dropdown-item text-muted" role="option">Tidak ada hasil.</span>'; return; }
    const html = items.map(function(it){
      const nama = (it.mem_nama||'').replace(/</g,'&lt;');
      const email = (it.mem_email||'').replace(/</g,'&lt;');
      const wa = (it.mem_whatsapp||'').replace(/</g,'&lt;');
      if(parseInt(it.mem_status,10) < 2) { return ''; }
      return '<button type="button" class="dropdown-item" role="option" data-id="'+it.mem_id+'" data-label="'+nama+' ('+ (email||wa) +')">'+nama+'<br><small class="text-muted">'+(email||'')+(email&&wa?' • ':'')+(wa||'')+'</small></button>';
    }).join('');
    elRes.innerHTML = html || '<span class="dropdown-item text-muted" role="option">Hanya member Premium yang dapat dipilih.</span>';
    elRes.classList.add('show');
    elRes.setAttribute('aria-expanded','true');
    elRes.querySelectorAll('button[data-id]').forEach(function(btn){
      btn.addEventListener('click', function(){ addSelected(this.getAttribute('data-id'), this.getAttribute('data-label')); elRes.classList.remove('show'); elRes.setAttribute('aria-expanded','false'); });
    });
  }

  function doSearch(q){
    if(lastController){ lastController.abort(); }
    lastController = new AbortController();
    const url = '<?= $weburl ?>plugins/epi/member-search.php?q=' + encodeURIComponent(q);
    fetch(url, {signal: lastController.signal})
      .then(r=>r.json())
      .then(function(data){
        const items = Array.isArray(data) ? data : (Array.isArray(data.items) ? data.items : []);
        renderResults(items);
      })
      .catch(()=>{ elRes.classList.add('show'); elRes.setAttribute('aria-expanded','true'); elRes.innerHTML = '<span class="dropdown-item text-muted" role="option">Gagal mencari.</span>'; });
  }

  if(elSearch){
    elSearch.addEventListener('input', function(){ const q = this.value.trim(); if(q.length >= 4){ doSearch(q); } else { elRes.innerHTML = ''; elRes.classList.remove('show'); elRes.setAttribute('aria-expanded','false'); } });
    elSearch.addEventListener('blur', function(){ setTimeout(function(){ elRes.classList.remove('show'); elRes.setAttribute('aria-expanded','false'); }, 150); });
    elSearch.addEventListener('focus', function(){ const q = this.value.trim(); if(q.length >= 4 && elRes.innerHTML){ elRes.classList.add('show'); elRes.setAttribute('aria-expanded','true'); } });
  }
  if(btnClear){ btnClear.addEventListener('click', function(){ selectedIds = []; elIds.value=''; elSearch.value=''; elRes.innerHTML=''; elRes.classList.remove('show'); elRes.setAttribute('aria-expanded','false'); renderSelected(); }); }
  document.querySelectorAll('[data-edit-id]').forEach(function(b){ b.addEventListener('click', function(){ var id = parseInt(this.getAttribute('data-edit-id'),10); var cfg = document.querySelector('[data-config-id="'+id+'"]'); if (cfg) { cfg.scrollIntoView({behavior:'smooth',block:'center'}); var sel = cfg.querySelector('select'); if(sel){ sel.focus(); } } }); });
  if(elIds && elIds.value){ selectedIds = elIds.value.split(',').map(function(x){ return parseInt(x,10); }).filter(Boolean); renderSelected(); }
  var frm = document.querySelector('form');
  if(frm){ frm.addEventListener('submit', function(e){ var ok=true; document.querySelectorAll('#contribConfig [data-config-id]').forEach(function(row){ var sel=row.querySelector('select'); var inp=row.querySelector('input[type="number"]'); if(!sel||!inp){ return; } var t=sel.value; var v=parseInt(inp.value||'0',10); inp.classList.remove('is-invalid'); if(t==='percent'){ if(isNaN(v)||v<0||v>100){ ok=false; inp.classList.add('is-invalid'); } } else { if(isNaN(v)||v<0){ ok=false; inp.classList.add('is-invalid'); } } }); if(!ok){ e.preventDefault(); } }); }
})();

// Benefit editor
(function(){
  const list = document.getElementById('benefitList');
  const prev = document.getElementById('benefitPreview');
  const btnAdd = document.getElementById('addBenefit');
  function sanitize(s){ return s.replace(/[\<\>]/g,'').trim(); }
  function renderPreview(){
    if(!prev || !list) return;
    const items = Array.from(list.querySelectorAll('div.row')).map(function(row){
      const label = sanitize(row.querySelector('input[name="benefit_items[]"]').value||'');
      const active = row.querySelector('input[name="benefit_active[]"]').checked;
      return active && label ? label : null;
    }).filter(Boolean);
    prev.innerHTML = items.length ? items.map(function(t){ return '<li>'+t+'</li>'; }).join('') : '<li class="text-muted">Belum ada poin</li>';
  }
  function addRow(){
    const existingSorts = Array.from(list.querySelectorAll('input[name="benefit_sort[]"]')).map(function(inp){
      var v = parseInt(inp.value||'0', 10);
      return isNaN(v) ? 0 : v;
    });
    const baseSort = existingSorts.length ? Math.max.apply(null, existingSorts) : 0;
    const nextSort = baseSort + 1;
    const row = document.createElement('div');
    row.className = 'row g-2 align-items-center mb-2';
    row.innerHTML = `
      <div class="col-12 col-md-8">
        <input type="text" name="benefit_items[]" class="form-control" maxlength="160" placeholder="Contoh: Akses EPIC Hub + materi EPI Academy" required>
      </div>
      <div class="col-6 col-md-2">
        <input type="number" name="benefit_sort[]" class="form-control" value="${nextSort}" min="0" title="Urutan">
      </div>
      <div class="col-6 col-md-2 d-flex align-items-center gap-2">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" value="1" checked data-active-sync>
          <label class="form-check-label">Aktif</label>
        </div>
        <input type="hidden" name="benefit_active_state[]" value="1">
        <button type="button" class="btn btn-sm btn-outline-danger text-nowrap removeBenefit" aria-label="Hapus"><i class="fa-solid fa-trash-can"></i></button>
      </div>`;
    list.appendChild(row);
    bindRow(row);
    renderPreview();
  }
  function bindRow(row){
    row.querySelectorAll('input').forEach(function(inp){ inp.addEventListener('input', renderPreview); });
    const chk = row.querySelector('[data-active-sync]');
    const hid = row.querySelector('input[name="benefit_active_state[]"]');
    if(chk && hid){ chk.addEventListener('change', function(){ hid.value = chk.checked ? '1' : '0'; renderPreview(); }); }
    const del = row.querySelector('.removeBenefit');
    if(del){ del.addEventListener('click', function(){ row.remove(); renderPreview(); }); }
  }
  if(list){ list.querySelectorAll('div.row').forEach(bindRow); }
  if(btnAdd){ btnAdd.addEventListener('click', addRow); }
  renderPreview();
})();
</script>
<?php showfooter(); ?>
