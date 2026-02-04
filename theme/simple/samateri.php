<?php
if (isset($slug[2]) && !empty($slug[2])) :
	if ($iduser = is_login()) :
		if (is_numeric($slug[2])) {
			$single = db_row("SELECT * FROM `sa_artikel` WHERE `art_id` = '".$slug[2]."'");
			if (isset($single['art_id'])) {
				$showtxt = '<h2>'.$single['art_judul'].'</h2>'.$single['art_konten'];
				// Handle multi-product
				$prodIds = explode(',', (string)$single['art_product']);
				$firstId = (int)($prodIds[0] ?? 0);
				$produk = db_row("SELECT * FROM `sa_page` WHERE `page_id`='".$firstId."'");
				$showtitle = $single['art_judul'];
			}
		} else {
			$produk = db_row("SELECT * FROM `sa_page` WHERE `page_url`='".$slug[2]."'");
			if (isset($produk['page_judul'])) {
				$showtitle = $produk['page_judul'];
			} else {
				$showtitle = 'Not Found';
			}
		}
		
	
		if (isset($produk['page_id'])) {
			$hasFreeAccess = ((int)($produk['pro_free_access'] ?? 0) === 1);
			$cekorder = db_row("SELECT * FROM `sa_order` WHERE `order_idproduk`=".(int)$produk['page_id']." AND `order_idmember`=".(int)$iduser." AND (`order_status`=1 OR (`order_hargaunik`=0 AND `order_trx`='free')) ORDER BY `order_id` DESC LIMIT 1");
			if ($hasFreeAccess || isset($cekorder['order_id'])) {
				$artikelList = db_select("SELECT * FROM `sa_artikel` LEFT JOIN `sa_kategori` ON `sa_kategori`.`kat_id`=`sa_artikel`.`art_kat_id` 
				WHERE FIND_IN_SET('".(int)$produk['page_id']."', `art_product`) ORDER BY `art_judul`");
                if (count($artikelList) > 0) {
                    $flatList = [];
                    foreach ($artikelList as $artikel) {
                        if (!isset($list[$artikel['kat_id']])) { 
                            $list[$artikel['kat_id']]['judul'] = $artikel['kat_nama'];
                            $list[$artikel['kat_id']]['artikel'] = ''; 
                        }
                        $materiUrl = $weburl.(isset($settings['url_materi']) ? $settings['url_materi'] : 'materi').'/'.$artikel['art_id'];
                        $judulSafe = htmlspecialchars($artikel['art_judul']);
                        // Simpan list flat untuk navigasi prev/next
                        $flatList[] = [
                          'id' => $artikel['art_id'],
                          'url' => $materiUrl,
                          'judul' => $artikel['art_judul']
                        ];
                        if (isset($single['art_id']) && $single['art_id'] == $artikel['art_id']) {
                            // Item aktif (materi yang sedang dilihat)
                            $list[$artikel['kat_id']]['artikel'] .= '<li class="materi-item active" aria-current="true"><div class="materi-link d-flex align-items-center"><span class="icon"><i class="fa-solid fa-play"></i></span><span class="title">'.$judulSafe.'</span></div></li>';
                        } else {
                            // Item materi lain, klik mengarah ke materi terkait
                            $list[$artikel['kat_id']]['artikel'] .= '<li class="materi-item"><a href="'.$materiUrl.'" class="materi-link d-flex align-items-center"><span class="icon"><i class="fa-solid fa-play"></i></span><span class="title">'.$judulSafe.'</span></a></li>';
                        }
                    }
					#$showtxt .= '<h3>Materi '.$produk['page_judul'].'</h3>';
					if (isset($list) && count($list) > 0) {
						$menumateri = '';
						foreach ($list as $key => $value ) {
							$menumateri .= '
							<div class="card mb-3">
								<div class="card-header info" style="cursor: pointer;" data-target="konten_'.$key.'">
									<h5>'.$value['judul'].'</h5>
								</div>
                                <div class="card-body konten_'.$key.' konten">
                                    <ol class="materi-list list-unstyled mb-0">
                                        '.$value['artikel'].'
                                    </ol>
                                </div>
                            </div>';
							if (!isset($first)) { $first = $key; }
						}

						if (isset($single['art_kat_id'])) { $first = $single['art_kat_id']; }

                        // Tambahkan tombol navigasi Prev/Next pada konten aktif
                        if (isset($single['art_id']) && count($flatList) > 0) {
                          $currentIndex = null; $prevUrl = null; $nextUrl = null;
                          for ($i = 0; $i < count($flatList); $i++) {
                            if ($flatList[$i]['id'] == $single['art_id']) { $currentIndex = $i; break; }
                          }
                          if ($currentIndex !== null) {
                            if ($currentIndex > 0) { $prevUrl = $flatList[$currentIndex - 1]['url']; }
                            if ($currentIndex < count($flatList) - 1) { $nextUrl = $flatList[$currentIndex + 1]['url']; }
                          }
                          $prevAttr = $prevUrl ? 'href="'.htmlspecialchars($prevUrl).'"' : 'href="#" aria-disabled="true" tabindex="-1"';
                          $nextAttr = $nextUrl ? 'href="'.htmlspecialchars($nextUrl).'"' : 'href="#" aria-disabled="true" tabindex="-1"';
                          $prevClass = 'btn btn-primary btn-sm materi-nav-btn prev' . ($prevUrl ? '' : ' disabled');
                          $nextClass = 'btn btn-primary btn-sm materi-nav-btn next' . ($nextUrl ? '' : ' disabled');
                          $navHtml = '
                            <div class="materi-nav d-flex justify-content-between align-items-center mt-3">
                              <a '.$prevAttr.' class="'. $prevClass .'"><i class="fa-solid fa-arrow-left"></i> Sebelumnya</a>
                              <a '.$nextAttr.' class="'. $nextClass .'">Berikutnya <i class="fa-solid fa-arrow-right"></i></a>
                            </div>';
                          $showtxt .= $navHtml;
                        }
					}
				} else {
					$menumateri = '';
					$showtxt = 'Maaf, belum ada artikel khusus produk ini.';
				}
			} else {
				$menumateri = '
						<div class="card">
							<div class="card-body">';
				if (isset($produk['pro_img']) && !empty($produk['pro_img'])) {
					$menumateri .= '<img src="'.$weburl.'upload/'.$produk['pro_img'].'" alt="'.$produk['page_judul'].'" class="img-fluid mb-3"/>';
				}
				$menumateri .= '
						<h2>'.$produk['page_judul'].'</h2>
						<p>'.$produk['page_diskripsi'].'</p>
					</div>
				</div>'; 
				if (is_login()) {
					$action = '<a href="'.$weburl.'order/'.$produk['page_url'].'" class="btn btn-success">Order '.$produk['page_judul'].' dulu</a>';
				} else {
					$action = '<a href="'.$weburl.'login?redirect='.$slugartikel.'/'.$data['art_slug'].'" class="btn btn-success">Silahkan login dulu</a>';
				}

				$showtxt = '<p>Maaf, artikel ini hanya untuk pembeli produk '.$produk['page_judul'].'.</p>
								'.$action;
			}
			
		}	else {
			$menumateri = '';
			$showtxt = 'Maaf, halaman tidak ditemukan';
		}


$head['pagetitle'] = $showtitle;
$head['container'] = 'container-fluid';
$head['scripthead'] = '';

$pixelId = '';
if (!empty($datasponsor['fbpixel'])) {
    $pixelId = htmlspecialchars($datasponsor['fbpixel'], ENT_QUOTES);
} elseif (!empty($settings['fbpixel'])) {
    $pixelId = htmlspecialchars($settings['fbpixel'], ENT_QUOTES);
}
if (!empty($pixelId)) {
    $head['scripthead'] .= '
    <!-- Meta Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version=\'2.0\';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,\'script\',
    \'https://connect.facebook.net/en_US/fbevents.js\');
    fbq(\'init\', \'" . $pixelId . "\');
    fbq(\'track\', \'PageView\');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=' . $pixelId . '&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->
    ';
}

$gtmId = '';
if (!empty($datasponsor['gtm'])) {
    $gtmId = htmlspecialchars($datasponsor['gtm'], ENT_QUOTES);
} elseif (!empty($settings['gtm'])) {
    $gtmId = htmlspecialchars($settings['gtm'], ENT_QUOTES);
}
if (!empty($gtmId)) {
    $head['scripthead'] .= '
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({\'gtm.start\':
    new Date().getTime(),event:\'gtm.js\'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!=\'dataLayer\'?\'&l=\'+l:\'\';j.async=true;j.src=
    \'https://www.googletagmanager.com/gtm.js?id=\'+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,\'script\',\'dataLayer\',\'GTM-' . $gtmId . '\');</script>
    <!-- End Google Tag Manager -->
    ';
}

showheader($head);
?>
    <style>
      /* Styling daftar materi di menu kiri */
      .materi-list { list-style: none; margin: 0; padding-left: 0; }
      .materi-item { margin-bottom: .5rem; border-radius: 10px; overflow: hidden; background: linear-gradient(90deg, #f7f9fc 0%, #eef3ff 100%); border: 1px solid #e6ebf5; box-shadow: 0 1px 2px rgba(0,0,0,.03); }
      .materi-item .materi-link { display: flex; align-items: center; gap: .5rem; padding: .6rem .75rem; text-decoration: none; color: inherit; }
      .materi-item .icon { width: 28px; height: 28px; border-radius: 50%; display: grid; place-items: center; background: rgba(13,110,253,.08); color: #0d6efd; }
      .materi-item.active { background: linear-gradient(90deg, #e8eefc 0%, #f6f9ff 100%); border-color: #cdd8f1; }
      .materi-item.active .materi-link { background: linear-gradient(135deg, #0d6efd 0%, #3b82f6 100%); color: #fff; }
      .materi-item.active .icon { background: rgba(255,255,255,.25); color: #fff; }
      .materi-item:hover { transform: translateY(-1px); transition: transform .2s ease, box-shadow .2s ease; box-shadow: 0 4px 10px rgba(13,110,253,.08); }
      /* Navigasi materi (Prev/Next) */
      .materi-nav .btn { border-radius: 999px; padding: .45rem .9rem; }
      .materi-nav .btn.disabled, .materi-nav .btn[aria-disabled="true"] { background: #e9ecef; color: #6c757d; border-color: #e9ecef; pointer-events: none; }
    </style>
		<div class="row mt-2">			
			<div class="col-md-8 col-lg-9 mb-3 order-md-2">
				<div class="card">
					<div class="card-body fr-view" style="overflow: hidden;">
					<?php if (isset($showtxt)) { echo $showtxt; } else { echo $menumateri; } ?>
					</div>
				</div>
			</div>
			<div class="col-md-4 col-lg-3 order-md-1">				
				<div class="sticky-top">
					<?php 
					if (isset($showtxt)) { 
						if (isset($produk['page_judul'])) {
							echo $menumateri; 
						}
					} else { 
						echo '
						<div class="card">
							<div class="card-body">';
						if (isset($produk['pro_img']) && !empty($produk['pro_img'])) {
							echo '<img src="'.$weburl.'upload/'.$produk['pro_img'].'" alt="'.$produk['page_judul'].'" class="img-fluid mb-3"/>';
						}
						echo '
								<h2>'.$produk['page_judul'].'</h2>
								<p>'.$produk['page_diskripsi'].'</p>
							</div>
						</div>'; 
					} ?>
				</div>
			</div>
		</div>
	</div>
<?php 
$footer['scriptfoot'] = '
	<script type="text/javascript">
		$(document).ready(function(){
		  $(".konten").hide(); // sembunyikan semua konten pada awalnya
';
if (isset($first)) { $footer['scriptfoot'] .= '$(".konten_'.$first.'").show();'; } 
$footer['scriptfoot'] .= '
          // Toggle collapse per kategori (stable, no double toggle)
          $(".info").off("click.materiToggle").on("click.materiToggle", function(e){
            e.preventDefault();
            // Hentikan handler lain pada elemen yang sama (mencegah double toggle dari dashfoot.js)
            e.stopImmediatePropagation();
            var target = $(this).data("target");
            var $content = $("." + target);
            // Tutup konten lain agar tidak terbuka bersamaan (opsional, bisa dihapus jika ingin multi-open)
            $(".konten").not($content).stop(true, true).slideUp(200);
            // Toggle konten target
            $content.stop(true, true).slideToggle(200);
          });
';
if (isset($first)) { 
  $footer['scriptfoot'] .= '
          // Pastikan kategori aktif terbuka setelah semua ready-handlers dieksekusi
          setTimeout(function(){
            $(".konten").hide();
            $(".konten_'.$first.'").show();
          }, 0);
  ';
}
$footer['scriptfoot'] .= '
        });
    </script>
	<script>
    document.addEventListener("contextmenu", function(e) {
        e.preventDefault();
    });
	</script>';

	showfooter($footer);
	else :
		header("Location: ".$weburl."login?redirect=materi/".$slug[2]);
	endif;
else :
	
endif; ?>
