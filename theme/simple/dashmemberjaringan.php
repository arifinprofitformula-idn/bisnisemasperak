<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }

$head['pagetitle'] = 'Jaringan Anda';
showheader($head);

$data = db_select("SELECT * FROM `sa_sponsor` LEFT JOIN `sa_member` ON `sa_member`.`mem_id`=`sa_sponsor`.`sp_mem_id` WHERE `sp_sponsor_id`=".$iduser);
if (count($data) > 0) {
	$status = array('Blm Valid','Free Member','Premium');
	echo '<div id="downline1">';
	$i = 0;
	foreach ($data as $data) {
		echo '
		<div class="listmember" id="member'.$data['mem_id'].'"><img src="'.$weburl.'/img/join.gif" style="height:18px;width:18px"/>';
		$jmldownline = '';		
		if (isset($data['sp_jmldownline'])) {
			$jmldownline = ' - '.$data['sp_jmldownline'];
		}
		echo '<a class="folder" id="'.$data['mem_id'].'"><img src="'.$weburl.'/img/folder.gif" id="down'.$data['mem_id'].'" style="display: inline; width:18px"/></a>
		<a class="detil" id="detil'.$data['mem_id'].'">'.$data['mem_nama'].'</a> <em>('.$status[$data['mem_status']].')</em>'.$jmldownline.'</div>';
		$i++;
	}

	if ($i == 0) { echo '<div>Anda belum memiliki jaringan. Silahkan berpromosi dan rekrut member baru</div>';}
	echo '
	</div>
	<div id="detilprofil"></div>
	<div style="clear:both"></div>
	';
} else {
	echo 'Belum ada jaringan';
}
$footer['scriptfoot'] = '
<script type="text/javascript">
	var $j = jQuery.noConflict();
	$j(function(){
		$j(document).on(\'click\',\'.folder\', function() {
			var idmember = this.id;			
			if ( $j("#downline"+idmember ).length ) {
				$j("#downline"+idmember).remove();
				$j("#down"+idmember).attr(\'src\',\''.$weburl.'img/folder.gif\');
			} else {
				$j("#member"+idmember).append(\' <img src="'.$weburl.'img/load.gif" id="load"/>\');
				$j.get("'.$weburl.'jaringanmember", { id: this.id },
				   	function(data){
				    	$j("#load").remove();
				    	$j("#member"+idmember).append(data);
				 	});
				$j("#down"+idmember).attr(\'src\',\''.$weburl.'img/folderopen.gif\');
			}
		});

		$j("#detilprofil").hide();
		
		$j(document).on(\'click\',\'.close\', function() {
			$j("#detilprofil").hide();
		});

		$j(document).on(\'click\',\'.detil\', function() {
			var idmember = this.id;
			if ($j("#themember").length) {
				$j("#themember").remove();
			}
			$j("#detilprofil").show();
			$j("#detilprofil").append(\' <img src="'.$weburl.'img/load.gif" id="load"/>\');
			$j.get("'.$weburl.'jaringanmember", { member: this.id },
			   	function(data){
			    	$j("#load").remove();
			    	$j("#detilprofil").append(data);
			 	});			
		});
	})
</script>';
showfooter($footer); ?>