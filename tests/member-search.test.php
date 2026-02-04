<?php
require_once dirname(__DIR__).'/fungsi.php';
header('Content-Type: text/html; charset=utf-8');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES); }
function getJson($url){ $c = @file_get_contents($url); if($c===false){ return null; } $j = json_decode($c,true); return $j; }

$base = rtrim($weburl, '/').'/api/member-search.php';
$sample = db_row("SELECT `mem_id`,`mem_nama`,`mem_email` FROM `sa_member` ORDER BY `mem_id` DESC LIMIT 1");
$email = isset($sample['mem_email']) ? $sample['mem_email'] : '';
$nama  = isset($sample['mem_nama']) ? $sample['mem_nama'] : '';
$id    = isset($sample['mem_id']) ? (int)$sample['mem_id'] : 0;
$namaq = ($nama !== '' ? substr($nama, 0, max(4, min(12, strlen($nama)))) : 'test');
$emailUpper = strtoupper($email);

$tests = [];

// 1) Name partial (case-insensitive)
$j1 = getJson($base.'?q='.urlencode($namaq));
$ok1 = is_array($j1) && isset($j1['status']) && $j1['status']===true && isset($j1['data']) && is_array($j1['data']);
$tests[] = ['name'=>'Name partial CI','ok'=>$ok1,'detail'=>($ok1?('items='.count($j1['data'])):'no data')];

// 2) Email exact (case-insensitive)
$j2 = ($email!=='' ? getJson($base.'?q='.urlencode($emailUpper)) : null);
$ok2 = is_array($j2) && isset($j2['status']) && $j2['status']===true && isset($j2['data']) && is_array($j2['data']);
// exact match: semua item harus punya email == sample (case-insensitive)
if($ok2){
  foreach($j2['data'] as $it){ if(strtolower($it['email']) !== strtolower($email)){ $ok2=false; break; } }
}
$tests[] = ['name'=>'Email exact CI','ok'=>$ok2,'detail'=>($ok2?('items='.count($j2['data'])):'no data or mismatch')];

// 3) Numeric ID exact
$j3 = ($id>0 ? getJson($base.'?q='.$id) : null);
$ok3 = is_array($j3) && isset($j3['status']) && $j3['status']===true && isset($j3['data']) && is_array($j3['data']);
if($ok3){ $ok3 = array_reduce($j3['data'], function($c,$it) use($id){ return $c && ($it['id']===$id); }, true); }
$tests[] = ['name'=>'ID exact','ok'=>$ok3,'detail'=>($ok3?('id='.$id):'no data or mismatch')];

// 4) Empty / not found
$j4 = getJson($base.'?q='.urlencode('zxqwv12_not_exists'));
$ok4 = is_array($j4) && isset($j4['status']) && $j4['status']===true && isset($j4['data']) && is_array($j4['data']) && count($j4['data'])===0;
$tests[] = ['name'=>'Empty state','ok'=>$ok4,'detail'=>($ok4?'ok':'unexpected items')];

echo '<!doctype html><html><head><meta charset="utf-8"><title>Member Search Tests</title><link href="'.h($weburl).'bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-3">';
echo '<h5>Backend Unit Tests: Member Search</h5><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Case</th><th>Status</th><th>Detail</th></tr></thead><tbody>';
foreach($tests as $t){ echo '<tr><td>'.h($t['name']).'</td><td>'.($t['ok']?'<span class="badge bg-success">PASS</span>':'<span class="badge bg-danger">FAIL</span>').'</td><td>'.h($t['detail']).'</td></tr>'; }
echo '</tbody></table></div>';

echo '<p class="text-muted small">Note: Tests membaca satu sample terakhir dari sa_member untuk email/nama, sehingga hasil bergantung pada data nyata.</p>';
echo '</body></html>';
?>
