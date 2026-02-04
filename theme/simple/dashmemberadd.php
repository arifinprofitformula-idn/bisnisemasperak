<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($datamember['mem_role'] < 5) { die(); exit(); }
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {		
	$editmember = db_row("SELECT * FROM `sa_member` 
		LEFT JOIN `sa_sponsor` ON `sa_sponsor`.`sp_mem_id` = `sa_member`.`mem_id` 
		WHERE `mem_id`=".$_GET['edit']);
	if (isset($editmember['mem_nama'])) {
		$head['pagetitle'] = 'Edit Data '.$editmember['mem_nama'];
	} else {
		$head['pagetitle'] = 'Add Member';		
	}
} else {
	$head['pagetitle'] = 'Add Member';			
}

$settings = getsettings();
$head['scripthead'] = '
<style type="text/css">
    .password-wrapper {
      position: relative;
    }
    
    .password-wrapper input[type="password"] {
      padding-right: 30px; /* Ruang untuk ikon */
    }
    
    .password-wrapper .toggle-password {
      position: absolute;
      top: 50%;
      right: 5px;
      transform: translateY(-50%);
      cursor: pointer;
    }
</style>
<script>
  function togglePassword() {
    var passwordInput = document.getElementById("password");
    var toggleBtn = document.getElementById("togglePassword");

    if (passwordInput.type === "password") {
      passwordInput.type = "text";
      toggleBtn.innerHTML = \'<i class="fas fa-eye-slash text-secondary"></i>\';
    } else {
      passwordInput.type = "password";
      toggleBtn.innerHTML = \'<i class="fas fa-eye text-secondary"></i>\';
    }
  }
</script>';
showheader($head);

if (isset($_POST['nama']) && !empty($_POST['nama']) && isset($_POST['email']) && validemail($_POST['email'])) {
	if (isset($editmember['mem_id'])) {
		$eximember = " AND `mem_id` != ".$editmember['mem_id'];
	} else {
		$eximember = '';
	}

	if (db_exist("SELECT `mem_email` FROM `sa_member` 
		WHERE `mem_email`='".cek($_POST['email'])."'".$eximember)) {
		echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
		  <strong>Error!</strong> Email sudah ada yang menggunakan
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	} else {
		$defaultkey = array('nama','email','password','whatsapp','kodeaff','status','role');
		$datalain = '';
		$settings = getsettings();
        foreach ($_POST as $key => $value) {
            if (in_array($key, $defaultkey)) {
                ${$key} = cek($value);
            } else {
                $datalain .= '['.txtonly(strtolower($key)).'|'.cek($value).']';
            }

			if ($key != 'password' && $key != 'kodeaff') {
				$settings = str_replace('['.$key.']',$value, $settings);
        }

        $currentUserRole = isset($datamember['mem_role']) ? (int)$datamember['mem_role'] : 1;
        $role = isset($role) ? (int)$role : (isset($editmember['mem_role']) ? (int)$editmember['mem_role'] : 1);
        if ($currentUserRole !== 9) {
            $role = isset($editmember['mem_role']) ? (int)$editmember['mem_role'] : 1;
        } else {
            if (!in_array($role, array(1,5,6,7,8,9), true)) { $role = 1; }
        }
		}

		if (isset($_FILES) && count($_FILES) > 0) {
			$max_size = 1024000;
			$whitelist_ext = array('jpeg','jpg','png','gif');
			$whitelist_type = array('image/jpeg', 'image/jpg', 'image/png','image/gif');
			$pic_dir = caripath('theme').'/upload';
			if (isset($editmember['mem_id'])) {
				$memberid = $editmember['mem_id'];
			} else {
				$memberid = 'XXX'.rand(1000,9999).'XXX';
			}
			
			if( ! file_exists( $pic_dir ) ) { mkdir( $pic_dir ); }

			foreach($_FILES as $field => $files) {
				$filename = $memberid.'_'.$field;
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
	        
	        if (class_exists('Imagick')) {
		        // Metode dengan Imagick
		        $img = new Imagick();
		        $img->readImage($file);
		        $width = $img->getImageWidth();
		        if ($width > 800) {
		            $width = 800;
		        }
		        $img->setimagebackgroundcolor('white');
		        $img->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
		        $img->setImageCompression(Imagick::COMPRESSION_JPEG);
		        $img->setImageCompressionQuality(80);
		        $img->resizeImage($width, 800, Imagick::FILTER_CATROM, 1, true);
		        $img->stripImage();
		        $img->writeImage($target_file);
	    } else {
	        // Metode alternatif tanpa Imagick
	        if (function_exists('imagecreatefromstring') && function_exists('imagecreatetruecolor') && function_exists('imagecopyresampled')) {
	        	$img = imagecreatefromstring(file_get_contents($file));
	        	if ($img !== false) {
	        		$width = imagesx($img);
	        		$height = imagesy($img);
	        		$new_width = 800;
	        		$new_height = floor($height * ($new_width / $width));

	        		// Resize image
	        		$tmp_img = imagecreatetruecolor($new_width, $new_height);
	        		imagecopyresampled($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

	        		// Save the image
	        		if ($imageFileType == 'jpg' || $imageFileType == 'jpeg') {
	        		    imagejpeg($tmp_img, $target_file, 80);
	        		} elseif ($imageFileType == 'png') {
	        		    imagepng($tmp_img, $target_file);
	        		} elseif ($imageFileType == 'gif') {
	        		    imagegif($tmp_img, $target_file);
	        		}

	        		imagedestroy($img);
	        		imagedestroy($tmp_img);
	        	} else {
	        		// Jika gagal membuat image dari string, simpan apa adanya
	        		if (!move_uploaded_file($file, $target_file)) {
	        			$txterror = 'Gagal menyimpan gambar.';
	        			$uploadOk = 0;
	        		}
	        	}
	        } else {
	        	// GD tidak tersedia: simpan file tanpa resize
	        	if (!move_uploaded_file($file, $target_file)) {
	        		$txterror = 'Gagal menyimpan gambar. Modul GD tidak tersedia.';
	        		$uploadOk = 0;
	        	}
	        }
	    }

	    if (file_exists($target_file)) {
	    	$datalain .= '['.txtonly(strtolower($field)).'|'.$filename.'.'.$imageFileType.']';     
	    }
	    	}
			}
		}

		if (isset($_POST['kodeaff']) && !empty($_POST['kodeaff'])) {
			if (isset($editmember['mem_kodeaff']) && $_POST['kodeaff'] == $editmember['mem_kodeaff']) {
				$kodeaff = $editmember['mem_kodeaff'];
			} else {
				$kodeaff = cekkodeaff(txtonly(strtolower($_POST['kodeaff'])));
			}
		} else {
			if (isset($editmember['mem_kodeaff'])) {
				$kodeaff = $editmember['mem_kodeaff'];
			} else {
				$kodeaff = cekkodeaff(txtonly(strtolower($nama)));	
			}
		}

		if (isset($_POST['password']) && !empty($_POST['password'])) {
			if (isset($editmember['mem_id'])) {
				$password = ",`mem_password` = '".create_hash($_POST['password'])."'";
			} else {
				$password = $_POST['password'];
			}
		} else {
			if (isset($editmember['mem_id'])) {
				$password = '';
			} else {
				$password = randomword();
			}
		}		
		
		if (isset($whatsapp)) { 
			$whatsapp = formatwa($whatsapp); 
		} else { 
			if (isset($editmember['mem_whatsapp'])) {
				$whatsapp = $editmember['mem_whatsapp'];
			} else {
				$whatsapp = ''; 
			}
		}

		$settings = str_replace('[password]',$password, $settings);
		$settings = str_replace('[kodeaff]',$kodeaff, $settings);

		$network = '['.numonly($_POST['id_sponsor']).']'.db_var("SELECT `sp_network` FROM `sa_sponsor` WHERE `sp_mem_id`=".numonly($_POST['id_sponsor']));

		# Simpan ke Database
		if (isset($editmember['mem_id'])) {
			# Update Data
            $cek = db_query("UPDATE `sa_member` SET 
            `mem_nama`='".$nama."',
            `mem_email`='".$email."',
            `mem_whatsapp`='".$whatsapp."',
            `mem_kodeaff`='".$kodeaff."',
            `mem_datalain`='".$datalain."',
            `mem_status`='".$status."',
            `mem_role`='".$role."'
            ".$password."
            WHERE `mem_id`=".$editmember['mem_id']);

			if (db_exist("SELECT `sp_mem_id` FROM `sa_sponsor` WHERE `sp_mem_id`=".$editmember['mem_id'])) {
				$cek = db_query("UPDATE `sa_sponsor` SET `sp_sponsor_id`=".numonly($_POST['id_sponsor']).",`sp_network`='".$network."' 
					WHERE `sp_mem_id`=".$editmember['mem_id']);
			} else {
				$cek = db_insert("INSERT INTO `sa_sponsor` (`sp_mem_id`,`sp_sponsor_id`,`sp_network`) VALUES (".$editmember['mem_id'].",".numonly($_POST['id_sponsor']).",'".$network."')");
			}

			$editmember = db_row("SELECT * FROM `sa_member` 
			LEFT JOIN `sa_sponsor` ON `sa_sponsor`.`sp_mem_id` = `sa_member`.`mem_id` 
			WHERE `mem_id`=".$editmember['mem_id']);

		} else {
			# Tambah Data
            $newuserid = db_insert("INSERT INTO `sa_member` (
                `mem_nama`,`mem_email`,`mem_password`,`mem_whatsapp`,`mem_kodeaff`,
                `mem_datalain`,`mem_tgldaftar`,`mem_status`,`mem_role`) 
            VALUES ('".$nama."','".$email."','".create_hash($password)."',
                '".$whatsapp."','".$kodeaff."','".$datalain."','".date('Y-m-d H:i:s')."',
                ".numonly($_POST['status']).",".$role.")");
			
			if (is_numeric($newuserid)) {
				$cek = db_insert("INSERT INTO `sa_sponsor` (`sp_mem_id`,`sp_sponsor_id`,`sp_network`) VALUES ($newuserid,".numonly($_POST['id_sponsor']).",'".$network."')");
				if (isset($memberid)) {
					$datalain = str_replace($memberid,$newuserid,$datalain);
					db_query("UPDATE `sa_member` SET `mem_datalain`='".$datalain."' WHERE `mem_id`=".$newuserid);
					$files = glob($pic_dir . '/'.$memberid.'*');					
					// Loop semua file yang ditemukan dan ganti nama file
					foreach ($files as $file) {
					    // Buat nama file baru dengan mengganti teks XXX123XXX dengan ID member baru
					    $newName = str_replace($memberid, $newuserid, $file);
					    // Ganti nama file
					    rename($file, $newName);
					}
				}

				# Kirim Notif yuk
				$customfield['newpass'] = $password;
				sa_notif('daftar',$newuserid,$customfield);

			} else {
				echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
					  <strong>Error!</strong> '.db_error().'
					  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
					</div>';
			}
		}
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
		}
	}
}	

?>
<form action="" method="post" enctype="multipart/form-data">
<div class="card">
  <div class="card-body">
	  <div class="mb-3 row">
	    <label class="col-sm-4 col-form-label">ID Sponsor</label>
	    <div class="col-sm-8">
	      <input type="number" class="form-control" name="id_sponsor" value="<?= $editmember['sp_sponsor_id'] ??= '';?>">
	    </div>
	  </div>
	  <?php
	  if (isset($editmember['mem_id'])) {
	  	# Update Profil
			$dataform = extractdata($editmember);
		  echo form_builder('profil',$dataform);
	  } else {
	  	# Registrasi Member
	  	echo form_builder('register');
	  }
	  ?>
	  <div class="mb-3 row">
	    <label class="col-sm-4 col-form-label">Status</label>
	    <div class="col-sm-3">
	      <select name="status" class="form-select">
	      	<?php
	      	$select[1] = $select[2] = '';
	      	if (isset($editmember['mem_status'])) { $select[$editmember['mem_status']] = ' selected'; }
	      	?>
	      	<option value="1"<?php echo $select[1];?>>Free Member</option>
	      	<option value="2"<?php echo $select[2];?>>Premium Member</option>
	      </select>
	    </div>
	  </div>
  <?php $isAdmin = isset($datamember['mem_role']) && (int)$datamember['mem_role'] === 9; ?>
  <?php if ($isAdmin) : ?>
  <div class="mb-3 row">
    <label class="col-sm-4 col-form-label">Role</label>
    <div class="col-sm-3">
      <select name="role" class="form-select">
        <?php
        $select = array();
        $select[1] = $select[5] = $select[6] = $select[7] = $select[8] = $select[9] = '';
        if (isset($editmember['mem_role'])) { $select[$editmember['mem_role']] = ' selected'; }
        ?>
        <option value="1"<?php echo $select[1];?>>Member</option>
        <option value="5"<?php echo $select[5];?>>Admin Staff</option>
        <option value="6"<?php echo $select[6];?>>Finance Manager</option>
        <option value="7"<?php echo $select[7];?>>Operasional Manager</option>
        <option value="8"<?php echo $select[8];?>>Writer Manager</option>
        <option value="9"<?php echo $select[9];?>>Administrator</option>
      </select>
      <div class="form-text">Perubahan role mempengaruhi akses pengguna. Gunakan dengan bijak.</div>
    </div>
  </div>
  <?php else : ?>
  <div class="mb-3 row">
    <label class="col-sm-4 col-form-label">Role</label>
    <div class="col-sm-3">
      <?php
      $roleMap = array(1=>'Member',5=>'Admin Staff',6=>'Finance Manager',7=>'Operasional Manager',8=>'Writer Manager',9=>'Administrator');
      $currentRoleCode = isset($editmember['mem_role']) ? (int)$editmember['mem_role'] : 1;
      $currentRoleLabel = isset($roleMap[$currentRoleCode]) ? $roleMap[$currentRoleCode] : 'Member';
      ?>
      <input type="text" class="form-control" value="<?= $currentRoleLabel; ?>" disabled>
      <div class="form-text text-muted">Hanya Administrator dapat mengubah Role.</div>
    </div>
  </div>
  <?php endif; ?>
	  
	  <input type="submit" class="btn btn-success" value=" SIMPAN ">
	</div>
</div>
</form>
<?php 
showfooter();
?>
