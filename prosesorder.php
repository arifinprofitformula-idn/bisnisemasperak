<?php
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if (isset($idinvoice) && is_numeric($idinvoice)) :
$proses = db_row("SELECT * FROM `sa_order`
			LEFT JOIN `sa_member` ON `sa_member`.`mem_id` = `sa_order`.`order_idmember`
			LEFT JOIN `sa_sponsor` ON `sa_sponsor`.`sp_mem_id`= `sa_order`.`order_idmember`
			LEFT JOIN `sa_page` ON `sa_page`.`page_id` = `sa_order`.`order_idproduk`
			WHERE `sa_order`.`order_status` = 0 AND `sa_order`.`order_id`=".$idinvoice);
if (isset($proses['order_id'])) {
	# Update data order
	db_query("UPDATE `sa_order` SET `order_status`=1,`order_idstaff`=".$staff.",`order_tglbayar`='".date('Y-m-d H:i:s')."' WHERE `order_id`=".$proses['order_id']);
	
	# Update Status Member jadi Premium
	if ($proses['mem_status'] < 2 && $proses['pro_harga'] > 0) {
		db_query("UPDATE `sa_member` SET `mem_status`=2,`mem_tglupgrade`='".date('Y-m-d H:i:s')."' WHERE `mem_id`=".$proses['mem_id']);
	}

	$keterangan = 'Penjualan '.$proses['page_judul'];
	$ins = "(".$proses['order_id'].",".$proses['order_idmember'].",".$proses['order_idsponsor'].",'".date('Y-m-d H:i:s')."',".$proses['order_hargaunik'].",0,1,'".$keterangan."',0,'SA'),";
	# Dapatkan data upline
	if (!empty($proses['sp_network'])) {
		$network = str_replace('][', ',', $proses['sp_network']);
		$network = substr($network, 1,-1);
		if (!empty($network)) {
			$upline = db_select("SELECT * FROM `sa_member` WHERE `mem_id` IN (".$network.") ORDER BY FIELD(`mem_id`,".$network.")");

            if (count($upline) > 0) {
                $komisi = unserialize($proses['pro_komisi']);
                $komisiType = (isset($komisi['type']) && in_array($komisi['type'], array('percent','fixed'))) ? $komisi['type'] : 'fixed';
                $hargaOrder = (isset($proses['order_hargaunik']) && (int)$proses['order_hargaunik'] > 0) ? (int)$proses['order_hargaunik'] : (isset($proses['order_harga']) ? (int)$proses['order_harga'] : 0);
                $lvl = 1;
                
                foreach ($upline as $upline) {
                    $baseVal = 0;
                    if ($upline['mem_status'] >= 2) {
                        $baseVal = isset($komisi['premium'][$lvl]) ? (float)$komisi['premium'][$lvl] : 0;
                    } else {
                        $baseVal = isset($komisi['free'][$lvl]) ? (float)$komisi['free'][$lvl] : 0;
                    }

                    // Hitung kredit sesuai tipe komisi
                    $kredit = 0;
                    if ($komisiType === 'percent') {
                        // Pastikan persentase tidak melebihi 100
                        $pct = max(0.0, min(100.0, $baseVal));
                        $kredit = (int) floor(($hargaOrder * $pct) / 100.0);
                    } else {
                        $kredit = (int) max(0, $baseVal);
                    }

                    if ($kredit > 0) {
                        $ketType = ($komisiType === 'percent') ? (rtrim(rtrim(number_format($baseVal,2,'.',''), '0'),'.').'%') : ('Rp '.number_format((int)$baseVal));
                        $keterangan = 'Komisi Penjualan '.$proses['page_judul'].' ['.$ketType.' dari Rp '.number_format($hargaOrder).', nominal Rp '.number_format($kredit).' L'.$lvl.']';
                        $ins .= "(".$proses['order_id'].",".$proses['order_idmember'].",".$upline['mem_id'].",'".date('Y-m-d H:i:s')."',".$kredit.",0,2,'".$keterangan."',".$lvl.",'SA'),";
                    }
                    $lvl++;
                }           
            }
		}
	}

    // Komisi Kontributor Multi (jika ada konfigurasi untuk produk)
    $contribs = db_select("SELECT * FROM `epi_product_contrib` WHERE `page_id`=".(int)$proses['order_idproduk']);
    if (is_array($contribs) && count($contribs) > 0) {
        $hargaOrder = (isset($proses['order_hargaunik']) && (int)$proses['order_hargaunik'] > 0) ? (int)$proses['order_hargaunik'] : (isset($proses['order_harga']) ? (int)$proses['order_harga'] : 0);
        foreach ($contribs as $contrib) {
            if (!isset($contrib['member_id']) || (int)$contrib['member_id'] <= 0) { continue; }
            $konMember = db_row("SELECT `mem_status` FROM `sa_member` WHERE `mem_id`=".(int)$contrib['member_id']);
            if (!$konMember || (int)$konMember['mem_status'] < 2) { continue; } // hanya Premium
            $ctype = (isset($contrib['type']) && in_array($contrib['type'], array('percent','fixed'))) ? $contrib['type'] : 'fixed';
            $cval = isset($contrib['value']) ? (float)$contrib['value'] : 0.0;
            $ckredit = 0;
            if ($ctype === 'percent') { $ckredit = (int) floor($hargaOrder * max(0.0, min(100.0, $cval)) / 100.0); }
            else { $ckredit = (int) max(0, $cval); }
            if ($ckredit > 0) {
                $ketType = ($ctype === 'percent') ? (rtrim(rtrim(number_format($cval,2,'.',''), '0'),'.').'%') : ('Rp '.number_format((int)$cval));
                $keterangan = 'Komisi Kontributor '.$proses['page_judul'].' ['.$ketType.' dari Rp '.number_format($hargaOrder).', nominal Rp '.number_format($ckredit).']';
                $ins .= "(".$proses['order_id'].",".$proses['order_idmember'].",".(int)$contrib['member_id'].",'".date('Y-m-d H:i:s')."',".$ckredit.",0,3,'".$keterangan."',0,'CONTRIB'),";
            }
        }
    }
	
	$cek = db_query("INSERT INTO `sa_laporan` (`lap_idorder`,`lap_idmember`,`lap_idsponsor`,`lap_tanggal`,`lap_masuk`,`lap_keluar`,`lap_code`,`lap_keterangan`,`lap_level`,`lap_app`) 
		VALUES ".substr($ins,0,-1));
	if ($cek === false) {
		echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
		  <strong>Error!</strong> '.db_error().'
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	} else {
		
		# Kirim Notif yuk
		// Hitung shortcodes komisi untuk Sponsor
		$komisiProdukVal = 0; $komisiTotalMasuk = 0; $komisiTotalKeluar = 0; $komisiPotensiVal = 0;
		// Siapkan URL Akses (Nama File / URL Akses) untuk member
		$urlAkses = '';
		if (isset($proses['pro_file'])) {
			$proFileVal = trim($proses['pro_file']);
			if ($proFileVal === '') {
				// Fallback: akses via halaman dashboard jika field kosong
				$urlAkses = $weburl.'dashboard/akses/'.($proses['page_url'] ?? '');
			} elseif (substr($proFileVal, 0, 4) === 'http') {
				// Link eksternal penuh dari backoffice
				$urlAkses = $proFileVal;
			} else {
				// File lokal: buat token download agar aman
				$token = generateDownloadToken($proFileVal);
				$urlAkses = $weburl.'download.php?f='.$proFileVal.'&id='.$token;
			}
		}

		// Komisi produk untuk sponsor level-1 berdasarkan status sponsor (premium/free)
		if (isset($proses['order_idsponsor']) && is_numeric($proses['order_idsponsor'])) {
			$sponsorStatus = db_row("SELECT `mem_status` FROM `sa_member` WHERE `mem_id`=".$proses['order_idsponsor']);
			if (isset($proses['pro_komisi']) && !empty($proses['pro_komisi'])) {
				$komisiSet = @unserialize($proses['pro_komisi']);
                if (is_array($komisiSet)) {
                    $komisiType = (isset($komisiSet['type']) && in_array($komisiSet['type'], array('percent','fixed'))) ? $komisiSet['type'] : 'fixed';
                    $level1Premium = isset($komisiSet['premium'][1]) ? (float)$komisiSet['premium'][1] : 0.0;
                    $level1Free    = isset($komisiSet['free'][1]) ? (float)$komisiSet['free'][1] : 0.0;
                    $baseVal = ((isset($sponsorStatus['mem_status']) && (int)$sponsorStatus['mem_status'] >= 2) ? $level1Premium : $level1Free);
                    if ($komisiType === 'percent') {
                        $pct = max(0.0, min(100.0, $baseVal));
                        $basisBayar = (isset($proses['order_hargaunik']) && (int)$proses['order_hargaunik'] > 0) ? (int)$proses['order_hargaunik'] : (int)$proses['order_harga'];
                        $komisiProdukVal = (int) floor($basisBayar * $pct / 100.0);
                    } else {
                        $komisiProdukVal = (int) max(0, $baseVal);
                    }
                }
			}
			// Total komisi sponsor (akumulasi masuk/keluar)
			$tot = db_row("SELECT COALESCE(SUM(`lap_masuk`),0) AS `masuk`, COALESCE(SUM(`lap_keluar`),0) AS `keluar` FROM `sa_laporan` WHERE `lap_code`=2 AND `lap_idsponsor`=".$proses['order_idsponsor']);
			$komisiTotalMasuk  = isset($tot['masuk']) ? (int)$tot['masuk'] : 0;
			$komisiTotalKeluar = isset($tot['keluar']) ? (int)$tot['keluar'] : 0;
			$komisiPotensiVal  = $komisiTotalMasuk - $komisiTotalKeluar;
		}

		$datalain = array(
			'idorder' => $proses['order_id'],
			'hrgunik' => number_format($proses['order_hargaunik']),
			'hrgproduk' => number_format($proses['order_harga']),
			'namaproduk' => $proses['page_judul'],
			'urlproduk' => $proses['page_url'],
			// Shortcode akses untuk Member
			'urlakses' => $urlAkses,
			// Shortcode komisi untuk Sponsor
			'komisiproduk'    => 'Rp '.number_format($komisiProdukVal),
			'komisitotal'     => 'Rp '.number_format($komisiTotalMasuk),
			'komisiditransfer'=> 'Rp '.number_format($komisiTotalKeluar),
			'komisipotensi'   => 'Rp '.number_format(max(0,$komisiPotensiVal))
		);
		sa_notif('prosesorder',$proses['order_idmember'],$datalain);

		echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
		  <strong>Ok!</strong> Terima kasih. Order '.$proses['order_id'].' telah diproses 🙏
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	}
} else {
	echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
	  <strong>Error!</strong> Order tidak ditemukan. Mungkin sudah dihapus atau sudah diproses sebelumnya.
	  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
	</div>';
}

endif;
