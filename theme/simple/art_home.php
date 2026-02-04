<?php
if (isset($slug[1]) && isset($_GET['add']) && is_numeric($_GET['add'])) {
	if (isset($datamember['mem_role']) && $datamember['mem_role'] >= 2) {
		if ($slug[1] == $slugartikel) {
			$idkat = $_GET['add'];
			include('addartikel.php');
		} else {
			if ($datamember['mem_role'] == 2) {
				echo 'Maaf, anda tidak bisa menambah kategori ini';
			} else {
				include('addkategori.php');
			}
		}
	} else {
		include('art_404.php');
	}
} elseif (isset($slug[1]) && isset($_GET['edit']) && is_numeric($_GET['edit'])) {
	if (isset($datamember['mem_role']) && $datamember['mem_role'] >= 2) {
		if ($slug[1] == $slugartikel) {
			$artikel = db_row("SELECT * FROM `sa_artikel` WHERE `art_id`=".$_GET['edit']);
			if (isset($artikel['art_id'])) {
				if ($datamember['mem_role'] == 2 && $artikel['art_writer'] != $datamember['mem_id']) {
					echo 'Maaf, anda bukan penulis artikel ini';
				} else {
					$idkat = $artikel['art_kat_id'];
					include('addartikel.php');
				}
			}
		} else {
			if ($datamember['mem_role'] == 2) {
				echo 'Maaf, anda tidak bisa menambah kategori ini';
			} else {
				include('addkategori.php');
			}
		}
	} else {
		include('art_404.php');
	}
} else {
	include('art_list.php');
}
?>