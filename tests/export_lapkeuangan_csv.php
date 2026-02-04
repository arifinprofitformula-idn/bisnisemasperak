<?php
define('EPI_LIB_ONLY', true);
require_once dirname(__DIR__,1).'/api/export-lapkeuangan.php';
$data = array(
  array('Tanggal'=>'2025-01-01 10:00:00','Keterangan'=>'Tes A','Member'=>'Alpha','Pemasukan'=>1000,'Pengeluaran'=>0,'Saldo'=>1000),
  array('Tanggal'=>'2025-01-02 10:00:00','Keterangan'=>'Tes B','Member'=>'Beta','Pemasukan'=>0,'Pengeluaran'=>200,'Saldo'=>800),
);
$csv = epi_build_csv_string($data);
if (!$csv || !is_string($csv)) { echo "FAIL: build_csv"; exit(1); }
$lines = preg_split('/\r?\n/', trim($csv));
if (count($lines) !== 3) { echo "FAIL: csv_lines"; exit(1); }
if (strpos($lines[0],'Tanggal')===false || strpos($lines[0],'Saldo')===false) { echo "FAIL: csv_header"; exit(1); }
echo "PASS: CSV"; exit(0);
