<?php 
// Fallback template untuk event 'aksesgratis' (jika template khusus belum diset, gunakan template 'order')
if (isset($event) && $event === 'aksesgratis') {
  $audiences = array('member','sponsor','admin');
  foreach ($audiences as $aud) {
    $judulKeyCur = 'judul_'.$event.'_'.$aud; $judulKeyFb = 'judul_order_'.$aud;
    $isiKeyCur   = 'isi_'.$event.'_'.$aud;   $isiKeyFb   = 'isi_order_'.$aud;
    $waKeyCur    = 'wa_'.$event.'_'.$aud;    $waKeyFb    = 'wa_order_'.$aud;
    if ((!isset($settings[$judulKeyCur]) || empty($settings[$judulKeyCur])) && isset($settings[$judulKeyFb])) { $settings[$judulKeyCur] = $settings[$judulKeyFb]; }
    if ((!isset($settings[$isiKeyCur]) || empty($settings[$isiKeyCur])) && isset($settings[$isiKeyFb])) { $settings[$isiKeyCur] = $settings[$isiKeyFb]; }
    if ((!isset($settings[$waKeyCur]) || empty($settings[$waKeyCur])) && isset($settings[$waKeyFb])) { $settings[$waKeyCur] = $settings[$waKeyFb]; }
  }
  // Fallback untuk WAFUCB channel
  if ((!isset($settings['wafucb_'.$event]) || empty($settings['wafucb_'.$event])) && isset($settings['wafucb_order'])) {
    $settings['wafucb_'.$event] = $settings['wafucb_order'];
  }
  if ((!isset($settings['wafucb_val_'.$event]) || $settings['wafucb_val_'.$event] === '') && isset($settings['wafucb_val_order'])) {
    $settings['wafucb_val_'.$event] = $settings['wafucb_val_order'];
  }
  // Fallback untuk Autoresponder Form
  if ((!isset($settings['form_action_'.$event]) || empty($settings['form_action_'.$event])) && isset($settings['form_action_order'])) {
    $settings['form_action_'.$event] = $settings['form_action_order'];
  }
  for ($i=1; $i<=10; $i++) {
    $ffCur = 'form_field_'.$event.$i; $ffFb = 'form_field_order'.$i;
    $fvCur = 'form_value_'.$event.$i; $fvFb = 'form_value_order'.$i;
    if ((!isset($settings[$ffCur]) || empty($settings[$ffCur])) && isset($settings[$ffFb])) { $settings[$ffCur] = $settings[$ffFb]; }
    if ((!isset($settings[$fvCur]) || empty($settings[$fvCur])) && isset($settings[$fvFb])) { $settings[$fvCur] = $settings[$fvFb]; }
  }
}

$data = db_row("SELECT * FROM `sa_member` 
LEFT JOIN `sa_sponsor` ON `sa_sponsor`.`sp_mem_id` = `sa_member`.`mem_id` 
WHERE `mem_id`=".$iduser);
$datamember = extractdata($data);
$datamember = is_array($datamember) ? $datamember : array();
$datamember['nama'] = isset($datamember['nama']) ? (string)$datamember['nama'] : '';
$datamember['email'] = isset($datamember['email']) ? (string)$datamember['email'] : '';
$datamember['whatsapp'] = isset($datamember['whatsapp']) ? (string)$datamember['whatsapp'] : '';
$datamember['kodeaff'] = isset($datamember['kodeaff']) ? (string)$datamember['kodeaff'] : '';
if (!empty($datamember['kodeaff'])) { $datamember['kodeaff'] = $weburl.$datamember['kodeaff']; }
$dataadmin = db_row("SELECT * FROM `sa_member` WHERE `mem_id`=1");
$dataadmin = extractdata($dataadmin);
$dataadmin = is_array($dataadmin) ? $dataadmin : array();
$dataadmin['nama'] = isset($dataadmin['nama']) ? (string)$dataadmin['nama'] : '';
$dataadmin['email'] = isset($dataadmin['email']) ? (string)$dataadmin['email'] : '';
$dataadmin['whatsapp'] = isset($dataadmin['whatsapp']) ? (string)$dataadmin['whatsapp'] : '';
$dataadmin['kodeaff'] = isset($dataadmin['kodeaff']) ? (string)$dataadmin['kodeaff'] : '';

if (isset($data['sp_sponsor_id']) && !empty($data['sp_sponsor_id']) && is_numeric($data['sp_sponsor_id'])) {    
  $data = db_row("SELECT * FROM `sa_member` WHERE `mem_id`=".$data['sp_sponsor_id']);
  if (isset($data['mem_id'])) {
    $datasponsor = extractdata($data);
  } else {
    $datasponsor = array();
  }
  $datasponsor = is_array($datasponsor) ? $datasponsor : array();
  $datasponsor['nama'] = isset($datasponsor['nama']) ? (string)$datasponsor['nama'] : '';
  $datasponsor['email'] = isset($datasponsor['email']) ? (string)$datasponsor['email'] : '';
  $datasponsor['whatsapp'] = isset($datasponsor['whatsapp']) ? (string)$datasponsor['whatsapp'] : '';
  $datasponsor['kodeaff'] = isset($datasponsor['kodeaff']) ? (string)$datasponsor['kodeaff'] : '';
  if (!empty($datasponsor['kodeaff'])) { $datasponsor['kodeaff'] = $weburl.$datasponsor['kodeaff']; }

  # Handle Password dulup
  if (isset($datalain['newpass']) && $datalain['newpass'] != '') {
    $settings = str_replace('[member_password]',$datalain['newpass'],$settings);
    $datamember['password'] = $datalain['newpass'];
  }

  # Handle Data Default
  $arrfield = array('nama','email','whatsapp','kodeaff');
  foreach ($arrfield as $arrfield) {      
    $valMem = isset($datamember[$arrfield]) ? (string)$datamember[$arrfield] : '';
    $valSp  = isset($datasponsor[$arrfield]) ? (string)$datasponsor[$arrfield] : '';
    $settings = str_replace('[member_'.$arrfield.']',$valMem,$settings);
    $settings = str_replace('[sponsor_'.$arrfield.']',$valSp,$settings);
  }

  # Handle data lain
  $form = db_select("SELECT * FROM `sa_form` WHERE `ff_field` NOT IN ('nama','email','whatsapp','kodeaff','password')");

  foreach ($form as $form) {
    $valmember = isset($datamember[$form['ff_field']]) ? (string)$datamember[$form['ff_field']] : '';
    $valsponsor = isset($datasponsor[$form['ff_field']]) ? (string)$datasponsor[$form['ff_field']] : '';
    $settings = str_replace('[member_'.$form['ff_field'].']',$valmember,$settings);
    $settings = str_replace('[sponsor_'.$form['ff_field'].']',$valsponsor,$settings);
  }

  # Handle data tambahan lain
  if (isset($datalain) && is_array($datalain) && count($datalain) > 0) {
    foreach ($datalain as $key => $value) {
      $settings = str_replace('['.$key.']',$value,$settings);
    }
  }

  # Kirim Email
  if (isset($settings['judul_'.$event.'_member']) && !empty($settings['judul_'.$event.'_member'])) {
    if (isset($datamember['email'])) {      
      smtpmailer($datamember['email'],$settings['judul_'.$event.'_member'],$settings['isi_'.$event.'_member']);
    }
  }

  if (isset($settings['judul_'.$event.'_sponsor']) && !empty($settings['judul_'.$event.'_sponsor'])) {
    if (isset($datasponsor['email'])) {
      smtpmailer($datasponsor['email'],$settings['judul_'.$event.'_sponsor'],$settings['isi_'.$event.'_sponsor']);
    }
  }

  if (isset($settings['judul_'.$event.'_admin']) && !empty($settings['judul_'.$event.'_admin'])) {    
    if (isset($dataadmin['email'])) {
      smtpmailer($dataadmin['email'],$settings['judul_'.$event.'_admin'],$settings['isi_'.$event.'_admin']);
    }
  }

  # Kirim WhatsApp
  
  if (!empty($datamember['whatsapp']) && isset($settings['wa_'.$event.'_member']) && !empty($settings['wa_'.$event.'_member'])) {
    kirimwa($datamember['whatsapp'],$settings['wa_'.$event.'_member']);
  }

  if (!empty($datasponsor['whatsapp']) && isset($settings['wa_'.$event.'_sponsor']) && !empty($settings['wa_'.$event.'_sponsor'])) {
    kirimwa($datasponsor['whatsapp'],$settings['wa_'.$event.'_sponsor']);
  }  

  if (!empty($dataadmin['whatsapp']) && isset($settings['wa_'.$event.'_admin']) && !empty($settings['wa_'.$event.'_admin'])) {
    kirimwa($dataadmin['whatsapp'],$settings['wa_'.$event.'_admin']);
  }

  # Kirim data ke WAFUCB

  if (isset($settings['wafucb_'.$event]) && is_numeric($settings['wafucb_'.$event])) {
    if (isset($settings['wafucb_val_'.$event]) && $settings['wafucb_val_'.$event] == 1) {
      $validate = 1;
    } else {
      $validate = 0;
    }

    $nearray = array_map(function($key) {
        return 'sp' . $key;
    }, array_keys($datasponsor));

    $newsponsor = array_combine($nearray, array_values($datasponsor));

    $kirimdata = array_merge($newsponsor, $datamember);

    if (isset($datalain) && is_array($datalain) && count($datalain) > 0) {
      $kirimdata = array_merge($datalain,$kirimdata);
    }

    $data = array(
      'wafucb_key' => $settings['wafucb_key'],
      'wafucb_id' => $settings['wafucb_id'],
      'chan_id' => $settings['wafucb_'.$event], 
      'whatsapp' => $datamember['whatsapp'],
      'validate' => $validate,
      'data' => $kirimdata
    );
    
    $postfield = json_encode($data);
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://wafucb.my.id/api',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => $postfield
    ));

    $return = curl_exec($curl);
    curl_close($curl);
  }

  # Kirim data ke Autoresponder 
  /* 
  if (isset($settings['form_action_'. $event]) && !empty($settings['form_action_'. $event])) {
    $form = '<form id="myForm" action="'.$settings['form_action_'. $event].'" method="post">';
    for ($i=1; $i <= 10 ; $i++) { 
      if (isset($settings['form_field_'. $event.$i]) && !empty($settings['form_field_'. $event.$i])) {
        $form .= '<input type="hidden" name="'.$settings['form_field_'. $event.$i].'" value="'.$settings['form_value_'. $event.$i].'">';
      }
    }
    
    $form .= '</form>
    <script>
    document.addEventListener(\'DOMContentLoaded\', function () {
        // Auto-submit form when the page is loaded
        document.getElementById(\'myForm\').submit();
    });
    </script>
    ';
    
    echo $form;
  }
  */
  if (isset($settings['form_action_'. $event]) && !empty($settings['form_action_'. $event])) {
  	for ($i=1; $i <= 10 ; $i++) { 
      if (isset($settings['form_field_'. $event.$i]) && !empty($settings['form_field_'. $event.$i])) {
        $post[$settings['form_field_'. $event.$i]] = $settings['form_value_'. $event.$i];
      }
    }

    if (isset($post) && count($post) > 0) {
      postData($settings['form_action_'. $event], $post);
    }
  }
} else {
  // Tidak ada sponsor, tetap kirim notifikasi ke member & admin
  // Handle Password baru (jika ada)
  if (isset($datalain['newpass']) && $datalain['newpass'] != '') {
    $settings = str_replace('[member_password]',$datalain['newpass'],$settings);
    $datamember['password'] = $datalain['newpass'];
  }

  // Replace shortcode default untuk member & kosongkan sponsor
  $arrfield = array('nama','email','whatsapp','kodeaff');
  foreach ($arrfield as $arrfield) {      
    $valMem = isset($datamember[$arrfield]) ? (string)$datamember[$arrfield] : '';
    $settings = str_replace('[member_'.$arrfield.']',$valMem,$settings);
    $settings = str_replace('[sponsor_'.$arrfield.']','',$settings);
  }

  // Replace shortcode form-field tambahan
  $form = db_select("SELECT * FROM `sa_form` WHERE `ff_field` NOT IN ('nama','email','whatsapp','kodeaff','password')");
  foreach ($form as $form) {
    $valmember = isset($datamember[$form['ff_field']]) ? (string)$datamember[$form['ff_field']] : '';
    $settings = str_replace('[member_'.$form['ff_field'].']',$valmember,$settings);
    $settings = str_replace('[sponsor_'.$form['ff_field'].']','',$settings);
  }

  // Handle data tambahan dari $datalain
  if (isset($datalain) && is_array($datalain) && count($datalain) > 0) {
    foreach ($datalain as $key => $value) { $settings = str_replace('['.$key.']',$value,$settings); }
  }

  // Kirim Email ke member & admin
  if (isset($settings['judul_'.$event.'_member']) && !empty($settings['judul_'.$event.'_member'])) {
    if (isset($datamember['email'])) { smtpmailer($datamember['email'],$settings['judul_'.$event.'_member'],$settings['isi_'.$event.'_member']); }
  }
  if (isset($settings['judul_'.$event.'_admin']) && !empty($settings['judul_'.$event.'_admin'])) {    
    if (isset($dataadmin['email'])) { smtpmailer($dataadmin['email'],$settings['judul_'.$event.'_admin'],$settings['isi_'.$event.'_admin']); }
  }

  // Kirim WhatsApp ke member & admin
  if (!empty($datamember['whatsapp']) && isset($settings['wa_'.$event.'_member']) && !empty($settings['wa_'.$event.'_member'])) {
    kirimwa($datamember['whatsapp'],$settings['wa_'.$event.'_member']);
  }
  if (!empty($dataadmin['whatsapp']) && isset($settings['wa_'.$event.'_admin']) && !empty($settings['wa_'.$event.'_admin'])) {
    kirimwa($dataadmin['whatsapp'],$settings['wa_'.$event.'_admin']);
  }

  // Kirim data ke Autoresponder (opsional)
  if (isset($settings['form_action_'. $event]) && !empty($settings['form_action_'. $event])) {
    for ($i=1; $i <= 10 ; $i++) { 
      if (isset($settings['form_field_'. $event.$i]) && !empty($settings['form_field_'. $event.$i])) {
        $post[$settings['form_field_'. $event.$i]] = $settings['form_value_'. $event.$i];
      }
    }
    if (isset($post) && count($post) > 0) { postData($settings['form_action_'. $event], $post); }
  }
}
?>
