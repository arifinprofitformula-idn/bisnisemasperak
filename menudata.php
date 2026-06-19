<?php
$dashfile = 'theme/'.$settings['theme'].'/';
$menu = array(
  'mainmenu' => array(
		'akses' => array('Akses Produk',$dashfile.'dashakses.php'),
		'artikel' => array('Artikel',$dashfile.'saartikel.php'),
		'export' => array('Export',$dashfile.'dashexport.php'),
		'home' => array('Dashboard',$dashfile.'dashhome.php'),
    'invoice' => array('Invoice',$dashfile.'sainvoice.php'),
    'konfirmasi' => array('Konfirmasi Pembayaran',$dashfile.'sakonfirmasi.php'),
		'jaringanmember' => array('Member',$dashfile.'jaringanmember.php'),
		'kategori' => array('kategori',$dashfile.'saartikel.php'),
		'login' => array('Login',$dashfile.'salogin.php'),
		'logout' => array('Logout', $dashfile.'salogout.php'),
		'materi' => array('Materi', $dashfile.'samateri.php'),
		'order' => array('Order',$dashfile.'saorder.php'),
		'linkaff' => array('Link Affiliasi','salinkaff.php'),
		'profil' => array('Profil',$dashfile.'dashprofil.php'),
		'orderanda' => array('Order Anda',$dashfile.'dashmemberorder.php'),
		'kliendetil' => array('Detil Klien',$dashfile.'dashkliendetil.php'),
		'register' => array('Register',$dashfile.'saregister.php'),
		'reset' => array('Reset Password',$dashfile.'sareset.php'),
		'sitemap.xml' => array('Sitemap',$dashfile.'sitemap.php'),
		'robots.txt' => array('Robots',$dashfile.'robots.php')
	),
	'manage' 		=> array(
							'label' 	=> 'Manage',
							'slug' 		=> '#',							
							'submenu' => array(
														'member' => array('Member',$dashfile.'dashmember.php',5),
														'import' => array('Import Data',$dashfile.'dashimport.php',5),
														'orderlist' => array('Order',$dashfile.'dashorder.php',5),
														'manageproduk' => array('Produk',$dashfile.'dashproduk.php',9),
														'page' => array('Landing Page',$dashfile.'dashpage.php',9),
														'bayar' => array('Bayar Komisi',$dashfile.'dashbayar.php',5),														
                                                        'lapkeuangan' => array('Keuangan',$dashfile.'dashlapkeuangan.php',6)
														)
							),
	'settings' 	=>  array(
							'label' 	=> 'Settings',
							'slug' 		=> '#',
							'submenu' => array(
														'setting' => array('Umum',$dashfile.'dashsetting.php',9),
														'form' => array('Formulir',$dashfile.'dashform.php',9),														
														'email' => array('Email',$dashfile.'dashemail.php',9),
														'whatsapp' => array('WhatsApp',$dashfile.'dashwhatsapp.php',9),
														'payment' => array('Payment Gateway',$dashfile.'dashpayment.php',9),
														'plugin' => array('Plugin',$dashfile.'dashplugin.php',9),
														'theme' => array('Theme',$dashfile.'dashtheme.php',9),
														'homebuilder' => array('Home Builder',$dashfile.'dashhomebuilder.php',9),
														'tutorial' => array('Tutorial',$dashfile.'dashtutorial.php',9),
														'upgrade' => array('Upgrade SA+',$dashfile.'dashupgrade.php',9)
														)
							),
	'integrasi'	=>  array(
							'label' 	=> 'Integrasi',
							'slug' 		=> '#',
							'submenu' => array(
														'autoresponder' => array('Autoresponder',$dashfile.'dashautoresponder.php',9),	
														'mailketing' => array('Mailketing API','admin/mailketing_config.php',9),
														'wafucb' => array('WAFUCB',$dashfile.'dashwafucb.php',9)
														)
							),
	'membermenu'	=>  array(
							'label' 	=> 'Memberarea',
							'slug' 		=> '#',
							'submenu' => array(														
														'klien' => array('Klien',$dashfile.'dashmemberklien.php',1),														
														'jaringan' => array('Jaringan',$dashfile.'dashmemberjaringan.php',1),
														'laporankomisi' => array('Komisi',$dashfile.'dashmemberkomisi.php',1),
														'produklist' => array('Produk',$dashfile.'dashmemberproduk.php',1),
														'orderanda' => array('Order Anda',$dashfile.'dashmemberorder.php',1),
                                                        'epichub' => array('AI CONTENT MAKER',$dashfile.'epichub_access.php',1)
														)
							)	
);

# Ok STOP di sini editnya

if (isset($settings['url_artikel'])) {
	$menu[$settings['url_artikel']] = array('label' => ucwords($settings['url_artikel']),
											'slug' => $settings['url_artikel'],
											'file' => $dashfile.'saartikel.php');
}

if (isset($settings['url_produk']) && !empty($settings['url_produk'])) {
	$menu[$settings['url_produk']] = array('label' => ucwords($settings['url_produk']),
										'slug' => $settings['url_produk'],
										'file' => $dashfile.'saproduk.php');
}

// Alias bawaan untuk akses langsung /product
// Memastikan halaman daftar produk dapat diakses tanpa kode referral di path umum "product"
if (!isset($menu['product'])) {
    $menu['product'] = array('label' => 'Katalog Produk', 'slug' => 'product', 'file' => $dashfile.'saproduk.php');
} else {
    if (isset($menu['product']['label'])) { $menu['product']['label'] = 'Katalog Produk'; }
}

if (isset($settings['url_materi'])) {
	$menu[$settings['url_materi']] = array('label' => ucwords($settings['url_materi']),
											'slug' => $settings['url_materi'],
											'file' => $dashfile.'samateri.php');
}

$menu = apply_filter('menu',$menu);
