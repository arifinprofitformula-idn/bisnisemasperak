<?php
$__root = dirname(__DIR__, 1);
@include_once $__root . DIRECTORY_SEPARATOR . 'config.php';
@include_once $__root . DIRECTORY_SEPARATOR . 'fungsi.php';
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

function epi_build_xlsx_file($rows, $sheetName='Laporan'){
  if (!class_exists('ZipArchive')) { return false; }
  $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
  $zip = new ZipArchive();
  if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) { return false; }
  $ct = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>
XML;
  $rels = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
XML;
  $wbName = htmlspecialchars($sheetName, ENT_QUOTES);
  $wb = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets><sheet name="$wbName" sheetId="1" r:id="rId1"/></sheets>
</workbook>
XML;
  $wbrels = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML;
  $styles = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>
  <fills count="1"><fill/></fills>
  <borders count="1"><border/></borders>
  <cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>
</styleSheet>
XML;
  $coreCreated = gmdate('Y-m-d\TH:i:s\Z');
  $core = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:title>Laporan</dc:title>
  <dc:creator>EPI Hub</dc:creator>
  <cp:lastModifiedBy>EPI Hub</cp:lastModifiedBy>
  <dcterms:created xsi:type="dcterms:W3CDTF">$coreCreated</dcterms:created>
  <dcterms:modified xsi:type="dcterms:W3CDTF">$coreCreated</dcterms:modified>
</cp:coreProperties>
XML;
  $app = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
  <Application>EPI Hub</Application>
</Properties>
XML;
  $sheetHead = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>
XML;
  $headers = array('Tanggal','Keterangan','Member','Pemasukan','Pengeluaran','Saldo');
  $colLetters = array('A','B','C','D','E','F');
  $sheet = '';
  $r = 1; $sheet .= '<row r="'.$r.'">';
  for($i=0;$i<count($headers);$i++){ $sheet .= '<c r="'.$colLetters[$i].$r.'" t="inlineStr"><is><t>'.htmlspecialchars($headers[$i],ENT_QUOTES).'</t></is></c>'; }
  $sheet .= '</row>'; $r++;
  foreach ($rows as $d){
    $sheet .= '<row r="'.$r.'">'
           . '<c r="A'.$r.'" t="inlineStr"><is><t>'.htmlspecialchars((string)$d['Tanggal'],ENT_QUOTES).'</t></is></c>'
           . '<c r="B'.$r.'" t="inlineStr"><is><t>'.htmlspecialchars((string)$d['Keterangan'],ENT_QUOTES).'</t></is></c>'
           . '<c r="C'.$r.'" t="inlineStr"><is><t>'.htmlspecialchars((string)$d['Member'],ENT_QUOTES).'</t></is></c>'
           . '<c r="D'.$r.'"><v>'.(int)$d['Pemasukan'].'</v></c>'
           . '<c r="E'.$r.'"><v>'.(int)$d['Pengeluaran'].'</v></c>'
           . '<c r="F'.$r.'"><v>'.(int)$d['Saldo'].'</v></c>'
           . '</row>';
    $r++;
  }
  $sheetTail = '</sheetData></worksheet>';
  $zip->addFromString('[Content_Types].xml', $ct);
  $zip->addFromString('_rels/.rels', $rels);
  $zip->addFromString('xl/workbook.xml', $wb);
  $zip->addFromString('xl/_rels/workbook.xml.rels', $wbrels);
  $zip->addFromString('xl/styles.xml', $styles);
  $zip->addFromString('xl/worksheets/sheet1.xml', $sheetHead.$sheet.$sheetTail);
  $zip->addFromString('docProps/core.xml', $core);
  $zip->addFromString('docProps/app.xml', $app);
  $zip->close();
  return $tmp;
}

function epi_build_csv_string($rows){
  $m = fopen('php://temp','r+');
  fputcsv($m, array('Tanggal','Keterangan','Member','Pemasukan','Pengeluaran','Saldo'));
  foreach($rows as $d){ fputcsv($m, array($d['Tanggal'],$d['Keterangan'],$d['Member'],$d['Pemasukan'],$d['Pengeluaran'],$d['Saldo'])); }
  rewind($m); $s = stream_get_contents($m); fclose($m); return $s;
}

$currentUser = null;
try {
    if (function_exists('is_login')) {
        $uid = is_login();
        if ($uid && function_exists('getdatamember')) { $userRow = getdatamember((int)$uid); if (is_array($userRow) && isset($userRow['mem_id'])) { $currentUser = $userRow; } }
    }
} catch (Throwable $e) {}
if (!$currentUser && isset($_SESSION['member']) && is_array($_SESSION['member'])) { $currentUser = $_SESSION['member']; }
if (!$currentUser && isset($datamember) && is_array($datamember)) { $currentUser = $datamember; }
if (!$currentUser || (int)($currentUser['mem_role'] ?? 0) < 9) { http_response_code(403); echo 'Forbidden'; exit; }

$detil = isset($_GET['detil']) ? trim((string)$_GET['detil']) : '';
$tahun = date('Y'); $bulan = date('m');
if ($detil && preg_match('/^\d{4}-\d{2}$/', $detil)) { $parts = explode('-', $detil); $tahun = $parts[0]; $bulan = $parts[1]; }
else {
  if (isset($_GET['tahun']) && preg_match('/^\d{4}$/', $_GET['tahun'])) { $tahun = (string)$_GET['tahun']; }
  if (isset($_GET['bulan']) && preg_match('/^\d{2}$/', $_GET['bulan'])) { $bulan = (string)$_GET['bulan']; }
}
$format = isset($_GET['format']) ? strtolower(trim((string)$_GET['format'])) : 'xlsx';
if (!in_array($format, array('xlsx','csv'), true)) { $format = 'xlsx'; }

$q = "SELECT `sa_laporan`.*,`sa_member`.`mem_nama` FROM `sa_laporan` LEFT JOIN `sa_member` ON `sa_member`.`mem_id`=`sa_laporan`.`lap_idmember` WHERE MONTH(`lap_tanggal`)=".(int)$bulan." AND YEAR(`lap_tanggal`)=".(int)$tahun." AND `lap_code`=1 ORDER BY `lap_tanggal`";
$rows = db_select($q);
$data = array(); $saldo = 0;
if (is_array($rows)) {
  foreach ($rows as $r) {
    $saldo = $saldo + ((int)$r['lap_masuk'] - (int)$r['lap_keluar']);
    $data[] = array(
      'Tanggal' => (string)$r['lap_tanggal'],
      'Keterangan' => (string)$r['lap_keterangan'],
      'Member' => (string)($r['mem_nama'] ?? ''),
      'Pemasukan' => (int)$r['lap_masuk'],
      'Pengeluaran' => (int)$r['lap_keluar'],
      'Saldo' => (int)$saldo,
    );
  }
}

if (!defined('EPI_LIB_ONLY')) {
  if ($format === 'csv') {
    $fname = 'lapkeuangan_'.(string)$tahun.'-'.(string)$bulan.'.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$fname.'"');
    echo epi_build_csv_string($data); exit;
  } else {
    $fname = 'lapkeuangan_'.(string)$tahun.'-'.(string)$bulan.'.xlsx';
    $file = epi_build_xlsx_file($data, 'Laporan');
    if ($file === false) {
      $fname = 'lapkeuangan_'.(string)$tahun.'-'.(string)$bulan.'.csv';
      header('Content-Type: text/csv; charset=UTF-8');
      header('Content-Disposition: attachment; filename="'.$fname.'"');
      echo epi_build_csv_string($data); exit;
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$fname.'"');
    header('Content-Length: '.filesize($file));
    readfile($file); @unlink($file); exit;
  }
}
