<?php
require_once dirname(dirname(__DIR__)).'/config.php';
require_once dirname(dirname(__DIR__)).'/fungsi.php';
header('Content-Type: application/json');

$pageId = isset($_GET['page_id']) ? (int)$_GET['page_id'] : 0;
if ($pageId <= 0) { echo json_encode(['show'=>0,'items'=>[]]); exit; }

$items = [];
$show = 0;
$hasSettings = db_select("SHOW TABLES LIKE 'epi_product_benefit_settings'");
$hasItems = db_select("SHOW TABLES LIKE 'epi_product_benefit'");
if (is_array($hasSettings) && count($hasSettings)>0 && is_array($hasItems) && count($hasItems)>0) {
  $show = (int)db_var("SELECT `show_benefit` FROM `epi_product_benefit_settings` WHERE `page_id`=".$pageId);
  if ($show === 1) {
    $rows = db_select("SELECT `label` FROM `epi_product_benefit` WHERE `page_id`=".$pageId." AND `is_active`=1 ORDER BY `sort_order` ASC, `id` ASC");
    if (is_array($rows)) {
      foreach ($rows as $r) { $items[] = ['label' => (string)$r['label']]; }
    }
  }
}
echo json_encode(['show'=>$show, 'items'=>$items]);
?>
