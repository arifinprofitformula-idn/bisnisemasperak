<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
showheader();
do_action('home_top');
$showdefault = 'YES';

if (isset($settings['homecanvas']) && !empty($settings['homecanvas'])) {
  $homecanvas = json_decode($settings['homecanvas'],TRUE);
  if (is_array($homecanvas) && count($homecanvas) > 0) {
    echo '<div id="dash-grid" class="content-boundary">';
    $items = [];
    foreach ($homecanvas as $modul) {
      $lebar = 1;
      if (isset($settings[$modul['canvasId']])) {
        $modsettings = json_decode($settings[$modul['canvasId']],TRUE);
        if (is_array($modsettings) && isset($modsettings['data']['lebar']) && is_numeric($modsettings['data']['lebar'])) {
          $lebar = max(1, min(3, intval($modsettings['data']['lebar'])));
        }
      }
      $items[] = [
        'moduleId' => $modul['moduleId'],
        'canvasId' => $modul['canvasId'],
        'lebar' => $lebar
      ];
    }

    $groups = [];
    $row = [];
    $sum = 0;
    foreach ($items as $it) {
      if ($sum + $it['lebar'] > 3) {
        if ($sum < 3 && count($row) > 0) {
          $row[count($row)-1]['lebar'] += (3 - $sum);
        }
        $groups[] = $row;
        $row = [];
        $sum = 0;
      }
      $row[] = $it;
      $sum += $it['lebar'];
      if ($sum === 3) {
        $groups[] = $row;
        $row = [];
        $sum = 0;
      }
    }
    if (count($row) > 0) {
      if ($sum < 3) {
        $row[count($row)-1]['lebar'] += (3 - $sum);
      }
      $groups[] = $row;
    }

    foreach ($groups as $grp) {
      echo '<div class="row g-3 g-lg-4 align-items-stretch">';
      foreach ($grp as $mod) {
        $col = $mod['lebar'] * 4;
        if (function_exists('modul_'.$mod['moduleId'])) {
          echo '<div class="col-12 col-lg-'.$col.'">';
          echo call_user_func('modul_'.$mod['moduleId'],$mod['canvasId']);
        } else {
          echo '<div class="col-12 col-lg-'.$col.'">Modul '.$mod['moduleId'].' tidak ada';
        }
        echo '</div>';
      }
      echo '</div>';
    }
    echo '</div>'; // content-boundary
    $showdefault = 'NO';
  }
}

if ($showdefault == 'YES') :
  # Tampilkan Home Default
?>

<div class="row g-3 g-lg-4 align-items-stretch">
  <div class="col">
    <?= modul_informasi(); ?>
  </div>
</div>

<div class="row g-3 g-lg-4 align-items-stretch">
  <div class="col-12 col-lg-6">
    <?= modul_affiliasi(); ?>
  </div>
  <div class="col-12 col-lg-6">
    <?= modul_landingpage(); ?>
  </div>
</div>

<div class="row g-3 g-lg-4 align-items-stretch">
  <div class="col-12 col-lg-6">    
    <?= modul_klienbaru('Klien Baru',5,'[nama] - <a href="https://wa.me/[whatsapp]" target="_blank">[whatsapp]</a> <small>([tgldaftar])</small>'); ?>
  </div>
  <div class="col-12 col-lg-6">
    <?= modul_akses(); ?>
  </div>
</div>

<div class="row g-3 g-lg-4 align-items-stretch">
  <div class="col">
    <?= modul_grafikvisitor(); ?>
  </div>
</div>

<?php 
endif;
do_action('home_bottom');
?>
<script>
  (function(){
    var boundary = document.getElementById('dash-grid');
    if (!boundary) return;
    var headers = boundary.querySelectorAll('.card-header');
    var infoCard = null;
    headers.forEach(function(h){ if (!infoCard && /Informasi/i.test(h.textContent)) { infoCard = h.closest('.card'); } });
    if (!infoCard) { infoCard = boundary.querySelector('.card'); }
    if (infoCard) { var w = infoCard.offsetWidth; if (w>0) { boundary.style.maxWidth = w + 'px'; } }
  })();
</script>
<?php showfooter(); ?>
