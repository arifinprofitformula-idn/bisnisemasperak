<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($datamember['mem_role'] < 5) { die(); exit(); }
$head['pagetitle']='Tutorial';
$settings = getsettings();
showheader($head);
?>
<div class="card">
  <div class="card-header">
      Tutorial Penggunaan SimpleAff
  </div>
  <div class="card-body">
  	<ol>
  	<?php
  	$tutorial = getData('https://cafebisnis.com/tutorialsimpleaffplus.php');
  	$tutorial = json_decode($tutorial,TRUE);
  	if (is_array($tutorial)) {
  		foreach ($tutorial as $tutorial) {
  			if (isset($tutorial['url']) && !empty($tutorial['url'])) {
  				echo '<li><a href="'.$tutorial['url'].'">'.$tutorial['title'].'</a></li>';
  			}
  		}
  	}
  	?>
  	</ol>
  </div>
</div>
<?php showfooter(); ?>