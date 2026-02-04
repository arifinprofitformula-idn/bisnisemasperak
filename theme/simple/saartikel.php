<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
$theslug = '';
$thumb = function_exists('epi_resolve_logo_src') ? epi_resolve_logo_src($weburl, $settings, $settings['logoweb'] ?? null) : ($weburl.$logoweb);
$slugartikel = $settings['url_artikel'] ??= 'artikel';
if (isset($slug[1]) && ($slug[1] == 'kategori' || $slug[1] == $slugartikel)) {
	$jenis = $slug[1];
	if (isset($slug[2]) && !empty($slug[2])) {
		$theslug = $slug[2];
	} 
} elseif(isset($slug[2]) && ($slug[2] == 'kategori' || $slug[2] == $slugartikel)) {
	$jenis = $slug[2];
	if (isset($slug[3]) && !empty($slug[3])) {
		$theslug = $slug[3];
	}
}

if ($iduser = is_login()) {
	$datamember = db_row("SELECT * FROM `sa_member` 
		LEFT JOIN `sa_sponsor` ON `sa_sponsor`.`sp_mem_id` = `sa_member`.`mem_id` WHERE `mem_id`=".$iduser);
	// Validasi sp_sponsor_id sebelum query
	if (isset($datamember['sp_sponsor_id']) && !empty($datamember['sp_sponsor_id']) && is_numeric($datamember['sp_sponsor_id'])) {
		$datamysponsor = db_row("SELECT * FROM `sa_member` WHERE `mem_id`=".$datamember['sp_sponsor_id']);
	} else {
		$datamysponsor = false;
	}

	$exdatamember = extractdata($datamember);
	$exdatamember['kodeaff'] = $weburl.$exdatamember['kodeaff'];

  if (isset($datamysponsor['mem_id']) && $datamysponsor['mem_id'] > 0) {
    $exdatasponsor = extractdata($datamysponsor);
    $exdatasponsor['kodeaff'] = $weburl.$exdatasponsor['kodeaff'];
  }

  $datamember = $exdatamember;
}

if ($theslug != '' && count($_GET) == 0) {
	$showadminbutton = '';

	if ($jenis == $slugartikel) {
		$data = db_row("SELECT * FROM `sa_artikel` 
			LEFT JOIN `sa_member` ON `sa_artikel`.`art_writer` = `sa_member`.`mem_id`
			WHERE `art_slug`='".cek($theslug)."'");
		$role = array('Visitor','Free Member','Premium Member');
		if (isset($data['art_id'])) {
			# Munculkan halaman artikel
			$showfile = 'art_single.php';
			$showtitle = $data['art_judul'];
			$showdesc = pendekin(strip_tags($data['art_konten']),170);
			$showauthor = $data['mem_nama'];
            if (!empty($data['art_img'])) { 
                $thumb = function_exists('epi_image_safe_url') ? epi_image_safe_url($data['art_img']) : ($weburl.'upload/'.$data['art_img']);
            }
            if ($data['art_product'] == 0) {
				if (!empty($data['art_role'])) {
					if (isset($datamember) && $data['art_role'] <= $datamember['mem_status']) {
                        $showartikel = $data['art_konten'];
					} else {	
						if (is_login()) {
							$action = '<a href="'.$weburl.'dashboard/produklist" class="btn btn-success">Order produk dulu</a>';
						} else {
							$action = '<a href="'.$weburl.'login?redirect='.$slugartikel.'/'.$data['art_slug'].'" class="btn btn-success">Silahkan login dulu</a>';
						}
									
						$showartikel = '
						<div class="card mt-3">
							<div class="card-body text-center">
								<p>'.pendekin(strip_tags($data['art_konten']),170).'</p>
								<p>Maaf, artikel ini hanya untuk '.$role[$data['art_role']].'.</p>
								'.$action.'
							</div>
						</div>';
					}			
				} else {
                    $showartikel = $data['art_konten'];
				}
			} else {
				# Cek apakah sudah beli produk (Support Multi-Product)
				$reqProducts = array_filter(explode(',', (string)$data['art_product']), 'strlen');
				// Bersihkan ID non-numeric dan cek "0"
				$ids = [];
				$isPublic = false;
				foreach ($reqProducts as $rp) {
					if (trim($rp) === '0') { $isPublic = true; break; }
					if (is_numeric($rp)) { $ids[] = (int)$rp; }
				}

				if (empty($ids) || $isPublic) {
					$showartikel = $data['art_konten'];
				} else {
					$idsStr = implode(',', $ids);
					// Ambil nama produk untuk pesan error
					$produkList = db_select("SELECT `page_judul`, `page_url` FROM `sa_page` WHERE `page_id` IN (".$idsStr.")");
					$namaProdukArr = [];
					$firstUrl = '';
					foreach ($produkList as $p) {
						$namaProdukArr[] = $p['page_judul'];
						if ($firstUrl === '') $firstUrl = $p['page_url'];
					}
					$namaproduk = implode(' / ', $namaProdukArr);

					$hasAccess = false;
					if (isset($datamember)) {
						$checkOrder = db_row("SELECT `order_id` FROM `sa_order` WHERE `order_status`=1 AND `order_idmember`=".$datamember['mem_id']." AND `order_idproduk` IN (".$idsStr.") LIMIT 1");
						if (isset($checkOrder['order_id'])) {
							$hasAccess = true;
						}
					}

					if ($hasAccess) {
						$showartikel = $data['art_konten'];
					} else {
						# Munculkan warning
						if (is_login()) {
							$action = '<a href="'.$weburl.'order/'.$firstUrl.'" class="btn btn-success">Order Produk</a>';
						} else {
							$action = '<a href="'.$weburl.'login?redirect='.$slugartikel.'/'.$data['art_slug'].'" class="btn btn-success">Silahkan login dulu</a>';
						}
						
						$showartikel = '
						<div class="card mt-3">
							<div class="card-body text-center">
								<p>'.pendekin(strip_tags($data['art_konten']),170).'</p>
								<p>Maaf, artikel ini hanya untuk pembeli produk: <strong>'.$namaproduk.'</strong>.</p>
								'.$action.'
							</div>
						</div>';
					}
				}
			}

            $showartikel = function_exists('epi_fix_images') ? epi_fix_images($showartikel) : $showartikel;
            if (isset($datamember) && $datamember['mem_role'] >=2) {
				if ($datamember['mem_role'] == 2 && $data['art_writer'] != $datamember['mem_id']) {
					# diem aja
				} else {
					$showadminbutton = '
					<div class="mt-3 mb-3">
					<a href="'.$weburl.$slugartikel.'/?edit='.$data['art_id'].'" class="btn btn-success mb-3">Edit '.ucwords($slugartikel).'</a> &nbsp;				
					<a href="'.$weburl.$slugartikel.'/?del='.$data['art_id'].'"  onclick="javascript:return confirm(\'Anda yakin ingin menghapus \\\''.$data['art_judul'].'\\\' ?\')" class="btn btn-danger mb-3">Hapus '.ucwords($slugartikel).'</a>
					</div>
					';
				}
			}

			# Ubah shortcode artikel			
			# Handle Data Default
		  $arrfield = array('nama','email','whatsapp','kodeaff');
		  foreach ($arrfield as $arrfield) {      
		    $showartikel = str_replace('[member_'.$arrfield.']',($exdatamember[$arrfield]??''),$showartikel);
		    $showartikel = str_replace('[sponsor_'.$arrfield.']',($exdatasponsor[$arrfield]??=''),$showartikel);
		  }

		  # Handle data lain
		  $form = db_select("SELECT * FROM `sa_form` WHERE `ff_field` NOT IN ('nama','email','whatsapp','kodeaff','password')");

		  foreach ($form as $form) {
		    $valmember = $valsponsor = '';
		    if (isset($exdatamember[$form['ff_field']])) {
		      $valmember = $exdatamember[$form['ff_field']];
		    }
		    if (isset($exdatasponsor[$form['ff_field']])) {
		      $valsponsor = $exdatasponsor[$form['ff_field']];
		    }
		    
		    $showartikel = str_replace('[member_'.$form['ff_field'].']',$valmember,$showartikel);
		    $showartikel = str_replace('[sponsor_'.$form['ff_field'].']',$valsponsor,$showartikel);
		  }

		  # Handle data tambahan lain
		  if (isset($datalain) && is_array($datalain) && count($datalain) > 0) {
		    foreach ($datalain as $key => $value) {
		      $showartikel = str_replace('['.$key.']',$value,$showartikel);
		    }
		  }	
			
		} else {
			# Munculkan halaman 404
			$showfile = 'art_404.php';
			$showtitle = 'Not Found';
		}
	} elseif ($jenis == 'kategori') {
		$data = db_row("SELECT * FROM `sa_kategori` WHERE `kat_slug`='".cek($theslug)."'");
		if (isset($data['kat_id'])) {
			# Munculkan halaman kategori
			$showfile = 'art_kategori.php';
			$showtitle = $data['kat_nama'];
			if (isset($datamember) && $datamember['mem_role'] >= 2) {
				$showadminbutton = '
				<div class="mt-3 mb-3">
				<a href="'.$weburl.$slugartikel.'/?add='.$data['kat_id'].'" class="btn btn-success mb-3">Tambah '.ucwords($slugartikel).'</a>
				<a href="'.$weburl.'kategori/?add='.$data['kat_id'].'" class="btn btn-primary mb-3">Tambah Kategori</a>
				<a href="'.$weburl.'kategori/?edit='.$data['kat_id'].'" class="btn btn-primary mb-3">Edit Kategori</a> &nbsp;
				<a href="'.$weburl.'kategori/?del='.$data['kat_id'].'"  onclick="javascript:return confirm(\'Anda yakin ingin menghapus \\\''.$data['kat_nama'].'\\\' dan semua artikel di dalamnya?\')" class="btn btn-danger mb-3">Hapus Kategori & '.ucwords($slugartikel).'</a>
				</div>
				';

				if ($datamember['mem_role'] == 2) {
					# Hanya bisa isi artikel saja
					$showadminbutton = '
					<div class="mt-3 mb-3">
						<a href="'.$weburl.$slugartikel.'/?add='.$data['kat_id'].'" class="btn btn-success mb-3">Tambah '.ucwords($slugartikel).'</a>
					</div>';
				}
			}
		} else {
			# Munculkan halaman 404
			$showfile = 'art_404.php';
			$showtitle = 'Not Found';
		}
	}
} else {
	$showfile = 'art_home.php';
	$showtitle = ucwords($slugartikel);
}
/*
if (isset($datasponsor)) {
	$datasponsor = extractdata($datasponsor);
}
*/

$head['pagetitle'] = $showtitle;
$head['thumbnail'] = $thumb ?? '';
$head['description'] = $showdesc ?? '';
$head['container'] = 'container-fluid';
$head['scripthead'] = '';

if (!isset($_GET['add']) && !isset($_GET['edit'])) {
    $head['scripthead'] .= '
		<style type="text/css">
			#menu-tree {
			  list-style: none; /* Menghilangkan bullet default */
			  padding-left: 0;
			  font-family: sans-serif; /* Opsional: Sesuaikan font */
			  color: #333; /* Opsional: Warna teks default */
			}

			#menu-tree ul {
			  list-style: none;
			  padding-left: 20px; /* Indentasi untuk sub-menu */
			}

			#menu-tree li {
			  margin-bottom: 5px;
			  cursor: pointer; /* Menunjukkan bahwa item bisa diklik */
			  user-select: none; /* Mencegah teks terpilih saat mengklik cepat */
			  padding: 3px 0;
			}

			/* Icon toggle (+/-) */
			#menu-tree .toggle-icon {
			  margin-right: 8px;
			  width: 15px; /* Memberi lebar tetap agar tidak bergeser */
			  text-align: center;
			  color: #007bff; /* Warna biru, bisa disesuaikan */
			}

			/* Icon kategori (folder) */
			#menu-tree .category-icon {
			  margin-right: 5px;
			  color: #ffc107; /* Warna kuning/oranye, bisa disesuaikan */
			}

			/* Icon artikel (file) */
			#menu-tree .article-icon {
			  margin-right: 5px;
			  color: #6c757d; /* Warna abu-abu, bisa disesuaikan */
			}

			/* Menyembunyikan sub-menu secara default */
			#menu-tree .submenu {
			  display: none;
			}

			/* Gaya saat sub-menu aktif/terbuka (opsional) */
			#menu-tree .submenu.active {
			  display: block;
			}
		</style>
    ';
} else {
	$head['scripthead'] .= '<link href="'.$weburl.'editor/css/froala_editor.pkgd.min.css" rel="stylesheet" type="text/css" />
	    <style type="text/css">
      a[id="fr-logo"] {
        height:1px !important;
        color:#ffffff !important;
      }
      #Layer_1 { height:1px !important; }
      p[data-f-id="pbf"] {
        height:1px !important;
      }
      a[href*="www.froala.com"] {
        height:1px !important;
        background: #fff !important;
        pointer-events: none;
      }
      #fr-logo {
          visibility: hidden;
      }
      /* Responsive images inside article content */
      .fr-view img, .article-content img {
        max-width: 100% !important;
        height: auto !important;
        display: block;
        margin: 12px auto;
      }
      @media (max-width: 576px) {
        .fr-view img, .article-content img {
          max-width: 100% !important;
          height: auto !important;
        }
      }
    </style>';
}

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
		<div class="row">				
				<?php	
				if (isset($_GET['del']) && is_numeric($_GET['del']) && is_login()) {
					if ($slug[1] == $slugartikel) {
						$cek = db_query("DELETE FROM `sa_artikel` WHERE `art_id`=".$_GET['del']);
						if ($cek === false) {
			        echo '
			        <div class="alert alert-danger alert-dismissible fade show" id="peringatan">
			          <strong>Error!</strong> '.db_error().'
			          <button type="button" class="btn-close" id="tutup"></button>
			        </div>';
			      } else {
			        echo '
			        <div class="alert alert-success alert-dismissible fade show" id="peringatan">
			          <strong>Ok!</strong> '.ucwords($slugartikel).' telah dihapus
			          <button type="button" class="btn-close" id="tutup"></button>
			        </div>';
			      }
					} else {
						$cek = db_query("DELETE FROM `sa_kategori` WHERE `kat_id`=".$_GET['del']);
						if ($cek === false) {
							$cek = db_query("DELETE FROM `sa_artikel` WHERE `art_kat_id`=".$_GET['del']);
			        echo '
			        <div class="alert alert-danger alert-dismissible fade show" id="peringatan">
			          <strong>Error!</strong> '.db_error().'
			          <button type="button" class="btn-close" id="tutup"></button>
			        </div>';
			      } else {
			        echo '
			        <div class="alert alert-success alert-dismissible fade show" id="peringatan">
			          <strong>Ok!</strong> Kategori telah dihapus
			          <button type="button" class="btn-close" id="tutup"></button>
			        </div>';
			      }
					}
				}
				
				if (!isset($_GET['add']) && !isset($_GET['edit'])) :
					echo '<div class="col-md-8 col-lg-9 order-md-2 mb-3">';

					$openfile = 'theme/'.$settings['theme'].'/'.$showfile;
					if (file_exists($openfile)) {
						include($openfile);
					} elseif (file_exists('theme/simple/'.$showfile)) {
						include('theme/simple/'.$showfile);
					}

					echo '</div>'; 
			?>
			<div class="col-md-4 col-lg-3 order-md-1">
				<div class="card">
					<div class="card-body" style="overflow: hidden;">

						<?php
						function buildMenuTree($categories, $articles, $weburl, $parentId = 0) {
						    global $settings;
						    $slugartikel = $settings['url_artikel'] ??= 'artikel';
						    $menuHtml = '';

						    // PERBAIKAN: Pastikan $categories dan $articles adalah array
						    if (!is_array($categories)) {
						        error_log("buildMenuTree: \$categories is not an array. Type given: " . gettype($categories));
						        return ''; // Mengembalikan string kosong atau handle error lain
						    }
						    if (!is_array($articles)) {
						        error_log("buildMenuTree: \$articles is not an array. Type given: " . gettype($articles));
						        return ''; // Mengembalikan string kosong atau handle error lain
						    }

						    // Ambil kategori di level saat ini
						    $currentLevelCategories = array_filter($categories, function($cat) use ($parentId) {
						        return $cat['kat_parent_id'] == $parentId;
						    });

						    if (!empty($currentLevelCategories) || ($parentId != 0 && !empty($articles))) {
						        $menuHtml .= '<ul' . ($parentId == 0 ? ' id="menu-tree"' : ' class="submenu"') . '>';

						        foreach ($currentLevelCategories as $category) {
						            $menuHtml .= '<li data-kat-id="' . $category['kat_id'] . '">';
						            // Cek apakah kategori ini punya sub-kategori atau artikel
						            $hasSubitems = false;
						            // Cek sub-kategori
						            $subCategories = array_filter($categories, function($cat) use ($category) {
						                return $cat['kat_parent_id'] == $category['kat_id'];
						            });
						            if (!empty($subCategories)) {
						                $hasSubitems = true;
						            }
						            // Cek artikel di kategori ini
						            $categoryArticles = array_filter($articles, function($art) use ($category) {
						                return $art['art_kat_id'] == $category['kat_id'];
						            });
						            if (!empty($categoryArticles)) {
						                $hasSubitems = true;
						            }

						            if ($hasSubitems) {
						                $menuHtml .= '<i class="fas fa-plus toggle-icon"></i>'; // Icon toggle hanya jika ada sub-item
						            }
						            $menuHtml .= '<i class="fas fa-folder category-icon"></i>'; // Icon folder untuk kategori
						            $menuHtml .= '<a href="' . $weburl . 'kategori/' . $category['kat_slug'] . '">' . htmlspecialchars($category['kat_nama']) . '</a>';

						            // Rekursif untuk sub-kategori
						            $menuHtml .= buildMenuTree($categories, $articles, $weburl, $category['kat_id']);

						            // Tambahkan artikel di bawah kategori ini
						            if (!empty($categoryArticles)) {
						                $menuHtml .= '<ul class="submenu">'; // Artikel juga dalam sub-menu
						                foreach ($categoryArticles as $article) {
						                    $menuHtml .= '<li>';
						                    $menuHtml .= '<i class="fas fa-file-alt article-icon"></i>'; // Icon file untuk artikel
						                    $menuHtml .= '<a href="' . $weburl . $slugartikel.'/' . $article['art_slug'] . '">' . htmlspecialchars($article['art_judul']) . '</a>';
						                    $menuHtml .= '</li>';
						                }
						                $menuHtml .= '</ul>';
						            }

						            $menuHtml .= '</li>';
						        }

						        $menuHtml .= '</ul>';
						    }

						    return $menuHtml;
						}


						// --- PENGGUNAAN ---

						// 1. Ambil semua data kategori dan artikel dari database
						$allCategories = db_select("SELECT * FROM `sa_kategori`");

						$allArticles = db_select("SELECT * FROM `sa_artikel`");
						// 2. Bangun HTML menu tree
						
						$htmlMenuTree = buildMenuTree($allCategories, $allArticles, $weburl, 0);
						$htmlMenuTree = substr($htmlMenuTree, 0,-5);

						# Tambah Add Kategori
						if (isset($datamember['mem_role']) && $datamember['mem_role'] >= 5) {
		        $htmlMenuTree .= '<li data-kat-id="0">';
		        $htmlMenuTree .= '<i class="fas fa-folder category-icon"></i>'; // Icon folder untuk kategori
		        $htmlMenuTree .= '<a href="' . $weburl . 'kategori/?add=0">Tambah Kategori Utama</a>';
		        $htmlMenuTree .= '</li>';
		        }

						echo $htmlMenuTree.'</ul>';
						
						?>

					</div>
				</div>
			</div>
			<?php 
			else:
				echo '<div class="col">';
					$openfile = 'theme/'.$settings['theme'].'/'.$showfile;
					if (file_exists($openfile)) {
						include($openfile);
					} elseif (file_exists('theme/simple/'.$showfile)) {
						include('theme/simple/'.$showfile);
					}
				echo '</div>'; 
			endif; ?>
		</div>
	</div>	
	<!-- Modal -->
	<div class="modal fade" id="peringatan" tabindex="-1" aria-labelledby="konfirmasilabel" aria-hidden="true">
	  <div class="modal-dialog">
	    <div class="modal-content">
	      <div class="modal-header">
	        <h5 class="modal-title" id="konfirmasilabel">JUDUL</h5>
	        <button type="button" id="tutup">
	          <span aria-hidden="true">&times;</span>
	        </button>
	      </div>
	      <div class="modal-body">ISI
	      </div>
	      <div class="modal-footer">        
	        <a href="#" class="btn btn-secondary delbutton">Hapus</a>
	        <button type="button" class="btn btn-primary" data-bs-dismiss="modal" id="tutup">Batal</button>
	      </div>
	    </div>
	  </div>
	</div>
<?php
// Konten untuk $footer['scriptfoot'] Anda
$footer['scriptfoot'] = '
<script>
$(document).ready(function() {
    // 1. Sembunyikan semua sub-menu saat halaman dimuat
    $(\'#menu-tree .submenu\').hide();

    // --- PERUBAHAN URUTAN INI PENTING ---
    // Pastikan kelas `has-submenu` ada sebelum kita mencoba memulihkan status.
    // Ini adalah langkah 2 dari sebelumnya, sekarang menjadi langkah 2 yang baru.
    $(\'#menu-tree li\').each(function() {
        if ($(this).children(\'.submenu\').length > 0) {
            $(this).addClass(\'has-submenu\');
            if ($(this).children(\'.toggle-icon\').length === 0) {
                $(this).prepend(\'<i class="fas fa-plus toggle-icon"></i>\');
            }
        } else {
            $(this).children(\'.toggle-icon\').remove();
            $(this).removeClass(\'has-submenu\'); 
        }
    });
    // --- AKHIR PERUBAHAN URUTAN ---

    // 2. Inisialisasi status toggle dari Local Storage (sekarang ini langkah 3)
    var openCategories = JSON.parse(localStorage.getItem(\'openMenuCategories\')) || [];
    console.log("Awal - Kategori yang tersimpan di Local Storage:", openCategories);

    // Iterasi melalui setiap item kategori di menu tree untuk memulihkan status
    // Sekarang, `li.has-submenu` seharusnya sudah benar-benar memiliki kelasnya.
    $(\'#menu-tree li.has-submenu\').each(function() {
        var $submenu = $(this).children(\'.submenu\');
        var $toggleIcon = $(this).children(\'.toggle-icon\');
        var categoryIdentifier = $(this).data(\'kat-id\'); 
        
        console.log("Memeriksa Kategori:", categoryIdentifier, "di Local Storage:", openCategories.includes(categoryIdentifier));

        if (categoryIdentifier !== undefined && categoryIdentifier !== null && openCategories.includes(categoryIdentifier)) {
            $submenu.show();
            $toggleIcon.removeClass(\'fa-plus\').addClass(\'fa-minus\');
            console.log("Kategori Dibuka Otomatis:", categoryIdentifier);
        }
    });

    // 3. Event listener untuk klik pada item kategori yang memiliki sub-menu (sebelumnya langkah 4)
    $(\'#menu-tree li.has-submenu\').on(\'click\', function(e) {
        if ($(e.target).is(\'a\')) {
            return;
        }

        e.stopPropagation();

        var $parentLi = $(this);
        var $submenu = $parentLi.children(\'.submenu\');
        var $toggleIcon = $parentLi.children(\'.toggle-icon\');
        var categoryIdentifier = $parentLi.data(\'kat-id\'); 
        
        console.log("Klik Kategori:", categoryIdentifier, "Status sub-menu visible:", $submenu.is(\':visible\'));

        if (categoryIdentifier !== undefined && categoryIdentifier !== null) {
            $submenu.slideToggle(200, function() {
                if ($submenu.is(\':visible\')) {
                    $toggleIcon.removeClass(\'fa-plus\').addClass(\'fa-minus\');
                    addCategoryToLocalStorage(categoryIdentifier);
                    console.log("Menambahkan ke Local Storage:", categoryIdentifier);
                } else {
                    $toggleIcon.removeClass(\'fa-minus\').addClass(\'fa-plus\');
                    removeCategoryFromLocalStorage(categoryIdentifier);
                    console.log("Menghapus dari Local Storage:", categoryIdentifier);
                }
            });
        }
    });

    // 4. Event listener untuk klik pada link kategori atau artikel (sebelumnya langkah 5)
    $(\'#menu-tree a\').on(\'click\', function(e) {
        console.log(\'Link diklik:\', $(this).attr(\'href\'));
    });

    // --- Fungsi Bantuan untuk Local Storage --- (tetap sama)
    function addCategoryToLocalStorage(id) {
        var openCategories = JSON.parse(localStorage.getItem(\'openMenuCategories\')) || [];
        if (!openCategories.includes(id)) {
            openCategories.push(id);
            localStorage.setItem(\'openMenuCategories\', JSON.stringify(openCategories));
            console.log("Local Storage diperbarui (tambah):", JSON.parse(localStorage.getItem(\'openMenuCategories\')));
        }
    }

    function removeCategoryFromLocalStorage(id) {
        var openCategories = JSON.parse(localStorage.getItem(\'openMenuCategories\')) || [];
        var index = openCategories.indexOf(id);
        if (index > -1) {
            openCategories.splice(index, 1);
            localStorage.setItem(\'openMenuCategories\', JSON.stringify(openCategories));
            console.log("Local Storage diperbarui (hapus):", JSON.parse(localStorage.getItem(\'openMenuCategories\')));
        }
    }
});
</script>';

if (isset($datamember) && $datamember['mem_role'] >= 2) {
    $weburlSafe = htmlspecialchars($weburl, ENT_QUOTES);
    $footer['scriptfoot'] .= '    
    <script type="text/javascript" src="' . $weburlSafe . 'editor/js/froala_editor.pkgd.min.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        new FroalaEditor("#editor", {
            imageUploadURL: "/upload_image.php",
            imageUploadParams: {
                id: "my_editor"
            },
            codeViewKeepOriginal: true,
            htmlUntouched: true,
            htmlAllowedTags: [".*"],
            htmlAllowedAttrs: [".*"],
            htmlRemoveTags: [],
            events: {
                "image.beforeUpload": function (files) {
                    var editor = this;
                    var formData = new FormData();
                    formData.append("file", files[0]);
                    var judulArtikel = document.getElementById("judul").value;
                    formData.append("judul", judulArtikel);

                    fetch("/upload_image.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.link) {
                            editor.image.insert(data.link, null, null, editor.image.get());
                        } else {
                            console.error("Upload failed:", data.error);
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                    });

                    return false;
                }
            }
        });
    });
    </script>
    <script>
    $(document).ready(function(){
        $("button").on("click", function() {
            $("#peringatan").remove();
        });

        $("input[type=file]").on("change", function() {
            var inputName = $(this).attr("name");
            var file = $(this).prop("files")[0];
            var img = $("<img>", {
                class: "img-fluid img-thumbnail",
                style: "max-width: 200px",
                alt: inputName
            });
            var url = URL.createObjectURL(file);
            img.attr("src", url);
            $("#preview" + inputName).empty().append(img);
        });
    });
    </script>
    <style>
    img { max-width: 100%; height: auto; display: block; margin: 0 auto; }
    .img-max-md { max-width: 720px; }
    .img-max-lg { max-width: 960px; }
    @media (max-width: 576px) { .img-max-md, .img-max-lg { max-width: 100%; } }
    </style>
    ';
}

if (!isset($_GET['add']) && !isset($_GET['edit'])) {
    $footer['scriptfoot'] .= '
    <script>
    document.addEventListener("contextmenu", function(e) {
        e.preventDefault();
    });
    </script>
    ';
}

showfooter($footer);
?>
