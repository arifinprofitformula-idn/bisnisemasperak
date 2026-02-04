<?php
$kategori = db_row("SELECT * FROM `sa_kategori` WHERE `kat_id`=".$idkat);
if (isset($kategori['kat_id'])) :
  if (isset($_POST['art_judul']) && !empty($_POST['art_judul']) && isset($_POST['art_konten']) && !empty($_POST['art_konten'])) {    
    if (isset($_FILES['thumb'])) {

      $max_size = 1024000;
      $files = $_FILES['thumb'];
      $whitelist_ext = array('jpeg','jpg','png','gif');
      $whitelist_type = array('image/jpeg', 'image/jpg', 'image/png','image/gif');
      $pic_dir = caripath('theme').'/upload';
      
      if( ! file_exists( $pic_dir ) ) { mkdir( $pic_dir ); }
      
      $gambar = $editgambar = '';

      if (isset($files['name']) && !empty($files['name'])) {
        $filename = txtonly(strtolower($_POST['art_judul']));
        $target_file = $pic_dir.'/'.$filename;
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($files["name"],PATHINFO_EXTENSION));
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
          $txterror = "Maaf, hanya support JPG, JPEG, PNG & GIF saja.";
          $uploadOk = 0;
        }
        //Check that the file is of the right type
        if (!in_array($files["type"], $whitelist_type)) {
          $txterror = "Maaf, hanya support JPG, JPEG, PNG & GIF saja.";
          $uploadOk = 0;
        }
        // Check file size
        if ($files["size"] > $max_size) {
          $txterror = 'Maaf, gambar terlalu besar. Max. 1Mb';
          $uploadOk = 0;
        }
        if ($uploadOk == 1) {
          $file = $files["tmp_name"];
          $target_file = $target_file.'.'.$imageFileType;

          $img = new Imagick();
          $img->readImage($file);
          $width = $img->getImageWidth();
          if ($width > 800) {
              $width = 800;
          }
          $img->setimagebackgroundcolor('white');
          //$img = $img->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
          $img->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
          $img->setImageCompression(Imagick::COMPRESSION_JPEG);
          $img->setImageCompressionQuality(80);
          $img->resizeImage($width,800,Imagick::FILTER_CATROM,1,TRUE);
          $img->stripImage();
          $img->writeImage($target_file);
          $gambar = $filename.'.'.$imageFileType;
          $editgambar = ",`art_img`='".$gambar."'";
        } else {
          echo '
          <div class="alert alert-danger alert-dismissible fade show" id="peringatan">
            <strong>Error!</strong> '.$txterror.'
            <button type="button" class="btn-close" id="tutup"></button>
          </div>';
        }
      }
    }

    if (!isset($txterror)) {
      if (empty($_POST['art_slug'])) {
        $art_slug = txtonly(strtolower($_POST['art_judul']));
      } else {
        $art_slug = txtonly(strtolower($_POST['art_slug']));    
      }

      $isiartikel = str_replace('<p data-f-id="pbf" style="text-align: center; font-size: 14px; margin-top: 30px; opacity: 0.65; font-family: sans-serif;">Powered by <a href="https://www.froala.com/wysiwyg-editor?pb=1" title="Froala Editor">Froala Editor</a></p>','',$_POST['art_konten']);

      if (isset($artikel['art_id'])) {        
        #UPDATE DATA        
        $art_slug = cekurlpost($art_slug,$artikel['art_id']);
        $cek = db_query("UPDATE `sa_artikel` SET           
          `art_judul` = '".cek($_POST['art_judul'])."',
          `art_slug` = '".$art_slug."',          
          `art_konten` = '".cek($isiartikel)."',
          `art_role` = '".numonly($_POST['role'])."',
          `art_product` = '".numonly($_POST['produk'])."',
          `art_status` = '".numonly($_POST['status'])."'
          ".$editgambar."
          WHERE `art_id`=".$artikel['art_id']);

        $artikel['art_judul'] = cek($_POST['art_judul']);
        $artikel['art_slug'] = $art_slug;
        $artikel['art_konten'] = $isiartikel;
        $artikel['art_role'] = numonly($_POST['role']);
        $artikel['art_product'] = numonly($_POST['produk']);
        $artikel['art_status'] = numonly($_POST['status']);
        
      } else {
        #INSERT DATA
        $art_slug = cekurlpost($art_slug);
        $cek = db_insert("INSERT INTO `sa_artikel` (`art_tglpublish`,`art_kat_id`,`art_judul`,`art_slug`,`art_img`,`art_konten`,`art_role`,`art_product`,`art_status`,`art_writer`) 
          VALUES ('".date('Y-m-d H:i:s')."','".$kategori['kat_id']."','".cek($_POST['art_judul'])."','".$art_slug."','".$gambar."','".cek($isiartikel)."','".cek($_POST['role'])."','".cek($_POST['produk'])."',1,".$datamember['mem_id'].")");
      }

      if ($cek === false) {
        echo '
        <div class="alert alert-danger alert-dismissible fade show" id="peringatan">
          <strong>Error!</strong> '.db_error().'
          <button type="button" class="btn-close" id="tutup"></button>
        </div>';
      } else {
        echo '
        <div class="alert alert-success alert-dismissible fade show" id="peringatan">
          <strong>Ok!</strong> Artikel telah disimpan. <a href="'.$weburl.'artikel/'.$art_slug.'">Lihat '.ucwords($slugartikel).'</a>
          <button type="button" class="btn-close" id="tutup"></button>
        </div>';
      }
    }
  }
?>
<form action="" method="post" enctype="multipart/form-data">
<div class="row">
  <div class="col-md-9 mb-3">
    <div class="card">
      <div class="card-header">
        Tambah <?=ucwords($slugartikel);?> di <?= $kategori['kat_nama'];?>
      </div>
      <div class="card-body">        
          <div class="form-floating mb-3">               
            <input type="text" class="form-control" name="art_judul" id="judul" value="<?= $artikel['art_judul'] ??= '';?>" required>
            <label for="judul">Judul</label> 
          </div>
          <div class="input-group mb-3">
            <span class="input-group-text" id="basic-addon3"><?= $weburl.$slugartikel;?>/</span>
              <input type="text" class="form-control" value="<?= $artikel['art_slug'] ??= '';?>" id="art_slug" name="art_slug" >        
          </div>

          <div class="mb-3 row">
            <textarea rows="5" id="editor" name="art_konten"><?= htmlspecialchars($artikel['art_konten'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>        
          </div>
        
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card">
      <div class="card-body">
          <div class="form-floating mb-3">
            <select name="status" class="form-select" id="minstatus">
              <?php 
              $status = array('','');
              if (isset($artikel['art_status']) && $artikel['art_status'] > 0) {
                $status[$artikel['art_status']] = ' selected'; 
              }
              ?>
              <option value="0"<?=$status[0];?>>Draft</option>
              <option value="1"<?=$status[1];?>>Publish</option>
            </select>
            <label for="minstatus">Publish</label>
          </div>
          <div class="form-floating mb-3">
            <select name="role" class="form-select" id="minstatus">
              <?php 
              $role = array('','','');
              if (isset($artikel['art_role']) && $artikel['art_role'] > 0) {
                $role[$artikel['art_role']] = ' selected'; 
              }
              ?>
              <option value="0"<?=$role[0];?>>Pengunjung</option>
              <option value="2"<?=$role[2];?>>Premium</option>
              <option value="1"<?=$role[1];?>>Free Member</option>
            </select>
            <label for="minstatus">Member Status</label>
          </div>

          <div class="form-floating mb-3">
            <select name="produk" class="form-select" id="syaratproduk">
              <option value="0">Siapa saja</option>
              <?php
              $produk = db_select("SELECT * FROM `sa_page` WHERE `pro_harga` IS NOT NULL");
              foreach ($produk as $produk) {
                echo '<option value="'.$produk['page_id'].'"';
                if (isset($artikel['art_product']) && $artikel['art_product'] == $produk['page_id']) {
                  echo ' selected';
                }
                echo '>'.$produk['page_judul'].'</option>';
              }
              ?>
            </select>
            <label for="syaratproduk">Khusus Buyer</label>
          </div>

          <div class="form-floating mb-3">
            <input type="file" class="form-control" name="thumb" id="artthumb">
            <label for="artthumb">Thumbnail</label>
            <small class="form-text text-muted">Rekomendasi ukuran: 200 x 200 pixel</small>
            <div class="mt-2" id="previewthumb">
              <?php 
              if (isset($artikel['art_img']) && $artikel['art_img'] != '') {
                echo '<img src="'.$weburl.'upload/'.$artikel['art_img'].'?id='.rand(100,999).'" class="img-fluid img-thumbnail" style="max-width: 200px">';
              }
              ?>
            </div>
          </div>
          <input type="hidden" name="art_kat_id" value="<?= $_GET['add']??=0;?>"/>
          <input type="submit" class="btn btn-success" name="" value=" SIMPAN ">
      </div>
    </div>
  </div>
</div>
</form>
<?php endif; ?>