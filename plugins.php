<?php  
$pluginFolder = __DIR__ . '/plugin/';
$setplugin = getsettings('plugin_aktif');
if (!empty($setplugin)) {
  $plugin_aktif = explode(',', $setplugin);
  foreach ($plugin_aktif as $plugin) {
    include($pluginFolder.$plugin.'/index.php');
  }
} 
?>