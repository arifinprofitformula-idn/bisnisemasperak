<?php
require_once dirname(__DIR__).'/fungsi.php';
header('Content-Type: application/json');
@ini_set('display_errors','0');
$settings = getsettings();
require_once dirname(__DIR__).'/menudata.php';
function nodeLabel($key, $conf){ if (isset($conf['label'])) return $conf['label']; return ucfirst($key); }
$tree = array();
if (isset($menu['mainmenu']) && is_array($menu['mainmenu'])){
  $children = array(); foreach ($menu['mainmenu'] as $k=>$v){ if (is_array($v) && isset($v[0])){ $children[] = array('key'=>$k,'label'=>$v[0]); } }
  $tree[] = array('key'=>'mainmenu','label'=>'Main Menu','children'=>$children);
}
foreach ($menu as $topKey => $conf){
  if ($topKey==='mainmenu') { continue; }
  if (isset($conf['submenu']) && is_array($conf['submenu'])){
    $children = array(); foreach ($conf['submenu'] as $k=>$v){ if (is_array($v) && isset($v[0])){ $children[] = array('key'=>$k,'label'=>$v[0]); } }
    $tree[] = array('key'=>$topKey,'label'=>nodeLabel($topKey,$conf),'children'=>$children);
  } else if (isset($conf['label']) && !isset($conf['submenu'])){
    $tree[] = array('key'=>$topKey,'label'=>nodeLabel($topKey,$conf),'children'=>array());
  }
}
echo json_encode(array('status'=>true,'tree'=>$tree));
?>
