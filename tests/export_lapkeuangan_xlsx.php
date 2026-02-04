<?php
define('EPI_LIB_ONLY', true);
require_once dirname(__DIR__,1).'/api/export-lapkeuangan.php';
$data = array(
  array('Tanggal'=>'2025-01-01 10:00:00','Keterangan'=>'Tes A','Member'=>'Alpha','Pemasukan'=>1000,'Pengeluaran'=>0,'Saldo'=>1000),
  array('Tanggal'=>'2025-01-02 10:00:00','Keterangan'=>'Tes B','Member'=>'Beta','Pemasukan'=>0,'Pengeluaran'=>200,'Saldo'=>800),
);
$file = epi_build_xlsx_file($data, 'Laporan');
if ($file === false) { echo "FAIL: build_xlsx"; exit(1); }
$z = new ZipArchive();
if ($z->open($file)!==true) { echo "FAIL: open_zip"; exit(1); }
$ok = ($z->locateName('xl/workbook.xml')!==false) && ($z->locateName('xl/worksheets/sheet1.xml')!==false);
$xml = $z->getFromName('xl/worksheets/sheet1.xml');
$z->close();
@unlink($file);
if (!$ok) { echo "FAIL: zip_entries"; exit(1); }
if (strpos($xml,'Tanggal')===false || strpos($xml,'Keterangan')===false || strpos($xml,'Saldo')===false) { echo "FAIL: sheet_content"; exit(1); }
echo "PASS: XLSX"; exit(0);
