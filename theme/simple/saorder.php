<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
// Start session & set nonce for lightweight validation security
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (empty($_SESSION['epi_nonce'])) { $_SESSION['epi_nonce'] = sha1(SECRET . microtime(true) . rand(1000,9999)); }
// Init defaults to avoid undefined notices in mixed flows (login/register)
$password = isset($password) ? $password : '';
if (isset($slug[2]) && !empty($slug[2])) :
	$order = db_row("SELECT * FROM `sa_page` WHERE `page_url`='".txtonly($slug[2])."'");
	if (isset($settings['tripay_sandbox']) && $settings['tripay_sandbox'] == 1) {
    $urlapi = 'api-sandbox';
  } else {
    $urlapi = 'api'; 
  }

	if (isset($order['page_judul'])) :
    // Hitung harga tampil efektif (promo price + aturan diskon)
    $promoCodeInput = '';
    if (isset($_POST['promo_code'])) { $promoCodeInput = trim($_POST['promo_code']); }
    elseif (isset($_GET['promo'])) { $promoCodeInput = trim($_GET['promo']); }
    $eff = epi_effective_price((int)$order['pro_harga'], (int)$order['pro_harga_display'], $promoCodeInput, (int)$order['page_id'], 1);
    $importantNote = '';
    if (isset($_POST['important_note'])) { $importantNote = trim((string)$_POST['important_note']); }
    $hargaEfektif = isset($eff['price']) ? (int)$eff['price'] : 0;
    // Guard: pastikan hargaTampil selalu angka valid
    $hargaTampil = is_numeric($hargaEfektif) ? (int)$hargaEfektif : ((isset($order['pro_harga']) && is_numeric($order['pro_harga'])) ? (int)$order['pro_harga'] : 0);
    $couponInvalid = (!empty($promoCodeInput) && (int)$eff['discount']===0);
    $pendingExists = false;
		# Kalau member sudah login, cek apakah sudah order atau belum
		if ($iduser = is_login()) {
			$cekorder = db_row("SELECT * FROM `sa_order` WHERE `order_idmember`=".$iduser." AND `order_idproduk`=".$order['page_id']);
			if (isset($cekorder['order_status'])) {
     	if ($cekorder['order_status'] == 1) {
	      	# Order sudah lunas, arahkan ke halaman download
	      	header("Location:".$weburl."dashboard/akses/".$order['page_url']);
     	} else {
     	# Sudah order tapi belum lunas: JANGAN redirect ke invoice, tampilkan form pembelian (identitas readonly, tanpa kupon)
     	$pendingExists = true;
      	$pendingOrderId = (int)$cekorder['order_id'];
     	$idmember = $iduser;
     	$idsponsor = db_var("SELECT `sp_sponsor_id` FROM `sa_sponsor` WHERE `sp_mem_id`=".$iduser);
        // Prefill identitas
        $me = db_row("SELECT `mem_nama`,`mem_email`,`mem_whatsapp` FROM `sa_member` WHERE `mem_id`=".$iduser);
        if (is_array($me)) {
          $nama = $me['mem_nama'] ?? '';
          $email = $me['mem_email'] ?? '';
          $whatsapp = isset($me['mem_whatsapp']) ? formatwa($me['mem_whatsapp']) : '';
        } else { $nama = ''; $email = ''; $whatsapp = ''; }
        // Abaikan kupon untuk kasus pending
        $promoCodeInput = '';
        $eff = epi_effective_price((int)$order['pro_harga'], (int)$order['pro_harga_display'], $promoCodeInput, (int)$order['page_id'], 1);
        $hargaTampil = (int)$eff['price'];
     	}
      }
      else {
	      	$idmember = $iduser;
	      	$idsponsor = db_var("SELECT `sp_sponsor_id` FROM `sa_sponsor` WHERE `sp_mem_id`=".$iduser);
        // Prefill identitas untuk alur order oleh member terdaftar
        $me = db_row("SELECT `mem_nama`,`mem_email`,`mem_whatsapp` FROM `sa_member` WHERE `mem_id`=".$iduser);
        if (is_array($me)) {
          $nama = $me['mem_nama'] ?? '';
          $email = $me['mem_email'] ?? '';
          $whatsapp = isset($me['mem_whatsapp']) ? formatwa($me['mem_whatsapp']) : '';
        } else {
          $nama = '';
          $email = '';
          $whatsapp = '';
        }
      }
        } elseif (isset($_POST['nama']) && !empty($_POST['nama']) && isset($_POST['email']) && validemail($_POST['email'])) {
          if (db_exist("SELECT `mem_email` FROM `sa_member` WHERE `mem_email`='".cek($_POST['email'])."'")) {
                $error = 'Email sudah terdaftar, silakan login untuk melanjutkan order.';
            }
            // Cek duplikasi nomor WhatsApp
            if (!isset($error) && isset($_POST['whatsapp']) && !empty($_POST['whatsapp'])) {
                $formatted_wa = formatwa($_POST['whatsapp']);
                if (!empty($formatted_wa)) {
                    if (db_exist("SELECT `mem_whatsapp` FROM `sa_member` WHERE `mem_whatsapp`='".cek($formatted_wa)."'")) {
                        $error = 'Nomor whatsapp sudah terdaftar, silakan login untuk melanjutkan order.';
                    }
                }
            }

			# Cek form yg required

			$req = db_select("SELECT * FROM `sa_form` WHERE `ff_registrasi`=1 AND `ff_required`=1");
			if (count($req) > 0) {
				foreach ($req as $req) {
					if (!isset($_POST[$req['ff_field']]) || empty($_POST[$req['ff_field']])) {
						$error = $req['ff_label'].' wajib diisi';
					} else {
						if ($req['ff_field'] == 'whatsapp') {
							if (empty(formatwa($_POST['whatsapp']))) {
								$error = $req['ff_label'].' wajib diisi dg format 08123456789';
							}
						}
					}
				}
			}

			if (!isset($error)) {
				if (!isset($idsponsor)) {
					if (isset($_COOKIE['idsponsor']) && is_numeric($_COOKIE['idsponsor'])) {
						if (db_exist("SELECT `mem_id` FROM `sa_member` WHERE `mem_id`=".$_COOKIE['idsponsor'])) {
							$idsponsor = $_COOKIE['idsponsor'];
						} else {
							$idsponsor = 1;
						}
					} else {
						$idsponsor = 1;
					}
				}

				if (isset($_POST['sponsor']) && !empty($_POST['sponsor'])) {
					$sponsor = db_var("SELECT `mem_id` FROM `sa_member` WHERE `mem_kodeaff`='".txtonly(strtolower($_POST['sponsor']))."'");
					
					if (is_numeric($sponsor)) {
						$idsponsor = $sponsor;
					} 
				}

				$defaultkey = array('nama','email','password','whatsapp','kodeaff');
				$datalain = '';
				

				foreach ($_POST as $key => $value) {
					if (in_array($key, $defaultkey)) {
						${$key} = cek($value);
					} else {
						$datalain .= '['.txtonly(strtolower($key)).'|'.cek($value).']';
					}
				}
				

				if (isset($_FILES) && count($_FILES) > 0) {
					$max_size = 1024000;
					$whitelist_ext = array('jpeg','jpg','png','gif');
					$whitelist_type = array('image/jpeg', 'image/jpg', 'image/png','image/gif');
					$pic_dir = str_replace('saregister.php','upload',__FILE__);
					$memberid = 'XXX'.rand(1000,9999).'XXX';
					
					if( ! file_exists( $pic_dir ) ) { mkdir( $pic_dir ); }

					foreach($_FILES as $field => $files) {
						$filename = $memberid.'_'.$field;
						$target_file = $pic_dir.'/'.$filename;
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
						// Check file size
				    if ($files["size"] > $max_size) {
				      $txterror = 'Maaf, gambar terlalu besar. Max. 1Mb';
				      $uploadOk = 0;
				    }
                if ($uploadOk == 1) {
                $file = $files["tmp_name"];
                $target_file = $target_file.'.'.$imageFileType;
                $clazzImagick = 'Imagick';
                if (class_exists($clazzImagick)) {
                    $img = new $clazzImagick();
                    $img->readImage($file);
                    $width = $img->getImageWidth();
                    if ($width > 800) { $width = 800; }
                    $img->setimagebackgroundcolor('white');
                    $img->mergeImageLayers(constant('Imagick::LAYERMETHOD_FLATTEN'));
                    $img->setImageCompression(constant('Imagick::COMPRESSION_JPEG'));
                    $img->setImageCompressionQuality(80);
                    $img->resizeImage($width,800,constant('Imagick::FILTER_CATROM'),1,TRUE);
                    $img->stripImage();
                    $img->writeImage($target_file);
                } else {
                    @move_uploaded_file($file, $target_file);
                }
                $datalain .= txtonly(strtolower($field)).'|'.$filename.'.'.$imageFileType."\n";        
                }
					}
				}
				
                if (!isset($password) || empty($password)) { $password = randomword(); } else { $password = $_POST['password']; }

                // Generate affiliate code: epic + 5 random digits (00000-99999), unique
                $kodeaff = generate_epic_kodeaff();
                $affCreatedAt = date('Y-m-d H:i:s');
                // Persist metadata into mem_datalain for auditing
                $datalain .= '[aff_code|'.$kodeaff.']'.'[aff_created_at|'.$affCreatedAt.']'.'[aff_source|checkout]';
                if (isset($whatsapp)) { $whatsapp = formatwa($whatsapp); } else { $whatsapp = ''; }
										
				$newuserid = db_insert("INSERT INTO `sa_member` (
					`mem_nama`,`mem_email`,`mem_password`,`mem_whatsapp`,`mem_kodeaff`,
					`mem_datalain`,`mem_tgldaftar`,`mem_status`,`mem_role`) 
				VALUES ('".$nama."','".$email."','".create_hash($password)."',
					'".$whatsapp."','".$kodeaff."','".$datalain."','".date('Y-m-d H:i:s')."',
					1,1)");

				
				if (is_numeric($newuserid)) {
					$spnetwork = db_var("SELECT `sp_network` FROM `sa_sponsor` WHERE `sp_mem_id`=".$idsponsor);
					$newspnetwork = '['.$idsponsor.']'.$spnetwork;

					$cek = db_insert("INSERT INTO `sa_sponsor` (`sp_mem_id`,`sp_sponsor_id`,`sp_network`) VALUES 
						(".$newuserid.",".$idsponsor.",'".$newspnetwork."')");
					if (isset($memberid)) {
						$datalain = str_replace($memberid,$newuserid,$datalain);
						db_query("UPDATE `sa_member` SET `mem_datalain`='".$datalain."' WHERE `mem_id`=".$newuserid);
						$files = glob($pic_dir . '/'.$memberid.'*');					
						// Loop semua file yang ditemukan dan ganti nama file
						foreach ($files as $file) {
						    // Buat nama file baru dengan mengganti teks XXX123XXX dengan ID member baru
						    $newName = str_replace($memberid, $newuserid, $file);
						    // Ganti nama file
						    rename($file, $newName);
						}
					}
					

				} else {
					$error = db_error();
				}

				if (isset($cek)) {
					if ($cek === false) {
						$error = db_error();
					} else {
						$idmember = $newuserid;
						$id = $newuserid;
						$hash = sha1(rand(0,500).microtime().SECRET);
						$signature = sha1(SECRET . $hash . $id);
						$cookie = base64_encode($signature . "-" . $hash . "-" . $id);
						setcookie('authentication', $cookie,time()+36000,'/');
						db_query("UPDATE `sa_member` SET `mem_lastlogin`='".date('Y-m-d H:i:s')."' WHERE `mem_id`=".$id);
					}
				}				
			}
		} elseif (isset($_POST['username']) && filter_var($_POST['username'],FILTER_VALIDATE_EMAIL) 
			&& isset($_POST['password']) && !empty($_POST['password'])) {

			$datamember = db_row("SELECT * FROM `sa_member` LEFT JOIN `sa_sponsor` ON `sa_sponsor`.`sp_mem_id`=`sa_member`.`mem_id`
				WHERE `mem_email`='".cek($_POST['username'])."'");

			if (isset($datamember['mem_id'])) {
				if (validate_password($_POST['password'],$datamember['mem_password'])) {
		      $idmember = $id = $datamember['mem_id'];
		      $idsponsor = $datamember['sp_sponsor_id'];
		      $hash = sha1(rand(0,500).microtime().SECRET);
		      $signature = sha1(SECRET . $hash . $id);
		      $cookie = base64_encode($signature . "-" . $hash . "-" . $id);
		      setcookie('authentication', $cookie,time()+36000,'/');
		      db_query("UPDATE `sa_member` SET `mem_lastlogin`='".date('Y-m-d H:i:s')."' WHERE `mem_id`=".$id);

		      # Cek apakah sudah order sebelumnya
            $cekorder = db_row("SELECT * FROM `sa_order` WHERE `order_idmember`=".$idmember." AND `order_idproduk`=".$order['page_id']);
            if (isset($cekorder['order_status'])) {
            	if ($cekorder['order_status'] == 1) {
            	    # Order sudah lunas, arahkan ke halaman download
            	    header("Location:".$weburl."dashboard/akses/".$order['page_url']);
            	    exit();
            	} else {
            		# Sudah order tapi belum lunas: tampilkan form pembelian (identitas readonly, tanpa kupon)
            		$pendingExists = true;
            		$pendingOrderId = (int)$cekorder['order_id'];
            		// Abaikan kupon
            		$promoCodeInput = '';
            		// Tidak redirect, biarkan user memilih metode pembayaran dan membuat order baru
            	}
            }

		    } else {
		        $error = 'Maaf, sepertinya Password anda kurang tepat, silahkan cek kembali dan pastikan tombol capslock tidak tertekan.';
		    }
			} else {
				$error = 'Maaf, kami tidak menemukan akun dengan email tersebut.';
			}
		}

		# Bikin Order
		if (isset($idmember) && is_numeric($idmember) && !isset($error)) {
			$lastidorder = db_var("SELECT AUTO_INCREMENT
          FROM information_schema.TABLES
          WHERE TABLE_NAME = 'sa_order'");
      if (!is_numeric($lastidorder)) {
        $lastidorder = 0;
      }

            // Alokasi Kode Unik yang tidak bentrok dengan invoice pending lainnya
            // Mode: 0=no unique, 1=subtract, 2=add; default add
            $modeUnik = (isset($settings['kodeunik']) && is_numeric($settings['kodeunik'])) ? (int)$settings['kodeunik'] : 2;
            // Seed awal dari AUTO_INCREMENT agar penyebaran merata
            $seed = (int)$lastidorder;
            // Kumpulan nilai unik yang sedang aktif (pending, belum lunas)
            $rowsUsed = db_select("SELECT `order_hargaunik` FROM `sa_order` WHERE `order_status`=0 AND `order_hargaunik`>0") ?: array();
            $used = array(); foreach ($rowsUsed as $u) { $used[(int)$u['order_hargaunik']] = true; }
            // Cari kandidat aman 1..999 dengan perhitungan sesuai mode
            // Guard: jika karena alur tertentu $hargaTampil belum terdefinisi, fallback ke hargaEfektif/harga dasar
            if (!isset($hargaTampil) || !is_numeric($hargaTampil)) {
                $hargaTampil = (isset($hargaEfektif) && is_numeric($hargaEfektif)) ? (int)$hargaEfektif : ((isset($order['pro_harga']) && is_numeric($order['pro_harga'])) ? (int)$order['pro_harga'] : 0);
            }
            $hrgunik = $hargaTampil; // default
            if ($modeUnik === 0) {
                $hrgunik = $hargaTampil;
            } else {
                $found = false;
                for ($j=0; $j<999; $j++) {
                    $idunik = (($seed + $j) % 1000); if ($idunik === 0) { $idunik = 1; }
                    if ($modeUnik === 1) {
                        $candidate = ($hargaTampil - 1000) + $idunik; if ($candidate <= 0) { continue; }
                    } elseif ($modeUnik === 2) {
                        $candidate = $hargaTampil + $idunik;
                    } else {
                        $candidate = $hargaTampil;
                    }
                    if (!isset($used[$candidate])) { $hrgunik = $candidate; $found = true; break; }
                }
                if (!$found) {
                    // fallback random 100..999 yang belum dipakai
                    for ($try=0; $try<999; $try++) {
                        $idunik = rand(100, 999);
                        $candidate = ($modeUnik === 1) ? max(1, ($hargaTampil - 1000) + $idunik) : ($hargaTampil + $idunik);
                        if (!isset($used[$candidate])) { $hrgunik = $candidate; break; }
                    }
                }
            }

            // Gunakan harga efektif dari perhitungan awal, bukan reset ke harga dasar
            $hargaTampil = $hargaEfektif;
            $order_trx = '';
            if ($hargaTampil == 0) {
                $hrgunik = 0;
                $order_trx = 'free';
            }

            // Duplikasi Order Guard (server-side, idempotent)
            // Cek apakah sudah ada order untuk member & produk ini, status pending (0) atau lunas (1)
            $existing = db_row("SELECT `order_id`,`order_status` FROM `sa_order` WHERE `order_idmember`=".$idmember." AND `order_idproduk`=".$order['page_id']." ORDER BY `order_id` DESC LIMIT 1");
            if (isset($existing['order_id'])) {
                if ((int)$existing['order_status'] === 1) {
                    // Sudah lunas: arahkan ke halaman akses
                    header("Location:".$weburl."dashboard/akses/".$order['page_url']);
                    exit();
                } else {
                    // Sudah memiliki order pending: jangan buat order baru
                    $pendingExists = true;
                    $pendingOrderId = (int)$existing['order_id'];
                    $error = 'Anda sudah memiliki pesanan yang belum dibayar untuk produk ini. Silakan lanjutkan pembayaran melalui invoice.';
                }
            }

            // Proses checkout & pembuatan order HANYA saat POST (klik "Order Sekarang")
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Guard: blokir order jika kupon tidak berlaku
                if (!isset($pendingExists) && !empty($promoCodeInput) && (int)$eff['discount']===0) {
                    $error = 'Kode promo tidak berlaku. Silakan periksa kembali atau hapus kupon untuk melanjutkan.';
                }

                // Guard: wajib pilih metode pembayaran jika harga > 0
                if ((!isset($pendingExists) || $pendingExists === false) && $hargaTampil > 0 && (!isset($_POST['payment']) || empty($_POST['payment']))) {
                    $error = 'Silakan pilih metode pembayaran terlebih dahulu.';
                }

                if (!isset($error)) {
              	# Bikin Transaksi
            if ((!isset($pendingExists) || $pendingExists === false) && $hargaTampil > 0 && isset($_POST['payment']) && !empty($_POST['payment'])) {
                if ($_POST['payment'] == 'manual') {
                    $order_trx = 'manual';
                } else {
                    # Tripay Create Start
					$apiKey       = $settings['tripay_api'];
			    $privateKey   = $settings['tripay_private'];
			    $merchantCode = $settings['tripay_merchant'];
			    $merchantRef  = str_pad($lastidorder,4,0,STR_PAD_LEFT);
                    $amount       = $hargaTampil;

			    # Instruksi Pembayaran
			    $data = [
			        'method'         => $_POST['payment'],
			        'merchant_ref'   => $merchantRef,
			        'amount'         => $amount,
			        'customer_name'  => $nama,
			        'customer_email' => $email,
			        'customer_phone' => $whatsapp,
			        'order_items'    => [
			            [
			                'sku'         => 'PRO-'.$order['page_id'],
			                'name'        => $order['page_judul'],
                            'price'       => $hargaTampil,
                            'quantity'    => 1,
                            'product_url' => $weburl.'produk/'.$order['page_url']
                        ]
                    ],
			        'return_url'   => $weburl.'thanks',
			        'expired_time' => (time() + (24 * 60 * 60)), // 24 jam
			        'signature'    => hash_hmac('sha256', $merchantCode.$merchantRef.$amount, $privateKey)
			    ];

			    $curl = curl_init();

			    curl_setopt_array($curl, [
			        CURLOPT_FRESH_CONNECT  => true,
			        CURLOPT_URL            => 'https://tripay.co.id/'.$urlapi.'/transaction/create',
			        CURLOPT_RETURNTRANSFER => true,
			        CURLOPT_HEADER         => false,
			        CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$apiKey],
			        CURLOPT_FAILONERROR    => false,
			        CURLOPT_POST           => true,
			        CURLOPT_POSTFIELDS     => http_build_query($data),
			        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4
			    ]);

			    $response = curl_exec($curl);
			    $error = curl_error($curl);

			    curl_close($curl);
			    $hasil = empty($error) ? $response : $error;
			    $arrhasil = json_decode($hasil,TRUE);

			    if (isset($arrhasil['success']) && $arrhasil['success'] == 1) {
			    	$order_trx = $arrhasil['data']['reference'];
			    } else {
			    	$order_trx = '';
			    }
					# Tripay Create End
				}
			}

            // Jika ada order pending, saat submit langsung arahkan ke invoice lama dan hentikan proses insert
            if (isset($pendingExists) && $pendingExists === true && isset($pendingOrderId) && is_numeric($pendingOrderId)) {
                header("Location:".$weburl."invoice/".$pendingOrderId);
                exit();
            }

			// Guard optional columns for backward compatibility, dan lakukan insert HANYA jika tidak ada order pending
            if (!isset($pendingExists) || $pendingExists === false) {
                $hasPriceDisplay = db_var("SHOW COLUMNS FROM `sa_order` LIKE 'order_price_display'");
                $hasDiscount = db_var("SHOW COLUMNS FROM `sa_order` LIKE 'order_discount'");
                $hasPromoCode = db_var("SHOW COLUMNS FROM `sa_order` LIKE 'order_promo_code'");
                $hasImportantNote = db_var("SHOW COLUMNS FROM `sa_order` LIKE 'order_important_note'");
                $cols = "`order_idmember`,`order_idsponsor`,`order_idproduk`,`order_tglorder`,`order_harga`,`order_hargaunik`,`order_trx`,`order_status`";
                $vals = $idmember.",".$idsponsor.",".$order['page_id'].",'".date('Y-m-d H:i:s')."',".$order['pro_harga'].",".$hrgunik.",'".$order_trx."',0";
                if ($hasPriceDisplay) { $cols .= ",`order_price_display`"; $vals .= ",".$hargaTampil; }
                if ($hasDiscount) { $cols .= ",`order_discount`"; $vals .= ",".(int)$eff['discount']; }
                if ($hasPromoCode) { $cols .= ",`order_promo_code`"; $vals .= ",'".cek($promoCodeInput)."'"; }
                if ($hasImportantNote) { $cols .= ",`order_important_note`"; $vals .= ",'".cek($importantNote)."'"; }
                $idorder = db_insert("INSERT INTO `sa_order` (".$cols.") VALUES (".$vals.")");
            } else {
                $idorder = false; // tidak ada insert ketika pending
            }

            if (is_numeric($idorder)) {
                # Kirim Notif yuk
                # Hitung informasi tambahan sesuai logika di invoice
                $hargaNormal    = (isset($order['pro_harga']) && is_numeric($order['pro_harga'])) ? (int)$order['pro_harga'] : 0;
                $hargaPromoBase = (isset($order['pro_harga_display']) && is_numeric($order['pro_harga_display'])) ? (int)$order['pro_harga_display'] : $hargaNormal;
                $diskonPromo    = max(0, $hargaNormal - $hargaPromoBase);
                $diskonKupon    = (isset($eff['discount']) && is_numeric($eff['discount'])) ? (int)$eff['discount'] : 0;
                $totalDiskon    = max(0, (int)$diskonPromo + (int)$diskonKupon);
                // Total pembayaran WA = nominal transfer dengan kode unik (order_hargaunik)
                $totalBayarNominal = (isset($hrgunik) && is_numeric($hrgunik)) ? (int)$hrgunik : $hargaTampil;

                $datalain = array(
                    'newpass'         => $password,
                    'idorder'         => $idorder,
                    'hrgunik'         => $hrgunik,
                    'hrgproduk'       => $order['pro_harga'],
                    'namaproduk'      => $order['page_judul'],
                    'urlproduk'       => $order['page_url'],
                    // Shortcodes tambahan untuk WA (sesuai sistem invoice)
                    'diskon'          => number_format($totalDiskon),              // Diskon Produk (promo+kupon)
                    'totalbayar'      => 'Rp ' . number_format($totalBayarNominal), // Total Pembayaran (final transfer)
                    'halaman_invoice' => $weburl . 'invoice/' . $idorder,          // URL Invoice lengkap
                    // Alias umum jika dibutuhkan pada template lain
                    'invoice_url'     => $weburl . 'invoice/' . $idorder
                );
                $dl = db_var("SELECT `mem_datalain` FROM `sa_member` WHERE `mem_id`=".$idmember);
                $dl = is_string($dl) ? $dl : '';
                $dl .= "last_order|".$idorder."|".date('Y-m-d H:i:s')."|".$order['page_url']."\n";
                $dl .= "[aff_order_id|".$idorder."]"."[aff_tx|".$order_trx."]"."\n";
                if ((!isset($hasImportantNote) || !$hasImportantNote) && !empty($importantNote)) { $dl .= "[order_note_".$idorder."|".str_replace(["\r","\n","|","[","]"]," ",$importantNote)."]\n"; }
                db_query("UPDATE `sa_member` SET `mem_datalain`='".cek($dl)."' WHERE `mem_id`=".$idmember);
                sa_notif('order',$idmember,$datalain);
                epi_discount_log($idmember, (int)$order['page_id'], $promoCodeInput, (int)$eff['discount'], (int)$hargaTampil);
                if (!empty($promoCodeInput)) { epi_coupon_register_usage($promoCodeInput, (int)$idorder, (int)$order['page_id'], (int)$idmember, (int)$eff['discount']); }
                # Redirect ke invoice
                header("Location:".$weburl."invoice/".$idorder);
            } else {
                $error = db_error();
            }
                } // end invalid-coupon guard
            } // end POST-only block
		}
?>
<!DOCTYPE html>
<html class="full" lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="<?=$weburl;?>img/<?=$favicon;?>" />
    <?php
      $skema = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
      $rekues = parse_url($_SERVER["REQUEST_URI"]);
      $canonical = $skema . "://" . $_SERVER['HTTP_HOST'] . (isset($rekues['path']) ? $rekues['path'] : '');
      $metaTitle = (isset($order['meta_headline']) && !empty($order['meta_headline'])) ? $order['meta_headline'] : ('Order ' . ($order['page_judul'] ?? ''));
      $rawDesc = (isset($order['meta_description']) && !empty($order['meta_description'])) ? $order['meta_description'] : ($order['page_diskripsi'] ?? '');
      $metaDesc = htmlspecialchars((string)pendekin(strip_tags((string)$rawDesc), 160), ENT_QUOTES);
      $candidates = array();
      if (isset($order['meta_img']) && !empty($order['meta_img'])) { $candidates[] = (string)$order['meta_img']; }
      if (isset($order['pro_img']) && !empty($order['pro_img'])) { $candidates[] = 'upload/' . (string)$order['pro_img']; }
      $candidates[] = 'upload/epichannelbatch3.jpg';
      $candidates[] = 'upload/epic-hub.jpg';
      $metaImg = '';
      foreach ($candidates as $c) {
        if (strpos($c, 'http') === 0) { $metaImg = $c; break; }
        if (file_exists($c)) { $metaImg = $weburl . $c; break; }
      }
      if ($metaImg === '') {
        if (isset($settings['logoweb']) && !empty($settings['logoweb'])) {
          $metaImg = $weburl . 'upload/' . $settings['logoweb'];
        } else {
          $metaImg = $weburl . 'img/simpleaff-logo.png';
        }
      }
    ?>
    <link rel="canonical" href="<?= htmlspecialchars((string)$canonical, ENT_QUOTES); ?>" />
    <meta name="description" content="<?= $metaDesc; ?>" />
    <meta name="author" content="" />

    <title><?= htmlspecialchars((string)$metaTitle, ENT_QUOTES); ?></title>
    <meta property="og:title" content="<?= htmlspecialchars((string)$metaTitle, ENT_QUOTES); ?>" />
    <meta property="og:description" content="<?= $metaDesc; ?>" />
    <meta property="og:url" content="<?= htmlspecialchars((string)$canonical, ENT_QUOTES); ?>" />
    <meta property="og:image" content="<?= htmlspecialchars((string)$metaImg, ENT_QUOTES); ?>" />
    <meta property="og:type" content="website" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?= htmlspecialchars((string)$metaTitle, ENT_QUOTES); ?>" />
    <meta name="twitter:description" content="<?= $metaDesc; ?>" />
    <meta name="twitter:image" content="<?= htmlspecialchars((string)$metaImg, ENT_QUOTES); ?>" />
    <meta name="twitter:url" content="<?= htmlspecialchars((string)$canonical, ENT_QUOTES); ?>" />

    <!-- Bootstrap Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <script src="https://kit.fontawesome.com/052e965aa8.js" crossorigin="anonymous"></script> 
    <style type="text/css">
        .password-wrapper {
          position: relative;
        }
        
        .password-wrapper input[type="password"] {
          padding-right: 30px; /* Ruang untuk ikon */
        }
        
        .password-wrapper .toggle-password {
          position: absolute;
          top: 50%;
          right: 5px;
          transform: translateY(-50%);
          cursor: pointer;
        }
    </style>
    <script>
      function togglePassword() {
	      var passwordInput = document.getElementById("password");
	      var toggleBtn = document.getElementById("togglePassword");

	      if (passwordInput.type === "password") {
	        passwordInput.type = "text";
	        toggleBtn.innerHTML = '<i class="fas fa-eye-slash text-secondary"></i>';
	      } else {
	        passwordInput.type = "password";
	        toggleBtn.innerHTML = '<i class="fas fa-eye text-secondary"></i>';
	      }
	    }
    </script>
    <script>
      // Expose validation endpoint & nonce to JS
      window.EPI_VALIDATE_URL = '<?= $weburl; ?>api/member-search.php?mode=exists';
    window.EPI_VALIDATE_NONCE = '<?= htmlspecialchars((isset($_SESSION['epi_nonce']) && is_scalar($_SESSION['epi_nonce'])) ? (string)$_SESSION['epi_nonce'] : '', ENT_QUOTES); ?>';
      // Flag: skip realtime validation bila user sudah login (karena field readonly dan sudah terdaftar)
      window.EPI_LOGGED_IN = <?= is_login() ? 'true' : 'false' ?>;
      function epiDebounce(fn, delay) { let t; return function(...args){ clearTimeout(t); t = setTimeout(()=>fn.apply(this,args), delay); }; }
      function setInputError(input, message, loginUrl) {
        // Tampilkan error hanya dengan opsi Login (tanpa reset password), dan auto-isi email
        if (!input) return;
        input.classList.add('is-invalid');
        let fb = input.nextElementSibling;
        if (!fb || !fb.classList || !fb.classList.contains('invalid-feedback')) {
          fb = document.createElement('div'); fb.className = 'invalid-feedback'; input.parentNode.insertBefore(fb, input.nextSibling);
        }
        // Ambil email saat ini bila ada
        const emailEl = document.querySelector('input[name="email"]');
        const currentEmail = emailEl ? emailEl.value.trim() : '';
        const href = currentEmail ? (loginUrl + (loginUrl.indexOf('?')>=0 ? '&' : '?') + 'email=' + encodeURIComponent(currentEmail)) : loginUrl;
        fb.innerHTML = message + ' <a href="' + href + '" class="fw-semibold" id="epi-inline-login">Login</a>';
        // Jaga-jaga: pastikan saat diklik selalu gunakan email terbaru
        const l = fb.querySelector('#epi-inline-login');
        if (l) {
          l.addEventListener('click', function(e){
            const em = emailEl ? emailEl.value.trim() : '';
            if (em) {
              e.preventDefault();
              const url = loginUrl + (loginUrl.indexOf('?')>=0 ? '&' : '?') + 'email=' + encodeURIComponent(em);
              window.location.href = url;
            }
          });
        }
      }
      function clearInputError(input) { if (!input) return; input.classList.remove('is-invalid'); let fb = input.nextElementSibling; if (fb && fb.classList && fb.classList.contains('invalid-feedback')) { fb.textContent=''; } }
      async function checkExists(field, value) {
        if (!value) return {exists:false};
        const url = `${window.EPI_VALIDATE_URL}&field=${encodeURIComponent(field)}&value=${encodeURIComponent(value)}&n=${window.EPI_VALIDATE_NONCE}`;
        try { const res = await fetch(url, { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest' } }); const data = await res.json(); return data && data.ok ? data.data : {exists:false}; } catch(e){ return {exists:false}; }
      }
      function attachRealtimeValidation() {
        if (window.EPI_LOGGED_IN) { return; }
        const email = document.querySelector('input[name="email"]');
        const wa = document.querySelector('input[name="whatsapp"]');
        const submit = document.getElementById('formsubmit');
        const loginUrl = '<?= isset($visiturl) ? ($visiturl.'?act=login') : 'login'; ?>';
        const toggleSubmit = () => { const invalids = document.querySelectorAll('.is-invalid'); if (submit) { submit.disabled = invalids.length > 0; } };
        if (email) {
          const onEmail = epiDebounce(async () => {
            clearInputError(email);
            const r = await checkExists('email', email.value.trim());
            if (r.exists) { setInputError(email, 'Email sudah terdaftar, silakan login untuk melanjutkan order.', loginUrl); }
            toggleSubmit();
          }, 500);
          email.addEventListener('input', onEmail); email.addEventListener('blur', onEmail);
        }
        if (wa) {
          const onWa = epiDebounce(async () => {
            clearInputError(wa);
            const r = await checkExists('whatsapp', wa.value.trim());
            if (r.exists) { setInputError(wa, 'Nomor whatsapp sudah terdaftar, silakan login untuk melanjutkan order.', loginUrl); }
            toggleSubmit();
          }, 500);
          wa.addEventListener('input', onWa); wa.addEventListener('blur', onWa);
        }
      }
      document.addEventListener('DOMContentLoaded', attachRealtimeValidation);
    </script>
    <?php
      if (!isset($idsponsor)) {
        if (isset($_COOKIE['idsponsor']) && is_numeric($_COOKIE['idsponsor'])) {
          $idsponsor = $_COOKIE['idsponsor'];
        } else {
          $idsponsor = 1;
        }
      }
      $datasponsor = db_row("SELECT * FROM `sa_member` WHERE `mem_id`=".$idsponsor);
      $pixelId = '';
      if (isset($datasponsor['fbpixel']) && !empty($datasponsor['fbpixel'])) {
    $pixelId = htmlspecialchars(is_scalar($datasponsor['fbpixel'] ?? '') ? (string)$datasponsor['fbpixel'] : '', ENT_QUOTES);
      } elseif (isset($settings['fbpixel']) && !empty($settings['fbpixel'])) {
    $pixelId = htmlspecialchars(is_scalar($settings['fbpixel'] ?? '') ? (string)$settings['fbpixel'] : '', ENT_QUOTES);
      }
      $gtmId = '';
      if (isset($datasponsor['gtm']) && !empty($datasponsor['gtm'])) {
    $gtmId = htmlspecialchars(is_scalar($datasponsor['gtm'] ?? '') ? (string)$datasponsor['gtm'] : '', ENT_QUOTES);
      } elseif (isset($settings['gtm']) && !empty($settings['gtm'])) {
    $gtmId = htmlspecialchars(is_scalar($settings['gtm'] ?? '') ? (string)$settings['gtm'] : '', ENT_QUOTES);
      }
    ?>
    <?php if (!empty($pixelId)): ?>
    <!-- Meta Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '<?= $pixelId; ?>');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=<?= $pixelId; ?>&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->
    <?php endif; ?>
    <?php if (!empty($gtmId)): ?>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-<?= $gtmId; ?>');</script>
    <!-- End Google Tag Manager -->
    <?php endif; ?>
</head>
<body>
	<header class="container py-3 text-center">
		<?php 
		  // Robust logo source resolution to avoid fatal when $logoweb is array or invalid
		  $defaultLogo = $weburl.'img/simpleaff-logo.png';
		  $logoSrc = $defaultLogo;
		  if (isset($logoweb) && !empty($logoweb)) {
		    if (is_string($logoweb)) {
		      $logoSrc = $weburl.$logoweb;
		    } elseif (is_array($logoweb)) {
		      // Try common keys if settings stored as structured array
		      if (isset($logoweb['url']) && is_string($logoweb['url']) && $logoweb['url'] !== '') {
		        $logoSrc = (strpos($logoweb['url'], 'http') === 0) ? $logoweb['url'] : ($weburl.$logoweb['url']);
		      } elseif (isset($logoweb['path']) && is_string($logoweb['path']) && $logoweb['path'] !== '') {
		        $logoSrc = $weburl.$logoweb['path'];
		      } elseif (isset($settings['logoweb']) && is_string($settings['logoweb']) && $settings['logoweb'] !== '') {
		        // Fallback to settings string if available
		        $logoSrc = $weburl.'upload/'.$settings['logoweb'];
		      } else {
		        $logoSrc = $defaultLogo; // final fallback
		      }
		    } else {
		      $logoSrc = $defaultLogo;
		    }
		  }
		  echo '<img src="'.htmlspecialchars((string)$logoSrc,ENT_QUOTES).'" alt="Logo" style="max-height:60px"/>';
		?>
	</header>
	<?php if (!empty($gtmId)): ?>
	<!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-<?= $gtmId; ?>"
	height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<!-- End Google Tag Manager (noscript) -->
	<?php endif; ?>
	<div class="container-fluid p-3">
		
		<form action="" method="post" onsubmit="document.getElementById('formsubmit').disabled=true;
          document.getElementById('formsubmit').value='Tunggu sebentar...';" enctype="multipart/form-data">
		<div class="row m-md-5">
			<div class="col-md-8 order-1">
				<?php
				if (isset($error)) {
					echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
				  <strong>Error!</strong> '.$error.'
				  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
				</div>';
				}
				?>
				<div class="card mb-3">					
				<?php 				
				if (isset($_GET['act']) == 'login') :?>
					<div class="card-header">
				    Login
				  </div>
					<div class="card-body">
				      <div class="mb-3 row">
				      	<h2>Login</h2>
				      </div>
				      <div class="mb-3 row">
						    <label for="staticEmail" class="col-sm-3 col-form-label text-start">Email</label>
                            <div class="col-sm-9">
    <input type="email" class="form-control" name="username" placeholder="email@example.com" value="<?= isset($_GET['email']) && is_scalar($_GET['email']) ? htmlspecialchars((string)$_GET['email'], ENT_QUOTES) : ''; ?>">
                            </div>
						  </div>
      <div class="mb-3 row">
        <label for="inputPassword" class="col-sm-3 col-form-label text-start">Password</label>
        <div class="col-sm-9">
          <div class="password-wrapper">
            <input type="password" id="password" class="form-control" name="password">
            <span class="toggle-password" id="togglePassword" onclick="togglePassword()"><i class="fas fa-eye text-secondary"></i></span>
          </div>
        </div>
      </div>
      <!-- Promo code tidak tampil pada halaman login -->
                          <div class="text-end">
                            <input type="submit" class="btn btn-success" value=" ORDER SEKARANG ">
                          </div>
						<div class="mt-3 pt-3 border-top row">					
							<div class="col text-center">
								Belum punya akun? Silahkan <a href="<?=$visiturl;?>">Register</a>
							</div>
						</div>
					</div>
				<?php else :?>
                    <div class="card-header">
                    <?= is_login() ? 'Form Pembelian Produk' : 'Register'; ?>
                  </div>
					<div class="card-body">
							<?php 
						  if (!isset($idsponsor)) {
						  	if (isset($_COOKIE['idsponsor']) && is_numeric($_COOKIE['idsponsor'])) {
						  		$idsponsor = $_COOKIE['idsponsor'];
						  	} else {
						  		$idsponsor = 1;
						  	}
						  }

				  		$datasponsor = db_row("SELECT * FROM `sa_member` WHERE `mem_id`=".$idsponsor);

                      if (is_login()) {
                        // Tampilkan detil identitas terisi otomatis (readonly) dan field kupon saja
                        echo '<div class="mb-3 row">'
                           .'<label class="col-sm-3 col-form-label text-start">Nama Lengkap</label>'
                           .'<div class="col-sm-9">'
    .'<input type="text" class="form-control" name="nama" value="'.htmlspecialchars(is_scalar($nama ?? '') ? (string)($nama ?? '') : '',ENT_QUOTES).'" readonly>'
                           .'</div>'
                           .'</div>';
                        echo '<div class="mb-3 row">'
                           .'<label class="col-sm-3 col-form-label text-start">Alamat Email</label>'
                           .'<div class="col-sm-9">'
    .'<input type="email" class="form-control" name="email" value="'.htmlspecialchars(is_scalar($email ?? '') ? (string)($email ?? '') : '',ENT_QUOTES).'" readonly>'
                           .'</div>'
                           .'</div>';
                        echo '<div class="mb-3 row">'
                           .'<label class="col-sm-3 col-form-label text-start">No. WhatsApp</label>'
                           .'<div class="col-sm-9">'
    .'<input type="text" class="form-control" name="whatsapp" value="'.htmlspecialchars(is_scalar($whatsapp ?? '') ? (string)($whatsapp ?? '') : '',ENT_QUOTES).'" readonly>'
                           .'</div>'
                           .'</div>';
                        if (!isset($pendingExists) || $pendingExists === false) {
                          echo '<div class="mb-3 row"><label class="col-sm-3 col-form-label text-start">Kode Promo (opsional)</label><div class="col-sm-9"'
                              .'><div class="input-group promo-input-group">'
    .'<input type="text" class="form-control" name="promo_code" id="promo_code" value="'.htmlspecialchars(is_scalar($promoCodeInput ?? '') ? (string)($promoCodeInput ?? '') : '',ENT_QUOTES).'" placeholder="Masukkan kode promo jika ada">'
                              .'<button type="button" class="btn btn-outline-primary" id="applyCoupon">Terapkan</button>'
    .(!empty($promoCodeInput) ? '<a href="'.htmlspecialchars((string)$visiturl,ENT_QUOTES).'" class="btn btn-outline-secondary" id="clearCoupon">Hapus</a>' : '')
                              .'</div>'
                              .($couponInvalid ? '<div class="text-danger small mt-1">Kupon tidak berlaku. Periksa kembali atau hapus kupon untuk melanjutkan.</div>' : '')
                              .'</div></div>';
                          echo '<div class="mb-3 row"><label class="col-sm-3 col-form-label text-start">Catatan Penting</label><div class="col-sm-9"><input type="text" class="form-control" name="important_note" id="important_note" value="'.htmlspecialchars(is_scalar($importantNote ?? '') ? (string)($importantNote ?? '') : '',ENT_QUOTES).'" placeholder="Tuliskan Nama EPIS Pembina yang akan diikuti"></div></div>';
                        } else {
                          echo '<div class="mb-3 row"><label class="col-sm-3 col-form-label text-start">Kode Promo</label><div class="col-sm-9"><input type="text" class="form-control" name="promo_code" placeholder="Kupon tidak berlaku untuk order pending" value="" disabled></div></div>';
                          echo '<div class="mb-3 row"><label class="col-sm-3 col-form-label text-start">Catatan Penting</label><div class="col-sm-9"><input type="text" class="form-control" name="important_note" placeholder="Tidak dapat diubah untuk order pending" value="" disabled></div></div>';
                        }
                      } else {
                        echo form_builder('register');
                        echo '<div class="mb-3 row"><label class="col-sm-3 col-form-label text-start">Kode Promo (opsional)</label><div class="col-sm-9"'
                              .'><div class="input-group promo-input-group">'
    .'<input type="text" class="form-control" name="promo_code" id="promo_code" value="'.htmlspecialchars(is_scalar($promoCodeInput ?? '') ? (string)($promoCodeInput ?? '') : '',ENT_QUOTES).'" placeholder="Masukkan kode promo jika ada">'
                              .'<button type="button" class="btn btn-outline-primary" id="applyCoupon">Terapkan</button>'
    .(!empty($promoCodeInput) ? '<a href="'.htmlspecialchars((string)$visiturl,ENT_QUOTES).'" class="btn btn-outline-secondary" id="clearCoupon">Hapus</a>' : '')
                              .'</div>'
                              .($couponInvalid ? '<div class="text-danger small mt-1">Kupon tidak berlaku. Periksa kembali atau hapus kupon untuk melanjutkan.</div>' : '')
                              .'</div></div>';
                        echo '<div class="mb-3 row"><label class="col-sm-3 col-form-label text-start">Catatan Penting</label><div class="col-sm-9"><input type="text" class="form-control" name="important_note" id="important_note" value="'.htmlspecialchars(is_scalar($importantNote ?? '') ? (string)($importantNote ?? '') : '',ENT_QUOTES).'" placeholder="Tuliskan Nama EPIS Pembina yang akan diikuti"></div></div>';
                      }
				      ?>
				      <div class="text-end">
			      		<?php if (isset($pendingExists) && $pendingExists === true && isset($pendingOrderId) && is_numeric($pendingOrderId)) { ?>
			      		  <a href="<?=$weburl;?>invoice/<?=$pendingOrderId;?>" class="btn btn-warning">Lanjutkan ke Invoice</a>
       		<?php } else { ?>
       		  <input type="submit" class="btn btn-success" id="formsubmit" value=" ORDER SEKARANG " <?= ($couponInvalid ? 'disabled' : '') ?>>
       		<?php } ?>
			      	</div>
                            <div class="mt-3 pt-3 border-top text-center">
                                <?php 
                                if (isset($datasponsor['mem_nama'])) {
                          		echo '<b>Pereferral:</b> '.$datasponsor['mem_nama'];
                      		}
                      		?>  		
          				</div>
                        <div class="mt-3 pt-3 border-top row">                   
                            <div class="col text-center">
                                <?php if (!is_login()) { ?>
                                  Sudah punya akun? Silahkan <a href="<?=$visiturl;?>?act=login" onclick="event.preventDefault(); var em=document.querySelector('input[name=\"email\"]'); var v=em?em.value.trim():''; var u='<?=$visiturl;?>?act=login'+(v?('&email='+encodeURIComponent(v)):''); window.location.href=u;">Login</a>
                                <?php } else { ?>
                                  <?php if (!isset($pendingExists) || $pendingExists === false) { ?>
                                    Anda sudah login. Silakan isi kode promo (opsional) lalu klik Order Sekarang.
                                  <?php } else { ?>
                                    Anda sudah login dan memiliki order pending untuk produk ini. Kupon tidak berlaku. Silakan lanjutkan pembayaran melalui invoice yang sudah ada.
                                    <div class="mt-2"><a href="<?=$weburl;?>invoice/<?=$pendingOrderId;?>" class="btn btn-sm btn-outline-warning">Buka Invoice</a></div>
                                  <?php } ?>
                                <?php } ?>
                            </div>
                        </div>
					</div>
				<?php endif;?>					
				</div>
			</div>
			<div class="col-md-4">
				<div class="card mb-3">
					<div class="card-header">
				    Order Anda
				  </div>
					<div class="card-body">
    <?php if (isset($order['pro_img']) && !empty($order['pro_img'])) { echo '<div class="text-center mb-2"><img src="'.$weburl.'upload/'.htmlspecialchars(is_scalar($order['pro_img']) ? (string)$order['pro_img'] : '',ENT_QUOTES).'" class="img-fluid img-thumbnail" alt="'.htmlspecialchars(is_scalar($order['page_judul'] ?? '') ? (string)$order['page_judul'] : '',ENT_QUOTES).'"/></div>'; } ?>
						<div class="table-responsive">
                            <table class="table table-hover table-bordered">
                                <thead class="table-secondary">
                                    <tr>
                                        <th>Produk</th>
                                        <th class="text-end">Harga</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?=$order['page_judul'];?></td>
                                        <td class="text-end"><?=number_format(isset($order['pro_harga_display']) && $order['pro_harga_display']!=='' ? (int)$order['pro_harga_display'] : (int)$order['pro_harga']);?></td>
                                    </tr>
                                    <?php $basePrice = (isset($order['pro_harga_display']) && $order['pro_harga_display']!=='' ? (int)$order['pro_harga_display'] : (int)$order['pro_harga']); $diskonNominal = max(0, $basePrice - (int)$hargaTampil); if ($diskonNominal>0) { ?>
                                    <tr>
                                        <td>Diskon</td>
                                        <td class="text-end text-success">-<?=number_format($diskonNominal);?></td>
                                    </tr>
                                    <?php } ?>
                                    <tr>
                                        <td><strong>Total</strong></td>
                                        <td class="text-end"><strong><?=number_format((int)$hargaTampil);?></strong></td>
                                    </tr>
                </tbody>
            </table>
        </div>
        <?php
          $benefits = array();
          $showBenefit = 0;
          $hasSettings = db_select("SHOW TABLES LIKE 'epi_product_benefit_settings'");
          $hasItems = db_select("SHOW TABLES LIKE 'epi_product_benefit'");
          if (is_array($hasSettings) && count($hasSettings) > 0 && is_array($hasItems) && count($hasItems) > 0) {
            $showBenefit = (int)db_var("SELECT `show_benefit` FROM `epi_product_benefit_settings` WHERE `page_id`=".(int)$order['page_id']);
            if ($showBenefit === 1) {
              $benefits = db_select("SELECT `label` FROM `epi_product_benefit` WHERE `page_id`=".(int)$order['page_id']." AND `is_active`=1 ORDER BY `sort_order` ASC, `id` ASC");
            }
          }
          if ($showBenefit === 1 && is_array($benefits) && count($benefits) > 0) {
            echo '<div class="card mt-2" style="background:#fff9f0;border:1px solid #ffe6bf"><div class="card-body"><div class="fw-semibold mb-1">Informasi Benefit:</div><ul class="mb-0 list-unstyled">';
            foreach ($benefits as $b) { echo '<li class="mb-1"><i class="fa-solid fa-circle-check text-success me-2"></i>'.htmlspecialchars($b['label']).'</li>'; }
            echo '</ul></div></div>';
          }
        ?>
            <?php 
              if (!empty($promoCodeInput) && (int)$eff['discount']===0) {
                echo '<div class="text-danger small">Kupon tidak berlaku. Periksa kembali kode atau hapus kupon untuk melanjutkan.</div>';
              } elseif (is_array($eff['rule']) && count($eff['rule'])>0) {
                echo '<div class="small text-muted">Diskon diterapkan: ';
                $parts=[]; foreach ($eff['rule'] as $rr){ $label = isset($rr['code'])&&$rr['code'] ? ($rr['type'].' ('.$rr['code'].')') : ($rr['type'].''); $parts[] = htmlspecialchars($label); }
                echo implode(', ', $parts) . '</div>';
              }
            ?>
				</div>
			</div>
			<?php
		  if (isset($pendingExists) && $pendingExists === true && isset($pendingOrderId) && is_numeric($pendingOrderId)) {
		  	echo '
		  	<div class="card mb-3">
					<div class="card-header">Metode Pembayaran</div>
					<div class="card-body">
						<div class="alert alert-info">Anda sudah memiliki pesanan yang belum dibayar untuk produk ini. Silakan lanjutkan pembayaran melalui invoice yang sudah ada.</div>
						<a href="'.$weburl.'invoice/'.$pendingOrderId.'" class="btn btn-warning">Buka Invoice</a>
					</div>
				</div>';
		  } elseif (!empty($promoCodeInput) && (int)$eff['discount']===0) {
		  	echo '
		  	<div class="card mb-3">
					<div class="card-header">Metode Pembayaran</div>
					<div class="card-body">
						<div class="alert alert-danger">Kupon tidak berlaku. Silakan perbaiki atau hapus kupon untuk melanjutkan pemesanan.</div>
					</div>
				</div>';
		  } elseif ($hargaTampil > 0) {
		  	echo '          
		  	<div class="card mb-3">
					<div class="card-header">
		    	Metode Pembayaran
		  		</div>
		  		<ul class="list-group list-group-flush">';
		  		if (isset($settings['carapembayaran']) && !empty($settings['carapembayaran'])) { 
		  			echo '
		    	<li class="list-group-item">
			    	<div class="form-check">
			    		<input class="form-check-input" type="radio" name="payment" value="manual" required checked aria-checked="true">
			    		<label class="form-check-label" for="flexCheckChecked">
			    		<img src="'.$weburl.'img/bank-transfer.jpg" alt="" style="width:100px; float:left; margin-right:10px"/>
			    		<strong>Transfer Bank</strong>
			    		</label>
			  		</div>
		    	</li>'; 
		  		}

			  	if (isset($settings['tripay_merchant']) && !empty($settings['tripay_merchant'])) {
			    # Daftar Metode Pembayaran
			    $curl = curl_init();

				    curl_setopt_array($curl, array(
				      CURLOPT_FRESH_CONNECT  => true,
				      CURLOPT_URL            => 'https://tripay.co.id/'.$urlapi.'/merchant/payment-channel',
				      CURLOPT_RETURNTRANSFER => true,
				      CURLOPT_HEADER         => false,
				      CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$settings['tripay_api']],
				      CURLOPT_FAILONERROR    => false,
				      CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4
				    ));

				    $response = curl_exec($curl);
				    $error = curl_error($curl);

				    curl_close($curl);

				    $hasil = empty($error) ? $response : $error;
				    $arrhasil = json_decode($hasil,TRUE);
				    if (isset($arrhasil['success']) && $arrhasil['success'] == 1) {
				      
				      foreach ($arrhasil['data'] as $payment) {
				        $fee = $payment['fee_customer']['flat'] + (($payment['fee_customer']['percent']/100) * $hargaTampil);
				        echo '
				        <li class="list-group-item">
				        	<div class="form-check">
							  <input class="form-check-input" type="radio" name="payment" value="'.$payment['code'].'" required>
							  <label class="form-check-label" for="flexCheckChecked">
							    <img src="'.$payment['icon_url'].'" alt="" style="width:100px; float:left; margin-right:10px"/>
				        		<strong>'.$payment['name'].'</strong><br/><small>Fee admin: Rp. '.number_format($fee).'</small>
							  </label>
							</div>
				        </li>';
				      }            
				    } else {
				      echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
				        <strong>Error!</strong> '.$arrhasil['message'].'
				        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
				      </div>';
				    }
			  	}
			  	echo '</ul>
			  	</div>';
			  } else {
			  	echo '
			  	<div class="card mb-3">
					<div class="card-header">Metode Pembayaran</div>
					<div class="card-body">
						<div class="alert alert-success">Produk ini gratis. Tidak perlu memilih metode pembayaran.</div>
					</div>
				</div>';
			  }
				?>
			</div>
		</div>
		</form>
	</div>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
      <script>
    (function(){
      var applyBtn = document.getElementById('applyCoupon');
      var input = document.getElementById('promo_code');
      var base = '<?php echo htmlspecialchars((string)$visiturl, ENT_QUOTES); ?>';
      if (applyBtn && input) {
        applyBtn.addEventListener('click', function(){
          var code = (input.value || '').trim();
          var sep = base.indexOf('?')>=0 ? '&' : '?';
          window.location.href = base + sep + 'promo=' + encodeURIComponent(code);
        });
      }

      // Prevent double submit on slow networks
      var btn = document.getElementById('formsubmit');
      if (btn) {
        btn.addEventListener('click', function(){
          setTimeout(function(){ try { btn.disabled = true; btn.classList.add('disabled'); } catch(e){} }, 0);
        });
      }

      // Disable "Order Sekarang" until a payment method is selected
      document.addEventListener('DOMContentLoaded', function(){
        var submitBtn = document.getElementById('formsubmit');
        if (!submitBtn) return;
        var methods = document.querySelectorAll('input[name="payment"]');
        if (!methods || methods.length === 0) return;
        var hasSelected = false;
        try { hasSelected = Array.prototype.some.call(methods, function(m){ return !!m.checked; }); } catch(e){}
        if (hasSelected) {
          try { submitBtn.disabled = false; submitBtn.classList.remove('disabled'); submitBtn.removeAttribute('aria-disabled'); } catch(e){}
        } else {
          try { submitBtn.disabled = true; submitBtn.classList.add('disabled'); submitBtn.setAttribute('aria-disabled','true'); } catch(e){}
        }
        methods.forEach(function(r){
          r.addEventListener('change', function(){
            try { submitBtn.disabled = false; submitBtn.classList.remove('disabled'); submitBtn.removeAttribute('aria-disabled'); } catch(e){}
          });
        });
      });
    })();
    </script>
</body>
</html>
<?php
	else :
		header("HTTP/1.0 404 Not Found");
		echo '<h1>Not Found</h1>';
	endif;
else :
	header("HTTP/1.0 404 Not Found");
	echo '<h1>Not Found</h1>';
endif;
?>
