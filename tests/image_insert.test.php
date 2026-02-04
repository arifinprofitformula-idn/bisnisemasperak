<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../fungsi.php';

function assertHas($haystack,$needle,$msg){ echo (strpos($haystack,$needle)!==false) ? "OK: {$msg}\n" : ("FAIL: {$msg} => missing '{$needle}'\n"); }

$html = '<p><img src="/upload/x.jpg"></p>';
$fixed = epi_fix_images($html);
assertHas($fixed,'loading="lazy"','lazy added');
assertHas($fixed,'onerror=','onerror fallback added');
assertHas($fixed,'/upload/x.jpg','src preserved path');

$html2 = '<img src="relative/path.png">';
$fixed2 = epi_fix_images($html2);
assertHas($fixed2,'/relative/path.png','relative prefixed');

echo "Done.\n";
?>
