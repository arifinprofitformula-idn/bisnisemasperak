<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if (isset($_GET['detil']) && is_numeric($_GET['detil'])) {
	$dataklien = db_row("SELECT * FROM `sa_member` 
		LEFT JOIN `sa_sponsor` ON `sa_sponsor`.`sp_mem_id` = `sa_member`.`mem_id` 
		WHERE `mem_id`=".$_GET['detil']." AND `sp_sponsor_id`=".$iduser);
	if (isset($dataklien['mem_id'])) :
		$head['pagetitle']='Profil '.$dataklien['mem_nama'];
		showheader($head);
		?>
		<div class="card">
		  <div class="card-body">
			  <?php 
			  $dataform = extractdata($dataklien);
				
			  $formfield = db_select("SELECT * FROM `sa_form` WHERE `ff_network`=1 ORDER BY `ff_sort`");
			  if (count($formfield) > 0) {
			  	foreach ($formfield as $formfield) {
			  		echo '
			  		<div class="mb-3 row">
			  			<div class="col-sm-4">'.$formfield['ff_label'].'</div>
			  			<div class="col-sm-8">';
			  		if (isset($dataform[$formfield['ff_field']])) {
			  			if ($formfield['ff_field'] == 'kodeaff') {
			  				echo '<a href="'.$weburl.$dataform[$formfield['ff_field']].'">'.$weburl.$dataform[$formfield['ff_field']].'</a>';
			  			} elseif ($formfield['ff_field'] == 'whatsapp') {
			  				echo '<a href="https://wa.me/'.$dataform[$formfield['ff_field']].'">'.$dataform[$formfield['ff_field']].'</a>';
			  			} else {
			  				echo $dataform[$formfield['ff_field']];
			  			}
			  		} else {
			  			echo '-';
			  		}
			  		echo '</div>
			  		</div>
			  		';
			  	}
			  }
			  ?>
			  <div class="mb-3 row">
					<div class="col-sm-4">Tanggal Daftar</div>
					<div class="col-sm-8"><?php echo $dataklien['mem_tgldaftar'];?></div>
				</div>
				<div class="mb-3 row">
					<div class="col-sm-4">Status</div>
					<div class="col-sm-8"><?php 
					$statusklien = array('','Free Member','Premium');
					echo $statusklien[$dataklien['mem_status']];?></div>
				</div>
				
			</div>
		</div>
		<?php 
	else :
		showheader();
		echo 'Data tidak ditemukan';
	endif;
} else {
	if (isset($_POST['kontenfu']) && !empty($_POST['kontenfu'])) {
		setcookie('kontenfu',rawurlencode($_POST['kontenfu']),strtotime('+30 days'),'/');
		$ok = 1;
	}

	$head['pagetitle']='Klien Anda';
	$head['scripthead'] = '<link href="'.$weburl.'fontawesome/css/brands.min.css" rel="stylesheet">';
	showheader($head);

	if (isset($ok)) {
		echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
			  <strong>Ok!</strong> Konten Follow Up telah dipasang di link whatsapp klien anda. Selamat mencoba.
			  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>';
	}
	?>
	<form action="" method="get">
	<div class="card mb-3">
		<div class="card-body">
		  <div class="row">	    
		    <div class="col-9 col-sm-10">
		    	<div class="input-group">
					  <input type="text" class="form-control" name="cari" placeholder="Cari Member" value="<?= $_GET['cari'] ??= '';?>">
					  <?php 
					  $select = array('','','');
					  if (isset($_GET['status']) && is_numeric($_GET['status'])) {
					  	$select[$_GET['status']] = ' selected';
					  }
					  ?>
					  <select name="status" class="form-select">
					  	<option value="">All Member</option>
					  	<option value="1"<?=$select[1];?>>Free Member</option>
					  	<option value="2"<?=$select[2];?>>Premium Member</option>
					  </select>
					  <input type="submit" value=" Cari " class="btn btn-secondary">
					</div>	      
		    </div>
		    <div class="col-3 col-sm-2 text-end">	    	
		    	<a href="export?data=klien" class="btn btn-primary d-none d-sm-block">Download</a>
		    	<a href="export?data=klien" class="btn btn-primary d-block d-sm-none"><i class="fa-sharp fa-solid fa-cloud-arrow-down"></i></a>
		    </div>
		  </div>
		</div>
	</div>
	</form>
	<div class="table-responsive">
	<table class="table table-hover table-bordered">
		<thead class="table-secondary">
			<tr>
				<th>ID</th>
				<th>Nama</th>
				<th class="d-none d-sm-table-cell">Email</th>
				<th class="d-none d-sm-table-cell">WhatsApp</th>
			</tr>
		</thead>
		<tbody>
			<?php 
			$jmlperpage = 25;
			if (isset($_GET['start']) && is_numeric($_GET['start'])) {
			    $start = ($_GET['start'] - 1) * $jmlperpage;
			    $page = $_GET['start'];
			} else {
			    $start = 0;
			    $page = 1;
			}

			$where = '';

			if (isset($_GET['cari']) && !empty($_GET['cari'])) {
				$s = cek($_GET['cari']);
				$where .= "AND (`mem_nama` LIKE '%".$s."%' 
									OR `mem_email` LIKE '%".$s."%' 
									OR `mem_whatsapp` LIKE '%".$s."%' 
									OR `mem_datalain` LIKE '%".$s."%' 
									OR `mem_kodeaff` LIKE '%".$s."%')";
			}

			if (isset($_GET['status']) && is_numeric($_GET['status'])) {
				$where .= "AND `mem_status`=".$_GET['status'];			
			}

			$data = db_select("SELECT *	FROM `sa_member` 
				LEFT JOIN `sa_sponsor` ON `sa_sponsor`.`sp_mem_id` = `sa_member`.`mem_id`
				WHERE `sa_sponsor`.`sp_sponsor_id`=".$iduser."
				".$where."
				ORDER BY `mem_tgldaftar` DESC
				LIMIT ".$start.",".$jmlperpage);
			if (count($data) > 0) {
				foreach ($data as $data) {
					if (isset($_POST['kontenfu']) && !empty($_POST['kontenfu'])) {
						$kontenfu = rawurlencode($_POST['kontenfu']);
					} elseif (isset($_COOKIE['kontenfu'])) {
						$kontenfu = $_COOKIE['kontenfu'];				
					} else {
						$kontenfu = '';
					}

					$kontenfu = str_replace('%5Bnama%5D', $data['mem_nama'], $kontenfu);
					echo '
					<tr>
					<td>'.$data['mem_id'].'</td>
					<td>
					<a href="?detil='.$data['mem_id'].'">'.$data['mem_nama'].'</a>';

					if ($data['mem_status'] == 2) { echo ' <sup><i class="fa-solid fa-circle-check text-success" title="Premium"></i></sup>'; }
					
					echo '
					<span class="d-sm-none">
						<br/><i class="fa-regular fa-envelope"></i> '.$data['mem_email'].'
						<br/><i class="fa-brands fa-whatsapp"></i> <a href="https://wa.me/'.$data['mem_whatsapp'].'?text='.$kontenfu.'" target="_blank">'.$data['mem_whatsapp'].'</a>
					</span>
					</td>
					<td class="d-none d-sm-table-cell">'.$data['mem_email'].'</td>
					<td class="d-none d-sm-table-cell"><a href="https://wa.me/'.$data['mem_whatsapp'].'?text='.$kontenfu.'" target="_blank">'.$data['mem_whatsapp'].'</a></td>		
					</tr>';
				}
			} else {
				echo '<tr><td colspan="4">Belum ada member</td></tr>';
			}
			?>
		</tbody>
	</table>
	</div>
	<?php
	$jmlmember = db_var("SELECT count(*)
				FROM `sa_member` 
				LEFT JOIN `sa_sponsor` ON `sa_sponsor`.`sp_mem_id` = `sa_member`.`mem_id`
				WHERE `sa_sponsor`.`sp_sponsor_id`=".$iduser." ".$where);
	$jmlpage = ceil($jmlmember/$jmlperpage);
	echo '
	<nav aria-label="Page navigation" class="mt-3">
	  <ul class="pagination">';
	if ($jmlpage > 10) {
	  if ($page <= 4){
	    # Depan
	    for ($i=1;$i<=5;$i++) {
	        if ($i == $page) {
	            echo '<li class="page-item active"><a class="page-link" href="?start='.$i.'">'.$i.'<span class="sr-only">(current)</span></a></li>';
	        } else {
	            echo '<li class="page-item"><a class="page-link" href="?start='.$i.'">'.$i.'</a></li>';
	        }
	    }
	    echo '
	    <li class="page-item disabled"><a class="page-link" href="#">...</a></li>
	    <li class="page-item"><a class="page-link" href="?start='.$jmlpage.'">'.$jmlpage.'</a></li>';
	  } elseif ($page >= 5 && $page <= ($jmlpage-5)) {
	    # Tengah
	    echo '<li class="page-item"><a class="page-link" href="?start=1">1</a></li>
	    <li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
	    for ($i=($page-2);$i<=($page+2);$i++) {
	        if ($i == $page) {
	            echo '<li class="page-item active"><a class="page-link" href="?start='.$i.'">'.$i.'<span class="sr-only">(current)</span></a></li>';
	        } else {
	            echo '<li><a class="page-link" href="?start='.$i.'">'.$i.'</a></li>';
	        }
	    }
	    echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>
	    <li class="page-item"><a class="page-link" href="?start='.$jmlpage.'">'.$jmlpage.'</a></li>';
	  } else {
	    # Belakang
	    echo '<li class="page-item"><a class="page-link" href="?start=1">1</a></li>
	    <li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
	    for ($i=($jmlpage-5);$i<=$jmlpage;$i++) {
	        if ($i == $page) {
	            echo '<li class="page-item active"><a class="page-link" href="?start='.$i.'">'.$i.'<span class="sr-only">(current)</span></a></li>';
	        } else {
	            echo '<li><a class="page-link" href="?start='.$i.'">'.$i.'</a></li>';
	        }
	    }
	  }
	} else {
	  for ($i=1;$i<=$jmlpage;$i++) {
	      if ($i == $page) {
	          echo '<li class="page-item active"><a class="page-link" href="?start='.$i.'">'.$i.'<span class="sr-only">(current)</span></a></li>';
	      } else {
	          echo '<li class="page-item"><a class="page-link" href="?start='.$i.'">'.$i.'</a></li>';
	      }
	  }
	}

	echo '
		</ul>
	</nav>';
	?>

	<!-- Template Follow-Up Section -->
	<div class="card mb-3">
		<div class="card-header">
			<h5 class="mb-0"><i class="fa-solid fa-message"></i> Template Follow-Up</h5>
			<small class="text-muted">Klik tombol salin untuk menyalin template ke clipboard</small>
		</div>
		<div class="card-body">
			<div class="row">
				<!-- Template 1 -->
				<div class="col-md-4 mb-3">
					<div class="border rounded p-3 h-100">
						<h6 class="text-primary mb-2">Template 1 - Perkenalan Produk</h6>
						<div class="template-content mb-3" id="template1">
							Halo [nama], terima kasih sudah registrasi di EPIC Hub 🙌. Tahukah [nama], Silvergram & Goldgram itu asli, bersertifikat, dan bisa dimulai dari gramasi kecil lho✨. Banyak yang sudah menjadikannya tabungan & hadiah berkelas. Mau saya tunjukkan contoh peluangnya untuk [nama]?
						</div>
						<button type="button" class="btn btn-outline-primary btn-sm w-100" onclick="copyTemplate('template1', this)">
							<i class="fa-solid fa-copy"></i> Salin Template
						</button>
					</div>
				</div>

				<!-- Template 2 -->
				<div class="col-md-4 mb-3">
					<div class="border rounded p-3 h-100">
						<h6 class="text-success mb-2">Template 2 - Peluang Bisnis</h6>
						<div class="template-content mb-3" id="template2">
							Hai [nama], dengan registrasi di EPIC Hub berarti [nama] sudah selangkah lebih dekat dengan peluang bisnis emas & perak 🚀. Jadi EPI Channel bisa dapat komisi hingga 1,6% + akses sistem modern yang memudahkan transaksi. Mau saya kirimkan detail cara kerjanya biar lebih jelas?
						</div>
						<button type="button" class="btn btn-outline-success btn-sm w-100" onclick="copyTemplate('template2', this)">
							<i class="fa-solid fa-copy"></i> Salin Template
						</button>
					</div>
				</div>

				<!-- Template 3 -->
				<div class="col-md-4 mb-3">
					<div class="border rounded p-3 h-100">
						<h6 class="text-warning mb-2">Template 3 - Investasi Aman</h6>
						<div class="template-content mb-3" id="template3">
							Halo [nama], emas & perak selalu jadi pilihan aman untuk simpan nilai kekayaan 💎. Bersama Emas Perak Indonesia, [nama] nggak hanya bisa menabung, tapi juga membangun bisnis jangka panjang. Boleh saya share ringkasan manfaatnya biar [nama] lebih paham?
						</div>
						<button type="button" class="btn btn-outline-warning btn-sm w-100" onclick="copyTemplate('template3', this)">
							<i class="fa-solid fa-copy"></i> Salin Template
						</button>
					</div>
				</div>
			</div>

			<!-- Alert untuk feedback copy -->
			<div id="copyAlert" class="alert alert-success alert-dismissible fade" role="alert" style="display: none;">
				<i class="fa-solid fa-check-circle"></i> <strong>Berhasil!</strong> Template telah disalin ke clipboard.
				<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>
		</div>
	</div>

	<script>
	function copyTemplate(templateId, button) {
		const templateElement = document.getElementById(templateId);
		const text = templateElement.textContent.trim();
		
		// Copy to clipboard
		navigator.clipboard.writeText(text).then(function() {
			// Show success feedback
			showCopyAlert();
			
			// Change button temporarily
			const originalHTML = button.innerHTML;
			button.innerHTML = '<i class="fa-solid fa-check"></i> Tersalin!';
			button.classList.remove('btn-outline-primary', 'btn-outline-success', 'btn-outline-warning');
			button.classList.add('btn-success');
			
			// Reset button after 2 seconds
			setTimeout(function() {
				button.innerHTML = originalHTML;
				button.classList.remove('btn-success');
				if (templateId === 'template1') {
					button.classList.add('btn-outline-primary');
				} else if (templateId === 'template2') {
					button.classList.add('btn-outline-success');
				} else {
					button.classList.add('btn-outline-warning');
				}
			}, 2000);
		}).catch(function(err) {
			// Fallback for older browsers
			const textArea = document.createElement('textarea');
			textArea.value = text;
			document.body.appendChild(textArea);
			textArea.select();
			document.execCommand('copy');
			document.body.removeChild(textArea);
			
			showCopyAlert();
		});
	}

	function showCopyAlert() {
		const alert = document.getElementById('copyAlert');
		alert.style.display = 'block';
		alert.classList.add('show');
		
		// Auto hide after 3 seconds
		setTimeout(function() {
			alert.classList.remove('show');
			setTimeout(function() {
				alert.style.display = 'none';
			}, 150);
		}, 3000);
	}
	</script>

	<form action="" method="post">
	<div class="card mb-3">
		<div class="card-body">
			<textarea name="kontenfu" placeholder="Konten follow up via WhatsApp" class="form-control"></textarea>
			<small class="form-text text-muted mb-3">Silahkan menambah kata-kata follow up sebelum klik link whatsapp klien di atas. Gunakan shortcode [nama] untuk menampilkan nama klien</small>
			<br/><input type="submit" value="Simpan" class="btn btn-primary">
		</div>
	</div>
	</form>
<?php
}
showfooter(); 
?>