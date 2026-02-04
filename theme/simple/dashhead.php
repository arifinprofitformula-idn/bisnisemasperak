<?php
$skema = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$rekues = parse_url($_SERVER["REQUEST_URI"]);
$visiturl = $skema . "://" . $_SERVER['HTTP_HOST'] . $rekues['path'];
// Inisialisasi settings agar tidak undefined
$settings = (isset($settings) && is_array($settings)) ? $settings : getsettings();
// Pastikan favicon terdefinisi
if (!isset($settings['favicon']) || empty($settings['favicon'])) { $favicon = 'img/simpleaff-favicon.png'; } else { $favicon = 'upload/'.$settings['favicon']; }
// Resolusi logo konsisten
$logoSrc = function_exists('epi_resolve_logo_src') ? epi_resolve_logo_src($weburl, $settings, $settings['logoweb'] ?? null) : ($weburl.(isset($settings['logoweb']) && $settings['logoweb'] ? 'upload/'.$settings['logoweb'] : 'img/simpleaff-logo.png'));
?>
<!DOCTYPE html>
<html class="full" lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="shortcut icon" type="image/x-icon" href="<?= $weburl.$favicon;?>" />
  <title><?= $head['pagetitle'] ??= 'Dashboard';?></title>  
  <!-- Bootstrap Core CSS -->
  <link href="<?= $weburl;?>bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="<?= $weburl;?>theme/simple/style.css">
  <link href="<?=$weburl;?>fontawesome/css/fontawesome.min.css" rel="stylesheet" />
  <link href="<?=$weburl;?>fontawesome/css/regular.min.css" rel="stylesheet" />
  <link href="<?=$weburl;?>fontawesome/css/solid.min.css" rel="stylesheet" />
  <link href="<?=$weburl;?>editor/css/froala_style.min.css" rel="stylesheet" type="text/css" />

  <meta name="description" content="<?= $head['description'] ?? ''; ?>" />
  <meta property="og:title" content="<?= $head['pagetitle'] ?? 'Dashboard';?>"/>
  <meta property="og:description" content="<?= $head['description'] ?? ''; ?>"/>
  <meta property="og:url" content="<?= $visiturl; ?>"/>
  <meta property="og:image" content="<?= $head['thumbnail'] ?? $logoSrc;?>"/>
  <meta property="og:type" content="website" />

  <?php 
  echo $head['scripthead']??='';
  $container = $head['container'] ??= 'container';
  if (isset($slug[1]) && $slug[1] == 'dashboard') {
    $homeurl = $weburl.'dashboard';
  } else {
    $homeurl = $weburl;
  }
  ?>
  <style>
    .site-logo{max-height:60px;height:auto;width:auto;transition:filter .2s ease}
    @media (max-width:576px){.site-logo{max-height:48px}}
    .site-logo:hover{filter:brightness(0.95)}
  </style>
</head>

<body class="dashboard">
<nav class="navbar sticky-top navbar-expand-md bg-body-tertiary">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?= $homeurl;?>"><img src="<?= htmlspecialchars($logoSrc, ENT_QUOTES);?>" alt="EPI Logo" class="site-logo"></a>
    <button class="navbar-toggler"  type="button" data-bs-toggle="collapse" data-bs-target="#navbarColor01" aria-controls="navbarColor01" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarColor01">
      <ul class="navbar-nav me-auto mb-2 mb-md-0">
        <li class="nav-item">
          <a class="nav-link active" aria-current="page" href="<?=$weburl;?>dashboard">Home</a>
        </li>
        <?php 
        // Gunakan path absolut untuk include menu agar robust
        $baseDir = dirname(__DIR__);
        $themeName = isset($settings['theme']) ? $settings['theme'] : 'simple';
        $menuPath = $baseDir . DIRECTORY_SEPARATOR . $themeName . DIRECTORY_SEPARATOR . 'menu.php';
        $fallbackMenu = $baseDir . DIRECTORY_SEPARATOR . 'simple' . DIRECTORY_SEPARATOR . 'menu.php';
        if (file_exists($menuPath)) {
          include($menuPath);
        } elseif (file_exists($fallbackMenu)) {
          include($fallbackMenu);
        } else {
          echo '<li class="nav-item"><span class="nav-link text-danger">Menu tidak ditemukan</span></li>';
        }
        ?>        
      </ul>
      <?php
        $isPremium = (isset($datamember['mem_status']) && intval($datamember['mem_status']) >= 2);
        $badgeText = $isPremium ? 'Premium' : 'Free';
        $badgeClass = $isPremium ? 'bg-primary' : 'bg-warning text-dark';
      ?>
      <span class="badge <?= $badgeClass; ?> me-3">Member: <?= $badgeText; ?></span>
      <div class="dropdown d-flex">
        <a href="#" class="d-block link-body-emphasis text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
          <img src="<?= isset($datamember['fotoprofil']) ? $weburl.'upload/'.$datamember['fotoprofil'] : $weburl.'img/pp.png';?>" alt="mdo" width="32" height="32" class="rounded-circle">
        </a>
        <ul class="dropdown-menu dropdown-menu-md-end dropdown-menu-sm-start text-small">
          <?php if (isset($datamember['mem_id'])) :?>
          <li><a class="dropdown-item" href="<?= $weburl;?>dashboard/profil">Profile</a></li>
          <li><a class="dropdown-item" href="<?= $weburl;?>dashboard/orderanda">Order Anda</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="<?= $weburl;?>logout">Logout</a></li>
          <?php else : ?>
            <li><a class="dropdown-item" href="<?= $weburl;?>login">Login</a></li>
            <li><a class="dropdown-item" href="<?= $weburl;?>register">Register</a></li>
          <?php endif; ?>
        </ul>
      </div>

    </div>
  </div>
</nav>

<div class="<?=$container;?> p-md-5 p-2"> 
  <?php 
  if (isset($bcslug)) :  ?>
  <nav style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='%236c757d'/%3E%3C/svg%3E&#34;);" aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= $weburl.'dashboard';?>">Dashboard</a></li>
      <?php 
      if (isset($_GET['edit'])) {
        if (is_numeric($_GET['edit'])) {
          $act = 'Edit ';
        } else {
          $act = 'Tambah ';
        }
        echo '
        <li class="breadcrumb-item"><a href="'.$weburl.'dashboard/'.$bcslug.'">'.$bcjudul.'</a></li>
        <li class="breadcrumb-item active" aria-current="page">'.$act.$bcjudul.'</li>';
      } elseif (isset($_GET['detil'])) {
        echo '
        <li class="breadcrumb-item"><a href="'.$weburl.'dashboard/'.$bcslug.'">'.$bcjudul.'</a></li>
        <li class="breadcrumb-item active" aria-current="page">Detil '.$bcjudul.'</li>';
      } else {
        echo '<li class="breadcrumb-item active" aria-current="page">'.$bcjudul.'</li>';
      }
      ?>
    </ol>
  </nav>
<?php endif; ?>
<!-- Content Start -->
