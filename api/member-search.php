<?php
require_once dirname(__DIR__).'/fungsi.php';
header('Content-Type: application/json');
epi_require_admin();
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$where = " WHERE 1 ";
if ($q !== '') {
  $qq = db_escape($q);
  if (is_numeric($q)) {
    $where .= " AND `mem_id`=".(int)$q;
  } elseif (strpos($q, '@') !== false) {
    // Jika valid email → exact match; jika tidak, fallback ke partial match
    if (filter_var($q, FILTER_VALIDATE_EMAIL)) {
      $where .= " AND LOWER(`mem_email`) = LOWER('".$qq."')";
    } else {
      $where .= " AND LOWER(`mem_email`) LIKE CONCAT('%', LOWER('".$qq."'), '%')";
    }
  } else {
    // Partial match similar to /dashboard/member (nama, kodeaff, whatsapp, datalain)
    $where .= " AND (".
      " LOWER(`mem_nama`) LIKE CONCAT('%', LOWER('".$qq."'), '%')".
      " OR LOWER(`mem_kodeaff`) LIKE CONCAT('%', LOWER('".$qq."'), '%')".
      " OR LOWER(`mem_whatsapp`) LIKE CONCAT('%', LOWER('".$qq."'), '%')".
      " OR LOWER(`mem_datalain`) LIKE CONCAT('%', LOWER('".$qq."'), '%')".
    ")";
  }
}
$rows = db_select("SELECT `mem_id`,`mem_nama`,`mem_email`,`mem_whatsapp`,`mem_role`,`mem_datalain` FROM `sa_member`".$where." ORDER BY `mem_id` DESC LIMIT 200") ?: array();
$err = false;
if ($rows === false) { $err = true; }
$data = array();
foreach ($rows as $r){
  $ext = extractdata($r);
  $foto = isset($ext['fotoprofil']) && !empty($ext['fotoprofil']) ? ($weburl.'upload/'.$ext['fotoprofil']) : ($weburl.'img/pp.png');
  $data[] = array('id'=>(int)$r['mem_id'],'nama'=>$r['mem_nama'],'email'=>$r['mem_email'],'wa'=>$r['mem_whatsapp'],'role'=>(int)$r['mem_role'],'foto'=>$foto);
}
if ($err){ http_response_code(500); echo json_encode(array('status'=>false,'message'=>'Database error','data'=>array())); }
else { echo json_encode(array('status'=>true,'data'=>$data)); }
?>
