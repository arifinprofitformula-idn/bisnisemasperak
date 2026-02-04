<?php
@include_once __DIR__ . '/../config.php';
@include_once __DIR__ . '/../fungsi.php';
if (session_status() === PHP_SESSION_NONE) { @session_start(); }

function epi_auto_cancel_orders_ensure_schema() {
  $colUpd = db_row("SHOW COLUMNS FROM `sa_order` LIKE 'order_updated_at'");
  if (!is_array($colUpd) || !isset($colUpd['Field'])) {
    @db_query("ALTER TABLE `sa_order` ADD `order_updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
  }
  $colReason = db_row("SHOW COLUMNS FROM `sa_order` LIKE 'order_cancel_reason'");
  if (!is_array($colReason) || !isset($colReason['Field'])) {
    @db_query("ALTER TABLE `sa_order` ADD `order_cancel_reason` VARCHAR(255) NULL");
  }

  @db_query("UPDATE `sa_order` SET `order_updated_at`=COALESCE(`order_updated_at`,`order_tglorder`) WHERE `order_updated_at` IS NULL");

  if (!db_var("SHOW TABLES LIKE 'epi_payment_confirm'")) {
    @db_query("CREATE TABLE IF NOT EXISTS `epi_payment_confirm` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `order_id` INT NOT NULL,
      `invoice_no` VARCHAR(32) NOT NULL,
      `atas_nama` VARCHAR(100) NULL,
      `bank_code` VARCHAR(32) NULL,
      `bank_label` VARCHAR(100) NULL,
      `bank_account` VARCHAR(50) NULL,
      `bank_owner` VARCHAR(100) NULL,
      `transfer_date` DATE NULL,
      `nominal` INT NULL,
      `nominal_expected` INT NULL,
      `file_path` VARCHAR(255) NULL,
      `file_name` VARCHAR(200) NULL,
      `file_size` INT NULL,
      `file_type` VARCHAR(64) NULL,
      `status` TINYINT DEFAULT 0,
      `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
      `updated_at` DATETIME NULL,
      `created_ip` VARCHAR(64) NULL,
      `user_agent` VARCHAR(255) NULL,
      `verified_by` INT NULL,
      `verified_note` VARCHAR(255) NULL,
      INDEX `idx_order` (`order_id`),
      INDEX `idx_status` (`status`),
      INDEX `idx_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  }

  if (!db_var("SHOW TABLES LIKE 'epi_payment_confirm_log'")) {
    @db_query("CREATE TABLE IF NOT EXISTS `epi_payment_confirm_log` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `confirm_id` INT NULL,
      `order_id` INT NULL,
      `action` VARCHAR(64) NULL,
      `message` VARCHAR(255) NULL,
      `ip` VARCHAR(64) NULL,
      `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
      INDEX `idx_order` (`order_id`),
      INDEX `idx_confirm` (`confirm_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  }

  if (!db_var("SHOW TABLES LIKE 'epi_auto_cancel_log'")) {
    @db_query("CREATE TABLE IF NOT EXISTS `epi_auto_cancel_log` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `order_id` INT NOT NULL,
      `member_id` INT NOT NULL,
      `action` VARCHAR(32) NULL,
      `prev_status` INT NOT NULL,
      `new_status` INT NOT NULL,
      `confirm_id` INT NULL,
      `confirm_status` INT NULL,
      `reason` VARCHAR(255) NULL,
      `batch_id` VARCHAR(64) NULL,
      `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
      INDEX `idx_order` (`order_id`),
      INDEX `idx_member` (`member_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  }

  $colAction = db_row("SHOW COLUMNS FROM `epi_auto_cancel_log` LIKE 'action'");
  if (!is_array($colAction) || !isset($colAction['Field'])) {
    @db_query("ALTER TABLE `epi_auto_cancel_log` ADD `action` VARCHAR(32) NULL AFTER `member_id`");
  }
  $colConfirmId = db_row("SHOW COLUMNS FROM `epi_auto_cancel_log` LIKE 'confirm_id'");
  if (!is_array($colConfirmId) || !isset($colConfirmId['Field'])) {
    @db_query("ALTER TABLE `epi_auto_cancel_log` ADD `confirm_id` INT NULL AFTER `new_status`");
  }
  $colConfirmStatus = db_row("SHOW COLUMNS FROM `epi_auto_cancel_log` LIKE 'confirm_status'");
  if (!is_array($colConfirmStatus) || !isset($colConfirmStatus['Field'])) {
    @db_query("ALTER TABLE `epi_auto_cancel_log` ADD `confirm_status` INT NULL AFTER `confirm_id`");
  }
}

function epi_run_auto_cancel_orders($options = array()) {
  global $weburl;

  $now = isset($options['now']) && is_string($options['now']) && $options['now'] !== '' ? $options['now'] : date('Y-m-d H:i:s');
  $thresholdHours = isset($options['threshold_hours']) && is_numeric($options['threshold_hours']) ? (int)$options['threshold_hours'] : 48;
  $batchId = isset($options['batch_id']) && is_string($options['batch_id']) && $options['batch_id'] !== '' ? $options['batch_id'] : ('batch_' . date('Ymd_His'));
  $sendNotif = array_key_exists('send_notif', $options) ? (bool)$options['send_notif'] : true;

  epi_auto_cancel_orders_ensure_schema();

  $nowSql = cek($now);
  $sql = "SELECT o.`order_id`, o.`order_idmember`, o.`order_status`, o.`order_tglorder`, o.`order_updated_at`, p.`page_judul`, p.`page_url`, pc.`id` AS `confirm_id`, pc.`status` AS `confirm_status`
          FROM `sa_order` o
          LEFT JOIN `sa_page` p ON p.`page_id`=o.`order_idproduk`
          LEFT JOIN (SELECT `order_id`, MAX(`id`) AS `max_id` FROM `epi_payment_confirm` GROUP BY `order_id`) pcl ON pcl.`order_id`=o.`order_id`
          LEFT JOIN `epi_payment_confirm` pc ON pc.`id`=pcl.`max_id`
          WHERE o.`order_status`=0
            AND TIMESTAMPDIFF(HOUR, o.`order_tglorder`, '".$nowSql."') >= ".$thresholdHours;
  $rows = db_select($sql);
  if (!is_array($rows)) { $rows = array(); }

  $canceled = 0;
  $skipped = 0;
  $errors = array();

  foreach ($rows as $r) {
    $oid = (int)($r['order_id'] ?? 0);
    $mid = (int)($r['order_idmember'] ?? 0);
    $prevSt = (int)($r['order_status'] ?? 0);
    $confirmId = isset($r['confirm_id']) ? (int)$r['confirm_id'] : 0;
    $confirmStatus = isset($r['confirm_status']) && $r['confirm_status'] !== null ? (int)$r['confirm_status'] : null;
    $confirmIdSql = ($confirmId > 0) ? (string)$confirmId : 'NULL';

    if ($confirmStatus === 0 || $confirmStatus === 1) {
      $reason = 'Invoice memiliki konfirmasi pembayaran; auto-cancel dihentikan';
      @db_query("INSERT INTO `epi_auto_cancel_log` (`order_id`,`member_id`,`action`,`prev_status`,`new_status`,`confirm_id`,`confirm_status`,`reason`,`batch_id`) VALUES (".$oid.",".$mid.",'skip_confirm',".$prevSt.",".$prevSt.",".$confirmId.",".(is_null($confirmStatus)?'NULL':(int)$confirmStatus).",'".cek($reason)."','".cek($batchId)."')");
      @db_query("INSERT INTO `epi_payment_confirm_log` (`confirm_id`,`order_id`,`action`,`message`,`ip`) VALUES (".$confirmIdSql.",".$oid.",'auto_cancel_skip','system: skip auto-cancel due to payment confirm','".cek(realIP())."')");
      $skipped++;
      continue;
    }

    $reason = 'Tidak dibayarkan dalam 48 jam';
    $ok = db_query("UPDATE `sa_order` SET `order_status`=2, `order_cancel_reason`='".cek($reason)."' WHERE `order_id`=".$oid." AND `order_status`=0");
    if ($ok === false) {
      $errors[] = 'Gagal update order #'.$oid.': '.(string)db_error();
      @db_query("INSERT INTO `epi_auto_cancel_log` (`order_id`,`member_id`,`action`,`prev_status`,`new_status`,`confirm_id`,`confirm_status`,`reason`,`batch_id`) VALUES (".$oid.",".$mid.",'error',".$prevSt.",".$prevSt.",".$confirmId.",".(is_null($confirmStatus)?'NULL':(int)$confirmStatus).",'".cek('db_error: '.(string)db_error())."','".cek($batchId)."')");
      continue;
    }

    @db_query("INSERT INTO `epi_auto_cancel_log` (`order_id`,`member_id`,`action`,`prev_status`,`new_status`,`confirm_id`,`confirm_status`,`reason`,`batch_id`) VALUES (".$oid.",".$mid.",'cancel',".$prevSt.",2,".$confirmId.",".(is_null($confirmStatus)?'NULL':(int)$confirmStatus).",'".cek($reason)."','".cek($batchId)."')");
    @db_query("INSERT INTO `epi_payment_confirm_log` (`confirm_id`,`order_id`,`action`,`message`,`ip`) VALUES (".$confirmIdSql.",".$oid.",'auto_cancel','system: pending>canceled after 48h','".cek(realIP())."')");

    if ($sendNotif) {
      $invUrl = rtrim($weburl,'/').'/invoice/'.$oid;
      $prodUrl = isset($r['page_url']) ? (rtrim($weburl,'/').'/order/'.$r['page_url']) : $invUrl;
      $datalain = array(
        'idorder' => (string)$oid,
        'namaproduk' => (string)($r['page_judul'] ?? ''),
        'urlproduk' => (string)$prodUrl,
        'halaman_invoice' => (string)$invUrl,
        'alasan' => $reason
      );
      sa_notif('cancel_order', $mid, $datalain);
    }

    $canceled++;
  }

  return array(
    'ok' => true,
    'batch_id' => $batchId,
    'threshold_hours' => $thresholdHours,
    'checked' => count($rows),
    'canceled' => $canceled,
    'skipped' => $skipped,
    'errors' => $errors
  );
}

if (!defined('EPI_AUTOCANCEL_NO_EXEC')) {
  $res = epi_run_auto_cancel_orders();
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($res);
}
?>
