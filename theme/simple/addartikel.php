<?php
// Bootstrapping jika file diakses langsung (bukan via router/theme)
if (!function_exists('db_row')) {
  // include fungsi dan konfigurasi agar db_row, getsettings, dll tersedia
  $rootDir = dirname(__DIR__, 2);
  $fn = $rootDir . DIRECTORY_SEPARATOR . 'fungsi.php';
  if (file_exists($fn)) { include_once($fn); }
}
// Pastikan $idkat terdefinisi ketika diakses langsung
if (!isset($idkat)) {
  $idkat = (isset($_GET['add']) && is_numeric($_GET['add'])) ? intval($_GET['add']) : 0;
}
// Siapkan $settings dan $slugartikel agar tidak undefined
if (!isset($settings) || !is_array($settings)) { $settings = getsettings(); }
$slugartikel = $settings['url_artikel'] ?? 'artikel';
// Fallback untuk $datamember agar proses simpan tidak error saat diakses langsung
if (!isset($datamember) || !is_array($datamember)) { $datamember = []; }
if (!isset($datamember['mem_id'])) { $datamember['mem_id'] = 0; }

$kategori = db_row("SELECT * FROM `sa_kategori` WHERE `kat_id`=".$idkat);
if (isset($kategori['kat_id'])) :
  if (isset($_POST['art_judul']) && !empty($_POST['art_judul']) && isset($_POST['art_konten']) && !empty($_POST['art_konten'])) {    
    if (isset($_FILES['thumb'])) {

      $max_size = 2097152; // 2MB
      $files = $_FILES['thumb'];
      $whitelist_ext = array('jpeg','jpg','png','gif');
      $whitelist_type = array('image/jpeg', 'image/jpg', 'image/png','image/gif');
      $rootBase = dirname(__DIR__, 2);
      $pic_dir = $rootBase.'/img';
      
      if( ! file_exists( $pic_dir ) ) { mkdir( $pic_dir, 0755, true ); }
      
      $gambar = $editgambar = '';

      if (isset($files['name']) && !empty($files['name'])) {
        $baseName = isset($_POST['art_judul']) ? strtolower((string)$_POST['art_judul']) : 'gambar-artikel';
        $baseName = preg_replace('/\s+/', '-', $baseName);
        $baseName = preg_replace('/[^a-z0-9\-]+/', '', $baseName);
        $baseName = preg_replace('/\-+/', '-', $baseName);
        $baseName = trim($baseName, '-');
        if ($baseName==='') { $baseName = 'gambar-artikel'; }
        $filename = $baseName;
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

          $imgClass = 'Imagick';
          if (class_exists($imgClass)) {
            $img = new $imgClass();
            $img->readImage($file);
            $width = $img->getImageWidth();
            if ($width > 1200) { $width = 1200; }
            $img->setimagebackgroundcolor('white');
            if (defined('Imagick::LAYERMETHOD_FLATTEN')) { $img->mergeImageLayers(constant('Imagick::LAYERMETHOD_FLATTEN')); }
            if (defined('Imagick::COMPRESSION_JPEG')) { $img->setImageCompression(constant('Imagick::COMPRESSION_JPEG')); }
            $img->setImageCompressionQuality(82);
            if (defined('Imagick::FILTER_CATROM')) { $img->resizeImage($width,800,constant('Imagick::FILTER_CATROM'),1,TRUE); } else { $img->resizeImage($width,800,0,1,TRUE); }
            $img->stripImage();
            $img->writeImage($target_file);
          } else {
            // Fallback tanpa Imagick: gunakan GD jika tersedia, jika tidak copy apa adanya
            $ok = false;
            $ext = $imageFileType;
            $img = false;
            if ($ext==='jpg' || $ext==='jpeg') { if (function_exists('imagecreatefromjpeg')) { $img = @imagecreatefromjpeg($file); } }
            elseif ($ext==='png') { if (function_exists('imagecreatefrompng')) { $img = @imagecreatefrompng($file); } }
            elseif ($ext==='gif') { if (function_exists('imagecreatefromgif')) { $img = @imagecreatefromgif($file); } }
            if ($img === false) {
              $ok = @move_uploaded_file($file, $target_file);
            } else {
              $width = imagesx($img); $height = imagesy($img);
              $maxW = 1200; $maxH = 800; $ratio = min($maxW/$width, $maxH/$height, 1);
              $newW = (int)floor($width*$ratio); $newH = (int)floor($height*$ratio);
              $tmp = imagecreatetruecolor($newW, $newH);
              imagecopyresampled($tmp, $img, 0,0,0,0, $newW,$newH, $width,$height);
              if ($ext==='jpg' || $ext==='jpeg') { $ok = imagejpeg($tmp, $target_file, 82); }
              elseif ($ext==='png') { $ok = imagepng($tmp, $target_file, 6); }
              elseif ($ext==='gif') { $ok = imagegif($tmp, $target_file); }
              @imagedestroy($tmp); @imagedestroy($img);
            }
            if (!$ok) { $txterror = 'Gagal menyimpan gambar.'; $uploadOk = 0; }
          }
          if ($uploadOk == 1) {
            $gambar = $filename.'.'.$imageFileType;
            $editgambar = ",`art_img`='".$gambar."'";
          }
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
        $art_slug_input = $_POST['art_judul'];
      } else {
        $art_slug_input = $_POST['art_slug'];
      }
      $art_slug = function_exists('epi_slugify') ? epi_slugify($art_slug_input) : txtonly(strtolower($art_slug_input));

      $isiartikel = str_replace('<p data-f-id="pbf" style="text-align: center; font-size: 14px; margin-top: 30px; opacity: 0.65; font-family: sans-serif;">Powered by <a href="https://www.froala.com/wysiwyg-editor?pb=1" title="Froala Editor">Froala Editor</a></p>','',$_POST['art_konten']);

      if (isset($artikel['art_id'])) {        
        #UPDATE DATA        
        $art_slug = function_exists('epi_unique_slug') ? epi_unique_slug($art_slug,'sa_artikel','art_slug','art_id',$artikel['art_id']) : cekurlpost($art_slug,$artikel['art_id']);
        $cek = db_query("UPDATE `sa_artikel` SET           
          `art_judul` = '".cek($_POST['art_judul'])."',
          `art_slug` = '".$art_slug."',          
          `art_konten` = '".cek($isiartikel)."',
          `art_role` = '".numonly($_POST['role'])."',
          `art_product` = '".(isset($_POST['produk']) && is_array($_POST['produk']) ? implode(',', array_map('intval', $_POST['produk'])) : (int)($_POST['produk']??0))."',
          `art_status` = '".numonly($_POST['status'])."'
          ".$editgambar."
          WHERE `art_id`=".$artikel['art_id']);

        $artikel['art_judul'] = cek($_POST['art_judul']);
        $artikel['art_slug'] = $art_slug;
        $artikel['art_konten'] = $isiartikel;
        $artikel['art_role'] = numonly($_POST['role']);
        $artikel['art_product'] = (isset($_POST['produk']) && is_array($_POST['produk']) ? implode(',', array_map('intval', $_POST['produk'])) : (int)($_POST['produk']??0));
        $artikel['art_status'] = numonly($_POST['status']);
        
        // AUDIT TRAIL
        $logDir = dirname(__DIR__, 2) . '/logs';
        if (!file_exists($logDir)) { @mkdir($logDir, 0755, true); }
        $logFile = $logDir . '/audit_access.log';
        $logEntry = date('Y-m-d H:i:s') . " | User: " . ($datamember['mem_id']??0) . " | Update Article: " . $artikel['art_id'] . " | Products: " . $artikel['art_product'] . " | Role: " . $artikel['art_role'] . "\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
        
      } else {
        #INSERT DATA
        $art_slug = function_exists('epi_unique_slug') ? epi_unique_slug($art_slug,'sa_artikel','art_slug','art_id') : cekurlpost($art_slug);
        $cek = db_insert("INSERT INTO `sa_artikel` (`art_tglpublish`,`art_kat_id`,`art_judul`,`art_slug`,`art_img`,`art_konten`,`art_role`,`art_product`,`art_status`,`art_writer`) 
          VALUES ('".date('Y-m-d H:i:s')."','".$kategori['kat_id']."','".cek($_POST['art_judul'])."','".$art_slug."','".$gambar."','".cek($isiartikel)."','".cek($_POST['role'])."','".(isset($_POST['produk']) && is_array($_POST['produk']) ? implode(',', array_map('intval', $_POST['produk'])) : (int)($_POST['produk']??0))."',1,".$datamember['mem_id'].")");
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
            <textarea rows="8" id="editor" class="editor form-control" name="art_konten"><?= $artikel['art_konten'] ?? '' ?></textarea>
            <small class="text-muted">Anda dapat menulis konten secara visual (WYSIWYG) dan juga membuka tampilan kode HTML via tombol "HTML" pada toolbar.</small>
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

          <div class="mb-3">
            <label class="form-label">Khusus Buyer</label>
            <div class="border p-2 rounded" style="max-height: 200px; overflow-y: auto; background: #fff;">
              <?php
              // Ambil produk yang sudah terpilih
              $selectedProducts = [];
              if (isset($artikel['art_product'])) {
                // Support format lama (int) dan baru (csv)
                $val = $artikel['art_product'];
                if (is_array($val)) { $val = implode(',', $val); }
                $selectedProducts = explode(',', (string)$val);
              } elseif (!isset($artikel['art_id'])) {
                // Default new article: 0 (Siapa saja)
                $selectedProducts = ['0'];
              }
              
              // Opsi Siapa Saja
              $checked0 = in_array('0', $selectedProducts) ? 'checked' : '';
              echo '<div class="form-check">
                      <input class="form-check-input" type="checkbox" name="produk[]" value="0" id="prod0" '.$checked0.'>
                      <label class="form-check-label" for="prod0">Siapa saja (Tanpa syarat pembelian)</label>
                    </div>';

              $produkList = db_select("SELECT * FROM `sa_page` WHERE `pro_harga` IS NOT NULL ORDER BY `page_judul` ASC");
              if ($produkList) {
                echo '<hr class="my-1">';
                foreach ($produkList as $p) {
                  $checked = in_array((string)$p['page_id'], $selectedProducts) ? 'checked' : '';
                  echo '<div class="form-check">
                          <input class="form-check-input" type="checkbox" name="produk[]" value="'.$p['page_id'].'" id="prod'.$p['page_id'].'" '.$checked.'>
                          <label class="form-check-label" for="prod'.$p['page_id'].'">'.$p['page_judul'].'</label>
                        </div>';
                }
              }
              ?>
            </div>
            <small class="form-text text-muted">Pilih satu atau lebih produk. Jika "Siapa saja" dipilih, akses terbuka untuk umum.</small>
          </div>

          <div class="form-floating mb-3">
            <input type="file" class="form-control" name="thumb" id="artthumb">
            <label for="artthumb">Thumbnail</label>
            <small class="form-text text-muted">Rekomendasi ukuran: 200 x 200 pixel</small>
            <div class="mt-2" id="previewthumb">
              <?php 
              if (isset($artikel['art_img']) && $artikel['art_img'] != '') {
                $prev = function_exists('epi_image_safe_url') ? epi_image_safe_url($artikel['art_img']) : ($weburl.'upload/'.$artikel['art_img']);
                echo '<img src="'.$prev.'?id='.rand(100,999).'" class="img-fluid img-thumbnail" style="max-width: 200px">';
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
<link href="<?= $weburl;?>editor/css/froala_editor.pkgd.min.css" rel="stylesheet" type="text/css" />
<link href="<?= $weburl;?>editor/css/froala_style.min.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="<?= $weburl;?>editor/js/froala_editor.pkgd.min.js"></script>
<script>
(function(){
  function loadScript(src, cb){var s=document.createElement('script');s.src=src;s.async=true;s.onload=cb||function(){};document.head.appendChild(s);} 
  function loadCSS(href){var l=document.createElement('link');l.rel='stylesheet';l.href=href;document.head.appendChild(l);} 
  function initEditor(){
    var el = document.getElementById('editor');
    if (!el) return;
    try {
      var judul = document.getElementById('judul');
      new FroalaEditor(el, {
        imageUploadURL: '/upload_image.php',
        imageAllowedTypes: ['jpeg', 'jpg', 'png', 'gif'],
        events: {
          'image.beforeUpload': function(files){
            var editor = this;
            if (!files || !files.length) return false;
            var formData = new FormData();
            formData.append('file', files[0]);
            formData.append('judul', judul ? judul.value : 'image');
            fetch('/upload_image.php', { method: 'POST', body: formData })
              .then(function(r){ return r.json(); })
              .then(function(d){ if (d && d.link) { editor.image.insert(d.link, null, null, editor.image.get()); } })
              .catch(function(e){});
            return false;
          }
        }
      });
    } catch(e) {
      console.error('Gagal inisialisasi Froala:', e);
    }
  }
  // Slug otomatis dari judul
  (function(){
    var judul = document.getElementById('judul');
    var slug = document.getElementById('art_slug');
    function slugify(t){ t=(t||'').toLowerCase(); t=t.replace(/\s+/g,'-'); t=t.replace(/[^a-z0-9\-]+/g,''); t=t.replace(/\-+/g,'-'); return t.replace(/^\-+|\-+$/g,''); }
    if (judul && slug) {
      var userEdited = false;
      slug.addEventListener('input', function(){ userEdited = true; });
      judul.addEventListener('input', function(){ if (!userEdited) { slug.value = slugify(judul.value); } });
    }
  })();
  // Fallback CDN bila library lokal tidak tersedia
  setTimeout(function(){
    if (typeof window.FroalaEditor === 'undefined') {
      loadCSS('https://cdn.jsdelivr.net/npm/froala-editor@4.1.4/css/froala_editor.pkgd.min.css');
      loadCSS('https://cdn.jsdelivr.net/npm/froala-editor@4.1.4/css/froala_style.min.css');
      loadScript('https://cdn.jsdelivr.net/npm/froala-editor@4.1.4/js/froala_editor.pkgd.min.js', function(){
        initEditor();
      });
    } else {
      initEditor();
    }
  }, 100);
})();
</script>
