<?php
require_once dirname(dirname(__DIR__)).'/config.php';
require_once dirname(dirname(__DIR__)).'/fungsi.php';
header('Content-Type: application/json');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if (strlen($q) < 4) { echo json_encode([]); exit; }

$qLike = db_escape($q);
$limit = 10;

$sql = "SELECT `mem_id`,`mem_nama`,`mem_email`,`mem_whatsapp`,`mem_status`
        FROM `sa_member`
        WHERE `mem_status` >= 2 AND (
          `mem_nama` LIKE '%$qLike%' OR `mem_email` LIKE '%$qLike%' OR `mem_whatsapp` LIKE '%$qLike%'
        )
        ORDER BY `mem_status` DESC, `mem_nama` ASC
        LIMIT $limit";
$rows = db_select($sql);
echo json_encode(is_array($rows) ? $rows : []);
?>
