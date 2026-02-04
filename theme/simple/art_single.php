<div class="row g-0 border rounded overflow-hidden flex-md-row mb-4 shadow-sm h-md-250 position-relative">
	<div class="col p-4 d-flex flex-column position-static fr-view bg-white">
		<h1><?= $data['art_judul'];?></h1>
		<div class="mb-1 text-body-secondary">
			Publish: <?= date('d M Y H:i',strtotime($data['art_tglpublish']));?>
			 by <?= $data['mem_nama'];?>
		</div>
		<?= $showartikel;?>
		<?= $showadminbutton;?>
	</div>
</div>