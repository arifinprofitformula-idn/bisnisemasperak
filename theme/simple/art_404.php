<form action="" method="get">
<div class="card mb-3">
	<div class="card-body">
	  <div class="row">	    
	    <div class="col-sm-12">
	    	<div class="input-group">
				  <input type="text" class="form-control" name="cari" placeholder="Cari Artikel" value="<?= $_GET['cari'] ??= '';?>">
				  <?php 
				  $select = array('','','');
				  if (isset($_GET['status']) && is_numeric($_GET['status'])) {
				  	$select[$_GET['status']] = ' selected';
				  }
				  ?>
				  <select name="status" class="form-select">
				  	<option value="">Semua Artikel</option>
				  	<option value="1"<?=$select[1];?>>Artikel Free</option>
				  	<option value="2"<?=$select[2];?>>Artikel Premium</option>
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
	
<div class="row g-0 border rounded overflow-hidden flex-md-row mb-4 shadow-sm h-md-250 position-relative">
	<div class="col p-4 d-flex flex-column position-static bg-white">

	<h1>404</h1>
	<p>Maaf, halaman yang anda cari belum ketemu nih</p>
	<p><a href="<?= $weburl.($settings['url_artikel']??='artikel');?>" class="btn btn-primary">Kembali ke Home Artikel</a></p>
	</div>
</div>