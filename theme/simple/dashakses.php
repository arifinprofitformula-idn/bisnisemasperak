<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
# Cek apakah sudah beli atau belum
if (isset($datamember['mem_id'])) {
	if (isset($slug[3]) && !empty($slug[3])) {
		$produk = db_row("SELECT * FROM `sa_page` WHERE `page_url`='".cek($slug[3])."'");
		$hargaTampil = (isset($produk['pro_harga_display']) && $produk['pro_harga_display'] !== '' ? $produk['pro_harga_display'] : $produk['pro_harga']);
		$freeOrder = db_row("SELECT `order_id`,`order_status` FROM `sa_order` WHERE `order_idproduk`=".(int)$produk['page_id']." AND `order_idmember`=".(int)$datamember['mem_id']." AND (`order_status`=1 OR (`order_hargaunik`=0 AND `order_trx`='free')) ORDER BY `order_id` DESC LIMIT 1");
		if ( ($hargaTampil == 0)
			|| ((isset($produk['pro_free_access']) && $produk['pro_free_access'] == 1))
			|| (isset($freeOrder['order_id']))
			|| (db_var("SELECT `order_status` FROM `sa_order` WHERE `order_idproduk`=".$produk['page_id']." AND `order_idmember`=".$datamember['mem_id']) == 1) ) {
			if (isset($freeOrder['order_id']) && (int)($freeOrder['order_status'] ?? 0) === 0) {
				$updates = "`order_status`=1, `order_trx`='free', `order_hargaunik`=0";
				if (db_var("SHOW COLUMNS FROM `sa_order` LIKE 'order_price_display'")) { $updates .= ", `order_price_display`=0"; }
				if (db_var("SHOW COLUMNS FROM `sa_order` LIKE 'order_discount'")) { $updates .= ", `order_discount`=0"; }
				db_query("UPDATE `sa_order` SET ".$updates." WHERE `order_id`=".(int)$freeOrder['order_id']);
			}
			// Jika produk gratis atau free-access, pastikan membuat order otomatis (untuk konsistensi dan notifikasi)
			if (($hargaTampil == 0) || (isset($produk['pro_free_access']) && $produk['pro_free_access'] == 1)) {
				$cekOrder = db_row("SELECT * FROM `sa_order` WHERE `order_idproduk`=".$produk['page_id']." AND `order_idmember`=".$datamember['mem_id']);
				if (!isset($cekOrder['order_id'])) {
					$idsponsor = db_var("SELECT `sp_sponsor_id` FROM `sa_sponsor` WHERE `sp_mem_id`=".$datamember['mem_id']);
					$hasPriceDisplay = db_var("SHOW COLUMNS FROM `sa_order` LIKE 'order_price_display'");
					$hasDiscount = db_var("SHOW COLUMNS FROM `sa_order` LIKE 'order_discount'");
					$hasPromoCode = db_var("SHOW COLUMNS FROM `sa_order` LIKE 'order_promo_code'");
					$cols = "`order_idmember`,`order_idsponsor`,`order_idproduk`,`order_tglorder`,`order_harga`,`order_hargaunik`,`order_trx`,`order_status`";
					$hargaNormal = (isset($produk['pro_harga']) && is_numeric($produk['pro_harga'])) ? (int)$produk['pro_harga'] : 0;
					$vals = (int)$datamember['mem_id'].",".(int)$idsponsor.",".(int)$produk['page_id'].",'".date('Y-m-d H:i:s')."',".$hargaNormal.",0,'free',1";
					if ($hasPriceDisplay) { $cols .= ",`order_price_display`"; $vals .= ",0"; }
					if ($hasDiscount) { $cols .= ",`order_discount`"; $vals .= ",0"; }
					if ($hasPromoCode) { $cols .= ",`order_promo_code`"; $vals .= ",'".cek('')."'"; }
					$idorder = db_insert("INSERT INTO `sa_order` (".$cols.") VALUES (".$vals.")");
					if (is_numeric($idorder)) {
						$datalain = array(
							'idorder'   => $idorder,
							'hrgunik'   => 0,
							'hrgproduk' => $produk['pro_harga'],
							'namaproduk'=> $produk['page_judul'],
							'urlproduk' => $produk['page_url']
						);
						sa_notif('aksesgratis',$datamember['mem_id'],$datalain);
					}
				} else {
					// Jika order sudah ada namun belum aktif, set aktif jika akses gratis
					if (isset($cekOrder['order_id']) && $cekOrder['order_status'] == 0) {
						$updates = "`order_status`=1, `order_trx`='free', `order_hargaunik`=0";
						if (db_var("SHOW COLUMNS FROM `sa_order` LIKE 'order_price_display'")) { $updates .= ", `order_price_display`=0"; }
						db_query("UPDATE `sa_order` SET ".$updates." WHERE `order_id`=".(int)$cekOrder['order_id']);
					}
				}
			}
			if (substr($produk['pro_file'], 0,4) == 'http') {
				header("Location:".$produk['pro_file']);
			} else {
				$token = generateDownloadToken($produk['pro_file']);
				#echo $_SERVER['REMOTE_ADDR'];
				header("Location:".$weburl.'download.php?f='.$produk['pro_file'].'&id='.$token);
				echo $weburl.'download.php?f='.$produk['pro_file'].'&id='.$token;
			}
		} else {
			echo 'Anda belum order produk ini';
		}
	}
} else {
	echo 'Belum Login';
}
