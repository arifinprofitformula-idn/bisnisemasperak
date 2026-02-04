<?php
header("Content-Type: text/plain");

echo "User-agent: *\n";
echo "Disallow: /dashboard\n";
echo "Disallow: /login\n";
echo "Sitemap: " . $weburl . "sitemap.xml\n"; 
?>