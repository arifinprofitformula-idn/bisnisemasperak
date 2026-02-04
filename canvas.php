<?php
include('fungsi.php');
$settings = getsettings();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order'])) {
  #$order = json_decode($_POST['order'], true);
  
  $newsetting['homecanvas'] = $_POST['order'];
  updatesettings($newsetting);
} else {
  if (isset($settings['homecanvas'])) { echo $settings['homecanvas']; }
}
?>
