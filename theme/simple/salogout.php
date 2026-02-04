<?php
setcookie('authentication', '',time()-36000,'/');
header('Location:'.$weburl.'login');
?>
