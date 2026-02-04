<?php
if (!defined('IS_IN_SCRIPT')) { die(); }
if ($datamember['mem_role'] < 9) { die('Forbidden'); }
$head['pagetitle']='Coupons Management';
showheader($head);

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$now = date('Y-m-d H:i:s');

function coupon_save($data, $id=null){
  $code = strtoupper(trim($data['code'])); $type = $data['type']; $value = (int)$data['value'];
  $priority = (int)($data['priority'] ?? 0); $minp = (int)($data['min_purchase'] ?? 0);
  $maxu = !empty($data['max_usage']) ? (int)$data['max_usage'] : 'NULL';
  $scope_all = !empty($data['scope_all']) ? 1 : 0;
  $product_ids = !empty($data['product_ids']) ? implode(',', array_map('intval', preg_split('/[,\s]+/', $data['product_ids']))) : '';
  $category_ids = !empty($data['category_ids']) ? implode(',', array_map('intval', preg_split('/[,\s]+/', $data['category_ids']))) : '';
  $member_status_min = isset($data['member_status_min']) && $data['member_status_min']!=='' ? (int)$data['member_status_min'] : 'NULL';
  $allowed_user_ids = !empty($data['allowed_user_ids']) ? implode(',', array_map('intval', preg_split('/[,\s]+/', $data['allowed_user_ids']))) : '';
  $start = !empty($data['start_at']) ? cek($data['start_at']) : NULL; $end = !empty($data['end_at']) ? cek($data['end_at']) : NULL;
  $status = isset($data['status']) ? (int)$data['status'] : 1;
  if ($id) {
    $sql = "UPDATE sa_coupon SET code='".cek($code)."', type='".cek($type)."', value=".$value.", priority=".$priority.", min_purchase=".$minp.", start_at=".($start?"'{$start}'":"NULL").", end_at=".($end?"'{$end}'":"NULL").", max_usage=".$maxu.", scope_all=".$scope_all.", product_ids=".(strlen($product_ids)>0?"'".cek($product_ids)."'":"NULL").", category_ids=".(strlen($category_ids)>0?"'".cek($category_ids)."'":"NULL").", member_status_min=".$member_status_min.", allowed_user_ids=".(strlen($allowed_user_ids)>0?"'".cek($allowed_user_ids)."'":"NULL").", status=".$status.", updated_at='".date('Y-m-d H:i:s')."' WHERE id=".(int)$id;
    db_query($sql);
    db_query("INSERT INTO sa_coupon_change_log (coupon_id,admin_id,action,details,changed_at) VALUES (".(int)$id.",".(int)$GLOBALS['datamember']['mem_id'].",'update','".cek(json_encode($data))."','".date('Y-m-d H:i:s')."')");
    return $id;
  } else {
    $sql = "INSERT INTO sa_coupon (code,type,value,priority,min_purchase,start_at,end_at,max_usage,scope_all,product_ids,category_ids,member_status_min,allowed_user_ids,status,created_by,created_at,updated_at) VALUES (
      '".cek($code)."','".cek($type)."',".$value.",".$priority.",".$minp.",".($start?"'{$start}'":"NULL").",".($end?"'{$end}'":"NULL").",".$maxu.",".$scope_all.",".(strlen($product_ids)>0?"'".cek($product_ids)."'":"NULL").",".(strlen($category_ids)>0?"'".cek($category_ids)."'":"NULL").",".$member_status_min.",".(strlen($allowed_user_ids)>0?"'".cek($allowed_user_ids)."'":"NULL").",".$status.",".(int)$GLOBALS['datamember']['mem_id'].",'".date('Y-m-d H:i:s')."','".date('Y-m-d H:i:s')."')";
    $id = db_insert($sql);
    db_query("INSERT INTO sa_coupon_change_log (coupon_id,admin_id,action,details,changed_at) VALUES (".(int)$id.",".(int)$GLOBALS['datamember']['mem_id'].",'create','".cek(json_encode($data))."','".date('Y-m-d H:i:s')."')");
    return $id;
  }
}

if ($action==='save') {
  $id = isset($_POST['id']) && $_POST['id'] ? (int)$_POST['id'] : null;
  $saved = coupon_save($_POST, $id);
  echo '<div class="alert alert-success">Coupon saved (ID: '.(int)$saved.')</div>';
}
if ($action==='delete' && isset($_GET['id'])) {
  $id = (int)$_GET['id'];
  db_query("DELETE FROM sa_coupon WHERE id=".$id);
  db_query("INSERT INTO sa_coupon_change_log (coupon_id,admin_id,action,details,changed_at) VALUES (".$id.",".(int)$GLOBALS['datamember']['mem_id'].",'delete','', '".date('Y-m-d H:i:s')."')");
  echo '<div class="alert alert-warning">Coupon deleted</div>';
}

$q = isset($_GET['q']) ? cek($_GET['q']) : '';
$type = isset($_GET['type']) ? cek($_GET['type']) : '';
$status = isset($_GET['status']) ? (int)$_GET['status'] : -1;
$where = [];
if ($q!=='') { $where[] = "code LIKE '%{$q}%'"; }
if ($type!=='') { $where[] = "type='{$type}'"; }
if ($status!==-1) { $where[] = "status=".$status; }
$sql = "SELECT * FROM sa_coupon" . (count($where)?" WHERE ".implode(' AND ',$where):'') . " ORDER BY priority DESC, code ASC LIMIT 200";
$rows = db_select($sql) ?: [];

?>
<div class="container-fluid">
  <div class="row">
  <div class="col-12 order-2">
    <div class="card mb-4 w-100">
      <div class="card-header">Coupons</div>
      <div class="card-body">
        <form class="row g-2" method="get">
          <div class="col-md-4"><input type="text" name="q" value="<?=htmlspecialchars($q)?>" class="form-control" placeholder="Search code"></div>
          <div class="col-md-3"><select name="type" class="form-select"><option value="">All types</option><option value="percent" <?=$type==='percent'?'selected':''?>>Percent</option><option value="fixed" <?=$type==='fixed'?'selected':''?>>Fixed</option></select></div>
          <div class="col-md-3"><select name="status" class="form-select"><option value="-1" <?=$status===-1?'selected':''?>>All</option><option value="1" <?=$status===1?'selected':''?>>Active</option><option value="0" <?=$status===0?'selected':''?>>Inactive</option></select></div>
          <div class="col-md-2"><button class="btn btn-secondary w-100">Filter</button></div>
        </form>
        <table class="table table-sm mt-3">
          <thead><tr><th>Code</th><th>Type</th><th>Value</th><th>Priority</th><th>Usage</th><th>Valid</th><th></th></tr></thead>
          <tbody>
            <?php foreach($rows as $r): ?>
              <tr>
                <td><?=htmlspecialchars($r['code'])?></td>
                <td><?=$r['type']?></td>
                <td><?=number_format($r['value'])?></td>
                <td><?=$r['priority']?></td>
                <td><?= (int)$r['used_count'] . '/' . (is_null($r['max_usage'])?'-':(int)$r['max_usage']) ?></td>
                <td><?= ($r['start_at'] && strtotime($r['start_at'])>time())?'Not yet':(($r['end_at'] && strtotime($r['end_at'])<time())?'Expired':'OK') ?></td>
                <td><a href="?action=edit&id=<?=$r['id']?>" class="btn btn-sm btn-primary">Edit</a> <a href="?action=delete&id=<?=$r['id']?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete coupon?')">Delete</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-12 order-1">
    <div class="card mb-4 w-100">
      <div class="card-header">Create / Edit Coupon</div>
      <div class="card-body">
        <?php $edit=null; if ($action==='edit' && isset($_GET['id'])) { $edit = db_row("SELECT * FROM sa_coupon WHERE id=".(int)$_GET['id']); } ?>
        <form id="couponForm" method="post" novalidate>
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
          <div class="mb-2"><label class="form-label">Code</label><input required name="code" class="form-control" value="<?=htmlspecialchars($edit['code'] ?? '')?>"></div>
          <div class="mb-2"><label class="form-label">Type</label><select name="type" class="form-select"><option value="percent" <?=($edit['type'] ?? '')==='percent'?'selected':''?>>Percent</option><option value="fixed" <?=($edit['type'] ?? '')==='fixed'?'selected':''?>>Fixed</option></select></div>
          <div class="mb-2"><label class="form-label">Value</label><input required type="number" name="value" class="form-control" value="<?= (int)($edit['value'] ?? 0) ?>"></div>
          <div class="mb-2"><label class="form-label">Priority</label><input type="number" name="priority" class="form-control" value="<?= (int)($edit['priority'] ?? 0) ?>"></div>
          <div class="mb-2"><label class="form-label">Min Purchase</label><input type="number" name="min_purchase" class="form-control" value="<?= (int)($edit['min_purchase'] ?? 0) ?>"></div>
          <div class="mb-2"><label class="form-label">Max Usage</label><input type="number" name="max_usage" class="form-control" value="<?= htmlspecialchars($edit['max_usage'] ?? '') ?>" placeholder="optional"></div>
          <div class="mb-2"><label class="form-label">Valid From</label><input type="datetime-local" name="start_at" class="form-control" value="<?= $edit && $edit['start_at'] ? date('Y-m-d\TH:i', strtotime($edit['start_at'])) : '' ?>"></div>
          <div class="mb-2"><label class="form-label">Valid Until</label><input type="datetime-local" name="end_at" class="form-control" value="<?= $edit && $edit['end_at'] ? date('Y-m-d\TH:i', strtotime($edit['end_at'])) : '' ?>"></div>
          <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="scope_all" id="scope_all" <?= ($edit ? ((int)$edit['scope_all']===1?'checked':'') : 'checked') ?>><label class="form-check-label" for="scope_all">Apply to all products</label></div>
          <div class="mb-2"><label class="form-label">Product IDs</label>
            <input type="hidden" id="product_ids" name="product_ids" value="<?= htmlspecialchars($edit['product_ids'] ?? '') ?>">
            <div class="input-group mb-2">
              <span class="input-group-text">Search</span>
              <input id="prodSearch" type="text" class="form-control" placeholder="Type product name">
            </div>
            <select id="prodSelect" class="form-select" multiple size="8"></select>
            <div id="prodLoading" class="text-muted" style="display:none;">Loading products…</div>
          </div>
          <!-- Category selection removed per request: simplify coupon targeting to product-only or apply-to-all -->
          <div class="mb-2"><label class="form-label">Min Member Status</label>
            <?php $statusVal = isset($edit['member_status_min']) ? (int)$edit['member_status_min'] : 0; ?>
            <select id="member_status_min" name="member_status_min" class="form-select">
              <option value="">None</option>
              <option value="1" <?= $statusVal===1?'selected':'' ?>>Free Member</option>
              <option value="2" <?= $statusVal===2?'selected':'' ?>>Premium Member</option>
            </select>
            <div class="mt-1"><span id="statusBadge" class="badge bg-secondary">—</span></div>
          </div>
          <div class="mb-2"><label class="form-label">Allowed User IDs</label><input name="allowed_user_ids" class="form-control" value="<?= htmlspecialchars($edit['allowed_user_ids'] ?? '') ?>" placeholder="comma separated"></div>
          <div class="mb-2"><label class="form-label">Status</label><select name="status" class="form-select"><option value="1" <?= ($edit && (int)$edit['status']===1)?'selected':'' ?>>Active</option><option value="0" <?= ($edit && (int)$edit['status']===0)?'selected':'' ?>>Inactive</option></select></div>
          <div id="formErrors" class="text-danger mb-2" style="display:none;"></div>
          <button class="btn btn-primary" type="submit">Save</button>
        </form>
      </div>
    </div>
    
  </div>
  </div>
</div>
<script>
  const debounce = (fn, wait=300) => { let t; return (...args) => { clearTimeout(t); t=setTimeout(()=>fn(...args), wait); }; };
  function fetchList(url, q, loadingId){ const el=document.getElementById(loadingId); if(el) el.style.display='block'; return fetch(url+'?q='+encodeURIComponent(q||''))
    .then(r=>r.json()).then(j=>{ if(el) el.style.display='none'; return Array.isArray(j.data)?j.data:[]; }).catch(()=>{ if(el) el.style.display='none'; return []; }); }
  function renderOptions(selectEl, items, selected){ selectEl.innerHTML=''; items.forEach(it=>{ const opt=document.createElement('option'); opt.value=String(it.id); let label=it.name; if(Object.prototype.hasOwnProperty.call(it,'status')){ label = (parseInt(it.status,10)===1) ? it.name : (it.name+' (Inactive)'); } opt.textContent=label; if(selected.has(String(it.id))){ opt.selected=true; } selectEl.appendChild(opt); }); }
  function updateHiddenFromSelect(selectEl, hiddenEl){ const vals=Array.from(selectEl.selectedOptions).map(o=>o.value); hiddenEl.value=vals.join(','); }
  function syncDisabledByScope(){ const on=document.getElementById('scope_all').checked; ['prodSearch','prodSelect'].forEach(id=>{ const el=document.getElementById(id); if(el){ el.disabled=on; } }); }
  function statusBadgeText(v){ switch(String(v)){ case '1': return 'Free Member'; case '2': return 'Premium Member'; default: return '—'; } }
  function initStatusBadge(){ const sel=document.getElementById('member_status_min'); const badge=document.getElementById('statusBadge'); const upd=()=>{ badge.textContent=statusBadgeText(sel.value); badge.className='badge '+(sel.value? 'bg-dark':'bg-secondary'); }; sel.addEventListener('change',upd); upd(); }
  function initProducts(){ const sel=document.getElementById('prodSelect'); const hid=document.getElementById('product_ids'); const search=document.getElementById('prodSearch'); const load=()=>{ fetchList('<?= $weburl ?>/api/products-list.php', search.value, 'prodLoading').then(items=>{ const selected=new Set((hid.value||'').split(',').filter(Boolean)); renderOptions(sel, items, selected); }); }; sel.addEventListener('change', ()=>updateHiddenFromSelect(sel,hid)); search.addEventListener('input', debounce(load, 400)); load(); }
  // Category selection disabled: no-op
  function initCategories(){ /* intentionally empty */ }
  function validateForm(evt){ const form=document.getElementById('couponForm'); const err=document.getElementById('formErrors'); const code=form.code.value.trim(); const type=form.type.value; const val=parseInt(form.value.value,10)||0; const scopeAll=document.getElementById('scope_all').checked; const pids=form.product_ids.value.trim(); const s=form.start_at.value; const e=form.end_at.value; const errs=[]; if(code.length<2||code.length>64){ errs.push('Kode harus 2–64 karakter.'); } if(!type){ errs.push('Tipe wajib dipilih.'); } if(val<=0){ errs.push('Nilai diskon harus > 0.'); } if(!scopeAll && !pids){ errs.push('Pilih minimal satu produk atau aktifkan apply to all.'); } if(s && e && (new Date(s) > new Date(e))){ errs.push('Rentang tanggal tidak valid.'); }
    if(errs.length){ evt.preventDefault(); err.innerHTML=errs.join('<br>'); err.style.display='block'; } }
  document.addEventListener('DOMContentLoaded', ()=>{ initProducts(); /* initCategories(); */ initStatusBadge(); syncDisabledByScope(); document.getElementById('scope_all').addEventListener('change', syncDisabledByScope); document.getElementById('couponForm').addEventListener('submit', validateForm); });
</script>
<?php showfooter(); ?>
