<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($datamember['mem_role'] < 5) { die(); exit(); }

if (isset($_GET['stats']) && $_GET['stats']==='member') {
    header('Content-Type: application/json; charset=utf-8');
    $totalAll = (int)db_var("SELECT COUNT(*) FROM `sa_member`");
    $totalFree = (int)db_var("SELECT COUNT(*) FROM `sa_member` WHERE `mem_status`=1");
    $totalPremium = (int)db_var("SELECT COUNT(*) FROM `sa_member` WHERE `mem_status`=2");
    $rows = db_select("SELECT `mem_id`,`mem_nama`,`mem_datalain` FROM `sa_member` WHERE `mem_status`=2 ORDER BY `mem_id` DESC");
    $validId = 0; $invalidId = 0; $validCert = 0; $invalidCert = 0;
    $statusOk = true; $statusMsg = '';
    $idList = array(); $certList = array();
    $statusOk = true; $statusMsg = '';
    $hasStateTable = (bool)db_var("SHOW TABLES LIKE 'epi_member_field_state'");
    $hasAuditTable = (bool)db_var("SHOW TABLES LIKE 'epi_member_field_audit'");
    if (is_array($rows)) {
        foreach ($rows as $r) {
            $d = extractdata(array('mem_datalain'=>$r['mem_datalain']));
            $idKeys = array('idepicresmi','id_epic_resmi','id_epic');
            $certKeys = array('linksertifikatepic','link_sertifikat_epic','sertifikat_epic','sertifikat');
            $hasId = false; $hasCert = false;
            $idVal = '';
            foreach ($idKeys as $k) { if (isset($d[$k]) && trim((string)$d[$k])!=='') { $idVal = trim((string)$d[$k]); $hasId = true; break; } }
            $certVal = '';
            foreach ($certKeys as $k) { if (isset($d[$k]) && trim((string)$d[$k])!=='') { $certVal = trim((string)$d[$k]); $hasCert = true; break; } }
            $idStatus = 'empty'; $certStatus = 'empty';
            if ($hasId) {
                if (preg_match('/^[A-Za-z0-9\-_]{3,64}$/', $idVal)) { $validId++; $idStatus = 'valid'; if (count($idList) < 10) { $idList[] = array('id'=>$idVal,'member_id'=>(int)$r['mem_id'],'name'=>cek($r['mem_nama'])); } }
                else { $invalidId++; $idStatus = 'invalid'; }
            } else { $invalidId++; }
            if ($hasCert) {
                if (preg_match('/^https?:\/\//', $certVal)) { $validCert++; $certStatus = 'valid'; if (count($certList) < 10) { $certList[] = array('link'=>$certVal,'member_id'=>(int)$r['mem_id'],'name'=>cek($r['mem_nama'])); } }
                else { $invalidCert++; $certStatus = 'invalid'; }
            } else { $invalidCert++; }
            if ($hasStateTable) {
                $memId = (int)$r['mem_id'];
                $st = db_row("SELECT `idepicresmi_value`,`idepicresmi_status`,`linksertifikatepic_value`,`linksertifikatepic_status` FROM `epi_member_field_state` WHERE `mem_id`=".$memId);
                if (!is_array($st)) {
                    db_query("INSERT INTO `epi_member_field_state` (`mem_id`,`idepicresmi_value`,`idepicresmi_status`,`linksertifikatepic_value`,`linksertifikatepic_status`,`updated_at`) VALUES (".$memId.", '".cek($idVal)."', '".cek($idStatus)."', '".cek($certVal)."', '".cek($certStatus)."', NOW())");
                    if ($hasAuditTable) { db_query("INSERT INTO `epi_member_field_audit` (`mem_id`,`field_key`,`old_value`,`new_value`,`old_status`,`new_status`,`changed_at`) VALUES (".$memId.", 'idepicresmi', '', '".cek($idVal)."', 'empty', '".cek($idStatus)."', NOW()), (".$memId.", 'linksertifikatepic', '', '".cek($certVal)."', 'empty', '".cek($certStatus)."', NOW())"); }
                } else {
                    if ((string)$st['idepicresmi_value'] !== (string)$idVal || (string)$st['idepicresmi_status'] !== (string)$idStatus) {
                        db_query("UPDATE `epi_member_field_state` SET `idepicresmi_value`='".cek($idVal)."', `idepicresmi_status`='".cek($idStatus)."', `updated_at`=NOW() WHERE `mem_id`=".$memId);
                        if ($hasAuditTable) { db_query("INSERT INTO `epi_member_field_audit` (`mem_id`,`field_key`,`old_value`,`new_value`,`old_status`,`new_status`,`changed_at`) VALUES (".$memId.", 'idepicresmi', '".cek($st['idepicresmi_value'])."', '".cek($idVal)."', '".cek($st['idepicresmi_status'])."', '".cek($idStatus)."', NOW())"); }
                    }
                    if ((string)$st['linksertifikatepic_value'] !== (string)$certVal || (string)$st['linksertifikatepic_status'] !== (string)$certStatus) {
                        db_query("UPDATE `epi_member_field_state` SET `linksertifikatepic_value`='".cek($certVal)."', `linksertifikatepic_status`='".cek($certStatus)."', `updated_at`=NOW() WHERE `mem_id`=".$memId);
                        if ($hasAuditTable) { db_query("INSERT INTO `epi_member_field_audit` (`mem_id`,`field_key`,`old_value`,`new_value`,`old_status`,`new_status`,`changed_at`) VALUES (".$memId.", 'linksertifikatepic', '".cek($st['linksertifikatepic_value'])."', '".cek($certVal)."', '".cek($st['linksertifikatepic_status'])."', '".cek($certStatus)."', NOW())"); }
                    }
                }
            }
        }
    } else {
        $statusOk = false; $statusMsg = 'DB error';
    }
    echo json_encode(array(
        'total_all'=>$totalAll,
        'total_free'=>$totalFree,
        'total_premium'=>$totalPremium,
        'id_valid'=>$validId,
        'id_invalid'=>$invalidId,
        'cert_valid'=>$validCert,
        'cert_invalid'=>$invalidCert,
        'id_list'=>$idList,
        'cert_list'=>$certList,
        'status'=>$statusOk,
        'message'=>$statusMsg
    ));
    exit;
}

if (isset($_POST['memberfu']) && !empty($_POST['memberfu'])) {
	setcookie('memberfu',rawurlencode($_POST['memberfu']),strtotime('+30 days'),'/');
	$ok = 1;
}

$head['pagetitle'] = 'Memberlist';
$head['scripthead'] = '<link href="'.$weburl.'fontawesome/css/brands.min.css" rel="stylesheet">';
showheader($head);
echo '<div id="memberPage" class="member-page">';
// Server-side computed aggregates for cards
$__totalAll = (int)db_var("SELECT COUNT(*) FROM `sa_member`");
$__totalFree = (int)db_var("SELECT COUNT(*) FROM `sa_member` WHERE `mem_status`=1");
$__totalPremium = (int)db_var("SELECT COUNT(*) FROM `sa_member` WHERE `mem_status`=2");
$__rowsPremium = db_select("SELECT `mem_id`,`mem_nama`,`mem_datalain` FROM `sa_member` WHERE `mem_status`=2 ORDER BY `mem_id` DESC");
$__validId = 0; $__invalidId = 0; $__validCert = 0; $__invalidCert = 0;
$__idList = array(); $__certList = array();
if (is_array($__rowsPremium)) {
    foreach ($__rowsPremium as $__r) {
        $__d = extractdata(array('mem_datalain'=>$__r['mem_datalain']));
        $__idKeys = array('idepicresmi','id_epic_resmi','id_epic');
        $__certKeys = array('linksertifikatepic','link_sertifikat_epic','sertifikat_epic','sertifikat');
        $__hasId = false; $__hasCert = false;
        $__idVal = '';
        foreach ($__idKeys as $__k) { if (isset($__d[$__k]) && trim((string)$__d[$__k])!=='') { $__idVal = trim((string)$__d[$__k]); $__hasId = true; break; } }
        $__certVal = '';
        foreach ($__certKeys as $__k) { if (isset($__d[$__k]) && trim((string)$__d[$__k])!=='') { $__certVal = trim((string)$__d[$__k]); $__hasCert = true; break; } }
        if ($__hasId) {
            if (preg_match('/^[A-Za-z0-9\-_]{3,64}$/', $__idVal)) { $__validId++; if (count($__idList) < 10) { $__idList[] = array('id'=>htmlspecialchars($__idVal, ENT_QUOTES),'name'=>htmlspecialchars($__r['mem_nama'], ENT_QUOTES),'member_id'=>(int)$__r['mem_id']); } }
            else { $__invalidId++; }
        } else { $__invalidId++; }
        if ($__hasCert) {
            if (preg_match('/^https?:\/\//', $__certVal)) { $__validCert++; if (count($__certList) < 10) { $__certList[] = array('link'=>htmlspecialchars($__certVal, ENT_QUOTES),'name'=>htmlspecialchars($__r['mem_nama'], ENT_QUOTES),'member_id'=>(int)$__r['mem_id']); } }
            else { $__invalidCert++; }
        } else { $__invalidCert++; }
    }
}
echo '<div class="row g-3 mb-3 align-items-stretch">'
.'<div class="col-12 col-md-4">'
.'<div class="card shadow-sm h-100" style="border-left:4px solid #D4AF37;">'
.'<div class="card-header d-flex justify-content-between align-items-center">'
.'<span class="fw-bold"><i class="fas fa-users"></i> Total Member EPIC Hub</span>'
.'</div>'
.'<div class="card-body">'
.'<div class="d-flex justify-content-between align-items-center mb-2"><span class="badge bg-dark">Total</span><span class="display-6 fw-bold" id="totalAll">'.number_format($__totalAll,0,',','.').'</span></div>'
.'<div class="d-flex justify-content-between align-items-center mb-2"><span class="badge bg-secondary">Free</span><span class="h5 mb-0" id="totalFree">'.number_format($__totalFree,0,',','.').'</span></div>'
.'<div class="d-flex justify-content-between align-items-center"><span class="badge bg-primary">Premium</span><span class="h5 mb-0" id="totalPremium">'.number_format($__totalPremium,0,',','.').'</span></div>'
.'</div>'
.'</div>'
.'</div>'
.'<div class="col-12 col-md-4">'
.'<div class="card shadow-sm h-100" style="border-left:4px solid #D4AF37;">'
.'<div class="card-header d-flex justify-content-between align-items-center">'
.'<span class="fw-bold"><i class="fas fa-id-card"></i> Total Pemilik ID EPIC</span>'
.'</div>'
.'<div class="card-body">'
.'<div class="d-flex justify-content-between align-items-center mb-2"><span class="badge bg-success">ID EPIC Valid</span><span class="h5 mb-0" id="idValid">'.number_format($__validId,0,',','.').'</span></div>'
.'<div class="d-flex justify-content-between align-items-center"><span class="badge bg-danger">ID EPIC Tidak Valid</span><span class="h5 mb-0" id="idInvalid">'.number_format($__invalidId,0,',','.').'</span></div>'
.'<div class="form-text">Hanya Premium Member</div>'
.'</div>'
.'</div>'
.'</div>'
.'<div class="col-12 col-md-4">'
.'<div class="card shadow-sm h-100" style="border-left:4px solid #D4AF37;">'
.'<div class="card-header d-flex justify-content-between align-items-center">'
.'<span class="fw-bold"><i class="fas fa-certificate"></i> Total Sertifikat EPIC</span>'
.'</div>'
.'<div class="card-body">'
.'<div class="d-flex justify-content-between align-items-center mb-2"><span class="badge bg-success">Sertifikat Valid</span><span class="h5 mb-0" id="certValid">'.number_format($__validCert,0,',','.').'</span></div>'
.'<div class="d-flex justify-content-between align-items-center"><span class="badge bg-danger">Sertifikat Tidak Valid</span><span class="h5 mb-0" id="certInvalid">'.number_format($__invalidCert,0,',','.').'</span></div>'
.'<div class="form-text">Hanya Premium Member</div>'
.'</div>'
.'</div>'
.'</div>'
.'</div>'.'</div>';
$echoErr = '<div id="statsError" class="alert alert-warning d-none" role="alert">Gagal memuat data statistik. Coba lagi beberapa saat.</div>';
echo $echoErr;
$__lp = (int)$__totalPremium;
echo <<<SCRIPT
<script>
var lastPremium= {$__lp};
function setText(id, val){ var el=document.getElementById(id); if(el){ el.textContent=new Intl.NumberFormat("id-ID").format(val); } }
function setList(id, items, isLink){
  var el=document.getElementById(id);
  if(!el) return;
  if(!items || items.length===0){
    el.innerHTML = '<li class="list-group-item py-1 text-muted">'+(id==='idValidList'?'Belum ada data valid':'Belum ada link sertifikat')+'</li>';
    return;
  }
  var html='';
  for(var i=0;i<items.length;i++){
    var v=items[i];
    if(isLink){
      html += '<li class="list-group-item py-1"><a href="'+v.link+'" target="_blank" rel="noopener">'+v.link+'</a></li>';
    } else {
      html += '<li class="list-group-item py-1">'+v.id+'</li>';
    }
  }
  el.innerHTML = html;
}
function refreshStats(){ fetch(window.location.pathname+"?stats=member",{method:"GET"}).then(function(r){ if(!r.ok){ throw new Error("HTTP"+r.status); } return r.json(); }).then(function(j){ setText("totalAll", j.total_all||0); setText("totalFree", j.total_free||0); setText("totalPremium", j.total_premium||0); setText("idValid", j.id_valid||0); setText("idInvalid", j.id_invalid||0); setText("certValid", j.cert_valid||0); setText("certInvalid", j.cert_invalid||0); setList("idValidList", j.id_list||[], false); setList("certValidList", j.cert_list||[], true); lastPremium = j.total_premium||0; var eb=document.getElementById('statsError'); if(eb){ if(j && j.status===false){ eb.classList.remove('d-none'); eb.textContent = j.message || eb.textContent; } else { eb.classList.add('d-none'); } } }).catch(function(err){ var eb=document.getElementById('statsError'); if(eb){ eb.classList.remove('d-none'); eb.textContent = 'Gagal memuat data statistik: '+(err && err.message ? err.message : 'Unknown error'); } }); }
document.addEventListener("DOMContentLoaded", function(){ setInterval(refreshStats, 10000); });
</script>
SCRIPT;
if (isset($ok)) {
	echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
		  <strong>Ok!</strong> Konten Follow Up telah dipasang di link whatsapp member anda. Selamat mencoba.
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
} elseif (isset($_GET['del']) && is_numeric($_GET['del'])) {
	# Hapus Member dan Pindahkan downlinenya ke admin
	$cek = db_query("DELETE FROM `sa_member` WHERE `mem_id`=".$_GET['del']);
	$cek = db_query("DELETE FROM `sa_sponsor` WHERE `sp_mem_id`=".$_GET['del']);
	$cek = db_query("UPDATE `sa_sponsor` SET `sp_sponsor_id`=".$iduser.",`sp_network`='[".$iduser."]' WHERE `sp_sponsor_id`=".$_GET['del']);

	if ($cek === false) {
		echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
		  <strong>Error!</strong> '.db_error().'
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	} else {
		echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
		  <strong>Ok!</strong> Member ID: '.$_GET['del'].' telah dihapus.
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	}
} elseif (isset($_GET['up']) && is_numeric($_GET['up'])) {
	$upmember = db_row("SELECT * FROM `sa_member` WHERE `mem_id`=".$_GET['up']." AND `mem_status` = 1");
	

	if (isset($upmember['mem_id'])) {
		db_query("UPDATE `sa_member` SET `mem_status`=2 WHERE `mem_id`=".$_GET['up']);
		sa_notif('upgrade',$_GET['up']);
		
		echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
		  <strong>Ok!</strong> '.$upmember['mem_nama'].' telah diupgrade.
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	} else {
		echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
		  <strong>Error!</strong> Member tidak ditemukan. Mungkin sudah diupgrade sebelumnya.
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	}
}
?>
<form action="" method="get" id="memberFiltersForm">
<div class="card mb-3">
  <div class="card-body">
    <div class="row">    
      <div class="col-sm-9">
          <div class="input-group" id="memberFilters">
            <input type="text" class="form-control" name="cari" placeholder="Tuliskan Data Nama/Email/Whatsapp disini" value="<?= $_GET['cari'] ??= '';?>">
            <?php 
            $select = array('','','');
            if (isset($_GET['status']) && is_numeric($_GET['status'])) {
              $select[$_GET['status']] = ' selected';
            }
          ?>
          <select name="status" class="form-select">
            <option value="">All Member</option>
            <option value="1"<?=$select[1];?>>Free Member</option>
            <option value="2"<?=$select[2];?>>Premium Member</option>
          </select>
          <input type="submit" value=" Cari " class="btn btn-secondary">
        </div>        
      </div>
        <div class="col-sm-3 text-end">
        	<a href="member?edit=new" class="btn btn-success" aria-label="Tambah member baru"><i class="fa-solid fa-plus"></i><span>Add</span></a> &nbsp;
        	<a href="export?data=member" class="btn btn-primary" aria-label="Unduh data member"><i class="fa-solid fa-cloud-arrow-down"></i><span>Download</span></a>
        </div>
    </div>
  </div>
</div>
</form>
<div class="table-responsive">
<table class="table table-hover table-bordered">
	<thead class="table-secondary">
		<tr>
			<th>ID</th>
			<th>Nama</th>
			<th class="d-none d-sm-table-cell">Email</th>
			<th class="d-none d-sm-table-cell">WhatsApp</th>
			<th class="d-none d-sm-table-cell">Sponsor</th>
			<th>&nbsp;</th>
		</tr>
	</thead>
	<tbody>
		<?php 
		$jmlperpage = 25;
		if (isset($_GET['start']) && is_numeric($_GET['start'])) {
		    $start = ($_GET['start'] - 1) * $jmlperpage;
		    $page = $_GET['start'];
		} else {
		    $start = 0;
		    $page = 1;
		}		
		
		$where = '';
		if (isset($_GET['cari']) && !empty($_GET['cari'])) {
			$s = cek($_GET['cari']);
			$where .= "WHERE (`m`.`mem_nama` LIKE '%".$s."%' 
								OR `m`.`mem_email` LIKE '%".$s."%' 
								OR `m`.`mem_whatsapp` LIKE '%".$s."%' 
								OR `m`.`mem_datalain` LIKE '%".$s."%' 
								OR `m`.`mem_kodeaff` LIKE '%".$s."%')";
		}

		if (isset($_GET['status']) && is_numeric($_GET['status'])) {
			if ($where == '') {
				$where .= "WHERE `m`.`mem_status`=".$_GET['status'];			
			} else {
				$where .= " AND `m`.`mem_status`=".$_GET['status'];			
			}
		}

		$data = db_select("SELECT 
			`m`.`mem_nama` AS `NamaMember`,
			`m`.`mem_whatsapp` AS `WAMember`,
			`m`.`mem_email` AS `EmailMember`,
			`m`.`mem_id` AS `IDMember`,
			`m`.`mem_status` AS `StatusMember`,
			`s`.`mem_nama` AS `NamaSponsor`,
			`s`.`mem_whatsapp` AS `WASponsor`,
			`s`.`mem_email` AS `EmailSponsor`,
			`s`.`mem_id` AS `IDSponsor`,
			`s`.`mem_status` AS `StatusSponsor`
			FROM `sa_member` `m` LEFT JOIN `sa_sponsor` `k` ON `m`.`mem_id` = `k`.`sp_mem_id` 
			LEFT JOIN `sa_member` `s` ON `k`.`sp_sponsor_id` = `s`.`mem_id`
			".$where."
			ORDER BY `m`.`mem_tgldaftar` DESC
			LIMIT ".$start.",".$jmlperpage);
		if (count($data) > 0) {
			foreach ($data as $data) {
				if (isset($_POST['memberfu']) && !empty($_POST['memberfu'])) {
					$memberfu = rawurlencode($_POST['memberfu']);
				} elseif (isset($_COOKIE['memberfu'])) {
					$memberfu = $_COOKIE['memberfu'];				
				} else {
					$memberfu = '';
				}

				$memberfu = str_replace('%5Bnama%5D', $data['NamaMember'], $memberfu);

				echo '
				<tr>
				<td>'.$data['IDMember'].'</td>
				<td>
				<a href="member?edit='.$data['IDMember'].'">'.$data['NamaMember'].'</a>';

				if ($data['StatusMember'] == 2) { echo ' <sup><i class="fa-solid fa-circle-check text-success" title="Premium"></i></sup>'; }

				echo '
				<span class="d-sm-none">
					<br/><i class="fa-regular fa-envelope"></i> '.$data['EmailMember'].'
					<br/><i class="fa-brands fa-whatsapp"></i> <a href="https://wa.me/'.$data['WAMember'].'?text='.$memberfu.'" target="_blank">'.$data['WAMember'].'</a>
					<br/><i class="fa-solid fa-user-tie"></i> <a href="member?edit='.$data['IDSponsor'].'">'.$data['NamaSponsor'].'</a>
				</span>
				</td>
				<td class="d-none d-sm-table-cell">'.$data['EmailMember'].'</td>
				<td class="d-none d-sm-table-cell"><a href="https://wa.me/'.$data['WAMember'].'?text='.$memberfu.'" target="_blank">'.$data['WAMember'].'</a></td>
				<td class="d-none d-sm-table-cell"><a href="member?edit='.$data['IDSponsor'].'">'.$data['NamaSponsor'].'</a>';
				
				if ($data['StatusSponsor'] == 2) { echo ' <sup><i class="fa-solid fa-circle-check text-success" title="Premium"></i></sup>'; }

				echo '</td>
				<td class="text-end">';
				
				if ($data['StatusMember'] == 1) {
					echo '<a href="?up='.$data['IDMember'].'"><i class="fa-solid fa-circle-arrow-up text-success" title="Upgrade"></i></a>';
				} 

				if ($data['IDMember'] != 1) {
					echo '
					&nbsp; 
					<a href="#" data-bs-toggle="modal" data-bs-target="#konfirmasi" data-bs-nama="'.$data['NamaMember'].'" 
					data-bs-id="'.$data['IDMember'].'"><i class="fa-solid fa-trash-can text-danger" title="Delete"></i></a>';
				}

				echo '
				</td>
				</tr>';
			}
		} else {
			echo '<tr><td colspan="4">Belum ada member</td></tr>';
		}
		?>
	</tbody>
</table>
</div>
<?php
$jmlmember = db_var("SELECT count(*)
	FROM `sa_member` `m` LEFT JOIN `sa_sponsor` `k` ON `m`.`mem_id` = `k`.`sp_mem_id` 
	LEFT JOIN `sa_member` `s` ON `k`.`sp_sponsor_id` = `s`.`mem_id`
	".$where);
$jmlpage = ceil($jmlmember/$jmlperpage);
echo '
<nav aria-label="Page navigation" class="mt-3">
  <ul class="pagination">';
if ($jmlpage > 10) {
  if ($page <= 4){
    # Depan
    for ($i=1;$i<=5;$i++) {
        if ($i == $page) {
            echo '<li class="page-item active"><a class="page-link" href="?start='.$i.'">'.$i.'<span class="sr-only">(current)</span></a></li>';
        } else {
            echo '<li class="page-item"><a class="page-link" href="?start='.$i.'">'.$i.'</a></li>';
        }
    }
    echo '
    <li class="page-item disabled"><a class="page-link" href="#">...</a></li>
    <li class="page-item"><a class="page-link" href="?start='.$jmlpage.'">'.$jmlpage.'</a></li>';
  } elseif ($page >= 5 && $page <= ($jmlpage-5)) {
    # Tengah
    echo '<li class="page-item"><a class="page-link" href="?start=1">1</a></li>
    <li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
    for ($i=($page-2);$i<=($page+2);$i++) {
        if ($i == $page) {
            echo '<li class="page-item active"><a class="page-link" href="?start='.$i.'">'.$i.'<span class="sr-only">(current)</span></a></li>';
        } else {
            echo '<li><a class="page-link" href="?start='.$i.'">'.$i.'</a></li>';
        }
    }
    echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>
    <li class="page-item"><a class="page-link" href="?start='.$jmlpage.'">'.$jmlpage.'</a></li>';
  } else {
    # Belakang
    echo '<li class="page-item"><a class="page-link" href="?start=1">1</a></li>
    <li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
    for ($i=($jmlpage-5);$i<=$jmlpage;$i++) {
        if ($i == $page) {
            echo '<li class="page-item active"><a class="page-link" href="?start='.$i.'">'.$i.'<span class="sr-only">(current)</span></a></li>';
        } else {
            echo '<li><a class="page-link" href="?start='.$i.'">'.$i.'</a></li>';
        }
    }
  }
} else {
  for ($i=1;$i<=$jmlpage;$i++) {
      if ($i == $page) {
          echo '<li class="page-item active"><a class="page-link" href="?start='.$i.'">'.$i.'<span class="sr-only">(current)</span></a></li>';
      } else {
          echo '<li class="page-item"><a class="page-link" href="?start='.$i.'">'.$i.'</a></li>';
      }
  }
}

echo '
	</ul>
</nav>';
?>

<form action="" method="post">
<div class="card mb-3">
	<div class="card-body">
		<textarea name="memberfu" placeholder="Konten follow up via WhatsApp" class="form-control"></textarea>
		<small class="form-text text-muted mb-3">Silahkan menambah kata-kata follow up sebelum klik link whatsapp klien di atas. Gunakan shortcode [nama] untuk menampilkan nama klien</small>
		<br/><input type="submit" value="Simpan" class="btn btn-primary">
	</div>
</div>
</form>

<!-- Modal -->
<div class="modal fade" id="konfirmasi" tabindex="-1" aria-labelledby="konfirmasilabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="konfirmasilabel">JUDUL</h5>
        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">ISI
      </div>
      <div class="modal-footer">        
        <a href="#" class="btn btn-secondary delbutton">Hapus</a>
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Batal</button>
      </div>
    </div>
  </div>
</div>

<?php
$footer['konfirm'] = "⚠️ Anda akan menghapus <strong>'+nama+'</strong>. Semua downline di bawahnya akan diarahkan ke admin.";
echo '</div>';
showfooter($footer);
?>
