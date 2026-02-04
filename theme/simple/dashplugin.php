<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
$head['pagetitle']='Plugins';
$pluginFolder = caripath('theme').'/plugin';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['pluginZip'])) {
  $uploadDir = $pluginFolder;
  $zipFile = $_FILES['pluginZip']['tmp_name'];
  $zipName = $_FILES['pluginZip']['name'];

  if (move_uploaded_file($zipFile, $uploadDir . $zipName)) {
      $zip = new ZipArchive;
      $res = $zip->open($uploadDir . $zipName);
      if ($res === TRUE) {
        $zip->extractTo($uploadDir);
        $zip->close();
        $notif = '<div class="alert alert-success alert-dismissible fade show" role="alert">
          <strong>Ok!</strong> Plugin berhasil diupload dan extract</strong>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
      } else {
        $notif = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
          Gagal mengekstraksi file zip plugin</strong>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
      }
      unlink($uploadDir . $zipName);
  } else {
      $notif = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
          Gagal mengupload file plugin</strong>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
  }
}

showheader($head);

if (isset($_GET['aktif']) && !empty($_GET['aktif'])) {
  if (isset($settings['plugin_aktif'])) {
    $plugin_aktif = explode(',', $settings['plugin_aktif']);
    if (!in_array($_GET['aktif'],$plugin_aktif)) {
      array_push($plugin_aktif, $_GET['aktif']);
    } 
  } else {
    $plugin_aktif = array($_GET['aktif']);
  }
  
  $newsetting['plugin_aktif'] = implode(',', $plugin_aktif);
  $settings = updatesettings($newsetting);
  include($pluginFolder.'/'.$_GET['aktif'].'/index.php');

  # Jalankan fungsi aktifasi plugin jika ada
  $functionName = $_GET['aktif'] . '_install';
  if (function_exists($functionName)) {
    $functionName();
  }

  echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
    <strong>Ok!</strong> Plugin '.$_GET['aktif'].' telah diaktifkan</strong>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>';

} elseif (isset($_GET['nonaktif']) && !empty($_GET['nonaktif'])) {
  if (isset($settings['plugin_aktif'])) {
    $plugin_aktif = explode(',', $settings['plugin_aktif']);
    $plugin_aktif = array_diff($plugin_aktif, array($_GET['nonaktif']));
    $newsetting['plugin_aktif'] = implode(',', $plugin_aktif);
    $settings = updatesettings($newsetting);
    
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
      <strong>Ok!</strong> Plugin '.$_GET['nonaktif'].' telah dinonaktifkan</strong>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
  }
}

echo $notif??='';

echo '
<form action="" method="post" enctype="multipart/form-data">
  <div class="card mb-3">
    <div class="card-body">
      <div class="row">     
        <div class="col-sm-12">
          <div class="input-group">
            <span class="input-group-text" id="basic-addon1">Tambah Plugin</span>
            <input type="file" class="form-control" name="pluginZip" />
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
      <tr><th>Plugin</th><th>Description</th></tr>
    </thead>
    <tbody>';


$items = scandir($pluginFolder);
if (is_array($items) && count($items) >= 3) {
  $folders = array_filter($items, function ($item) use ($pluginFolder) {
    $indexFile = $pluginFolder . '/' . $item . '/index.php';
    return is_dir($pluginFolder . '/' . $item) && !in_array($item, ['.', '..']) && file_exists($indexFile) && isPluginIndexFile($indexFile);
  });

  // Output tabel daftar plugin
  if (isset($settings['plugin_aktif']) && !empty($settings['plugin_aktif'])) {
    $plugin_aktif = explode(',', $settings['plugin_aktif']);
  } else {
    $plugin_aktif = array();
  }

  foreach ($folders as $folder) {
      $indexFile = $pluginFolder . '/' . $folder . '/index.php';
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
          if (in_array($folder,$plugin_aktif)) {
            echo '<a href="plugin?nonaktif='.$folder.'">Non Aktif</a>';
          } else {
            echo '<a href="plugin?aktif='.$folder.'">Aktifkan</a>';
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
            echo ' <a href="'.trim($matches['URI'][1]??='').'">Plugin URI</a>';
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