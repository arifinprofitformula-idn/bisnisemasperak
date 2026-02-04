<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
$dataklien = db_row("SELECT * FROM `sa_member` 
	LEFT JOIN `sa_sponsor` ON `sa_sponsor`.`sp_mem_id` = `sa_member`.`mem_id` 
	WHERE `mem_id`=".$_GET['id']." AND `sp_sponsor_id`=".$iduser);
}
$head['pagetitle']='Profil '.$dataklien['mem_nama'];
showheader($head);
?>
<div class="card">
  <div class="card-body">
	  <?php 
	  $dataform = extractdata($dataklien);
		
	  $formfield = db_select("SELECT * FROM `sa_form` WHERE `ff_network`=1 ORDER BY `ff_sort`");
	  if (count($formfield) > 0) {
	  	foreach ($formfield as $formfield) {
	  		echo '
	  		<div class="mb-3 row">
	  			<div class="col-sm-4">'.$formfield['ff_label'].'</div>
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
			<div class="col-sm-4">Tanggal Daftar</div>
			<div class="col-sm-8"><?php echo $dataklien['mem_tgldaftar'];?></div>
		</div>
		<div class="mb-3 row">
			<div class="col-sm-4">Status</div>
			<div class="col-sm-8"><?php 
			$statusklien = array('','Free Member','Premium');
			echo $statusklien[$dataklien['mem_status']];?></div>
		</div>
		
	</div>
</div>
<?php showfooter(); ?>