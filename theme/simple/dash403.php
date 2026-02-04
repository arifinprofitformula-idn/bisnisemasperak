<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
$head['pagetitle']='Forbidden';
showheader($head);
?>
	  <div class="row mt-3 align-items-center">
	    <div class="col d-none d-md-block align-self-start">	      
	    </div>
	    <div class="col-md-6 col-lg-6 col-sm-12 align-self-center text-center rounded p-3 border">
	      <h1>Forbidden</h1>	      
	      <p><img src="<?= $weburl?>img/forbidden.gif" width="100%" alt="Forbidden"></p>
	      <p>Maaf, anda tidak diijinkan mengakses halaman ini</p>
	    </div>
	    <div class="col d-none d-md-block align-self-end">	      
	    </div>
	  </div>
<?php showfooter(); ?>