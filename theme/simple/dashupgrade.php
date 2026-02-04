<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($datamember['mem_role'] < 9) { die(); exit(); }

$head['pagetitle'] = 'Upgrade Otomatis';
showheader($head);
echo '
<div class="card mb-3">
	<div class="card-body">';
if (isset($_GET['upgrade']) && ($_GET['upgrade'] == 'all' || $_GET['upgrade'] == 'lite')) {
	# Ambil Data login dulu
	echo '
	<p>Pastikan anda sudah melakukan backup database dan file sebelum melakukan upgrade. <a href="https://www.youtube.com/watch?v=4Gvq9gOsZpA">Panduan backup klik di sini</a></p>
	<form action="'.$weburl.'dashboard/upgrade" method="post">
		<div class="mb-3 row">
      <label class="col-sm-3 col-form-label text-start">Username Cafebisnis</label>
      <div class="col-sm-9">
        <input type="text" class="form-control" name="username" required>
        <small class="form-text text-muted">Username untuk login ke <a href="https://cafebisnis.com">Cafebisnis.com</a>. 
        Gunakan akun yang anda gunakan untuk order SimpleAff</small>
      </div>
    </div>
    <div class="mb-3 row">
      <label class="col-sm-3 col-form-label text-start">Password Cafebisnis</label>
      <div class="col-sm-9">
        <input type="password" class="form-control" name="password" required>
        <small class="form-text text-muted">Password untuk login ke Cafebisnis.com.</small>
      </div>      
    </div>
    <input type="hidden" class="form-control" name="upgrade" value="'.$_GET['upgrade'].'">
    <input type="submit" class="btn btn-success" value=" Login dan Upgrade Sekarang ">
	</form>
	';
} elseif (isset($_POST['upgrade']) && isset($_POST['username']) && !empty($_POST['username']) && isset($_POST['password']) && !empty($_POST['password'])) {
	# Mulai Upgrade
	$data = array(
		'username' => $_POST['username'],
		'password' => $_POST['password'],
		'upgrade' => $_POST['upgrade']
	);
	$json = postData('https://cafe'.'bisnis.'.'com/updatesimpleaffplus.php', $data);
	$hasil = json_decode($json,TRUE);
	if (isset($hasil['error'])) {
		echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
		  <strong>Error!</strong> '.$hasil['error'].'.
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	} else {
		// Mengunduh file pembaruan    
    $updatePath = caripath('theme');
    // Mendownload file pembaruan dari server
    $updateUrl = $hasil['downloadfolder'].$hasil['file'];
    $headers = get_headers($updateUrl, 1);
		$headers = array_change_key_case($headers, CASE_LOWER);
    
		$fileExists = strpos($headers[0], '200 OK') !== false;
		$contentLength = isset($headers['content-length']) ? (int)$headers['content-length'] : 0;
		
		if ($fileExists && $contentLength > 0) {
    	
	    $downloadedFile = $updatePath.$hasil['file'];
	    file_put_contents($downloadedFile, file_get_contents($updateUrl));
	    // Mengekstrak file pembaruan
	    $zip = new ZipArchive;
	    if ($zip->open($downloadedFile) === TRUE) {
        $zip->extractTo($updatePath);
        $zip->close();
        # Update versi
        include('upgrade.php');
        $newsettings['ver'] = $hasil['versi'];
        updatesettings($newsettings);
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
				  <strong>Sukses!</strong> Extract file berhasil. Anda sudah menggunakan versi terbaru SimpleAff
				  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
				</div>';
	    } else {
	      echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
				  <strong>Error!</strong> Gagal mengekstract file upgrade.
				  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
				</div>';
	    }
	  } else {
		  echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
			  <strong>Error!</strong> File update tidak ditemukan.
			  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>';
		}
	}
} else {
	$ver = getsettings('ver');

	$data = getData('https://caf'.'ebisnis.c'.'om/update'.'simpleaffplus.php');
	if ($data != '') {
		$dataversi = json_decode($data,TRUE);
		if (isset($dataversi['versi'])) {
			$versionComparison = version_compare($ver, $dataversi['versi']);
			if ($versionComparison < 0) {
			    echo '
			    <div class="text-center">
			    <h3>Butuh Upgrade Nih</h3>
			    <p>Versi saat ini (' . $ver . ') lebih rendah daripada versi terbaru (' . $dataversi['versi'] . '). Yuk upgrade otomatis sekarang</p>';
			    if (isset($dataversi['lite'])) {
			    	echo '<p><a href="?upgrade=all" class="btn btn-success mb-2">Upgrade Total</a> <a href="?upgrade=lite" class="btn btn-primary mb-2">Upgrade file baru saja</a></p>';
			    } else {
			    	echo '<p><a href="?upgrade=all" class="btn btn-success mb-2">Upgrade</a></p>';
			    }
			    
			    echo '
			    </div>
			    <p><strong>Apa yang baru di '.$dataversi['nama'].'?</strong></p>
			    <p>'.$dataversi['new'].'</p>';
			} elseif ($versionComparison === 0) {
			    echo '<div class="text-center">
			    <h3>Selamat! Anda sudah menggunakan versi terbaru.<br/>
			    <small>( SimpleAff plus ver.' . $dataversi['versi'] . ' )</small></h3>';
			} else {
			    echo 'Wait, kayaknya ada yang salah deh sebab versi anda saat ini (' . $ver . ') lebih tinggi daripada versi terbaru (' . $dataversi['versi'] . ').';
			}
		} elseif (isset($dataversi['error'])) {
			echo $dataversi['error'];
		} else {
			echo '<h2>Ada masalah saat menghubungi Cafebisnis</h2>
			<pre>'.$data.'</pre>';
		}
	} else {
		echo 'Gagal menghubungi server Cafebisnis';
	}
}

echo '</div>
</div>';
showfooter();
?>