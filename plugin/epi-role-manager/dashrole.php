<?php
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
global $datamember;
$head['pagetitle'] = 'Setting Role';
$head['scripthead'] = '<style>.role-dropdown .dropdown-menu{transition:opacity .15s ease, transform .15s ease; transform:scale(.98); opacity:0; background-color:transparent; border:1px solid #dee2e6;} .role-dropdown .dropdown-menu.show{transform:scale(1); opacity:1;} .role-dropdown .dropdown-toggle{background-color:transparent !important; border:1px solid #dee2e6;} .menu-chip{display:inline-flex; align-items:center; gap:.5rem; padding:.25rem .5rem; border-radius:.5rem; border:1px solid #ced4da; margin:.125rem; background:transparent;} .menu-chip.active{border-color:#198754;} .menu-chip.inactive{border-color:#e0e0e0; color:#6c757d;} .menu-chip .icon{font-size:1rem; width:1.125rem; text-align:center;} .menu-group{margin-bottom:.5rem;} .menu-group h6{margin-bottom:.25rem;} .perm-tree{display:flex; flex-wrap:wrap; gap:.75rem; border:1px solid #dee2e6; border-radius:.5rem; padding:.5rem; max-width:100%; overflow-x:hidden;} .tree-node{flex:1 1 28rem; min-width:18rem;} .tree-header{display:flex; align-items:center; gap:.5rem; border-bottom:1px solid #eee; padding:.25rem 0;} .tree-children{display:flex; flex-direction:column; gap:.5rem; padding-left:.75rem;} .tree-toggle{border:1px solid #ced4da; background:transparent; width:1.75rem; height:1.75rem; border-radius:.25rem; display:inline-flex; align-items:center; justify-content:center;} .tree-toggle:focus{outline:2px solid #86b7fe; outline-offset:2px;} .perm-item{display:flex; align-items:center;}</style>';
showheader($head);
if ((int)($datamember['mem_role'] ?? 1) < 9) {
  echo '<div class="alert alert-danger">Akses ditolak. Halaman ini khusus Administrator.</div>';
  showfooter();
  return;
}
?>
<div class="container-fluid">
  <div class="row g-3">
    <div class="col-12">
      <div class="card h-100">
        <div class="card-header bg-light">Role Permission Management</div>
        <div class="card-body">
          <div class="alert alert-info p-2">Checkbox menandakan akses aktif. Menu wajib ditandai dan tidak dapat dinonaktifkan.</div>
          <div class="row g-2 align-items-end mb-2">
            <div class="col-md-6">
              <label class="form-label">Pilih Role</label>
              <div id="roleDropdown" class="role-dropdown">
                <button id="btnRole" class="btn btn-outline-secondary dropdown-toggle w-100 text-start" type="button" aria-haspopup="true" aria-expanded="false"></button>
                <div id="roleMenu" class="dropdown-menu w-100" role="menu" aria-labelledby="btnRole"></div>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Cari Menu</label>
              <input type="text" id="permSearch" class="form-control" placeholder="Ketik untuk mencari menu">
            </div>
          </div>
          <div class="row">
            <div class="col-12">
              <div id="permTree" class="perm-tree" role="tree" aria-label="Permissions"></div>
            </div>
          </div>
          <div class="mt-3 d-flex gap-2">
            <button id="btnSavePerm" class="btn btn-success">Simpan Pengaturan</button>
            <button id="btnResetPerm" class="btn btn-secondary">Reset</button>
            <span id="permState" class="text-warning small" style="display:none;">Perubahan belum disimpan</span>
          </div>
          <div id="changeReport" class="mt-2"></div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
(function(){
  var pendingPerm=false,currentRole='9',versionMap={};
  var serverPerms={};
  var mandatory=['home','profil','logout','orderanda','product'];
  var tips={
    'lapkeuangan':'Modul laporan keuangan',
    'bayar':'Bayar komisi/settlement',
    'invoice':'Kelola invoice',
    'payment':'Pengaturan payment gateway',
    'orderlist':'Kelola order',
    'manageproduk':'Kelola produk',
    'page':'Builder landing page',
    'import':'Import data',
    'member':'Manajemen member',
    'artikel':'Manajemen konten/artikel',
    'kategori':'Kategori konten',
    'email':'Template email',
    'tutorial':'Materi/tutorial'
  };
  var roles={ '1':'Member','5':'Admin Staff','6':'Finance Manager','7':'Operasional Manager','8':'Writer Manager','9':'Administrator' };
  function tooltip(label,key){ var t=(tips[key]||''); return '<span class="text-muted" title="'+t+'">'+label+'</span>'; }
  function permItem(key,label,checked,disabled){
    var dis = disabled?' disabled':''; var chk = checked?' checked':'';
    return '<div class="perm-item"><label class="form-check m-0"><input data-key="'+key+'" type="checkbox" class="form-check-input"'+chk+dis+'><span class="form-check-label">'+tooltip(label,key)+'</span></label></div>';
  }
  function createRoleDropdown(){ var btn=document.getElementById('btnRole'); var menu=document.getElementById('roleMenu'); function setLabel(){ btn.textContent=roles[currentRole]||('Role '+currentRole); }
    function close(){ menu.classList.remove('show'); btn.setAttribute('aria-expanded','false'); }
    function open(){ menu.classList.add('show'); btn.setAttribute('aria-expanded','true'); var first=menu.querySelector('[role="menuitem"]'); if(first){ first.focus(); activeIndex=0; highlightActive(); } }
    function highlightActive(){ var items=Array.from(menu.querySelectorAll('[role="menuitem"]')); items.forEach(function(it,i){ if(i===activeIndex){ it.classList.add('active'); it.scrollIntoView({block:'nearest'}); } else { it.classList.remove('active'); } }); }
    menu.innerHTML=''; Object.keys(roles).forEach(function(rc){ var it=document.createElement('button'); it.type='button'; it.className='dropdown-item'; it.setAttribute('role','menuitem'); it.setAttribute('tabindex','-1'); it.setAttribute('data-role',rc); it.textContent=roles[rc]; it.addEventListener('click', function(){ currentRole=this.getAttribute('data-role'); setLabel(); close(); loadRolePerm(currentRole); }); menu.appendChild(it); }); setLabel(); btn.addEventListener('click', function(){ if(menu.classList.contains('show')){ close(); } else { open(); } }); document.addEventListener('click', function(e){ if(!document.getElementById('roleDropdown').contains(e.target)){ close(); } }); btn.addEventListener('keydown', function(ev){ if(ev.key==='ArrowDown'){ ev.preventDefault(); open(); } else if(ev.key==='Enter' || ev.key===' '){ ev.preventDefault(); open(); } }); menu.addEventListener('keydown', function(ev){ var items=Array.from(menu.querySelectorAll('[role="menuitem"]')); if(ev.key==='Escape'){ ev.preventDefault(); close(); btn.focus(); } else if(ev.key==='ArrowDown'){ ev.preventDefault(); activeIndex=Math.min(items.length-1, activeIndex+1); highlightActive(); } else if(ev.key==='ArrowUp'){ ev.preventDefault(); activeIndex=Math.max(0, activeIndex-1); highlightActive(); } else if(ev.key==='Enter'){ ev.preventDefault(); if(items[activeIndex]){ items[activeIndex].click(); } } }); }
  function buildTree(perms){
    return fetch('<?= $weburl ?>api/permissions-list.php')
      .then(r=>r.json()).then(function(j){
        var T=document.getElementById('permTree'); T.innerHTML='';
        (j.tree||[]).forEach(function(node,i){
          var box=document.createElement('div'); box.className='tree-node'; box.setAttribute('role','treeitem'); box.setAttribute('aria-expanded','true'); box.setAttribute('tabindex','0');
          var header=document.createElement('div'); header.className='tree-header';
          var btn=document.createElement('button'); btn.type='button'; btn.className='tree-toggle'; btn.setAttribute('aria-controls','tree-'+i); btn.setAttribute('aria-expanded','true'); btn.innerHTML='<i class="fa-solid fa-chevron-down"></i>';
          header.appendChild(btn);
          var title=document.createElement('div'); title.textContent=node.label||('Group '+node.key); header.appendChild(title);
          box.appendChild(header);
          var children=document.createElement('div'); children.className='tree-children'; children.id='tree-'+i; children.setAttribute('role','group');
          (node.children||[]).forEach(function(it){ var allow=(currentRole==='9')?true:(!!perms[it.key]); var mand=mandatory.indexOf(it.key)>=0; var li=document.createElement('div'); li.innerHTML=permItem(it.key, it.label, allow||mand, mand); children.appendChild(li); });
          box.appendChild(children);
          btn.addEventListener('click', function(){ var exp=this.getAttribute('aria-expanded')==='true'; this.setAttribute('aria-expanded', String(!exp)); box.setAttribute('aria-expanded', String(!exp)); children.style.display = exp ? 'none' : ''; this.innerHTML = exp ? '<i class="fa-solid fa-chevron-right"></i>' : '<i class="fa-solid fa-chevron-down"></i>'; });
          T.appendChild(box);
        });
        Array.from(document.querySelectorAll('input[data-key]')).forEach(function(ch){ ch.addEventListener('change', function(){ pendingPerm=true; document.getElementById('permState').style.display='inline'; }); });
        return j;
      });
  }
  function loadRolePerm(rc){ currentRole = rc;
    fetch('<?= $weburl ?>api/role-permissions.php?role='+encodeURIComponent(rc))
      .then(r=>r.json()).then(function(j){ versionMap=j.version||{}; serverPerms=j.perms||{}; window.__initTime = Date.now(); var base=serverPerms; if(currentRole==='1'){ var defaults=['home','laporankomisi','produklist','klien','jaringan','orderanda','artikel','epistore']; defaults.forEach(function(k){ base[k]=true; }); } buildTree(base); });
  }
  function saveRolePerm(){ var body={ role: currentRole, items:[], version: versionMap, init_at: window.__initTime||Date.now()};
    var currentMap={}; Array.from(document.querySelectorAll('input[data-key]')).forEach(function(ch){ currentMap[ch.getAttribute('data-key')] = ch.checked?1:0; });
    Object.keys(currentMap).forEach(function(k){ var prev=serverPerms[k]===true?1:0; var now=currentMap[k]; if(prev!==now){ body.items.push({key:k, allowed: now}); } });
    if(body.items.length===0){ alert('Tidak ada perubahan'); return; }
    var nonMandActive=Object.keys(currentMap).filter(function(k){ return mandatory.indexOf(k)<0 && currentMap[k]===1; }); if(nonMandActive.length===0){ alert('Minimal satu menu non-wajib harus aktif'); return; }
    var diffCount=body.items.length;
    if(diffCount>5){ if(!confirm('Anda akan menyimpan '+diffCount+' perubahan. Lanjutkan?')){ return; } }
    fetch('<?= $weburl ?>api/role-permissions.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) })
      .then(function(r){ return r.json().catch(function(){ return {status:false, message:'Gagal memproses respon server'}; }); })
      .then(function(j){ 
        if(!j || j.status!==true){ alert(j && j.message ? j.message : 'Gagal menyimpan'); return; }
        alert(j.message||'Saved');
        pendingPerm=false; document.getElementById('permState').style.display='none';
        var rep=document.getElementById('changeReport'); if(rep){ var html=''; (j.changed||[]).forEach(function(ch){ html += '<div><span class="badge bg-info me-2">'+ch.key+'</span><span>'+(ch.prev===1?'Aktif':'Nonaktif')+' → '+(ch.new===1?'Aktif':'Nonaktif')+'</span><span class="ms-2 text-muted">'+ch.changed_at+'</span></div>'; }); rep.innerHTML=html; }
        loadRolePerm(currentRole);
      })
      .catch(function(){ alert('Jaringan bermasalah, coba lagi'); });
  }
  function filterPerms(term){ var q=(term||'').toLowerCase(); var T=document.getElementById('permTree'); Array.from(T.querySelectorAll('.tree-node')).forEach(function(node){ var children=Array.from(node.querySelectorAll('.form-check-label')); var any=false; children.forEach(function(lbl){ var match=(lbl.textContent||'').toLowerCase().indexOf(q)>=0; lbl.closest('div').style.display = (q==='' || match) ? '' : 'none'; if(match) any=true; }); node.style.display = (q==='' || any) ? '' : 'none'; }); }
  document.getElementById('btnSavePerm').addEventListener('click', function(){ saveRolePerm(); });
  document.getElementById('btnResetPerm').addEventListener('click', function(){ loadRolePerm(currentRole); document.getElementById('permState').style.display='none'; document.getElementById('permSearch').value=''; filterPerms(''); });
  if(!document.getElementById('btnResetDefault')){
    var btnDef=document.createElement('button'); btnDef.id='btnResetDefault'; btnDef.className='btn btn-outline-secondary'; btnDef.textContent='Reset Default';
    document.querySelector('.mt-3.d-flex.gap-2').appendChild(btnDef);
    btnDef.addEventListener('click', function(){ fetch('<?= $weburl ?>api/permissions-list.php').then(r=>r.json()).then(function(j){ var defaults={}; if(currentRole==='9'){ (j.data||[]).forEach(function(it){ defaults[it.key]=true; }); } else if(currentRole==='1'){ ['home','laporankomisi','produklist','klien','jaringan','orderanda','artikel','epistore'].forEach(function(k){ defaults[k]=true; }); } Array.from(document.querySelectorAll('input[data-key]')).forEach(function(ch){ var k=ch.getAttribute('data-key'); var mand = mandatory.indexOf(k)>=0; ch.checked = mand || !!defaults[k]; }); document.getElementById('permState').style.display='inline'; }); });
  }
  document.getElementById('permSearch').addEventListener('input', function(){ var v=this.value||''; if(window.__ps) clearTimeout(window.__ps); window.__ps=setTimeout(function(){ filterPerms(v); }, 200); });

  var activeIndex=0; createRoleDropdown(); loadRolePerm('9');
  setInterval(function(){ if(pendingPerm){ return; } var currentChecks={}; Array.from(document.querySelectorAll('input[data-key]')).forEach(function(ch){ currentChecks[ch.getAttribute('data-key')] = ch.checked; }); buildTree(serverPerms).then(function(){ var base=serverPerms; Array.from(document.querySelectorAll('input[data-key]')).forEach(function(ch){ var k=ch.getAttribute('data-key'); if(currentChecks.hasOwnProperty(k)){ ch.checked = currentChecks[k]; } else { ch.checked = (currentRole==='9') ? true : !!base[k]; } }); }); }, 30000);
})();
</script>
<?php showfooter(); ?>
