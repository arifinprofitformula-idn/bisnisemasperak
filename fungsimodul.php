<?php
function modul_klienbaru($judulmodul='Klien Terbaru',$jmlmember=5,$format='[nama] <small>([tgldaftar])</small>') {  
  global $settings;
  if (isset($settings[$judulmodul])) {
    $modsettings = json_decode($settings[$judulmodul],TRUE);
    $modsettings = $modsettings['data'];
    if (isset($modsettings['klienbaruTitle']) && !empty($modsettings['klienbaruTitle'])) { 
      $judulmodul = $modsettings['klienbaruTitle']; 
    } else {
      $judulmodul = 'Klien Terbaru';
    }
    if (isset($modsettings['klien_jumlah']) && !empty($modsettings['klien_jumlah'])) { $jmlmember = $modsettings['klien_jumlah']; }
    if (isset($modsettings['klien_format']) && !empty($modsettings['klien_format'])) { $format = $modsettings['klien_format']; }
  } elseif (substr($judulmodul, 0,7) == 'module-') {
    $judulmodul = 'Klien Terbaru';
  }
  
  $show = $list = '';
  if ($iduser = is_login()) {
    $data = db_select("SELECT * FROM `sa_member` 
        LEFT JOIN `sa_sponsor` ON `sa_sponsor`.`sp_mem_id` = `sa_member`.`mem_id`
        WHERE `sa_sponsor`.`sp_sponsor_id`=".$iduser." 
        ORDER BY `mem_tgldaftar` DESC LIMIT 0,".$jmlmember);
    if (count($data) > 0) {
      $list .= '<ol>';
      foreach ($data as $data) {
        $exdata = extractdata($data);       
        $list .= '<li>'.convertdata($format,$exdata).'</li>';
      }
      $list .= '</ol>';
    } else {
      $list .= '<em>Belum ada Klien</em>';
    }
  }
  $show = '
    <div class="card mb-3">
      <div class="card-header">
        '.$judulmodul.'
      </div>
      <div class="card-body">
        '.$list.'
      </div>
    </div>';

  $show = apply_filter('mod_klienbaru',$show);
  return $show;
}

function modul_affiliasi($judulmodul='Affiliasi') {
  global $weburl,$settings,$datamember;
  $show = '';
  if (isset($settings[$judulmodul])) {
    $modsettings = json_decode($settings[$judulmodul],TRUE);
    $modsettings = $modsettings['data'];
    if (isset($modsettings['affiliasiTitle']) && !empty($modsettings['affiliasiTitle'])) { 
      $judulmodul = $modsettings['affiliasiTitle']; 
    } else {
      $judulmodul = 'Affiliasi';
    }
  } elseif (substr($judulmodul, 0,7) == 'module-') {
    $judulmodul = 'Affiliasi';
  }

  $jmldl = db_select("SELECT `sa_member`.`mem_status` AS `status`,count(*) AS `jmldl` FROM `sa_sponsor` 
          LEFT JOIN `sa_member` ON `sa_sponsor`.`sp_mem_id` = `sa_member`.`mem_id`
          WHERE `sp_sponsor_id`=".$datamember['mem_id']."
          GROUP BY `mem_status`");
  // Validasi sp_sponsor_id sebelum query
  if (isset($datamember['sp_sponsor_id']) && !empty($datamember['sp_sponsor_id']) && is_numeric($datamember['sp_sponsor_id'])) {
    $datasponsor = db_row("SELECT * FROM `sa_member` WHERE `mem_id`=".$datamember['sp_sponsor_id']);
  } else {
    $datasponsor = false;
  }

  $jumlah = array(0,0,0);
  if (count($jmldl) > 0) {
    foreach ($jmldl as $jmldl) {
      $jumlah[$jmldl['status']] = $jmldl['jmldl'];
    }
  }

  // Ringkasan komisi sponsor (untuk ditampilkan di kartu Affiliasi)
  // Komisi Siap Cair = Total masuk - Total keluar; Sudah Ditransfer = Total keluar
  $komisi = db_row("SELECT COALESCE(SUM(`lap_masuk`),0) AS `masuk`, COALESCE(SUM(`lap_keluar`),0) AS `keluar` 
                    FROM `sa_laporan` 
                    WHERE `lap_code`=2 AND `lap_idsponsor`=".(int)$datamember['mem_id']);
  $komisiMasuk  = isset($komisi['masuk']) ? (int)$komisi['masuk'] : 0;
  $komisiKeluar = isset($komisi['keluar']) ? (int)$komisi['keluar'] : 0;
  $komisiSiapCair = max(0, $komisiMasuk - $komisiKeluar);

  $show = '
    <div class="card mb-3">
      <div class="card-header">
        '.$judulmodul.'
      </div>
      <div class="card-body"> 
        <div class="row mt-1">
          <div class="col-4">URL Affiliasi</div>
          <div class="col-8"><a href="'.$weburl.$datamember['mem_kodeaff'].'">'.$weburl.$datamember['mem_kodeaff'].'</a>
            &nbsp;&nbsp;<a onclick="copyToClipboard(\''.$weburl.$datamember['mem_kodeaff'].'\')" style="text-decoration:none;cursor: pointer;" 
              title="Copy to Clipboard"><i class="fa-regular fa-copy"></i></a>
          </div>
        </div>
        <div class="row mt-1">
          <div class="col-4">Free Member</div>
          <div class="col-8"><a href="dashboard/klien?status=1">'.number_format($jumlah[1]).'</a></div>
        </div>
        <div class="row mt-1">
          <div class="col-4">Premium Member</div>
          <div class="col-8"><a href="dashboard/klien?status=2">'.number_format($jumlah[2]).'</a></div>
        </div>
        <div class="row mt-1">
          <div class="col-4">Total Member</div>
          <div class="col-8"><a href="dashboard/klien">'.number_format($jumlah[1] + $jumlah[2]).'</a></div>
        </div>
        <div class="row mt-1">
          <div class="col-4">Komisi Siap Cair</div>
          <div class="col-8"><strong>Rp '.number_format($komisiSiapCair).' (Belum Ditransfer)</strong></div>
        </div>
        <div class="row mt-1">
          <div class="col-4">Total Komisi</div>
          <div class="col-8">Rp '.number_format($komisiKeluar).' (Sudah Ditransfer)</div>
        </div>
        <div class="row mt-1">
          <div class="col-4">Sponsor Anda</div>
          <div class="col-8">';
      if (isset($datasponsor['mem_nama'])) {
        $show .= $datasponsor['mem_nama'].'<br/>';
        $show .= '<a href="https://wa.me/'.formatwa($datasponsor['mem_whatsapp']).'" target="_blank">'.$datasponsor['mem_whatsapp'].'</a><br/>';
        $show .= $datasponsor['mem_email'];
      } else {
        $show .= 'Tidak ada data sponsor';
      }
      $show .= '
          </div>
        </div>
      </div>
    </div>
  ';
  $show = apply_filter('mod_affiliasi',$show);
  return $show;
}

function modul_grafikvisitor($judulmodul = 'Pengunjung & Pendaftar 30 hari terakhir') {
    global $datamember, $settings;

    // --- Pengaturan Judul Modul ---
    if (isset($settings[$judulmodul])) {
        $modsettings = json_decode($settings[$judulmodul], TRUE);
        $modsettings = $modsettings['data'];
        if (isset($modsettings['grafikvisitorTitle']) && !empty($modsettings['grafikvisitorTitle'])) {
            $judulmodul = $modsettings['grafikvisitorTitle'];
        } else {
            $judulmodul = 'Pengunjung & Pendaftar 30 hari terakhir';
        }
    } elseif (substr($judulmodul, 0, 7) == 'module-') {
        $judulmodul = 'Pengunjung & Pendaftar 30 hari terakhir';
    }

    // --- Pastikan fungsi db_select() tersedia ---
    if (!function_exists('db_select')) {
        // Ini adalah placeholder. Anda harus menggantinya dengan implementasi db_select() Anda yang sebenarnya.
        // Asumsi: db_select() mengembalikan array dari baris hasil atau false jika gagal.
        function db_select($sql) {
            error_log("ERROR: Fungsi db_select() tidak ditemukan. Kueri berikut gagal: " . $sql);
            return false;
        }
    }

    // --- Ambil Data Pendaftar dari sa_member ---
    // Mengambil jumlah pendaftar per tanggal untuk sponsor yang sedang login
    $stats_raw = db_select("SELECT DATE(`mem_tgldaftar`) as `tanggal`,
        SUM(CASE WHEN mem_status = 1 THEN 1 ELSE 0 END) as `pendaftar_status_1`,
        SUM(CASE WHEN mem_status = 2 THEN 1 ELSE 0 END) as `pendaftar_status_2`
        FROM `sa_member`
        LEFT JOIN `sa_sponsor` ON `sa_sponsor`.`sp_mem_id` = `sa_member`.`mem_id`
        WHERE `sa_sponsor`.`sp_sponsor_id` = " . (int)$datamember['mem_id'] . "
        GROUP BY `tanggal`
        ORDER BY `tanggal` ASC;");

    $registrant_data = [];
    if ($stats_raw && is_array($stats_raw)) { // Pastikan $stats_raw adalah array
        foreach ($stats_raw as $row) {
            // Tanggal dari DB sudah YYYY-MM-DD
            $registrant_data[$row['tanggal']] = (int)$row['pendaftar_status_1'] + (int)$row['pendaftar_status_2'];
        }
    }

    // --- Ambil Data Pengunjung dari visitor_data() ---
    // visitor_data() mengembalikan tanggal dalam format YYYYMMDD
    $visitor_raw = visitor_data($datamember['mem_id']);
    $visitor_data_formatted = [];
    foreach ($visitor_raw as $date_ymd => $count) {
        // Konversi tanggal dari YYYYMMDD ke YYYY-MM-DD untuk konsistensi
        $formatted_date = date('Y-m-d', strtotime($date_ymd));
        $visitor_data_formatted[$formatted_date] = (int)$count;
    }

    // --- Gabungkan Data Pengunjung dan Pendaftar untuk 30 Hari Terakhir ---
    $chart_data_rows = [];
    // Loop untuk 30 hari terakhir (dari 29 hari yang lalu hingga hari ini)
    for ($i = 29; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        
        // Ambil jumlah pengunjung, default 0 jika tidak ada data
        $visitor_count = $visitor_data_formatted[$date] ?? 0;
        
        // Ambil jumlah pendaftar, default 0 jika tidak ada data
        $registrant_count = $registrant_data[$date] ?? 0;

        // Tambahkan baris data ke array
        $chart_data_rows[] = "['{$date}', {$visitor_count}, {$registrant_count}]";
    }

    // Gabungkan baris data menjadi string yang dipisahkan koma untuk JavaScript
    $chart_js_data = implode(',', $chart_data_rows);

    // --- HTML dan JavaScript untuk Grafik ---
    $show = '
    <div class="card mb-3">
        <div class="card-header">
            ' . $judulmodul . '
        </div>
        <div class="card-body">
            <div id="chart_div" style="margin:30px 0 0 0; width: 100%; height: 300px"></div>
            <div id="message_box" style="display:none; padding: 10px; margin-top: 10px; border-radius: 5px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;"></div>
        </div>
    </div>
    
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load(\'current\', {\'packages\':[\'corechart\']});
        google.charts.setOnLoadCallback(drawChart);
        
        function drawChart() {
            var dataTable = new google.visualization.DataTable();
            dataTable.addColumn(\'string\', \'Tanggal\');
            dataTable.addColumn(\'number\', \'Jml Pengunjung: \');
            dataTable.addColumn(\'number\', \'Jml Pendaftar: \');
            
            dataTable.addRows([
                ' . $chart_js_data . '
            ]);
            
            var options = {
                title: \'\', // Judul grafik bisa diset di sini jika tidak di card-header
                vAxis: { title: \'Jumlah\' }, // Judul sumbu Y lebih umum
                hAxis: { title: \'Tanggal\', format: \'MMM d\' }, // Format tanggal lebih mudah dibaca
                legend: { position: \'top\' },
                chartArea:{left:70,top:10,width:\'90%\',height:\'70%\'}, // Sesuaikan lebar chartArea
                series: {
                    0: { color: \'#4285F4\' }, // Warna untuk Pengunjung (biru Google)
                    1: { color: \'#EA4335\' }  // Warna untuk Pendaftar (merah Google)
                }
            };
            
            var chart = new google.visualization.LineChart(document.getElementById(\'chart_div\'));
            chart.draw(dataTable, options);
        }

        // Fungsi untuk menampilkan pesan sementara
        function showMessage(message, type = \'success\') {
            var msgBox = document.getElementById(\'message_box\');
            msgBox.innerText = message;
            msgBox.style.display = \'block\';
            if (type === \'success\') {
                msgBox.style.backgroundColor = \'#d4edda\';
                msgBox.style.color = \'#155724\';
                msgBox.style.borderColor = \'#c3e6cb\';
            } else if (type === \'error\') {
                msgBox.style.backgroundColor = \'#f8d7da\';
                msgBox.style.color = \'#721c24\';
                msgBox.style.borderColor = \'#f5c6cb\';
            }
            // Sembunyikan pesan setelah beberapa detik
            setTimeout(function() {
                msgBox.style.display = \'none\';
            }, 3000); // Pesan akan hilang setelah 3 detik
        }

        async function copyToClipboard(text) {
            try {
                // Gunakan Clipboard API modern jika tersedia
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(text);
                } else {
                    // Fallback untuk browser lama
                    const textArea = document.createElement(\"textarea\");
                    textArea.value = text;
                    textArea.style.position = \"fixed\";
                    textArea.style.left = \"-999999px\";
                    textArea.style.top = \"-999999px\";
                    document.body.appendChild(textArea);
                    textArea.focus();
                    textArea.select();
                    document.execCommand(\"copy\");
                    textArea.remove();
                }
                
                // Tampilkan notifikasi sukses
                showCopySuccessModal();
                  
            } catch (err) {
                 console.error(\"Gagal menyalin teks: \", err);
                   // Fallback jika semua metode gagal
                   alert(\"Gagal menyalin teks. Silakan salin manual.\");
            }
        }

        function showCopySuccessModal() {
            // Buat modal jika belum ada
            if (!document.getElementById(\'copySuccessModal\')) {
                var modalHTML = `
                    <div id="copySuccessModal" class="copy-modal-overlay" style="display: none;">
                        <div class="copy-modal-content">
                            <div class="copy-modal-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="copy-modal-text">Sukses Tersalin</div>
                        </div>
                    </div>
                `;
                document.body.insertAdjacentHTML(\'beforeend\', modalHTML);
                
                // Tambahkan CSS untuk modal
                var style = document.createElement(\'style\');
                style.textContent = `
                    .copy-modal-overlay {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0, 0, 0, 0.3);
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        z-index: 9999;
                        backdrop-filter: blur(2px);
                    }
                    
                    .copy-modal-content {
                        background: linear-gradient(135deg, #ffd700, #ffed4e, #fff8dc);
                        border: 2px solid #ffd700;
                        border-radius: 12px;
                        padding: 25px 35px;
                        text-align: center;
                        box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3), 
                                    0 4px 12px rgba(0,0,0,0.15);
                        transform: scale(0.8);
                        animation: modalAppear 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
                        max-width: 320px;
                        min-width: 250px;
                    }
                    
                    .copy-modal-icon {
                        font-size: 2.5rem;
                        color: #2c2c2c;
                        margin-bottom: 12px;
                        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
                    }
                    
                    .copy-modal-text {
                        font-size: 1.1rem;
                        font-weight: 600;
                        color: #2c2c2c;
                        text-shadow: 0 1px 2px rgba(255, 255, 255, 0.4);
                        letter-spacing: 0.3px;
                    }
                    
                    @keyframes modalAppear {
                        0% {
                            transform: scale(0.7) translateY(-20px);
                            opacity: 0;
                        }
                        100% {
                            transform: scale(1) translateY(0);
                            opacity: 1;
                        }
                    }
                    
                    @keyframes modalDisappear {
                        0% {
                            transform: scale(1) translateY(0);
                            opacity: 1;
                        }
                        100% {
                            transform: scale(0.7) translateY(-20px);
                            opacity: 0;
                        }
                    }
                    
                    .copy-modal-content.disappearing {
                        animation: modalDisappear 0.25s ease-in forwards;
                    }
                `;
                document.head.appendChild(style);
            }
            
            // Tampilkan modal
            var modal = document.getElementById(\'copySuccessModal\');
            var content = modal.querySelector(\'.copy-modal-content\');
            
            // Reset animasi jika modal sudah tampil
            content.classList.remove(\'disappearing\');
            modal.style.display = \'flex\';
            
            // Sembunyikan modal setelah 1.8 detik
            setTimeout(function() {
                content.classList.add(\'disappearing\');
                setTimeout(function() {
                    modal.style.display = \'none\';
                }, 250);
            }, 1800);
        }
    </script>
    ';
    $show = apply_filter('mod_grafikvisitor', $show);
    return $show;
}

function modul_landingpage($judulmodul='Landing Page') {
  global $weburl,$datamember,$settings;
  if (isset($settings[$judulmodul])) {
    $modsettings = json_decode($settings[$judulmodul],TRUE);    
    $modsettings = $modsettings['data'];

    if (isset($modsettings['landingpageTitle']) && !empty($modsettings['landingpageTitle'])) { 
      $judulmodul = $modsettings['landingpageTitle']; 
    } else {
      $judulmodul = 'Landing Page';
    }
  } elseif (substr($judulmodul, 0,7) == 'module-') {
    $judulmodul = 'Landing Page';
  }

  $page = db_select("SELECT * FROM `sa_page` WHERE `pro_status`=1 ORDER BY `page_judul`");
  $show = '';
  if (count($page) > 0) {
    $show .= '
  <div class="card mb-3">
    <div class="card-header">
      '.$judulmodul.'
    </div>
    <div class="card-body">
      <ol>
        <li>
          <a href="'.$weburl.$datamember['mem_kodeaff'].'/register" target="_blank" title="Visit Link">Page Registrasi</a>
          &nbsp;&nbsp;<a onclick="copyToClipboard(\''.$weburl.$datamember['mem_kodeaff'].'/register\')" 
          style="text-decoration:none;cursor: pointer;" title="Copy to Clipboard"> 
          <i class="fa-regular fa-copy"></i></a>        
        </li>
    ';
    foreach ($page as $page) {
      $show .= '<li>
        <a href="'.$weburl.$datamember['mem_kodeaff'].'/'.$page['page_url'].'" target="_blank" title="Visit Link">'.$page['page_judul'].'</a>
        &nbsp;&nbsp;<a onclick="copyToClipboard(\''.$weburl.$datamember['mem_kodeaff'].'/'.$page['page_url'].'\')" 
        style="text-decoration:none;cursor: pointer;" title="Copy to Clipboard"> 
        <i class="fa-regular fa-copy"></i></a>        
      </li>';
    }
    $show .= '
      </ol>
    </div>
  </div>     
    ';
  }

  $show = apply_filter('mod_landingpage',$show);
  return $show;
}

function modul_informasi($judulmodul='Informasi') {
  global $weburl, $settings, $datamember;
  $show = '';
  $settings = ubahshortcode($settings,$datamember['mem_id']);

  if (isset($settings[$judulmodul])) {
    $modsettings = json_decode($settings[$judulmodul],TRUE);
    if (isset($modsettings['data']['informasiTitle']) && !empty($modsettings['data']['informasiTitle'])) { 
      $judulmodul = $modsettings['data']['informasiTitle']; 
    } else {
      $judulmodul = 'Informasi';
    }
  } elseif (substr($judulmodul, 0,7) == 'module-') {
    $judulmodul = 'Informasi';
  }

  if (isset($settings['informasi']) && !empty($settings['informasi']) && $datamember['mem_status'] == 1) {
    $info = $settings['informasi'];
  } elseif (isset($settings['infopremium']) && !empty($settings['infopremium']) && $datamember['mem_status'] == 2) {
    $info = $settings['infopremium'];
  }

  if (isset($info)) {
    $show = '
      <div class="card mb-3">
        <div class="card-header">
          '.$judulmodul.'
        </div>
        <div class="card-body fr-view"> 
          '.$info.'
        </div>
      </div>
    ';
  }

  $show = apply_filter('mod_info',$show);
  return $show;
}

function modul_akses($judulmodul='Akses Produk') {
  global $weburl, $settings, $datamember;
  $show = $list = '';
  
  if (isset($settings[$judulmodul])) {
    $modsettings = json_decode($settings[$judulmodul],TRUE);
    $modsettings = $modsettings['data'];
    if (isset($modsettings['aksesTitle']) && !empty($modsettings['aksesTitle'])) { 
      $judulmodul = $modsettings['aksesTitle'];
    } else {
      $judulmodul = 'Akses Produk';
    }
  } elseif (substr($judulmodul, 0,7) == 'module-') {
    $judulmodul = 'Akses Produk';
  }
  
  $order = db_select("SELECT * FROM `sa_order` LEFT JOIN `sa_page` ON `sa_page`.`page_id` = `sa_order`.`order_idproduk` 
    WHERE `sa_page`.`pro_harga` > 0 AND `sa_order`.`order_status`=1 AND `order_idmember`=".$datamember['mem_id']);
  if (count($order) > 0) {
    foreach ($order as $order) {
      $list .= '
      <tr>
        <td>'.$order['page_judul'].'</td>
        <td class="text-end"><a href="'.$weburl.'dashboard/akses/'.$order['page_url'].'" class="btn btn-sm btn-success" target="_blank">Akses</a></td>
      </tr>';
    }
  }

  $freeproduk = db_select("SELECT * FROM `sa_page` WHERE `pro_harga` = 0");
  if (count($freeproduk) > 0) {
    foreach ($freeproduk as $freeproduk) {
      $list .= '
      <tr>
        <td>'.$freeproduk['page_judul'].'</td>
        <td class="text-end"><a href="'.$weburl.'dashboard/akses/'.$freeproduk['page_url'].'" class="btn btn-sm btn-success" target="_blank">Akses</a></td>
      </tr>';
    }
  }

  $show = '
    <div class="card mb-3">
      <div class="card-header">
        '.$judulmodul.'
      </div>
      <div class="card-body fr-view"> 
        <table class="table table-stripped">'.$list.'</table>
      </div>
    </div>
  ';

  $show = apply_filter('mod_aksesproduk',$show);
  return $show;
}

function modul_pesanan($judulmodul="Pesanan Anda") {
  global $weburl, $settings, $datamember;
  $show = $list = '';
  
  if (isset($settings[$judulmodul])) {
    $modsettings = json_decode($settings[$judulmodul],TRUE);
    $modsettings = $modsettings['data'];
    if (isset($modsettings['pesananTitle']) && !empty($modsettings['pesananTitle'])) { $judulmodul = $modsettings['pesananTitle']; }
  } elseif (substr($judulmodul, 0,7) == 'module-') {
    $judulmodul = 'Pesanan Anda';
  }

  $order = db_select("SELECT * FROM `sa_order` LEFT JOIN `sa_page` ON `sa_page`.`page_id` = `sa_order`.`order_idproduk` 
    WHERE `sa_page`.`pro_harga` > 0 AND `sa_order`.`order_status`=0 AND `order_idmember`=".$datamember['mem_id']." LIMIT 0,10");
  if (count($order) > 0) {
    foreach ($order as $order) {
      $list .= '
      <tr>
        <td>'.$order['page_judul'].'</td>
        <td class="text-end"><a href="'.$weburl.'invoice/'.$order['order_id'].'" class="btn btn-sm btn-success" target="_blank">Invoice</a></td>
      </tr>';
    }
  }
  $show = '
    <div class="card mb-3">
      <div class="card-header">
        '.$judulmodul.'
      </div>
      <div class="card-body fr-view"> 
        <table class="table table-stripped">'.$list.'</table>
      </div>
    </div>
  ';

  $show = apply_filter('mod_aksesproduk',$show);
  return $show;
}

function modul_penghasilan($judulmodul="Penghasilan") {
  global $weburl, $settings, $datamember;  
  $show = $list = '';
  if (isset($datamember['mem_id'])) {
    if (isset($settings[$judulmodul])) {
      $modsettings = json_decode($settings[$judulmodul],TRUE);
      $modsettings = $modsettings['data'];
      if (isset($modsettings['penghasilanTitle']) && !empty($modsettings['penghasilanTitle'])) { $judulmodul = $modsettings['penghasilanTitle']; }
    } elseif (substr($judulmodul, 0,7) == 'module-') {
      $judulmodul = 'Penghasilan';
    }
    
    $komisi = db_row("SELECT SUM(`lap_masuk`) AS `masuk`, SUM(`lap_keluar`) AS `keluar` FROM `sa_laporan` WHERE `lap_code`=2 AND `lap_idsponsor`=".$datamember['mem_id']);
    $komisiblnini = db_var("SELECT SUM(`lap_masuk`) AS `komisi` FROM `sa_laporan` WHERE YEAR(`lap_tanggal`) = YEAR(CURDATE())
    AND MONTH(`lap_tanggal`) = MONTH(CURDATE()) AND `lap_code`=2 AND `lap_idsponsor`=".$datamember['mem_id']);
    $masuk = $komisi['masuk']??=0;
    $keluar = $komisi['keluar']??=0;
    $show = '
      <div class="card mb-3">
        <div class="card-header">
          '.$judulmodul.'
        </div>
        <div class="card-body fr-view"> 
          <table class="table table-stripped">          
            <tr><td>Total Komisi</td><td class="text-end">'.number_format($masuk).'</td></tr>
            <tr><td>Komisi Dibayar</td><td class="text-end">'.number_format($keluar).'</td></tr>
            <tr><td>Komisi Tertahan</td><td class="text-end">'.number_format($masuk-$keluar).'</td></tr>          
            <tr><td>Komisi Bulan ini</td><td class="text-end">'.number_format($komisiblnini).'</td></tr>
          </table>
        </div>
      </div>
    ';
  }

  return $show;
}

function modul_leaderboard($judulmodul="Leaderboard") {
  global $weburl, $settings, $datamember;
  $show = $list = '';
  $leader_type = 1;
  $leader_format = '[nama] - [jumlah]';
  $leader_jumlah = 10;
  
  if (isset($settings[$judulmodul])) {
    $modsettings = json_decode($settings[$judulmodul],TRUE);
    $modsettings = $modsettings['data'];
    if (isset($modsettings['leaderboardTitle']) && !empty($modsettings['leaderboardTitle'])) { $judulmodul = $modsettings['leaderboardTitle']; }
    if (isset($modsettings['leader_type']) && !empty($modsettings['leader_type'])) { $leader_type = $modsettings['leader_type']; }
    if (isset($modsettings['leader_jumlah']) && !empty($modsettings['leader_jumlah'])) { $leader_jumlah = $modsettings['leader_jumlah']; }
    if (isset($modsettings['leader_format']) && !empty($modsettings['leader_format'])) { $leader_format = $modsettings['leader_format']; }    
  } elseif (substr($judulmodul, 0,7) == 'module-') {
    $judulmodul = 'Leaderboard';
  }

  if (isset($modsettings['leader_start']) && !empty($modsettings['leader_start'])) {
    $start = $modsettings['leader_start'];
    if (isset($modsettings['leader_end']) && !empty($modsettings['leader_end'])) {
      $end = $modsettings['leader_end'];
    } else {
      $end = date('Y-m-d');
    }
  } else {
    $between = "";
  }

  switch ($leader_type) {    
    case '2':
      // Komisi Terbanyak
      if (isset($start)) {
        $between = "AND `lap_tanggal` BETWEEN '".$start."' AND '".$end."'";
      }

      $datalist = db_select("SELECT *, SUM(`lap_masuk`) AS `jumlah` FROM `sa_laporan` 
        LEFT JOIN `sa_member` ON `sa_member`.`mem_id` = `sa_laporan`.`lap_idsponsor`
        WHERE `lap_code`=2 ".$between." GROUP BY `lap_idsponsor` HAVING `jumlah` > 0 
        ORDER BY `jumlah` DESC LIMIT 0,".$leader_jumlah);   
      break;
    case '3':
      // Rekrut Terbanyak
      if (isset($start)) {
        $between = "AND `member`.`mem_tgldaftar` BETWEEN '".$start."' AND '".$end."'";
      }
      $query = "SELECT `sponsor`.*, COUNT(`member`.`mem_id`) AS `jumlah` 
        FROM `sa_sponsor` `sp` 
        LEFT JOIN `sa_member` `sponsor` ON `sp`.`sp_sponsor_id` = `sponsor`.`mem_id`
        LEFT JOIN `sa_member` `member` ON `sp`.`sp_mem_id` = `member`.`mem_id` ".$between."
        WHERE `sp_sponsor_id` > 0 
        GROUP BY `sp`.`sp_sponsor_id` 
        HAVING `jumlah` > 0
        ORDER BY `jumlah` DESC
        LIMIT 0,".$leader_jumlah;
      $datalist = db_select($query);
      break;    
    default:
      // Omset Terbanyak
      if (isset($start)) {
        $between = "AND `lap_tanggal` BETWEEN '".$start."' AND '".$end."'";
      }

      $datalist = db_select("SELECT *, SUM(`lap_masuk`) AS `jumlah` FROM `sa_laporan` 
        LEFT JOIN `sa_member` ON `sa_member`.`mem_id` = `sa_laporan`.`lap_idsponsor`
        WHERE `lap_code`=1 ".$between." GROUP BY `lap_idsponsor` HAVING `jumlah` > 0 
        ORDER BY `jumlah` DESC LIMIT 0,".$leader_jumlah);
      break;
  }

  if (isset($datalist) && count($datalist) > 0) {
    $list = '<ol>';
    foreach ($datalist as $datalist) {
      $exdata = extractdata($datalist); 
      $showdata = convertdata($leader_format,$exdata);
      $showdata = str_replace('[jumlah]', number_format($datalist['jumlah']), $showdata);      
      $list .= '<li>'.$showdata.'</li>';
    }
    $list .= '</ol>';
  } else {
    $list = '<em>Belum ada Data</em>';
  }

  $show = '
    <div class="card mb-3">
      <div class="card-header">
        '.$judulmodul.'
      </div>
      <div class="card-body">
        '.$list.'
      </div>
    </div>
  ';

  return $show;
}

function modul_text($judulmodul="Pengumuman",$isikonten="") {
  global $settings, $datamember;
  if (isset($settings[$judulmodul])) {
    $modsettings = json_decode($settings[$judulmodul],TRUE);
    $modsettings = $modsettings['data'];
    if (isset($modsettings['textTitle']) && !empty($modsettings['textTitle'])) { $judulmodul = $modsettings['textTitle']; }
    if (isset($modsettings['text_konten']) && !empty($modsettings['text_konten'])) { $isikonten = $modsettings['text_konten']; }
  } elseif (substr($judulmodul, 0,7) == 'module-') {
    $judulmodul = 'Pengumuman';
  }

  $show = '
    <div class="card mb-3">
      <div class="card-header">
        '.$judulmodul.'
      </div>
      <div class="card-body">
        '.$isikonten.'
      </div>
    </div>
  ';

  return $show;
}
?>
