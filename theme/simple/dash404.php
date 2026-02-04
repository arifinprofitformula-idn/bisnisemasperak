<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
$head['pagetitle']='Not Found';
showheader($head);
?>
	  <div class="row mt-3 align-items-center">
	    <div class="col d-none d-md-block align-self-start">	      
	    </div>
	    <div class="col-md-6 col-lg-6 col-sm-12 align-self-center text-center bg-body-tertiary rounded p-3 border">
	      <h1>Not Found</h1>	      
	      <p><img src="<?= $weburl?>img/notfound.jpg" alt="Not Found"></p>
	      <p>Maaf, halaman yang anda akses tidak tersedia</p>
	    </div>
	    <div class="col d-none d-md-block align-self-end">	      
	    </div>
	  </div>
<?php showfooter(); ?>