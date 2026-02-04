<?php
#include('fungsi.php');
if (!db_var("show tables like 'sa_member'")) {	
	# CREATE TABEL
	db_query("CREATE TABLE `sa_member` (
		`mem_id` bigint(20) NOT NULL auto_increment,
		`mem_nama` varchar(50) NOT NULL default '',
		`mem_email` varchar(50) NULL default '',		
		`mem_password` varchar(100) NOT NULL default '',
		`mem_whatsapp` varchar(50) NULL,
		`mem_kodeaff` varchar(50) NULL,
		`mem_datalain` text NULL,
		`mem_tgldaftar` datetime NOT NULL default current_timestamp(),
		`mem_tglupgrade` datetime NULL,
		`mem_lastlogin` datetime NULL,
		`mem_status` varchar(1) NOT NULL default '0',
		`mem_role` varchar(1) NOT NULL default '1', # 1: member, 2: staff, 9: admin
		`mem_confirm` VARCHAR(6) NULL,
		PRIMARY KEY  (`mem_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

	db_query("CREATE TABLE `sa_sponsor` (
		`sp_id` bigint(20) NOT NULL auto_increment,
		`sp_mem_id` varchar(50) NOT NULL default '',
		`sp_sponsor_id` varchar(50) NOT NULL default '',
		`sp_network` text NULL,
		PRIMARY KEY  (`sp_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
	
	db_query("CREATE TABLE `sa_page` (
		`page_id` bigint(20) NOT NULL auto_increment,
		`page_judul` varchar(50) NOT NULL default '',
		`page_diskripsi` varchar(200) NOT NULL default '',
		`page_url` varchar(50) NOT NULL default '',
		`page_iframe` varchar(100) NOT NULL default '',
		`page_method` varchar(50) NOT NULL default '1', # 1 - Pakai iframe, 2 - Redirect
		`pro_harga` int(11) DEFAULT NULL,
	  `pro_komisi` text NULL,
	  `pro_file` varchar(200) DEFAULT NULL,
	  `pro_status` varchar(1) NOT NULL DEFAULT '1' COMMENT '0: tdk aktif; 1 aktif',
	  `pro_img` VARCHAR(50) NULL,
	  `page_fr` text,  
		PRIMARY KEY  (`page_id`),
		UNIQUE(`page_url`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
	
	db_query("CREATE TABLE `sa_setting` (
		`set_id` bigint(20) NOT NULL auto_increment,
		`set_label` varchar(50) NOT NULL default '',
		`set_value` text NULL,		
		PRIMARY KEY  (`set_id`),
		UNIQUE(`set_label`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

	db_query("CREATE TABLE `sa_form` (
	  `ff_id` int(11) NOT NULL AUTO_INCREMENT,
	  `ff_label` varchar(255) NOT NULL,
	  `ff_field` varchar(255) NOT NULL,
	  `ff_type` varchar(255) NOT NULL,
	  `ff_keterangan` text,
	  `ff_options` text,
	  `ff_profil` char(1) NOT NULL DEFAULT '0',
	  `ff_registrasi` char(1) NOT NULL DEFAULT '0',
	  `ff_network` char(1) NOT NULL DEFAULT '0',
	  `ff_required` char(1) NOT NULL DEFAULT '0',
	  `ff_sort` int(11) NOT NULL DEFAULT '0',
	  PRIMARY KEY (`ff_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

	db_query("CREATE TABLE `sa_order` (
		`order_id` bigint(20) NOT NULL auto_increment,		
		`order_idmember` bigint(20) NOT NULL default '0',
		`order_idsponsor` bigint(20) NOT NULL default '0',
		`order_idproduk` bigint(20) NOT NULL default '0',
		`order_tglorder` datetime NOT NULL default current_timestamp(),
		`order_tglbayar` datetime NULL,
		`order_tglexpired` datetime NULL,
		`order_harga` bigint(20) NOT NULL default '0',
		`order_hargaunik` bigint(20) NOT NULL default '0',
		`order_status` varchar(1) NOT NULL default '0',
		`order_idstaff` bigint(20) NOT NULL default '0',
		`order_trx` VARCHAR(50) NULL,
		PRIMARY KEY (`order_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

	db_query("CREATE TABLE `sa_laporan` (
	  `lap_id` bigint(20) NOT NULL auto_increment,
	  `lap_idorder` bigint(20) NOT NULL DEFAULT 0,
	  `lap_idmember` bigint(20) NOT NULL DEFAULT 0,
	  `lap_idsponsor` bigint(20) NOT NULL DEFAULT 0,
	  `lap_tanggal` datetime NOT NULL DEFAULT current_timestamp(),
	  `lap_masuk` bigint(20) NOT NULL DEFAULT 0,
	  `lap_keluar` bigint(20) NOT NULL DEFAULT 0,
	  `lap_code` varchar(1) NOT NULL DEFAULT '', # 1: Lap keuangan admin; 2: Lap komisi member;
	  `lap_keterangan` text NULL,
	  `lap_level` varchar(1) NOT NULL DEFAULT '0',
	  `lap_app` varchar(5) NOT NULL default 'SA',
	  PRIMARY KEY (`lap_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

	db_query("CREATE TABLE `sa_kategori` (
		`kat_id` bigint(20) NOT NULL auto_increment,							
		`kat_parent_id` varchar(50) NOT NULL default '',
		`kat_nama` varchar(100) NULL,
		`kat_slug` varchar(100) NULL,
		PRIMARY KEY  (`kat_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

	db_query("CREATE TABLE `sa_visitor` (
	    `id_sponsor` INT NOT NULL,
	    `visit_date` DATE NOT NULL,
	    `count` INT DEFAULT 0,
	    PRIMARY KEY (`id_sponsor`, `visit_date`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

	db_query("CREATE TABLE `sa_artikel` (
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
		`art_writer` bigint(20) NOT NULL,
		PRIMARY KEY  (`art_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
	
	# CREATE CONTENT
	$passadmin = 'simpleaff123';
	db_insert("INSERT INTO `sa_member` (`mem_id`,`mem_nama`,`mem_email`,`mem_password`,`mem_whatsapp`,`mem_kodeaff`,`mem_datalain`,`mem_tgldaftar`,`mem_tglupgrade`,`mem_lastlogin`,`mem_status`,`mem_role`) 
		VALUES (1,'Administrator','admin@yourdomain.com','".create_hash($passadmin)."','08123456789','admin','','".date('Y-m-d H:i:s')."','".date('Y-m-d H:i:s')."','".date('Y-m-d H:i:s')."',2,9)");
	db_insert ("INSERT INTO `sa_sponsor` (`sp_mem_id`,`sp_sponsor_id`) VALUES (1,0)");
	db_query ("INSERT INTO `sa_form` 
		(`ff_label`,`ff_field`,`ff_type`,`ff_keterangan`,`ff_options`,`ff_profil`,`ff_registrasi`,`ff_network`,`ff_required`,`ff_sort`) 
		VALUES 
		('Foto Profil','fotoprofil','file','Maksimal ukuran file 1Mb','',1,1,0,0,1),
		('Nama Lengkap','nama','text','','',1,1,1,1,2),
		('Alamat Email','email','email','','',1,1,1,1,3),
		('No. WhatsApp','whatsapp','number','','',1,1,1,1,4),
		('Password','password','password','','',1,1,0,0,5),
		('Jenis Kelamin','gender','select','','Pria"."\n"."Wanita',1,1,1,0,6),
		('URL Affiliasi','kodeaff','kodeaff','','',1,0,1,0,7),
		('Rekening','rekening','textarea','Masukkan data rekening lengkap (Nama bank, cabang, nomor rekening, atas nama)','',1,0,1,0,8)
		");
	db_query("INSERT INTO `sa_setting` (`set_label`,`set_value`) VALUES 		
		('judulweb','Simple Aff Plus by Cafebisnis'),
		('diskripsiweb','Jual produk digital dengan system yang ringan dan sederhana'),
		('homepage','".$weburl."salesletter/contoh'),
		('theme','simple'),
		('judul_daftar_member','Terima kasih telah mendaftar ".$weburl."'),
		('isi_daftar_member','<p>Terima kasih telah bergabung bersama ".$weburl.". Berikut data diri anda:</p>

<p>Nama: [member_nama]<br />
Email: [member_email]<br />
Password: [member_password]</p>

<p>Sponsor Anda:</p>

<p>[sponsor_nama]<br />
[sponsor_whatsapp]</p>

<p>Silahkan login ke ".$weburl."dashboard</p>
'),
		('judul_daftar_sponsor','Ada yang daftar nih'),
		('isi_daftar_sponsor','<p>Mantap!! Ada yang daftar lewat link affiliasi kakak nih. Yuk kita follow up. Berikut datanya:</p>

<p>Nama: <strong>[member_nama]</strong><br />
Email: <strong>[member_email]</strong></p>

<p>Ayoo sebarkan terus link affiliasi kakak di <strong>[sponsor_kodeaff]</strong></p>
'),
		('judul_upgrade_member','Yeeyy!! Welcome to the Club!'),
		('isi_upgrade_member','<p>Great !! Senang banget akhirnya kak [member_nama] mau bergabung. Yuk bersama kita raih kesuksesan di https://simpleaff.my.id</p>

<p>Jika kak [member_nama] punya pertanyaan seputar web ini, jangan sungkan tanya kepada admin atau sponsor kakak yaitu:</p>

<p>Nama : <strong>[sponsor_nama]</strong><br />
Email: <strong>[sponsor_email]</strong></p>

<p>Sukses selalu ya kak!</p>
'),
		('judul_upgrade_sponsor','Yeeey !! [member_nama] akhirnya bergabung!'),
		('isi_upgrade_sponsor','<p>Joss banget kak! Kak [member_nama] akhirnya bergabung. Yuk kita bantu beliau biar bisa sukses bersama. Berikut datanya:</p>

<p>Nama : [member_nama]<br />
Email: [member_email]</p>

<p>Yuk terus sebarkan link affiliasi kakak di&nbsp;<strong>[sponsor_kodeaff]</strong></p>
')
		");

	echo '
<h1>Install done!</h1>
<p>Silahkan login ke <a href="'.$weburl.'dashboard">'.$weburl.'dashboard</a> dengan data berikut:<br/>
Email: admin@yourdomain.com<br/>
Password: '.$passadmin.'</p>

<p>Anda bisa mengganti email dan password di menu profil</p>
<p>'.db_error().'</p>';

}