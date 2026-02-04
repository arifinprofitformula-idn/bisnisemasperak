<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../fungsi.php';

function assertEqual($a,$b,$msg){ echo ($a === $b) ? "OK: {$msg}\n" : ("FAIL: {$msg} => got '".var_export($a,true)."' expected '".var_export($b,true)."'\n"); }

$table = 'epi_slug_test';
mysqli_query($con, "DROP TABLE IF EXISTS `{$table}`");
mysqli_query($con, "CREATE TABLE `{$table}` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `slug` VARCHAR(255) NOT NULL UNIQUE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

assertEqual(epi_slugify('Apapun Judulnya'), 'apapun-judulnya', 'lowercase and spaces');
assertEqual(epi_slugify(' A   B '), 'a-b', 'multiple spaces');
assertEqual(epi_slugify('@@@###%%%'), 'n-a', 'invalid chars to fallback');
assertEqual(epi_slugify('judul--besar---banget'), 'judul-besar-banget', 'collapse dashes');

mysqli_query($con, "INSERT INTO `{$table}` (`slug`) VALUES ('apapun-judulnya')");
$u1 = epi_unique_slug('apapun-judulnya',$table,'slug','id');
assertEqual($u1, 'apapun-judulnya2', 'uniqueness next suffix');

mysqli_query($con, "INSERT INTO `{$table}` (`slug`) VALUES ('a'),('a2')");
$u2 = epi_unique_slug('a',$table,'slug','id');
assertEqual($u2, 'a3', 'increment after existing 1 and 2');

mysqli_query($con, "INSERT INTO `{$table}` (`slug`) VALUES ('x')");
$row = mysqli_query($con, "SELECT `id` FROM `{$table}` WHERE `slug`='x' LIMIT 1");
$id = mysqli_fetch_assoc($row)['id'];
$u3 = epi_unique_slug('x',$table,'slug','id',$id);
assertEqual($u3, 'x', 'exclude id keeps same');

mysqli_query($con, "DROP TABLE IF EXISTS `{$table}`");
echo "Done.\n";
?>
