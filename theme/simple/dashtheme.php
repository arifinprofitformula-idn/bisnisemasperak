<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if (isset($_GET['aktif']) && !empty($_GET['aktif'])) {
  $newsetting['theme'] = $_GET['aktif'];
  $settings = updatesettings($newsetting);

  $notif = '<div class="alert alert-success alert-dismissible fade show" role="alert">
    <strong>Ok!</strong> Theme '.$_GET['aktif'].' telah diaktifkan</strong>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>';

}

$head['pagetitle']='Theme';
$themeFolder = __DIR__ . '/../../theme';
showheader($head);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['themeZip'])) {
  $uploadDir = $themeFolder;
  $zipFile = $_FILES['themeZip']['tmp_name'];
  $zipName = $_FILES['themeZip']['name'];

  if (move_uploaded_file($zipFile, $uploadDir . $zipName)) {
      $zip = new ZipArchive;
      $res = $zip->open($uploadDir . $zipName);
      if ($res === TRUE) {
        $zip->extractTo($uploadDir);
        $zip->close();
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
          <strong>Ok!</strong> Plugin berhasil diupload dan extract</strong>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
      } else {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
          Gagal mengekstraksi file zip plugin</strong>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
      }
      unlink($uploadDir . $zipName);
  } else {
      echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
          Gagal mengupload file plugin</strong>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
  }
}

$items = scandir($themeFolder);
echo $notif??='';
echo '
<form action="" method="post" enctype="multipart/form-data">
  <div class="card mb-3">
    <div class="card-body">
      <div class="row">     
        <div class="col-sm-12">
          <div class="input-group">
            <span class="input-group-text" id="basic-addon1">Tambah Theme</span>
            <input type="file" class="form-control" name="themeZip" />
            <input type="submit" class="btn btn-secondary" value="Upload" />
          </div>
        </div>
      </div>
    </div>
  </div>
</form>
<div class="table-responsive">
  <table class="table table-hover table-bordered">
    <thead class="table-secondary">
      <tr><th>Theme</th><th>Description</th></tr>
    </thead>
    <tbody>';



if (is_array($items) && count($items) >= 3) {
  $folders = array_filter($items, function ($item) use ($themeFolder) {
    $indexFile = $themeFolder . '/' . $item . '/index.php';
    return is_dir($themeFolder . '/' . $item) && !in_array($item, ['.', '..']) && file_exists($indexFile) && isPluginIndexFile($indexFile);
  });

  foreach ($folders as $folder) {
      $indexFile = $themeFolder . '/' . $folder . '/index.php';
      if (file_exists($indexFile)) {
          $fileContent = file_get_contents($indexFile);
          preg_match('/Name\s*:\s*([^\n]+)/', $fileContent, $matches['Name']);
          preg_match('/URI\s*:\s*([^\n]+)/', $fileContent, $matches['URI']);
          preg_match('/Author\s*:\s*([^\n]+)/', $fileContent, $matches['Author']);
          preg_match('/Version\s*:\s*([^\n]+)/', $fileContent, $matches['Version']);
          preg_match('/Description\s*:\s*([^\n]+)/', $fileContent, $matches['Description']);

          echo '<tr>';
          echo '<td><strong>' . trim($matches['Name'][1]) . '</strong><br/>';
          echo '<small>';
          if (isset($settings['theme']) && $settings['theme'] == $folder) {
            echo 'Digunakan';
          } else {
            echo '<a href="theme?aktif='.$folder.'">Pakai ini</a>';
          }
          
          echo ' | 
          <a href="#" data-bs-toggle="modal" data-bs-target="#konfirmasi" data-bs-nama="'.$matches['Name'][1].'" 
          data-bs-id="'.$folder.'">Hapus</a></small>';
          echo '</td>';
          echo '<td>' . trim($matches['Description'][1]??='-'). '<br/><small>';
          if (isset($matches['Version'][1]) && !empty($matches['Version'][1])) {            
            echo 'Version: '.trim($matches['Version'][1]??='');
          }
          if (isset($matches['Author'][1]) && !empty($matches['Author'][1])) {
            echo ' By ' . trim($matches['Author'][1]??='');
          }
          if (isset($matches['URI'][1]) && !empty($matches['URI'][1])) {
            echo ' <a href="'.trim($matches['URI'][1]??='').'">Theme URI</a>';
          }
          echo '</small></td>';
          echo '</tr>';
      }
    }

} else {
  echo '<tr><td colspan="2">Belum ada plugin terinstall. Silahkan upload file zip dari plugin anda. Atau upload ke folder <code>plugin</code></td></tr>';
}

    echo '
    </tbody>
  </table>
</div>';

showfooter();
?>