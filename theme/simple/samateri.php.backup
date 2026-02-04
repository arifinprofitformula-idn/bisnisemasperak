<?php
if (isset($slug[2]) && !empty($slug[2])) :
	if ($iduser = is_login()) :
		if (is_numeric($slug[2])) {
			$single = db_row("SELECT * FROM `sa_artikel` WHERE `art_id` = '".$slug[2]."'");
			if (isset($single['art_id'])) {
				$showtxt = '<h2>'.$single['art_judul'].'</h2>'.$single['art_konten'];
				$produk = db_row("SELECT * FROM `sa_page` WHERE `page_id`='".$single['art_product']."'");
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
			$cekorder = db_row("SELECT * FROM `sa_order` WHERE `order_idproduk`=".$produk['page_id']." AND `order_idmember`=".$iduser." AND `order_status`=1");
			if (isset($cekorder['order_id'])) {
				$artikel = db_select("SELECT * FROM `sa_artikel` LEFT JOIN `sa_kategori` ON `sa_kategori`.`kat_id`=`sa_artikel`.`art_kat_id` 
				WHERE `art_product`=".$produk['page_id']." ORDER BY `art_judul`");
				if (count($artikel) > 0) {
					foreach ($artikel as $artikel) {
						if (!isset($list[$artikel['kat_id']])) { 
							$list[$artikel['kat_id']]['judul'] = $artikel['kat_nama'];
							$list[$artikel['kat_id']]['artikel'] = ''; 
						}
						if (isset($single['art_id']) && $single['art_id'] == $artikel['art_id']) {
							$list[$artikel['kat_id']]['artikel'] .= '<li><strong>'.$artikel['art_judul'].'</strong></li>';
						} else {
							$list[$artikel['kat_id']]['artikel'] .= '<li><a href="'.$weburl.($settings['url_materi']??='materi').'/'.$artikel['art_id'].'">'.$artikel['art_judul'].'</a></li>';
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
									<ol>
										'.$value['artikel'].'
									</ol>
								</div>
							</div>';
							if (!isset($first)) { $first = $key; }
						}

						if (isset($single['art_kat_id'])) { $first = $single['art_kat_id']; }
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

if (!empty($datasponsor['fbpixel'])) {
    $fbpixel = htmlspecialchars($datasponsor['fbpixel'], ENT_QUOTES);
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
    fbq(\'init\', \'' . $fbpixel . '\');
    fbq(\'track\', \'PageView\');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=' . $fbpixel . '&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->
    ';
}

if (!empty($datasponsor['gtm'])) {
    $gtm = htmlspecialchars($datasponsor['gtm'], ENT_QUOTES);
    $head['scripthead'] .= '
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({\'gtm.start\':
    new Date().getTime(),event:\'gtm.js\'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!=\'dataLayer\'?\'&l=\'+l:\'\';j.async=true;j.src=
    \'https://www.googletagmanager.com/gtm.js?id=\'+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,\'script\',\'dataLayer\',\'GTM-' . $gtm . '\');</script>
    <!-- End Google Tag Manager -->
    ';
}

showheader($head);
?>
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
		  $(".konten").hide(); // sembunyikan semua konten pada awalnya';

if (isset($first)) { $footer['scriptfoot'] .= '$(".konten_'.$first.'").show();'; } 
$footer['scriptfoot'] .= '	  
	        // Temukan konten terkait dengan data-target yang sesuai
	        var target = $(this).data(\'target\');
	        var konten = $(\'.\' + target);

	        // Toggle (sembunyikan/tampilkan) konten
	        konten.slideToggle();
	    });
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