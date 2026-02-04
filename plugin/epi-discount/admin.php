<?php
if (!defined('IS_IN_SCRIPT')) { die(); }
if ($datamember['mem_role'] < 9) { die('Forbidden'); }
$head['pagetitle']='Discount Rules';
showheader($head);
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $json = $_POST['rules'] ?? '';
  $ok = false;
  if (!empty($json)) {
    $arr = json_decode($json, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($arr)) {
      updatesettings(['discount_rules' => json_encode($arr)]);
      $ok = true;
    }
  }
  echo '<div class="alert '.($ok?'alert-success':'alert-danger').'">'.($ok?'Rules updated':'Invalid JSON').'</div>';
}
$cur = getsettings('discount_rules');
?>
<div class="card">
  <div class="card-header">Discount Rules (JSON)</div>
  <div class="card-body">
    <form method="post">
      <textarea name="rules" class="form-control" rows="18"><?php echo htmlspecialchars($cur ?: "[
  {\"type\":\"percent\",\"value\":20,\"code\":\"PROMO20\",\"exclusive_group\":\"code\"},
  {\"type\":\"fixed\",\"value\":50000,\"min_purchase\":100000,\"stackable\":true},
  {\"type\":\"bogo\",\"buy_qty\":2,\"get_qty\":1,\"product_ids\":[1,2],\"exclusive_group\":\"qty\"},
  {\"type\":\"member\",\"mode\":\"percent\",\"value\":10,\"member_role\":5,\"stackable\":true}
]", ENT_QUOTES); ?></textarea>
      <div class="mt-2"><button class="btn btn-primary" type="submit">Save</button></div>
      <div class="mt-3 text-muted small">Fields: type, value, code (optional), min_purchase, start, end, product_ids[], member_role, buy_qty/get_qty (bogo), stackable (bool), exclusive_group (string)</div>
    </form>
  </div>
</div>
<?php showfooter(); ?>

