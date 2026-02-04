<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
$head['pagetitle']='Edit Profil';
$head['scripthead'] = <<<'HTML'
<style type="text/css">
    .password-wrapper { position: relative; }
    .password-wrapper input[type="password"],
    .password-wrapper input[type="text"] { padding-right: 40px; }
    .password-wrapper .toggle-password {
      position: absolute; top: 50%; right: 6px; transform: translateY(-50%);
      cursor: pointer; z-index: 2; padding: 2px 6px; line-height: 1;
    }
    /* Indikator validasi konfirmasi password */
    .pwd-indicator { margin-top: 6px; font-size: 0.92rem; }
    .pwd-indicator[aria-live] { min-height: 1.25rem; }
    .is-warning { border-color: #f4c542 !important; box-shadow: 0 0 0 0.2rem rgba(244,197,66,.25); }
    /* Rekomendasi password */
    .pwd-suggestions { margin-top: 8px; }
    .pwd-suggestion-item { margin-right: 4px; margin-bottom: 4px; }
    /* Tombol WhatsApp style */
    .btn-whatsapp { border: none; color: #000; background-color: #25D366; padding: 2px 8px; font-size: 0.85rem; }
    .btn-whatsapp:hover { background-color: #1EBE59; color: #000; }
    /* Chip silver shining untuk rekomendasi */
    .chip-silver { 
      background: linear-gradient(145deg, #f0f0f0, #c0c0c0);
      color: #000; border: 1px solid #b0b0b0; 
      box-shadow: 0 1px 2px rgba(0,0,0,0.08);
      padding: 2px 8px; font-size: 0.85rem;
    }
</style>
<script>
  document.addEventListener("DOMContentLoaded", function(){
    var form = document.querySelector("form");
    var pwdInput = form ? (form.querySelector("input[name='password']") || form.querySelector("input[type='password']") || document.getElementById("password")) : null;
    var confirmInput = document.getElementById("password_confirm");

    function toggleVisibility(input, btn){
      if (!input) return;
      if (input.type === "password") { input.type = "text"; btn.innerHTML = '<i class="fas fa-eye-slash text-secondary"></i>'; }
      else { input.type = "password"; btn.innerHTML = '<i class="fas fa-eye text-secondary"></i>'; }
    }

    function attachToggle(targetInput, btnId){
      if (!targetInput) return;
      if (!targetInput.parentElement.classList.contains('password-wrapper')){
        var wrap = document.createElement('div'); wrap.className = 'password-wrapper';
        targetInput.parentElement.insertBefore(wrap, targetInput);
        wrap.appendChild(targetInput);
        var btn = document.createElement('button');
        btn.type = 'button'; btn.id = btnId; btn.className = 'btn btn-sm btn-outline-secondary toggle-password';
        btn.setAttribute('aria-label','Tampilkan/sembunyikan password');
        btn.innerHTML = '<i class="fas fa-eye text-secondary"></i>';
        wrap.appendChild(btn);
        btn.addEventListener('click', function(){ toggleVisibility(targetInput, btn); });
      }
    }

    // Password strength evaluasi
    function getStrength(p){
      var len = p.length;
      var hasLower = /[a-z]/.test(p);
      var hasUpper = /[A-Z]/.test(p);
      var hasDigit = /\d/.test(p);
      var hasSymbol = /[^A-Za-z0-9]/.test(p);
      var score = (len >= 12 ? 1 : 0) + (hasLower?1:0) + (hasUpper?1:0) + (hasDigit?1:0) + (hasSymbol?1:0);
      var label, color;
      if (!p) { label = 'Kosong'; color = 'secondary'; }
      else if (score >= 5) { label = 'Kuat'; color = 'success'; }
      else if (score >= 3) { label = 'Sedang'; color = 'warning'; }
      else { label = 'Lemah'; color = 'danger'; }
      return {score: score, label: label, color: color};
    }

    // Levenshtein sederhana untuk deteksi typo
    function levenshtein(a,b){
      var m = [], i, j;
      if(a.length === 0) return b.length;
      if(b.length === 0) return a.length;
      for(i = 0; i <= b.length; i++){ m[i] = [i]; }
      for(j = 0; j <= a.length; j++){ m[0][j] = j; }
      for(i = 1; i <= b.length; i++){
        for(j = 1; j <= a.length; j++){
          m[i][j] = Math.min(
            m[i-1][j] + 1,
            m[i][j-1] + 1,
            m[i-1][j-1] + (a[j-1] === b[i-1] ? 0 : 1)
          );
        }
      }
      return m[b.length][a.length];
    }

    // UI indikator untuk konfirmasi password
    var confirmIndicator = null;
    function ensureConfirmIndicator(){
      if (!confirmInput) return null;
      if (!confirmIndicator){
        confirmIndicator = document.createElement('div');
        confirmIndicator.className = 'pwd-indicator';
        confirmIndicator.id = 'pwdConfirmIndicator';
        confirmIndicator.setAttribute('aria-live','polite');
        confirmInput.parentElement.appendChild(confirmIndicator);
      }
      return confirmIndicator;
    }

    function setConfirmClasses(state){
      // state: 'ok' | 'warn' | 'error' | 'neutral'
      if (!confirmInput) return;
      confirmInput.classList.remove('is-valid','is-invalid','is-warning','border-warning');
      if (state === 'ok'){ confirmInput.classList.add('is-valid'); }
      else if (state === 'warn'){ confirmInput.classList.add('is-warning'); }
      else if (state === 'error'){ confirmInput.classList.add('is-invalid'); }
    }

    function updateConfirmIndicator(){
      var ind = ensureConfirmIndicator(); if (!ind || !pwdInput || !confirmInput) return;
      var pwd = pwdInput.value || '';
      var cfm = confirmInput.value || '';
      var strength = getStrength(pwd);
      if (!cfm){ ind.textContent = ''; setConfirmClasses('neutral'); return; }
      if (cfm === pwd){
        if (strength.color === 'success'){
          ind.innerHTML = '<span class="text-success">Cocok dan kuat, jangan lupa simpan untuk perubahan</span>';
          setConfirmClasses('ok');
        } else if (strength.color === 'warning'){
          ind.innerHTML = '<span class="text-warning">Cocok, namun disarankan diperkuat (min. 12 karakter + variasi). Simpan untuk perubahan</span>';
          setConfirmClasses('warn');
        } else {
          ind.innerHTML = '<span class="text-danger">Cocok, tetapi password terlalu lemah. Tetap simpan untuk melakukan perubahan</span>';
          setConfirmClasses('error');
        }
      } else {
        // deteksi kemungkinan typo
        var d = levenshtein(pwd, cfm);
        if (d <= 2 && Math.abs(pwd.length - cfm.length) <= 1){
          ind.innerHTML = '<span class="text-warning">Kemungkinan salah ketik, periksa kembali</span>';
          setConfirmClasses('warn');
        } else {
          ind.innerHTML = '<span class="text-danger">Password tidak cocok</span>';
          setConfirmClasses('error');
        }
      }
    }

    // Rekomendasi password
    function genWord(){
      var words = ['Rumah','Kucing','Langit','Pantai','Kopi','Buku','Hutan','Gunung','Bunga','Sahabat','Semangat','Jakarta','Mentari','Telaga','Angin'];
      return words[Math.floor(Math.random()*words.length)];
    }
    function genSpecial(){ var s = ['@','#','$','%','!','?','&']; return s[Math.floor(Math.random()*s.length)]; }
    function genNumber(){
      var base = new Date().getFullYear();
      var variants = [base, base+1, base-1, Math.floor(1000+Math.random()*9000)];
      return variants[Math.floor(Math.random()*variants.length)].toString();
    }
    function generateStrongMemorable(){
      var w1 = genWord(); var w2 = genWord(); while (w2 === w1) { w2 = genWord(); }
      var special = genSpecial(); var num = genNumber();
      var candidate = w1 + special + num;
      if (candidate.length < 12) { candidate = w1 + w2 + special + num; }
      candidate = candidate[0].toUpperCase() + candidate.slice(1);
      return candidate;
    }
    function buildSuggestionsUI(){
      if (!pwdInput) return;
      var wrap = pwdInput.parentElement.classList.contains('password-wrapper') ? pwdInput.parentElement : pwdInput.parentElement;
      var container = document.createElement('div');
      container.className = 'pwd-suggestions';
      var btnGen = document.createElement('button');
      btnGen.type = 'button'; btnGen.className = 'btn btn-sm btn-whatsapp me-2';
      btnGen.textContent = 'Klik Disini untuk Mendapatkan Rekomendasi Password';
      var list = document.createElement('div');
      list.className = 'd-flex flex-wrap';
      container.appendChild(btnGen);
      container.appendChild(list);
      wrap.parentElement.appendChild(container);
      function render(){
        list.innerHTML = '';
        for (var i=0;i<3;i++){
          var sug = generateStrongMemorable();
          var chip = document.createElement('button');
          chip.type = 'button'; chip.className = 'btn btn-sm chip-silver pwd-suggestion-item';
          chip.textContent = sug;
          chip.addEventListener('click', function(e){
            var val = e.target.textContent;
            if (pwdInput){ pwdInput.value = val; }
            if (confirmInput){ confirmInput.value = val; }
            updateConfirmIndicator();
          });
          list.appendChild(chip);
        }
      }
      btnGen.addEventListener('click', render);
      render();
    }

    attachToggle(pwdInput, 'togglePassword');
    var toggleConfirmBtn = document.getElementById('togglePasswordConfirm');
    if (confirmInput && toggleConfirmBtn){
      toggleConfirmBtn.addEventListener('click', function(){ toggleVisibility(confirmInput, toggleConfirmBtn); });
    }

    // Bind juga jika ada span toggle bawaan di template
    var spanToggles = document.querySelectorAll('span.toggle-password, span#togglePassword');
    spanToggles.forEach(function(el){
      var target = null;
      if (el.id && el.id.indexOf('Confirm') !== -1 && confirmInput){ target = confirmInput; }
      else if (pwdInput){ target = pwdInput; }
      if (!target){
        var wrap = el.closest('.password-wrapper');
        if (wrap){ target = wrap.querySelector('input[type="password"], input[type="text"]'); }
      }
      el.addEventListener('click', function(){ if (target) toggleVisibility(target, el); });
    });

    if (pwdInput){ pwdInput.addEventListener('input', updateConfirmIndicator); }
    if (confirmInput){ confirmInput.addEventListener('input', updateConfirmIndicator); }
    buildSuggestionsUI();

    if (form && pwdInput && confirmInput){
      form.addEventListener('submit', function(e){
        if (pwdInput.value && pwdInput.value !== confirmInput.value){
          e.preventDefault();
          var alert = document.getElementById('pwdMismatchAlert');
          if (!alert){
            alert = document.createElement('div');
            alert.id = 'pwdMismatchAlert';
            alert.className = 'alert alert-danger alert-dismissible fade show';
            alert.setAttribute('role','alert');
            alert.innerHTML = '<strong>Error!</strong> Password baru dan konfirmasi tidak cocok.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            var cardBody = document.querySelector('.card .card-body');
            if (cardBody){ cardBody.insertBefore(alert, cardBody.firstChild); }
          }
          confirmInput.focus();
        }
      });
    }
  });
</script>
HTML;
showheader($head);
$editmember = db_row("SELECT * FROM `sa_member` 
		LEFT JOIN `sa_sponsor` ON `sa_sponsor`.`sp_mem_id` = `sa_member`.`mem_id` 
		WHERE `mem_id`=".$iduser);
$dataform = extractdata($editmember);

if (isset($_POST['nama']) && !empty($_POST['nama']) && isset($_POST['email']) && validemail($_POST['email'])) {
	if (db_exist("SELECT `mem_email` FROM `sa_member` 
		WHERE `mem_email`='".cek($_POST['email'])."' AND `mem_id` != ".$iduser)) {
		echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
		  <strong>Error!</strong> Email sudah ada yang menggunakan
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	} else {
		$defaultkey = array('nama','email','password','password_confirm','whatsapp','kodeaff');
		$datalain = '';		

		foreach ($_POST as $key => $value) {			
			if (in_array($key, $defaultkey)) {
				${$key} = cek($value);
			} else {
				$datalain .= '['.txtonly(strtolower($key)).'|'.cek($value).']';
			}
		}

		if (isset($_FILES) && count($_FILES) > 0) {
			$max_size = 1024000;
			$whitelist_ext = array('jpeg','jpg','png','gif');
			$whitelist_type = array('image/jpeg', 'image/jpg', 'image/png','image/gif');
			$pic_dir = caripath('theme').'/upload';
			
			if( ! file_exists( $pic_dir ) ) { mkdir( $pic_dir ); }

			foreach($_FILES as $field => $files) {
				if (isset($files['name']) && !empty($files['name'])) {
					$filename = $iduser.'_'.$field;
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
				    $target_file = $target_file . '.' . $imageFileType;

				    // Proses gambar tanpa Imagick (GD-only)
				    $imgData = @file_get_contents($file);
				    if ($imgData !== false && function_exists('imagecreatefromstring')) {
				        $src = @imagecreatefromstring($imgData);
				        if ($src !== false) {
				            $origW = imagesx($src);
				            $origH = imagesy($src);
				            $maxW = 800;
				            $newW = ($origW > $maxW) ? $maxW : $origW;
				            $newH = (int) floor($origH * ($newW / $origW));

				            $dst = imagecreatetruecolor($newW, $newH);
				            if ($imageFileType === 'png') {
				                imagealphablending($dst, false);
				                imagesavealpha($dst, true);
				                $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
				                imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
				            } elseif ($imageFileType === 'gif') {
				                $transIndex = imagecolortransparent($src);
				                if ($transIndex >= 0) {
				                    $transColor = imagecolorsforindex($src, $transIndex);
				                    $transIndexDst = imagecolorallocate($dst, $transColor['red'], $transColor['green'], $transColor['blue']);
				                    imagefill($dst, 0, 0, $transIndexDst);
				                    imagecolortransparent($dst, $transIndexDst);
				                }
				            }

				            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
				            if ($imageFileType == 'jpg' || $imageFileType == 'jpeg') {
				                imagejpeg($dst, $target_file, 80);
				            } elseif ($imageFileType == 'png') {
				                imagepng($dst, $target_file, 6);
				            } elseif ($imageFileType == 'gif') {
				                imagegif($dst, $target_file);
				            } else {
				                imagejpeg($dst, $target_file, 80);
				            }

				            imagedestroy($src);
				            imagedestroy($dst);
				        } else {
				            // Fallback: jika GD gagal membaca, simpan file apa adanya
				            move_uploaded_file($file, $target_file);
				        }
				    } else {
				        // Fallback: jika GD tidak tersedia, simpan file apa adanya
				        move_uploaded_file($file, $target_file);
				    }

				    $datalain .= '[' . txtonly(strtolower($field)) . '|' . $filename . '.' . $imageFileType . ']';
					}

	    	} else {
	    		if (isset($dataform[$field]) && !empty($dataform[$field])) {
	    			$datalain .= '['.txtonly(strtolower($field)).'|'.$dataform[$field].']';	
	    		} else {
	    			$datalain .= '['.txtonly(strtolower($field)).'| ]';	
	    		}	    		
	    	}
			}
		}

		if (isset($_POST['kodeaff']) && !empty($_POST['kodeaff'])) {
			if ($_POST['kodeaff'] == $editmember['mem_kodeaff']) {
				$kodeaff = $editmember['mem_kodeaff'];
			} else {
				$kodeaff = cekkodeaff(txtonly(strtolower($_POST['kodeaff'])));
			}
		} else {
			$kodeaff = $editmember['mem_kodeaff'];	
		}

		if (isset($_POST['password']) && !empty($_POST['password'])) {
			if (!isset($_POST['password_confirm']) || $_POST['password'] !== $_POST['password_confirm']) {
				$password_error = true;
				$password = '';
			} else {
				$password = ",`mem_password` = '".create_hash($_POST['password'])."'";
			}
		} else {
			$password = '';
		}		
		
		if (isset($whatsapp)) { $whatsapp = formatwa($whatsapp); } else { $whatsapp = ''; }

		$cek = db_query("UPDATE `sa_member` SET 
			`mem_nama`='".$nama."',
			`mem_email`='".$email."',
			`mem_whatsapp`='".$whatsapp."',
			`mem_kodeaff`='".$kodeaff."',
			`mem_datalain`='".$datalain."'
			".$password."
			WHERE `mem_id`=".$iduser);
		
		$editmember = db_row("SELECT * FROM `sa_member` 
		LEFT JOIN `sa_sponsor` ON `sa_sponsor`.`sp_mem_id` = `sa_member`.`mem_id` 
		WHERE `mem_id`=".$iduser);
		$dataform = extractdata($editmember);
	}

	if (isset($cek)) {
		if ($cek === false) {
			echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
			  <strong>Error!</strong> '.db_error().'
			  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>';
		} else {
			echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
			  <strong>Ok!</strong> Data member telah disimpan.
			  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>';
			if (!empty($password_error)) {
				echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
				  <strong>Perhatian!</strong> Password baru dan konfirmasi tidak cocok. Password tidak diubah.
				  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
				</div>';
			}
		}
	}
}	

?>
<form action="" method="post" enctype="multipart/form-data">
<div class="card">
  <div class="card-body">
    <?php if (isset($datamember['mem_role']) && (int)$datamember['mem_role'] === 9): ?>
      <div class="mb-3 row">
        <label class="col-sm-4 col-form-label text-start">ID Member</label>
        <div class="col-sm-3">
          <input type="number" id="mem_id" class="form-control" value="<?= $editmember['mem_id'];?>" name="mem_id" disabled>
        </div>
      </div>
    <?php endif; ?>
    <?php echo form_builder('profil',$dataform); ?>
    <div class="mb-3 row">
      <label class="col-sm-4 col-form-label text-start">Konfirmasi Password Baru</label>
      <div class="col-sm-4">
        <div class="password-wrapper">
          <input type="password" id="password_confirm" class="form-control" name="password_confirm" placeholder="Ulangi password baru">
        </div>
        <div class="form-text">Pastikan password sudah kuat dan sesuai dengan keinginanmu</div>
      </div>
    </div>
    <input type="submit" class="btn btn-success" value=" SIMPAN ">
  </div>
</div>
</form>
<?php showfooter(); ?>