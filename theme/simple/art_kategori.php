<form action="" method="get">
<div class="card mb-3">
	<div class="card-body">
	  <div class="row">	    
	    <div class="col-sm-12">
	    	<div class="input-group">
				  <input type="text" class="form-control" name="cari" placeholder="Cari <?= ucwords($slugartikel); ?>" value="<?= $_GET['cari'] ??= '';?>">
				  <?php 
				  $select = array('','','');
				  if (isset($_GET['status']) && is_numeric($_GET['status'])) {
				  	$select[$_GET['status']] = ' selected';
				  }
				  ?>
				  <select name="status" class="form-select">
				  	<option value="">Semua <?= ucwords($slugartikel); ?></option>
				  	<option value="1"<?=$select[1];?>><?= ucwords($slugartikel); ?> Free</option>
				  	<option value="2"<?=$select[2];?>><?= ucwords($slugartikel); ?> Premium</option>
				  </select>
				  <?php
				  $produk = db_select("SELECT * FROM `sa_page` WHERE `pro_harga` IS NOT NULL");
				  if (count($produk) > 0) {
				  	echo '<select name="produk" class="form-select">
				  	<option value="">Semua Produk</option>';
				  	foreach ($produk as $produk) {
				  		echo '<option value="'.$produk['page_id'].'"';
				  		if (isset($_GET['produk']) && $produk['page_id'] == $_GET['produk']) {
				  			echo ' selected';
				  		}
				  		echo '>'.$produk['page_judul'].'</option>';
				  	}
				  	echo '</select>';
				  }
				  ?>
				  <input type="submit" value=" Cari " class="btn btn-secondary">
				</div>	      
	    </div>
	  </div>
	</div>
</div>
</form>

<?php
if (isset($data['kat_id'])) {
	echo '<h1 class="m-4">'.$showtitle.'</h1>';
	echo $showadminbutton;

	$jmlperpage = 10;
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
		$where .= " AND (`art_judul` LIKE '%".$s."%' 
							OR `art_konten` LIKE '%".$s."%')";
	}

	if (isset($_GET['status']) && is_numeric($_GET['status'])) {
		$where .= " AND `art_role`=".$_GET['status'];
	}

	if (isset($_GET['produk']) && is_numeric($_GET['produk'])) {
		$where .= " AND FIND_IN_SET('".$_GET['produk']."', `art_product`)";	
	}

	if (isset($datamember['mem_role']) && $datamember['mem_role'] > 5) {
		$showall = ""; 
	} else {
		$showall = "`art_status`=1 AND";
	}

	$artikel = db_select("SELECT * FROM `sa_artikel` 
		LEFT JOIN `sa_member` ON `sa_artikel`.`art_writer` = `sa_member`.`mem_id`
		WHERE ".$showall."`art_kat_id`=".$data['kat_id'].$where." 
		ORDER BY `art_tglpublish` DESC 
		LIMIT ".$start.",".$jmlperpage);

	if (count($artikel) > 0) {
		foreach ($artikel as $artikel) :
            if (!empty($artikel['art_img'])) {
                $thumb = function_exists('epi_image_safe_url') ? epi_image_safe_url($artikel['art_img']) : ($weburl.'upload/'.$artikel['art_img']);
            } else {
                $thumb = $weburl.'img/noimage.jpg';
            }
		?>
		<div class="d-block d-sm-none">
	    <img src="<?= $thumb;?>" class="img-fluid" alt="<?= $artikel['art_judul'];?>"/>
	  </div>
		<div class="row g-0 border rounded overflow-hidden flex-md-row mb-4 shadow-sm h-md-250 position-relative">
      <div class="col-auto d-none d-md-block">
        <img src="<?= $thumb;?>" class="img-fluid" style="max-width:200px; max-height:200px" alt="<?= $artikel['art_judul'];?>"/>
      </div>
      <div class="col p-4 d-flex flex-column position-static">
        <h3 class="mb-0"><?= $artikel['art_judul'];?></h3>
        <div class="mb-1 text-body-secondary"><?= date('d M Y H:i',strtotime($artikel['art_tglpublish']));?>
        	 by <?= $artikel['mem_nama'];?>
        </div>
        <p class="card-text mb-auto"><?= pendekin(strip_tags($artikel['art_konten']),170); ?></p>
        <a href="<?= $weburl.$slugartikel.'/'.$artikel['art_slug'];?>" class="icon-link gap-1 icon-link-hover stretched-link">
          Selanjutnya..
          <svg class="bi"><use xlink:href="#chevron-right"/></svg>
        </a>
      </div>
    </div>
		
		<?php
		endforeach;

		$jumlah = db_var("SELECT COUNT(*) FROM `sa_artikel` 
		WHERE `art_kat_id`=".$data['kat_id'].$where);

		$jmlpage = floor(($jumlah/$jmlperpage)+1);
		echo '
		<nav aria-label="Page navigation" class="mt-3">
		  <ul class="pagination">';
		if ($jmlpage > 10) {
		  if ($page <= 4){
		    # Depan
		    for ($i=1;$i<=5;$i++) {
		        if ($i == $page) {
		            echo '<li class="page-item active"><a class="page-link" href="?start='.$i.'">'.$i.'<span class="visually-hidden">(current)</span></a></li>';
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
		            echo '<li class="page-item active"><a class="page-link" href="?start='.$i.'">'.$i.'<span class="visually-hidden">(current)</span></a></li>';
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
		            echo '<li class="page-item active"><a class="page-link" href="?start='.$i.'">'.$i.'<span class="visually-hidden">(current)</span></a></li>';
		        } else {
		            echo '<li><a class="page-link" href="?start='.$i.'">'.$i.'</a></li>';
		        }
		    }
		  }
		} else {
		  for ($i=1;$i<=$jmlpage;$i++) {
		      if ($i == $page) {
		          echo '<li class="page-item active"><a class="page-link" href="?start='.$i.'">'.$i.'<span class="visually-hidden">(current)</span></a></li>';
		      } else {
		          echo '<li class="page-item"><a class="page-link" href="?start='.$i.'">'.$i.'</a></li>';
		      }
		  }
		}

		echo '
			</ul>
		</nav>';

	} else {
		echo '
		<div class="row g-0 border rounded overflow-hidden flex-md-row mb-4 shadow-sm h-md-250 position-relative">
			<div class="col p-4 d-flex flex-column position-static">
				<h2>Maaf, Belum ada '.ucwords($slugartikel).' 🙏</h2>
				<p>Sabar ya... kami sedang menyiapkan '.ucwords($slugartikel).' yang oke buat kamu</p>
			</div>
		</div>';	
	}
	
} else {
	include('art_404.php');
}
