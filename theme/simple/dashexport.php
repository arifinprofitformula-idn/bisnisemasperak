<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if (isset($_GET['data'])) {
	if ($_GET['data'] == 'member' || $_GET['data'] == 'klien') {
		# Download data seluruh member

		if ($_GET['data'] == 'member') {
			if ($datamember['mem_role'] < 9) { die(); exit(); }
			$data = db_select("SELECT `m`.*,`s`.`mem_nama` AS `NamaSponsor` FROM `sa_member` `m` 
				LEFT JOIN `sa_sponsor` `k` ON `m`.`mem_id` = `k`.`sp_mem_id` 
				LEFT JOIN `sa_member` `s` ON `k`.`sp_sponsor_id` = `s`.`mem_id` 
				ORDER BY `m`.`mem_tgldaftar` DESC");
			$filedata = 'member-';
		} else {
			$data = db_select("SELECT `m`.*,`s`.`mem_nama` AS `NamaSponsor` FROM `sa_member` `m` 
				LEFT JOIN `sa_sponsor` `k` ON `m`.`mem_id` = `k`.`sp_mem_id` 
				LEFT JOIN `sa_member` `s` ON `k`.`sp_sponsor_id` = `s`.`mem_id`
				WHERE `k`.`sp_sponsor_id`=".$iduser." 
				ORDER BY `m`.`mem_tgldaftar` DESC");
			$filedata = 'klien-';			
		}

		if (count($data) > 0) {
			$member[0] = array('ID','Nama Lengkap','Alamat Email','No. Whatsapp','URL Affiliasi','Tgl. Daftar','Tgl. Upgrade','Login Terakhir','Status');
			$fieldlist = array('id','nama','email','whatsapp','kodeaff','tgldaftar','tglupgrade','lastlogin','statusmember');
			$form = db_select("SELECT * FROM `sa_form` WHERE `ff_field` NOT IN ('nama','email','whatsapp','kodeaff','password','sponsor')");
			if (count($form) > 0) {
				foreach ($form as $form) {
					array_push($member[0], $form['ff_label']);
					array_push($fieldlist, $form['ff_field']);
				}
			}
			array_push($member[0],'Nama Sponsor');
			array_push($fieldlist,'NamaSponsor');
			$i = 1;
			echo '<pre>';
			foreach ($data as $data) {
				$extdata = extractdata($data);
				#print_r($extdata);
				$member[$i] = array();
				foreach ($fieldlist as $field) {
					if (isset($extdata[$field])) { $insdata = $extdata[$field]; } else { $insdata = ''; }
					array_push($member[$i],$insdata);					
				}
				$i++;
			}			
			
			include('xlsxgen.php');
			$namafile = $filedata.date('Ymd').'.xlsx';
			$xlsx = SimpleXLSXGen::fromArray( $member );
			$xlsx->downloadAs($namafile);			
			
		}

	} elseif ($_GET['data'] == 'settings') {
		# Download seluruh setting kecuali data member
		if ($datamember['mem_role'] < 9) { die(); exit(); }
	}
}