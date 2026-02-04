<?php
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
$status = array('Blm Valid','Free Member','Premium');
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
	$id = $_GET['id'];	
	$data = db_select("SELECT * FROM `sa_sponsor` LEFT JOIN `sa_member` ON `sa_member`.`mem_id`=`sa_sponsor`.`sp_mem_id` WHERE `sp_sponsor_id`=".$id);
			if (count($data) > 0) {
			echo '<div class="geserkanan" id="downline'.$id.'">';
			foreach ($data as $data) {
				echo '
				<div class="listmember" id="member'.$data['mem_id'].'">
				<img src="'.$weburl.'img/join.gif" />';
				if ($data['mem_status'] > 0) {				
					echo '<a class="folder" id="'.$data['mem_id'].'"><img src="'.$weburl.'img/folder.gif" id="down'.$data['mem_id'].'" style="display: inline; width:18px"/></a><a class="detil" id="detil'.$data['mem_id'].'">'.$data['mem_nama'].'</a> <em>('.$status[$data['mem_status']].')</em></div>';
				} else {
					echo ' <img src="'.$weburl.'img/folder.gif" style="display: inline;" id="down'.$data['mem_id'].'"/>
					'.$data['mem_nama'].' <em>(blm valid)</em></div>';
				}
			}
			echo '</div>';
		}
} else {
	$idmember = str_replace('detil','',$_GET['member']);
	$dataklien = db_row("SELECT * FROM `sa_sponsor` LEFT JOIN `sa_member` ON `sa_member`.`mem_id`=`sa_sponsor`.`sp_mem_id` 
		WHERE `sp_mem_id`=".$idmember);
	
	echo '
	<div id="themember">
		<a class="close" title="Tutup"><i class="fa-solid fa-xmark"></i></a>
		<div class="p-3">
	';
		
		$dataform = extractdata($dataklien);
	  $formfield = db_select("SELECT * FROM `sa_form` WHERE `ff_network`=1 ORDER BY `ff_sort`");
	  if (count($formfield) > 0) {
	  	foreach ($formfield as $formfield) {
	  		echo '
	  		<div class="mb-3 row">
	  			<div class="col-sm-4"><strong>'.$formfield['ff_label'].':</strong></div>
	  			<div class="col-sm-8">';
	  		if (isset($dataform[$formfield['ff_field']])) {
	  			if ($formfield['ff_field'] == 'kodeaff') {
	  				echo '<a href="'.$weburl.$dataform[$formfield['ff_field']].'">'.$weburl.$dataform[$formfield['ff_field']].'</a>';
	  			} elseif ($formfield['ff_field'] == 'whatsapp') {
	  				echo '<a href="https://wa.me/'.$dataform[$formfield['ff_field']].'">'.$dataform[$formfield['ff_field']].'</a>';
	  			} else {
	  				echo $dataform[$formfield['ff_field']];
	  			}
	  		} else {
	  			echo '-';
	  		}
	  		echo '</div>
	  		</div>
	  		';
	  	}
	  }
	  ?>
		  <div class="mb-3 row">
				<div class="col-sm-4"><strong>Tanggal Daftar:</strong></div>
				<div class="col-sm-8"><?php echo $dataklien['mem_tgldaftar'];?></div>
			</div>
			<div class="mb-3 row">
				<div class="col-sm-4"><strong>Status:</strong></div>
				<div class="col-sm-8"><?php echo $status[$dataklien['mem_status']];?></div>
			</div>
		</div>
	</div>
<?php
}
?>